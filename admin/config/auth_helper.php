<?php
require_once __DIR__ . '/config.php';

// kiểm tra đăng nhập
function is_logged_in() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}


function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// yêu cầu đăng nhập
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_PATH . 'login.php');
        exit;
    }
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: ' . BASE_PATH . 'admin/login.php');
        exit;
    }
}

// đăng nhập hệ thống
function login_user($user) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_fullname'] = $user['fullname'];
    
    // Nếu người dùng có vai trò admin, cũng thiết lập các khóa phiên admin
    if (isset($user['role']) && $user['role'] === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_fullname'] = $user['fullname'];
    }
}
?>
