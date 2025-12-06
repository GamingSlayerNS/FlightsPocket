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

if (!isset($_FILES['hotels-xml']) || $_FILES['hotels-xml']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to upload file.']);
    exit;
}

// Limit file size to 2MB
if ($_FILES['hotels-xml']['size'] > 2 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File size exceeds 2MB limit.']);
    exit;
}

$fileContent = file_get_contents($_FILES['hotels-xml']['tmp_name']);

libxml_use_internal_errors(true);  
$xml = simplexml_load_string($fileContent);

if ($xml === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid XML file.']);
    exit;
}

$mysqli = createMysqli();
$stmt = $mysqli->prepare("INSERT INTO Hotels (HotelID, HotelName, City, PricePerNight) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE HotelName=VALUES(HotelName), City=VALUES(City), PricePerNight=VALUES(PricePerNight)");

$successCount = 0;
$failureCount = 0;
$errors = [];

foreach ($xml->Hotel as $index => $hotel) {
    // Extract values safely
        $hotelID        = (string) $hotel['id'];
        $hotelName      = isset($hotel->hotelName) ? trim((string)$hotel->hotelName) : null;
        $city           = isset($hotel->city) ? trim((string)$hotel->city) : null;
        $pricePerNight  = isset($hotel->pricePerNight) ? trim((string)$hotel->pricePerNight) : null;

    if (empty($hotelID) || empty($hotelName) || empty($city) || empty($pricePerNight)) {
        $errors[] = "Entry $index: Missing required fields.";
        $failureCount++;
        continue;
    }

    // Validate data types and formats
    if (!is_numeric($pricePerNight)) {
        $errors[] = "Entry $index: Invalid price format.";
        $failureCount++;
        continue;
    }

    $stmt->bind_param(
        'isss',
        $hotelID,
        $hotelName,
        $city,
        $pricePerNight
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