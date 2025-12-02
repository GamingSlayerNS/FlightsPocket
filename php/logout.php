<?php
require_once 'util/authentication.php';
require_once 'util/db.php';

$cookieName = 'auth_token';

if (!isset($_COOKIE[$cookieName]) || empty($_COOKIE[$cookieName])) {
    // Nothing to do - redirect to login
    header('Location: /login.php');
    exit;
}

$token = $_COOKIE[$cookieName];

$mysqli = createMysqli();
if ($mysqli && !$mysqli->connect_errno) {
    $stmt = $mysqli->prepare("DELETE FROM Sessions WHERE Token = ?");
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
    }
    $mysqli->close();
}

// Clear cookie
setcookie($cookieName, '', time() - 3600, '/');

// Redirect to login page
header('Location: /login.php');
exit;
?>