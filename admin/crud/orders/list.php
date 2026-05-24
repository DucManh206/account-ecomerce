<?php
require_once __DIR__ . "/../../../admin/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/../../../crud/orders/order_modules.php";
require_once __DIR__ . "/../../../admin/crud/transactions/admin_transaction_modules.php";

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
    '' => 'Tất cả',
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
    'refunded' => 'Đã hoàn tiền',
];

function admin_order_status_class($status) {
    return match ($status) {
        'completed' => 'is-success',
        'processing' => 'is-info',
        'pending' => 'is-warning',
        'cancelled' => 'is-danger',
        'refunded' => 'is-muted',
        default => 'is-muted',
    };
}
function admin_order_date($value) {
    if (empty($value)) return 'N/A';
    return date('d/m/Y H:i', strtotime($value));
}

ob_start();
?>

<style>
.order-page-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px;flex-wrap:wrap}.order-page-title{font-size:1.45rem;font-weight:800;margin:0;color:#111827;letter-spacing:-.03em}.order-page-sub{color:#6b7280;font-size:.88rem;margin-top:4px}.order-head-actions{display:flex;gap:8px;align-items:center}.order-stats-grid{display:grid;grid-template-columns:repeat(6,minmax(140px,1fr));gap:12px;margin-bottom:16px}.order-stat{background:#fff;border:1px solid #edf0f5;border-radius:16px;padding:14px;box-shadow:0 6px 20px rgba(15,23,42,.04);position:relative;overflow:hidden}.order-stat:before{content:"";position:absolute;right:-20px;top:-20px;width:72px;height:72px;border-radius:999px;background:var(--tint,#eef2ff)}.order-stat-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--tint,#eef2ff);color:var(--tone,#4f46e5);margin-bottom:10px;position:relative}.order-stat-value{font-size:1.25rem;font-weight:800;color:#111827;position:relative;white-space:nowrap}.order-stat-label{font-size:.72rem;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.04em;position:relative}.order-panel{background:#fff;border:1px solid #edf0f5;border-radius:18px;box-shadow:0 8px 24px rgba(15,23,42,.05);overflow:hidden}.order-toolbar{display:grid;grid-template-columns:minmax(280px,1fr) 190px auto auto;gap:10px;padding:14px;border-bottom:1px solid #edf0f5;background:linear-gradient(180deg,#fff,#fbfcff)}.order-search{position:relative}.order-search i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#9ca3af}.order-search input,.order-toolbar select{width:100%;height:42px;border:1px solid #e5e7eb;background:#f9fafb;border-radius:12px;padding:0 14px;font-size:.88rem;outline:none}.order-search input{padding-left:38px}.order-search input:focus,.order-toolbar select:focus{border-color:#6E56CF;background:#fff;box-shadow:0 0 0 3px rgba(110,86,207,.12)}.order-table-wrap{overflow-x:auto}.order-table{width:100%;border-collapse:separate;border-spacing:0}.order-table th{background:#f8fafc;color:#64748b;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;font-weight:800;padding:13px 14px;border-bottom:1px solid #edf0f5;white-space:nowrap}.order-table td{padding:13px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:.88rem}.order-table tbody tr:hover{background:#fafbff}.order-id{font-weight:800;color:#4f46e5}.order-user{display:flex;align-items:center;gap:10px}.order-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#6E56CF,#38BDF8);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;flex-shrink:0}.order-user-name{font-weight:700;color:#111827}.order-user-id{font-size:.72rem;color:#94a3b8}.order-product{display:flex;align-items:center;gap:10px;min-width:240px}.order-product img{width:46px;height:38px;border-radius:10px;object-fit:cover;background:#f1f5f9;flex-shrink:0}.order-product-name{font-weight:700;color:#111827;max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.order-product-meta{font-size:.72rem;color:#94a3b8}.order-price{font-weight:800;color:#059669;white-space:nowrap}.order-date{color:#64748b;white-space:nowrap}.order-status{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:.75rem;font-weight:800;white-space:nowrap}.order-status.is-success{background:#dcfce7;color:#15803d}.order-status.is-warning{background:#fef3c7;color:#b45309}.order-status.is-info{background:#dbeafe;color:#1d4ed8}.order-status.is-danger{background:#fee2e2;color:#b91c1c}.order-status.is-muted{background:#f1f5f9;color:#64748b}.order-actions{display:flex;justify-content:flex-end;gap:6px}.order-empty{padding:56px 20px;text-align:center;color:#64748b}.order-empty i{font-size:2.4rem;color:#cbd5e1;margin-bottom:12px}.order-pagination{display:flex;align-items:center;justify-content:space-between;padding:13px 14px;background:#fbfcff;gap:12px;flex-wrap:wrap}.order-pages{display:flex;gap:5px;align-items:center}.order-pages a,.order-pages span{min-width:34px;height:34px;padding:0 10px;border-radius:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;font-weight:700;font-size:.82rem;border:1px solid #e5e7eb;color:#475569;background:#fff}.order-pages .current{background:#6E56CF;color:#fff;border-color:#6E56CF}.order-pages .disabled{opacity:.45;background:#f8fafc}.order-detail-hero{display:flex;gap:14px;align-items:flex-start;margin-bottom:16px}.order-detail-img{width:84px;height:70px;border-radius:14px;object-fit:cover;background:#f1f5f9}.order-detail-title{font-size:1rem;font-weight:800;color:#111827;margin-bottom:5px}.order-detail-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:14px}.order-detail-card{border:1px solid #edf0f5;border-radius:14px;padding:12px;background:#fbfcff}.order-detail-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;font-weight:800;margin-bottom:4px}.order-detail-value{font-weight:800;color:#111827}.order-account-box{background:#0f172a;color:#e2e8f0;border-radius:14px;padding:12px;font-family:Consolas,monospace;font-size:.82rem;white-space:pre-wrap;word-break:break-word;max-height:220px;overflow:auto}.order-toast{position:fixed;right:24px;bottom:24px;z-index:3000;background:#111827;color:#fff;border-radius:14px;padding:12px 14px;box-shadow:0 16px 35px rgba(15,23,42,.22);display:none;font-weight:700}.order-toast.show{display:block}.order-toast.success{background:#059669}.order-toast.danger{background:#dc2626}@media(max-width:1200px){.order-stats-grid{grid-template-columns:repeat(3,1fr)}}@media(max-width:768px){.order-stats-grid{grid-template-columns:repeat(2,1fr)}.order-toolbar{grid-template-columns:1fr}.order-detail-grid{grid-template-columns:1fr}.order-page-head{align-items:flex-start}.order-head-actions{width:100%;justify-content:stretch}.order-head-actions .nx-btn{flex:1;justify-content:center}}
</style>

<div class="order-page-head">
    <div>
        <h1 class="order-page-title">Đơn hàng</h1>
        <div class="order-page-sub">Theo dõi đơn mua, tài khoản đã bán, hoàn tiền và trạng thái xử lý.</div>
    </div>
    <div class="order-head-actions">
        <a href="?status=pending" class="nx-btn nx-btn-secondary"><i class="fa-solid fa-clock me-1"></i> Chờ xử lý</a>
        <a href="?status=completed" class="nx-btn nx-btn-primary"><i class="fa-solid fa-circle-check me-1"></i> Đã hoàn tất</a>
    </div>
</div>

<div class="order-stats-grid">
    <div class="order-stat" style="--tint:#ede9fe;--tone:#6E56CF"><div class="order-stat-icon"><i class="fa-solid fa-receipt"></i></div><div class="order-stat-value"><?php echo number_format($stats['total']); ?></div><div class="order-stat-label">Tổng đơn</div></div>
    <div class="order-stat" style="--tint:#fef3c7;--tone:#d97706"><div class="order-stat-icon"><i class="fa-solid fa-clock"></i></div><div class="order-stat-value"><?php echo number_format($stats['pending']); ?></div><div class="order-stat-label">Chờ xử lý</div></div>
    <div class="order-stat" style="--tint:#dbeafe;--tone:#2563eb"><div class="order-stat-icon"><i class="fa-solid fa-spinner"></i></div><div class="order-stat-value"><?php echo number_format($stats['processing'] ?? 0); ?></div><div class="order-stat-label">Đang xử lý</div></div>
    <div class="order-stat" style="--tint:#dcfce7;--tone:#059669"><div class="order-stat-icon"><i class="fa-solid fa-check-circle"></i></div><div class="order-stat-value"><?php echo number_format($stats['completed']); ?></div><div class="order-stat-label">Hoàn tất</div></div>
    <div class="order-stat" style="--tint:#fee2e2;--tone:#dc2626"><div class="order-stat-icon"><i class="fa-solid fa-xmark-circle"></i></div><div class="order-stat-value"><?php echo number_format($stats['cancelled']); ?></div><div class="order-stat-label">Đã hủy</div></div>
    <div class="order-stat" style="--tint:#ecfdf5;--tone:#047857"><div class="order-stat-icon"><i class="fa-solid fa-coins"></i></div><div class="order-stat-value"><?php echo number_format($stats['revenue']); ?>đ</div><div class="order-stat-label">Doanh thu</div></div>
</div>

<div class="order-panel">
    <form method="GET" class="order-toolbar" id="filterForm">
        <div class="order-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Tìm ID đơn, username, sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select name="status" onchange="document.getElementById('filterForm').submit()">
            <?php foreach ($statusOptions as $val => $label): ?>
                <option value="<?php echo $val; ?>" <?php echo $status === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="nx-btn nx-btn-primary"><i class="fa-solid fa-filter me-1"></i> Lọc</button>
        <a href="list.php" class="nx-btn nx-btn-secondary"><i class="fa-solid fa-rotate-left me-1"></i> Reset</a>
    </form>

    <div class="order-table-wrap">
        <?php if (count($orders) > 0): ?>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Đơn hàng</th>
                    <th>Người mua</th>
                    <th>Sản phẩm</th>
                    <th>Giá</th>
                    <th>Trạng thái</th>
                    <th>Ngày mua</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <?php
                    $s = $o['status'] ?? 'pending';
                    $sInfo = $statusMap[$s] ?? ['label' => ucfirst($s), 'icon' => 'fa-circle'];
                    $img = $o['image_url'] ?: 'https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80';
                    ?>
                    <tr data-order-id="<?php echo intval($o['id']); ?>">
                        <td><span class="order-id">#<?php echo str_pad($o['id'], 6, '0', STR_PAD_LEFT); ?></span></td>
                        <td>
                            <div class="order-user">
                                <div class="order-avatar"><?php echo strtoupper(substr($o['username'] ?? 'U', 0, 1)); ?></div>
                                <div>
                                    <div class="order-user-name"><?php echo htmlspecialchars($o['username'] ?? 'N/A'); ?></div>
                                    <div class="order-user-id">User ID: <?php echo intval($o['user_id'] ?? 0); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="order-product">
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
                                <div>
                                    <div class="order-product-name"><?php echo htmlspecialchars($o['product_title'] ?? 'N/A'); ?></div>
                                    <div class="order-product-meta">Product ID: <?php echo intval($o['product_id'] ?? 0); ?><?php echo !empty($o['account_id']) ? ' · Account #' . intval($o['account_id']) : ''; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="order-price"><?php echo number_format($o['price'] ?? 0, 0, ',', '.'); ?>đ</span></td>
                        <td><span class="order-status <?php echo admin_order_status_class($s); ?>"><i class="fa-solid <?php echo $sInfo['icon'] ?? 'fa-circle'; ?>"></i><?php echo htmlspecialchars($sInfo['label']); ?></span></td>
                        <td><span class="order-date"><?php echo admin_order_date($o['created_at'] ?? null); ?></span></td>
                        <td>
                            <div class="order-actions">
                                <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="viewOrder(<?php echo intval($o['id']); ?>)" title="Xem chi tiết"><i class="fa-solid fa-eye"></i></button>
                                <?php if ($s === 'completed'): ?>
                                    <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="refundOrder(<?php echo intval($o['id']); ?>)" title="Hoàn tiền"><i class="fa-solid fa-rotate-left"></i></button>
                                <?php endif; ?>
                                <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteOrder(<?php echo intval($o['id']); ?>)" title="Xóa đơn"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="order-empty"><i class="fa-solid fa-receipt"></i><h5>Không có đơn hàng nào</h5><p>Thử đổi bộ lọc hoặc từ khóa tìm kiếm.</p></div>
        <?php endif; ?>
    </div>

    <div class="order-pagination">
        <div class="text-muted small fw-semibold">Hiển thị <?php echo $total ? min($offset + 1, $total) : 0; ?>–<?php echo min($offset + $limit, $total); ?> của <?php echo number_format($total); ?> đơn hàng</div>
        <div class="order-pages">
            <?php $baseQuery = '&status=' . urlencode($status) . '&search=' . urlencode($search); ?>
            <?php if ($page > 1): ?><a href="?page=<?php echo $page - 1 . $baseQuery; ?>"><i class="fa-solid fa-chevron-left"></i></a><?php else: ?><span class="disabled"><i class="fa-solid fa-chevron-left"></i></span><?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i === $page): ?><span class="current"><?php echo $i; ?></span><?php else: ?><a href="?page=<?php echo $i . $baseQuery; ?>"><?php echo $i; ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="?page=<?php echo $page + 1 . $baseQuery; ?>"><i class="fa-solid fa-chevron-right"></i></a><?php else: ?><span class="disabled"><i class="fa-solid fa-chevron-right"></i></span><?php endif; ?>
        </div>
    </div>
</div>

<div class="nx-modal" id="orderModal">
    <div class="nx-modal-inner" style="max-width:760px;">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title"><i class="fa-solid fa-receipt me-2"></i>Chi tiết đơn hàng <span id="modalOrderId"></span></h5>
            <button class="nx-modal-close" onclick="closeModal()"></button>
        </div>
        <div class="nx-modal-body" id="modalBody"><div class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div></div>
        <div class="nx-modal-footer" id="modalFooter"></div>
    </div>
</div>
<div class="order-toast" id="orderToast"></div>

<script>
const ORDER_API = '../../../api/admin_order.php';
const statusMap = <?php echo json_encode($statusMap); ?>;

function viewOrder(orderId) {
    document.getElementById('modalOrderId').textContent = '#' + String(orderId).padStart(6, '0');
    document.getElementById('modalBody').innerHTML = '<div class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fa-2x"></i></div>';
    document.getElementById('modalFooter').innerHTML = '';
    showModal('orderModal');

    fetch(ORDER_API + '?action=get&id=' + encodeURIComponent(orderId))
        .then(readJsonResponse)
        .then(res => res.success ? renderOrderDetail(res.data) : showModalError(res.message || 'Không tải được đơn hàng'))
        .catch(err => showModalError(err.message));
}

function renderOrderDetail(o) {
    const sInfo = statusMap[o.status] || { label: o.status || 'N/A', icon: 'fa-circle' };
    const img = o.image_url || 'https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80';
    const account = o.account_data ? `<div class="order-detail-card" style="grid-column:1/-1"><div class="order-detail-label">Thông tin tài khoản đã giao</div><div class="order-account-box">${escapeHtml(formatAccountData(o.account_data))}</div></div>` : '';
    document.getElementById('modalBody').innerHTML = `
        <div class="order-detail-hero">
            <img src="${escapeAttr(img)}" class="order-detail-img" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
            <div style="flex:1">
                <div class="order-detail-title">${escapeHtml(o.product_title || 'N/A')}</div>
                <span class="order-status ${statusClass(o.status)}"><i class="fa-solid ${escapeAttr(sInfo.icon || 'fa-circle')}"></i>${escapeHtml(sInfo.label)}</span>
                <div class="text-muted small mt-2">Order #${String(o.id).padStart(6, '0')} · ${o.created_at ? new Date(o.created_at).toLocaleString('vi-VN') : 'N/A'}</div>
            </div>
        </div>
        <div class="order-detail-grid">
            <div class="order-detail-card"><div class="order-detail-label">Người mua</div><div class="order-detail-value">${escapeHtml(o.username || 'N/A')}</div></div>
            <div class="order-detail-card"><div class="order-detail-label">Giá trị</div><div class="order-detail-value text-success">${formatNumber(o.price)}đ</div></div>
            <div class="order-detail-card"><div class="order-detail-label">Product ID</div><div class="order-detail-value">#${escapeHtml(o.product_id || '0')}</div></div>
            <div class="order-detail-card"><div class="order-detail-label">Account ID</div><div class="order-detail-value">${o.account_id ? '#' + escapeHtml(o.account_id) : 'N/A'}</div></div>
            ${account}
        </div>
        <label class="nx-label fw-bold">Cập nhật trạng thái</label>
        <select class="nx-select" id="orderStatusSelect" style="width:100%;">
            ${Object.entries(statusMap).map(([k, v]) => `<option value="${k}" ${o.status === k ? 'selected' : ''}>${escapeHtml(v.label)}</option>`).join('')}
        </select>
    `;
    document.getElementById('modalFooter').innerHTML = `
        <button class="nx-btn nx-btn-secondary" onclick="closeModal()">Đóng</button>
        ${o.status === 'completed' ? `<button class="nx-btn nx-btn-danger" onclick="refundOrder(${Number(o.id)})"><i class="fa-solid fa-rotate-left me-1"></i>Hoàn tiền</button>` : ''}
        <button class="nx-btn nx-btn-primary" onclick="saveOrderStatus(${Number(o.id)})"><i class="fa-solid fa-check me-1"></i>Lưu trạng thái</button>
    `;
}

function saveOrderStatus(orderId) {
    const status = document.getElementById('orderStatusSelect').value;
    postOrder({action:'update_status', id: orderId, status}).then(res => {
        toast(res.message || (res.success ? 'Đã cập nhật' : 'Không cập nhật được'), res.success ? 'success' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 450);
    });
}
function refundOrder(orderId) {
    if (!confirm('Hoàn tiền đơn hàng #' + String(orderId).padStart(6, '0') + '?')) return;
    postOrder({action:'refund', id: orderId}).then(res => {
        toast(res.message || (res.success ? 'Hoàn tiền thành công' : 'Hoàn tiền thất bại'), res.success ? 'success' : 'danger');
        if (res.success) setTimeout(() => location.reload(), 650);
    });
}
function deleteOrder(orderId) {
    if (!confirm('Xóa đơn hàng #' + String(orderId).padStart(6, '0') + '?')) return;
    postOrder({action:'delete', id: orderId}).then(res => {
        toast(res.message || (res.success ? 'Đã xóa' : 'Xóa thất bại'), res.success ? 'success' : 'danger');
        if (res.success) {
            const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
            if (row) row.remove();
        }
    });
}
function postOrder(data) {
    const body = new URLSearchParams(data);
    return fetch(ORDER_API, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString()})
        .then(readJsonResponse)
        .catch(err => { toast(err.message, 'danger'); throw err; });
}
async function readJsonResponse(r) {
    const text = await r.text();
    try { return JSON.parse(text); } catch (e) { throw new Error('Server không trả JSON: ' + text.substring(0, 140)); }
}
function showModalError(msg) { document.getElementById('modalBody').innerHTML = `<div class="text-center py-5 text-danger fw-bold">${escapeHtml(msg)}</div>`; }
function closeModal() { hideModal('orderModal'); }
document.getElementById('orderModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
function statusClass(status) { return ({completed:'is-success',processing:'is-info',pending:'is-warning',cancelled:'is-danger',refunded:'is-muted'})[status] || 'is-muted'; }
function formatAccountData(raw) { try { const obj = JSON.parse(raw); return Object.entries(obj).map(([k,v]) => `${k}: ${v}`).join('\n'); } catch(e) { return raw || ''; } }
function escapeHtml(str) { const div = document.createElement('div'); div.textContent = str ?? ''; return div.innerHTML; }
function escapeAttr(str) { return String(str ?? '').replace(/"/g, '&quot;'); }
function formatNumber(n) { return new Intl.NumberFormat('vi-VN').format(n || 0); }
function toast(message, type='success') { const el = document.getElementById('orderToast'); el.textContent = message; el.className = 'order-toast show ' + type; setTimeout(() => el.className = 'order-toast', 2600); }
</script>

<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý Đơn hàng', 'orders');
?>
