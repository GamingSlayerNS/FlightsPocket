<?php
require_once 'util/authentication.php';
require_once 'util/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$errors = [];
$phoneNumber = trim($data['phoneNumber'] ?? '');
$password = $data['password'] ?? '';

if (empty($phoneNumber)) {
    $errors[] = 'Phone Number is required.';
}
if (empty($password)) {
    $errors[] = 'Password is required.';
}

if (count($errors) > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$mysqli = createMysqli();

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to connect to database: ' . $mysqli->connect_error,
    ]);
    exit;
}

// Check if user exists and verify password
$stmt = $mysqli->prepare("SELECT PhoneNumber, Password FROM Users WHERE PhoneNumber = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database query preparation failed: ' . $mysqli->error,
    ]);
    $mysqli->close();
    exit;
}

$stmt->bind_param("s", $phoneNumber);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $mysqli->close();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid phone number.',
    ]);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
$mysqli->close();

// Verify password
if (!password_verify($password, $user['Password'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Incorrect password.',
    ]);
    exit;
}

// Create session and set cookie
if (!createSession($phoneNumber)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create session.',
    ]);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
]);
?>

