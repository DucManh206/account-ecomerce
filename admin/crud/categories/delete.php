<?php
require_once __DIR__ . '/categories.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: list.php?error=' . urlencode('ID danh mục không hợp lệ.'));
    exit;
}

$category = getCategoryById($pdo, $id);
if (!$category) {
    header('Location: list.php?error=' . urlencode('Danh mục không tồn tại.'));
    exit;
}

if (deleteCategory($pdo, $id)) {
    header('Location: list.php?success=' . urlencode('Xóa danh mục thành công.'));
    exit;
} else {
    header('Location: list.php?error=' . urlencode('Có lỗi xảy ra khi xóa danh mục.'));
    exit;
}
?>
