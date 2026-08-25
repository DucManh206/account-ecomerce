<?php
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/../../../includes/flash.php';

$search = trim($_GET['search'] ?? '');
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from'] ?? '') ? $_GET['date_from'] : '';
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to'] ?? '') ? $_GET['date_to'] : '';

if (($_GET['export'] ?? '') === 'csv') {
    $rows = getOrdersForExport($pdo, $search, $dateFrom, $dateTo);
    record_admin_activity($pdo, 'orders_exported', 'order', null, 'Xuất báo cáo đơn hàng CSV', ['rows' => count($rows), 'date_from' => $dateFrom, 'date_to' => $dateTo]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d-His') . '.csv"');
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Mã đơn', 'Thời gian', 'Username', 'Khách hàng', 'Sản phẩm', 'Giá mua']);
    foreach ($rows as $row) {
        $safe = static function ($value) {
            $value = (string) $value;
            return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
        };
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $safe($row['username'] ?? ''),
            $safe($row['user_fullname'] ?? ''),
            $safe($row['account_name'] ?? ''),
            $row['price'],
        ]);
    }
    fclose($output);
    exit;
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = app_setting_int('admin_page_size', 20, 10, 100);
$result = getFilteredOrders($pdo, $search, $dateFrom, $dateTo, $pageSize, ($page - 1) * $pageSize);
$summary = $result['summary'];
$totalPages = max(1, (int) ceil($summary['total'] / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
    $result = getFilteredOrders($pdo, $search, $dateFrom, $dateTo, $pageSize, ($page - 1) * $pageSize);
}
$orders = $result['rows'];
$today = date('Y-m-d');
$last30Days = date('Y-m-d', strtotime('-29 days'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar admin-page-heading">
            <div><span class="eyebrow">Doanh thu & lịch sử bán</span><h1>Đơn hàng</h1><p>Lịch sử giao dịch là dữ liệu tài chính và không thể xóa trực tiếp.</p></div>
            <a class="btn btn-secondary" href="?<?= http_build_query(['search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'export' => 'csv']) ?>">Xuất CSV</a>
        </header>

        <div class="content-body">
            <?= render_flash() ?>
            <section class="stats-grid stats-grid-compact">
                <article class="stat-card"><span class="stat-label">Đơn hàng theo bộ lọc</span><strong class="stat-number"><?= number_format($summary['total']) ?></strong><span class="stat-meta">Dữ liệu đã hoàn tất</span></article>
                <article class="stat-card"><span class="stat-label">Doanh thu</span><strong class="stat-number stat-number-money"><?= number_format($summary['revenue'], 0, ',', '.') ?>đ</strong><span class="stat-meta">Tổng giá bán thực tế</span></article>
                <article class="stat-card"><span class="stat-label">Giá trị trung bình</span><strong class="stat-number stat-number-money"><?= number_format($summary['average'], 0, ',', '.') ?>đ</strong><span class="stat-meta">Mỗi tài khoản đã bán</span></article>
            </section>

            <div class="quick-filter-row">
                <a href="list.php" class="filter-chip-link">Toàn thời gian</a>
                <a href="?<?= http_build_query(['date_from' => $today, 'date_to' => $today]) ?>" class="filter-chip-link">Hôm nay</a>
                <a href="?<?= http_build_query(['date_from' => $last30Days, 'date_to' => $today]) ?>" class="filter-chip-link">30 ngày</a>
            </div>

            <form method="GET" class="toolbar-card">
                <div class="toolbar-search"><label for="search">Tìm đơn hàng</label><input id="search" name="search" type="search" value="<?= htmlspecialchars($search) ?>" placeholder="Mã đơn, khách hàng hoặc sản phẩm"></div>
                <div class="toolbar-field"><label for="date_from">Từ ngày</label><input id="date_from" name="date_from" type="date" value="<?= htmlspecialchars($dateFrom) ?>"></div>
                <div class="toolbar-field"><label for="date_to">Đến ngày</label><input id="date_to" name="date_to" type="date" value="<?= htmlspecialchars($dateTo) ?>"></div>
                <button type="submit" class="btn btn-primary">Áp dụng</button>
                <?php if ($search || $dateFrom || $dateTo): ?><a class="btn btn-secondary" href="list.php">Xóa lọc</a><?php endif; ?>
            </form>

            <section class="table-card">
                <div class="table-card-header"><div><h2><?= number_format($summary['total']) ?> giao dịch</h2><p>Trang <?= $page ?> / <?= $totalPages ?></p></div></div>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Sản phẩm</th><th>Giá mua</th><th>Thời gian</th><th>Liên kết</th></tr></thead>
                        <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?= $order['id'] ?></strong></td>
                                <td><strong><?= htmlspecialchars($order['user_fullname'] ?? 'Tài khoản đã xóa') ?></strong><span class="table-subtext">@<?= htmlspecialchars($order['username'] ?? 'không còn dữ liệu') ?></span></td>
                                <td><?= htmlspecialchars(order_product_name($order)) ?></td>
                                <td class="money-positive"><?= number_format($order['price'], 0, ',', '.') ?>đ</td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                <td class="actions">
                                    <?php if ($order['user_id']): ?><a class="btn btn-small btn-edit" href="../users/update.php?id=<?= $order['user_id'] ?>">Khách hàng</a><?php endif; ?>
                                    <?php if ($order['account_id'] && $order['account_name']): ?><a class="btn btn-small btn-secondary" href="../accounts/update.php?id=<?= $order['account_id'] ?>">Sản phẩm</a><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$orders): ?><tr><td colspan="6" class="empty">Không có đơn hàng phù hợp bộ lọc.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <?php if ($totalPages > 1): ?><nav class="pagination" aria-label="Phân trang"><?php for ($i = 1; $i <= $totalPages; $i++): ?><a class="page-link <?= $i === $page ? 'active' : '' ?>" href="?<?= http_build_query(['search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'page' => $i]) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
