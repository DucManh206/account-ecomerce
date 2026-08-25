<?php
require_once __DIR__ . '/accounts.php';
require_once __DIR__ . '/../../../includes/flash.php';

function inventory_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action'])) {
    verify_csrf(true);
    $action = (string) $_POST['admin_action'];

    try {
        if ($action === 'quick_update') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $priceRaw = trim($_POST['price'] ?? '');
            $categoryId = max(0, (int) ($_POST['category_id'] ?? 0));
            $status = $_POST['status'] ?? '';
            $hidden = ($_POST['hidden'] ?? '0') === '1' ? 1 : 0;

            if ($id <= 0 || $name === '' || mb_strlen($name) > 200) {
                throw new RuntimeException('Tên tài khoản không hợp lệ.');
            }
            if (!is_numeric($priceRaw) || (float) $priceRaw < 0) {
                throw new RuntimeException('Giá bán phải là số không âm.');
            }
            if (!in_array($status, ['available', 'sold'], true)) {
                throw new RuntimeException('Trạng thái tài khoản không hợp lệ.');
            }
            $price = round((float) $priceRaw);

            $pdo->beginTransaction();
            $accountStmt = $pdo->prepare('SELECT id, name FROM accounts WHERE id = ? FOR UPDATE');
            $accountStmt->execute([$id]);
            $currentAccount = $accountStmt->fetch();
            if (!$currentAccount) {
                throw new RuntimeException('Tài khoản không còn tồn tại.');
            }

            if ($categoryId > 0) {
                $categoryStmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
                $categoryStmt->execute([$categoryId]);
                if ((int) $categoryStmt->fetchColumn() === 0) {
                    throw new RuntimeException('Danh mục đã chọn không còn tồn tại.');
                }
            }

            $orderStmt = $pdo->prepare('SELECT MAX(id) FROM orders WHERE account_id = ?');
            $orderStmt->execute([$id]);
            $orderId = (int) ($orderStmt->fetchColumn() ?: 0);
            if ($orderId > 0 && $status === 'available') {
                throw new RuntimeException('Tài khoản đã thuộc đơn #' . $orderId . ' nên không thể đưa trở lại kho.');
            }

            $updateStmt = $pdo->prepare('UPDATE accounts SET name = ?, price = ?, category_id = ?, status = ?, hidden = ? WHERE id = ?');
            $updateStmt->execute([$name, $price, $categoryId ?: null, $status, $hidden, $id]);
            record_admin_activity(
                $pdo,
                'inventory_quick_updated',
                'account',
                $id,
                'Chỉnh sửa nhanh tài khoản #' . $id,
                ['previous_name' => $currentAccount['name'], 'name' => $name, 'price' => $price, 'category_id' => $categoryId ?: null, 'status' => $status, 'hidden' => $hidden]
            );
            $pdo->commit();
            inventory_json_response(['success' => true, 'message' => 'Đã lưu thay đổi cho tài khoản #' . $id . '.']);
        }

        $ids = json_decode($_POST['ids'] ?? '[]', true);
        $ids = array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : []), static fn($id) => $id > 0)));
        if (!$ids || count($ids) > 200) {
            throw new RuntimeException('Danh sách tài khoản không hợp lệ.');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $description = '';
        $metadata = ['ids' => $ids];
        $skipped = 0;
        $pdo->beginTransaction();

        if ($action === 'category') {
            $categoryId = max(0, (int) ($_POST['category_id'] ?? 0));
            if ($categoryId > 0) {
                $categoryStmt = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
                $categoryStmt->execute([$categoryId]);
                $categoryName = $categoryStmt->fetchColumn();
                if ($categoryName === false) {
                    throw new RuntimeException('Danh mục đã chọn không còn tồn tại.');
                }
            } else {
                $categoryName = 'Chưa phân loại';
            }
            $stmt = $pdo->prepare('UPDATE accounts SET category_id = ? WHERE id IN (' . $placeholders . ')');
            $stmt->execute(array_merge([$categoryId ?: null], $ids));
            $description = 'Đổi danh mục hàng loạt sang ' . $categoryName;
            $metadata['category_id'] = $categoryId ?: null;
        } elseif ($action === 'available') {
            $skipStmt = $pdo->prepare('SELECT COUNT(DISTINCT a.id) FROM accounts a JOIN orders o ON o.account_id = a.id WHERE a.id IN (' . $placeholders . ')');
            $skipStmt->execute($ids);
            $skipped = (int) $skipStmt->fetchColumn();
            $stmt = $pdo->prepare("UPDATE accounts a LEFT JOIN orders o ON o.account_id = a.id SET a.status = 'available' WHERE a.id IN (" . $placeholders . ') AND o.id IS NULL');
            $stmt->execute($ids);
            $description = 'Chuyển tài khoản chưa có đơn sang đang bán';
        } else {
            $allowedActions = [
                'show' => ['field' => 'hidden', 'value' => 0, 'description' => 'Hiện tài khoản hàng loạt'],
                'hide' => ['field' => 'hidden', 'value' => 1, 'description' => 'Ẩn tài khoản hàng loạt'],
                'sold' => ['field' => 'status', 'value' => 'sold', 'description' => 'Chuyển tài khoản sang đã bán'],
            ];
            if (!isset($allowedActions[$action])) {
                throw new RuntimeException('Thao tác không được hỗ trợ.');
            }
            $definition = $allowedActions[$action];
            $stmt = $pdo->prepare('UPDATE accounts SET ' . $definition['field'] . ' = ? WHERE id IN (' . $placeholders . ')');
            $stmt->execute(array_merge([$definition['value']], $ids));
            $description = $definition['description'];
        }

        $updated = $stmt->rowCount();
        $metadata['skipped'] = $skipped;
        record_admin_activity($pdo, 'inventory_bulk_updated', 'account', null, $description, $metadata);
        $pdo->commit();

        $message = 'Đã cập nhật ' . $updated . ' tài khoản.';
        if ($skipped > 0) {
            $message .= ' Giữ nguyên ' . $skipped . ' tài khoản đã có đơn hàng.';
        }
        inventory_json_response(['success' => true, 'message' => $message]);
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        inventory_json_response(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('inventory update: ' . $e->getMessage());
        inventory_json_response(['success' => false, 'message' => 'Không thể cập nhật kho tài khoản lúc này.'], 500);
    }
}

$requestedStatus = $_GET['status'] ?? 'all';
$requestedVisibility = $_GET['visibility'] ?? 'all';
$requestedHealth = $_GET['health'] ?? 'all';
$requestedSort = $_GET['sort'] ?? 'newest';
$status = in_array($requestedStatus, ['all', 'available', 'sold'], true) ? $requestedStatus : 'all';
$visibility = in_array($requestedVisibility, ['all', 'visible', 'hidden'], true) ? $requestedVisibility : 'all';
$health = in_array($requestedHealth, ['all', 'missing_details', 'missing_image', 'unclassified'], true) ? $requestedHealth : 'all';
$sort = in_array($requestedSort, ['newest', 'oldest', 'price_desc', 'price_asc', 'name'], true) ? $requestedSort : 'newest';
$category = max(0, (int) ($_GET['category'] ?? 0));
$search = trim($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = app_setting_int('admin_page_size', 20, 10, 100);
$filters = compact('status', 'visibility', 'health', 'sort', 'category', 'search');
$result = getManagedAccounts($pdo, $filters, $pageSize, ($page - 1) * $pageSize);
$totalPages = max(1, (int) ceil($result['total'] / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
    $result = getManagedAccounts($pdo, $filters, $pageSize, ($page - 1) * $pageSize);
}
$accounts = $result['rows'];
$counts = getAccountCounts($pdo);
$categories = getCategories($pdo);
$activeFilterCount = (int) ($search !== '') + (int) ($category > 0) + (int) ($status !== 'all') + (int) ($visibility !== 'all') + (int) ($health !== 'all');
$firstResult = $result['total'] > 0 ? (($page - 1) * $pageSize) + 1 : 0;
$lastResult = min($page * $pageSize, $result['total']);

$inventoryUrl = static function (array $overrides = []) use ($search, $category, $status, $visibility, $health, $sort): string {
    $query = array_merge(['search' => $search, 'category' => $category ?: '', 'status' => $status, 'visibility' => $visibility, 'health' => $health, 'sort' => $sort], $overrides);
    $query = array_filter($query, static fn($value) => $value !== '' && $value !== null);
    return 'list.php?' . http_build_query($query);
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kho tài khoản - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css?v=<?= filemtime(__DIR__ . '/../../../assets/admin/css/admin.css') ?>">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar admin-page-heading inventory-heading">
            <div><span class="eyebrow">Kho hàng số</span><h1>Quản lý tài khoản</h1><p>Kiểm tra chất lượng dữ liệu, cập nhật nhanh và kiểm soát trạng thái giao hàng.</p></div>
            <div class="inventory-heading-actions"><a href="../categories/list.php" class="btn btn-secondary">Danh mục</a><a href="add.php" class="btn btn-primary">Nhập tài khoản</a></div>
        </header>

        <div class="content-body inventory-content">
            <?= render_flash() ?>

            <section class="inventory-overview" aria-label="Tổng quan kho tài khoản">
                <a class="inventory-metric <?= $status === 'all' && $health === 'all' ? 'active' : '' ?>" href="list.php"><span>Tổng kho</span><strong><?= number_format($counts['total']) ?></strong><small><?= number_format($counts['hidden']) ?> đang ẩn</small></a>
                <a class="inventory-metric <?= $status === 'available' ? 'active' : '' ?>" href="<?= htmlspecialchars($inventoryUrl(['status' => 'available', 'health' => 'all', 'page' => null])) ?>"><span>Sẵn sàng bán</span><strong><?= number_format($counts['available']) ?></strong><small><?= number_format($counts['inventory_value'], 0, ',', '.') ?>đ giá trị kho</small></a>
                <a class="inventory-metric <?= $status === 'sold' ? 'active' : '' ?>" href="<?= htmlspecialchars($inventoryUrl(['status' => 'sold', 'health' => 'all', 'page' => null])) ?>"><span>Đã bán</span><strong><?= number_format($counts['sold']) ?></strong><small>Có lịch sử đơn hàng</small></a>
                <a class="inventory-metric inventory-metric-alert <?= $health === 'missing_details' ? 'active' : '' ?>" href="<?= htmlspecialchars($inventoryUrl(['status' => 'available', 'health' => 'missing_details', 'page' => null])) ?>"><span>Cần bổ sung</span><strong><?= number_format($counts['missing_details']) ?></strong><small>Thiếu thông tin giao</small></a>
            </section>

            <form method="GET" class="inventory-command-bar" id="inventoryFilters">
                <div class="inventory-search-row">
                    <label class="inventory-search-box" for="search"><span>Tìm tài khoản</span><input type="search" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tên, mô tả, danh mục hoặc ID"></label>
                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    <?php if ($activeFilterCount > 0): ?><a class="btn btn-secondary" href="list.php">Xóa <?= $activeFilterCount ?> bộ lọc</a><?php endif; ?>
                </div>
                <div class="inventory-filter-row">
                    <label><span>Danh mục</span><select name="category" data-auto-submit><option value="0">Tất cả danh mục</option><?php foreach ($categories as $item): ?><option value="<?= $item['id'] ?>" <?= $category === (int) $item['id'] ? 'selected' : '' ?>><?= htmlspecialchars($item['name']) ?></option><?php endforeach; ?></select></label>
                    <label><span>Trạng thái</span><select name="status" data-auto-submit><option value="all">Tất cả trạng thái</option><option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Đang bán</option><option value="sold" <?= $status === 'sold' ? 'selected' : '' ?>>Đã bán</option></select></label>
                    <label><span>Hiển thị</span><select name="visibility" data-auto-submit><option value="all">Tất cả hiển thị</option><option value="visible" <?= $visibility === 'visible' ? 'selected' : '' ?>>Đang hiện</option><option value="hidden" <?= $visibility === 'hidden' ? 'selected' : '' ?>>Đang ẩn</option></select></label>
                    <label><span>Chất lượng dữ liệu</span><select name="health" data-auto-submit><option value="all">Tất cả dữ liệu</option><option value="missing_details" <?= $health === 'missing_details' ? 'selected' : '' ?>>Thiếu thông tin giao</option><option value="missing_image" <?= $health === 'missing_image' ? 'selected' : '' ?>>Thiếu hình ảnh</option><option value="unclassified" <?= $health === 'unclassified' ? 'selected' : '' ?>>Chưa phân loại</option></select></label>
                    <label><span>Sắp xếp</span><select name="sort" data-auto-submit><option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới cập nhật</option><option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option><option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá cao trước</option><option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá thấp trước</option><option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Tên A–Z</option></select></label>
                </div>
            </form>

            <div class="bulk-action-bar" id="bulkBar" hidden>
                <div><span class="bulk-kicker">Đang chọn</span><strong><span id="selectedCount">0</span> tài khoản</strong></div>
                <div class="bulk-actions">
                    <button type="button" class="btn btn-small btn-secondary" data-bulk-action="show">Hiện</button><button type="button" class="btn btn-small btn-secondary" data-bulk-action="hide">Ẩn</button><button type="button" class="btn btn-small btn-success" data-bulk-action="available">Đang bán</button><button type="button" class="btn btn-small btn-delete" data-bulk-action="sold">Đã bán</button>
                    <div class="bulk-category-control"><select id="bulkCategory" aria-label="Danh mục mới"><option value="">Đổi danh mục…</option><option value="0">Chưa phân loại</option><?php foreach ($categories as $item): ?><option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option><?php endforeach; ?></select><button type="button" class="btn btn-small btn-secondary" id="applyBulkCategory">Áp dụng</button></div>
                    <button type="button" class="btn btn-small btn-text" id="clearSelection">Bỏ chọn</button>
                </div>
            </div>
            <div id="actionFeedback" class="alert" role="status" aria-live="polite" hidden></div>

            <section class="table-card inventory-table-card">
                <div class="table-card-header inventory-table-header"><div><h2><?= number_format($result['total']) ?> tài khoản</h2><p>Hiển thị <?= number_format($firstResult) ?>–<?= number_format($lastResult) ?> · trang <?= $page ?>/<?= $totalPages ?></p></div><div class="inventory-legend"><span class="quality-dot"></span> Nhãn đỏ là dữ liệu cần bổ sung</div></div>
                <div class="table-scroll">
                    <table class="data-table inventory-table">
                        <thead><tr><th><input type="checkbox" id="selectAll" aria-label="Chọn tất cả trên trang"></th><th>Tài khoản</th><th>Danh mục</th><th>Giá bán</th><th>Trạng thái</th><th>Hiển thị</th><th>Thao tác</th></tr></thead>
                        <tbody>
                        <?php foreach ($accounts as $account): ?>
                            <?php $missingDetails = $account['status'] === 'available' && trim((string) ($account['account_detail'] ?? '')) === ''; $initial = mb_strtoupper(mb_substr($account['name'], 0, 1, 'UTF-8'), 'UTF-8'); ?>
                            <tr class="<?= $account['hidden'] ? 'row-muted' : '' ?> <?= $missingDetails ? 'row-needs-attention' : '' ?>" data-row-id="<?= $account['id'] ?>">
                                <td><input type="checkbox" class="row-checkbox" value="<?= $account['id'] ?>" aria-label="Chọn <?= htmlspecialchars($account['name']) ?>"></td>
                                <td><div class="inventory-product-cell"><?php if (trim((string) ($account['image'] ?? '')) !== ''): ?><img class="inventory-thumb" src="<?= htmlspecialchars($account['image']) ?>" alt="" loading="lazy"><?php else: ?><span class="inventory-thumb inventory-thumb-fallback"><?= htmlspecialchars($initial) ?></span><?php endif; ?><div class="inventory-product-copy"><strong><?= htmlspecialchars($account['name']) ?></strong><span class="table-subtext">#<?= $account['id'] ?> · cập nhật <?= date('d/m', strtotime($account['updated_at'] ?? $account['created_at'])) ?> · <?= htmlspecialchars(mb_strimwidth(trim((string) ($account['description'] ?? '')), 0, 58, '…', 'UTF-8') ?: 'Chưa có mô tả') ?></span><?php if ($missingDetails): ?><span class="quality-issue">Thiếu thông tin giao</span><?php endif; ?></div></div></td>
                                <td><a class="table-filter-link" href="<?= htmlspecialchars($inventoryUrl(['category' => $account['category_id'] ?: '', 'page' => null])) ?>"><?= htmlspecialchars($account['category_name'] ?? 'Chưa phân loại') ?></a></td>
                                <td class="money-positive"><?= number_format($account['price'], 0, ',', '.') ?>đ</td>
                                <td><span class="badge <?= $account['status'] === 'available' ? 'badge-completed' : 'badge-rejected' ?>"><?= $account['status'] === 'available' ? 'Đang bán' : 'Đã bán' ?></span><?php if ($account['order_id']): ?><a class="table-subtext table-order-link" href="../orders/list.php?search=<?= $account['order_id'] ?>">Đơn #<?= $account['order_id'] ?></a><?php endif; ?></td>
                                <td><button type="button" class="visibility-toggle <?= $account['hidden'] ? '' : 'active' ?>" data-id="<?= $account['id'] ?>" data-hidden="<?= $account['hidden'] ?>" aria-pressed="<?= $account['hidden'] ? 'false' : 'true' ?>"><span></span><?= $account['hidden'] ? 'Đang ẩn' : 'Đang hiện' ?></button></td>
                                <td class="actions inventory-row-actions"><button type="button" class="btn btn-small btn-edit quick-edit-button" aria-label="Sửa nhanh <?= htmlspecialchars($account['name']) ?>" data-id="<?= $account['id'] ?>" data-name="<?= htmlspecialchars($account['name'], ENT_QUOTES) ?>" data-price="<?= (float) $account['price'] ?>" data-category="<?= (int) ($account['category_id'] ?? 0) ?>" data-status="<?= htmlspecialchars($account['status']) ?>" data-hidden="<?= (int) $account['hidden'] ?>" data-has-order="<?= $account['order_id'] ? '1' : '0' ?>">Sửa</button><a href="update.php?id=<?= $account['id'] ?>" class="btn btn-small btn-text">Chi tiết</a><?php if (!$account['order_id']): ?><form method="POST" action="delete.php" class="inline-form" onsubmit="return confirm('Xóa vĩnh viễn tài khoản chưa bán này?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $account['id'] ?>"><button type="submit" class="btn btn-small btn-text btn-text-danger">Xóa</button></form><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$accounts): ?><tr><td colspan="7"><div class="inventory-empty"><strong>Không có tài khoản phù hợp</strong><span>Thử xóa bớt bộ lọc hoặc nhập thêm tài khoản mới.</span><a class="btn btn-secondary" href="list.php">Xem toàn bộ kho</a></div></td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <?php if ($totalPages > 1): ?><nav class="pagination" aria-label="Phân trang"><?php for ($i = 1; $i <= $totalPages; $i++): ?><a class="page-link <?= $i === $page ? 'active' : '' ?>" href="<?= htmlspecialchars($inventoryUrl(['page' => $i])) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
        </div>
    </main>
</div>

<dialog class="inventory-drawer" id="quickEditDrawer" aria-labelledby="quickEditTitle">
    <div class="inventory-drawer-shell">
        <header class="inventory-drawer-header"><div><span class="eyebrow">Chỉnh sửa tại chỗ</span><h2 id="quickEditTitle">Tài khoản <span id="quickEditId"></span></h2><p>Cập nhật các trường vận hành thường dùng mà không rời danh sách.</p></div><button type="button" class="drawer-close" id="closeQuickEdit" aria-label="Đóng">×</button></header>
        <form id="quickEditForm" class="inventory-quick-form">
            <input type="hidden" id="quickId" name="id">
            <label><span>Tên tài khoản</span><input type="text" id="quickName" name="name" maxlength="200" required></label>
            <div class="form-row"><label><span>Giá bán (đ)</span><input type="number" id="quickPrice" name="price" min="0" step="1000" required></label><label><span>Danh mục</span><select id="quickCategory" name="category_id"><option value="0">Chưa phân loại</option><?php foreach ($categories as $item): ?><option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option><?php endforeach; ?></select></label></div>
            <div class="form-row"><label><span>Trạng thái</span><select id="quickStatus" name="status"><option value="available">Đang bán</option><option value="sold">Đã bán</option></select></label><label><span>Hiển thị</span><select id="quickHidden" name="hidden"><option value="0">Đang hiện</option><option value="1">Đang ẩn</option></select></label></div>
            <p class="drawer-note" id="orderLockNote" hidden>Tài khoản đã có đơn hàng nên không thể chuyển lại trạng thái “Đang bán”.</p>
            <div id="quickEditFeedback" class="alert" role="status" aria-live="polite" hidden></div>
            <footer class="inventory-drawer-actions"><a class="btn btn-secondary" id="fullEditLink" href="update.php">Sửa đầy đủ</a><button class="btn btn-primary" id="saveQuickEdit" type="submit">Lưu thay đổi</button></footer>
        </form>
    </div>
</dialog>

<script>
const csrfToken = <?= json_encode(csrf_token()) ?>;
const checkboxes = [...document.querySelectorAll('.row-checkbox')];
const bulkBar = document.getElementById('bulkBar');
const selectedCount = document.getElementById('selectedCount');
const actionFeedback = document.getElementById('actionFeedback');

function showFeedback(target, message, type = 'error') {
    target.textContent = message;
    target.classList.remove('alert-error', 'alert-success');
    target.classList.add(type === 'success' ? 'alert-success' : 'alert-error');
    target.hidden = false;
}
function selectedIds() { return checkboxes.filter(item => item.checked).map(item => Number(item.value)); }
function refreshSelection() {
    const ids = selectedIds();
    selectedCount.textContent = String(ids.length);
    bulkBar.hidden = ids.length === 0;
    const selectAll = document.getElementById('selectAll');
    selectAll.checked = ids.length > 0 && ids.length === checkboxes.length;
    selectAll.indeterminate = ids.length > 0 && ids.length < checkboxes.length;
    document.querySelectorAll('tr[data-row-id]').forEach(row => row.classList.toggle('row-selected', Boolean(row.querySelector('.row-checkbox')?.checked)));
}
async function requestInventoryAction(payload) {
    const response = await fetch('list.php', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: new URLSearchParams({...payload, csrf_token: csrfToken})});
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Không thể cập nhật kho tài khoản.');
    return data;
}
async function applyBulkAction(action, extra = {}) {
    const ids = selectedIds();
    if (ids.length === 0) { showFeedback(actionFeedback, 'Hãy chọn ít nhất một tài khoản.'); return; }
    const data = await requestInventoryAction({admin_action: action, ids: JSON.stringify(ids), ...extra});
    sessionStorage.setItem('inventory_feedback', data.message);
    window.location.reload();
}

const storedFeedback = sessionStorage.getItem('inventory_feedback');
if (storedFeedback) { sessionStorage.removeItem('inventory_feedback'); showFeedback(actionFeedback, storedFeedback, 'success'); }
checkboxes.forEach(item => item.addEventListener('change', refreshSelection));
document.getElementById('selectAll').addEventListener('change', event => { checkboxes.forEach(item => item.checked = event.target.checked); refreshSelection(); });
document.getElementById('clearSelection').addEventListener('click', () => { checkboxes.forEach(item => item.checked = false); refreshSelection(); });
document.querySelectorAll('[data-bulk-action]').forEach(button => button.addEventListener('click', async () => { try { button.disabled = true; await applyBulkAction(button.dataset.bulkAction); } catch (error) { showFeedback(actionFeedback, error.message); button.disabled = false; } }));
document.getElementById('applyBulkCategory').addEventListener('click', async event => {
    const category = document.getElementById('bulkCategory').value;
    if (category === '') { showFeedback(actionFeedback, 'Hãy chọn danh mục mới.'); return; }
    try { event.currentTarget.disabled = true; await applyBulkAction('category', {category_id: category}); } catch (error) { showFeedback(actionFeedback, error.message); event.currentTarget.disabled = false; }
});
document.querySelectorAll('.visibility-toggle').forEach(button => button.addEventListener('click', async () => {
    try {
        button.disabled = true;
        const data = await requestInventoryAction({admin_action: button.dataset.hidden === '1' ? 'show' : 'hide', ids: JSON.stringify([Number(button.dataset.id)])});
        sessionStorage.setItem('inventory_feedback', data.message);
        window.location.reload();
    } catch (error) { showFeedback(actionFeedback, error.message); button.disabled = false; }
}));
document.querySelectorAll('[data-auto-submit]').forEach(select => select.addEventListener('change', () => document.getElementById('inventoryFilters').requestSubmit()));

const drawer = document.getElementById('quickEditDrawer');
const quickForm = document.getElementById('quickEditForm');
const quickStatus = document.getElementById('quickStatus');
const quickFeedback = document.getElementById('quickEditFeedback');
const orderLockNote = document.getElementById('orderLockNote');
document.querySelectorAll('.quick-edit-button').forEach(button => button.addEventListener('click', () => {
    document.getElementById('quickId').value = button.dataset.id;
    document.getElementById('quickEditId').textContent = '#' + button.dataset.id;
    document.getElementById('quickName').value = button.dataset.name;
    document.getElementById('quickPrice').value = button.dataset.price;
    document.getElementById('quickCategory').value = button.dataset.category;
    quickStatus.value = button.dataset.status;
    document.getElementById('quickHidden').value = button.dataset.hidden;
    document.getElementById('fullEditLink').href = 'update.php?id=' + encodeURIComponent(button.dataset.id);
    const hasOrder = button.dataset.hasOrder === '1';
    quickStatus.querySelector('option[value="available"]').disabled = hasOrder;
    orderLockNote.hidden = !hasOrder;
    quickFeedback.hidden = true;
    drawer.showModal();
    document.getElementById('quickName').focus();
}));
document.getElementById('closeQuickEdit').addEventListener('click', () => drawer.close());
drawer.addEventListener('click', event => { if (event.target === drawer) drawer.close(); });
quickForm.addEventListener('submit', async event => {
    event.preventDefault();
    const saveButton = document.getElementById('saveQuickEdit');
    try {
        saveButton.disabled = true; saveButton.textContent = 'Đang lưu…';
        const data = await requestInventoryAction({admin_action: 'quick_update', id: document.getElementById('quickId').value, name: document.getElementById('quickName').value, price: document.getElementById('quickPrice').value, category_id: document.getElementById('quickCategory').value, status: quickStatus.value, hidden: document.getElementById('quickHidden').value});
        sessionStorage.setItem('inventory_feedback', data.message);
        window.location.reload();
    } catch (error) { showFeedback(quickFeedback, error.message); saveButton.disabled = false; saveButton.textContent = 'Lưu thay đổi'; }
});
</script>
</body>
</html>
