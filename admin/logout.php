<?php
// Chuyển hướng trang đăng xuất admin về trang đăng xuất chung của hệ thống
require_once __DIR__ . '/config/config.php';
header('Location: ' . BASE_PATH . 'logout.php');
exit;
