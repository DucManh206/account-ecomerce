<?php
// AJAX request handler - must be before output starts
require_once __DIR__ . "/../../../admin/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/account_stock.php";
require_once __DIR__ . "/../products/products.php";

// Handle AJAX POST requests (save/edit/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $result = admin_handleStockRequest();
    echo json_encode($result);
    exit;
}

$stats = admin_getStockStats();

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$product_id = $_GET['product_id'] ?? '';

$result = admin_getStockList([
    'status' => $status,
    'product_id' => $product_id !== '' ? intval($product_id) : null,
    'search' => $search,
    'page' => $page,
    'per_page' => $per_page,
]);
$items = $result['items'];
$total = $result['total'];
$total_pages = $result['total_pages'];

$productsResult = admin_getProductsPaginated(['per_page' => 200]);
$products = $productsResult['items'];

ob_start();
?>
<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-2">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #6E56CF, #4F46E5); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fa-solid fa-database"></i></div>
            <div class="nx-stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="nx-stat-label">Tổng tài khoản</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #10B981, #059669); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fa-solid fa-check-circle"></i></div>
            <div class="nx-stat-value"><?php echo number_format($stats['available']); ?></div>
            <div class="nx-stat-label">Còn hàng</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #F59E0B, #D97706); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="nx-stat-value"><?php echo number_format($stats['sold']); ?></div>
            <div class="nx-stat-label">Đã bán</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #DC2626, #B91C1C); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="nx-stat-value"><?php echo number_format($stats['reserved']); ?></div>
            <div class="nx-stat-label">Đã đặt</div>
        </div>
    </div>
</div>

<!-- Search + Filter -->
<form class="nx-filters mb-4" method="GET">
    <div class="nx-search">
        <i class="fa-solid fa-search"></i>
        <input type="text" name="search" class="nx-input" placeholder="Tìm theo nội dung tài khoản..." value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <select name="product_id" class="nx-select" style="width:auto;">
        <option value="">Tất cả sản phẩm</option>
        <?php foreach ($products as $p): ?>
            <option value="<?php echo $p['id']; ?>" <?php echo $product_id == $p['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($p['title']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="nx-select" style="width:auto;" onchange="this.form.submit()">
        <option value="">Tất cả trạng thái</option>
        <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Còn hàng</option>
        <option value="sold" <?php echo $status === 'sold' ? 'selected' : ''; ?>>Đã bán</option>
        <option value="reserved" <?php echo $status === 'reserved' ? 'selected' : ''; ?>>Đã đặt</option>
    </select>
    <a href="?" class="nx-btn nx-btn-secondary"><i class="fa-solid fa-rotate-left"></i> Reset</a>
    <button type="button" class="nx-btn nx-btn-primary" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Thêm</button>
    <button type="button" class="nx-btn nx-btn-primary" onclick="openBulkImportModal()" style="background:var(--admin-success);"><i class="fa-solid fa-upload"></i> Nhập hàng loạt</button>
</form>

<!-- Bulk Action Bar -->
<div id="bulkActions" class="mb-3 d-none">
    <div class="d-flex align-items-center gap-2 p-2 bg-light rounded" style="border:1px solid var(--admin-border);">
        <span class="small fw-bold text-muted">Đã chọn: <span id="selectedCount">0</span></span>
        <button type="button" class="nx-btn nx-btn-sm nx-btn-danger" onclick="bulkDelete()"><i class="fa-solid fa-trash"></i> Xóa đã chọn</button>
        <button type="button" class="nx-btn nx-btn-sm nx-btn-success" onclick="bulkStatus('available')"><i class="fa-solid fa-check"></i> Đặt lại còn hàng</button>
        <button type="button" class="nx-btn nx-btn-sm nx-btn-secondary" onclick="bulkClear()">Bỏ chọn</button>
    </div>
</div>

<!-- Table -->
<div class="nx-card">
    <div class="nx-card-body p-0">
        <?php if (count($items) > 0): ?>
            <div class="table-responsive">
                <table class="nx-table">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" id="selectAll" onchange="toggleAll(this)" style="accent-color:var(--admin-primary);"></th>
                            <th class="ps-0">ID</th>
                            <th>Sản phẩm</th>
                            <th>Thông tin tài khoản</th>
                            <th style="width:100px;">Trạng thái</th>
                            <th style="width:140px;">Ngày tạo</th>
                            <th class="text-end pe-4" style="width:120px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item):
                            $statusClass = ['available' => 'nx-badge-success', 'sold' => 'nx-badge-warning', 'reserved' => 'nx-badge-secondary'];
                            $statusText = ['available' => 'Còn hàng', 'sold' => 'Đã bán', 'reserved' => 'Đã đặt'];
                            $s = $item['status'] ?? 'available';
                        ?>
                            <tr id="row-<?php echo $item['id']; ?>">
                                <td><input type="checkbox" class="stock-check" value="<?php echo $item['id']; ?>" onchange="updateBulkBar()" style="accent-color:var(--admin-primary);"></td>
                                <td class="ps-0 fw-bold text-muted">#<?php echo $item['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo htmlspecialchars($item['product_image'] ?? ''); ?>" style="width:36px;height:36px;border-radius:6px;object-fit:cover;" onerror="this.src='https://via.placeholder.com/36'">
                                        <div>
                                            <div class="fw-bold" style="font-size:0.85rem;"><?php echo htmlspecialchars($item['product_title'] ?? 'N/A'); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($item['product_category'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $adata = json_decode($item['account_data'] ?? '{}', true);
                                    if (is_array($adata)):
                                        foreach (array_slice($adata, 0, 2) as $k => $v): ?>
                                            <div class="small"><span class="text-muted"><?php echo ucfirst($k); ?>:</span> <code><?php echo htmlspecialchars(mb_substr($v, 0, 40)); ?></code></div>
                                        <?php endforeach;
                                        if (count($adata) > 2): ?>
                                            <div class="small text-muted">…còn <?php echo count($adata) - 2; ?> trường</div>
                                        <?php endif;
                                    else: ?>
                                        <code class="small"><?php echo htmlspecialchars(mb_substr($item['account_data'], 0, 50)); ?>...</code>
                                    <?php endif; ?>
                                </td>
                                <td><span class="nx-badge <?php echo $statusClass[$s] ?? 'nx-badge-secondary'; ?>"><?php echo $statusText[$s] ?? $s; ?></span></td>
                                <td class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick='editStock(<?php echo json_encode($item); ?>)'><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteStock(<?php echo $item['id']; ?>)"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="nx-pagination-wrap">
                <div class="nx-pagination-info">
                    Hiển thị <?php echo min(($page - 1) * $per_page + 1, $total); ?>–<?php echo min($page * $per_page, $total); ?> của <?php echo number_format($total); ?>
                    <?php if ($search): ?> | Tìm kiếm: "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                </div>
                <div class="nx-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&product_id=<?php echo urlencode($product_id); ?>&search=<?php echo urlencode($search); ?>"><i class="fa-solid fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&product_id=<?php echo urlencode($product_id); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&product_id=<?php echo urlencode($product_id); ?>&search=<?php echo urlencode($search); ?>"><i class="fa-solid fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-database fa-2x mb-3 d-block opacity-25"></i>
                <h5>Chưa có tài khoản nào</h5>
                <p>Bắt đầu bằng cách thêm tài khoản mới hoặc nhập hàng loạt</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="nx-modal" id="stockModal">
    <div class="nx-modal-inner" style="max-width:500px;">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="modalTitle"><i class="fa-solid fa-plus me-2"></i>Thêm tài khoản</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('stockModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="stockForm" onsubmit="saveStock(event)">
            <input type="hidden" id="editId" name="id" value="">
            <input type="hidden" name="action" id="formAction" value="create">
            <div class="nx-modal-body">
                <div class="nx-form-group">
                    <label class="nx-label">Sản phẩm <span class="text-danger">*</span></label>
                    <select class="nx-select" id="fProductId" name="product_id" required>
                        <option value="">-- Chọn sản phẩm --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Thông tin tài khoản (JSON) <span class="text-danger">*</span></label>
                    <textarea class="nx-input" id="fAccountData" name="account_data" rows="5" required placeholder='{"account":"email@example.com","password":"Matkhau123!"}'></textarea>
                    <div class="form-text">Nhập dạng JSON: {"account":"...", "password":"...", "email":"..."}</div>
                </div>
                <div class="nx-form-group" id="statusGroup" style="display:none;">
                    <label class="nx-label">Trạng thái</label>
                    <select class="nx-select" id="fStatus" name="status">
                        <option value="available">Còn hàng</option>
                        <option value="sold">Đã bán</option>
                        <option value="reserved">Đã đặt</option>
                    </select>
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('stockModal')">Hủy</button>
                <button type="submit" class="nx-btn nx-btn-primary"><i class="fa-solid fa-check me-1"></i>Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Import Modal -->
<div class="nx-modal" id="bulkImportModal">
    <div class="nx-modal-inner" style="max-width:600px;">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="bulkImportTitle"><i class="fa-solid fa-upload me-2"></i>Nhập hàng loạt tài khoản</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('bulkImportModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="bulkImportForm" onsubmit="bulkImportStock(event)">
            <div class="nx-modal-body">
                <div class="nx-form-group">
                    <label class="nx-label">Sản phẩm <span class="text-danger">*</span></label>
                    <select class="nx-select" id="bulkProductId" name="product_id" required>
                        <option value="">-- Chọn sản phẩm --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Số lượng tạo tự động</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="number" class="nx-input" name="count" min="1" max="100" value="10" style="width:100px;">
                        <span class="text-muted small">sẽ tạo tài khoản giả ngẫu nhiên</span>
                    </div>
                </div>
                <div class="nx-divider"><span>HOẶC</span></div>
                <div class="nx-form-group">
                    <label class="nx-label">Dán danh sách tài khoản (1 dòng = 1 tài khoản JSON)</label>
                    <textarea class="nx-input" name="bulk_data" rows="12" placeholder='{"account":"user1@example.com","password":"Pass123!"}
{"account":"user2@example.com","password":"Pass456!"}
{"account":"user3@example.com","password":"Pass789!"}
...'></textarea>
                    <div class="form-text">Mỗi dòng là một JSON object. Không có dấu phẩy giữa các dòng.</div>
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('bulkImportModal')">Hủy</button>
                <button type="submit" class="nx-btn nx-btn-primary"><i class="fa-solid fa-upload me-1"></i>Nhập tài khoản</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('stockForm').reset();
    document.getElementById('editId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('statusGroup').style.display = 'none';
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus me-2"></i>Thêm tài khoản';
    showModal('stockModal');
}

function editStock(data) {
    document.getElementById('stockForm').reset();
    document.getElementById('editId').value = data.id;
    document.getElementById('formAction').value = 'update';
    document.getElementById('fProductId').value = data.product_id;
    document.getElementById('fAccountData').value = data.account_data;
    document.getElementById('fStatus').value = data.status || 'available';
    document.getElementById('statusGroup').style.display = 'block';
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa tài khoản';
    showModal('stockModal');
}

function saveStock(e) {
    e.preventDefault();
    const form = document.getElementById('stockForm');
    const formData = new FormData(form);

    fetch('list.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            hideModal('stockModal');
            location.reload();
        } else {
            alert(data.message || 'Lỗi');
        }
    })
    .catch(() => alert('Lỗi kết nối'));
}

function deleteStock(id) {
    if (!confirm('Xóa tài khoản #' + id + '?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    fetch('list.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('row-' + id);
            if (row) row.remove();
        } else {
            alert(data.message || 'Lỗi');
        }
    })
    .catch(() => alert('Lỗi kết nối'));
}

// Bulk Import
function openBulkImportModal() {
    document.getElementById('bulkImportForm').reset();
    showModal('bulkImportModal');
}

function bulkImportStock(e) {
    e.preventDefault();
    const form = document.getElementById('bulkImportForm');
    const formData = new FormData(form);
    const bulkData = formData.get('bulk_data') || '';
    const count = parseInt(formData.get('count')) || 10;

    if (bulkData.trim()) {
        // Parse multi-line JSON - each line is one account
        const lines = bulkData.trim().split('\n').filter(l => l.trim());
        let valid = [];
        let errors = [];
        lines.forEach((line, i) => {
            try {
                const obj = JSON.parse(line.trim());
                valid.push(obj);
            } catch(e) {
                errors.push('Dòng ' + (i+1) + ': ' + e.message);
            }
        });
        if (valid.length === 0) {
            alert('Không có dữ liệu JSON hợp lệ!\n' + errors.join('\n'));
            return;
        }
        if (errors.length > 0) {
            if (!confirm(valid.length + ' dòng hợp lệ, ' + errors.length + ' dòng lỗi.\n' + errors.join('\n') + '\n\nTiếp tục nhập ' + valid.length + ' tài khoản?')) {
                return;
            }
        }
        // Create form data with each account as separate entry
        formData.append('action', 'create_bulk');
        formData.append('bulk_json', JSON.stringify(valid));
        formData.delete('bulk_data');
    } else {
        formData.append('action', 'create_bulk');
        formData.delete('bulk_data');
    }

    fetch('list.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            hideModal('bulkImportModal');
            location.reload();
        } else {
            alert(data.message || 'Lỗi');
        }
    })
    .catch(() => alert('Lỗi kết nối'));
}

// Bulk checkbox actions
function toggleAll(el) {
    document.querySelectorAll('.stock-check').forEach(cb => cb.checked = el.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.stock-check:checked');
    const bar = document.getElementById('bulkActions');
    document.getElementById('selectedCount').textContent = checked.length;
    if (checked.length > 0) {
        bar.classList.remove('d-none');
    } else {
        bar.classList.add('d-none');
    }
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.stock-check:checked')).map(cb => parseInt(cb.value));
}

function bulkDelete() {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    if (!confirm('Xóa ' + ids.length + ' tài khoản đã chọn?')) return;
    const formData = new FormData();
    formData.append('action', 'delete_bulk');
    formData.append('ids', JSON.stringify(ids));
    fetch('list.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Lỗi');
        }
    })
    .catch(() => alert('Lỗi kết nối'));
}

function bulkStatus(status) {
    const ids = getSelectedIds();
    if (ids.length === 0) return;
    const formData = new FormData();
    formData.append('action', 'bulk_status');
    formData.append('ids', JSON.stringify(ids));
    formData.append('status', status);
    fetch('list.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Lỗi');
        }
    })
    .catch(() => alert('Lỗi kết nối'));
}

function bulkClear() {
    document.querySelectorAll('.stock-check').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkBar();
}

function showModal(id) { document.getElementById(id).classList.add('show'); }
function hideModal(id) { document.getElementById(id).classList.remove('show'); }
</script>

<?php
$content = ob_get_clean();
admin_renderLayout('Kho tài khoản', 'account_stock');
?>
