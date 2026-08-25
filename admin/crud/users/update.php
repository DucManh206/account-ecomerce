<?php
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/../../../includes/flash.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$user = getUserById($pdo, $id);
if (!$user) {
    set_flash('error', 'Thành viên không tồn tại.');
    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['form_action'] ?? '';

    try {
        if ($action === 'profile') {
            $fullname = trim($_POST['fullname'] ?? '');
            $role = in_array($_POST['role'] ?? '', ['admin', 'user'], true) ? $_POST['role'] : 'user';
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            if ($fullname === '') {
                throw new RuntimeException('Họ tên không được để trống.');
            }
            if ($id === (int) $_SESSION['admin_user_id'] && ($role !== 'admin' || $isActive !== 1)) {
                throw new RuntimeException('Bạn không thể tự hạ quyền hoặc khóa tài khoản đang đăng nhập.');
            }
            if ($user['role'] === 'admin' && $role !== 'admin') {
                $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
                if ($adminCount <= 1) {
                    throw new RuntimeException('Hệ thống phải còn ít nhất một quản trị viên đang hoạt động.');
                }
            }

            $stmt = $pdo->prepare('UPDATE users SET fullname = ?, role = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$fullname, $role, $isActive, $id]);
            record_admin_activity(
                $pdo,
                'user_updated',
                'user',
                $id,
                'Cập nhật hồ sơ và quyền truy cập của @' . $user['username'],
                ['role' => $role, 'is_active' => $isActive]
            );
            set_flash('success', 'Đã cập nhật hồ sơ và quyền truy cập.');
        } elseif ($action === 'balance') {
            $adjustment = (float) ($_POST['adjustment'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            if ($adjustment == 0) {
                throw new RuntimeException('Số tiền điều chỉnh phải khác 0.');
            }
            if (mb_strlen($reason) < 5) {
                throw new RuntimeException('Vui lòng ghi lý do điều chỉnh rõ ràng.');
            }

            $pdo->beginTransaction();
            $lockStmt = $pdo->prepare('SELECT balance, username FROM users WHERE id = ? FOR UPDATE');
            $lockStmt->execute([$id]);
            $lockedUser = $lockStmt->fetch();
            if (!$lockedUser) {
                throw new RuntimeException('Thành viên không còn tồn tại.');
            }
            $balanceAfter = (float) $lockedUser['balance'] + $adjustment;
            if ($balanceAfter < 0) {
                throw new RuntimeException('Điều chỉnh này làm số dư âm.');
            }

            $updateStmt = $pdo->prepare('UPDATE users SET balance = ? WHERE id = ?');
            $updateStmt->execute([$balanceAfter, $id]);
            record_balance_transaction(
                $pdo,
                $id,
                'adjustment',
                $adjustment,
                $balanceAfter,
                'admin_adjustment',
                null,
                $reason,
                (int) $_SESSION['admin_user_id']
            );
            record_admin_activity(
                $pdo,
                'balance_adjusted',
                'user',
                $id,
                'Điều chỉnh số dư của @' . $lockedUser['username'],
                ['amount' => $adjustment, 'balance_after' => $balanceAfter, 'reason' => $reason]
            );
            $pdo->commit();
            set_flash('success', 'Đã điều chỉnh số dư. Số dư mới: ' . number_format($balanceAfter, 0, ',', '.') . 'đ.');
        } elseif ($action === 'password') {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            if (strlen($newPassword) < 8) {
                throw new RuntimeException('Mật khẩu mới cần ít nhất 8 ký tự.');
            }
            if (!hash_equals($newPassword, $confirmPassword)) {
                throw new RuntimeException('Xác nhận mật khẩu không khớp.');
            }
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
            record_admin_activity($pdo, 'password_reset', 'user', $id, 'Đặt lại mật khẩu cho @' . $user['username']);
            set_flash('success', 'Đã đặt lại mật khẩu cho thành viên.');
        } else {
            throw new RuntimeException('Thao tác không hợp lệ.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', $e->getMessage());
    }

    header('Location: update.php?id=' . $id);
    exit;
}

$user = getUserById($pdo, $id);
$ledgerStmt = $pdo->prepare(
    'SELECT bt.*, creator.fullname AS creator_name
     FROM balance_transactions bt
     LEFT JOIN users creator ON creator.id = bt.created_by
     WHERE bt.user_id = ? ORDER BY bt.id DESC LIMIT 15'
);
$ledgerStmt->execute([$id]);
$ledger = $ledgerStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['fullname']) ?> - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar admin-page-heading">
            <div>
                <a class="back-link" href="list.php">← Danh sách thành viên</a>
                <h1><?= htmlspecialchars($user['fullname']) ?></h1>
                <p>@<?= htmlspecialchars($user['username']) ?> · thành viên #<?= $user['id'] ?> · tham gia <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
            </div>
            <div class="balance-callout"><span>Số dư hiện tại</span><strong><?= number_format($user['balance'], 0, ',', '.') ?>đ</strong></div>
        </header>

        <div class="content-body">
            <?= render_flash() ?>

            <section class="detail-grid">
                <div class="detail-main">
                    <form method="POST" class="form-card">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="form_action" value="profile">
                        <div class="section-heading"><span class="section-index">01</span><div><h2>Hồ sơ & quyền truy cập</h2><p>Khóa tài khoản sẽ chặn đăng nhập nhưng giữ nguyên lịch sử.</p></div></div>
                        <div class="form-group"><label for="username">Tên đăng nhập</label><input id="username" value="<?= htmlspecialchars($user['username']) ?>" disabled></div>
                        <div class="form-group"><label for="fullname">Họ và tên</label><input id="fullname" name="fullname" maxlength="100" required value="<?= htmlspecialchars($user['fullname']) ?>"></div>
                        <div class="form-row">
                            <div class="form-group"><label for="role">Vai trò</label><select id="role" name="role"><option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Khách hàng</option><option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên</option></select></div>
                            <div class="form-group"><label>Trạng thái truy cập</label><label class="checkbox-card"><input type="checkbox" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>><span><strong>Cho phép hoạt động</strong><small>Người dùng có thể đăng nhập và mua hàng.</small></span></label></div>
                        </div>
                        <button type="submit" class="btn btn-primary align-self-start">Lưu hồ sơ</button>
                    </form>

                    <section class="form-card">
                        <div class="section-heading"><span class="section-index">02</span><div><h2>Sổ biến động số dư</h2><p>Mọi khoản nạp, mua hàng và điều chỉnh đều được lưu lại.</p></div></div>
                        <div class="ledger-list">
                            <?php foreach ($ledger as $entry): ?>
                                <article class="ledger-item">
                                    <div><strong><?= htmlspecialchars($entry['description']) ?></strong><span><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?><?= $entry['creator_name'] ? ' · ' . htmlspecialchars($entry['creator_name']) : '' ?></span></div>
                                    <div class="ledger-amount <?= $entry['amount'] >= 0 ? 'positive' : 'negative' ?>"><?= $entry['amount'] >= 0 ? '+' : '' ?><?= number_format($entry['amount'], 0, ',', '.') ?>đ<?php if ($entry['balance_after'] !== null): ?><small>Còn <?= number_format($entry['balance_after'], 0, ',', '.') ?>đ</small><?php endif; ?></div>
                                </article>
                            <?php endforeach; ?>
                            <?php if (!$ledger): ?><div class="empty">Chưa có biến động số dư.</div><?php endif; ?>
                        </div>
                    </section>
                </div>

                <aside class="detail-side">
                    <form method="POST" class="form-card compact-card">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="form_action" value="balance">
                        <div class="section-heading"><div><h2>Điều chỉnh số dư</h2><p>Dùng số dương để cộng, số âm để trừ.</p></div></div>
                        <div class="form-group"><label for="adjustment">Số tiền điều chỉnh</label><input type="number" id="adjustment" name="adjustment" step="1000" placeholder="Ví dụ: 50000 hoặc -20000" required></div>
                        <div class="form-group"><label for="reason">Lý do</label><textarea id="reason" name="reason" rows="3" minlength="5" maxlength="255" required placeholder="Ghi rõ căn cứ điều chỉnh"></textarea></div>
                        <button type="submit" class="btn btn-primary">Ghi nhận điều chỉnh</button>
                    </form>

                    <form method="POST" class="form-card compact-card">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="form_action" value="password">
                        <div class="section-heading"><div><h2>Đặt lại mật khẩu</h2><p>Mật khẩu hiện tại sẽ mất hiệu lực ngay.</p></div></div>
                        <div class="form-group"><label for="new_password">Mật khẩu mới</label><input type="password" id="new_password" name="new_password" minlength="8" required autocomplete="new-password"></div>
                        <div class="form-group"><label for="confirm_password">Nhập lại mật khẩu</label><input type="password" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password"></div>
                        <button type="submit" class="btn btn-secondary">Đặt lại mật khẩu</button>
                    </form>

                    <div class="mini-stats">
                        <div><span>Đơn hàng</span><strong><?= number_format($user['order_count']) ?></strong></div>
                        <div><span>Tổng đã mua</span><strong><?= number_format($user['total_spent'], 0, ',', '.') ?>đ</strong></div>
                    </div>
                </aside>
            </section>
        </div>
    </main>
</div>
</body>
</html>
