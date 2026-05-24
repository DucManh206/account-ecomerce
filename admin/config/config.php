<?php
// ============================================================
// Cấu hình ứng dụng - Shop bán tài khoản
// ============================================================

// Tên trang web
define('SITE_NAME', 'Account Shop - Nhóm 5');

// Đường dẫn gốc
define('BASE_URL', 'http://localhost');

// Bắt đầu session (dùng chung cho toàn bộ admin)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Báo lỗi (tắt khi production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
