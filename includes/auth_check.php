<?php
// includes/auth_check.php - Session and Role Based Access Control

if (session_status() === PHP_SESSION_NONE) {
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
        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => $_SESSION['user_role'] ?? 'renter',
            'phone' => $_SESSION['user_phone'] ?? ''
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
