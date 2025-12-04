<?php
require_once 'util/authentication.php';
require_once 'util/db.php';
requireAuth();

if (!isset($GLOBALS['user']['Admin']) || !$GLOBALS['user']['Admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin privileges required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

if (!isset($_FILES['flights-json']) || $_FILES['flights-json']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to upload file.']);
    exit;
}

// Limit file size to 2MB
if ($_FILES['flights-json']['size'] > 2 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File size exceeds 2MB limit.']);
    exit;
}

$fileContent = file_get_contents($_FILES['flights-json']['tmp_name']);
$flights = json_decode($fileContent, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($flights)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON file.']);
    exit;
}

$mysqli = createMysqli();
$stmt = $mysqli->prepare("INSERT INTO Flights (FlightID, Origin, Destination, DepartureDate, ArrivalDate, DepartureTime, ArrivalTime, AvailableSeats, Price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE Origin=VALUES(Origin), Destination=VALUES(Destination), DepartureDate=VALUES(DepartureDate), ArrivalDate=VALUES(ArrivalDate), DepartureTime=VALUES(DepartureTime), ArrivalTime=VALUES(ArrivalTime), AvailableSeats=VALUES(AvailableSeats), Price=VALUES(Price)");

$successCount = 0;
$failureCount = 0;
$errors = [];

foreach ($flights as $index => $flight) {
    if (!isset($flight['flightId'], $flight['origin'], $flight['destination'], $flight['departureDate'], $flight['arrivalDate'], $flight['departureTime'], $flight['arrivalTime'], $flight['availableSeats'], $flight['price'])) {
        $errors[] = "Entry $index: Missing required fields.";
        $failureCount++;
        continue;
    }

    // Validate data types and formats
    if (!is_string($flight['flightId']) || !is_numeric($flight['availableSeats']) || !is_numeric($flight['price'])) {
        $errors[] = "Entry $index: Invalid data types for fields.";
        $failureCount++;
        continue;
    }

    $stmt->bind_param(
        'sssssssdi',
        $flight['flightId'],
        $flight['origin'],
        $flight['destination'],
        $flight['departureDate'],
        $flight['arrivalDate'],
        $flight['departureTime'],
        $flight['arrivalTime'],
        $flight['availableSeats'],
        $flight['price']
    );

    if ($stmt->execute()) {
        $successCount++;
    } else {
        $errors[] = "Entry $index: Database error - " . $stmt->error;
        $failureCount++;
    }
}

$stmt->close();
$mysqli->close();

$response = [
    'success' => true,
    'message' => 'Flights upload completed.',
    'details' => [
        'successful_entries' => $successCount,
        'failed_entries' => $failureCount,
        'errors' => $errors
    ]
];

http_response_code(200);
echo json_encode($response);
?>