<?php
require_once __DIR__ . "/../../../admin/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/types.php";

$types = admin_getTypes();
$categories = admin_getCategoriesFromTypes();

// Nhóm types theo category_id
$grouped = [];
foreach ($types as $t) {
    $cid = $t['category_id'] ?? 0;
    if (!isset($grouped[$cid])) $grouped[$cid] = [];
    $grouped[$cid][] = $t;
}

// Map category id -> name
$catMap = [];
foreach ($categories as $c) {
    $catMap[$c['id']] = $c;
}

ob_start();
?>
<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý Loại sản phẩm</h1>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            <?php echo count($types); ?> loại trong <?php echo count($categories); ?> danh mục
        </p>
    </div>
    <button class="nx-btn nx-btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-plus me-1"></i> Thêm loại
    </button>
</div>

<!-- Alert -->
<div id="alertBox" class="nx-alert d-none mb-3"></div>

<?php if (count($categories) === 0): ?>
    <div class="nx-card">
        <div class="nx-card-body text-center py-5">
            <i class="fa-solid fa-tags fa-2x mb-3 d-block opacity-25 text-muted"></i>
            <p class="text-muted mb-3">Chưa có danh mục nào. Vui lòng tạo danh mục trước.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($categories as $cat): ?>
        <?php
        $catTypes = $grouped[$cat['id']] ?? [];
        ?>
        <div class="nx-card mb-3">
            <div class="nx-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(110,86,207,0.1);display:flex;align-items:center;justify-content:center;color:#6E56CF;">
                        <i class="fa-solid <?php echo htmlspecialchars($cat['icon_class'] ?? 'fa-folder'); ?>"></i>
                    </div>
                    <span class="fw-bold"><?php echo htmlspecialchars($cat['name']); ?></span>
                    <?php if (!empty($cat['description'])): ?>
                        <span class="text-muted small">— <?php echo htmlspecialchars($cat['description']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="nx-badge nx-badge-muted"><?php echo count($catTypes); ?> loại</span>
                    <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="openAddModal(<?php echo $cat['id']; ?>)">
                        <i class="fa-solid fa-plus"></i> Thêm loại
                    </button>
                </div>
            </div>
            <div class="nx-card-body p-0">
                <?php if (count($catTypes) > 0): ?>
                    <div class="table-responsive">
                        <table class="nx-table">
                            <thead>
                                <tr>
                                    <th class="ps-4" style="width:50px;">ID</th>
                                    <th>Loại sản phẩm</th>
                                    <th style="width:80px;">Icon</th>
                                    <th style="width:80px;">Thứ tự</th>
                                    <th class="text-end pe-4" style="width:120px;">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($catTypes as $t): ?>
                                    <tr id="row-<?php echo $t['id']; ?>">
                                        <td class="ps-4 fw-bold text-muted">#<?php echo $t['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid <?php echo htmlspecialchars($t['icon_class'] ?? 'fa-tag'); ?>" style="color:#6E56CF;width:20px;"></i>
                                                <span class="fw-bold"><?php echo htmlspecialchars($t['name']); ?></span>
                                            </div>
                                        </td>
                                        <td><code style="font-size:0.75rem;"><?php echo htmlspecialchars($t['icon_class']); ?></code></td>
                                        <td><span class="nx-badge nx-badge-muted"><?php echo $t['sort_order'] ?? 0; ?></span></td>
                                        <td class="text-end pe-4">
                                            <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick='editType(<?php echo json_encode($t); ?>)'>
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteType(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars(addslashes($t['name'])); ?>')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="nx-card-body text-center py-3">
                        <p class="text-muted mb-2 small">Chưa có loại nào trong danh mục này.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Add / Edit Modal -->
<div class="nx-modal" id="typeModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="modalTitle"><i class="fa-solid fa-plus me-2"></i>Thêm loại</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('typeModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="typeForm" onsubmit="saveType(event)">
            <input type="hidden" id="editId" name="id" value="">
            <input type="hidden" name="action" id="formAction" value="create">
            <div class="nx-modal-body">
                <div class="nx-form-group">
                    <label class="nx-label">Danh mục <span class="text-danger">*</span></label>
                    <select class="nx-select" id="fCategoryId" name="category_id" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Tên loại <span class="text-danger">*</span></label>
                    <input type="text" class="nx-input" id="fName" name="name" required placeholder="VD: Valorant, Netflix Premium">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Icon</label>
                    <div class="nx-icon-picker">
                        <?php
                        $quickIcons = admin_getTypeIconChoices();
                        foreach ($quickIcons as $qi): ?>
                            <label data-icon="<?php echo htmlspecialchars($qi[0]); ?>" onclick="selectIcon(this, '<?php echo htmlspecialchars($qi[0]); ?>')" title="<?php echo htmlspecialchars($qi[1]); ?>">
                                <i class="fa-solid <?php echo htmlspecialchars($qi[0]); ?>"></i>
                                <input type="radio" name="_icon_radio" value="<?php echo htmlspecialchars($qi[0]); ?>">
                            </label>
                        <?php endforeach; ?>
                        <input type="hidden" id="fIcon" name="icon_class" value="fa-tag">
                    </div>
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Thứ tự</label>
                    <input type="number" class="nx-input" id="fSort" name="sort_order" value="0" min="0">
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('typeModal')">Hủy</button>
                <button type="submit" class="nx-btn nx-btn-primary" id="submitBtn">
                    <i class="fa-solid fa-check me-1"></i>Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="nx-modal" id="deleteModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" style="color: var(--danger);"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xác nhận xóa</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('deleteModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="nx-modal-body">
            <p>Xóa loại <strong id="delTypeName"></strong>?</p>
            <p id="delWarning" class="text-danger small" style="display:none;"></p>
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
        setTimeout(() => {
            if (!type.includes('danger')) box.classList.add('d-none');
        }, 5000);
    }

    function selectIcon(btn, icon) {
        document.querySelectorAll('.nx-icon-picker label').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('fIcon').value = icon;
    }

    function openAddModal(catId) {
        document.getElementById('typeForm').reset();
        document.getElementById('editId').value = '';
        document.getElementById('formAction').value = 'create';
        document.getElementById('fCategoryId').value = catId || '';
        document.getElementById('fIcon').value = 'fa-tag';
        document.getElementById('fSort').value = '0';
        document.querySelectorAll('.nx-icon-picker label').forEach(b => b.classList.remove('selected'));
        const defaultIconBtn = document.querySelector('.nx-icon-picker label[data-icon="fa-tag"]');
        if (defaultIconBtn) defaultIconBtn.classList.add('selected');
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus me-2"></i>Thêm loại';
        document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Tạo';
        showModal('typeModal');
    }

    function editType(data) {
        document.getElementById('typeForm').reset();
        document.getElementById('editId').value = data.id;
        document.getElementById('formAction').value = 'update';
        document.getElementById('fCategoryId').value = data.category_id || '';
        document.getElementById('fName').value = data.name || '';
        document.getElementById('fIcon').value = data.icon_class || 'fa-tag';
        document.getElementById('fSort').value = data.sort_order || 0;
        document.querySelectorAll('.nx-icon-picker label').forEach(b => b.classList.remove('selected'));
        const matchingBtn = document.querySelector('.nx-icon-picker label[data-icon="' + (data.icon_class || 'fa-tag') + '"]');
        if (matchingBtn) matchingBtn.classList.add('selected');
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa loại';
        document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Cập nhật';
        showModal('typeModal');
    }

    function saveType(e) {
        e.preventDefault();
        const form = document.getElementById('typeForm');
        const formData = new FormData(form);

        fetch('../../lib/admin_types_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    hideModal('typeModal');
                    showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
                    location.reload();
                } else {
                    showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
                }
            })
            .catch(() => showAlert('danger', 'Đã xảy ra lỗi'));
    }

    function deleteType(id, name) {
        deleteTarget = id;
        document.getElementById('delTypeName').textContent = '"' + name + '"';
        document.getElementById('delWarning').innerHTML = '';
        document.getElementById('delWarning').style.display = 'none';
        showModal('deleteModal');
    }

    function confirmDelete() {
        if (!deleteTarget) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', deleteTarget);
        fetch('../../lib/admin_types_modules.php', {
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
                    document.getElementById('delWarning').textContent = data.message;
                    document.getElementById('delWarning').style.display = '';
                }
                deleteTarget = null;
            })
            .catch(() => {
                hideModal('deleteModal');
                showAlert('danger', 'Đã xảy ra lỗi');
            });
    }
</script>
<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý Loại sản phẩm', 'types');
?>
