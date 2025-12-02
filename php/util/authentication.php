<?php
function requireAuth() {
    $cookieName = 'auth_token';
    // Check if the authentication cookie exists and is not empty
    if (!isset($_COOKIE[$cookieName]) || empty($_COOKIE[$cookieName])) {
        // Redirect to login page
        header('Location: /login.php');
        exit;
    }
}
