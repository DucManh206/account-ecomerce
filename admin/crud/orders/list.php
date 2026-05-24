<?php
require_once __DIR__ . '/orders.php';
$orders = getAllOrders($pdo);
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    
    <main class="main-content">
        <header class="topbar">
            <h1>Lịch sử giao dịch / Đơn hàng</h1>
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
                            <th>Mã đơn</th>
                            <th>Thành viên mua</th>
                            <th>Tài khoản đã mua</th>
                            <th>Giá mua thực tế</th>
                            <th>Ngày mua</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td>#<?= $ord['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($ord['user_fullname'] ?? 'N/A') ?></strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">@<?= htmlspecialchars($ord['username'] ?? 'N/A') ?></span>
                            </td>
                            <td><?= htmlspecialchars($ord['account_name'] ?? 'Tài khoản đã bị xóa khỏi hệ thống') ?></td>
                            <td style="color: #10b981; font-weight: 700;"><?= number_format($ord['price'], 0, ',', '.') ?>đ</td>
                            <td><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></td>
                            <td class="actions">
                                <a href="delete.php?id=<?= $ord['id'] ?>" class="btn btn-small btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này khỏi lịch sử hệ thống? (Lưu ý: Không hoàn lại tiền hay đổi trạng thái tài khoản)')">Xóa lịch sử</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$orders): ?>
                        <tr>
                            <td colspan="6" class="empty">Chưa có đơn hàng nào được thực hiện.</td>
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
