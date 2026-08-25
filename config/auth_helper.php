<?php
require_once __DIR__ . '/config.php';

// Kiểm tra trạng thái đăng nhập của khách hàng
function is_logged_in() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

// Kiểm tra quyền quản trị viên
function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Bắt buộc đăng nhập
function require_login() {
    if (!is_logged_in()) {
        $returnPath = $_SERVER['REQUEST_URI'] ?? (BASE_PATH . 'index.php');
        header('Location: ' . BASE_PATH . 'login.php?redirect=' . rawurlencode($returnPath));
        exit;
    }
}

// Bắt buộc quyền admin
function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: ' . BASE_PATH . 'login.php');
        exit;
    }
}

// Đăng nhập hệ thống & Tái tạo Session ID chống Session Fixation
function login_user($user) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Tái tạo Session ID để phòng chống Session Fixation Attack
    session_regenerate_id(true);
    
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_fullname'] = $user['fullname'];
    
    // Nếu người dùng có vai trò admin, thiết lập các khóa phiên admin
    if (isset($user['role']) && $user['role'] === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_fullname'] = $user['fullname'];
    }
}
?>
