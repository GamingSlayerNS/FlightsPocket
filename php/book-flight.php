<?php
require_once 'util/authentication.php';
requireAuth();
header('Content-Type: application/json');
require_once 'util/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

$input = file_get_contents('php://input');
$jsonInput = json_decode($input, true);

if (!isset($jsonInput['bookings']) || !is_array($jsonInput['bookings']) || empty($jsonInput['bookings'])) {
    http_response_code(400);
    echo json_encode(['error' => 'A "bookings" array is required.']);
    exit;
}

$bookings = $jsonInput['bookings'];

$userId = isset($jsonInput['userId']) ? $jsonInput['userId'] : '';
$bookingNumber = isset($jsonInput['bookingNumber']) ? $jsonInput['bookingNumber'] : '';
$totalPrice = isset($jsonInput['totalPrice']) ? $jsonInput['totalPrice'] : 0;
$passengers = isset($jsonInput['passengers']) && is_array($jsonInput['passengers']) ? $jsonInput['passengers'] : [];

// Prefer authenticated user id (phone number) over client-provided userId
if (isset($GLOBALS['user']) && is_array($GLOBALS['user']) && !empty($GLOBALS['user']['PhoneNumber'])) {
    $userId = $GLOBALS['user']['PhoneNumber'];
}

// Fallback booking number if not provided
if (empty($bookingNumber)) {
    $bookingNumber = 'B' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

$mysqli = createMysqli();

// Fetch flights from the database
$flightMap = [];
$result = $mysqli->query("SELECT * FROM Flights");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $flightMap[$row['FlightID']] = $row;
    }
    $result->free();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch flights from the database']);
    exit;
}

$validationErrors = [];
$results = [];

foreach ($bookings as $index => $booking) {
    $flightId = $booking['flightId'];
    $passengerCount = $booking['passengerCount'];

    if (!isset($flightMap[$flightId])) {
        $validationErrors[] = "Booking #" . ($index + 1) . ": Flight not found (flightId: {$flightId})";
        continue;
    }

    $flight = $flightMap[$flightId];
    $currentAvailableSeats = (int)$flight['AvailableSeats'];

    if ($passengerCount > $currentAvailableSeats) {
        $validationErrors[] = "Booking #" . ($index + 1) . ": Insufficient available seats for flight {$flightId} (requested: {$passengerCount}, available: {$currentAvailableSeats})";
        continue;
    }

    $results[] = [
        'flightId' => $flightId,
        'passengerCount' => $passengerCount,
        'currentAvailableSeats' => $currentAvailableSeats
    ];
}

if (!empty($validationErrors)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Validation failed',
        'messages' => $validationErrors
    ]);
    exit;
}

$mysqli->begin_transaction();
try {
    foreach ($results as $result) {
        $flightId = $result['flightId'];
        $newAvailableSeats = $result['currentAvailableSeats'] - $result['passengerCount'];

        // Update available seats in the Flights table
        $stmt = $mysqli->prepare("UPDATE Flights SET AvailableSeats = ? WHERE FlightID = ?");
        $stmt->bind_param('ii', $newAvailableSeats, $flightId);
        $stmt->execute();
        $stmt->close();

        // Insert booking into FlightBookings table
        $stmt = $mysqli->prepare("INSERT INTO FlightBookings (FlightID, TotalPrice) VALUES (?, ?)");
        $stmt->bind_param('id', $flightId, $totalPrice);
        $stmt->execute();
        $flightBookingId = $stmt->insert_id;
        $stmt->close();

        // Insert tickets into Tickets table
        foreach ($passengers as $passenger) {
            $stmt = $mysqli->prepare("INSERT INTO Tickets (FlightBookingID, SSN, Price) VALUES (?, ?, ?)");
            $stmt->bind_param('isd', $flightBookingId, $passenger['ssn'], $passenger['price']);
            $stmt->execute();
            $stmt->close();
        }
    }

    $mysqli->commit();
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Flights booked successfully'
    ]);
} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to book flights']);
}
?>
