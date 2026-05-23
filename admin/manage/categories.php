<?php
require_once __DIR__ . "/../../admin_lib/admin_layout_modules.php";
require_once __DIR__ . "/../../admin_lib/admin_category_modules.php";
require_once __DIR__ . "/../../admin_lib/admin_types_modules.php";

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
    <button class="btn btn-primary" onclick="openAddCatModal()">
        <i class="fa-solid fa-plus me-1"></i> Thêm Danh mục
    </button>
</div>

<!-- Alert -->
<div id="alertBox" class="alert d-none mb-3" role="alert"></div>

<?php if (count($categories) === 0): ?>
    <div class="card bg-white">
        <div class="card-body text-center py-5">
            <i class="fa-solid fa-layer-group fa-2x mb-3 d-block opacity-20 text-muted"></i>
            <p class="text-muted mb-3">Chưa có danh mục nào.</p>
            <button class="btn btn-primary" onclick="openAddCatModal()">
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
                <div class="card bg-white h-100">
                    <!-- Category Header -->
                    <div class="card-header bg-white py-3">
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
                                <button class="btn btn-sm btn-outline-secondary" onclick="openAddTypeModal(<?php echo $cat['id']; ?>)" title="Thêm loại">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick='editCategory(<?php echo json_encode($cat); ?>)' title="Sửa">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>', <?php echo count($catTypes); ?>)" title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Types list -->
                    <?php if (count($catTypes) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($catTypes as $t): ?>
                                <?php $prodCount = admin_getTypeProductCount($t['id']); ?>
                                <li class="list-group-item d-flex align-items-center justify-content-between py-2" id="type-row-<?php echo $t['id']; ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid <?php echo htmlspecialchars($t['icon_class'] ?? 'fa-tag'); ?>" style="color:#6E56CF;width:18px;text-align:center;"></i>
                                        <span class="fw-semibold" style="font-size:0.88rem;"><?php echo htmlspecialchars($t['name']); ?></span>
                                        <?php if ($prodCount > 0): ?>
                                            <span class="badge bg-light text-dark border small"><?php echo $prodCount; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary py-1 px-2" onclick='editType(<?php echo json_encode($t); ?>, <?php echo $cat['id']; ?>)' title="Sửa loại">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger py-1 px-2" onclick="deleteType(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars(addslashes($t['name'])); ?>', <?php echo $prodCount; ?>)" title="Xóa loại">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="card-body text-center py-3">
                            <p class="text-muted mb-2 small">Chưa có loại nào trong danh mục này.</p>
                            <button class="btn btn-sm btn-outline-primary" onclick="openAddTypeModal(<?php echo $cat['id']; ?>)">
                                <i class="fa-solid fa-plus me-1"></i> Thêm loại đầu tiên
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ===================== MODALS ===================== -->

<!-- Category Add/Edit Modal -->
<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="catModalTitle"><i class="fa-solid fa-folder-plus me-2"></i>Thêm Danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="catForm" onsubmit="saveCategory(event)">
                <input type="hidden" id="catEditId" name="id" value="">
                <input type="hidden" name="action" id="catFormAction" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên danh mục <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="catName" name="name" required placeholder="VD: Game, Netflix, GPT">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Icon</label>
                        <div class="d-flex gap-1 flex-wrap">
                            <?php foreach ($catIconChoices as $ic): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary icon-btn-cat" data-icon="<?php echo $ic[0]; ?>" onclick="selectCatIcon(this, '<?php echo $ic[0]; ?>')" title="<?php echo $ic[1]; ?>">
                                    <i class="fa-solid <?php echo $ic[0]; ?>"></i>
                                </button>
                            <?php endforeach; ?>
                            <input type="hidden" id="catIconClass" name="icon_class" value="fa-folder">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mô tả</label>
                        <input type="text" class="form-control" id="catDescription" name="description" placeholder="Mô tả ngắn (tùy chọn)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Thứ tự</label>
                        <input type="number" class="form-control" id="catSortOrder" name="sort_order" value="0" min="0">
                        <div class="form-text">Số càng nhỏ thì xếp trên đầu</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="catSubmitBtn">
                        <i class="fa-solid fa-check me-1"></i>Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Type Add/Edit Modal -->
<div class="modal fade" id="typeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="typeModalTitle"><i class="fa-solid fa-tag me-2"></i>Thêm Loại</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="typeForm" onsubmit="saveType(event)">
                <input type="hidden" id="typeEditId" name="id" value="">
                <input type="hidden" name="action" id="typeFormAction" value="create">
                <input type="hidden" id="typeCategoryId" name="category_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Danh mục <span class="text-danger">*</span></label>
                        <select class="form-select" id="typeCategorySelect" name="category_select" required onchange="document.getElementById('typeCategoryId').value=this.value">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên loại <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="typeName" name="name" required placeholder="VD: Valorant, Netflix Premium">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Icon</label>
                        <div class="d-flex gap-1 flex-wrap">
                            <?php foreach ($typeIconChoices as $ic): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary icon-btn-type" data-icon="<?php echo $ic[0]; ?>" onclick="selectTypeIcon(this, '<?php echo $ic[0]; ?>')" title="<?php echo $ic[1]; ?>">
                                    <i class="fa-solid <?php echo $ic[0]; ?>"></i>
                                </button>
                            <?php endforeach; ?>
                            <input type="hidden" id="typeIconClass" name="icon_class" value="fa-tag">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Thứ tự</label>
                        <input type="number" class="form-control" id="typeSortOrder" name="sort_order" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="typeSubmitBtn">
                        <i class="fa-solid fa-check me-1"></i>Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Confirm -->
<div class="modal fade" id="deleteCatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xóa Danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Xóa danh mục <strong id="delCatName"></strong>?</p>
                <p id="delCatWarning" class="text-danger small"></p>
                <span class="text-muted" style="font-size:0.85rem;">Hành động này không thể hoàn tác.</span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCatBtn" onclick="confirmDeleteCategory()">
                    <i class="fa-solid fa-trash me-1"></i>Xóa
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Type Confirm -->
<div class="modal fade" id="deleteTypeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xóa Loại</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Xóa loại <strong id="delTypeName"></strong>?</p>
                <p id="delTypeWarning" class="text-danger small"></p>
                <span class="text-muted" style="font-size:0.85rem;">Hành động này không thể hoàn tác.</span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTypeBtn" onclick="confirmDeleteType()">
                    <i class="fa-solid fa-trash me-1"></i>Xóa
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const ALL_CATEGORIES = <?php echo json_encode(array_values($categories)); ?>;
    const ALL_TYPES = <?php echo json_encode($allTypes); ?>;
    const CATEGORIES_DATA = <?php echo json_encode(array_values($categories)); ?>;

    let catModal, typeModal, deleteCatModal, deleteTypeModal;
    let deleteCatTarget = null;
    let deleteTypeTarget = null;
    let currentEditTypeCategory = null;

    document.addEventListener('DOMContentLoaded', function() {
        catModal = new bootstrap.Modal(document.getElementById('catModal'));
        typeModal = new bootstrap.Modal(document.getElementById('typeModal'));
        deleteCatModal = new bootstrap.Modal(document.getElementById('deleteCatModal'));
        deleteTypeModal = new bootstrap.Modal(document.getElementById('deleteTypeModal'));
    });

    // ==================== HELPERS ====================
    function showAlert(type, message) {
        const box = document.getElementById('alertBox');
        box.className = 'alert alert-' + type + ' alert-dismissible fade show';
        box.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
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
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        const defaultIconBtn = document.querySelector('.icon-btn-cat[data-icon="fa-folder"]');
        if (defaultIconBtn) {
            defaultIconBtn.classList.remove('btn-outline-secondary');
            defaultIconBtn.classList.add('btn-primary');
        }
        document.getElementById('catModalTitle').innerHTML = '<i class="fa-solid fa-folder-plus me-2"></i>Thêm Danh mục';
        document.getElementById('catSubmitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Tạo';
        catModal.show();
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
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
            if (b.dataset.icon === (data.icon_class || 'fa-folder')) {
                b.classList.remove('btn-outline-secondary');
                b.classList.add('btn-primary');
            }
        });
        document.getElementById('catModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa Danh mục';
        document.getElementById('catSubmitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Cập nhật';
        catModal.show();
    }

    function selectCatIcon(btn, icon) {
        document.querySelectorAll('.icon-btn-cat').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-primary');
        document.getElementById('catIconClass').value = icon;
    }

    function saveCategory(e) {
        e.preventDefault();
        const form = document.getElementById('catForm');
        const formData = new FormData(form);
        fetch('../admin_lib/admin_category_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    catModal.hide();
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
        deleteCatModal.show();
    }

    function confirmDeleteCategory() {
        if (!deleteCatTarget) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', deleteCatTarget);
        fetch('../admin_lib/admin_category_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                deleteCatModal.hide();
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
                deleteCatModal.hide();
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
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        const defaultTypeBtn = document.querySelector('.icon-btn-type[data-icon="fa-tag"]');
        if (defaultTypeBtn) {
            defaultTypeBtn.classList.remove('btn-outline-secondary');
            defaultTypeBtn.classList.add('btn-primary');
        }
        document.getElementById('typeModalTitle').innerHTML = '<i class="fa-solid fa-tag me-2"></i>Thêm Loại';
        document.getElementById('typeSubmitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Tạo';
        currentEditTypeCategory = null;
        typeModal.show();
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
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
            if (b.dataset.icon === (data.icon_class || 'fa-tag')) {
                b.classList.remove('btn-outline-secondary');
                b.classList.add('btn-primary');
            }
        });
        document.getElementById('typeModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa Loại';
        document.getElementById('typeSubmitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Cập nhật';
        currentEditTypeCategory = data.category_id || catId;
        typeModal.show();
    }

    function selectTypeIcon(btn, icon) {
        document.querySelectorAll('.icon-btn-type').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-primary');
        document.getElementById('typeIconClass').value = icon;
    }

    function saveType(e) {
        e.preventDefault();
        // Sync category select -> hidden field
        document.getElementById('typeCategoryId').value = document.getElementById('typeCategorySelect').value;

        const form = document.getElementById('typeForm');
        const formData = new FormData(form);

        const url = '/admin_lib/admin_types_modules.php';

        fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    typeModal.hide();
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
        deleteTypeModal.show();
    }

    function confirmDeleteType() {
        if (!deleteTypeTarget) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', deleteTypeTarget);

        fetch('../admin_lib/admin_types_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                deleteTypeModal.hide();
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
                deleteTypeModal.hide();
                showAlert('danger', 'Đã xảy ra lỗi');
            });
    }
</script>
<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý Danh mục & Loại', 'categories');
?>