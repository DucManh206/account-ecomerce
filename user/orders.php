<?php
session_start();
if (session_status() === PHP_SESSION_NONE) { /* already started */ }

require_once __DIR__ . '/../lib/order_modules.php';
require_once __DIR__ . '/../lib/user_modules.php';
require_once __DIR__ . '/../lib/settings_modules.php';
require_once __DIR__ . '/../lib/ui_modules.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit;
}

$username = $_SESSION['username'];
$userId   = intval($_SESSION['user_id'] ?? 0);
$orders      = order_getByUser($username);
$transactions = user_getTransactionHistory($username);
$balance     = getBalance($username);
$statusMap   = order_getStatusMap();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead('Lịch sử đơn hàng'); ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        .user-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
        }
        /* Nav tabs */
        .nexus-tabs { margin-bottom: 1.5rem; }
        .tab-btn {
            padding: 12px 20px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: var(--transition-fast);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-btn:hover { color: var(--text-primary); }
        .tab-btn.active {
            color: var(--purple);
            border-bottom-color: var(--purple);
        }
        .tab-badge {
            background: var(--purple-dim);
            color: var(--purple);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        /* Trans card */
        .trans-card {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 1rem 1.2rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .trans-type {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .trans-type.purchase { background: var(--red-dim); color: var(--red); }
        .trans-type.topup,
        .trans-type.deposit { background: var(--green-dim); color: var(--green); }
        .trans-type.refund { background: var(--blue-dim); color: var(--blue); }
        .trans-desc { font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; }
        .trans-balance { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
        .trans-amount { font-weight: 800; font-size: 1rem; min-width: 100px; text-align: right; }
        .trans-amount.negative { color: var(--red); }
        .trans-amount.positive { color: var(--green); }
    </style>
</head>
<body>
    <?php ui_renderNavbar($username, 0, $balance, 'user'); ?>

    <div class="user-page">
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Lịch sử tài khoản
                </h1>
            </div>
            <div class="nexus-balance-card" style="text-align:right;">
                <div class="nexus-balance-label">Số dư ví</div>
                <div class="nexus-balance-value"><?php echo number_format($balance, 0, ',', '.'); ?>đ</div>
            </div>
        </div>

        <div class="nexus-tabs">
            <button class="tab-btn active" onclick="switchTab('orders')">
                <i class="fa-solid fa-bag-shopping"></i> Đơn hàng
                <span class="tab-badge"><?php echo count($orders); ?></span>
            </button>
            <button class="tab-btn" onclick="switchTab('transactions')">
                <i class="fa-solid fa-exchange-alt"></i> Giao dịch
                <span class="tab-badge"><?php echo count($transactions); ?></span>
            </button>
        </div>

        <!-- Tab: Đơn hàng -->
        <div id="tab-orders" class="tab-content active">
            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $order):
                    $s     = $order['status'] ?? 'pending';
                    $sInfo = $statusMap[$s] ?? ['label' => ucfirst($s), 'icon' => 'fa-circle'];
                    $accountData = json_decode($order['account_data'] ?? '{}', true);
                    $statusClass = 'nx-status-' . $s;
                    $accPreview = $accountData['account'] ?? $accountData['email'] ?? '';
                ?>
                    <div class="nx-order-card" onclick="location.href='order-detail.php?id=<?php echo $order['id']; ?>'">
                        <div class="nx-order-img">
                            <img src="<?php echo htmlspecialchars($order['image_url'] ?? ''); ?>" alt="" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
                        </div>
                        <div class="nx-order-info">
                            <h5 class="nx-order-title"><?php echo htmlspecialchars($order['product_title'] ?? 'Sản phẩm'); ?></h5>
                            <div class="nx-order-meta">
                                <span><i class="fa-solid fa-hashtag"></i>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span><i class="fa-solid fa-calendar"></i><?php echo date('d/m/Y H:i', strtotime($order['created_at'] ?? 'now')); ?></span>
                            </div>
                            <?php if ($accPreview): ?>
                                <div class="nx-order-account">
                                    <span class="label"><i class="fa-solid fa-key"></i> TK:</span>
                                    <span class="value"><?php echo htmlspecialchars($accPreview); ?> ····</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="nx-order-right">
                            <div class="nx-order-price"><?php echo number_format($order['price'] ?? 0); ?>đ</div>
                            <span class="nx-order-status <?php echo $statusClass; ?>">
                                <?php echo $sInfo['label']; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="nexus-card">
                    <?php ui_renderEmptyState(
                        'fa-bag-shopping',
                        'Chưa có đơn hàng nào',
                        'Bạn chưa mua sản phẩm nào.',
                        '../index.php',
                        'Khám phá cửa hàng'
                    ); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Giao dịch -->
        <div id="tab-transactions" class="tab-content">
            <?php if (count($transactions) > 0): ?>
                <?php foreach ($transactions as $trans):
                    $type = $trans['type'] ?? '';
                    $amount = intval($trans['amount'] ?? 0);
                ?>
                    <div class="trans-card">
                        <div>
                            <span class="trans-type <?php echo htmlspecialchars($type); ?>">
                                <?php if ($type === 'purchase'): ?>
                                    <i class="fa-solid fa-bag-shopping"></i> Mua hàng
                                <?php elseif ($type === 'topup' || $type === 'deposit'): ?>
                                    <i class="fa-solid fa-plus-circle"></i> <?php echo $type === 'deposit' ? 'Đã duyệt' : 'Nạp tiền'; ?>
                                <?php elseif ($type === 'refund'): ?>
                                    <i class="fa-solid fa-rotate-left"></i> Hoàn tiền
                                <?php else: ?>
                                    <i class="fa-solid fa-exchange-alt"></i> <?php echo htmlspecialchars(ucfirst($type)); ?>
                                <?php endif; ?>
                            </span>
                            <div class="trans-desc"><?php echo htmlspecialchars($trans['description'] ?? ''); ?></div>
                            <div class="trans-balance">
                                <?php echo date('d/m/Y H:i:s', strtotime($trans['created_at'] ?? 'now')); ?>
                                &nbsp;·&nbsp; Số dư: <?php echo number_format(intval($trans['balance_after'] ?? 0)); ?>đ
                            </div>
                        </div>
                        <div class="trans-amount <?php echo $amount > 0 ? 'positive' : 'negative'; ?>">
                            <?php echo ($amount > 0 ? '+' : '') . number_format($amount); ?>đ
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="nexus-card">
                    <?php ui_renderEmptyState(
                        'fa-exchange-alt',
                        'Chưa có giao dịch nào',
                        'Lịch sử giao dịch sẽ hiển thị ở đây.'
                    ); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php ui_renderFooter(); ?>
    <?php ui_renderToastContainer(); ?>
    <?php ui_renderScripts(); ?>
    <script>
        function switchTab(name) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector('.tab-btn[onclick="switchTab(\'' + name + '\')"]').classList.add('active');
            document.getElementById('tab-' + name).classList.add('active');
        }
    </script>
</body>
</html>
