<?php
require_once __DIR__ . '/admin/config/db.php';

require_login();

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Xử lý thông báo từ trang nạp tiền
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

$ordersStmt = $pdo->prepare("
    SELECT orders.*, accounts.name AS account_name, accounts.account_detail, categories.name AS category_name
    FROM orders
    LEFT JOIN accounts ON orders.account_id = accounts.id
    LEFT JOIN categories ON accounts.category_id = categories.id
    WHERE orders.user_id = ?
    ORDER BY orders.id DESC
");
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang cá nhân & Lịch sử mua hàng - Account Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 32px;
            margin-top: 40px;
            margin-bottom: 60px;
        }
        .profile-sidebar-card {
            background-color: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 30px;
            height: fit-content;
        }
        .profile-main-card {
            background-color: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 30px;
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #10b981);
            color: white;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
        }
        .user-info-text {
            text-align: center;
            margin-bottom: 24px;
        }
        .user-info-text h3 {
            font-size: 1.25rem;
            color: var(--text-white);
            font-weight: 700;
        }
        .user-info-text p {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-top: 4px;
        }
        .balance-box {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: var(--radius-sm);
            padding: 16px;
            text-align: center;
            margin-bottom: 30px;
        }
        .balance-box label {
            font-size: 0.85rem;
            color: #34d399;
            font-weight: 600;
            text-transform: uppercase;
        }
        .balance-value {
            font-size: 1.85rem;
            font-weight: 800;
            color: #10b981;
            margin-top: 4px;
        }
        .deposit-section {
            text-align: center;
        }
        .btn-topup-link {
            display: block;
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            padding: 12px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: var(--transition);
        }
        .btn-topup-link:hover {
            background-color: rgba(16, 185, 129, 0.2);
            border-color: #10b981;
        }
        .credential-toggle {
            background: none;
            border: 1px solid rgba(167, 139, 250, 0.3);
            color: #a78bfa;
            padding: 4px 10px;
            font-size: 0.78rem;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 8px;
            transition: var(--transition);
        }
        .credential-toggle:hover {
            background-color: rgba(167, 139, 250, 0.1);
        }
        .credential-hidden {
            display: none;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 16px;
        }
        .order-table th {
            padding: 12px 16px;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--text-gray);
            border-bottom: 1px solid var(--border-color);
        }
        .order-table td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.95rem;
            color: #e5e7eb;
            vertical-align: top;
        }
        .history-credentials {
            background-color: #0f172a;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #34d399;
            white-space: pre-wrap;
            margin-top: 8px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .btn-copy-sm {
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-white);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            margin-top: 6px;
            display: inline-block;
            transition: var(--transition);
        }
        .btn-copy-sm:hover {
            background-color: var(--primary);
        }
        @media (max-width: 900px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>
        function copyText(id, btnId) {
            var el = document.getElementById(id);
            navigator.clipboard.writeText(el.innerText).then(function() {
                var btn = document.getElementById(btnId);
                btn.innerText = "Đã copy!";
                setTimeout(function() {
                    btn.innerText = "Sao chép";
                }, 1500);
            });
        }

        function toggleCredential(orderId) {
            var block = document.getElementById('credentialBlock_' + orderId);
            var btn = block.previousElementSibling;
            if (block.classList.contains('credential-hidden')) {
                block.classList.remove('credential-hidden');
                btn.innerText = 'Ẩn thông tin';
            } else {
                block.classList.add('credential-hidden');
                btn.innerText = 'Xem thông tin đăng nhập';
            }
        }
    </script>
</head>
<body>

    <header class="navbar">
        <div class="container navbar-content">
            <a href="index.php" class="logo">
                AccountShop
            </a>
            <div class="nav-links">
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <a href="admin/dashboard.php" class="btn-nav" style="border-color: #f59e0b; color: #f59e0b !important;">Quản trị viên</a>
                <?php endif; ?>
                <a href="index.php" class="nav-link">Trang chủ</a>
                <a href="topup.php" class="nav-link">Nạp tiền</a>
                <a href="cart.php" class="nav-link">Giỏ hàng</a>
                <a href="logout.php" class="btn-nav" style="background: var(--danger);">Đăng xuất</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if ($success): ?>
            <div class="frontend-alert" style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: #a7f3d0; margin-top: 24px;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="frontend-alert frontend-alert-error" style="margin-top: 24px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <aside class="profile-sidebar-card">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['fullname'], 0, 1)) ?>
                </div>
                <div class="user-info-text">
                    <h3><?= htmlspecialchars($user['fullname']) ?></h3>
                    <p>@<?= htmlspecialchars($user['username']) ?></p>
                </div>

                <div class="balance-box">
                    <label>Số dư tài khoản</label>
                    <div class="balance-value"><?= number_format($user['balance'], 0, ',', '.') ?>đ</div>
                </div>

                <div class="deposit-section">
                    <a href="topup.php" class="btn-topup-link">Nạp tiền tài khoản</a>
                </div>
            </aside>

            <main class="profile-main-card">
                <h2 style="font-size: 1.5rem; color: var(--text-white); font-weight: 700; margin-bottom: 20px;">Lịch sử mua tài khoản</h2>
                
                <?php if (empty($orders)): ?>
                    <div class="empty" style="text-align: center; padding: 40px 0; color: var(--text-gray);">
                        <p style="font-size: 1.1rem; font-style: italic;">Bạn chưa mua tài khoản nào.</p>
                        <a href="index.php" class="tab-btn" style="display: inline-block; margin-top: 16px;">Xem các acc đang bán</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th>Đơn hàng</th>
                                    <th>Tài khoản đã mua</th>
                                    <th>Giá mua</th>
                                    <th>Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($orders as $ord): ?>
                                <tr>
                                    <td>#<?= $ord['id'] ?></td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-white);"><?= htmlspecialchars($ord['account_name'] ?? 'Sản phẩm đã bị xóa') ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Danh mục: <?= htmlspecialchars($ord['category_name'] ?? 'Chưa phân loại') ?></div>
                                        
                                        <?php if (!empty($ord['account_detail'])): ?>
                                            <div style="margin-top: 10px;">
                                                <button class="credential-toggle" onclick="toggleCredential(<?= $ord['id'] ?>)">Xem thông tin đăng nhập</button>
                                                <div id="credentialBlock_<?= $ord['id'] ?>" class="credential-hidden">
                                                    <div id="credential_<?= $ord['id'] ?>" class="history-credentials"><?= htmlspecialchars($ord['account_detail']) ?></div>
                                                    <button id="copyBtn_<?= $ord['id'] ?>" onclick="copyText('credential_<?= $ord['id'] ?>', 'copyBtn_<?= $ord['id'] ?>')" class="btn-copy-sm">Sao chép</button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #34d399; font-weight: 700;"><?= number_format($ord['price'], 0, ',', '.') ?>đ</td>
                                    <td style="font-size: 0.85rem; color: var(--text-gray);"><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <footer>
        <div class="container footer-content">
            <p>&copy; 2026 AccountShop - Nhóm 5. Bài tập lớn Lập trình web và ứng dụng.</p>
        </div>
    </footer>

</body>
</html>
