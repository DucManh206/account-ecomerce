<?php
require_once __DIR__ . '/../../lib/settings_modules.php';

function admin_renderSidebar($currentPage = '')
{
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $adminPos = strrpos($scriptPath, '/admin');
    $basePath = ($adminPos !== false) ? substr($scriptPath, 0, $adminPos) : '';

    $navItems = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'href' => $basePath . '/admin/dashboard.php'],
        ['key' => 'products', 'label' => 'Sản phẩm', 'icon' => 'fa-box-open', 'href' => $basePath . '/admin/manage/products.php'],
        ['key' => 'users', 'label' => 'Người dùng', 'icon' => 'fa-users', 'href' => $basePath . '/admin/manage/users.php'],
        ['key' => 'categories', 'label' => 'Danh mục', 'icon' => 'fa-layer-group', 'href' => $basePath . '/admin/manage/categories.php'],
        ['key' => 'orders', 'label' => 'Đơn hàng', 'icon' => 'fa-receipt', 'href' => $basePath . '/admin/manage/orders.php'],
        ['key' => 'deposits', 'label' => 'Nạp tiền', 'icon' => 'fa-wallet', 'href' => $basePath . '/admin/manage/deposits.php'],
    ];

    $configItems = [
        ['key' => 'settings', 'label' => 'Cấu hình hệ thống', 'icon' => 'fa-gears', 'href' => $basePath . '/admin/manage/settings.php'],
        ['key' => 'account_fields', 'label' => 'Cấu trúc tài khoản', 'icon' => 'fa-list-check', 'href' => $basePath . '/admin/manage/account-fields.php'],
    ];

    ob_start();
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <div class="brand-text">
            <i class="<?php echo nexus_icon(); ?>"></i>
            <span>NEXUS PANEL</span>
        </div>
    </div>

    <div class="admin-sidebar-nav">
        <div class="sidebar-section-label">QUẢN LÝ</div>
        <?php foreach ($navItems as $item): ?>
            <a href="<?php echo $item['href']; ?>" class="sidebar-item <?php echo ($currentPage === $item['key']) ? 'active' : ''; ?>">
                <i class="fa-solid <?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>

        <div class="sidebar-divider"></div>
        <div class="sidebar-section-label">HỆ THỐNG</div>
        <?php foreach ($configItems as $item): ?>
            <a href="<?php echo $item['href']; ?>" class="sidebar-item <?php echo ($currentPage === $item['key']) ? 'active' : ''; ?>">
                <i class="fa-solid <?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>

        <div class="sidebar-divider"></div>
        <a href="<?php echo $basePath; ?>/index.php" class="sidebar-item">
            <i class="fa-solid fa-store"></i>
            <span>Xem cửa hàng</span>
        </a>
        <a href="<?php echo $basePath; ?>/auth/logout.php" class="sidebar-item" style="color: #ef4444;">
            <i class="fa-solid fa-power-off"></i>
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>
<?php
    return ob_get_clean();
}
