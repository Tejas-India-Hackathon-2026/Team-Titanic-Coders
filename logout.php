<?php
// logout.php - Session Termination
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Completely wipe all session data
$_SESSION = [];
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_role']);
unset($_SESSION['user_phone']);
unset($_SESSION['user_is_verified']);

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Start fresh session just for the flash alert
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash_message('success', 'You have been successfully signed out.');
header("Location: login.php");
exit;
