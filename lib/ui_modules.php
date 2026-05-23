<?php
/**
 * NEXUS STORE - Shared UI Components
 * Sử dụng chung: require_once __DIR__ . '/ui_modules.php';
 *
 * Hướng dẫn sử dụng:
 *   require_once __DIR__ . '/ui_modules.php';
 *
 *   1. Gọi ui_renderHead('Tiêu đề trang') ở <head>
 *   2. Gọi ui_renderNavbar($username, $cartCount, $balance) sau <body>
 *   3. Gọi ui_renderFooter() trước </body>
 *   4. Gọi ui_renderToastContainer() trước </body> sau footer
 *   5. Gọi ui_renderScripts() trước </body> cuối cùng
 */

require_once __DIR__ . '/settings_modules.php';

/**
 * Detect base path từ file hiện tại
 * Returns relative path from current page to project root (where assets/ lives)
 * Example: /user/orders.php -> '../'
 *          /admin/manage/products.php -> '../../'
 *          /index.php -> ''
 */
function ui_getBasePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($scriptName);
    
    // Normalize path separators
    $dir = str_replace('\\', '/', $dir);
    
    // Remove leading slash
    $dir = ltrim($dir, '/');
    
    // If empty or root, return empty string
    if ($dir === '' || $dir === '.') {
        return '';
    }
    
    // Count directory levels
    $segments = array_filter(explode('/', $dir));
    $depth = count($segments);
    
    // Return appropriate number of ../
    return $depth > 0 ? str_repeat('../', $depth) : '';
}

/**
 * Render <head> tag với CSS/JS cần thiết
 * @param string $title Tiêu đề trang
 * @param string $extraCss Đường dẫn CSS bổ sung (tùy chọn)
 */
function ui_renderHead($title = 'NEXUS STORE', $extraCss = '') {
    $storeName = function_exists('getStoreName') ? getStoreName() : 'NEXUS STORE';
    $base = ui_getBasePath();
    $cssPath = empty($base) ? 'assets/css/nexus.css' : $base . '/assets/css/nexus.css';
    $jsPath = empty($base) ? 'assets/js/nexus-ui.js' : $base . '/assets/js/nexus-ui.js';
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | <?php echo htmlspecialchars($storeName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $cssPath; ?>">
    <?php if ($extraCss): ?>
    <link rel="stylesheet" href="<?php echo $extraCss; ?>">
    <?php endif;
}

/**
 * Render Navbar - nhiều variant cho từng loại trang
 *
 * @param string|null $username Username đã đăng nhập
 * @param int $cartCount Số sản phẩm trong giỏ hàng
 * @param int $balance Số dư tài khoản
 * @param string $variant 'store' | 'user' | 'minimal'
 *   - store: Navbar cho trang chủ/cửa hàng/sản phẩm (có logo, cart, user menu)
 *   - user: Navbar cho trang user (orders/deposit/checkout) - đơn giản hơn
 *   - minimal: Navbar tối giản (không cart, cho trang auth)
 */
function ui_renderNavbar($username = null, $cartCount = 0, $balance = 0, $variant = 'store') {
    $base = ui_getBasePath();
    $storeName = function_exists('getStoreName') ? getStoreName() : 'NEXUS STORE';
    $storeIcon = function_exists('getStoreIconClass') ? getStoreIconClass() : 'fa-solid fa-ghost';
    $homeUrl = empty($base) ? 'index.php' : $base . '/index.php';
    $cartUrl = empty($base) ? 'cart/' : $base . '/cart/';
    $loginUrl = empty($base) ? 'auth/login.php' : $base . '/auth/login.php';
    $logoutUrl = empty($base) ? 'auth/logout.php' : $base . '/auth/logout.php';
    $ordersUrl = empty($base) ? 'user/orders.php' : $base . '/user/orders.php';
    $depositUrl = empty($base) ? 'user/deposit.php' : $base . '/user/deposit.php';
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    $adminUrl = empty($base) ? 'admin/' : $base . '/admin/';
    ?>
    <nav class="nexus-navbar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <!-- Brand -->
                <a class="navbar-brand" href="<?php echo $homeUrl; ?>">
                    <i class="<?php echo htmlspecialchars($storeIcon); ?> me-2" style="color: #A78BFA;"></i><?php echo htmlspecialchars($storeName); ?>
                </a>

                <!-- Right side -->
                <div class="d-flex align-items-center gap-3">
                    <?php if ($isAdmin): ?>
                        <a href="<?php echo $adminUrl; ?>" class="btn-nexus-ghost" style="color: #ef4444;">
                            <i class="fa-solid fa-shield-halved"></i> Quản trị
                        </a>
                    <?php endif; ?>

                    <?php if ($variant === 'store'): ?>
                        <!-- Cart Button -->
                        <a href="<?php echo $cartUrl; ?>" class="nav-icon-btn">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="nexus-cart-count" style="position:absolute;top:-6px;right:-6px;min-width:20px;height:20px;background:#ef4444;border-radius:50%;font-size:0.7rem;font-weight:700;display:<?php echo $cartCount > 0 ? 'flex' : 'none'; ?>;align-items:center;justify-content:center;padding:0 4px;">
                                <?php echo $cartCount; ?>
                            </span>
                        </a>
                    <?php endif; ?>

                    <?php if ($username): ?>
                        <?php if ($variant === 'user'): ?>
                            <a href="<?php echo $ordersUrl; ?>" class="btn-nexus-ghost">
                                <i class="fa-solid fa-receipt me-1"></i> Đơn hàng
                            </a>
                            <a href="<?php echo $depositUrl; ?>" class="btn-nexus-ghost">
                                <i class="fa-solid fa-plus-circle me-1"></i> Nạp tiền
                            </a>
                        <?php endif; ?>

                        <div class="dropdown">
                            <button class="nav-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-regular fa-user me-1"></i> <?php echo htmlspecialchars($username); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-sm"
                                style="background:var(--card-base);border:1px solid var(--border-subtle);border-radius:var(--radius-md);margin-top:8px;min-width:200px;">
                                <?php if ($variant === 'store' && $balance > 0): ?>
                                    <li>
                                        <a class="dropdown-item" href="#" style="font-size:0.85rem;padding:10px 16px;">
                                            <i class="fa-solid fa-wallet text-warning me-2"></i>
                                            Số dư: <?php echo number_format($balance, 0, ',', '.'); ?>đ
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider" style="border-color:var(--border-subtle);margin:4px 0;"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?php echo $ordersUrl; ?>" style="font-size:0.85rem;">
                                    <i class="fa-solid fa-receipt me-2"></i> Đơn hàng
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo $depositUrl; ?>" style="font-size:0.85rem;">
                                    <i class="fa-solid fa-plus-circle me-2"></i> Nạp tiền
                                </a></li>
                                <li><hr class="dropdown-divider" style="border-color:var(--border-subtle);margin:4px 0;"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo $logoutUrl; ?>" style="font-size:0.85rem;">
                                    <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất
                                </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <?php if ($variant === 'store'): ?>
                            <a href="<?php echo $loginUrl; ?>" class="btn-nexus-primary">
                                <i class="fa-regular fa-user me-1"></i> Đăng nhập
                            </a>
                        <?php elseif ($variant === 'user'): ?>
                            <a href="<?php echo $homeUrl; ?>" class="btn-nexus-ghost">
                                <i class="fa-solid fa-home me-1"></i> Trang chủ
                            </a>
                            <a href="<?php echo $loginUrl; ?>" class="btn-nexus-primary">
                                <i class="fa-regular fa-user me-1"></i> Đăng nhập
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php
}

/**
 * Render Footer
 */
function ui_renderFooter() {
    $footerStoreName = function_exists('getStoreName') ? getStoreName() : 'NEXUS STORE';
    $footerIcon = function_exists('nexus_icon') ? nexus_icon() : 'fa-solid fa-ghost';
    ?>
    <footer class="nexus-footer">
        <div class="container">
            <div class="nexus-footer-inner">
                <span class="nexus-footer-brand">
                    <i class="<?php echo htmlspecialchars($footerIcon); ?> me-2" style="color: #A78BFA;"></i><?php echo htmlspecialchars($footerStoreName); ?>
                </span>
                <span class="nexus-footer-text">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($footerStoreName); ?>. Mọi quyền được bảo lưu.</span>
                <div class="nexus-footer-links">
                    <a href="#">Điều khoản</a>
                    <a href="#">Bảo mật</a>
                    <a href="#">Liên hệ</a>
                </div>
            </div>
        </div>
    </footer>
    <?php
}

/**
 * Render Toast Container HTML
 */
function ui_renderToastContainer() {
    echo '<div id="nexus-toast-container"></div>';
}

/**
 * Render Scripts (Bootstrap + Nexus UI JS)
 */
function ui_renderScripts() {
    $base = ui_getBasePath();
    $jsPath = empty($base) ? 'assets/js/nexus-ui.js' : $base . '/assets/js/nexus-ui.js';
    $cartPath = empty($base) ? 'assets/js/cart.js' : $base . '/assets/js/cart.js';
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $jsPath; ?>"></script>
    <script src="<?php echo $cartPath; ?>"></script>
    <?php
}

/**
 * Render Breadcrumb
 *
 * @param array $crumbs Mảng [['label' => '', 'url' => ''], ...]
 * @param string $currentLabel Label của trang hiện tại (không có url)
 */
function ui_renderBreadcrumb($crumbs = [], $currentLabel = '') {
    ?>
    <div class="nexus-breadcrumb">
        <a href="index.php"><i class="fa-solid fa-house"></i></a>
        <?php foreach ($crumbs as $crumb): ?>
            <i class="fa-solid fa-chevron-right nexus-breadcrumb-sep"></i>
            <a href="<?php echo htmlspecialchars($crumb['url']); ?>"><?php echo htmlspecialchars($crumb['label']); ?></a>
        <?php endforeach; ?>
        <?php if ($currentLabel): ?>
            <i class="fa-solid fa-chevron-right nexus-breadcrumb-sep"></i>
            <span><?php echo htmlspecialchars($currentLabel); ?></span>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render Empty State
 *
 * @param string $icon Font Awesome icon class
 * @param string $title Tiêu đề
 * @param string $desc Mô tả
 * @param string|null $actionUrl URL của nút hành động
 * @param string $actionLabel Label của nút hành động
 */
function ui_renderEmptyState($icon = 'fa-boxes-stacked', $title = 'Không có dữ liệu', $desc = '', $actionUrl = null, $actionLabel = 'Quay lại') {
    ?>
    <div class="nexus-empty">
        <div class="nexus-empty-icon">
            <i class="fa-solid <?php echo $icon; ?>"></i>
        </div>
        <div class="nexus-empty-title"><?php echo htmlspecialchars($title); ?></div>
        <?php if ($desc): ?>
            <div class="nexus-empty-desc"><?php echo htmlspecialchars($desc); ?></div>
        <?php endif; ?>
        <?php if ($actionUrl): ?>
            <a href="<?php echo htmlspecialchars($actionUrl); ?>" class="btn-nexus-primary" style="margin-top:1.5rem;">
                <?php echo htmlspecialchars($actionLabel); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render Balance Card
 *
 * @param int $balance Số dư
 * @param bool $compact Chế độ compact cho sidebar
 */
function ui_renderBalanceCard($balance = 0, $compact = false) {
    $pad = $compact ? '' : '';
    ?>
    <div class="nexus-balance-card <?php echo $compact ? 'nexus-balance-compact' : ''; ?>">
        <div class="nexus-balance-label">Số dư hiện tại</div>
        <div class="nexus-balance-value"><?php echo number_format($balance); ?>đ</div>
    </div>
    <?php
}
