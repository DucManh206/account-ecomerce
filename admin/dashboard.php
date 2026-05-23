<?php
require_once __DIR__ . "/../admin_lib/admin_layout_modules.php";
require_once __DIR__ . "/../admin_lib/admin_stats_modules.php";

$stats = admin_getDashboardStats();
$username = htmlspecialchars($_SESSION['username'] ?? 'Admin');

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Xin chào, <?php echo $username; ?>!</h1>
        <p class="text-muted mb-0" style="font-size:0.9rem;">Chào mừng bạn quay trở lại NEXUS Admin Panel</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="nx-stat-card nx-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="nx-stat-value text-dark"><?php echo number_format($stats['total_products']); ?></div>
                    <div class="nx-stat-label">Sản phẩm</div>
                </div>
                <div class="nx-stat-icon" style="background:rgba(110,86,207,0.1); color:#6E56CF;">
                    <i class="fa-solid fa-box-open"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="nx-stat-card nx-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="nx-stat-value text-dark"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="nx-stat-label">Người dùng</div>
                </div>
                <div class="nx-stat-icon" style="background:rgba(56,189,248,0.1); color:#38BDF8;">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="nx-stat-card nx-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="nx-stat-value text-dark"><?php echo number_format($stats['total_admins']); ?></div>
                    <div class="nx-stat-label">Quản trị viên</div>
                </div>
                <div class="nx-stat-icon" style="background:rgba(239,68,68,0.1); color:#EF4444;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="nx-stat-card nx-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="nx-stat-value text-success"><?php echo number_format($stats['total_balance'], 0, ',', '.'); ?>đ</div>
                    <div class="nx-stat-label">Tổng số dư</div>
                </div>
                <div class="nx-stat-icon" style="background:rgba(16,185,129,0.1); color:#10B981;">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="nx-card">
            <div class="nx-card-body d-flex align-items-center gap-3 py-3">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(245,158,11,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-tags" style="color:#F59E0B;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;"><?php echo $stats['discounted_products']; ?></div>
                    <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">Sản phẩm giảm giá</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="nx-card">
            <div class="nx-card-body d-flex align-items-center gap-3 py-3">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(252,211,77,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-crown" style="color:#FCD34D;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;"><?php echo $stats['vip_products']; ?></div>
                    <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">Sản phẩm VIP</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="nx-card">
            <div class="nx-card-body d-flex align-items-center gap-3 py-3">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(110,86,207,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-layer-group" style="color:#6E56CF;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;"><?php echo count($stats['categories']); ?></div>
                    <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">Danh mục</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="nx-card">
            <div class="nx-card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-clock me-2 text-primary"></i>Sản phẩm mới nhất</span>
                <a href="/admin/manage/products.php" class="nx-btn nx-btn-sm nx-btn-secondary"><i class="fa-solid fa-arrow-right me-1"></i>Xem tất cả</a>
            </div>
            <div class="nx-card-body p-0">
                <div class="table-responsive">
                    <table class="nx-table nx-table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th class="text-end pe-4">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($stats['latest_products']) > 0): ?>
                                <?php foreach ($stats['latest_products'] as $p): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted">#<?php echo str_pad($p['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?php echo htmlspecialchars($p['image_url']); ?>" width="40" height="28" style="border-radius:6px;object-fit:cover;" alt="" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
                                            <span class="fw-semibold" style="font-size:0.85rem;"><?php echo htmlspecialchars($p['title']); ?></span>
                                        </div>
                                    </td>
                                    <td><span class="nx-badge-muted"><?php echo htmlspecialchars($p['category']); ?></span></td>
                                    <td class="text-success fw-bold"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</td>
                                    <td class="text-end pe-4">
                                        <?php if (!empty($p['badge'])): ?>
                                            <span class="nx-badge-vip"><?php echo htmlspecialchars($p['badge']); ?></span>
                                        <?php else: ?>
                                            <span class="nx-badge-muted">Thường</span>
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
    </div>

    <div class="col-lg-5">
        <div class="nx-card">
            <div class="nx-card-header">
                <i class="fa-solid fa-ranking-star me-2 text-success"></i>Top người dùng nhiều tiền nhất
            </div>
            <div class="nx-card-body p-0">
                <?php if (count($stats['top_balance']) > 0): ?>
                    <?php foreach ($stats['top_balance'] as $i => $u): ?>
                    <div class="d-flex align-items-center px-4 py-3 <?php echo ($i < count($stats['top_balance']) - 1) ? 'border-bottom' : ''; ?>">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6E56CF,#38BDF8);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.8rem;flex-shrink:0;">
                            <?php echo $i + 1; ?>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div style="font-weight:700;font-size:0.88rem;"><?php echo htmlspecialchars($u['username']); ?></div>
                        </div>
                        <div class="text-success fw-bold" style="font-size:0.88rem;">
                            <?php echo number_format($u['balance'], 0, ',', '.'); ?>đ
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">Chưa có dữ liệu</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($stats['categories']) > 0): ?>
        <div class="nx-card mt-3">
            <div class="nx-card-header">
                <i class="fa-solid fa-layer-group me-2 text-warning"></i>Danh mục sản phẩm
            </div>
            <div class="nx-card-body">
                <?php foreach ($stats['categories'] as $cat): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold" style="font-size:0.88rem;"><?php echo htmlspecialchars($cat['category']); ?></span>
                    <span class="nx-badge-muted"><?php echo $cat['count']; ?> sản phẩm</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
admin_renderLayout('Dashboard', 'dashboard');
?>
