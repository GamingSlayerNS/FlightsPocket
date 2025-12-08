<?php
require_once 'util/authentication.php';
requireAuth();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

$input = file_get_contents('php://input');
$jsonInput = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($jsonInput)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$hotelId = $jsonInput['hotel_id'];
$hotelName = $jsonInput['hotel_name'];
$hotelCity = $jsonInput['hotel_city'];
$numRoomsNeeded = (int)$jsonInput['num_rooms_needed'];

if ($numRoomsNeeded <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'num_rooms_needed must be greater than 0']);
    exit;
}

$xmlFile = __DIR__ . '/../db/hotels.xml';
if (!file_exists($xmlFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Hotels XML file not found']);
    exit;
}

$xml = simplexml_load_file($xmlFile);
if ($xml === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to parse hotels XML file']);
    exit;
}

$hotelIndex = -1;
$hotelFound = null;

foreach ($xml->Hotel as $index => $hotel) {
    $xmlHotelId = (string)$hotel['id'];
    if ($xmlHotelId === $hotelId) {
        $hotelFound = $hotel;
        $hotelIndex = $index;
        break;
    }
}

if ($hotelIndex < 0) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Hotel not found',
        'details' => [
            'hotel_id' => $hotelId,
            'hotel_name' => $hotelName,
            'hotel_city' => $hotelCity
        ]
    ]);
    exit;
}

$currentAvailableRooms = (int)$hotelFound->numAvailableRooms;
if ($numRoomsNeeded > $currentAvailableRooms) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Insufficient available rooms',
        'requested' => $numRoomsNeeded,
        'available' => $currentAvailableRooms
    ]);
    exit;
}

$newAvailableRooms = $currentAvailableRooms - $numRoomsNeeded;
$hotelFound->numAvailableRooms = $newAvailableRooms;

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());

if ($dom->save($xmlFile) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save hotels XML file']);
    exit;
}

// Update hotel-booking.json
$bookingFile = __DIR__ . '/../db/hotel-booking.json';
$bookings = [];

if (file_exists($bookingFile)) {
    $bookingContent = file_get_contents($bookingFile);
    if ($bookingContent !== false) {
        $decoded = json_decode($bookingContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // If it's an array, use it; if it's a single object, convert to array
            if (isset($decoded[0])) {
                $bookings = $decoded;
            } else {
                $bookings = [$decoded];
            }
        }
    }
}

// Add new booking
$bookings[] = $jsonInput;

// Save updated bookings
$bookingJsonData = json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($bookingJsonData === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to encode booking JSON data']);
    exit;
}

$bookingDir = dirname($bookingFile);
if (!is_dir($bookingDir)) {
    mkdir($bookingDir, 0755, true);
}

if (file_put_contents($bookingFile, $bookingJsonData) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write booking file']);
    exit;
}

$mysqli = createMysqli();
$mysqli->begin_transaction();

try {
    // --- PREP DATA ---
    $hotelId = $jsonInput['hotel_id'];
    $bookingId = $jsonInput['booking_number']; // JS already generates it
    $checkIn  = $jsonInput['checkIn_date'];
    $checkOut = $jsonInput['checkOut_date'];
    $numRooms = (int)$jsonInput['num_rooms_needed'];

    // Strip "$" from JS formatted prices, convert to decimal
    $pricePerNight = floatval(str_replace('$','', $jsonInput['price_per_night']));
    $totalPrice    = floatval(str_replace('$','', $jsonInput['total_price']));

    // ------------------------------
    // 1. INSERT INTO HotelBookings
    // ------------------------------
    $stmt = $mysqli->prepare("
        INSERT INTO HotelBookings 
            (HotelBookingID, HotelID, CheckInDate, CheckOutDate, NumberOfRooms, PricePerNight, TotalPrice)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssidd",
        $bookingId,
        $hotelId,
        $checkIn,
        $checkOut,
        $numRooms,
        $pricePerNight,
        $totalPrice
    );
    $stmt->execute();
    $stmt->close();

    // -------------------------------------
    // 2. INSERT GUESTS BASED ON COUNT
    // -------------------------------------
    $stmt = $mysqli->prepare("
        INSERT INTO Guests (SSN, HotelBookingID, FirstName, LastName, DateOfBirth, Category)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    // Your JS payload does NOT include guest details like SSN, names, DOB.
    // So here is an example of how guests *should* be sent:
    //
    // data["guests"] = [
    //   { ssn:"123456789", firstName:"John", lastName:"Doe", dob:"2000-01-01", category:"Adult" }
    // ]
    //
    // If your frontend does NOT send this, you MUST modify JS to include per-guest data.
    //
    if (isset($jsonInput['guests']) && is_array($jsonInput['guests'])) {
        foreach ($jsonInput['guests'] as $g) {
            $stmt->bind_param(
                "ssssss",
                $g['ssn'],
                $bookingId,
                $g['firstName'],
                $g['lastName'],
                $g['dob'],
                $g['type']
            );
            $stmt->execute();
        }
    } else {
        // If no guest details provided — return an error
        throw new Exception("No guests provided");
    }

    $stmt->close();

    // Commit
    $mysqli->commit();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Hotel booked successfully',
        'booking' => $jsonInput,
        'hotel' => [
            'hotel_id' => (string)$hotelFound['id'],
            'hotel_name' => (string)$hotelFound->hotelName,
            'city' => (string)$hotelFound->city,
            'rooms_booked' => $numRoomsNeeded,
            'remaining_rooms' => $newAvailableRooms
        ]
        // 'bookingId' => $bookingId
    ]);
} catch (Exception $ex) {
    $mysqli->rollback();
    error_log("BOOKING ERROR: " . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
}
?>