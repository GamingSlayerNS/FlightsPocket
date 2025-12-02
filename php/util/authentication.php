<?php
require_once __DIR__ . '/db.php';

function requireAuth() {
    $cookieName = 'auth_token';
    
    // Check if cookie exists
    if (!isset($_COOKIE[$cookieName]) || empty($_COOKIE[$cookieName])) {
        header('Location: /login.php');
        exit;
    }

    $token = $_COOKIE[$cookieName];
    $mysqli = createMysqli();
    
    if ($mysqli->connect_errno) {
        header('Location: /login.php');
        exit;
    }

    $stmt = $mysqli->prepare("SELECT User, Expiration FROM Sessions WHERE Token = ? AND Expiration > NOW()");
    if (!$stmt) {
        $mysqli->close();
        header('Location: /login.php');
        exit;
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If no active session found, redirect to login
    if ($result->num_rows === 0) {
        $stmt->close();
        $mysqli->close();
        header('Location: /login.php');
        exit;
    }

    // Session is valid
    $stmt->close();
    $mysqli->close();
}

function createSession($phoneNumber) {
    $token = bin2hex(random_bytes(16));
    $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $mysqli = createMysqli();
    
    if ($mysqli->connect_errno) {
        return false;
    }

    // ON DUPLICATE KEY UPDATE handles existing sessions
    $stmt = $mysqli->prepare("INSERT INTO Sessions (User, Token, Expiration) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Token = ?, Expiration = ?");
    if (!$stmt) {
        $mysqli->close();
        return false;
    }

    $stmt->bind_param("sssss", $phoneNumber, $token, $expiration, $token, $expiration);
    
    if (!$stmt->execute()) {
        $stmt->close();
        $mysqli->close();
        return false;
    }

    $stmt->close();
    $mysqli->close();
    
    setcookie('auth_token', $token, time() + (24 * 60 * 60), '/');
    
    return true;
}
