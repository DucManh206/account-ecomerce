<?php
require_once __DIR__ . '/users.php';
$users = getAllUsers($pdo);
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thành viên - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    
    <main class="main-content">
        <header class="topbar">
            <h1>Quản lý thành viên (Khách hàng)</h1>
            <a href="add.php" class="btn btn-primary">+ Thêm thành viên</a>
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
                            <th>Tên đăng nhập</th>
                            <th>Họ và tên</th>
                            <th>Vai trò</th>
                            <th>Số dư (balance)</th>
                            <th>Ngày đăng ký</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><?= htmlspecialchars($u['fullname']) ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge" style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid #f59e0b; color: #f59e0b; padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; font-weight: 600; display: inline-block;">Quản trị viên</span>
                                <?php else: ?>
                                    <span class="badge" style="background-color: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; color: #3b82f6; padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; font-weight: 600; display: inline-block;">Khách hàng</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #10b981; font-weight: 700;"><?= number_format($u['balance'], 0, ',', '.') ?>đ</td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td class="actions">
                                <a href="update.php?id=<?= $u['id'] ?>" class="btn btn-small btn-edit">Sửa / Nạp tiền</a>
                                <a href="delete.php?id=<?= $u['id'] ?>" class="btn btn-small btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa thành viên này? Tất cả dữ liệu đơn hàng liên quan cũng sẽ bị xóa.')">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$users): ?>
                        <tr>
                            <td colspan="6" class="empty">Chưa có thành viên nào đăng ký.</td>
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
