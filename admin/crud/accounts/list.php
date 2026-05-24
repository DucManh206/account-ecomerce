<?php
require_once __DIR__ . '/accounts.php';
$accounts = getAllAccounts($pdo);
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-header"><h2>Account Shop</h2><span class="sidebar-role">Admin</span></div>
        <nav class="sidebar-nav">
            <a href="../../dashboard.php" class="nav-item"><span>&#x1F3E0;</span><span>Dashboard</span></a>
            <a href="list.php" class="nav-item active"><span>&#x1F4CB;</span><span>Quản lý tài khoản</span></a>
            <a href="../categories/list.php" class="nav-item"><span>&#x1F4C1;</span><span>Danh mục</span></a>
            <hr class="nav-divider">
            <a href="../../../index.php" target="_blank" class="nav-item"><span>&#x1F30D;</span><span>Xem trang chủ</span></a>
            <a href="../../logout.php" class="nav-item nav-logout"><span>&#x1F6AA;</span><span>Đăng xuất</span></a>
        </nav>
    </aside>
    <main class="main-content">
        <header class="topbar"><h1>Quản lý tài khoản</h1><a href="add.php" class="btn btn-primary">+ Thêm tài khoản</a></header>
        <div class="content-body">
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <div class="table-card">
                <table class="data-table">
                    <thead><tr><th>ID</th><th>Tên tài khoản</th><th>Danh mục</th><th>Giá</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead>
                    <tbody>
                    <?php foreach ($accounts as $acc): ?>
                        <tr>
                            <td><?= $acc['id'] ?></td>
                            <td><strong><?= htmlspecialchars($acc['name']) ?></strong></td>
                            <td><?= htmlspecialchars($acc['category_name'] ?? 'Chưa phân loại') ?></td>
                            <td><?= number_format($acc['price'], 0, ',', '.') ?>đ</td>
                            <td><span class="badge <?= $acc['status'] === 'available' ? 'badge-green' : 'badge-red' ?>"><?= $acc['status'] === 'available' ? 'Đang bán' : 'Đã bán' ?></span></td>
                            <td><?= date('d/m/Y', strtotime($acc['created_at'])) ?></td>
                            <td class="actions">
                                <a href="update.php?id=<?= $acc['id'] ?>" class="btn btn-small btn-edit">Sửa</a>
                                <a href="delete.php?id=<?= $acc['id'] ?>" class="btn btn-small btn-delete" onclick="return confirm('Bạn chắc chắn muốn xóa tài khoản này?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$accounts): ?><tr><td colspan="7" class="empty">Chưa có tài khoản nào.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
