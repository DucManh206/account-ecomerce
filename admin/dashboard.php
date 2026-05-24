<?php
require_once __DIR__ . '/config/db.php';

require_admin();

// Thống kê số lượng cơ bản
$totalAccounts = $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
$availableAccounts = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status='available'")->fetchColumn();
$soldAccounts = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status='sold'")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(price) FROM orders")->fetchColumn() ?: 0;

// Lấy danh sách 5 đơn hàng gần đây
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

// Lấy danh sách 5 giao dịch nạp tiền gần nhất
$recentTopups = [];
$totalDeposits = 0;
try {
    $recentTopups = $pdo->query("
        SELECT sepay_transactions.*, users.username
        FROM sepay_transactions
        JOIN users ON sepay_transactions.user_id = users.id
        ORDER BY sepay_transactions.id DESC
        LIMIT 5
    ")->fetchAll();
    
    $totalDeposits = $pdo->query("SELECT SUM(amount) FROM sepay_transactions")->fetchColumn() ?: 0;
} catch (Exception $e) {
    $recentTopups = [];
    $totalDeposits = 0;
}

// Thống kê số tài khoản theo danh mục
$categoryStock = [];
try {
    $categoryStock = $pdo->query("
        SELECT c.id, c.name, 
               COUNT(a.id) AS total_count,
               SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) AS available_count
        FROM categories c
        LEFT JOIN accounts a ON c.id = a.category_id
        GROUP BY c.id, c.name
    ")->fetchAll();
} catch (Exception $e) {
    $categoryStock = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống quản lý - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/admin/css/admin.css">
    <style>
        /* CSS cấu hình giao diện trang quản trị */
        :root {
            --card-bg: #101114;
            --border-color: #1c1d22;
            --background: #08090a;
            --text-main: #f3f4f6;
            --text-muted: #71717a;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        body {
            background-color: var(--background);
            color: var(--text-main);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 8fr 5fr;
            gap: 20px;
            align-items: start;
        }

        .dashboard-col-main, .dashboard-col-side {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Panel Container */
        .panel-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 20px;
            position: relative;
            transition: border-color 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;
        }

        .panel-card:hover {
            border-color: #3f3f46;
            box-shadow: 0 4px 20px rgba(0,0,0,0.35);
        }

        /* Top KPI Section Header */
        .kpi-main-title {
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
        }

        .kpi-value {
            font-family: 'Courier New', Courier, monospace;
            font-size: 2.85rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -1px;
            line-height: 1.1;
        }

        .kpi-meta {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kpi-meta span {
            color: #d1d5db;
            font-weight: 600;
        }

        /* Stock breakdown progress list style */
        .inventory-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 12px;
        }

        .inventory-item {
            font-size: 0.8rem;
        }

        .inventory-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .inventory-name {
            color: #e5e7eb;
            font-weight: 500;
        }

        .inventory-count {
            font-family: monospace;
            color: var(--text-muted);
        }

        .inventory-bar-wrapper {
            width: 100%;
            background-color: #18181b;
            height: 3px;
            overflow: hidden;
            border: 1px solid #27272a;
        }

        .inventory-bar-fill {
            height: 100%;
            background-color: #e4e4e7;
            transition: width 0.3s ease;
        }

        .inventory-bar-low {
            background-color: var(--warning);
        }

        /* Activity split layouts */
        .activity-split-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Unified Stroke SVG Actions list */
        .action-item-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .action-item-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg);
            color: #e4e4e7;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .action-item-link:hover {
            border-color: #3f3f46;
            background-color: #18181b;
            color: #ffffff;
        }

        .action-item-link svg {
            stroke: var(--text-muted);
            transition: stroke 0.15s ease;
        }

        .action-item-link:hover svg {
            stroke: #ffffff;
        }

        /* Activity list stream style */
        .activity-dense-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .activity-dense-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background-color: #09090b;
            border: 1px solid var(--border-color);
            font-size: 0.82rem;
            transition: all 0.15s ease;
        }

        .activity-dense-item:hover {
            border-color: #3f3f46;
        }

        .activity-dense-meta {
            color: var(--text-muted);
            font-size: 0.75rem;
            margin-top: 2px;
        }

        .badge-compact {
            font-family: monospace;
            font-size: 0.65rem;
            padding: 1px 5px;
            border: 1px solid transparent;
            font-weight: 600;
        }

        .badge-compact-success {
            background-color: rgba(16, 185, 129, 0.05);
            color: var(--success);
            border-color: var(--success);
        }

        .badge-compact-info {
            background-color: rgba(59, 130, 246, 0.05);
            color: #3b82f6;
            border-color: #3b82f6;
        }

        @media (max-width: 1120px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .activity-split-grid {
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
                <h1>Hệ thống quản trị</h1>
                <div class="topbar-info">
                    <span><?= date('d/m/Y H:i') ?></span>
                </div>
            </header>

            <div class="content-body">
                
                <!-- Bố cục lưới chia cột quản trị -->
                <div class="dashboard-grid">
                    
                    <!-- Cột bên trái hiển thị doanh thu và các bảng thống kê -->
                    <div class="dashboard-col-main">
                        
                        <!-- Card thống kê doanh thu và các đơn hàng -->
                        <div class="panel-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div class="kpi-main-title">
                                    <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                    DOANH THU TOÀN HỆ THỐNG (TÍCH LŨY)
                                </div>
                                <div class="kpi-value"><?= number_format($totalRevenue, 0, ',', '.') ?>đ</div>
                            </div>
                            <div class="kpi-meta">
                                <div>Đơn hàng: <span><?= $totalOrders ?> giao dịch</span></div>
                                <span style="color: #27272a;">&bull;</span>
                                <div>Nạp ngân hàng: <span><?= number_format($totalDeposits, 0, ',', '.') ?>đ</span></div>
                                <span style="color: #27272a;">&bull;</span>
                                <div>Thành viên: <span><?= $totalUsers ?></span></div>
                            </div>
                        </div>

                        <!-- Chia cột hóa đơn và lịch sử nạp tiền -->
                        <div class="activity-split-grid">
                            
                            <!-- Danh sách đơn hàng gần đây -->
                            <div class="panel-card">
                                <div class="kpi-main-title" style="margin-bottom: 14px;">
                                    <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                        <line x1="1" y1="10" x2="23" y2="10"></line>
                                    </svg>
                                    HÓA ĐƠN MUA HÀNG GẦN ĐÂY
                                </div>
                                <div class="activity-dense-list">
                                    <?php foreach ($recentOrders as $order): ?>
                                        <div class="activity-dense-item">
                                            <div>
                                                <div style="font-weight: 600; color: #ffffff;"><?= htmlspecialchars($order['account_name']) ?></div>
                                                <div class="activity-dense-meta">
                                                    Khách: @<?= htmlspecialchars($order['username']) ?> &bull; <?= date('H:i d/m', strtotime($order['created_at'])) ?>
                                                </div>
                                            </div>
                                            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end;">
                                                <span style="font-family: monospace; font-weight: 700; color: #e4e4e7;">
                                                    -<?= number_format($order['price'], 0, ',', '.') ?>đ
                                                </span>
                                                <span class="badge-compact badge-compact-info" style="margin-top: 4px;">Đã mua</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentOrders)): ?>
                                        <div class="empty" style="border: 1px dashed var(--border-color); padding: 24px !important;">Chưa có giao dịch phát sinh.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Danh sách giao dịch nạp tiền gần đây -->
                            <div class="panel-card">
                                <div class="kpi-main-title" style="margin-bottom: 14px;">
                                    <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                    LỊCH SỬ NẠP TIỀN GẦN ĐÂY
                                </div>
                                <div class="activity-dense-list">
                                    <?php foreach ($recentTopups as $topup): ?>
                                        <div class="activity-dense-item">
                                            <div>
                                                <div style="font-weight: 600; color: #ffffff;">Nạp số dư SePay</div>
                                                <div class="activity-dense-meta">
                                                    Khách: @<?= htmlspecialchars($topup['username']) ?> &bull; <?= date('H:i d/m', strtotime($topup['transaction_date'])) ?>
                                                </div>
                                            </div>
                                            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end;">
                                                <span style="font-family: monospace; font-weight: 700; color: var(--success);">
                                                    +<?= number_format($topup['amount'], 0, ',', '.') ?>đ
                                                </span>
                                                <span class="badge-compact badge-compact-success" style="margin-top: 4px;">Ngân hàng</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentTopups)): ?>
                                        <div class="empty" style="border: 1px dashed var(--border-color); padding: 24px !important;">Chưa có lịch sử nạp tiền.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Cột bên phải: phân bố kho hàng và thao tác nhanh -->
                    <div class="dashboard-col-side">
                        
                        <!-- Bảng thống kê kho hàng khả dụng -->
                        <div class="panel-card">
                            <div class="kpi-main-title">
                                <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                PHÂN BỐ KHO HÀNG KHẢ DỤNG
                            </div>
                            <div class="inventory-list">
                                <?php foreach ($categoryStock as $cat): 
                                    $total = $cat['total_count'];
                                    $avail = $cat['available_count'];
                                    $pct = $total > 0 ? ($avail / $total) * 100 : 0;
                                    $lowStock = $avail < 3;
                                ?>
                                    <div class="inventory-item">
                                        <div class="inventory-header">
                                            <span class="inventory-name"><?= htmlspecialchars($cat['name']) ?></span>
                                            <span class="inventory-count"><?= $avail ?> / <?= $total ?> acc</span>
                                        </div>
                                        <div class="inventory-bar-wrapper">
                                            <div class="inventory-bar-fill <?= $lowStock ? 'inventory-bar-low' : '' ?>" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($categoryStock)): ?>
                                    <div class="empty" style="padding: 10px !important;">Kho hàng chưa phân loại.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Danh sách các thao tác nhanh -->
                        <div class="panel-card" style="padding: 16px;">
                            <div class="kpi-main-title" style="margin-bottom: 12px;">THAO TÁC NHANH HỆ THỐNG</div>
                            <div class="action-item-list">
                                <a href="crud/accounts/add.php" class="action-item-link">
                                    <span>Thêm tài khoản restock</span>
                                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </a>
                                <a href="crud/settings/topup.php" class="action-item-link">
                                    <span>Cấu hình API ngân hàng</span>
                                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                    </svg>
                                </a>
                                <a href="crud/categories/list.php" class="action-item-link">
                                    <span>Xem quản lý danh mục</span>
                                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
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
