<?php
session_start();
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../lib/user_modules.php';
require_once __DIR__ . '/../lib/ui_modules.php';
require_once __DIR__ . '/../lib/order_modules.php';
require_once __DIR__ . '/../admin_lib/admin_account_field_modules.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = intval($_GET['order_id'] ?? 0);
$username = $_SESSION['username'];

if ($order_id <= 0) {
    header('Location: ../index.php');
    exit;
}

$order = user_getOrderDetail($order_id, $username);

if (!$order) {
    header('Location: ../index.php');
    exit;
}

$account_info = $order['account_data'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead('Thông tin đơn hàng'); ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        .checkout-page {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
    </style>
</head>
<body>
    <?php ui_renderNavbar($username, 0, 0, 'user'); ?>

    <div class="checkout-page">
        <div class="nexus-breadcrumb" style="margin-bottom:1.5rem;">
            <a href="../index.php"><i class="fa-solid fa-house"></i></a>
            <i class="fa-solid fa-chevron-right nexus-breadcrumb-sep"></i>
            <a href="../user/orders.php">Đơn hàng</a>
            <i class="fa-solid fa-chevron-right nexus-breadcrumb-sep"></i>
            <span>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
        </div>

        <div class="nexus-alert nexus-alert-success" style="margin-bottom:1.5rem;">
            <i class="fa-solid fa-check-circle"></i>
            <span><strong>Mua hàng thành công!</strong> Thông tin tài khoản của bạn được hiển thị dưới đây.</span>
        </div>

        <div class="nexus-card" style="margin-bottom:1rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;padding-bottom:1rem;border-bottom:1px solid var(--border-subtle);flex-wrap:wrap;gap:0.8rem;">
                <div>
                    <h3 style="margin:0;font-weight:700;">Đơn hàng #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                    <small style="color:var(--text-muted);"><?php echo date('d/m/Y H:i:s', strtotime($order['created_at'])); ?></small>
                </div>
                <span class="nexus-badge nexus-badge-green">
                    <i class="fa-solid fa-check"></i> Hoàn tất
                </span>
            </div>

            <div class="nexus-info-row">
                <span class="nexus-info-label">Sản phẩm</span>
                <span class="nexus-info-value"><?php echo htmlspecialchars($order['product_title']); ?></span>
            </div>
            <div class="nexus-info-row">
                <span class="nexus-info-label">Danh mục</span>
                <span class="nexus-info-value"><?php echo htmlspecialchars($order['category']); ?></span>
            </div>
            <div class="nexus-info-row">
                <span class="nexus-info-label">Giá</span>
                <span class="nexus-info-value" style="color:var(--green);"><?php echo number_format($order['price']); ?>đ</span>
            </div>
            <div class="nexus-info-row" style="padding-bottom:0;">
                <span class="nexus-info-label">Trạng thái</span>
                <span class="nexus-info-value" style="color:var(--green);"><i class="fa-solid fa-check-circle"></i> Đã thanh toán</span>
            </div>
        </div>

        <div class="nexus-card" style="margin-bottom:1rem;">
            <div class="account-box">
                <div class="account-box-title">
                    <i class="fa-solid fa-lock"></i>
                    Thông tin tài khoản
                </div>
                <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;">
                    Dưới đây là thông tin đăng nhập của tài khoản bạn vừa mua. Vui lòng lưu lại thông tin này ở nơi an toàn.
                </p>
                <?php
                $fieldMap = fieldType_getMap();
                echo '<div style="background:#0a0a15;border-radius:6px;overflow:hidden;margin-bottom:12px;">';
                echo renderAccountFields($account_info, $fieldMap);
                echo '</div>';
                ?>
                <button class="btn-nexus-purple" onclick="copyAllAccountInfo()" style="width:100%;justify-content:center;">
                    <i class="fa-solid fa-copy"></i> Sao chép tất cả
                </button>
            </div>

            <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--border-subtle);">
                <div style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;display:flex;align-items:center;gap:6px;">
                    <i class="fa-solid fa-info-circle"></i> Lưu ý quan trọng
                </div>
                <ul style="color:var(--text-muted);font-size:0.9rem;margin:0;padding-left:1.5rem;">
                    <li style="margin-bottom:4px;">Vui lòng đổi mật khẩu ngay sau khi đăng nhập lần đầu tiên</li>
                    <li style="margin-bottom:4px;">Bảo mật thông tin tài khoản của bạn</li>
                    <li>Nếu gặp vấn đề, vui lòng liên hệ hỗ trợ</li>
                </ul>
            </div>
        </div>

        <div class="nexus-card">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:0.8rem;">
                <i class="fa-solid fa-history" style="color:var(--purple);"></i>
                <span style="font-weight:700;">Lịch sử mua hàng</span>
            </div>
            <p style="color:var(--text-muted);font-size:0.9rem;margin:0;">
                Bạn có thể xem tất cả các đơn hàng của mình trong <a href="../user/orders.php" style="color:var(--purple);">trang lịch sử đơn hàng</a>.
            </p>
        </div>
    </div>

    <?php ui_renderFooter(); ?>
    <?php ui_renderToastContainer(); ?>
    <?php ui_renderScripts(); ?>
    <script>
        async function copyAllAccountInfo() {
            const parts = [];
            document.querySelectorAll('.nx-account-field-value > span, .nx-account-field-value > a').forEach(function(el) {
                const text = el.tagName === 'A' ? el.href : el.textContent.trim();
                if (text) parts.push(text);
            });
            if (!parts.length) return;
            const text = parts.join('\n');
            try {
                await navigator.clipboard.writeText(text);
                NexusToast.success('Đã sao chép!');
            } catch {
                NexusToast.error('Không thể sao chép');
            }
        }
    </script>
</body>
</html>
