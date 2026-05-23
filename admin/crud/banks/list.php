<?php
require_once __DIR__ . "/../../../admin/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/banks.php";

$banks = admin_getBanks();

ob_start();
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Quan ly Ngan hang</h1>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            <?php echo count($banks); ?> ngan hang
        </p>
    </div>
    <button class="nx-btn nx-btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-plus me-1"></i> Them ngan hang
    </button>
</div>

<div id="alertBox" class="nx-alert d-none mb-3"></div>

<?php if (count($banks) === 0): ?>
    <div class="nx-card">
        <div class="nx-card-body text-center py-5">
            <i class="fa-solid fa-university fa-2x mb-3 d-block opacity-20 text-muted"></i>
            <p class="text-muted mb-3">Chua co ngan hang nao.</p>
            <button class="nx-btn nx-btn-primary" onclick="openAddModal()">
                <i class="fa-solid fa-plus me-1"></i> Them ngan hang dau tien
            </button>
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
                        <th>Ngan hang</th>
                        <th>So tai khoan</th>
                        <th>Ten tai khoan</th>
                        <th>Chi nhanh</th>
                        <th style="width:100px;">Trang thai</th>
                        <th class="text-end pe-4" style="width:120px;">Hanh dong</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($banks as $bank): ?>
                    <tr id="row-<?php echo $bank['id']; ?>">
                        <td class="ps-4 fw-bold text-muted small">#<?php echo $bank['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:36px;height:36px;border-radius:8px;background:rgba(110,86,207,0.1);display:flex;align-items:center;justify-content:center;color:#6E56CF;">
                                    <i class="fa-solid fa-university"></i>
                                </div>
                                <span class="fw-bold"><?php echo htmlspecialchars($bank['name']); ?></span>
                            </div>
                        </td>
                        <td><code style="font-size:0.85rem;"><?php echo htmlspecialchars($bank['account_no']); ?></code></td>
                        <td><?php echo htmlspecialchars($bank['account_name']); ?></td>
                        <td><span class="text-muted"><?php echo htmlspecialchars($bank['branch']); ?></span></td>
                        <td>
                            <?php
                            $statusClass = match($bank['status'] ?? 'active') {
                                'active' => 'nx-badge-success',
                                'inactive' => 'nx-badge-muted',
                                default => 'nx-badge-muted',
                            };
                            $statusLabel = match($bank['status'] ?? 'active') {
                                'active' => 'Hoat dong',
                                'inactive' => 'Khong hoat dong',
                                default => 'Khong xac dinh',
                            };
                            ?>
                            <span class="nx-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick='editBank(<?php echo json_encode($bank); ?>)'>
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteBank(<?php echo $bank['id']; ?>, '<?php echo htmlspecialchars(addslashes($bank['name'])); ?>')">
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

<div class="nx-modal" id="bankModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="modalTitle"><i class="fa-solid fa-university me-2"></i>Them ngan hang</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('bankModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="bankForm" onsubmit="saveBank(event)">
            <input type="hidden" id="editId" name="id" value="">
            <input type="hidden" name="action" id="formAction" value="create">
            <div class="nx-modal-body">
                <div class="nx-form-group">
                    <label class="nx-label">Ten ngan hang <span class="text-danger">*</span></label>
                    <input type="text" class="nx-input" id="fName" name="name" required placeholder="VD: VietinBank">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">So tai khoan <span class="text-danger">*</span></label>
                    <input type="text" class="nx-input" id="fAccountNo" name="account_no" required placeholder="123456789">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Ten tai khoan</label>
                    <input type="text" class="nx-input" id="fAccountName" name="account_name" placeholder="NGUYEN VAN A">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Chi nhanh</label>
                    <input type="text" class="nx-input" id="fBranch" name="branch" placeholder="Chi nhanh TP.HCM">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">QR Template</label>
                    <input type="text" class="nx-input" id="fQrTemplate" name="qr_template" placeholder="https://...">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Trang thai</label>
                    <select class="nx-select" id="fStatus" name="status">
                        <option value="active">Hoat dong</option>
                        <option value="inactive">Khong hoat dong</option>
                    </select>
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('bankModal')">Huy</button>
                <button type="submit" class="nx-btn nx-btn-primary" id="submitBtn">
                    <i class="fa-solid fa-check me-1"></i>Luu
                </button>
            </div>
        </form>
    </div>
</div>

<div class="nx-modal" id="deleteModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" style="color:var(--danger);"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xac nhan xoa</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('deleteModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="nx-modal-body">
            <p>Xoa ngan hang <strong id="delBankName"></strong>?</p>
            <span class="text-muted" style="font-size:0.85rem;">Hanh dong nay khong the hoan tac.</span>
        </div>
        <div class="nx-modal-footer">
            <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('deleteModal')">Huy</button>
            <button type="button" class="nx-btn nx-btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                <i class="fa-solid fa-trash me-1"></i>Xoa
            </button>
        </div>
    </div>
</div>

<script>
let deleteTarget = null;

function showAlert(type, message) {
    const box = document.getElementById('alertBox');
    box.className = 'nx-alert nx-alert-' + type;
    box.innerHTML = message;
    box.classList.remove('d-none');
    setTimeout(() => { if (!type.includes('danger')) box.classList.add('d-none'); }, 5000);
}

function openAddModal() {
    document.getElementById('bankForm').reset();
    document.getElementById('editId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-university me-2"></i>Them ngan hang';
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Luu';
    showModal('bankModal');
}

function editBank(data) {
    document.getElementById('bankForm').reset();
    document.getElementById('editId').value = data.id;
    document.getElementById('formAction').value = 'update';
    document.getElementById('fName').value = data.name || '';
    document.getElementById('fAccountNo').value = data.account_no || '';
    document.getElementById('fAccountName').value = data.account_name || '';
    document.getElementById('fBranch').value = data.branch || '';
    document.getElementById('fQrTemplate').value = data.qr_template || '';
    document.getElementById('fStatus').value = data.status || 'active';
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sua ngan hang';
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Cap nhat';
    showModal('bankModal');
}

function saveBank(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('bankForm'));
    fetch('../../lib/admin_bank_modules.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                hideModal('bankModal');
                showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
                location.reload();
            } else {
                showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
            }
        })
        .catch(() => showAlert('danger', 'Da xay ra loi'));
}

function deleteBank(id, name) {
    deleteTarget = id;
    document.getElementById('delBankName').textContent = '"' + name + '"';
    showModal('deleteModal');
}

function confirmDelete() {
    if (!deleteTarget) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', deleteTarget);
    fetch('../../lib/admin_bank_modules.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            hideModal('deleteModal');
            if (data.success) {
                const row = document.getElementById('row-' + deleteTarget);
                if (row) row.remove();
                showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
            } else {
                showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
            }
            deleteTarget = null;
        })
        .catch(() => { hideModal('deleteModal'); showAlert('danger', 'Da xay ra loi'); });
}
</script>
<?php
$content = ob_get_clean();
admin_renderLayout('Quan ly Ngan hang', 'banks');
?>
