<?php
require_once __DIR__ . '/accounts.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: list.php?error=' . urlencode('ID tài khoản không hợp lệ.'));
    exit;
}

$account = getAccountById($pdo, $id);
if (!$account) {
    header('Location: list.php?error=' . urlencode('Tài khoản không tồn tại.'));
    exit;
}

if (deleteAccount($pdo, $id)) {
    header('Location: list.php?success=' . urlencode('Xóa tài khoản thành công.'));
    exit;
} else {
    header('Location: list.php?error=' . urlencode('Có lỗi xảy ra khi xóa tài khoản.'));
    exit;
}
?>
