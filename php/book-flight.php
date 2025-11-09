<?php
header('Content-Type: application/json');

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

$jsonFile = __DIR__ . '/../db/flights.json';

$flights = [];
if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    if ($jsonContent !== false) {
        $decoded = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $flights = $decoded;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to parse flights JSON file']);
            exit;
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to read flights JSON file']);
        exit;
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Flights JSON file not found']);
    exit;
}

$flightMap = [];
foreach ($flights as $index => $flight) {
    if (isset($flight['flightId'])) {
        $flightMap[$flight['flightId']] = $index;
    }
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
    
    $flightIndex = $flightMap[$flightId];
    $currentAvailableSeats = isset($flights[$flightIndex]['availableSeats']) 
        ? (int)$flights[$flightIndex]['availableSeats'] 
        : 0;
    
    if ($passengerCount > $currentAvailableSeats) {
        $validationErrors[] = "Booking #" . ($index + 1) . ": Insufficient available seats for flight {$flightId} (requested: {$passengerCount}, available: {$currentAvailableSeats})";
        continue;
    }
    
    $results[] = [
        'index' => $index,
        'flightIndex' => $flightIndex,
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

$bookingResults = [];
$flightDetails = [];

foreach ($results as $result) {
    $flightIndex = $result['flightIndex'];
    $newAvailableSeats = $result['currentAvailableSeats'] - $result['passengerCount'];
    $flights[$flightIndex]['availableSeats'] = $newAvailableSeats;
    
    $flightDetails[] = $flights[$flightIndex];
    
    $bookingResults[] = [
        'flightId' => $result['flightId'],
        'passengersBooked' => $result['passengerCount'],
        'remainingSeats' => $newAvailableSeats
    ];
}

$jsonData = json_encode($flights, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($jsonData === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to encode JSON data']);
    exit;
}

$dir = dirname($jsonFile);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Write to file
if (file_put_contents($jsonFile, $jsonData) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write to file']);
    exit;
}

// Build booking record
$bookingRecord = [
    'userId' => $userId,
    'bookingNumber' => $bookingNumber,
    'totalPrice' => $totalPrice,
    'flights' => [],
    'passengers' => $passengers
];

if (count($flightDetails) === 1) {
    $bookingRecord['flights']['outbound'] = $flightDetails[0];
} elseif (count($flightDetails) >= 2) {
    $bookingRecord['flights']['outbound'] = $flightDetails[0];
    $bookingRecord['flights']['return'] = $flightDetails[1];
}

$bookingFile = __DIR__ . '/../db/flight-booking.json';
$existingBookings = [];

if (file_exists($bookingFile)) {
    $bookingContent = file_get_contents($bookingFile);
    if ($bookingContent !== false) {
        $decoded = json_decode($bookingContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            if (isset($decoded[0])) {
                $existingBookings = $decoded;
            } else {
                $existingBookings = [$decoded];
            }
        }
    }
}

$existingBookings[] = $bookingRecord;

$bookingJsonData = json_encode($existingBookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Flights booked successfully',
    'bookings' => $bookingResults,
    'totalBookings' => count($bookingResults)
]);
?>
