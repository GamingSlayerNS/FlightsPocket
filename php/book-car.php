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

$carId = $jsonInput['carId'];
$city = $jsonInput['city'];
$type = $jsonInput['type'];
$checkInDate = $jsonInput['checkIn_date'];
$checkOutDate = $jsonInput['checkOut_date'];

if (empty($carId)) {
    http_response_code(400);
    echo json_encode(['error' => 'carId is required']);
    exit;
}

if (empty($checkInDate) || empty($checkOutDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'checkIn_date and checkOut_date are required']);
    exit;
}

$xmlFile = __DIR__ . '/../db/rental_cars.xml';
if (!file_exists($xmlFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Rental cars XML file not found']);
    exit;
}

$xml = simplexml_load_file($xmlFile);
if ($xml === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to parse rental cars XML file']);
    exit;
}

$carFound = null;
$carIndex = -1;

foreach ($xml->Car as $index => $car) {
    $xmlCarId = (string)$car['id'];
    if ($xmlCarId === $carId) {
        $carFound = $car;
        $carIndex = $index;
        break;
    }
}

if ($carIndex < 0 || $carFound === null) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Car not found',
        'details' => [
            'carId' => $carId,
            'city' => $city,
            'type' => $type
        ]
    ]);
    exit;
}

$carFound->checkInDate = $checkInDate;
$carFound->checkOutDate = $checkOutDate;

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());

if ($dom->save($xmlFile) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save rental cars XML file']);
    exit;
}

// Update car-booking.json
$bookingFile = __DIR__ . '/../db/car-booking.json';
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

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Car booked successfully',
    'booking' => $jsonInput,
    'car' => [
        'carId' => (string)$carFound['id'],
        'city' => (string)$carFound->city,
        'type' => (string)$carFound->type,
        'pricePerDay' => (string)$carFound->pricePerDay
    ]
]);
?>

