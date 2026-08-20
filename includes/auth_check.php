<?php
// includes/auth_check.php - Session, Cyber Security Hardening & Role Based Access Control

// Cyber Defense: HTTP Security Headers
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

// Cyber Defense: Secure Cookie Flags (HttpOnly & SameSite)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user data
 */
function current_user() {
    if (is_logged_in()) {
        global $pdo;
        $is_verified = 0;
        if (isset($_SESSION['user_is_verified'])) {
            $is_verified = (int)$_SESSION['user_is_verified'];
        } elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'owner' && isset($pdo)) {
            try {
                $st = $pdo->prepare("SELECT is_verified FROM owners WHERE id = ?");
                $st->execute([$_SESSION['user_id']]);
                $is_verified = (int)$st->fetchColumn();
                $_SESSION['user_is_verified'] = $is_verified;
            } catch (Exception $e) {}
        }

        $avatar = $_SESSION['user_avatar'] ?? '';
        if (empty($avatar) && isset($pdo)) {
            $role = $_SESSION['user_role'] ?? 'renter';
            $tbl = ($role === 'owner') ? 'owners' : (($role === 'admin') ? 'admins' : 'renters');
            try {
                $st = $pdo->prepare("SELECT avatar FROM $tbl WHERE id = ?");
                $st->execute([$_SESSION['user_id']]);
                $avatar = $st->fetchColumn() ?: '';
                $_SESSION['user_avatar'] = $avatar;
            } catch (Exception $e) {}
        }

        return [
            'id'          => $_SESSION['user_id'],
            'name'        => $_SESSION['user_name'] ?? 'User',
            'email'       => $_SESSION['user_email'] ?? '',
            'role'        => $_SESSION['user_role'] ?? 'renter',
            'phone'       => $_SESSION['user_phone'] ?? '',
            'avatar'      => $avatar,
            'is_verified' => $is_verified
        ];
    }
    return null;
}

/**
 * Get user role ('renter', 'owner', 'admin', or null)
 */
function user_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Require login to access a page
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_message'] = [
            'type' => 'warning',
            'message' => 'Please log in to access this page.'
        ];
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Require Owner role (or Admin)
 */
function require_owner() {
    require_login();
    if (user_role() !== 'owner' && user_role() !== 'admin') {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Access denied: Owner privileges are required to view this page.'
        ];
        header("Location: index.php");
        exit;
    }
}

/**
 * Require Admin role
 */
function require_admin() {
    require_login();
    if (user_role() !== 'admin') {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Access denied: Administrator privileges required.'
        ];
        header("Location: index.php");
        exit;
    }
}

/**
 * Require Renter role
 */
function require_renter() {
    require_login();
    if (user_role() !== 'renter' && user_role() !== 'admin') {
        $_SESSION['flash_message'] = [
            'type' => 'info',
            'message' => 'Renter privileges required.'
        ];
        header("Location: index.php");
        exit;
    }
}
