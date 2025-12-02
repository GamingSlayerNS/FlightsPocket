<?php
require_once 'util/authentication.php';
requireAuth();
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
$firstName = trim($data['firstName'] ?? '');
$lastName = trim($data['lastName'] ?? '');
$phoneNumber = trim($data['phoneNumber'] ?? '');
$dateOfBirth = $data['dateOfBirth'] ?? '';
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$gender = isset($data['gender']) ? trim($data['gender']) : null;

if (empty($firstName)) {
    $errors[] = 'First Name is required.';
}
if (empty($lastName)) {
    $errors[] = 'Last Name is required.';
}
if (empty($phoneNumber)) {
    $errors[] = 'Phone Number is required.';
}
if (empty($dateOfBirth)) {
    $errors[] = 'Date of Birth is required.';
}
if (empty($email)) {
    $errors[] = 'Email is required.';
}
if (empty($password)) {
    $errors[] = 'Password is required.';
}

if (count($errors) > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$socket = getenv('MYSQL_SOCKET');
if ($socket === false) {
    $socket = null;
}

$mysqli = new mysqli(
    hostname: null,
    username: 'root',
    password: '',
    database: 'flightspocket',
    port: 0,
    socket: $socket
);

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to connect to database: ' . $mysqli->connect_error,
    ]);
    exit;
}

$stmt = $mysqli->prepare("SELECT PhoneNumber FROM Users WHERE PhoneNumber = ?");
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

if ($result->num_rows > 0) {
    $stmt->close();
    $mysqli->close();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Phone number is already registered. Please use a different phone number.',
    ]);
    exit;
}
$stmt->close();

// Convert date from MM/DD/YYYY to YYYY-MM-DD because that is what mysqli expects
$dateParts = explode('/', $dateOfBirth);
if (count($dateParts) !== 3) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid date format. Expected MM/DD/YYYY.',
    ]);
    $mysqli->close();
    exit;
}

$month = $dateParts[0];
$day = $dateParts[1];
$year = $dateParts[2];
$mysqlDate = $year . '-' . $month . '-' . $day;

if (!checkdate((int)$month, (int)$day, (int)$year)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid date of birth.',
    ]);
    $mysqli->close();
    exit;
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("INSERT INTO Users (PhoneNumber, Password, FirstName, LastName, DateOfBirth, Gender, Email) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database query preparation failed: ' . $mysqli->error,
    ]);
    $mysqli->close();
    exit;
}

$stmt->bind_param("sssssss", $phoneNumber, $hashedPassword, $firstName, $lastName, $mysqlDate, $gender, $email);

if (!$stmt->execute()) {
    // Check if error is due to duplicate phone number (shouldn't happen, but just in case)
    if ($mysqli->errno === 1062) {
        $stmt->close();
        $mysqli->close();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Phone number is already registered. Please use a different phone number.',
        ]);
        exit;
    }
    
    $stmt->close();
    $mysqli->close();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to register user: ' . $mysqli->error,
    ]);
    exit;
}

$stmt->close();
$mysqli->close();

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Registered successfully',
]);
?>

