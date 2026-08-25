<?php
require_once __DIR__ . '/accounts.php';
require_once __DIR__ . '/../../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit;
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$account = getAccountById($pdo, $id);
if (!$account) {
    set_flash('error', 'Sản phẩm không tồn tại.');
    header('Location: list.php');
    exit;
}

$orderStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE account_id = ?');
$orderStmt->execute([$id]);
if ((int) $orderStmt->fetchColumn() > 0) {
    set_flash('error', 'Không thể xóa sản phẩm đã phát sinh đơn hàng. Hãy ẩn sản phẩm để giữ lịch sử giao dịch.');
    header('Location: list.php');
    exit;
}

try {
    record_admin_activity($pdo, 'account_deleted', 'account', $id, 'Xóa sản phẩm chưa bán: ' . $account['name']);
    deleteAccount($pdo, $id);
    set_flash('success', 'Đã xóa sản phẩm chưa phát sinh đơn hàng.');
} catch (Throwable $e) {
    error_log('delete account: ' . $e->getMessage());
    set_flash('error', 'Không thể xóa sản phẩm này.');
}
header('Location: list.php');
exit;
