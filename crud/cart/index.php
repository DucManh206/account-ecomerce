<?php
session_start();
require_once __DIR__ . '/../../crud/cart/cart_modules.php';
require_once __DIR__ . '/../../crud/users/user_modules.php';
require_once __DIR__ . '/../../crud/ui/ui_modules.php';
require_once __DIR__ . '/../../crud/settings/settings_modules.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$cartItems = getCartItems($userId);
$cartTotal = getCartTotal($userId);
$cartCount = getCartCount($userId);
$userBalance = $username ? getBalance($username) : 0;
$base = ui_getBasePath();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead('Giỏ hàng'); ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 24px;
            padding-bottom: 60px;
            align-items: start;
        }
        @media (max-width: 992px) {
            .cart-layout { grid-template-columns: 1fr; }
            .cart-summary { position: static !important; }
        }
        .cart-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-subtle);
        }
        .cart-panel-title {
            font-size: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cart-panel-title .count {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-muted);
        }
        .btn-clear-all {
            background: transparent;
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-clear-all:hover {
            background: var(--red-dim);
            border-color: var(--red);
            color: var(--red);
        }
        .cart-items-list {
            padding: 0;
        }
        .summary-login-prompt {
            background: var(--purple-dim);
            border: 1px solid rgba(110, 86, 207, 0.2);
            border-radius: var(--radius-md);
            padding: 16px;
            text-align: center;
            margin-bottom: 16px;
        }
        .summary-login-prompt p {
            color: var(--text-secondary);
            font-size: 0.88rem;
            margin: 0 0 12px;
        }
        .btn-checkout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-normal);
            margin-top: 20px;
            text-decoration: none;
        }
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-green);
            color: white;
        }
        .btn-checkout:disabled {
            background: var(--card-hover);
            color: var(--text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-checkout.insufficient {
            background: var(--red);
            cursor: not-allowed;
        }
        .btn-checkout.insufficient:hover {
            transform: none;
            box-shadow: none;
            color: white;
        }
        .balance-check {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 16px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            background: var(--green-dim);
        }
        .balance-check.insufficient {
            border-color: rgba(239, 68, 68, 0.2);
            background: var(--red-dim);
        }
        .balance-check-label { font-size: 0.85rem; color: var(--text-secondary); }
        .balance-check-value { font-size: 1rem; font-weight: 700; color: var(--green); }
        .balance-check.insufficient .balance-check-value { color: var(--red); }
    </style>
</head>
<body>
    <?php ui_renderNavbar($username, $cartCount['quantity'], $userBalance, 'store'); ?>

    <div class="container-main">
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="fa-solid fa-cart-shopping"></i>
                    Giỏ hàng
                </h1>
                <p class="page-subtitle"><?php echo $cartCount['items']; ?> sản phẩm</p>
            </div>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="nexus-card">
                <?php ui_renderEmptyState(
                    'fa-cart-circle-xmark',
                    'Giỏ hàng trống',
                    'Bạn chưa thêm sản phẩm nào vào giỏ hàng.',
                    $base . '/index.php',
                    'Tiếp tục mua sắm'
                ); ?>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="nexus-card" style="padding:0;overflow:hidden;">
                    <div class="cart-panel-header">
                        <div class="cart-panel-title">
                            <i class="fa-solid fa-box-open" style="color:var(--purple);"></i>
                            Sản phẩm
                            <span class="count">(<?php echo $cartCount['items']; ?> sản phẩm)</span>
                        </div>
                        <button class="btn-clear-all" onclick="Cart.clear()">
                            <i class="fa-solid fa-trash-can"></i> Xóa tất cả
                        </button>
                    </div>
                    <div class="cart-items-list" id="cartBody">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="nx-cart-item" data-cart-id="<?php echo $item['cart_id']; ?>" data-price="<?php echo $item['price']; ?>" data-subtotal="<?php echo $item['subtotal']; ?>">
                                <div class="nx-cart-img">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
                                </div>
                                <div class="nx-cart-info">
                                    <div class="nx-cart-category">
                                        <i class="<?php echo getIconClass(htmlspecialchars($item['icon_class'])); ?>"></i>
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </div>
                                    <h3 class="nx-cart-title">
                                        <a href="<?php echo $base; ?>/products/index.php?id=<?php echo $item['product_id']; ?>">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </a>
                                    </h3>
                                    <div class="nx-cart-price">
                                        <?php echo number_format($item['price'], 0, ',', '.'); ?>đ
                                    </div>
                                </div>
                                <div class="nx-cart-actions">
                                    <div class="nx-qty">
                                        <button class="nx-qty-btn" onclick="Cart.update(<?php echo $item['cart_id']; ?>, <?php echo max(1, $item['quantity'] - 1); ?>)">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                        <span class="nx-qty-val"><?php echo $item['quantity']; ?></span>
                                        <button class="nx-qty-btn" onclick="Cart.update(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                    <button class="nx-cart-remove" onclick="Cart.remove(<?php echo $item['cart_id']; ?>, this.closest('.nx-cart-item'))">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Summary -->
                <div class="cart-summary nexus-sticky">
                    <div class="nexus-panel">
                        <h3 class="summary-title" style="font-size:1rem;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                            <i class="fa-solid fa-receipt" style="color:var(--purple);"></i>
                            Tóm tắt đơn hàng
                        </h3>

                        <?php if (!$username): ?>
                            <div class="summary-login-prompt">
                                <p><i class="fa-solid fa-circle-info me-1"></i> Vui lòng đăng nhập để thanh toán</p>
                                <a href="<?php echo $base; ?>/auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-nexus-purple" style="width:100%;justify-content:center;">
                                    <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập ngay
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="balance-check <?php echo ($userBalance < $cartTotal) ? 'insufficient' : ''; ?>">
                                <span class="balance-check-label"><i class="fa-solid fa-wallet me-2"></i>Số dư ví</span>
                                <span class="balance-check-value"><?php echo number_format($userBalance, 0, ',', '.'); ?>đ</span>
                            </div>
                        <?php endif; ?>

                        <div class="nexus-info-row">
                            <span class="nexus-info-label">Tạm tính (<?php echo $cartCount['quantity']; ?> sản phẩm)</span>
                            <span class="nexus-info-value"><?php echo number_format($cartTotal, 0, ',', '.'); ?>đ</span>
                        </div>
                        <div class="nexus-info-row">
                            <span class="nexus-info-label">Giảm giá</span>
                            <span class="nexus-info-value" style="color:var(--green);">0đ</span>
                        </div>
                        <div class="nexus-divider" style="margin:12px 0;"></div>
                        <div class="nexus-info-row" style="padding-bottom:0;">
                            <span class="nexus-info-label" style="font-size:1rem;font-weight:700;">Tổng cộng</span>
                            <span class="nexus-info-value cart-total-amount" style="font-size:1.3rem;color:var(--green);">
                                <?php echo number_format($cartTotal, 0, ',', '.'); ?>đ
                            </span>
                        </div>

                        <?php if ($username): ?>
                            <?php if ($userBalance >= $cartTotal): ?>
                                <button class="btn-checkout" onclick="processCheckout()">
                                    <i class="fa-solid fa-credit-card"></i> Thanh toán ngay
                                </button>
                            <?php else: ?>
                                <button class="btn-checkout insufficient" disabled>
                                    <i class="fa-solid fa-circle-exclamation"></i> Số dư không đủ
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>

                        <a href="<?php echo $base; ?>/index.php" class="btn-nexus-secondary" style="width:100%;justify-content:center;margin-top:10px;">
                            <i class="fa-solid fa-arrow-left"></i> Tiếp tục mua sắm
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php ui_renderFooter(); ?>
    <?php ui_renderToastContainer(); ?>
    <?php ui_renderScripts(); ?>
    <script>
        async function processCheckout() {
            const btn = document.querySelector('.btn-checkout');
            if (!btn || btn.disabled) return;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';

            try {
                const formData = new FormData();
                formData.append('action', 'checkout');

                const response = await fetch('<?php echo $base; ?>/api/cart_checkout.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    NexusToast.success(result.message);
                    setTimeout(() => { window.location.href = 'success.php'; }, 1200);
                } else {
                    let msg = result.message || 'Thanh toán thất bại!';
                    if (result.errors && result.errors.length > 0) {
                        msg += '\n\nChi tiết:\n' + result.errors.join('\n');
                    }
                    NexusToast.error(msg);
                    Cart.loadCart();
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Checkout error:', error);
                NexusToast.error('Đã xảy ra lỗi khi thanh toán!');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    </script>
</body>
</html>
