<?php
require_once __DIR__ . "/../lib/admin_layout_modules.php";
require_once __DIR__ . "/../lib/admin_product_modules.php";
require_once __DIR__ . "/../lib/admin_types_modules.php";
require_once __DIR__ . "/../lib/admin_transaction_modules.php";

// Dữ liệu ban đầu - Sản phẩm
$initialData = admin_getProductsPaginated([
    'search' => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? '',
    'type_id' => $_GET['type_id'] ?? null,
    'page' => intval($_GET['page'] ?? 1),
]);
$types = admin_getTypes();

// Nhóm types theo category_id cho filter dropdown
$typeCategories = [];
foreach ($types as $t) {
    $cid = $t['category_id'] ?? 0;
    if (!isset($typeCategories[$cid])) $typeCategories[$cid] = [];
    $typeCategories[$cid][] = $t;
}

// Lấy categories cho dropdown filter
$filterCategories = admin_getCategoriesFromTypes();
$catIdToName = [];
foreach ($filterCategories as $c) {
    $catIdToName[$c['id']] = $c['name'];
}

// Kho tài khoản - lấy tất cả sản phẩm cho dropdown
$allProducts = mysqli_query($conn, "SELECT id, title FROM products ORDER BY title ASC");
$stockProducts = [];
while ($row = mysqli_fetch_assoc($allProducts)) {
    $stockProducts[] = $row;
}

// Tab hiện tại
$activeTab = $_GET['tab'] ?? 'products';

ob_start();
?>
<!-- Tabs -->
<div class="d-flex align-items-center gap-3 mb-3">
    <h1 class="page-title mb-0" style="font-size:1.3rem;">Quản lý</h1>
    <div class="ms-auto d-flex gap-1">
        <a href="?tab=products" class="nx-btn nx-btn-sm <?php echo $activeTab === 'products' ? 'nx-btn-primary' : 'nx-btn-secondary'; ?>">
            <i class="fa-solid fa-box me-1"></i> Sản phẩm
        </a>
        <a href="?tab=stock" class="nx-btn nx-btn-sm <?php echo $activeTab === 'stock' ? 'nx-btn-primary' : 'nx-btn-secondary'; ?>">
            <i class="fa-solid fa-database me-1"></i> Kho tài khoản
        </a>
    </div>
</div>

<?php if ($activeTab === 'products'): ?>

<!-- ===== TAB: SẢN PHẨM ===== -->
<div class="page-header">
    <div>
        <p class="text-muted mb-0" style="font-size:0.85rem;" id="statsLine">
            Hiển thị <strong id="showCount"><?php echo count($initialData['items']); ?></strong>
            / <strong><?php echo $initialData['total']; ?></strong> sản phẩm
        </p>
    </div>
    <button class="nx-btn nx-btn-primary" onclick="openAddModal()">
        <i class="fa-solid fa-plus me-1"></i> Thêm sản phẩm
    </button>
</div>

<!-- Search + Filter Bar -->
<div class="nx-card mb-3">
    <div class="nx-card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="nx-label small fw-bold mb-1">Tìm kiếm</label>
                <input type="text" class="nx-input border-start-0" id="searchInput" placeholder="Tên sản phẩm, danh mục, loại..." style="border-radius:0 8px 8px 0;" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label class="nx-label small fw-bold mb-1">Loại sản phẩm</label>
                <select class="nx-select" id="filterCategory" style="border-radius:8px;">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($filterCategories as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="nx-label small fw-bold mb-1">Loại</label>
                <select class="nx-select" id="filterType" style="border-radius:8px;">
                    <option value="">Tất cả loại</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="nx-btn nx-btn-primary w-100" onclick="applyFilter()" style="border-radius:8px;">
                    <i class="fa-solid fa-filter me-1"></i> Lọc
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Alert -->
<div id="alertBox" class="alert d-none mb-3" role="alert"></div>

<!-- Products Table -->
<div class="nx-card">
    <!-- Bulk Action Bar -->
    <div class="nx-card-body py-2 border-bottom d-none" id="bulkBar">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <input type="checkbox" id="selectAllTop" onchange="toggleSelectAll()">
                <label for="selectAllTop" class="fw-bold text-muted small">Chọn tất cả trang</label>
                <span class="nx-badge nx-badge-primary ms-1" id="selectedCount">0</span>
            </div>
            <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="bulkDelete()">
                <i class="fa-solid fa-trash me-1"></i>Xóa đã chọn
            </button>
        </div>
    </div>

    <div class="nx-card-body p-0">
        <div class="table-responsive">
            <table class="nx-table table-hover align-middle mb-0" id="productsTable">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:40px;">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th style="width:60px;">ID</th>
                        <th style="width:80px;">Hình</th>
                        <th>Tên sản phẩm</th>
                        <th style="width:120px;">Danh mục</th>
                        <th style="width:140px;">Loại</th>
                        <th style="width:120px;">Giá</th>
                        <th style="width:80px;">Trạng thái</th>
                        <th class="text-end pe-4" style="width:120px;">Hành động</th>
                    </tr>
                </thead>
                <tbody id="productsBody">
                    <!-- Rendered by JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="nx-card-footer" id="paginationArea">
        <!-- Rendered by JS -->
    </div>
</div>

<!-- Image Preview Modal -->
<div class="nx-modal" id="imgPreviewModal" style="background:rgba(0,0,0,0.85);">
    <div class="nx-modal-inner" style="background:transparent;box-shadow:none;max-width:90vw;">
        <button type="button" class="nx-modal-close" onclick="closeImgPreview()" style="background:transparent;border:none;color:#fff;opacity:0.7;width:32px;height:32px;border-radius:6px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-xmark"></i></button>
        <div class="nx-modal-body text-center p-0" style="background:transparent;">
            <img id="previewImg" src="" alt="" style="max-width:100%;border-radius:8px;">
        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<div class="nx-modal" id="productModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title" id="modalTitle"><i class="fa-solid fa-plus me-2"></i>Thêm sản phẩm</h5>
            <button type="button" class="nx-modal-close" onclick="closeProductModal()"></button>
        </div>
        <form id="productForm" onsubmit="saveProduct(event)">
            <input type="hidden" id="editId" name="id" value="">
            <input type="hidden" name="action" id="formAction" value="create">
            <div class="nx-modal-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="nx-card p-3" style="background: #f8fafc; border-style: dashed;">
                                    <label class="nx-label fw-bold small text-primary mb-2">Thông tin cơ bản</label>
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="nx-label fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                                            <input type="text" class="nx-input" id="fTitle" name="title" required placeholder="VD: Tài khoản Netflix Premium">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="nx-label fw-bold">Badge</label>
                                            <input type="text" class="nx-input" id="fBadge" name="badge" placeholder="Hot, VIP, -50%...">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="nx-label fw-bold">Danh mục <span class="text-danger">*</span></label>
                                <select class="nx-select" id="fCategory" name="category" required onchange="onCategoryChange()">
                                    <option value="">-- Chọn --</option>
                                    <?php foreach ($filterCategories as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="nx-label fw-bold">Loại sản phẩm</label>
                                <select class="nx-select" id="fTypeId" name="type_id">
                                    <option value="">-- Không phân loại --</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="nx-label fw-bold text-success">Giá bán hiện tại <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="nx-input" id="fPrice" name="price" required min="0" placeholder="0">
                                    <span class="input-group-text">đ</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="nx-label fw-bold text-muted">Giá gốc (để gạch đi)</label>
                                <div class="input-group">
                                    <input type="number" class="nx-input" id="fOldPrice" name="old_price" min="0" placeholder="0">
                                    <span class="input-group-text">đ</span>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="nx-label fw-bold">Link ảnh sản phẩm</label>
                                <div class="d-flex gap-2">
                                    <input type="url" class="nx-input" id="fImageUrl" name="image_url" placeholder="https://..." oninput="updateImagePreview()">
                                    <div id="imgPreviewContainer" style="width: 42px; height: 42px; border-radius: 8px; border: 1px solid #ddd; overflow: hidden; flex-shrink: 0;">
                                        <img id="imgPreviewThumb" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- Icon + Color -->
                    <div class="col-md-6">
                        <label class="nx-label fw-bold">Icon</label>
                        <div class="d-flex gap-1 flex-wrap">
                            <?php
                            $quickIcons = [
                                ['fa-n', 'Netflix'],
                                ['fa-youtube', 'YouTube'],
                                ['fa-spotify', 'Spotify'],
                                ['fa-play', 'Disney+'],
                                ['fa-robot', 'AI'],
                                ['fa-gamepad', 'Game'],
                                ['fa-fire', 'Fire'],
                                ['fa-cloud', 'Cloud'],
                                ['fa-crown', 'Crown'],
                                ['fa-box', 'Mặc định'],
                            ];
                            foreach ($quickIcons as $ic): ?>
                                <button type="button" class="nx-btn nx-btn-sm nx-btn-secondary icon-btn" data-icon="<?php echo $ic[0]; ?>" onclick="selectIcon(this, '<?php echo $ic[0]; ?>')" title="<?php echo $ic[1]; ?>">
                                    <i class="fa-solid <?php echo $ic[0]; ?>"></i>
                                </button>
                            <?php endforeach; ?>
                            <input type="hidden" id="fIconClass" name="icon_class" value="fa-box">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="nx-label fw-bold">Màu nền badge</label>
                        <div class="d-flex gap-2 align-items-center">
                            <?php
                            $colors = [
                                ['bg-danger', '#EF4444'],
                                ['bg-primary', '#3B82F6'],
                                ['bg-success', '#10B981'],
                                ['bg-warning', '#F59E0B'],
                                ['bg-info', '#06B6D4'],
                                ['bg-dark', '#1F2937'],
                                ['bg-secondary', '#6B7280'],
                                ['bg-light', '#F3F4F6'],
                            ];
                            foreach ($colors as $c): ?>
                                <label class="color-swatch" style="background:<?php echo $c[1]; ?>;" title="<?php echo $c[0]; ?>">
                                    <input type="radio" name="color_class" value="<?php echo $c[0]; ?>" class="d-none">
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Description & Details -->
                    <div class="col-12">
                        <div class="nx-card p-3" style="background: #fff;">
                            <label class="nx-label fw-bold small text-primary mb-2">Chi tiết sản phẩm</label>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="nx-label fw-bold">Mô tả ngắn</label>
                                    <textarea class="nx-input" id="fDescription" name="description" rows="2" placeholder="Hiển thị ở trang chủ..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="nx-label fw-bold d-flex justify-content-between">
                                        <span>Thông số kỹ thuật (JSON)</span>
                                        <a href="javascript:void(0)" onclick="fillDemoJson()" class="small text-decoration-none">Mẫu thử</a>
                                    </label>
                                    <textarea class="nx-input font-monospace" id="fDetails" name="details" rows="3" placeholder='{"Rank": "Vàng 1", "VP": "200 ACC VP"}' style="font-size:0.82rem; background: #fafafa;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nx-modal-footer" style="background: #f8fafc;">
                <button type="button" class="btn btn-link text-muted text-decoration-none me-auto" onclick="closeProductModal()">Hủy bỏ</button>
                <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                    <i class="fa-solid fa-cloud-arrow-up me-2"></i>Cập nhật sản phẩm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function fillDemoJson() {
        const demo = { "Nền tảng": "PC", "Bảo hành": "7 Ngày", "Chất lượng": "1080p" };
        document.getElementById('fDetails').value = JSON.stringify(demo, null, 2);
    }
</script>

<!-- Delete Confirm Modal -->
<div class="nx-modal" id="deleteModal">
    <div class="nx-modal-inner">
        <div class="nx-modal-header">
            <h5 class="nx-modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xác nhận xóa</h5>
            <button type="button" class="nx-modal-close" onclick="closeDeleteModal()"></button>
        </div>
        <div class="nx-modal-body">
            <p>Bạn có chắc muốn xóa sản phẩm <strong id="delProductName"></strong>?</p>
            <span class="text-muted" style="font-size:0.85rem;">Hành động này không thể hoàn tác.</span>
        </div>
        <div class="nx-modal-footer">
            <button type="button" class="nx-btn nx-btn-secondary" onclick="closeDeleteModal()">Hủy</button>
            <button type="button" class="nx-btn nx-btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                <i class="fa-solid fa-trash me-1"></i>Xóa
            </button>
        </div>
    </div>
</div>

<script>
    // ==================== CONFIG ====================
    const ALL_TYPES = <?php echo json_encode($types); ?>;
    const CAT_ID_TO_NAME = <?php echo json_encode($catIdToName); ?>;
    const TYPE_CATEGORIES = <?php echo json_encode($typeCategories); ?>;
    const PAGINATION_DATA = <?php echo json_encode([
                                'items' => $initialData['items'],
                                'total' => $initialData['total'],
                                'page' => $initialData['page'],
                                'per_page' => $initialData['per_page'],
                                'total_pages' => $initialData['total_pages'],
                                'search' => $_GET['search'] ?? '',
                                'category' => $_GET['category'] ?? '',
                                'type_id' => $_GET['type_id'] ?? null,
                            ]); ?>;

    let currentPage = PAGINATION_DATA.page;
    let currentSearch = PAGINATION_DATA.search;
    let currentCategory = PAGINATION_DATA.category;
    let currentTypeId = PAGINATION_DATA.type_id;
    let deleteTarget = null;

    // ==================== INIT ====================
    document.addEventListener('DOMContentLoaded', function() {
        renderTable(PAGINATION_DATA);
        initFilterUI();
        initColorSwatches();

        // Enter to search
        document.getElementById('searchInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') applyFilter();
        });
    });

    function initFilterUI() {
        if (currentCategory) {
            document.getElementById('filterCategory').value = currentCategory;
            populateTypeFilter(currentCategory);
            if (currentTypeId) {
                document.getElementById('filterType').value = currentTypeId;
            }
        }
    }

    function initColorSwatches() {
        document.querySelectorAll('.color-swatch').forEach(swatch => {
            swatch.addEventListener('click', function() {
                document.querySelectorAll('.color-swatch').forEach(s => s.style.outline = 'none');
                this.style.outline = '3px solid #6E56CF';
                this.style.borderRadius = '6px';
                const radio = this.querySelector('input');
                radio.checked = true;
            });
        });
    }

    // ==================== TABLE RENDERING ====================
    function renderTable(data) {
        const tbody = document.getElementById('productsBody');
        const items = data.items || [];

        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-5">
 <i class="fa-solid fa-box-open fa-2x mb-3 d-block opacity-20 text-muted"></i>
 <p class="text-muted mb-2">Không tìm thấy sản phẩm nào</p>
 <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="resetFilter()">Xóa bộ lọc</button>
 </td></tr>`;
            document.getElementById('paginationArea').innerHTML = '';
            document.getElementById('statsLine').innerHTML = `Hiển thị <strong id="showCount">0</strong> / <strong>0</strong> sản phẩm`;
            document.getElementById('bulkBar').classList.add('d-none');
            return;
        }

        tbody.innerHTML = items.map(p => {
            const discount = (p.old_price > 0 && p.price < p.old_price) ?
                Math.round((1 - p.price / p.old_price) * 100) : 0;
            const badgeClass = getBadgeClass(p.badge);
            const badgeStyle = getBadgeStyle(p.badge);

            return `<tr id="row-${p.id}" class="${isRowSelected(p.id) ? 'table-active' : ''}">
 <td class="ps-4">
 <input type="checkbox" class="row-check" value="${p.id}" ${isRowSelected(p.id) ? 'checked' : ''} onchange="onRowCheckChange(this)">
 </td>
 <td class="ps-4 fw-bold text-muted small">#${String(p.id).padStart(4,'0')}</td>
 <td>
 <img src="${escapeHtml(p.image_url)}" width="60" height="42" style="border-radius:8px;object-fit:cover;cursor:pointer;" alt="" onclick="openImgPreview('${escapeHtml(p.image_url)}')" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
 </td>
 <td>
 <div style="font-weight:700;font-size:0.88rem;max-width:280px;" class="text-truncate">${escapeHtml(p.title)}</div>
 ${p.game_type ? `<div style="font-size:0.72rem;color:#9ca3af;" class="text-truncate">${escapeHtml(p.game_type)}</div>` : ''}
 </td>
 <td><span class="nx-badge nx-badge-muted">${escapeHtml(p.category)}</span></td>
 <td>${p.type_name ? `<span style="background:rgba(110,86,207,0.12);color:#6E56CF;border:1px solid rgba(110,86,207,0.2);font-weight:600;">
 <i class="fa-solid ${escapeHtml(p.type_icon || 'fa-tag')} me-1"></i>${escapeHtml(p.type_name)}
 </span>` : '<span class="text-muted small">—</span>'}</td>
 <td>
 <span class="text-success fw-bold">${formatNumber(p.price)}đ</span>
 ${p.old_price > 0 ? `<div class="small text-decoration-line-through text-muted">${formatNumber(p.old_price)}đ</div>` : ''}
 </td>
 <td>
 ${p.badge ? `<span class="nx-badge ${badgeClass}" ${badgeStyle}>${escapeHtml(p.badge)}</span>` : ''}
 ${discount > 0 ? `<span class="nx-badge nx-badge-danger ms-1" style="font-size:0.65rem;">-${discount}%</span>` : ''}
 </td>
 <td class="text-end pe-4">
 <button class="nx-btn nx-btn-sm nx-btn-secondary" onclick='editProduct(${JSON.stringify(p)})' title="Sửa">
 <i class="fa-solid fa-pen-to-square"></i>
 </button>
 <button class="nx-btn nx-btn-sm nx-btn-danger" onclick="deleteProduct(${p.id}, '${escapeHtml(p.title.replace(/'/g,"\\'"))}')" title="Xóa">
 <i class="fa-solid fa-trash"></i>
 </button>
 </td>
 </tr>`;
        }).join('');

        // Update stats
        const showing = items.length;
        const total = data.total;
        document.getElementById('statsLine').innerHTML =
            `Hiển thị <strong id="showCount">${showing}</strong> / <strong>${total}</strong> sản phẩm`;
        document.getElementById('paginationArea').innerHTML = renderPagination(data);

        updateBulkBar();
    }

    function renderPagination(data) {
        if (data.total_pages <= 1) return '';

        const {
            page,
            total_pages,
            per_page,
            total
        } = data;
        let html = '<div class="d-flex align-items-center justify-content-between px-3 py-2">';

        // Summary
        html += `<span class="small text-muted">Trang ${page} / ${total_pages} — ${formatNumber(total)} sản phẩm</span>`;

        // Controls
        html += '<div class="d-flex gap-1 align-items-center">';

        // Prev
        if (page > 1) {
            html += `<button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="goPage(${page - 1})">
 <i class="fa-solid fa-chevron-left"></i>
 </button>`;
        } else {
            html += `<button class="nx-btn nx-btn-sm nx-btn-secondary" disabled><i class="fa-solid fa-chevron-left"></i></button>`;
        }

        // Page numbers
        const maxVisible = 5;
        let start = Math.max(1, page - Math.floor(maxVisible / 2));
        let end = Math.min(total_pages, start + maxVisible - 1);
        if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);
        for (let i = start; i <= end; i++) {
            if (i === page) {
                html += `<button class="nx-btn nx-btn-sm nx-btn-primary">${i}</button>`;
            } else {
                html += `<button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="goPage(${i})">${i}</button>`;
            }
        }

        // Next
        if (page < total_pages) {
            html += `<button class="nx-btn nx-btn-sm nx-btn-secondary" onclick="goPage(${page + 1})">
 <i class="fa-solid fa-chevron-right"></i>
 </button>`;
        } else {
            html += `<button class="nx-btn nx-btn-sm nx-btn-secondary" disabled><i class="fa-solid fa-chevron-right"></i></button>`;
        }

        // Per page
        html += `<select class="nx-select" style="width:auto;" onchange="changePerPage(this.value)">
 <option value="10" ${per_page==10?'selected':''}>10 / trang</option>
 <option value="20" ${per_page==20?'selected':''}>20 / trang</option>
 <option value="50" ${per_page==50?'selected':''}>50 / trang</option>
 <option value="100" ${per_page==100?'selected':''}>100 / trang</option>
 </select>`;

        html += '</div></div>';
        return html;
    }

    // ==================== FILTER & PAGINATION ====================
    function applyFilter() {
        currentSearch = document.getElementById('searchInput').value.trim();
        currentCategory = document.getElementById('filterCategory').value;
        currentTypeId = document.getElementById('filterType').value || null;
        currentPage = 1;
        loadProducts();
    }

    function resetFilter() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterCategory').value = '';
        document.getElementById('filterType').innerHTML = '<option value="">Tất cả loại</option>';
        currentSearch = '';
        currentCategory = '';
        currentTypeId = null;
        currentPage = 1;
        loadProducts();
    }

    function goPage(page) {
        currentPage = page;
        loadProducts();
    }

    function changePerPage(per_page) {
        currentPage = 1;
        loadProducts(per_page);
    }

    function onCategoryChange() {
        const cat = document.getElementById('fCategory').value;
        const typeSelect = document.getElementById('fTypeId');
        typeSelect.innerHTML = '<option value="">— Không chọn —</option>';
        if (!cat) return;

        const types = TYPE_CATEGORIES[cat] || [];
        types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            typeSelect.appendChild(opt);
        });
    }

    function populateTypeFilter(category) {
        const select = document.getElementById('filterType');
        select.innerHTML = '<option value="">Tất cả loại</option>';
        const types = TYPE_CATEGORIES[category] || [];
        types.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            select.appendChild(opt);
        });
    }

    // Trigger type filter when category changes
    document.getElementById('filterCategory').addEventListener('change', function() {
        populateTypeFilter(this.value);
    });

    function loadProducts(overridePerPage) {
        const body = new URLSearchParams();
        body.set('action', 'paginated');
        body.set('page', currentPage);
        if (overridePerPage) body.set('per_page', overridePerPage);
        if (currentSearch) body.set('search', currentSearch);
        if (currentCategory) body.set('category', currentCategory);
        if (currentTypeId) body.set('type_id', currentTypeId);

        fetch('../lib/admin_product_modules.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            })
            .then(async r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const text = await r.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Raw Server Response:', text);
                    throw new Error('Không thể phân tích dữ liệu trả về từ server.');
                }
            })
            .then(data => {
                if (data.success) {
                    renderTable(data);
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            })
            .catch(err => {
                console.error('loadProducts error:', err);
                showAlert('danger', 'Không thể tải dữ liệu: ' + err.message);
            });
    }

    // ==================== CRUD ====================
    function openAddModal() {
        document.getElementById('productForm').reset();
        document.getElementById('editId').value = '';
        document.getElementById('formAction').value = 'create';
        document.getElementById('fCategory').value = '';
        document.getElementById('fTypeId').innerHTML = '<option value="">— Không chọn —</option>';
        document.getElementById('fIconClass').value = 'fa-box';
        document.getElementById('imgPreviewThumb').style.display = 'none';
        document.getElementById('imgPreviewThumb').src = '';
        document.querySelectorAll('.icon-btn').forEach(b => b.classList.remove('nx-btn-primary'));
        document.querySelectorAll('.icon-btn').forEach(b => b.classList.add('nx-btn-secondary'));
        document.querySelector('.icon-btn[title="Mặc định"]').classList.remove('nx-btn-secondary');
        document.querySelector('.icon-btn[title="Mặc định"]').classList.add('nx-btn-primary');
        document.querySelectorAll('.color-swatch').forEach(s => s.style.outline = 'none');
        const firstColor = document.querySelector('.color-swatch');
        if (firstColor) {
            firstColor.style.outline = '3px solid #6E56CF';
            firstColor.querySelector('input').checked = true;
        }
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus me-2"></i>Thêm sản phẩm';
        document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Lưu';
        showModal('productModal');
    }

    function editProduct(data) {
        document.getElementById('productForm').reset();
        document.getElementById('editId').value = data.id;
        document.getElementById('formAction').value = 'update';

        document.getElementById('fTitle').value = data.title || '';
        document.getElementById('fCategory').value = data.category || '';
        document.getElementById('fGameType').value = data.game_type || '';
        document.getElementById('fImageUrl').value = data.image_url || '';
        document.getElementById('fPrice').value = data.price || 0;
        document.getElementById('fOldPrice').value = data.old_price || 0;
        document.getElementById('fBadge').value = data.badge || '';
        document.getElementById('fDescription').value = data.description || '';
        document.getElementById('fDetails').value = data.details || '';

        // Icon
        document.getElementById('fIconClass').value = data.icon_class || 'fa-box';
        document.querySelectorAll('.icon-btn').forEach(b => {
            b.classList.remove('nx-btn-primary');
            b.classList.add('nx-btn-secondary');
            if (b.dataset.icon === (data.icon_class || 'fa-box')) {
                b.classList.remove('nx-btn-secondary');
                b.classList.add('nx-btn-primary');
            }
        });

        // Color
        document.querySelectorAll('.color-swatch').forEach(s => {
            s.style.outline = 'none';
            if (s.querySelector('input').value === (data.color_class || 'bg-secondary')) {
                s.style.outline = '3px solid #6E56CF';
                s.style.borderRadius = '6px';
                s.querySelector('input').checked = true;
            }
        });

        // Type options + selection
        onCategoryChange();
        if (data.type_id) {
            const opt = document.getElementById('fTypeId').querySelector(`option[value="${data.type_id}"]`);
            if (opt) opt.selected = true;
        }

        // Image preview
        updateImagePreview();

        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Sửa sản phẩm';
        document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-check me-1"></i>Cập nhật';
        showModal('productModal');
    }

    function updateImagePreview() {
        const url = document.getElementById('fImageUrl').value;
        const img = document.getElementById('imgPreviewThumb');
        if (url) {
            img.src = url;
            img.style.display = 'block';
            img.onerror = () => {
                img.style.display = 'none';
            };
        } else {
            img.style.display = 'none';
        }
    }

    function selectIcon(btn, icon) {
        document.querySelectorAll('.icon-btn').forEach(b => {
            b.classList.remove('nx-btn-primary');
            b.classList.add('nx-btn-secondary');
        });
        btn.classList.remove('nx-btn-secondary');
        btn.classList.add('nx-btn-primary');
        document.getElementById('fIconClass').value = icon;
    }

    function saveProduct(e) {
        e.preventDefault();
        const form = document.getElementById('productForm');
        const formData = new FormData(form);
        const isUpdate = formData.get('action') === 'update';

        fetch('../lib/admin_product_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(async r => {
                const text = await r.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Raw Server Response:', text);
                    throw new Error('Server trả về dữ liệu lỗi (không phải JSON). Xem chi tiết tại Console (F12). Chi tiết: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                if (data.success) {
                    hideModal('productModal');
                    showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
                    if (isUpdate) {
                        // Live update: reload current page data
                        loadProducts();
                    } else {
                        loadProducts();
                    }
                } else {
                    showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
                }
            })
            .catch((err) => {
                console.error(err);
                showAlert('danger', err.message);
            });
    }

    function deleteProduct(id, name) {
        deleteTarget = id;
        document.getElementById('delProductName').textContent = '"' + name + '"';
        showModal('deleteModal');
    }

    function confirmDelete() {
        if (!deleteTarget) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', deleteTarget);
        fetch('../lib/admin_product_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(async r => {
                const text = await r.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Raw Server Response:', text);
                    throw new Error('Server trả về dữ liệu lỗi (không phải JSON). Chi tiết: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                hideModal('deleteModal');
                if (data.success) {
                    const row = document.getElementById('row-' + deleteTarget);
                    if (row) row.remove();
                    showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
                    loadProducts();
                } else {
                    showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
                }
                deleteTarget = null;
            })
            .catch(err => {
                console.error(err);
                hideModal('deleteModal');
                showAlert('danger', err.message);
            });
    }

    // ==================== BULK DELETE ====================
    const selectedIds = new Set();

    function isRowSelected(id) {
        return selectedIds.has(String(id));
    }

    function onRowCheckChange(el) {
        const row = el.closest('tr');
        if (el.checked) {
            selectedIds.add(el.value);
            row.classList.add('table-active');
        } else {
            selectedIds.delete(el.value);
            row.classList.remove('table-active');
        }
        updateBulkBar();
        updateSelectedCount();
    }

    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        const checks = document.querySelectorAll('.row-check');
        checks.forEach(c => {
            c.checked = checked;
        });
        const rows = document.querySelectorAll('#productsBody tr');
        if (checked) {
            checks.forEach(c => selectedIds.add(c.value));
            rows.forEach(r => r.classList.add('table-active'));
        } else {
            checks.forEach(c => selectedIds.delete(c.value));
            rows.forEach(r => r.classList.remove('table-active'));
        }
        updateBulkBar();
        updateSelectedCount();
    }

    function updateBulkBar() {
        const bar = document.getElementById('bulkBar');
        const anySelected = selectedIds.size > 0;
        bar.classList.toggle('d-none', !anySelected);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        document.getElementById('selectedCount').textContent = selectedIds.size;
    }

    function bulkDelete() {
        if (selectedIds.size === 0) return;
        if (!confirm(`Xóa ${selectedIds.size} sản phẩm đã chọn?`)) return;

        const formData = new FormData();
        formData.append('action', 'bulk_delete');
        selectedIds.forEach(id => formData.append('ids[]', id));

        fetch('../lib/admin_product_modules.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + data.message);
                    selectedIds.clear();
                    loadProducts();
                } else {
                    showAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + data.message);
                }
            })
            .catch(() => showAlert('danger', 'Đã xảy ra lỗi'));
    }

    // ==================== IMAGE PREVIEW ====================
    function openImgPreview(url) {
        document.getElementById('previewImg').src = url;
        showModal('imgPreviewModal');
    }

    // ==================== HELPERS ====================
    function showAlert(type, message) {
        const box = document.getElementById('alertBox');
        box.className = `alert nx-alert-${type} show`;
        box.innerHTML = message;
        box.classList.remove('d-none');
        setTimeout(() => {
            if (!type.includes('danger')) box.classList.add('d-none');
        }, 5000);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatNumber(n) {
        return new Intl.NumberFormat('vi-VN').format(n || 0);
    }

    function getBadgeClass(badge) {
        const map = {
            'Hot': 'nx-badge-danger',
            'VIP': 'nx-badge-vip',
            'Deal': 'nx-badge-success',
            'New': 'nx-badge-primary'
        };
        return map[badge] || 'nx-badge-muted';
    }

    function getBadgeStyle(badge) {
        return badge === 'VIP' ? 'style="background:linear-gradient(135deg,#f6d365,#fda085);color:#000;font-weight:700;"' : '';
    }

    function closeProductModal() {
        document.getElementById('productModal').classList.remove('show');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
    }

    function closeImgPreview() {
        document.getElementById('imgPreviewModal').classList.remove('show');
    }
</script>

<?php endif; // end products tab ?>

<?php /* ===== TAB: KHO TAI KHOAN ===== */ ?>
<?php if ($activeTab === 'stock'): ?>

<!-- Alert -->
<div id="stockAlert" class="nx-alert d-none mb-3"></div>

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="nx-card">
            <div class="nx-card-body d-flex align-items-center gap-3 py-3">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(110,86,207,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-database" style="color:#6E56CF;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;" id="statTotal">—</div>
                    <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">Tổng tài khoản</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="nx-card">
            <div class="nx-card-body d-flex align-items-center gap-3 py-3">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(16,185,129,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-check-circle" style="color:#10B981;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;" id="statAvail">—</div>
                    <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">Khả dụng</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="nx-card">
            <div class="nx-card-body d-flex align-items-center gap-3 py-3">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-cart-shopping" style="color:#EF4444;"></i>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;" id="statSold">—</div>
                    <div style="font-size:0.75rem;color:#6b7280;font-weight:600;text-transform:uppercase;">Đã bán</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Account Form (Manual) -->
<div class="nx-card mb-3">
    <div class="nx-card-header d-flex align-items-center justify-content-between">
        <span><i class="fa-solid fa-plus-circle me-2 text-primary"></i>Thêm tài khoản (thủ công)</span>
    </div>
    <div class="nx-card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="nx-label small fw-bold">Sản phẩm <span class="text-danger">*</span></label>
                <select class="nx-select" id="stockProduct">
                    <option value="">-- Chọn sản phẩm --</option>
                    <?php foreach ($stockProducts as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="nx-label small fw-bold">User</label>
                <input type="text" class="nx-input" id="stockUser" placeholder="acc@gmail.com">
            </div>
            <div class="col-md-2">
                <label class="nx-label small fw-bold">Pass</label>
                <input type="text" class="nx-input" id="stockPass" placeholder="Matkhau123">
            </div>
            <div class="col-md-3">
                <label class="nx-label small fw-bold">Extra <span class="text-muted fw-normal">(key:value, mỗi dòng 1)</span></label>
                <textarea class="nx-input font-monospace" id="stockExtra" rows="2" placeholder="cookie:ABC123&#10;token:XYZ789" style="font-size:0.8rem;"></textarea>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="nx-btn nx-btn-primary w-100" onclick="stockAdd()">
                    <i class="fa-solid fa-plus me-1"></i> Thêm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Add -->
<div class="nx-card mb-3">
    <div class="nx-card-header d-flex align-items-center justify-content-between">
        <span><i class="fa-solid fa-upload me-2 text-primary"></i>Import hàng loạt</span>
    </div>
    <div class="nx-card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="nx-label small fw-bold">Sản phẩm <span class="text-danger">*</span></label>
                <select class="nx-select" id="bulkProduct">
                    <option value="">-- Chọn sản phẩm --</option>
                    <?php foreach ($stockProducts as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-9">
                <label class="nx-label small fw-bold">Danh sách <span class="text-muted fw-normal">(key:value, mỗi dòng 1)</span></label>
                <textarea class="nx-input font-monospace" id="bulkAccounts" rows="5" placeholder="account:acc1@gmail.com:Matkhau1&#10;cookie:ABC123DEF456&#10;key:XYZ789&#10;account:acc2@gmail.com:Matkhau2" style="font-size:0.8rem;"></textarea>
            </div>
        </div>
        <div class="mt-3">
            <button class="nx-btn nx-btn-secondary" onclick="stockBulkAdd()">
                <i class="fa-solid fa-upload me-1"></i> Import
            </button>
            <span class="text-muted ms-2" style="font-size:0.8rem;">
                Format: <code>account:user:pass</code> | <code>cookie:value</code> | <code>key:value</code>
            </span>
        </div>
    </div>
</div>

<!-- Accounts List -->
<div class="nx-card">
    <div class="nx-card-header d-flex align-items-center justify-content-between">
        <span><i class="fa-solid fa-list me-2 text-primary"></i>Danh sách tài khoản</span>
        <div class="d-flex gap-2">
            <select class="nx-select" id="stockFilterProduct" onchange="stockLoad()" style="width:auto;min-width:200px;">
                <option value="">-- Tất cả sản phẩm --</option>
                <?php foreach ($stockProducts as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="nx-card-body p-0">
        <div class="table-responsive">
            <table class="nx-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Sản phẩm</th>
                        <th>Thông tin tài khoản</th>
                        <th style="width:100px;">Trạng thái</th>
                        <th style="width:140px;">Ngày tạo</th>
                        <th class="pe-4" style="width:80px;">Xóa</th>
                    </tr>
                </thead>
                <tbody id="stockBody">
                    <tr><td colspan="6" class="text-center py-5 text-muted">Chọn sản phẩm để xem</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // ==================== ACCOUNT STOCK ====================
    function stockAlert(type, msg) {
        const box = document.getElementById('stockAlert');
        box.className = `alert nx-alert-${type} show`;
        box.innerHTML = msg;
        box.classList.remove('d-none');
        setTimeout(() => { if (!type.includes('danger')) box.classList.add('d-none'); }, 5000);
    }

    function stockAdd() {
        const pid = document.getElementById('stockProduct').value;
        const user = document.getElementById('stockUser').value.trim();
        const pass = document.getElementById('stockPass').value.trim();
        const extraRaw = document.getElementById('stockExtra').value.trim();

        if (!pid) return stockAlert('danger', 'Chọn sản phẩm');
        if (!user && !pass && !extraRaw) return stockAlert('danger', 'Nhập thông tin tài khoản (user, pass hoặc extra)');

        const accountData = {};
        if (user) accountData.user = user;
        if (pass) accountData.pass = pass;
        if (extraRaw) {
            extraRaw.split('\n').forEach(line => {
                const idx = line.indexOf(':');
                if (idx > 0) {
                    const k = line.substring(0, idx).trim();
                    const v = line.substring(idx + 1).trim();
                    if (k && v) accountData[k] = v;
                }
            });
        }

        const body = new URLSearchParams({action:'add', product_id:pid, account_data:JSON.stringify(accountData)});
        fetch('../lib/admin_account_stock_modules.php', {method:'POST', body, headers:{'Content-Type':'application/x-www-form-urlencoded'}})
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    stockAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + d.message);
                    document.getElementById('stockUser').value = '';
                    document.getElementById('stockPass').value = '';
                    document.getElementById('stockExtra').value = '';
                    stockLoad();
                } else {
                    stockAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + d.message);
                }
            })
            .catch(() => stockAlert('danger', 'Lỗi kết nối server'));
    }

    function stockBulkAdd() {
        const pid = document.getElementById('bulkProduct').value;
        const text = document.getElementById('bulkAccounts').value.trim();
        if (!pid) return stockAlert('danger', 'Chọn sản phẩm');
        if (!text) return stockAlert('danger', 'Nhập danh sách tài khoản');

        const body = new URLSearchParams({action:'bulk_add', product_id:pid, accounts_text:text});
        fetch('../lib/admin_account_stock_modules.php', {method:'POST', body, headers:{'Content-Type':'application/x-www-form-urlencoded'}})
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    stockAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + d.message);
                    document.getElementById('bulkAccounts').value = '';
                    stockLoad();
                } else {
                    stockAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + d.message);
                }
            })
            .catch(() => stockAlert('danger', 'Lỗi kết nối server'));
    }

    function stockLoad() {
        const pid = document.getElementById('stockFilterProduct').value;
        const tbody = document.getElementById('stockBody');
        const statTotal = document.getElementById('statTotal');
        const statAvail = document.getElementById('statAvail');
        const statSold = document.getElementById('statSold');

        if (!pid) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">Chọn sản phẩm để xem</td></tr>';
            statTotal.textContent = '—';
            statAvail.textContent = '—';
            statSold.textContent = '—';
            return;
        }

        // Load stats
        fetch('../lib/admin_account_stock_modules.php?action=stats')
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    statTotal.textContent = d.data.total_accounts;
                    statAvail.textContent = d.data.available_accounts;
                    statSold.textContent = d.data.sold_accounts;
                }
            });

        // Load accounts by product
        fetch('../lib/admin_account_stock_modules.php?action=get_by_product&product_id=' + pid)
            .then(r => r.json())
            .then(d => {
                if (!d.success || !d.data.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">Không có tài khoản nào</td></tr>';
                    return;
                }
                tbody.innerHTML = d.data.map(a => {
                    const isAvail = a.status === 'available';
                    const accInfo = isAvail
                        ? `<code class="font-monospace" style="font-size:0.8rem;">${escapeHtml(a.account_data)}</code>`
                        : `<span class="text-muted small">${escapeHtml(a.account_data)}</span>`;
                    return `<tr>
                        <td class="ps-4 fw-bold text-muted small">#${a.id}</td>
                        <td><span class="nx-badge nx-badge-muted">${a.product_id}</span></td>
                        <td>${accInfo}</td>
                        <td><span class="${isAvail ? 'text-success' : 'text-danger'}">${isAvail ? 'Khả dụng' : 'Đã bán'}</span></td>
                        <td>${new Date(a.created_at).toLocaleDateString('vi-VN')}</td>
                        <td class="pe-4">
                            ${isAvail ? `<button class="nx-btn nx-btn-sm nx-btn-danger" onclick="stockDel(${a.id})"><i class="fa-solid fa-trash"></i></button>` : ''}
                        </td>
                    </tr>`;
                }).join('');
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-danger">Lỗi tải dữ liệu</td></tr>';
            });
    }

    function stockDel(id) {
        if (!confirm('Xóa tài khoản này?')) return;
        const body = new URLSearchParams({action:'delete', account_id:id});
        fetch('../lib/admin_account_stock_modules.php', {method:'POST', body, headers:{'Content-Type':'application/x-www-form-urlencoded'}})
            .then(r => r.json())
            .then(d => {
                if (d.success) { stockAlert('success', '<i class="fa-solid fa-check-circle me-1"></i>' + d.message); stockLoad(); }
                else stockAlert('danger', '<i class="fa-solid fa-xmark-circle me-1"></i>' + d.message);
            })
            .catch(() => stockAlert('danger', 'Lỗi kết nối server'));
    }
</script>

<?php endif; // end stock tab ?>

<?php
$content = ob_get_clean();
admin_renderLayout('Quản lý Sản phẩm', 'products');
?>
