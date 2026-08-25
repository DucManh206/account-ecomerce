<?php
require_once __DIR__ . '/../includes/csrf.php';
$currentScript = $_SERVER['SCRIPT_NAME'];
$isActiveDashboard = (strpos($currentScript, 'dashboard.php') !== false) ? 'active' : '';
$isActiveAccounts = (strpos($currentScript, 'crud/accounts/') !== false) ? 'active' : '';
$isActiveCategories = (strpos($currentScript, 'crud/categories/') !== false) ? 'active' : '';
$isActiveUsers = (strpos($currentScript, 'crud/users/') !== false) ? 'active' : '';
$isActiveOrders = (strpos($currentScript, 'crud/orders/') !== false) ? 'active' : '';
$isActiveTopups = (strpos($currentScript, 'crud/topups/') !== false) ? 'active' : '';
$isActiveSettings = (strpos($currentScript, 'crud/settings/') !== false) ? 'active' : '';
$pendingTopups = 0;
if (isset($pdo)) {
    try {
        $pendingTopups = (int) $pdo->query("SELECT COUNT(*) FROM topup_requests WHERE status = 'pending'")->fetchColumn();
    } catch (Throwable $e) {
        $pendingTopups = 0;
    }
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_PATH ?>admin/dashboard.php" class="sidebar-brand"><?= htmlspecialchars(SITE_SHORT_NAME) ?></a>
        <span class="sidebar-role">Khu vực vận hành</span>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_PATH ?>admin/dashboard.php" class="nav-item <?= $isActiveDashboard ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span>Dashboard</span>
        </a>
        <a href="<?= BASE_PATH ?>admin/crud/accounts/list.php" class="nav-item <?= $isActiveAccounts ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
            <span>Quản lý tài khoản</span>
        </a>
        <a href="<?= BASE_PATH ?>admin/crud/categories/list.php" class="nav-item <?= $isActiveCategories ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>Danh mục</span>
        </a>
        <a href="<?= BASE_PATH ?>admin/crud/users/list.php" class="nav-item <?= $isActiveUsers ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span>Thành viên</span>
        </a>
        <a href="<?= BASE_PATH ?>admin/crud/orders/list.php" class="nav-item <?= $isActiveOrders ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span>Đơn hàng</span>
        </a>
        <a href="<?= BASE_PATH ?>admin/crud/topups/list.php" class="nav-item <?= $isActiveTopups ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <span>Yêu cầu nạp tiền</span>
            <?php if ($pendingTopups > 0): ?><span class="nav-count"><?= $pendingTopups ?></span><?php endif; ?>
        </a>
        <div class="nav-section-label">Cấu hình</div>
        <a href="<?= BASE_PATH ?>admin/crud/settings/general.php" class="nav-item <?= $isActiveSettings && basename($currentScript) === 'general.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 21v-7"></path><path d="M4 10V3"></path><path d="M12 21v-9"></path><path d="M12 8V3"></path><path d="M20 21v-5"></path><path d="M20 12V3"></path><path d="M1 14h6"></path><path d="M9 8h6"></path><path d="M17 16h6"></path>
            </svg>
            <span>Cài đặt cửa hàng</span>
        </a>
        <a href="<?= BASE_PATH ?>admin/crud/settings/topup.php" class="nav-item <?= $isActiveSettings && basename($currentScript) === 'topup.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            <span>Kết nối thanh toán</span>
        </a>
        <hr class="nav-divider">
        <a href="<?= BASE_PATH ?>index.php" class="nav-item" target="_blank">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="2" y1="12" x2="22" y2="12"></line>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
            </svg>
            <span>Xem trang chủ</span>
        </a>
        <form method="POST" action="<?= BASE_PATH ?>logout.php" class="sidebar-logout-form">
            <?= csrf_field() ?>
            <button type="submit" class="nav-item nav-logout">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
                <span>Đăng xuất</span>
            </button>
        </form>
    </nav>
    <div class="sidebar-footer">
        <p>Xin chào, <strong><?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'Quản trị viên') ?></strong></p>
    </div>
</aside>
