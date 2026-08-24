<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

require_login();

$userId = $_SESSION['user_id'];

// Xử lý thông báo từ query URL cũ nếu có
if (isset($_GET['success'])) {
    set_flash('success', $_GET['success']);
}

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_change_password'])) {
    verify_csrf();

    $oldPassword = trim($_POST['old_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        set_flash('error', 'Vui lòng điền đầy đủ thông tin để đổi mật khẩu.');
    } elseif ($newPassword !== $confirmPassword) {
        set_flash('error', 'Mật khẩu mới và xác nhận mật khẩu không khớp.');
    } elseif (strlen($newPassword) < 6) {
        set_flash('error', 'Mật khẩu mới phải từ 6 ký tự trở lên.');
    } else {
        $userQuery = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $userQuery->execute([$userId]);
        $currUser = $userQuery->fetch();

        if ($currUser && (password_verify($oldPassword, $currUser['password']) || $currUser['password'] === md5($oldPassword))) {
            $newHashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $updatePwStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($updatePwStmt->execute([$newHashed, $userId])) {
                set_flash('success', 'Đổi mật khẩu thành công! Hãy ghi nhớ mật khẩu mới của bạn.');
                header('Location: profile.php');
                exit;
            } else {
                set_flash('error', 'Có lỗi xảy ra khi cập nhật mật khẩu.');
            }
        } else {
            set_flash('error', 'Mật khẩu hiện tại không chính xác.');
        }
    }
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

$pageTitle = 'Trang cá nhân & Lịch sử mua hàng - Account Shop';
$extraCss = '
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 32px;
        margin-top: 40px;
        margin-bottom: 60px;
    }
    .profile-sidebar-card, .profile-main-card {
        background-color: var(--bg-card);
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
    .balance-box {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.3);
        border-radius: var(--radius-sm);
        padding: 16px;
        text-align: center;
        margin-bottom: 24px;
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
    @media (max-width: 900px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }
';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

    <div class="container" style="min-height: 70vh;">
        <?= render_flash() ?>

        <div class="profile-grid">
            <aside class="profile-sidebar-card" style="height: fit-content;">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['fullname'] ?? 'U', 0, 1)) ?>
                </div>
                <div style="text-align: center; margin-bottom: 24px;">
                    <h3 style="font-size: 1.25rem; color: var(--text-white); font-weight: 700;"><?= htmlspecialchars($user['fullname'] ?? '') ?></h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-top: 4px;">@<?= htmlspecialchars($user['username'] ?? '') ?></p>
                </div>

                <div class="balance-box">
                    <label style="font-size: 0.85rem; color: #34d399; font-weight: 600; text-transform: uppercase;">Số dư tài khoản</label>
                    <div style="font-size: 1.85rem; font-weight: 800; color: #10b981; margin-top: 4px;"><?= number_format($user['balance'] ?? 0, 0, ',', '.') ?>đ</div>
                </div>

                <div style="margin-bottom: 24px;">
                    <a href="topup.php" class="btn-buy" style="display: block; text-align: center; text-decoration: none;">+ Nạp tiền tài khoản</a>
                </div>

                <hr style="border: none; border-top: 1px solid var(--border-color); margin: 24px 0;">

                <!-- Form đổi mật khẩu -->
                <div>
                    <h4 style="font-size: 1rem; color: var(--text-white); font-weight: 700; margin-bottom: 16px;">Đổi mật khẩu</h4>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action_change_password" value="1">
                        
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 0.8rem; color: var(--text-gray); display: block; margin-bottom: 4px;">Mật khẩu hiện tại</label>
                            <input type="password" name="old_password" required placeholder="Nhập mật khẩu cũ" style="width: 100%; padding: 8px 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-white); outline: none;">
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 0.8rem; color: var(--text-gray); display: block; margin-bottom: 4px;">Mật khẩu mới</label>
                            <input type="password" name="new_password" required placeholder="Tối thiểu 6 ký tự" style="width: 100%; padding: 8px 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-white); outline: none;">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="font-size: 0.8rem; color: var(--text-gray); display: block; margin-bottom: 4px;">Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới" style="width: 100%; padding: 8px 12px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-white); outline: none;">
                        </div>

                        <button type="submit" class="tab-btn" style="width: 100%; text-align: center; border-radius: var(--radius-sm);">Cập nhật mật khẩu</button>
                    </form>
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
                                                    <button id="copyBtn_<?= $ord['id'] ?>" onclick="copyText('credential_<?= $ord['id'] ?>', 'copyBtn_<?= $ord['id'] ?>')" class="btn-copy-sm" style="background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: var(--text-white); padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer; margin-top: 6px;">Sao chép</button>
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

    <script>
        function copyText(id, btnId) {
            var el = document.getElementById(id);
            if (!el) return;
            navigator.clipboard.writeText(el.innerText).then(function() {
                var btn = document.getElementById(btnId);
                if (btn) {
                    btn.innerText = "Đã copy!";
                    setTimeout(function() {
                        btn.innerText = "Sao chép";
                    }, 1500);
                }
            });
        }

        function toggleCredential(orderId) {
            var block = document.getElementById('credentialBlock_' + orderId);
            if (!block) return;
            var btn = block.previousElementSibling;
            if (block.classList.contains('credential-hidden')) {
                block.classList.remove('credential-hidden');
                if (btn) btn.innerText = 'Ẩn thông tin';
            } else {
                block.classList.add('credential-hidden');
                if (btn) btn.innerText = 'Xem thông tin đăng nhập';
            }
        }
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
