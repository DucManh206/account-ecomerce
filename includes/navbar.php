<?php
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isHomeActive = ($currentScript === 'index.php' || $currentScript === '') ? 'active' : '';
$isTopupActive = ($currentScript === 'topup.php') ? 'active' : '';
$isCartActive = ($currentScript === 'cart.php') ? 'active' : '';
$isProfileActive = ($currentScript === 'profile.php') ? 'active' : '';

$navBalance = 0;
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true && isset($pdo)) {
    try {
        $balStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $balStmt->execute([$_SESSION['user_id']]);
        $navBalance = $balStmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $navBalance = 0;
    }
}
?>
<header class="navbar">
    <div class="container navbar-content">
        <a href="<?= BASE_PATH ?>index.php" class="logo">
            AccountShop
        </a>
        
        <div class="nav-links">
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                <a href="<?= BASE_PATH ?>admin/dashboard.php" class="btn-nav" style="border-color: #f59e0b; color: #f59e0b !important;">Quản trị viên</a>
            <?php endif; ?>
            
            <a href="<?= BASE_PATH ?>index.php" class="nav-link <?= $isHomeActive ?>">Trang chủ</a>
            <a href="<?= BASE_PATH ?>topup.php" class="nav-link <?= $isTopupActive ?>">Nạp tiền</a>
            
            <a href="<?= BASE_PATH ?>cart.php" class="cart-badge-indicator <?= $isCartActive ?>">
                <span>Giỏ hàng</span>
                <span class="cart-count" id="cartCount"><?= count($_SESSION['cart']) ?></span>
            </a>
            
            <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
                <a href="<?= BASE_PATH ?>profile.php" class="balance-indicator">
                    Số dư: <?= number_format($navBalance, 0, ',', '.') ?>đ
                </a>
                <a href="<?= BASE_PATH ?>profile.php" class="nav-link <?= $isProfileActive ?>" style="color: var(--text-white); font-weight: 600;">
                    Hi, <?= htmlspecialchars($_SESSION['user_fullname'] ?? 'Khách') ?>
                </a>
                <a href="<?= BASE_PATH ?>logout.php" class="btn-nav" style="background: var(--danger);">Đăng xuất</a>
            <?php else: ?>
                <a href="<?= BASE_PATH ?>login.php" class="nav-link">Đăng nhập</a>
                <a href="<?= BASE_PATH ?>register.php" class="btn-nav">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</header>
