<?php
// ============================================================
// Dashboard - Trang quản trị chính
// ============================================================
require_once __DIR__ . '/config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Lấy thống kê
$totalAccounts = $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
$availableAccounts = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status='available'")->fetchColumn();
$soldAccounts = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status='sold'")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/admin/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Account Shop</h2>
                <span class="sidebar-role">Admin</span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <span class="nav-icon">&#x1F3E0;</span>
                    <span>Dashboard</span>
                </a>
                <a href="crud/accounts/list.php" class="nav-item">
                    <span class="nav-icon">&#x1F4CB;</span>
                    <span>Quản lý tài khoản</span>
                </a>
                <a href="crud/categories/list.php" class="nav-item">
                    <span class="nav-icon">&#x1F4C1;</span>
                    <span>Danh mục</span>
                </a>
                <hr class="nav-divider">
                <a href="../index.php" class="nav-item" target="_blank">
                    <span class="nav-icon">&#x1F30D;</span>
                    <span>Xem trang chủ</span>
                </a>
                <a href="logout.php" class="nav-item nav-logout">
                    <span class="nav-icon">&#x1F6AA;</span>
                    <span>Đăng xuất</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <p>Xin chào, <strong><?= htmlspecialchars($_SESSION['admin_fullname']) ?></strong></p>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-content">
            <header class="topbar">
                <h1>Dashboard</h1>
                <div class="topbar-info">
                    <span><?= date('d/m/Y H:i') ?></span>
                </div>
            </header>

            <div class="content-body">
                <div class="stats-grid">
                    <div class="stat-card stat-total">
                        <div class="stat-number"><?= $totalAccounts ?></div>
                        <div class="stat-label">Tổng số tài khoản</div>
                    </div>
                    <div class="stat-card stat-available">
                        <div class="stat-number"><?= $availableAccounts ?></div>
                        <div class="stat-label">Đang bán</div>
                    </div>
                    <div class="stat-card stat-sold">
                        <div class="stat-number"><?= $soldAccounts ?></div>
                        <div class="stat-label">Đã bán</div>
                    </div>
                    <div class="stat-card stat-category">
                        <div class="stat-number"><?= $totalCategories ?></div>
                        <div class="stat-label">Danh mục</div>
                    </div>
                </div>

                <div class="quick-actions">
                    <h2>Thao tác nhanh</h2>
                    <div class="action-grid">
                        <a href="crud/accounts/add.php" class="action-card">
                            <span class="action-icon">&#x2795;</span>
                            <span>Thêm tài khoản mới</span>
                        </a>
                        <a href="crud/accounts/list.php" class="action-card">
                            <span class="action-icon">&#x1F50D;</span>
                            <span>Xem danh sách</span>
                        </a>
                        <a href="crud/categories/list.php" class="action-card">
                            <span class="action-icon">&#x1F4C2;</span>
                            <span>Quản lý danh mục</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
