<?php
require_once __DIR__ . "/../../../admin/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/account_field_types.php";

$fields = admin_getAccountFieldTypes();
$totalFields = count($fields);

ob_start();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Loại Field Tài khoản</h1>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            <?php echo $totalFields; ?> loại field — Dùng để hiển thị thông tin tài khoản khi bán
        </p>
    </div>
    <button class="nx-btn nx-btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-plus me-1"></i> Thêm field
    </button>
</div>

<div id="alertBox" class="nx-alert d-none mb-3"></div>

<div class="nx-card">
    <div class="nx-card-body p-0">
        <?php if (count($fields) > 0): ?>
            <div class="table-responsive">
                <table class="nx-table">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width:60px;">Icon</th>
                            <th>Key</th>
                            <th>Label</th>
                            <th>Placeholder</th>
                            <th style="width:80px;">Mặc định</th>
                            <th style="width:80px;">Thứ tự</th>
                            <th class="text-end pe-4" style="width:120px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fields as $f):
                            $isDefault = intval($f['is_default'] ?? 0) === 1;
                        ?>
                            <tr id="row-<?php echo $f['id']; ?>">
                                <td class="ps-4">
                                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(110,86,207,0.1);display:flex;align-items:center;justify-content:center;color:#6E56CF;">
                                        <i class="fa-solid <?php echo htmlspecialchars($f['icon_class'] ?? 'fa-key'); ?>"></i>
                                    </div>
                                </td>
                                <td>
                                    <code style="font-size:0.85rem;"><?php echo htmlspecialchars($f['key']); ?></code>
                                    <?php if ($isDefault): ?>
                                        <span class="nx-badge nx-badge-primary ms-1" style="font-size:0.6rem;">Mặc định</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="fw-bold"><?php echo htmlspecialchars($f['label']); ?></span></td>
                                <td><code class="small text-muted"><?php echo htmlspecialchars($f['placeholder'] ?? ''); ?></code></td>
                                <td>
                                    <?php if ($isDefault): ?>
                                        <span class="nx-badge nx-badge-success">Có</span>
                                    <?php else: ?>
                                        <span class="nx-badge nx-badge-muted">Không</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="nx-badge nx-badge-muted"><?php echo $f['sort_order'] ?? 0; ?></span></td>
                                <td class="text-end pe-4">
                                    <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick='editField(<?php echo json_encode($f); ?>)'>
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php if (!$isDefault): ?>
                                        <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteField(<?php echo $f['id']; ?>, '<?php echo htmlspecialchars(addslashes($f['label'])); ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fa-solid fa-key fa-2x mb-3 d-block opacity-25"></i>
                <h5>Chưa có field nào</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="nx-modal" id="fieldModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="modalTitle"><i class="fa-solid fa-plus me-2"></i>Thêm field</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('fieldModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="fieldForm" onsubmit="saveField(event)">
            <input type="hidden" id="editId" name="id" value="">
            <input type="hidden" name="action" id="formAction" value="create">
            <div class="nx-modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="nx-form-group">
                            <label class="nx-label">Key <span class="text-danger">*</span></label>
                            <input type="text" class="nx-input" id="fKey" name="key" required placeholder="VD: account, password" pattern="[a-z0-9_]+">
                            <div class="form-text">Chỉ chứa a-z, 0-9, dấu gạch dưới. Duy nhất.</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="nx-form-group">
                            <label class="nx-label">Label <span class="text-danger">*</span></label>
                            <input type="text" class="nx-input" id="fLabel" name="label" required placeholder="VD: Tài khoản">
                        </div>
                    </div>
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Icon</label>
                    <div class="nx-icon-picker">
                        <?php
                        $icons = [
                            ['fa-key', 'Key'],
                            ['fa-user', 'User'],
                            ['fa-lock', 'Lock'],
                            ['fa-envelope', 'Email'],
                            ['fa-cookie-bite', 'Cookie'],
                            ['fa-fingerprint', 'Fingerprint'],
                            ['fa-hashtag', 'Hashtag'],
                            ['fa-shield-halved', 'Shield'],
                            ['fa-sticky-note', 'Note'],
                            ['fa-link', 'Link'],
                            ['fa-barcode', 'Barcode'],
                            ['fa-barcode', 'Code'],
                            ['fa-lock', 'Pass'],
                            ['fa-eye', 'Mật khẩu'],
                            ['fa-at', 'Username'],
                            ['fa-id-card', 'ID Card'],
                            ['fa-phone', 'Phone'],
                            ['fa-gamepad', 'Game'],
                        ];
                        foreach ($icons as $ic): ?>
                            <label data-icon="<?php echo $ic[0]; ?>" onclick="selectIcon(this, '<?php echo $ic[0]; ?>')" title="<?php echo $ic[1]; ?>">
                                <i class="fa-solid <?php echo $ic[0]; ?>"></i>
                                <input type="radio" name="_icon_radio" value="<?php echo $ic[0]; ?>">
                            </label>
                        <?php endforeach; ?>
                        <input type="hidden" id="fIcon" name="icon_class" value="fa-key">
                    </div>
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Placeholder</label>
                    <input type="text" class="nx-input" id="fPlaceholder" name="placeholder" placeholder="VD: Nhập tài khoản...">
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="nx-form-group">
                            <label class="nx-label">Thứ tự</label>
                            <input type="number" class="nx-input" id="fSort" name="sort_order" value="0" min="0">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="nx-form-group">
                            <label class="nx-label">Mặc định</label>
                            <select class="nx-select" id="fDefault" name="is_default">
                                <option value="0">Không</option>
                                <option value="1">Có</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('fieldModal')">Hủy</button>
                <button type="submit" class="nx-btn nx-btn-primary" id="submitBtn">
                    <i class="fa-solid fa-check me-1"></i>Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm -->
<div class="nx-modal" id="deleteModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" style="color: var(--danger);"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xác nhận xóa</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('deleteModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="nx-modal-body">
            <p>Xóa field <strong id="delFieldName"></strong>?</p>
            <span class="text-muted" style="font-size:0.85rem;">Hành động này không thể hoàn tác.</span>
        </div>
        <div class="nx-modal-footer">
            <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('deleteModal')">Hủy</button>
            <button type="button" class="nx-btn nx-btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                <i class="fa-solid fa-trash me-1"></i>Xóa
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

function selectIcon(btn, icon) {
    document.querySelectorAll('.nx-icon-picker label').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('fIcon').value = icon;
}

function openAddModal() {
    document.getElementById('fieldForm').reset();
    document.getElementById('editId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('fKey').readOnly = false;
    document.querySelectorAll('.nx-icon-picker label').forEach(b => b.classList.remove('selected'));
    document.querySelector('.nx-icon-picker label[data-icon="fa-key"]').classList.add('selected');
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus me-2"></i>Thêm field';
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Tạo';
    showModal('fieldModal');
}

function editField(data) {
    document.getElementById('fieldForm').reset();
    document.getElementById('editId').value = data.id;
    document.getElementById('formAction').value = 'update';
    document.getElementById('fKey').value = data.key || '';
    document.getElementById('fKey').readOnly = true;
    document.getElementById('fLabel').value = data.label || '';
    document.getElementById('fIcon').value = data.icon_class || 'fa-key';
    document.getElementById('fPlaceholder').value = data.placeholder || '';
    document.getElementById('fSort').value = data.sort_order || 0;
    document.getElementById('fDefault').value = data.is_default || 0;
    document.querySelectorAll('.nx-icon-picker label').forEach(b => b.classList.remove('selected'));
    const matchingBtn = document.querySelector('.nx-icon-picker label[data-icon="' + (data.icon_class || 'fa-key') + '"]');
    if (matchingBtn) matchingBtn.classList.add('selected');
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa field';
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Cập nhật';
    showModal('fieldModal');
}

function saveField(e) {
    e.preventDefault();
    const form = document.getElementById('fieldForm');
    const formData = new FormData(form);

    fetch('../lib/admin_account_field_modules.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            hideModal('fieldModal');
            location.reload();
        } else {
            showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
        }
    })
    .catch(() => showAlert('danger', 'Lỗi kết nối'));
}

function deleteField(id, name) {
    deleteTarget = id;
    document.getElementById('delFieldName').textContent = '"' + name + '"';
    showModal('deleteModal');
}

function confirmDelete() {
    if (!deleteTarget) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', deleteTarget);
    fetch('../lib/admin_account_field_modules.php', {
        method: 'POST',
        body: formData
    })
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
    .catch(() => {
        hideModal('deleteModal');
        showAlert('danger', 'Lỗi kết nối');
    });
}

function showModal(id) { document.getElementById(id).classList.add('show'); }
function hideModal(id) { document.getElementById(id).classList.remove('show'); }
</script>

<?php
$content = ob_get_clean();
admin_renderLayout('Loại Field Tài khoản', 'account_fields');
?>
