<?php
require_once __DIR__ . "/../../../admin/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/categories.php";
require_once __DIR__ . "/../../../admin/crud/types/admin_types_modules.php";

// Load data
$categories = admin_getCategories();
$allTypes = admin_getTypes();
$allCategoryData = admin_getCategories();

// Nhóm types theo category_id
$typesByCat = [];
foreach ($allTypes as $t) {
    $cid = $t['category_id'] ?? 0;
    if (!isset($typesByCat[$cid])) $typesByCat[$cid] = [];
    $typesByCat[$cid][] = $t;
}

// Lấy icons danh mục và loại từ module
$catIconChoices = admin_getCategoryIconChoices();
$typeIconChoices = admin_getTypeIconChoices();

// Category icons map
$catIcons = [];
foreach ($categories as $c) {
    $catIcons[$c['id']] = $c['icon_class'];
}

ob_start();
?>
<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý Danh mục & Loại</h1>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            <?php echo count($categories); ?> danh mục —
            <?php echo count($allTypes); ?> loại
        </p>
    </div>
    <button class="nx-btn nx-btn-primary" onclick="openAddCatModal()">
        <i class="fa-solid fa-plus me-1"></i> Thêm Danh mục
    </button>
</div>

<!-- Alert -->
<div id="alertBox" class="nx-alert d-none mb-3"></div>

<?php if (count($categories) === 0): ?>
    <div class="nx-card">
        <div class="nx-card-body text-center py-5">
            <i class="fa-solid fa-layer-group fa-2x mb-3 d-block opacity-20 text-muted"></i>
            <p class="text-muted mb-3">Chưa có danh mục nào.</p>
            <button class="nx-btn nx-btn-primary" onclick="openAddCatModal()">
                <i class="fa-solid fa-plus me-1"></i> Tạo danh mục đầu tiên
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3" id="categoryList">
        <?php foreach ($categories as $cat): ?>
            <?php
            $catTypes = $typesByCat[$cat['id']] ?? [];
            $totalProducts = 0;
            foreach ($catTypes as $t) {
                $totalProducts += admin_getTypeProductCount($t['id']);
            }
            ?>
            <div class="col-lg-6" id="cat-card-<?php echo $cat['id']; ?>">
                <div class="nx-card h-100">
                    <!-- Category Header -->
                    <div class="nx-card-header py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="cat-icon" style="background:rgba(110,86,207,0.1);color:#6E56CF;">
                                    <i class="fa-solid <?php echo htmlspecialchars($cat['icon_class'] ?? 'fa-folder'); ?>"></i>
                                </div>
                                <div>
                                    <div class="fw-bold" id="cat-name-<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></div>
                                    <div class="small text-muted">
                                        <?php echo count($catTypes); ?> loại ·
                                        <span id="cat-prod-count-<?php echo $cat['id']; ?>"><?php echo $totalProducts; ?></span> sản phẩm
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="openAddTypeModal(<?php echo $cat['id']; ?>)" title="Thêm loại">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                                <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick='editCategory(<?php echo json_encode($cat); ?>)' title="Sửa">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>', <?php echo count($catTypes); ?>)" title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Types list -->
                    <div class="px-4 pb-3">
                        <div class="sidebar-section-label ps-0 mb-2">DANH SÁCH LOẠI</div>
                        <?php if (count($catTypes) > 0): ?>
                            <div class="d-flex flex-column gap-1">
                                <?php foreach ($catTypes as $t): ?>
                                    <?php $prodCount = admin_getTypeProductCount($t['id']); ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded-3 border border-light bg-light bg-opacity-10" id="type-row-<?php echo $t['id']; ?>">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid <?php echo htmlspecialchars($t['icon_class'] ?? 'fa-tag'); ?>" style="color:var(--primary);width:18px;text-align:center;"></i>
                                            <span class="fw-semibold" style="font-size:0.85rem;"><?php echo htmlspecialchars($t['name']); ?></span>
                                            <span class="badge bg-white text-muted border small"><?php echo $prodCount; ?> SP</span>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <button class="nx-btn nx-btn-sm nx-btn-secondary p-1" style="height:28px; width:28px;" onclick='editType(<?php echo json_encode($t); ?>, <?php echo $cat['id']; ?>)' title="Sửa loại">
                                                <i class="fa-solid fa-pen-to-square" style="font-size:0.75rem;"></i>
                                            </button>
                                            <button class="nx-btn nx-btn-sm nx-btn-danger p-1" style="height:28px; width:28px;" onclick="deleteType(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars(addslashes($t['name'])); ?>', <?php echo $prodCount; ?>)" title="Xóa loại">
                                                <i class="fa-solid fa-trash" style="font-size:0.75rem;"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3 border rounded-3 border-dashed">
                                <p class="text-muted mb-2 small">Chưa có loại nào.</p>
                                <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="openAddTypeModal(<?php echo $cat['id']; ?>)">
                                    <i class="fa-solid fa-plus me-1"></i> Thêm loại
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ===================== MODALS ===================== -->

<!-- Category Add/Edit Modal -->
<div class="nx-modal" id="catModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="catModalTitle"><i class="fa-solid fa-folder-plus me-2"></i>Thêm Danh mục</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('catModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="catForm" onsubmit="saveCategory(event)">
            <input type="hidden" id="catEditId" name="id" value="">
            <input type="hidden" name="action" id="catFormAction" value="create">
            <div class="nx-modal-body">
                <div class="nx-form-group">
                    <label class="nx-label">Tên danh mục <span class="text-danger">*</span></label>
                    <input type="text" class="nx-input" id="catName" name="name" required placeholder="VD: Game, Netflix, GPT">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Icon</label>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php foreach ($catIconChoices as $ic): ?>
                            <button type="button" class="icon-btn-cat" data-icon="<?php echo $ic[0]; ?>" onclick="selectCatIcon(this, '<?php echo $ic[0]; ?>')" title="<?php echo $ic[1]; ?>">
                                <i class="fa-solid <?php echo $ic[0]; ?>"></i>
                            </button>
                        <?php endforeach; ?>
                        <input type="hidden" id="catIconClass" name="icon_class" value="fa-folder">
                    </div>
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Mô tả</label>
                    <input type="text" class="nx-input" id="catDescription" name="description" placeholder="Mô tả ngắn (tùy chọn)">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Thứ tự</label>
                    <input type="number" class="nx-input" id="catSortOrder" name="sort_order" value="0" min="0">
                    <div class="form-text">Số càng nhỏ thì xếp trên đầu</div>
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('catModal')">Hủy</button>
                <button type="submit" class="nx-btn nx-btn-primary" id="catSubmitBtn">
                    <i class="fa-solid fa-check me-1"></i>Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Type Add/Edit Modal -->
<div class="nx-modal" id="typeModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="typeModalTitle"><i class="fa-solid fa-tag me-2"></i>Thêm Loại</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('typeModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="typeForm" onsubmit="saveType(event)">
            <input type="hidden" id="typeEditId" name="id" value="">
            <input type="hidden" name="action" id="typeFormAction" value="create">
            <input type="hidden" id="typeCategoryId" name="category_id" value="">
            <div class="nx-modal-body">
                <div class="nx-form-group">
                    <label class="nx-label">Danh mục <span class="text-danger">*</span></label>
                    <select class="nx-select" id="typeCategorySelect" name="category_select" required onchange="document.getElementById('typeCategoryId').value=this.value">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Tên loại <span class="text-danger">*</span></label>
                    <input type="text" class="nx-input" id="typeName" name="name" required placeholder="VD: Valorant, Netflix Premium">
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Icon</label>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php foreach ($typeIconChoices as $ic): ?>
                            <button type="button" class="icon-btn-type" data-icon="<?php echo $ic[0]; ?>" onclick="selectTypeIcon(this, '<?php echo $ic[0]; ?>')" title="<?php echo $ic[1]; ?>">
                                <i class="fa-solid <?php echo $ic[0]; ?>"></i>
                            </button>
                        <?php endforeach; ?>
                        <input type="hidden" id="typeIconClass" name="icon_class" value="fa-tag">
                    </div>
                </div>
                <div class="nx-form-group">
                    <label class="nx-label">Thứ tự</label>
                    <input type="number" class="nx-input" id="typeSortOrder" name="sort_order" value="0" min="0">
                </div>
            </div>
            <div class="nx-modal-footer">
                <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('typeModal')">Hủy</button>
                <button type="submit" class="nx-btn nx-btn-primary" id="typeSubmitBtn">
                    <i class="fa-solid fa-check me-1"></i>Lưu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Category Confirm -->
<div class="nx-modal" id="deleteCatModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xóa Danh mục</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('deleteCatModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="nx-modal-body">
            <p>Xóa danh mục <strong id="delCatName"></strong>?</p>
            <p id="delCatWarning" class="text-danger small"></p>
            <span class="text-muted" style="font-size:0.85rem;">Hành động này không thể hoàn tác.</span>
        </div>
        <div class="nx-modal-footer">
            <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('deleteCatModal')">Hủy</button>
            <button type="button" class="nx-btn nx-btn-danger" id="confirmDeleteCatBtn" onclick="confirmDeleteCategory()">
                <i class="fa-solid fa-trash me-1"></i>Xóa
            </button>
        </div>
    </div>
</div>

<!-- Delete Type Confirm -->
<div class="nx-modal" id="deleteTypeModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xóa Loại</h5>
            <button type="button" class="nx-modal-close" onclick="hideModal('deleteTypeModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="nx-modal-body">
            <p>Xóa loại <strong id="delTypeName"></strong>?</p>
            <p id="delTypeWarning" class="text-danger small"></p>
            <span class="text-muted" style="font-size:0.85rem;">Hành động này không thể hoàn tác.</span>
        </div>
        <div class="nx-modal-footer">
            <button type="button" class="nx-btn nx-btn-secondary" onclick="hideModal('deleteTypeModal')">Hủy</button>
            <button type="button" class="nx-btn nx-btn-danger" id="confirmDeleteTypeBtn" onclick="confirmDeleteType()">
                <i class="fa-solid fa-trash me-1"></i>Xóa
            </button>
        </div>
    </div>
</div>

<script>
    const ALL_CATEGORIES = <?php echo json_encode(array_values($categories)); ?>;
    const ALL_TYPES = <?php echo json_encode($allTypes); ?>;
    const CATEGORIES_DATA = <?php echo json_encode(array_values($categories)); ?>;

    let deleteCatTarget = null;
    let deleteTypeTarget = null;
    let currentEditTypeCategory = null;

    // ==================== MODAL HELPERS ====================
    function showModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('active');
        }
    }

    function hideModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    // ==================== HELPERS ====================
    function showAlert(type, message) {
        const box = document.getElementById('alertBox');
        box.className = 'nx-alert nx-alert-' + type + ' fade show';
        box.innerHTML = message + '<button type="button"></button>';
        box.classList.remove('d-none');
        setTimeout(() => {
            if (!type.includes('danger')) box.classList.add('d-none');
        }, 5000);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // ==================== CATEGORY ====================
    function openAddCatModal() {
        document.getElementById('catForm').reset();
        document.getElementById('catEditId').value = '';
        document.getElementById('catFormAction').value = 'create';
        document.getElementById('catIconClass').value = 'fa-folder';
        document.getElementById('catSortOrder').value = '0';
        document.querySelectorAll('.icon-btn-cat').forEach(b => {
            b.classList.remove('nx-btn-primary');
            b.classList.add('nx-btn-secondary');
        });
        const defaultIconBtn = document.querySelector('.icon-btn-cat[data-icon="fa-folder"]');
        if (defaultIconBtn) {
            defaultIconBtn.classList.remove('nx-btn-secondary');
            defaultIconBtn.classList.add('nx-btn-primary');
        }
        document.getElementById('catModalTitle').innerHTML = '<i class="fa-solid fa-folder-plus me-2"></i>Thêm Danh mục';
        document.getElementById('catSubmitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Tạo';
        showModal('catModal');
    }

    function editCategory(data) {
        document.getElementById('catForm').reset();
        document.getElementById('catEditId').value = data.id;
        document.getElementById('catFormAction').value = 'update';
        document.getElementById('catName').value = data.name || '';
        document.getElementById('catDescription').value = data.description || '';
        document.getElementById('catSortOrder').value = data.sort_order || 0;
        document.getElementById('catIconClass').value = data.icon_class || 'fa-folder';
        document.querySelectorAll('.icon-btn-cat').forEach(b => {
            b.classList.remove('nx-btn-primary');
            b.classList.add('nx-btn-secondary');
            if (b.dataset.icon === (data.icon_class || 'fa-folder')) {
                b.classList.remove('nx-btn-secondary');
                b.classList.add('nx-btn-primary');
            }
        });
        document.getElementById('catModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa Danh mục';
        document.getElementById('catSubmitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Cập nhật';
        showModal('catModal');
    }

    function selectCatIcon(btn, icon) {
        document.querySelectorAll('.icon-btn-cat').forEach(b => {
            b.classList.remove('nx-btn-primary');
            b.classList.add('nx-btn-secondary');
        });
        btn.classList.remove('nx-btn-secondary');
        btn.classList.add('nx-btn-primary');
        document.getElementById('catIconClass').value = icon;
    }

    function saveCategory(e) {
        e.preventDefault();
        const form = document.getElementById('catForm');
        const formData = new FormData(form);
        fetch('../../lib/admin_category_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    hideModal('catModal');
                    showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
                    location.reload();
                } else {
                    showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
                }
            })
            .catch(() => showAlert('danger', 'Đã xảy ra lỗi'));
    }

    function deleteCategory(id, name, typeCount) {
        deleteCatTarget = id;
        document.getElementById('delCatName').textContent = '"' + name + '"';
        const warning = document.getElementById('delCatWarning');
        if (typeCount > 0) {
            warning.innerHTML = `⚠️ <b class="text-danger">Cảnh báo:</b> Còn ${typeCount} loại (tags) đang thuộc danh mục này.<br>Xóa danh mục sẽ <b>xóa theo toàn bộ các loại con</b> bên trong nó. Sự thay đổi không ảnh hưởng đến đơn hàng nhưng các sản phẩm chứa tag này sẽ bị <b>mất mục tag</b> và <b>trắng tag</b>!`;
            warning.style.display = '';
        } else {
            warning.style.display = 'none';
        }
        showModal('deleteCatModal');
    }

    function confirmDeleteCategory() {
        if (!deleteCatTarget) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', deleteCatTarget);
        fetch('../../lib/admin_category_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                hideModal('deleteCatModal');
                if (data.success) {
                    const card = document.getElementById('cat-card-' + deleteCatTarget);
                    if (card) card.remove();
                    showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
                } else {
                    showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
                }
                deleteCatTarget = null;
            })
            .catch(() => {
                hideModal('deleteCatModal');
                showAlert('danger', 'Đã xảy ra lỗi');
            });
    }

    // ==================== TYPE ====================
    function openAddTypeModal(catId) {
        document.getElementById('typeForm').reset();
        document.getElementById('typeEditId').value = '';
        document.getElementById('typeFormAction').value = 'create';
        document.getElementById('typeCategoryId').value = catId || '';
        document.getElementById('typeCategorySelect').value = catId || '';
        document.getElementById('typeSortOrder').value = '0';
        document.getElementById('typeIconClass').value = 'fa-tag';
        document.querySelectorAll('.icon-btn-type').forEach(b => {
            b.classList.remove('nx-btn-primary');
            b.classList.add('nx-btn-secondary');
        });
        const defaultTypeBtn = document.querySelector('.icon-btn-type[data-icon="fa-tag"]');
        if (defaultTypeBtn) {
            defaultTypeBtn.classList.remove('nx-btn-secondary');
            defaultTypeBtn.classList.add('nx-btn-primary');
        }
        document.getElementById('typeModalTitle').innerHTML = '<i class="fa-solid fa-tag me-2"></i>Thêm Loại';
        document.getElementById('typeSubmitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Tạo';
        currentEditTypeCategory = null;
        showModal('typeModal');
    }

    function editType(data, catId) {
        document.getElementById('typeForm').reset();
        document.getElementById('typeEditId').value = data.id;
        document.getElementById('typeFormAction').value = 'update';
        document.getElementById('typeCategoryId').value = data.category_id || catId || '';
        document.getElementById('typeCategorySelect').value = data.category_id || catId || '';
        document.getElementById('typeName').value = data.name || '';
        document.getElementById('typeSortOrder').value = data.sort_order || 0;
        document.getElementById('typeIconClass').value = data.icon_class || 'fa-tag';
        document.querySelectorAll('.icon-btn-type').forEach(b => {
            b.classList.remove('nx-btn-primary');
            b.classList.add('nx-btn-secondary');
            if (b.dataset.icon === (data.icon_class || 'fa-tag')) {
                b.classList.remove('nx-btn-secondary');
                b.classList.add('nx-btn-primary');
            }
        });
        document.getElementById('typeModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa Loại';
        document.getElementById('typeSubmitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Cập nhật';
        currentEditTypeCategory = data.category_id || catId;
        showModal('typeModal');
    }

    function selectTypeIcon(btn, icon) {
        document.querySelectorAll('.icon-btn-type').forEach(b => {
            b.classList.remove('nx-btn-primary');
            b.classList.add('nx-btn-secondary');
        });
        btn.classList.remove('nx-btn-secondary');
        btn.classList.add('nx-btn-primary');
        document.getElementById('typeIconClass').value = icon;
    }

    function saveType(e) {
        e.preventDefault();
        document.getElementById('typeCategoryId').value = document.getElementById('typeCategorySelect').value;

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

    function deleteType(id, name, prodCount) {
        deleteTypeTarget = id;
        document.getElementById('delTypeName').textContent = '"' + name + '"';
        const warning = document.getElementById('delTypeWarning');
        if (prodCount > 0) {
            warning.innerHTML = `⚠️ <b class="text-danger">Cảnh báo:</b> Còn ${prodCount} sản phẩm / đơn hàng đang sử dụng loại này.<br>Xóa loại tag này sẽ <b>không xóa sản phẩm / đơn hàng</b>, nhưng nó sẽ không còn xuất hiện trên trang chủ vì trắng mục tag!`;
            warning.style.display = '';
        } else {
            warning.style.display = 'none';
        }
        showModal('deleteTypeModal');
    }

    function confirmDeleteType() {
        if (!deleteTypeTarget) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', deleteTypeTarget);

        fetch('../../lib/admin_types_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                hideModal('deleteTypeModal');
                if (data.success) {
                    const row = document.getElementById('type-row-' + deleteTypeTarget);
                    if (row) row.remove();
                    showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
                } else {
                    showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
                }
                deleteTypeTarget = null;
            })
            .catch(() => {
                hideModal('deleteTypeModal');
                showAlert('danger', 'Đã xảy ra lỗi');
            });
    }
</script>
<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý Danh mục & Loại', 'categories');
?>
