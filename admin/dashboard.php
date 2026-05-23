<?php
require_once __DIR__ . "/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/crud/dashboard/admin_stats_modules.php";

$stats = admin_getDashboardStats();
$username = htmlspecialchars($_SESSION['username'] ?? 'Admin');

ob_start();
?>
<!-- Welcome Banner -->
<div class="dash-welcome">
    <div class="dash-welcome-bg"></div>
    <div class="dash-welcome-content">
        <div class="dash-welcome-text">
            <span class="dash-greeting">Xin chào, <strong><?php echo $username; ?></strong></span>
            <h1 class="dash-welcome-title">Dashboard</h1>
            <p class="dash-welcome-sub">Chào mừng bạn quay trở lại! Hôm nay bạn muốn quản lý gì?</p>
        </div>
        <div class="dash-welcome-actions">
            <a href="crud/products/list.php" class="nx-btn nx-btn-primary">
                <i class="fa-solid fa-plus"></i> Thêm sản phẩm
            </a>
            <a href="crud/orders/list.php" class="nx-btn dash-btn-outline">
                <i class="fa-solid fa-receipt"></i> Đơn hàng
            </a>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="dash-stats">
    <div class="dash-stat-card" data-delay="0">
        <div class="dash-stat-icon" style="background:linear-gradient(135deg,#6E56CF,#4F46E5);">
            <i class="fa-solid fa-box-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value"><span class="stat-counter" data-target="<?php echo $stats['total_products']; ?>">0</span></div>
            <div class="dash-stat-label">Sản phẩm</div>
        </div>
        <div class="dash-stat-trend up">
            <i class="fa-solid fa-arrow-up"></i> Tổng kho
        </div>
    </div>
    <div class="dash-stat-card" data-delay="100">
        <div class="dash-stat-icon" style="background:linear-gradient(135deg,#38BDF8,#0EA5E9);">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value"><span class="stat-counter" data-target="<?php echo $stats['total_users']; ?>">0</span></div>
            <div class="dash-stat-label">Người dùng</div>
        </div>
        <div class="dash-stat-trend up">
            <i class="fa-solid fa-user-check"></i> <?php echo $stats['total_clients']; ?> client
        </div>
    </div>
    <div class="dash-stat-card" data-delay="200">
        <div class="dash-stat-icon" style="background:linear-gradient(135deg,#EF4444,#DC2626);">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value"><span class="stat-counter" data-target="<?php echo $stats['total_admins']; ?>">0</span></div>
            <div class="dash-stat-label">Quản trị viên</div>
        </div>
        <div class="dash-stat-trend">
            <i class="fa-solid fa-user-gear"></i> Hệ thống
        </div>
    </div>
    <div class="dash-stat-card" data-delay="300">
        <div class="dash-stat-icon" style="background:linear-gradient(135deg,#10B981,#059669);">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value text-success"><?php echo number_format($stats['total_balance'], 0, ',', '.'); ?>đ</div>
            <div class="dash-stat-label">Tổng số dư</div>
        </div>
        <div class="dash-stat-trend">
            <i class="fa-solid fa-coins"></i> Users
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="dash-section-title">
    <i class="fa-solid fa-bolt"></i> Thao tác nhanh
</div>
<div class="dash-quick-actions">
    <a href="crud/products/list.php" class="dash-quick-item">
        <div class="dash-quick-icon" style="background:#ede9fe;color:#6E56CF;">
            <i class="fa-solid fa-box"></i>
        </div>
        <span>Sản phẩm</span>
    </a>
    <a href="crud/users/list.php" class="dash-quick-item">
        <div class="dash-quick-icon" style="background:#dbeafe;color:#2563EB;">
            <i class="fa-solid fa-users"></i>
        </div>
        <span>Người dùng</span>
    </a>
    <a href="crud/orders/list.php" class="dash-quick-item">
        <div class="dash-quick-icon" style="background:#d1fae5;color:#059669;">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <span>Đơn hàng</span>
    </a>
    <a href="crud/deposit_requests/list.php" class="dash-quick-item">
        <div class="dash-quick-icon" style="background:#fef3c7;color:#D97706;">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <span>Nạp tiền</span>
    </a>
    <a href="crud/account_stock/list.php" class="dash-quick-item">
        <div class="dash-quick-icon" style="background:#fce4ec;color:#E11D48;">
            <i class="fa-solid fa-database"></i>
        </div>
        <span>Kho TK</span>
    </a>
    <a href="crud/sepay_transactions/list.php" class="dash-quick-item">
        <div class="dash-quick-icon" style="background:#f3e8ff;color:#9333EA;">
            <i class="fa-solid fa-university"></i>
        </div>
        <span>SePay</span>
    </a>
    <a href="crud/categories/list.php" class="dash-quick-item">
        <div class="dash-quick-icon" style="background:#e0f2fe;color:#0284C7;">
            <i class="fa-solid fa-layer-group"></i>
        </div>
        <span>Danh mục</span>
    </a>
</div>

<!-- Main Grid -->
<div class="row g-3 mb-4">
    <!-- Latest Products -->
    <div class="col-lg-8">
        <div class="dash-card">
            <div class="dash-card-header">
                <div class="dash-card-title">
                    <i class="fa-solid fa-clock text-primary"></i>
                    Sản phẩm mới nhất
                </div>
                <a href="crud/products/list.php" class="nx-btn nx-btn-sm nx-btn-secondary">
                    Xem tất cả <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
            <?php if (count($stats['latest_products']) > 0): ?>
            <div class="dash-card-body p-0">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th class="text-end pe-4">Tình trạng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['latest_products'] as $p): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted small">#<?php echo str_pad($p['id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="dash-product-thumb">
                                        <img src="<?php echo htmlspecialchars($p['image_url'] ?? ''); ?>" onerror="this.style.display='none'">
                                        <span class="dash-thumb-placeholder"><i class="fa-solid fa-box"></i></span>
                                    </div>
                                    <span class="fw-bold" style="font-size:0.88rem;"><?php echo htmlspecialchars($p['title']); ?></span>
                                </div>
                            </td>
                            <td><span class="dash-badge dash-badge-muted"><?php echo htmlspecialchars($p['category']); ?></span></td>
                            <td class="fw-bold text-success"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</td>
                            <td class="text-end pe-4">
                                <?php if (!empty($p['badge'])): ?>
                                    <span class="dash-badge dash-badge-warning"><?php echo htmlspecialchars($p['badge']); ?></span>
                                <?php else: ?>
                                    <span class="dash-badge dash-badge-muted">Thường</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="dash-card-body text-center py-4 text-muted">Chưa có sản phẩm nào</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="col-lg-4">
        <!-- Top Balance -->
        <div class="dash-card mb-3">
            <div class="dash-card-header">
                <div class="dash-card-title">
                    <i class="fa-solid fa-trophy text-warning"></i>
                    Top số dư
                </div>
            </div>
            <?php if (count($stats['top_balance']) > 0): ?>
                <?php foreach ($stats['top_balance'] as $i => $u): ?>
                <div class="dash-top-user <?php echo $i < 3 ? 'dash-top-highlight' : ''; ?>">
                    <div class="dash-rank-badge <?php echo $i === 0 ? 'rank-gold' : ($i === 1 ? 'rank-silver' : ($i === 2 ? 'rank-bronze' : '')); ?>">
                        <?php if ($i === 0): ?>
                            <i class="fa-solid fa-crown"></i>
                        <?php elseif ($i === 1): ?>
                            <i class="fa-solid fa-medal"></i>
                        <?php elseif ($i === 2): ?>
                            <i class="fa-solid fa-medal"></i>
                        <?php else: ?>
                            <?php echo $i + 1; ?>
                        <?php endif; ?>
                    </div>
                    <div class="dash-top-user-avatar">
                        <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold" style="font-size:0.88rem;"><?php echo htmlspecialchars($u['username']); ?></div>
                        <div class="text-muted" style="font-size:0.7rem;">Người dùng</div>
                    </div>
                    <div class="fw-bold text-success" style="font-size:0.88rem;">
                        <?php echo number_format($u['balance'], 0, ',', '.'); ?>đ
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="dash-card-body text-center py-3 text-muted">Chưa có dữ liệu</div>
            <?php endif; ?>
        </div>

        <!-- Category Distribution -->
        <div class="dash-card">
            <div class="dash-card-header">
                <div class="dash-card-title">
                    <i class="fa-solid fa-chart-pie text-primary"></i>
                    Phân bố danh mục
                </div>
            </div>
            <div class="dash-card-body">
                <?php if (count($stats['categories']) > 0): ?>
                    <?php 
                    $catColors = ['#6E56CF','#38BDF8','#10B981','#F59E0B','#EF4444','#EC4899','#8B5CF6','#14B8A6'];
                    $ci = 0;
                    ?>
                    <?php foreach ($stats['categories'] as $cat): ?>
                    <?php
                        $pct = ($stats['total_products'] > 0) ? round($cat['count'] / $stats['total_products'] * 100) : 0;
                        $color = $catColors[$ci % count($catColors)];
                        $ci++;
                    ?>
                    <div class="dash-cat-item">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="dash-cat-name">
                                <span class="dash-cat-dot" style="background:<?php echo $color; ?>"></span>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </span>
                            <span class="dash-badge dash-badge-muted"><?php echo $cat['count']; ?> SP</span>
                        </div>
                        <div class="dash-progress">
                            <div class="dash-progress-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-3 text-muted">Chưa có dữ liệu</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
admin_renderLayout('Dashboard', 'dashboard');
?>
