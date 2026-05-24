<?php
require_once __DIR__ . '/categories.php';
$categories = getAllCategories($pdo);
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Account Shop</h2>
            <span class="sidebar-role">Admin</span>
        </div>
        <nav class="sidebar-nav">
            <a href="../../dashboard.php" class="nav-item">
                <span class="nav-icon">&#x1F3E0;</span>
                <span>Dashboard</span>
            </a>
            <a href="../accounts/list.php" class="nav-item">
                <span class="nav-icon">&#x1F4CB;</span>
                <span>Quản lý tài khoản</span>
            </a>
            <a href="list.php" class="nav-item active">
                <span class="nav-icon">&#x1F4C1;</span>
                <span>Danh mục</span>
            </a>
            <hr class="nav-divider">
            <a href="../../../index.php" target="_blank" class="nav-item">
                <span class="nav-icon">&#x1F30D;</span>
                <span>Xem trang chủ</span>
            </a>
            <a href="../../logout.php" class="nav-item nav-logout">
                <span class="nav-icon">&#x1F6AA;</span>
                <span>Đăng xuất</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <p>Xin chào, <strong><?= htmlspecialchars($_SESSION['admin_fullname']) ?></strong></p>
        </div>
    </aside>
    
    <main class="main-content">
        <header class="topbar">
            <h1>Quản lý danh mục</h1>
            <a href="add.php" class="btn btn-primary">+ Thêm danh mục</a>
        </header>
        
        <div class="content-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên danh mục</th>
                            <th>Mô tả</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= $cat['id'] ?></td>
                            <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                            <td><?= htmlspecialchars($cat['description'] ?? '') ?></td>
                            <td><?= date('d/m/Y', strtotime($cat['created_at'])) ?></td>
                            <td class="actions">
                                <a href="update.php?id=<?= $cat['id'] ?>" class="btn btn-small btn-edit">Sửa</a>
                                <a href="delete.php?id=<?= $cat['id'] ?>" class="btn btn-small btn-delete" onclick="return confirm('Bạn chắc chắn muốn xóa danh mục này? Các tài khoản thuộc danh mục này sẽ chuyển về trạng thái Chưa phân loại.')">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$categories): ?>
                        <tr>
                            <td colspan="5" class="empty">Chưa có danh mục nào.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
