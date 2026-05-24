<?php
require_once __DIR__ . '/config.php';

/**
 * Check if any user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

/**
 * Check if an admin is logged in
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require normal user login, redirect if not authenticated
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_PATH . 'login.php');
        exit;
    }
}

/**
 * Require admin login, redirect if not authenticated
 */
function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: ' . BASE_PATH . 'admin/login.php');
        exit;
    }
}

/**
 * Unified login handler to prevent session key inconsistency.
 * Sets appropriate session keys for normal users and admin users alike.
 */
function login_user($user) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set standard user session keys
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_fullname'] = $user['fullname'];
    
    // If the user has an admin role, also establish the admin session keys
    if (isset($user['role']) && $user['role'] === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_fullname'] = $user['fullname'];
    }
}
?>
