<?php
require_once __DIR__ . '/config/db.php';
require_admin();

$requestedPeriod = $_GET['period'] ?? '30';
$period = in_array($requestedPeriod, ['7', '30', '90', 'custom'], true) ? $requestedPeriod : '30';
$today = new DateTimeImmutable('today');
if ($period === 'custom') {
    $fromValue = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from'] ?? '') ? $_GET['date_from'] : $today->modify('-29 days')->format('Y-m-d');
    $toValue = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to'] ?? '') ? $_GET['date_to'] : $today->format('Y-m-d');
    $fromDate = new DateTimeImmutable($fromValue);
    $toDate = new DateTimeImmutable($toValue);
    if ($fromDate > $toDate) {
        [$fromDate, $toDate] = [$toDate, $fromDate];
    }
    if ($fromDate->diff($toDate)->days > 365) {
        $fromDate = $toDate->modify('-365 days');
    }
} else {
    $days = (int) $period;
    $toDate = $today;
    $fromDate = $today->modify('-' . ($days - 1) . ' days');
}

$from = $fromDate->format('Y-m-d 00:00:00');
$to = $toDate->format('Y-m-d 23:59:59');
$dayCount = $fromDate->diff($toDate)->days + 1;
$previousToDate = $fromDate->modify('-1 day');
$previousFromDate = $previousToDate->modify('-' . ($dayCount - 1) . ' days');
$previousFrom = $previousFromDate->format('Y-m-d 00:00:00');
$previousTo = $previousToDate->format('Y-m-d 23:59:59');

function fetch_order_metrics(PDO $pdo, string $from, string $to): array
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS orders, COALESCE(SUM(price), 0) AS revenue, COALESCE(AVG(price), 0) AS average_order
         FROM orders WHERE created_at BETWEEN ? AND ?'
    );
    $stmt->execute([$from, $to]);
    return $stmt->fetch();
}

function percent_change(float $current, float $previous): ?float
{
    if ($previous == 0.0) {
        return $current == 0.0 ? 0.0 : null;
    }
    return (($current - $previous) / $previous) * 100;
}

$metrics = fetch_order_metrics($pdo, $from, $to);
$previousMetrics = fetch_order_metrics($pdo, $previousFrom, $previousTo);
$revenueChange = percent_change((float) $metrics['revenue'], (float) $previousMetrics['revenue']);
$orderChange = percent_change((float) $metrics['orders'], (float) $previousMetrics['orders']);

$depositStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM balance_transactions WHERE transaction_type = 'deposit' AND created_at BETWEEN ? AND ?");
$depositStmt->execute([$from, $to]);
$periodDeposits = (float) $depositStmt->fetchColumn();

$userStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND created_at BETWEEN ? AND ?");
$userStmt->execute([$from, $to]);
$newUsers = (int) $userStmt->fetchColumn();

$inventory = $pdo->query(
    "SELECT COUNT(*) AS total,
            SUM(status = 'available') AS available,
            SUM(status = 'sold') AS sold,
            SUM(hidden = 1) AS hidden
     FROM accounts"
)->fetch();
$pendingTopups = (int) $pdo->query("SELECT COUNT(*) FROM topup_requests WHERE status = 'pending'")->fetchColumn();

$dailyStmt = $pdo->prepare(
    'SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(price), 0) AS revenue
     FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY day'
);
$dailyStmt->execute([$from, $to]);
$dailyMap = [];
foreach ($dailyStmt->fetchAll() as $row) {
    $dailyMap[$row['day']] = $row;
}
$chart = [];
$cursor = $fromDate;
while ($cursor <= $toDate) {
    $key = $cursor->format('Y-m-d');
    $chart[] = [
        'day' => $key,
        'label' => $cursor->format('d/m'),
        'orders' => (int) ($dailyMap[$key]['orders'] ?? 0),
        'revenue' => (float) ($dailyMap[$key]['revenue'] ?? 0),
    ];
    $cursor = $cursor->modify('+1 day');
}
$maxChartRevenue = max(array_merge([1], array_column($chart, 'revenue')));
$chartStep = count($chart) > 31 ? 7 : (count($chart) > 14 ? 3 : 1);

$recentOrders = $pdo->query(
    'SELECT o.*, u.username, COALESCE(o.product_name, a.name) AS account_name
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     LEFT JOIN accounts a ON a.id = o.account_id
     ORDER BY o.id DESC LIMIT 6'
)->fetchAll();

$activity = $pdo->query(
    'SELECT l.*, u.fullname AS admin_name
     FROM admin_activity_logs l
     LEFT JOIN users u ON u.id = l.admin_id
     ORDER BY l.id DESC LIMIT 8'
)->fetchAll();

$lowStockThreshold = app_setting_int('low_stock_threshold', 3, 1, 100);
$categoryStock = $pdo->query(
    "SELECT c.id, c.name, COUNT(a.id) AS total_count,
            SUM(CASE WHEN a.status = 'available' THEN 1 ELSE 0 END) AS available_count
     FROM categories c LEFT JOIN accounts a ON a.category_id = c.id
     GROUP BY c.id, c.name ORDER BY available_count ASC, c.name"
)->fetchAll();

$customerStmt = $pdo->prepare(
    'SELECT u.id, u.username, u.fullname, COUNT(o.id) AS orders, SUM(o.price) AS spent
     FROM orders o JOIN users u ON u.id = o.user_id
     WHERE o.created_at BETWEEN ? AND ?
     GROUP BY u.id, u.username, u.fullname ORDER BY spent DESC LIMIT 5'
);
$customerStmt->execute([$from, $to]);
$topCustomers = $customerStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tổng quan vận hành - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar admin-page-heading dashboard-heading">
            <div>
                <span class="eyebrow">Tổng quan vận hành</span>
                <h1>Chào <?= htmlspecialchars($_SESSION['admin_fullname'] ?? 'quản trị viên') ?></h1>
                <p>Dữ liệu từ <?= $fromDate->format('d/m/Y') ?> đến <?= $toDate->format('d/m/Y') ?>, so với giai đoạn liền trước.</p>
            </div>
            <div class="dashboard-actions">
                <?php if ($pendingTopups > 0): ?><a class="btn btn-warning" href="crud/topups/list.php?status=pending"><?= $pendingTopups ?> yêu cầu cần xử lý</a><?php endif; ?>
                <a class="btn btn-primary" href="crud/accounts/add.php">Nhập kho</a>
            </div>
        </header>

        <div class="content-body">
            <form method="GET" class="period-toolbar">
                <div class="period-tabs">
                    <?php foreach (['7' => '7 ngày', '30' => '30 ngày', '90' => '90 ngày'] as $value => $label): ?>
                        <a class="period-tab <?= $period === $value ? 'active' : '' ?>" href="?period=<?= $value ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="period" value="custom">
                <label>Từ ngày <input type="date" name="date_from" value="<?= $fromDate->format('Y-m-d') ?>"></label>
                <label>Đến ngày <input type="date" name="date_to" value="<?= $toDate->format('Y-m-d') ?>"></label>
                <button class="btn btn-small btn-secondary" type="submit">Áp dụng</button>
            </form>

            <section class="kpi-grid">
                <article class="kpi-card kpi-card-featured">
                    <div class="kpi-card-head"><span>Doanh thu</span><span class="trend <?= $revenueChange !== null && $revenueChange < 0 ? 'down' : 'up' ?>"><?= $revenueChange === null ? 'Kỳ trước chưa có dữ liệu' : ($revenueChange >= 0 ? '+' : '') . number_format($revenueChange, 1) . '%' ?></span></div>
                    <strong><?= number_format($metrics['revenue'], 0, ',', '.') ?>đ</strong>
                    <p><?= number_format($metrics['orders']) ?> đơn đã hoàn tất</p>
                </article>
                <article class="kpi-card"><div class="kpi-card-head"><span>Đơn hàng</span><span class="trend <?= $orderChange !== null && $orderChange < 0 ? 'down' : 'up' ?>"><?= $orderChange === null ? 'Mới' : ($orderChange >= 0 ? '+' : '') . number_format($orderChange, 1) . '%' ?></span></div><strong><?= number_format($metrics['orders']) ?></strong><p>Trung bình <?= number_format($metrics['average_order'], 0, ',', '.') ?>đ / đơn</p></article>
                <article class="kpi-card"><div class="kpi-card-head"><span>Tiền nạp</span></div><strong><?= number_format($periodDeposits, 0, ',', '.') ?>đ</strong><p>Ghi nhận trong sổ số dư</p></article>
                <article class="kpi-card"><div class="kpi-card-head"><span>Khách mới</span></div><strong><?= number_format($newUsers) ?></strong><p><?= number_format($inventory['available']) ?> sản phẩm đang bán</p></article>
            </section>

            <section class="dashboard-grid dashboard-grid-primary">
                <article class="panel-card chart-panel">
                    <div class="panel-heading"><div><span class="eyebrow">Xu hướng bán hàng</span><h2>Doanh thu theo ngày</h2></div><strong><?= number_format($metrics['revenue'], 0, ',', '.') ?>đ</strong></div>
                    <div class="bar-chart" role="img" aria-label="Biểu đồ doanh thu theo ngày">
                        <?php foreach ($chart as $index => $point): $height = max(3, ($point['revenue'] / $maxChartRevenue) * 100); ?>
                            <div class="bar-column" title="<?= $point['label'] ?>: <?= number_format($point['revenue'], 0, ',', '.') ?>đ · <?= $point['orders'] ?> đơn">
                                <div class="bar-value" style="height: <?= number_format($height, 2, '.', '') ?>%"></div>
                                <?php if ($index % $chartStep === 0 || $index === count($chart) - 1): ?><span><?= $point['label'] ?></span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="panel-card">
                    <div class="panel-heading"><div><span class="eyebrow">Kho hàng</span><h2>Tồn theo danh mục</h2></div><a href="crud/accounts/list.php?status=available">Xem kho</a></div>
                    <div class="stock-list">
                        <?php foreach ($categoryStock as $item): $available = (int) $item['available_count']; $total = (int) $item['total_count']; $ratio = $total > 0 ? ($available / $total) * 100 : 0; ?>
                            <a class="stock-row" href="crud/accounts/list.php?category=<?= $item['id'] ?>">
                                <div><strong><?= htmlspecialchars($item['name']) ?></strong><span><?= $available ?> / <?= $total ?> sản phẩm</span></div>
                                <div class="stock-meter"><span class="<?= $available <= $lowStockThreshold ? 'low' : '' ?>" style="width: <?= number_format($ratio, 2, '.', '') ?>%"></span></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <p class="panel-note">Màu cảnh báo xuất hiện khi tồn kho ≤ <?= $lowStockThreshold ?>, có thể đổi trong Cài đặt cửa hàng.</p>
                </article>
            </section>

            <section class="dashboard-grid dashboard-grid-secondary">
                <article class="panel-card">
                    <div class="panel-heading"><div><span class="eyebrow">Giao dịch mới</span><h2>Đơn hàng gần đây</h2></div><a href="crud/orders/list.php">Tất cả</a></div>
                    <div class="activity-list">
                        <?php foreach ($recentOrders as $order): ?>
                            <a class="activity-row" href="crud/orders/list.php?search=<?= $order['id'] ?>"><div><strong><?= htmlspecialchars($order['account_name'] ?? 'Sản phẩm đã xóa') ?></strong><span>@<?= htmlspecialchars($order['username'] ?? 'không còn dữ liệu') ?> · <?= date('d/m H:i', strtotime($order['created_at'])) ?></span></div><b><?= number_format($order['price'], 0, ',', '.') ?>đ</b></a>
                        <?php endforeach; ?>
                        <?php if (!$recentOrders): ?><div class="empty">Chưa có đơn hàng.</div><?php endif; ?>
                    </div>
                </article>

                <article class="panel-card">
                    <div class="panel-heading"><div><span class="eyebrow">Khách hàng</span><h2>Mua nhiều trong kỳ</h2></div><a href="crud/users/list.php">Tất cả</a></div>
                    <div class="activity-list ranked-list">
                        <?php foreach ($topCustomers as $index => $customer): ?>
                            <a class="activity-row" href="crud/users/update.php?id=<?= $customer['id'] ?>"><span class="rank-number"><?= $index + 1 ?></span><div><strong><?= htmlspecialchars($customer['fullname']) ?></strong><span>@<?= htmlspecialchars($customer['username']) ?> · <?= $customer['orders'] ?> đơn</span></div><b><?= number_format($customer['spent'], 0, ',', '.') ?>đ</b></a>
                        <?php endforeach; ?>
                        <?php if (!$topCustomers): ?><div class="empty">Chưa có dữ liệu trong kỳ.</div><?php endif; ?>
                    </div>
                </article>

                <article class="panel-card">
                    <div class="panel-heading"><div><span class="eyebrow">Kiểm soát thay đổi</span><h2>Nhật ký quản trị</h2></div></div>
                    <div class="audit-list">
                        <?php foreach ($activity as $entry): ?>
                            <div class="audit-row"><span></span><div><strong><?= htmlspecialchars($entry['description']) ?></strong><small><?= htmlspecialchars($entry['admin_name'] ?? 'Hệ thống') ?> · <?= date('d/m H:i', strtotime($entry['created_at'])) ?></small></div></div>
                        <?php endforeach; ?>
                        <?php if (!$activity): ?><div class="empty">Nhật ký sẽ xuất hiện sau thao tác quản trị đầu tiên.</div><?php endif; ?>
                    </div>
                </article>
            </section>
        </div>
    </main>
</div>
</body>
</html>
