<?php
require_once __DIR__ . "/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/crud/dashboard/admin_stats_modules.php";

$stats = admin_getDashboardStats();
$username = htmlspecialchars($_SESSION['username'] ?? 'Admin');

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="text-muted mb-0">Xin chào, <?php echo $username; ?>. Chào mừng bạn quay trở lại!</p>
    </div>
</div>

<!-- Stat Grid -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon bg-p-light">
            <i class="fa-solid fa-box-open"></i>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
        <div class="stat-label">Sản phẩm</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-s-light" style="background: rgba(56, 189, 248, 0.1); color: #38bdf8;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
        <div class="stat-label">Người dùng</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-d-light">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_admins']); ?></div>
        <div class="stat-label">Quản trị viên</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-s-light">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <div class="stat-value text-success"><?php echo number_format($stats['total_balance'], 0, ',', '.'); ?>đ</div>
        <div class="stat-label">Tổng số dư người dùng</div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="nx-card">
            <div class="nx-card-header">
                <h3 class="nx-card-title"><i class="fa-solid fa-clock me-2 text-primary"></i>Sản phẩm mới nhất</h3>
                <a href="crud/products/list.php" class="btn btn-sm btn-light" style="font-size: 0.75rem; font-weight: 700;">Xem tất cả</a>
            </div>
            <div class="table-responsive">
                <table class="nx-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sản phẩm</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th class="text-end">Tình trạng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($stats['latest_products']) > 0): ?>
                            <?php foreach ($stats['latest_products'] as $p): ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?php echo str_pad($p['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                            <img src="<?php echo htmlspecialchars($p['image_url'] ?? ''); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($p['title']); ?>&background=6E56CF&color=fff'">
                                        </div>
                                        <span class="fw-bold"><?php echo htmlspecialchars($p['title']); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark" style="font-size: 0.7rem;"><?php echo htmlspecialchars($p['category']); ?></span></td>
                                <td class="fw-bold text-success"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</td>
                                <td class="text-end">
                                    <?php if (!empty($p['badge'])): ?>
                                        <span class="badge-nx badge-warning"><?php echo htmlspecialchars($p['badge']); ?></span>
                                    <?php else: ?>
                                        <span class="badge-nx" style="background: #f1f5f9; color: #64748b;">Thường</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có sản phẩm nào</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <h3 class="nx-card-title"><i class="fa-solid fa-trophy me-2 text-warning"></i>Top số dư</h3>
            </div>
            <div class="nx-card-body p-0">
                <?php if (count($stats['top_balance']) > 0): ?>
                    <?php foreach ($stats['top_balance'] as $i => $u): ?>
                    <div class="d-flex align-items-center p-3 <?php echo ($i < count($stats['top_balance']) - 1) ? 'border-bottom' : ''; ?>">
                        <div class="avatar-sm me-3">
                            <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold" style="font-size: 0.9rem;"><?php echo htmlspecialchars($u['username']); ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">Người dùng</div>
                        </div>
                        <div class="fw-bold text-success" style="font-size: 0.9rem;">
                            <?php echo number_format($u['balance'], 0, ',', '.'); ?>đ
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">Chưa có dữ liệu</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-header">
                <h3 class="nx-card-title"><i class="fa-solid fa-chart-pie me-2 text-primary"></i>Phân bố danh mục</h3>
            </div>
            <div class="nx-card-body">
                <?php foreach ($stats['categories'] as $cat): ?>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="fw-semibold" style="font-size: 0.85rem;"><?php echo htmlspecialchars($cat['category']); ?></span>
                    <span class="badge rounded-pill bg-light text-dark"><?php echo $cat['count']; ?> SP</span>
                </div>
                <div class="progress mb-3" style="height: 6px; border-radius: 10px;">
                    <?php 
                        $pct = ($stats['total_products'] > 0) ? ($cat['count'] / $stats['total_products'] * 100) : 0;
                    ?>
                    <div class="progress-bar" style="width: <?php echo $pct; ?>%; background: var(--primary); border-radius: 10px;"></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
admin_renderLayout('Dashboard', 'dashboard');
?>
