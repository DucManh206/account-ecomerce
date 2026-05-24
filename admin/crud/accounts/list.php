<?php
require_once __DIR__ . '/accounts.php';

// Handle AJAX toggle hidden (single)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_hidden' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $newState = toggleHidden($pdo, (int)$_GET['id']);
    echo json_encode(['success' => true, 'hidden' => (int)$newState]);
    exit;
}

// Handle AJAX bulk hide/show
if (isset($_POST['action']) && in_array($_POST['action'], ['bulk_hide', 'bulk_show'])) {
    header('Content-Type: application/json');
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (is_array($ids) && count($ids) > 0) {
        $newHidden = $_POST['action'] === 'bulk_hide' ? 1 : 0;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE accounts SET hidden = ? WHERE id IN ($placeholders)");
        $stmt->execute(array_merge([$newHidden], array_map('intval', $ids)));
        echo json_encode(['success' => true, 'hidden' => $newHidden, 'ids' => array_map('intval', $ids)]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No IDs provided']);
    }
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$accounts = getFilteredAccounts($pdo, $statusFilter);
$counts = getAccountCounts($pdo);
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
    <style>
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
            width: fit-content;
        }
        .filter-tab {
            padding: 10px 22px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
            background: transparent;
            border-right: 1px solid var(--border-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-tab:last-child {
            border-right: none;
        }
        .filter-tab:hover {
            color: var(--text-main);
            background: rgba(255,255,255,0.03);
        }
        .filter-tab.active {
            color: #0a0a0a;
            background: #ffffff;
        }
        .filter-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            font-size: 0.75rem;
            font-weight: 700;
            border: 1px solid var(--border-color);
            background: rgba(255,255,255,0.05);
            color: var(--text-muted);
        }
        .filter-tab.active .filter-count {
            background: rgba(0,0,0,0.1);
            border-color: rgba(0,0,0,0.2);
            color: #0a0a0a;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 40px;
            height: 22px;
            cursor: pointer;
            display: inline-block;
            vertical-align: middle;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: #333;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            left: 2px;
            top: 50%;
            transform: translateY(-50%);
            background: #666;
            transition: var(--transition);
        }
        .toggle-switch input:checked + .toggle-slider {
            background: var(--success);
            border-color: var(--success);
        }
        .toggle-switch input:checked + .toggle-slider::before {
            left: 20px;
            background: #ffffff;
        }
        .toggle-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: var(--text-muted);
            cursor: pointer;
        }
        .toggle-label .label-text {
            transition: var(--transition);
        }
        .toggle-label.is-visible .label-text {
            color: var(--success);
        }
        .toggle-label.is-hidden .label-text {
            color: var(--danger);
        }

        /* Row hidden state */
        tr.row-hidden {
            opacity: 0.45;
        }
        tr.row-hidden:hover {
            opacity: 0.7;
        }

        /* Badge for hidden */
        .badge-hidden {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-color: var(--warning);
            font-size: 0.7rem;
            margin-left: 6px;
        }

        /* Checkbox styling */
        .row-checkbox {
            width: 16px;
            height: 16px;
            accent-color: #ffffff;
            cursor: pointer;
            vertical-align: middle;
        }

        /* Bulk action bar */
        .bulk-bar {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: #1a1a1a;
            border: 1px solid var(--border-color);
            margin-bottom: 16px;
            animation: slideDown 0.2s ease;
        }
        .bulk-bar.show {
            display: flex;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .bulk-bar .bulk-info {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            margin-right: auto;
        }
        .bulk-bar .bulk-info span {
            color: #ffffff;
            font-weight: 800;
        }
        .btn-bulk-hide {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-color: var(--warning);
            padding: 8px 16px;
            font-size: 0.8rem;
        }
        .btn-bulk-hide:hover {
            background: var(--warning);
            color: #0a0a0a;
        }
        .btn-bulk-show {
            background: var(--success-light);
            color: var(--success);
            border-color: var(--success);
            padding: 8px 16px;
            font-size: 0.8rem;
        }
        .btn-bulk-show:hover {
            background: var(--success);
            color: #ffffff;
        }
        .btn-bulk-cancel {
            background: transparent;
            color: var(--text-muted);
            border-color: var(--border-color);
            padding: 8px 16px;
            font-size: 0.8rem;
        }
        .btn-bulk-cancel:hover {
            color: var(--text-main);
            border-color: var(--text-main);
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <h1>Quản lý tài khoản</h1>
            <a href="add.php" class="btn btn-primary">+ Thêm tài khoản</a>
        </header>
        
        <div class="content-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="list.php?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">
                    Tất cả <span class="filter-count"><?= $counts['total'] ?></span>
                </a>
                <a href="list.php?status=available" class="filter-tab <?= $statusFilter === 'available' ? 'active' : '' ?>">
                    Đang bán <span class="filter-count"><?= $counts['available'] ?></span>
                </a>
                <a href="list.php?status=sold" class="filter-tab <?= $statusFilter === 'sold' ? 'active' : '' ?>">
                    Đã bán <span class="filter-count"><?= $counts['sold'] ?></span>
                </a>
            </div>
            
            <!-- Bulk Action Bar -->
            <div class="bulk-bar" id="bulkBar">
                <div class="bulk-info">Đã chọn <span id="selectedCount">0</span> sản phẩm</div>
                <button class="btn btn-bulk-show" onclick="bulkAction('bulk_show')">Hiện hàng loạt</button>
                <button class="btn btn-bulk-hide" onclick="bulkAction('bulk_hide')">Ẩn hàng loạt</button>
                <button class="btn btn-bulk-cancel" onclick="clearSelection()">Bỏ chọn</button>
            </div>

            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" class="row-checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                            <th>ID</th>
                            <th>Tên tài khoản</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th>Trạng thái</th>
                            <th>Hiển thị</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($accounts as $acc): ?>
                        <tr class="<?= $acc['hidden'] ? 'row-hidden' : '' ?>" id="row-<?= $acc['id'] ?>">
                            <td><input type="checkbox" class="row-checkbox item-checkbox" value="<?= $acc['id'] ?>" onchange="updateBulkBar()"></td>
                            <td><?= $acc['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($acc['name']) ?></strong>
                                <?php if ($acc['hidden']): ?>
                                    <span class="badge badge-hidden" id="badge-hidden-<?= $acc['id'] ?>">ẨN</span>
                                <?php else: ?>
                                    <span class="badge badge-hidden" id="badge-hidden-<?= $acc['id'] ?>" style="display:none;">ẨN</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($acc['category_name'] ?? 'Chưa phân loại') ?></td>
                            <td><?= number_format($acc['price'], 0, ',', '.') ?>đ</td>
                            <td>
                                <span class="badge <?= $acc['status'] === 'available' ? 'badge-green' : 'badge-red' ?>">
                                    <?= $acc['status'] === 'available' ? 'Đang bán' : 'Đã bán' ?>
                                </span>
                            </td>
                            <td>
                                <label class="toggle-label <?= $acc['hidden'] ? 'is-hidden' : 'is-visible' ?>" id="toggle-label-<?= $acc['id'] ?>">
                                    <span class="toggle-switch">
                                        <input type="checkbox"
                                               <?= !$acc['hidden'] ? 'checked' : '' ?>
                                               onchange="toggleVisibility(<?= $acc['id'] ?>, this)"
                                               id="toggle-<?= $acc['id'] ?>">
                                        <span class="toggle-slider"></span>
                                    </span>
                                    <span class="label-text"><?= $acc['hidden'] ? 'Đang ẩn' : 'Hiển thị' ?></span>
                                </label>
                            </td>
                            <td><?= date('d/m/Y', strtotime($acc['created_at'])) ?></td>
                            <td class="actions">
                                <a href="update.php?id=<?= $acc['id'] ?>" class="btn btn-small btn-edit">Sửa</a>
                                <a href="delete.php?id=<?= $acc['id'] ?>" class="btn btn-small btn-delete" onclick="return confirm('Bạn chắc chắn muốn xóa tài khoản này?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$accounts): ?>
                        <tr>
                            <td colspan="9" class="empty">Chưa có tài khoản nào.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
function toggleVisibility(id, checkbox) {
    fetch('list.php?action=toggle_hidden&id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateRowUI(id, data.hidden);
            }
        })
        .catch(() => {
            checkbox.checked = !checkbox.checked;
        });
}

function updateRowUI(id, hidden) {
    const row = document.getElementById('row-' + id);
    const badge = document.getElementById('badge-hidden-' + id);
    const label = document.getElementById('toggle-label-' + id);
    const toggle = document.getElementById('toggle-' + id);
    if (!row) return;
    const labelText = label ? label.querySelector('.label-text') : null;

    if (hidden) {
        row.classList.add('row-hidden');
        if (badge) badge.style.display = '';
        if (label) { label.classList.remove('is-visible'); label.classList.add('is-hidden'); }
        if (labelText) labelText.textContent = 'Đang ẩn';
        if (toggle) toggle.checked = false;
    } else {
        row.classList.remove('row-hidden');
        if (badge) badge.style.display = 'none';
        if (label) { label.classList.remove('is-hidden'); label.classList.add('is-visible'); }
        if (labelText) labelText.textContent = 'Hiển thị';
        if (toggle) toggle.checked = true;
    }
}

// Bulk selection
function toggleSelectAll(master) {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = master.checked;
    });
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    const bar = document.getElementById('bulkBar');
    const countEl = document.getElementById('selectedCount');
    const allCbs = document.querySelectorAll('.item-checkbox');
    const selectAll = document.getElementById('selectAll');

    countEl.textContent = checked.length;
    if (checked.length > 0) {
        bar.classList.add('show');
    } else {
        bar.classList.remove('show');
    }
    selectAll.checked = allCbs.length > 0 && checked.length === allCbs.length;
}

function clearSelection() {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkBar();
}

function bulkAction(action) {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    const ids = Array.from(checked).map(cb => parseInt(cb.value));
    if (ids.length === 0) return;

    const label = action === 'bulk_hide' ? 'ẩn' : 'hiện';
    if (!confirm('Bạn muốn ' + label + ' ' + ids.length + ' sản phẩm đã chọn?')) return;

    const formData = new FormData();
    formData.append('action', action);
    formData.append('ids', JSON.stringify(ids));

    fetch('list.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                data.ids.forEach(id => updateRowUI(id, data.hidden));
                clearSelection();
            }
        })
        .catch(err => alert('Lỗi: ' + err.message));
}
</script>
</body>
</html>
