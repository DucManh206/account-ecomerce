<?php
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/../../../includes/flash.php';

set_flash('error', 'Lịch sử đơn hàng là dữ liệu tài chính và không thể xóa trực tiếp.');
header('Location: list.php');
exit;
