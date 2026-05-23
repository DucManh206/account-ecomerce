<?php
session_start();
if (session_status() === PHP_SESSION_NONE) { /* already started */ }

require_once __DIR__ . '/../lib/order_modules.php';
require_once __DIR__ . '/../../admin/lib/admin_account_field_modules.php';
require_once __DIR__ . '/../lib/settings_modules.php';
require_once __DIR__ . '/../lib/ui_modules.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit;
}

$orderId  = intval($_GET['id'] ?? 0);
$username = $_SESSION['username'];

if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

$order = user_getOrderDetail($orderId, $username);

if (!$order) {
    header('Location: orders.php');
    exit;
}

$statusMap = order_getStatusMap();
$s         = $order['status'] ?? 'pending';
$sInfo     = $statusMap[$s] ?? ['label' => ucfirst($s), 'icon' => 'fa-circle'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead('Chi tiết đơn hàng #' . str_pad($orderId, 6, '0', STR_PAD_LEFT)); ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        .order-page {
            max-width: 720px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
        }
        /* Status badge */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .badge-pending    { background: rgba(255,255,255,0.08); color: var(--text-muted); }
        .badge-processing { background: var(--amber-dim); color: var(--amber); }
        .badge-completed  { background: var(--green-dim); color: var(--green); }
        .badge-cancelled,
        .badge-refunded   { background: var(--red-dim); color: var(--red); }
        /* Info grid */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 480px) { .info-grid { grid-template-columns: 1fr; } }
        .info-item { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .info-item:last-child { border-bottom: none; }
        .info-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value { font-weight: 600; font-size: 0.9rem; }
        .info-value.success { color: var(--green); }
        /* Product row */
        .product-row { display: flex; align-items: center; gap: 14px; margin-bottom: 1rem; }
        .product-img {
            width: 70px; height: 70px;
            border-radius: 10px;
            object-fit: cover;
            background: rgba(255,255,255,0.05);
            flex-shrink: 0;
        }
        .product-name { font-weight: 700; font-size: 1rem; margin-bottom: 4px; }
        .product-category { font-size: 0.8rem; color: var(--text-muted); }
        /* Account box */
        .account-box {
            background: #0f0f1e;
            border: 1px solid rgba(110, 86, 207, 0.25);
            border-radius: 10px;
            padding: 1.2rem;
            margin-top: 1rem;
        }
        .account-box-title {
            font-size: 0.82rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--purple);
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        /* Copy btn */
        .btn-copy {
            margin-top: 12px;
            padding: 10px 18px;
            background: var(--purple);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-copy:hover { background: #5a47b8; }
        .btn-copy.copied { background: var(--green); }
    </style>
</head>
<body>
    <?php ui_renderNavbar($username, 0, 0, 'user'); ?>

    <div class="order-page">
        <div class="nexus-breadcrumb" style="margin-bottom:1.5rem;">
            <a href="orders.php"><i class="fa-solid fa-house"></i></a>
            <i class="fa-solid fa-chevron-right nexus-breadcrumb-sep"></i>
            <a href="orders.php">Đơn hàng</a>
            <i class="fa-solid fa-chevron-right nexus-breadcrumb-sep"></i>
            <span>#<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></span>
        </div>

        <div class="page-header" style="padding:0 0 1.5rem;">
            <div>
                <h1 class="page-title">Đơn hàng #<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></h1>
                <p class="page-subtitle">Đặt lúc <?php echo date('H:i d/m/Y', strtotime($order['created_at'] ?? 'now')); ?></p>
            </div>
            <span class="badge-status badge-<?php echo $s; ?>">
                <i class="fa-solid <?php echo $sInfo['icon'] ?? 'fa-circle'; ?>"></i>
                <?php echo $sInfo['label']; ?>
            </span>
        </div>

        <!-- Order Info -->
        <div class="nexus-card" style="margin-bottom:1rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border-subtle);flex-wrap:wrap;gap:0.8rem;">
                <span style="font-weight:700;font-size:1rem;">Thông tin đơn hàng</span>
            </div>

            <div class="product-row">
                <img src="<?php echo htmlspecialchars($order['image_url'] ?? ''); ?>"
                     alt="<?php echo htmlspecialchars($order['product_title'] ?? ''); ?>"
                     class="product-img"
                     onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
                <div>
                    <div class="product-name"><?php echo htmlspecialchars($order['product_title'] ?? 'Sản phẩm'); ?></div>
                    <div class="product-category"><?php echo htmlspecialchars($order['category'] ?? ''); ?></div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Mã đơn hàng</div>
                    <div class="info-value" style="color:var(--purple);">#<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ngày đặt</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i:s', strtotime($order['created_at'] ?? 'now')); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Giá sản phẩm</div>
                    <div class="info-value success"><?php echo number_format($order['price'] ?? 0); ?>đ</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Thanh toán</div>
                    <div class="info-value success"><i class="fa-solid fa-check-circle"></i> Đã thanh toán</div>
                </div>
            </div>
        </div>

        <!-- Account Info -->
        <div class="nexus-card">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:0.8rem;">
                <i class="fa-solid fa-key" style="color:var(--purple);"></i>
                <span style="font-weight:700;font-size:1rem;">Thông tin tài khoản</span>
            </div>
            <p style="color:var(--text-muted);font-size:0.88rem;margin-bottom:0;">
                Dưới đây là thông tin đăng nhập của tài khoản bạn đã mua. Vui lòng bảo mật thông tin này.
            </p>

            <div class="account-box">
                <div class="account-box-title">
                    <i class="fa-solid fa-shield-halved"></i>
                    Thông tin tài khoản
                </div>
                <?php
                $fieldMap = fieldType_getMap();
                echo '<div style="background:#0a0a15;border-radius:6px;overflow:hidden;">';
                echo renderAccountFields($order['account_data'] ?? '', $fieldMap);
                echo '</div>';
                ?>
                <button class="btn-copy" id="copyBtn" onclick="copyAllAccountInfo()">
                    <i class="fa-regular fa-copy"></i> Sao chép tất cả
                </button>
            </div>

            <div class="nexus-info-box-amber" style="margin-top:1rem;">
                <div style="font-weight:700;font-size:0.88rem;color:var(--amber);margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Lưu ý quan trọng
                </div>
                <ul style="margin:0;padding-left:1.2rem;color:var(--text-muted);font-size:0.85rem;">
                    <li style="margin-bottom:4px;">Vui lòng đổi mật khẩu ngay sau khi đăng nhập lần đầu tiên</li>
                    <li style="margin-bottom:4px;">Bảo mật thông tin tài khoản của bạn</li>
                    <li>Nếu gặp vấn đề, vui lòng liên hệ hỗ trợ</li>
                </ul>
            </div>
        </div>
    </div>

    <?php ui_renderFooter(); ?>
    <?php ui_renderToastContainer(); ?>
    <?php ui_renderScripts(); ?>
    <script>
        async function copyAllAccountInfo() {
            const parts = [];
            document.querySelectorAll('.nx-account-field-value > span, .nx-account-field-value > a').forEach(el => {
                const text = el.tagName === 'A' ? el.href : el.textContent.trim();
                if (text) parts.push(text);
            });
            if (!parts.length) return;
            const text = parts.join('\n');
            const btn = document.getElementById('copyBtn');
            try {
                await navigator.clipboard.writeText(text);
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Đã sao chép!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.innerHTML = '<i class="fa-regular fa-copy"></i> Sao chép tất cả';
                    btn.classList.remove('copied');
                }, 2000);
            } catch {
                NexusToast.error('Không thể sao chép. Vui lòng sao chép thủ công.');
            }
        }
    </script>
</body>
</html>
