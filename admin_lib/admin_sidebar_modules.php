<?php
require_once __DIR__ . '/../lib/settings_modules.php';

function admin_renderSidebar($currentPage = '')
{
    // Tự động detect base path - lấy path gốc không có /admin
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $scriptDir = dirname($scriptPath); // /web_updated - Copy/admin hoặc /admin
    
    // Tìm vị trí /admin trong path
    $adminPos = strpos($scriptDir, '/admin');
    if ($adminPos !== false) {
        $basePath = substr($scriptDir, 0, $adminPos); // /web_updated - Copy
    } else {
        $basePath = $scriptDir; // fallback
    }

    $navItems = [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fa-gauge',
            'href' => $basePath . '/admin/dashboard.php',
        ],
        [
            'key' => 'products',
            'label' => 'Sản phẩm',
            'icon' => 'fa-box-open',
            'href' => $basePath . '/admin/manage/products.php',
        ],
        [
            'key' => 'users',
            'label' => 'Người dùng',
            'icon' => 'fa-users',
            'href' => $basePath . '/admin/manage/users.php',
        ],
        [
            'key' => 'categories',
            'label' => 'Danh mục & Loại',
            'icon' => 'fa-layer-group',
            'href' => $basePath . '/admin/manage/categories.php',
        ],
        [
            'key' => 'orders',
            'label' => 'Đơn hàng',
            'icon' => 'fa-receipt',
            'href' => $basePath . '/admin/manage/orders.php',
        ],
        [
            'key' => 'deposits',
            'label' => 'Nạp tiền',
            'icon' => 'fa-wallet',
            'href' => $basePath . '/admin/manage/deposits.php',
        ],
    ];

    $bottomItems = [
        [
            'key' => 'settings',
            'label' => 'Cấu hình',
            'icon' => 'fa-gear',
            'href' => $basePath . '/admin/manage/settings.php',
        ],
        [
            'key' => 'account_fields',
            'label' => 'Loại field TK',
            'icon' => 'fa-list-check',
            'href' => $basePath . '/admin/manage/account-fields.php',
        ],
        [
            'key' => 'back_to_shop',
            'label' => 'Trở về Shop',
            'icon' => 'fa-store',
            'href' => $basePath . '/index.php',
            'divider' => true,
        ],
        [
            'key' => 'logout',
            'label' => 'Đăng xuất',
            'icon' => 'fa-right-from-bracket',
            'href' => $basePath . '/auth/logout.php',
            'class' => 'text-danger',
        ],
    ];

    ob_start();
?>
    <div class="sidebar" style="width: 260px; min-width: 260px;">
        <div class="sidebar-brand">
            <i class="<?php echo nexus_icon(); ?> me-2"></i><?php echo htmlspecialchars(getStoreName()); ?>
        </div>

        <div class="sidebar-section-label">QUẢN LÝ</div>
        <?php foreach ($navItems as $item): ?>
            <?php if (!empty($item['divider'])): ?>
                <hr class="mx-3 border-secondary">
            <?php endif; ?>
            <a href="<?php echo $item['href']; ?>"
                class="<?php echo ($currentPage === $item['key']) ? 'active' : ''; ?>"
                <?php echo (!empty($item['disabled'])) ? 'onclick="return false;" style="opacity:0.4;cursor:not-allowed;"' : ''; ?>>
                <i class="fa-solid <?php echo $item['icon']; ?> me-2"></i>
                <?php echo htmlspecialchars($item['label']); ?>
                <?php if (!empty($item['disabled'])): ?>
                    <span class="badge bg-secondary ms-auto" style="font-size:0.6rem;">Sắp ra</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>

        <hr class="mx-3 border-secondary">

        <?php foreach ($bottomItems as $item): ?>
            <?php if (!empty($item['divider'])): ?>
                <hr class="mx-3 border-secondary">
            <?php endif; ?>
            <a href="<?php echo $item['href']; ?>"
                class="<?php echo htmlspecialchars($item['class'] ?? ''); ?>">
                <i class="fa-solid <?php echo $item['icon']; ?> me-2"></i>
                <?php echo htmlspecialchars($item['label']); ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php
    return ob_get_clean();
}
