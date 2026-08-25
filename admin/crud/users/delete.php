<?php
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/../../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit;
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$user = getUserById($pdo, $id);
if (!$user) {
    set_flash('error', 'Thành viên không tồn tại.');
    header('Location: list.php');
    exit;
}
if ($id === (int) $_SESSION['admin_user_id']) {
    set_flash('error', 'Bạn không thể xóa tài khoản đang đăng nhập.');
    header('Location: list.php');
    exit;
}

$relationStmt = $pdo->prepare(
    'SELECT
        (SELECT COUNT(*) FROM orders WHERE user_id = ?) +
        (SELECT COUNT(*) FROM topup_requests WHERE user_id = ?) +
        (SELECT COUNT(*) FROM balance_transactions WHERE user_id = ?) AS related_count'
);
$relationStmt->execute([$id, $id, $id]);
if ((int) $relationStmt->fetchColumn() > 0) {
    set_flash('error', 'Không thể xóa thành viên đã phát sinh giao dịch. Hãy dùng chức năng tạm khóa để giữ toàn vẹn dữ liệu.');
    header('Location: list.php');
    exit;
}

try {
    record_admin_activity($pdo, 'user_deleted', 'user', $id, 'Xóa tài khoản chưa phát sinh dữ liệu @' . $user['username']);
    deleteUser($pdo, $id);
    set_flash('success', 'Đã xóa tài khoản chưa phát sinh dữ liệu.');
} catch (Throwable $e) {
    error_log('delete user: ' . $e->getMessage());
    set_flash('error', 'Không thể xóa tài khoản này.');
}
header('Location: list.php');
exit;
