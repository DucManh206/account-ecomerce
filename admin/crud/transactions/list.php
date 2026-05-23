<?php
require_once __DIR__ . '/../../../admin/crud/layout/admin_layout_modules.php';
require_once __DIR__ . '/transactions.php';

$title = "Danh sách Transactions";
$items = [];
if (function_exists('admin_getTransactionss')) {
    $items = admin_getTransactionss();
} else if (function_exists('get_all_transactions')) {
    $items = get_all_transactions();
}

ob_start();
?>
<div class="page-header">
    <h1 class="page-title"><?php echo $title; ?></h1>
    <a href="add.php" class="nx-btn nx-btn-primary"><i class="fa-solid fa-plus"></i> Thêm mới</a>
</div>

<div class="nx-card">
    <div class="table-responsive">
        <table class="nx-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Thông tin</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>#<?php echo $item['id']; ?></td>
                    <td><?php echo htmlspecialchars($item['name'] ?? $item['title'] ?? $item['username'] ?? 'Item'); ?></td>
                    <td>
                        <a href="update.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-light">Sửa</a>
                        <a href="delete.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa?')">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
render_admin_layout($content, $title);
?>