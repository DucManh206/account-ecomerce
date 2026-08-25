<?php
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/../../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit;
}
verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$category = getCategoryById($pdo, $id);
if (!$category) {
    set_flash('error', 'Danh mục không tồn tại.');
    header('Location: list.php');
    exit;
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE category_id = ?');
$countStmt->execute([$id]);
if ((int) $countStmt->fetchColumn() > 0) {
    set_flash('error', 'Danh mục vẫn còn sản phẩm. Hãy chuyển sản phẩm sang danh mục khác trước khi xóa.');
    header('Location: list.php');
    exit;
}

try {
    deleteCategory($pdo, $id);
    record_admin_activity($pdo, 'category_deleted', 'category', $id, 'Xóa danh mục: ' . $category['name']);
    set_flash('success', 'Đã xóa danh mục.');
} catch (Throwable $e) {
    set_flash('error', 'Không thể xóa danh mục này.');
}
header('Location: list.php');
exit;
