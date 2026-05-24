<?php
session_start();
require_once __DIR__ . '/../ui/ui_modules.php';
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$checkout = $_SESSION['last_checkout'] ?? null;
$orderIds = [];
$accounts = [];
$totalPaid = 0;
$newBalance = null;
$completedAt = date('Y-m-d H:i:s');

if (is_array($checkout)) {
    $orderIds = array_map('intval', $checkout['order_ids'] ?? []);
    $accounts = is_array($checkout['accounts'] ?? null) ? $checkout['accounts'] : [];
    $totalPaid = intval($checkout['total_paid'] ?? 0);
    $newBalance = isset($checkout['new_balance']) ? intval($checkout['new_balance']) : null;
    $completedAt = $checkout['completed_at'] ?? $completedAt;
}

// Fallback: nếu user refresh hoặc session mất last_checkout thì lấy 5 đơn mới nhất của user.
if (empty($accounts) && isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $sql = "SELECT o.id AS order_id, o.account_data, o.price, o.created_at, p.title AS product
            FROM orders o
            LEFT JOIN products p ON p.id = o.product_id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
            LIMIT 5";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $uid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $accounts[] = [
                'order_id' => intval($row['order_id']),
                'product' => $row['product'] ?: 'Sản phẩm',
                'account' => json_decode($row['account_data'], true) ?: ['raw' => $row['account_data']],
            ];
            $orderIds[] = intval($row['order_id']);
            $totalPaid += intval($row['price']);
            $completedAt = $row['created_at'];
        }
        mysqli_stmt_close($stmt);
    }
}

function cart_success_value($value): string {
    if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return (string)$value;
}

$base = ui_getBasePath();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead('Thanh toán thành công'); ?>
    <style>
        .success-wrap{max-width:980px;margin:42px auto 80px;padding:0 18px}.success-hero{background:linear-gradient(135deg,rgba(16,185,129,.16),rgba(110,86,207,.14));border:1px solid rgba(16,185,129,.24);border-radius:28px;padding:34px;text-align:center;box-shadow:0 24px 70px rgba(0,0,0,.24)}.success-icon{width:78px;height:78px;margin:0 auto 18px;border-radius:24px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;color:#fff;font-size:34px;box-shadow:0 18px 45px rgba(16,185,129,.3)}.success-title{font-size:clamp(1.7rem,4vw,2.6rem);font-weight:900;margin:0 0 8px}.success-sub{color:var(--text-secondary);margin:0}.summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:22px 0}.summary-card{background:rgba(255,255,255,.04);border:1px solid var(--border-subtle);border-radius:18px;padding:18px}.summary-label{font-size:.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em}.summary-value{font-size:1.2rem;font-weight:800;margin-top:6px}.account-card{background:var(--card-bg);border:1px solid var(--border-subtle);border-radius:22px;margin-top:18px;overflow:hidden}.account-head{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:18px 20px;border-bottom:1px solid var(--border-subtle)}.account-name{font-weight:850}.order-badge{font-size:.78rem;background:rgba(110,86,207,.16);color:#a78bfa;padding:6px 10px;border-radius:999px}.secret-list{padding:16px 20px;display:grid;gap:10px}.secret-row{display:grid;grid-template-columns:170px 1fr auto;gap:10px;align-items:center;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:10px 12px}.secret-key{color:var(--text-muted);font-size:.84rem}.secret-val{font-family:ui-monospace,SFMono-Regular,Consolas,monospace;word-break:break-all}.copy-btn{border:0;border-radius:10px;background:rgba(16,185,129,.14);color:#34d399;padding:7px 10px;font-weight:700}.action-row{display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-top:24px}.empty-box{margin-top:22px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.22);border-radius:18px;padding:20px;color:#fbbf24}@media(max-width:760px){.summary-grid{grid-template-columns:1fr}.secret-row{grid-template-columns:1fr}.account-head{align-items:flex-start;flex-direction:column}}
    </style>
</head>
<body class="bg-nexus">
    <div class="success-wrap">
        <section class="success-hero">
            <div class="success-icon"><i class="fa-solid fa-check"></i></div>
            <h1 class="success-title">Thanh toán thành công</h1>
            <p class="success-sub">Tài khoản đã được xuất bên dưới. Hãy lưu lại thông tin này ngay.</p>
            <div class="summary-grid">
                <div class="summary-card"><div class="summary-label">Số đơn</div><div class="summary-value"><?php echo count($orderIds); ?></div></div>
                <div class="summary-card"><div class="summary-label">Đã thanh toán</div><div class="summary-value text-success"><?php echo number_format($totalPaid); ?>đ</div></div>
                <div class="summary-card"><div class="summary-label">Thời gian</div><div class="summary-value"><?php echo htmlspecialchars(date('H:i d/m/Y', strtotime($completedAt))); ?></div></div>
            </div>
            <?php if ($newBalance !== null): ?><p class="success-sub">Số dư còn lại: <strong><?php echo number_format($newBalance); ?>đ</strong></p><?php endif; ?>
        </section>

        <?php if (!empty($accounts)): ?>
            <?php foreach ($accounts as $item): $acc = is_array($item['account'] ?? null) ? $item['account'] : ['data' => cart_success_value($item['account'] ?? '')]; ?>
                <article class="account-card">
                    <div class="account-head">
                        <div><div class="account-name"><?php echo htmlspecialchars($item['product'] ?? 'Sản phẩm'); ?></div><small class="text-muted">Thông tin tài khoản đã mua</small></div>
                        <span class="order-badge">#<?php echo intval($item['order_id'] ?? 0); ?></span>
                    </div>
                    <div class="secret-list">
                        <?php foreach ($acc as $key => $value): $val = cart_success_value($value); ?>
                            <div class="secret-row">
                                <div class="secret-key"><?php echo htmlspecialchars((string)$key); ?></div>
                                <div class="secret-val"><?php echo nl2br(htmlspecialchars($val)); ?></div>
                                <button class="copy-btn" type="button" onclick="copySecret(this)" data-copy="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-box">Chưa có dữ liệu checkout trong phiên này. Bạn có thể xem lại đơn trong lịch sử mua hàng.</div>
        <?php endif; ?>

        <div class="action-row">
            <a class="btn-nexus" href="<?php echo $base; ?>/user/orders.php"><i class="fa-solid fa-clock-rotate-left"></i> Lịch sử đơn hàng</a>
            <a class="btn-nexus-secondary" href="<?php echo $base; ?>/"><i class="fa-solid fa-house"></i> Về trang chủ</a>
            <a class="btn-nexus-secondary" href="<?php echo $base; ?>/cart/"><i class="fa-solid fa-cart-shopping"></i> Giỏ hàng</a>
        </div>
    </div>
    <script>
        function copySecret(btn){navigator.clipboard.writeText(btn.dataset.copy||'').then(()=>{const old=btn.innerHTML;btn.innerHTML='<i class="fa-solid fa-check"></i> Copied';setTimeout(()=>btn.innerHTML=old,1200);});}
    </script>
    <?php ui_renderScripts(); ?>
</body>
</html>
