<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';
require_login();

$userId = (int) $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_change_password'])) {
    verify_csrf();
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        set_flash('error', 'Mật khẩu mới cần ít nhất 8 ký tự.');
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        set_flash('error', 'Xác nhận mật khẩu mới không khớp.');
    } else {
        $passwordStmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $passwordStmt->execute([$userId]);
        $currentHash = $passwordStmt->fetchColumn();
        if (!$currentHash || !password_verify($oldPassword, $currentHash)) {
            set_flash('error', 'Mật khẩu hiện tại không chính xác.');
        } else {
            $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $updateStmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $userId]);
            set_flash('success', 'Đã đổi mật khẩu.');
        }
    }
    header('Location: profile.php');
    exit;
}

$userStmt = $pdo->prepare(
    'SELECT u.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.price), 0) AS total_spent, MAX(o.created_at) AS last_order_at
     FROM users u LEFT JOIN orders o ON o.user_id = u.id WHERE u.id = ? GROUP BY u.id'
);
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

$ordersStmt = $pdo->prepare(
    'SELECT o.*, a.name AS account_name, a.account_detail, c.name AS category_name
     FROM orders o
     LEFT JOIN accounts a ON a.id = o.account_id
     LEFT JOIN categories c ON c.id = a.category_id
     WHERE o.user_id = ? ORDER BY o.id DESC'
);
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();

$ledgerStmt = $pdo->prepare(
    'SELECT * FROM balance_transactions WHERE user_id = ? ORDER BY id DESC LIMIT 20'
);
$ledgerStmt->execute([$userId]);
$ledger = $ledgerStmt->fetchAll();

$pageTitle = 'Tài khoản của tôi - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main id="main-content" class="profile-page">
    <div class="container">
        <?= render_flash() ?>

        <header class="profile-hero">
            <div class="profile-identity">
                <span class="profile-monogram"><?= htmlspecialchars(mb_strtoupper(mb_substr($user['fullname'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                <div><span class="section-kicker">Tài khoản thành viên</span><h1><?= htmlspecialchars($user['fullname']) ?></h1><p>@<?= htmlspecialchars($user['username']) ?> · tham gia <?= date('m/Y', strtotime($user['created_at'])) ?></p></div>
            </div>
            <div class="profile-balance"><span>Số dư khả dụng</span><strong><?= number_format($user['balance'], 0, ',', '.') ?>đ</strong><a href="topup.php">Nạp thêm số dư →</a></div>
        </header>

        <section class="profile-stats" aria-label="Thống kê tài khoản">
            <div><span>Đơn đã mua</span><strong><?= number_format($user['order_count']) ?></strong></div>
            <div><span>Tổng thanh toán</span><strong><?= number_format($user['total_spent'], 0, ',', '.') ?>đ</strong></div>
            <div><span>Lần mua gần nhất</span><strong><?= $user['last_order_at'] ? date('d/m/Y', strtotime($user['last_order_at'])) : 'Chưa có' ?></strong></div>
        </section>

        <div class="profile-layout">
            <section class="profile-orders">
                <div class="profile-section-heading"><div><span class="section-kicker">Sản phẩm của bạn</span><h2>Lịch sử mua hàng</h2></div><a href="index.php">Mua thêm</a></div>
                <?php if (!$orders): ?>
                    <div class="profile-empty"><h3>Bạn chưa mua tài khoản nào</h3><p>Sản phẩm đã thanh toán sẽ xuất hiện ở đây kèm thông tin đăng nhập.</p><a href="index.php" class="tab-btn">Xem cửa hàng</a></div>
                <?php else: ?>
                    <div class="purchase-list">
                        <?php foreach ($orders as $order): ?>
                            <article class="purchase-item">
                                <div class="purchase-item-head">
                                        <div><span>Đơn #<?= $order['id'] ?> · <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span><h3><?= htmlspecialchars(order_product_name($order)) ?></h3><p><?= htmlspecialchars(order_product_category($order)) ?></p></div>
                                    <strong><?= number_format($order['price'], 0, ',', '.') ?>đ</strong>
                                </div>
                                <?php $credentials = order_delivered_credentials($order); if ($credentials !== ''): ?>
                                    <details class="credential-panel">
                                        <summary>Xem thông tin đăng nhập</summary>
                                        <div class="credential-content"><pre id="credential-<?= $order['id'] ?>"><?= htmlspecialchars($credentials) ?></pre><button type="button" data-copy-target="credential-<?= $order['id'] ?>">Sao chép</button></div>
                                    </details>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="profile-side">
                <section class="profile-panel">
                    <div class="profile-section-heading"><div><span class="section-kicker">Sổ số dư</span><h2>Biến động gần đây</h2></div></div>
                    <div class="balance-history">
                        <?php foreach ($ledger as $entry): ?>
                            <div class="balance-history-item"><div><strong><?= htmlspecialchars($entry['description']) ?></strong><span><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?></span></div><b class="<?= $entry['amount'] >= 0 ? 'positive' : 'negative' ?>"><?= $entry['amount'] >= 0 ? '+' : '' ?><?= number_format($entry['amount'], 0, ',', '.') ?>đ</b></div>
                        <?php endforeach; ?>
                        <?php if (!$ledger): ?><p class="profile-panel-empty">Chưa có biến động số dư.</p><?php endif; ?>
                    </div>
                </section>

                <section class="profile-panel">
                    <div class="profile-section-heading"><div><span class="section-kicker">Bảo mật</span><h2>Đổi mật khẩu</h2></div></div>
                    <form method="POST" class="profile-password-form">
                        <?= csrf_field() ?><input type="hidden" name="action_change_password" value="1">
                        <label>Mật khẩu hiện tại<input type="password" name="old_password" required autocomplete="current-password"></label>
                        <label>Mật khẩu mới<input type="password" name="new_password" minlength="8" required autocomplete="new-password"></label>
                        <label>Nhập lại mật khẩu<input type="password" name="confirm_password" minlength="8" required autocomplete="new-password"></label>
                        <button type="submit">Cập nhật mật khẩu</button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</main>

<script>
document.querySelectorAll('[data-copy-target]').forEach(button => button.addEventListener('click', async () => {
    const target = document.getElementById(button.dataset.copyTarget);
    if (!target) return;
    try {
        await navigator.clipboard.writeText(target.innerText);
        const original = button.textContent;
        button.textContent = 'Đã sao chép';
        setTimeout(() => button.textContent = original, 1400);
    } catch (error) {
        button.textContent = 'Không thể sao chép';
    }
}));
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
