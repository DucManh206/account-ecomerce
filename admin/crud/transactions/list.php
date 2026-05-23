<?php
require_once __DIR__ . '/../../../admin/crud/layout/admin_layout_modules.php';
require_once __DIR__ . '/transactions.php';

$items = transactions_getAll();

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Lich su Giao dich</h1>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            <?php echo count($items); ?> giao dich
        </p>
    </div>
</div>

<?php if (count($items) === 0): ?>
<div class="nx-card">
    <div class="nx-card-body text-center py-5">
        <i class="fa-solid fa-receipt fa-2x mb-3 d-block opacity-20 text-muted"></i>
        <p class="text-muted mb-3">Chua co giao dich nao.</p>
    </div>
</div>
<?php else: ?>
<div class="nx-card">
    <div class="nx-card-body p-0">
        <div class="table-responsive">
            <table class="nx-table">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:60px;">ID</th>
                        <th>Nguoi dung</th>
                        <th>Loai</th>
                        <th>So tien</th>
                        <th>Trang thai</th>
                        <th>Ngay</th>
                        <th class="text-end pe-4" style="width:120px;">Hanh dong</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr id="row-<?php echo $item['id']; ?>">
                        <td class="ps-4 fw-bold text-muted small">#<?php echo $item['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#6E56CF,#38BDF8);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.7rem;flex-shrink:0;">
                                    <?php echo strtoupper(substr($item['user_id'] ?? 'U', 0, 1)); ?>
                                </div>
                                <span class="fw-semibold"><?php echo htmlspecialchars($item['user_id'] ?? 'N/A'); ?></span>
                            </div>
                        </td>
                        <td><span class="nx-badge nx-badge-muted"><?php echo htmlspecialchars($item['type'] ?? $item['transaction_type'] ?? 'N/A'); ?></span></td>
                        <td>
                            <?php
                            $amount = intval($item['amount'] ?? $item['price'] ?? 0);
                            $color = $amount >= 0 ? 'text-success' : 'text-danger';
                            ?>
                            <span class="fw-bold <?php echo $color; ?>"><?php echo number_format($amount, 0, ',', '.'); ?>d</span>
                        </td>
                        <td>
                            <?php
                            $status = $item['status'] ?? 'completed';
                            $statusClass = match($status) {
                                'completed', 'success' => 'nx-badge-success',
                                'pending' => 'nx-badge-warning',
                                'failed', 'cancelled' => 'nx-badge-danger',
                                default => 'nx-badge-muted',
                            };
                            $statusLabel = match($status) {
                                'completed', 'success' => 'Hoan tat',
                                'pending' => 'Cho xu ly',
                                'failed', 'cancelled' => 'That bai',
                                default => 'N/A',
                            };
                            ?>
                            <span class="nx-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                        </td>
                        <td><span class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($item['created_at'] ?? 'now')); ?></span></td>
                        <td class="text-end pe-4">
                            <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick='editTransaction(<?php echo json_encode($item); ?>)'>
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteTransaction(<?php echo $item['id']; ?>)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="nx-modal" id="editModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="modalTitle"><i class="fa-solid fa-pen-to-square me-2"></i>Sua giao dich</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('editModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="editForm" onsubmit="saveTransaction(event)">
            <input type="hidden" id="editId" name="id" value="">
            <input type="hidden" name="action" value="update">
            <div class="nx-modal-body">
                <div class="nx-form-group">
                    <label class="nx-label">Trang thai</label>
                    <select class="nx-select" id="fStatus" name="status">
                        <option value="pending">Cho xu ly</option>
                        <option value="completed">Hoan tat</option>
                        <option value="failed">That bai</option>
                    </select>
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('editModal')">Huy</button>
                <button type="submit" class="nx-btn nx-btn-primary"><i class="fa-solid fa-check me-1"></i>Luu</button>
            </div>
        </form>
    </div>
</div>

<script>
function hideModal(id) { document.getElementById(id).classList.remove('show'); }

function editTransaction(data) {
    document.getElementById('editId').value = data.id;
    document.getElementById('fStatus').value = data.status || 'pending';
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sua giao dich #' + data.id;
    document.getElementById('editModal').classList.add('show');
}

function saveTransaction(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('editForm'));
    fetch('../../lib/admin_transaction_modules.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                hideModal('editModal');
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert('Da xay ra loi'));
}

function deleteTransaction(id) {
    if (!confirm('Xoa giao dich nay?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    fetch('../../lib/admin_transaction_modules.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('row-' + id);
                if (row) row.remove();
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert('Da xay ra loi'));
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) hideModal('editModal');
});
</script>
<?php
$content = ob_get_clean();
admin_renderLayout('Lich su Giao dich', 'transactions');
?>
