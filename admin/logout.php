<?php
// ============================================================
// Đăng xuất - Admin
// ============================================================
require_once __DIR__ . '/config/config.php';

// Xóa toàn bộ session
$_SESSION = [];
session_destroy();

// Chuyển về trang đăng nhập
header('Location: login.php');
exit;
