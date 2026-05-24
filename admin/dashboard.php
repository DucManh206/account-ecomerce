<?php
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$totalAccounts = $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
$availableAccounts = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status='available'")->fetchColumn();
$soldAccounts = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status='sold'")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(price) FROM orders")->fetchColumn() ?: 0;

// Lấy 5 đơn hàng gần nhất
$recentOrders = [];
try {
    $recentOrders = $pdo->query("
        SELECT orders.*, users.username, accounts.name AS account_name
        FROM orders
        JOIN users ON orders.user_id = users.id
        JOIN accounts ON orders.account_id = accounts.id
        ORDER BY orders.id DESC
        LIMIT 5
    ")->fetchAll();
} catch (Exception $e) {
    $recentOrders = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/admin/css/admin.css">
    <style>
        /* Custom Asymmetric Layout overrides */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }
        
        .dashboard-col-main, .dashboard-col-side {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        
        /* Primary KPI Hero */
        .hero-stat-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 36px;
            position: relative;
            transition: var(--transition);
        }
        
        .hero-stat-card:hover {
            border-color: #555;
        }
        
        .hero-number {
            font-size: 3.25rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1px;
            line-height: 1.1;
        }
        
        .hero-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px;
            font-weight: 600;
        }
        
        /* Asymmetric grid in main */
        .asym-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 24px;
        }
        
        .asym-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 24px;
            transition: var(--transition);
        }
        
        .asym-card:hover {
            border-color: #555;
        }
        
        .asym-number {
            font-size: 2rem;
            font-weight: 700;
            color: #ffffff;
        }
        
        .asym-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 4px;
            letter-spacing: 0.5px;
        }
        
        /* Progress bar */
        .progress-bar-wrapper {
            width: 100%;
            background-color: #1a1a1a;
            height: 6px;
            margin-top: 14px;
            border: 1px solid #262626;
        }
        
        .progress-bar-fill {
            background-color: #ffffff;
            height: 100%;
            transition: width 0.3s ease;
        }
        
        /* Telemetry Panel */
        .telemetry-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 24px;
            transition: var(--transition);
        }
        
        .telemetry-card:hover {
            border-color: #555;
        }
        
        .telemetry-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        
        .telemetry-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            font-size: 0.9rem;
        }
        
        .telemetry-row:last-child {
            border-bottom: none;
        }
        
        .telemetry-label {
            color: var(--text-muted);
        }
        
        .telemetry-value {
            color: #ffffff;
            font-weight: 600;
            font-family: monospace;
        }
        
        /* Health dots */
        .health-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50% !important;
            margin-right: 6px;
            vertical-align: middle;
        }
        
        .health-green {
            background-color: var(--success);
            box-shadow: 0 0 8px var(--success);
        }
        
        .health-orange {
            background-color: var(--warning);
            box-shadow: 0 0 8px var(--warning);
        }
        
        /* Sidebar SVG spacing & transition */
        .sidebar-nav .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
        }
        
        .sidebar-nav .nav-item svg {
            stroke: var(--sidebar-text);
            transition: var(--transition);
        }
        
        .sidebar-nav .nav-item:hover svg,
        .sidebar-nav .nav-item.active svg {
            stroke: currentColor;
        }

        .sidebar-nav .nav-item.active svg {
            stroke: #0a0a0a;
        }
        
        /* Table density & custom look */
        .dense-table-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 24px;
        }
        
        .dense-table-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #ffffff;
            margin-bottom: 16px;
        }

        .dense-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .dense-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background-color: #0d0d0d;
            border: 1px solid var(--border-color);
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .dense-item:hover {
            border-color: #555;
        }

        .dense-item-meta {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-top: 2px;
        }

        .dense-item-price {
            font-weight: 700;
            color: var(--success);
        }
        
        /* Quick Actions list */
        .actions-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .action-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            color: #ffffff;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .action-link:hover {
            border-color: #ffffff;
            background-color: #161616;
        }
        
        .action-link svg {
            stroke: var(--text-muted);
            transition: var(--transition);
        }
        
        .action-link:hover svg {
            stroke: #ffffff;
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .asym-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="main-content">
            <header class="topbar">
                <h1>Hệ thống kiểm soát</h1>
                <div class="topbar-info">
                    <span><?= date('d/m/Y H:i') ?></span>
                </div>
            </header>

            <div class="content-body">
                <div class="dashboard-grid">
                    
                    <!-- Cột chính bên trái -->
                    <div class="dashboard-col-main">
                        
                        <!-- Hero Card: Doanh Thu -->
                        <div class="hero-stat-card">
                            <div class="hero-number"><?= number_format($totalRevenue, 0, ',', '.') ?>đ</div>
                            <div class="hero-label">Doanh thu tích lũy hệ thống</div>
                        </div>

                        <!-- Asymmetric Info Row -->
                        <div class="asym-grid">
                            
                            <!-- Card chứa thông số bán hàng kèm progress bar -->
                            <div class="asym-card">
                                <div class="asym-number"><?= $soldAccounts ?> <span style="font-size: 1rem; font-weight: normal; color: var(--text-muted);">/ <?= $totalAccounts ?> tổng số</span></div>
                                <div class="asym-label">Tài khoản đã bán</div>
                                <div class="progress-bar-wrapper">
                                    <?php 
                                    $salesRate = $totalAccounts > 0 ? round(($soldAccounts / $totalAccounts) * 100, 1) : 0;
                                    ?>
                                    <div class="progress-bar-fill" style="width: <?= $salesRate ?>%;"></div>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px; text-align: right; font-weight: 500;">
                                    Tỉ lệ bán: <?= $salesRate ?>%
                                </div>
                            </div>
                            
                            <!-- Card nhỏ gọn hiển thị lượng khách hàng -->
                            <div class="asym-card" style="display: flex; flex-direction: column; justify-content: center;">
                                <div class="asym-number"><?= $totalUsers ?></div>
                                <div class="asym-label">Khách hàng đăng ký</div>
                            </div>

                        </div>

                        <!-- Nhật ký giao dịch gần đây -->
                        <div class="dense-table-card">
                            <div class="dense-table-title">Nhật ký giao dịch gần đây</div>
                            <div class="dense-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <div class="dense-item">
                                        <div>
                                            <div style="font-weight: 600; color: #ffffff;"><?= htmlspecialchars($order['account_name']) ?></div>
                                            <div class="dense-item-meta">
                                                Người mua: @<?= htmlspecialchars($order['username']) ?> &bull; <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right; display: flex; align-items: center; gap: 16px;">
                                            <span class="dense-item-price">+<?= number_format($order['price'], 0, ',', '.') ?>đ</span>
                                            <span class="badge badge-green" style="font-size: 0.65rem; padding: 2px 6px;">Hoàn tất</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($recentOrders)): ?>
                                    <div class="empty" style="border: 1px dashed var(--border-color); padding: 24px !important;">Chưa có giao dịch phát sinh.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- Cột phụ bên phải -->
                    <div class="dashboard-col-side">
                        
                        <!-- Telemetry Panel -->
                        <div class="telemetry-card">
                            <div class="telemetry-title">Trạng thái node hệ thống</div>
                            
                            <div class="telemetry-row">
                                <span class="telemetry-label">Dịch vụ CSDL</span>
                                <span class="telemetry-value">
                                    <span class="health-dot health-green"></span>Online
                                </span>
                            </div>

                            <div class="telemetry-row">
                                <span class="telemetry-label">Hàng đang bán</span>
                                <span class="telemetry-value">
                                    <?php if ($availableAccounts < 3): ?>
                                        <span class="health-dot health-orange"></span><?= $availableAccounts ?> acc (Thấp)
                                    <?php else: ?>
                                        <span class="health-dot health-green"></span><?= $availableAccounts ?> acc
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="telemetry-row">
                                <span class="telemetry-label">Timezone</span>
                                <span class="telemetry-value" style="font-size: 0.8rem;">Asia/Ho_Chi_Minh</span>
                            </div>
                        </div>
                        
                        <!-- Quick Actions Dense list -->
                        <div class="telemetry-card" style="padding: 20px;">
                            <div class="telemetry-title" style="margin-bottom: 12px; border-bottom: none; padding-bottom: 0;">Thao tác nhanh</div>
                            <div class="actions-list">
                                <a href="crud/accounts/add.php" class="action-link">
                                    <span>Thêm tài khoản</span>
                                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </a>
                                <a href="crud/users/list.php" class="action-link">
                                    <span>Cộng tiền thành viên</span>
                                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                    </svg>
                                </a>
                                <a href="crud/categories/list.php" class="action-link">
                                    <span>Xem danh mục</span>
                                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>

                    </div>
                    
                </div>
            </div>
        </main>
    </div>
</body>
</html>
