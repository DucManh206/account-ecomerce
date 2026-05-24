<?php
/**
 * Shared frontend UI module.
 *
 * Gom các phần UI dùng lặp lại 
 */

if (!function_exists('ui_getBasePath')) {
function ui_getBasePath(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    // Detect the real project root from browser-facing route folders.
    // Important for shims like /user/orders.php and /user/user/orders.php:
    // assets must still resolve to /assets/..., not /user/assets/...
    foreach (['/crud/', '/admin/', '/user/', '/cart/', '/auth/', '/api/'] as $segment) {
        $pos = strpos($script, $segment);
        if ($pos !== false) {
            $root = substr($script, 0, $pos);
            return $root === '' ? '' : rtrim($root, '/');
        }
    }

    $root = str_replace('\\', '/', dirname($script));
    $root = rtrim($root, '/');
    if ($root === '/' || $root === '.' || $root === '\\') $root = '';
    return $root === '' ? '' : rtrim($root, '/');
}}

if (!function_exists('ui_url')) {
function ui_url(string $path = ''): string
{
    $base = ui_getBasePath();
    $path = ltrim($path, '/');
    return ($base === '' ? '' : $base) . '/' . $path;
}}

if (!function_exists('ui_e')) {
function ui_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}}

if (!function_exists('ui_renderHead')) {
function ui_renderHead(string $title = 'Nexus Store', string $theme = 'store'): void
{
    $title = trim($title) !== '' ? $title : 'Nexus Store';
    $base = ui_getBasePath();
    echo '<meta charset="UTF-8">' . PHP_EOL;
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . PHP_EOL;
    echo '<title>' . ui_e($title) . '</title>' . PHP_EOL;
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . PHP_EOL;
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . PHP_EOL;
    echo '<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">' . PHP_EOL;
    echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">' . PHP_EOL;
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' . PHP_EOL;
    echo '<link rel="stylesheet" href="' . ui_e($base . '/assets/css/nexus.css') . '">' . PHP_EOL;
    echo '<script>document.documentElement.setAttribute("data-theme", ' . json_encode($theme, JSON_UNESCAPED_UNICODE) . ');</script>' . PHP_EOL;
}
}

if (!function_exists('ui_renderNavbar')) {
function ui_renderNavbar(?string $username = null, int $cartQuantity = 0, $balance = 0, string $active = 'store'): void
{
    $isLoggedIn = !empty($username);
    $balanceText = number_format((float)$balance, 0, ',', '.') . 'đ';
    ?>
    <nav class="navbar navbar-expand-lg nexus-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= ui_e(ui_url('index.php')) ?>">
                <i class="fa-solid fa-bolt me-2"></i>Nexus Store
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nexusNavbar" aria-controls="nexusNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nexusNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link text-light <?= $active === 'store' ? 'active fw-semibold' : '' ?>" href="<?= ui_e(ui_url('index.php')) ?>">Cửa hàng</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item"><a class="nav-link text-light <?= $active === 'user' ? 'active fw-semibold' : '' ?>" href="<?= ui_e(ui_url('user/orders.php')) ?>">Đơn hàng</a></li>
                        <li class="nav-item"><a class="nav-link text-light" href="<?= ui_e(ui_url('user/deposit.php')) ?>">Nạp tiền</a></li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <a class="nav-icon-btn position-relative" href="<?= ui_e(ui_url('cart/index.php')) ?>">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <?php if ($cartQuantity > 0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= (int)$cartQuantity ?></span><?php endif; ?>
                    </a>
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-user me-1"></i><?= ui_e($username) ?> · <?= ui_e($balanceText) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= ui_e(ui_url('user/orders.php')) ?>"><i class="fa-solid fa-receipt me-2"></i>Đơn hàng</a></li>
                                <li><a class="dropdown-item" href="<?= ui_e(ui_url('user/deposit.php')) ?>"><i class="fa-solid fa-wallet me-2"></i>Nạp tiền</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= ui_e(ui_url('auth/logout.php')) ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-btn" href="<?= ui_e(ui_url('auth/login.php')) ?>">Đăng nhập</a>
                        <a class="nav-btn" href="<?= ui_e(ui_url('auth/register.php')) ?>">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php
}
}

if (!function_exists('ui_renderBreadcrumb')) {
function ui_renderBreadcrumb(array $items): void
{
    echo '<nav aria-label="breadcrumb" class="container mt-4"><ol class="breadcrumb nexus-breadcrumb">';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        $label = is_array($item) ? ($item['label'] ?? '') : (string)$item;
        $url = is_array($item) ? ($item['url'] ?? null) : null;
        if ($i === $last || empty($url)) echo '<li class="breadcrumb-item active" aria-current="page">' . ui_e($label) . '</li>';
        else echo '<li class="breadcrumb-item"><a href="' . ui_e($url) . '">' . ui_e($label) . '</a></li>';
    }
    echo '</ol></nav>';
}
}

if (!function_exists('ui_renderEmptyState')) {
function ui_renderEmptyState(string $icon = 'fa-box-open', string $title = 'Chưa có dữ liệu', string $message = '', ?string $actionUrl = null, ?string $actionLabel = null): void
{
    ?>
    <div class="nexus-empty-state text-center py-5">
        <div class="empty-icon mb-3"><i class="fa-solid <?= ui_e($icon) ?>"></i></div>
        <h3><?= ui_e($title) ?></h3>
        <?php if ($message !== ''): ?><p class="text-secondary mb-4"><?= ui_e($message) ?></p><?php endif; ?>
        <?php if ($actionLabel && $actionUrl): ?><a class="btn btn-light" href="<?= ui_e($actionUrl) ?>"><?= ui_e($actionLabel) ?></a><?php endif; ?>
    </div>
    <?php
}
}

if (!function_exists('ui_renderFooter')) {
function ui_renderFooter(): void
{
    ?>
    <footer class="nexus-footer mt-5 py-4">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-2 text-secondary">
            <span>© <?= date('Y') ?> Nexus Store</span>
            <span>Uy tín · Tự động · Hỗ trợ 24/7</span>
        </div>
    </footer>
    <?php
}
}

if (!function_exists('ui_renderToastContainer')) {
function ui_renderToastContainer(): void
{
    echo '<div id="nexus-toast-container"></div>' . PHP_EOL;
}
}

if (!function_exists('ui_renderScripts')) {
function ui_renderScripts(): void
{
    $base = ui_getBasePath();
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>' . PHP_EOL;
    echo '<script src="' . ui_e($base . '/assets/js/nexus-ui.js') . '"></script>' . PHP_EOL;
}
}
