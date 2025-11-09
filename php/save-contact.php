<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

$firstName = isset($_POST['first-name']) ? trim($_POST['first-name']) : '';
$lastName = isset($_POST['last-name']) ? trim($_POST['last-name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

$errors = [];
if (empty($firstName)) {
    $errors[] = 'First name is required.';
}
if (empty($lastName)) {
    $errors[] = 'Last name is required.';
}
if (empty($phone)) {
    $errors[] = 'Phone number is required.';
}
if (empty($gender)) {
    $errors[] = 'Gender is required.';
}
if (empty($email)) {
    $errors[] = 'Email is required.';
}
if (empty($comment)) {
    $errors[] = 'Comment is required.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => 'Validation failed', 'messages' => $errors]);
    exit;
}

$contact = [
    'firstname' => $firstName,
    'lastname' => $lastName,
    'phoneNum' => $phone,
    'gender' => $gender,
    'email' => $email,
    'comment' => $comment
];

// Relative to this file
$jsonFile = __DIR__ . '/../db/contact-file.json';

// Read existing contacts
$contacts = [];
if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    if ($jsonContent !== false) {
        $decoded = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $contacts = $decoded;
        }
    }
}

// Append new contact
$contacts[] = $contact;

$jsonData = json_encode($contacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($jsonData === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to encode JSON data']);
    exit;
}

$dir = dirname($jsonFile);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

if (file_put_contents($jsonFile, $jsonData) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write to file']);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Contact saved successfully',
    'contact' => $contact
]);
?>

