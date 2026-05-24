<?php
require_once __DIR__ . '/orders.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: list.php?error=' . urlencode('ID đơn hàng không hợp lệ.'));
    exit;
}

$order = getOrderById($pdo, $id);
if (!$order) {
    header('Location: list.php?error=' . urlencode('Đơn hàng không tồn tại.'));
    exit;
}

if (deleteOrder($pdo, $id)) {
    header('Location: list.php?success=' . urlencode('Xóa lịch sử đơn hàng thành công.'));
    exit;
} else {
    header('Location: list.php?error=' . urlencode('Có lỗi xảy ra khi xóa lịch sử đơn hàng.'));
    exit;
}
?>
