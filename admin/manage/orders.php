<?php
require_once __DIR__ . "/../../admin_lib/admin_layout_modules.php";
require_once __DIR__ . "/../../lib/order_modules.php";
require_once __DIR__ . "/../../admin_lib/admin_transaction_modules.php";

$stats = order_getStats();

$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$result  = order_getAll(['limit' => $limit, 'offset' => $offset, 'status' => $status, 'search' => $search]);
$orders  = $result['orders'];
$total   = $result['total'];
$totalPages = $total > 0 ? ceil($total / $limit) : 1;

$statusMap = order_getStatusMap();

$statusOptions = [
    ''          => 'Tất cả',
    'pending'   => 'Chờ xử lý',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
    'refunded'  => 'Đã hoàn tiền',
];

ob_start();
?>

<!-- Stats -->
<div class="stats-row">
    <div class="nx-stat-card">
        <div class="nx-stat-icon" style="background:#ede9fe;color:#7c3aed;">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="nx-stat-value"><?php echo number_format($stats['total']); ?></div>
        <div class="nx-stat-label">Tổng đơn hàng</div>
    </div>
    <div class="nx-stat-card">
        <div class="nx-stat-icon" style="background:#fef3c7;color:#d97706;">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div class="nx-stat-value"><?php echo number_format($stats['pending']); ?></div>
        <div class="nx-stat-label">Chờ xử lý</div>
    </div>
    <div class="nx-stat-card">
        <div class="nx-stat-icon" style="background:#d1fae5;color:#059669;">
            <i class="fa-solid fa-check-circle"></i>
        </div>
        <div class="nx-stat-value"><?php echo number_format($stats['completed']); ?></div>
        <div class="nx-stat-label">Hoàn tất</div>
    </div>
    <div class="nx-stat-card">
        <div class="nx-stat-icon" style="background:#f3f4f6;color:#6b7280;">
            <i class="fa-solid fa-xmark-circle"></i>
        </div>
        <div class="nx-stat-value"><?php echo number_format($stats['cancelled']); ?></div>
        <div class="nx-stat-label">Đã hủy</div>
    </div>
    <div class="nx-stat-card">
        <div class="nx-stat-icon" style="background:#dbeafe;color:#2563eb;">
            <i class="fa-solid fa-coins"></i>
        </div>
        <div class="nx-stat-value"><?php echo number_format($stats['revenue']); ?>đ</div>
        <div class="nx-stat-label">Doanh thu</div>
    </div>
    <div class="nx-stat-card">
        <div class="nx-stat-icon" style="background:#fce7f3;color:#db2777;">
            <i class="fa-solid fa-calendar-day"></i>
        </div>
        <div class="nx-stat-value"><?php echo number_format($stats['today']); ?></div>
        <div class="nx-stat-label">Hôm nay</div>
    </div>
</div>

<!-- Filter Bar -->
<form method="GET" class="nx-filter-bar" id="filterForm">
    <div class="search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" placeholder="Tìm đơn hàng, tên người dùng, sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <select name="status" class="nx-select" style="width:auto;" onchange="document.getElementById('filterForm').submit()">
        <?php foreach ($statusOptions as $val => $label): ?>
            <option value="<?php echo $val; ?>" <?php echo $status === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="nx-btn nx-btn-primary"><i class="fa-solid fa-filter me-1"></i> Lọc</button>
    <a href="orders.php" class="nx-btn nx-btn-secondary"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
</form>

<!-- Orders Table -->
<div class="nx-table-wrap">
    <?php if (count($orders) > 0): ?>
        <table class="nx-table">
            <thead>
                <tr>
                    <th>Đơn hàng</th>
                    <th>Người dùng</th>
                    <th>Sản phẩm</th>
                    <th>Giá</th>
                    <th>Trạng thái</th>
                    <th>Ngày mua</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <?php
                        $s = $o['status'] ?? 'pending';
                        $sInfo = $statusMap[$s] ?? ['label' => ucfirst($s), 'class' => 'bg-secondary'];
                    ?>
                    <tr data-order-id="<?php echo $o['id']; ?>">
                        <td><span class="cell-id">#<?php echo str_pad($o['id'], 6, '0', STR_PAD_LEFT); ?></span></td>
                        <td>
                            <div class="cell-user">
                                <div class="cell-avatar"><?php echo strtoupper(substr($o['username'] ?? 'U', 0, 1)); ?></div>
                                <span class="cell-username"><?php echo htmlspecialchars($o['username'] ?? 'N/A'); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="cell-product">
                                <img src="<?php echo htmlspecialchars($o['image_url'] ?? ''); ?>" alt="" class="cell-product-img" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
                                <span class="cell-product-name"><?php echo htmlspecialchars($o['product_title'] ?? 'N/A'); ?></span>
                            </div>
                        </td>
                        <td><span class="cell-price"><?php echo number_format($o['price'] ?? 0); ?>đ</span></td>
                        <td>
                            <span class="badge-status <?php echo 'nx-badge nx-badge-' . $s; ?>">
                                <i class="fa-solid <?php echo $sInfo['icon'] ?? 'fa-circle'; ?>"></i>
                                <?php echo $sInfo['label']; ?>
                            </span>
                        </td>
                        <td><span class="cell-date"><?php echo date('d/m/Y H:i', strtotime($o['created_at'] ?? 'now')); ?></span></td>
                        <td class="cell-actions">
                            <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="viewOrder(<?php echo $o['id']; ?>)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteOrder(<?php echo $o['id']; ?>)" title="Xóa đơn">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="nx-pagination-wrap">
            <div class="nx-pagination-info">
                Hiển thị <?php echo min($offset + 1, $total); ?>–<?php echo min($offset + $limit, $total); ?> của <?php echo number_format($total); ?> đơn hàng
            </div>
            <div class="nx-pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fa-solid fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php
                    $start = max(1, $page - 2);
                    $end   = min($totalPages, $page + 2);
                    if ($end - $start < 4) {
                        if ($start === 1) $end = min($totalPages, 5);
                        if ($end === $totalPages) $start = max(1, $totalPages - 4);
                    }
                    for ($i = $start; $i <= $end; $i++):
                ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fa-solid fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="nx-empty">
            <i class="fa-solid fa-receipt"></i>
            <h5>Không có đơn hàng nào</h5>
            <p>Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
        </div>
    <?php endif; ?>
</div>

<!-- Order Detail Modal -->
<div class="nx-modal-overlay" id="orderModal">
    <div class="nx-modal">
        <div class="nx-modal-header">
            <h3><i class="fa-solid fa-receipt me-2"></i>Chi tiết đơn hàng <span id="modalOrderId"></span></h3>
            <button class="nx-modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="nx-modal-body" id="modalBody">
            <div style="text-align:center;padding:40px 0;color:#9ca3af;">
                <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
            </div>
        </div>
        <div class="nx-modal-footer" id="modalFooter"></div>
    </div>
</div>

<script>
const statusMap = <?php echo json_encode($statusMap); ?>;
const statusOptions = <?php echo json_encode($statusOptions); ?>;

function viewOrder(orderId) {
    const modal = document.getElementById('orderModal');
    document.getElementById('modalOrderId').textContent = '#' + String(orderId).padStart(6, '0');
    document.getElementById('modalBody').innerHTML = '<div style="text-align:center;padding:40px 0;color:#9ca3af;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
    document.getElementById('modalFooter').innerHTML = '';
    modal.classList.add('show');

    fetch('api/admin_order.php?action=get&id=' + orderId)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                renderOrderDetail(res.data);
            } else {
                document.getElementById('modalBody').innerHTML = '<div style="text-align:center;padding:40px;color:#dc2626;">' + res.message + '</div>';
            }
        })
        .catch(() => {
            document.getElementById('modalBody').innerHTML = '<div style="text-align:center;padding:40px;color:#dc2626;">Lỗi khi tải dữ liệu</div>';
        });
}

function renderOrderDetail(o) {
    const statusClass = 'nx-badge nx-badge-' + (o.status || 'pending');
    const sInfo = statusMap[o.status] || { label: o.status, icon: 'fa-circle' };

    const accountData = o.account_data ? JSON.parse(o.account_data) : {};
    let accountHtml = '';
    if (o.account_data) {
        accountHtml = `
            <div class="nx-detail-item" style="grid-column:1/-1;">
                <div class="nx-detail-label">Thông tin tài khoản</div>
                <div class="nx-account-box">${escapeHtml(o.account_data)}</div>
            </div>
        `;
    }

    document.getElementById('modalBody').innerHTML = `
        <div class="nx-detail-grid">
            <div class="nx-detail-item">
                <div class="nx-detail-label">Người mua</div>
                <div class="nx-detail-value">${escapeHtml(o.username || 'N/A')}</div>
            </div>
            <div class="nx-detail-item">
                <div class="nx-detail-label">Sản phẩm</div>
                <div class="nx-detail-value">${escapeHtml(o.product_title || 'N/A')}</div>
            </div>
            <div class="nx-detail-item">
                <div class="nx-detail-label">Giá</div>
                <div class="nx-detail-value text-success">${formatNumber(o.price)}đ</div>
            </div>
            <div class="nx-detail-item">
                <div class="nx-detail-label">Ngày tạo</div>
                <div class="nx-detail-value">${o.created_at ? new Date(o.created_at).toLocaleString('vi-VN') : 'N/A'}</div>
            </div>
            ${accountHtml}
        </div>

        <div style="margin-bottom:16px;">
            <div class="nx-detail-label" style="margin-bottom:8px;">Trạng thái</div>
            <select class="nx-select" id="orderStatusSelect">
                ${Object.entries(statusMap).map(([k, v]) =>
                    `<option value="${k}" ${o.status === k ? 'selected' : ''}>${v.label}</option>`
                ).join('')}
            </select>
        </div>
    `;

    const footer = document.getElementById('modalFooter');
    footer.innerHTML = `
        <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="closeModal()">Đóng</button>
        ${o.status === 'completed' ? `<button class="nx-btn nx-btn-sm nx-btn-danger" onclick="refundOrder(${o.id})"><i class="fa-solid fa-rotate-left me-1"></i>Hoàn tiền</button>` : ''}
        <button class="nx-btn nx-btn-sm nx-btn-primary" onclick="saveOrderStatus(${o.id})"><i class="fa-solid fa-check me-1"></i>Lưu trạng thái</button>
    `;
}

function saveOrderStatus(orderId) {
    const status = document.getElementById('orderStatusSelect').value;

    fetch('api/admin_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_status&id=${orderId}&status=${status}`
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert(res.message || 'Lỗi khi cập nhật');
        }
    })
    .catch(() => alert('Lỗi kết nối'));
}

function refundOrder(orderId) {
    if (!confirm('Bạn có chắc muốn hoàn tiền đơn hàng này?')) return;

    fetch('api/admin_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=refund&id=${orderId}`
    })
    .then(r => r.json())
    .then(res => {
        alert(res.message || (res.success ? 'Hoàn tiền thành công!' : 'Lỗi'));
        if (res.success) location.reload();
    })
    .catch(() => alert('Lỗi kết nối'));
}

function deleteOrder(orderId) {
    if (!confirm('Bạn có chắc muốn xóa đơn hàng #' + orderId + '?')) return;

    fetch('api/admin_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&id=${orderId}`
    })
    .then(r => r.json())
    .then(res => {
        alert(res.message || (res.success ? 'Xóa thành công!' : 'Lỗi'));
        if (res.success) {
            const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
            if (row) row.remove();
        }
    })
    .catch(() => alert('Lỗi kết nối'));
}

function closeModal() {
    document.getElementById('orderModal').classList.remove('show');
}

document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

function formatNumber(n) {
    return new Intl.NumberFormat('vi-VN').format(n || 0);
}
</script>

<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý Đơn hàng', 'orders');
?>
