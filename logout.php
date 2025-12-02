<?php
// Remove the auth cookie by setting it to expire in the past
setcookie('auth_token', '', time() - 3600, '/');
header('Location: /login.php');
exit;
?>
