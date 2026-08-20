<?php
// logout.php - Guaranteed Complete Session Destruction
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Clear and unset all session variables
$_SESSION = [];
session_unset();

// 2. Invalidate the session cookie in browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 86400,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy session storage on server
session_destroy();

// 4. Redirect to login with explicit logged_out parameter
header("Location: login.php?logged_out=1");
exit;
