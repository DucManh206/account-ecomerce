<?php
require_once __DIR__ . '/users.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: list.php?error=' . urlencode('ID thành viên không hợp lệ.'));
    exit;
}

$user = getUserById($pdo, $id);
if (!$user) {
    header('Location: list.php?error=' . urlencode('Thành viên không tồn tại.'));
    exit;
}

if (deleteUser($pdo, $id)) {
    header('Location: list.php?success=' . urlencode('Xóa thành viên thành công.'));
    exit;
} else {
    header('Location: list.php?error=' . urlencode('Có lỗi xảy ra khi xóa thành viên.'));
    exit;
}
?>
