<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

$expiryMinutes = app_setting_int('topup_expiry_minutes', 15, 5, 60);
$expiryCutoff = date('Y-m-d H:i:s', time() - ($expiryMinutes * 60));
$expireStmt = $pdo->prepare("UPDATE topup_requests SET status = 'expired' WHERE status = 'pending' AND created_at < ?");
$expireStmt->execute([$expiryCutoff]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $action = $_POST['request_action'] ?? '';
    $note = mb_substr(trim($_POST['admin_note'] ?? ''), 0, 255);

    if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        set_flash('error', 'Thao tác xử lý yêu cầu không hợp lệ.');
        header('Location: list.php');
        exit;
    }

    try {
        $pdo->beginTransaction();
        $requestStmt = $pdo->prepare(
            'SELECT tr.*, u.username, u.balance
             FROM topup_requests tr
             JOIN users u ON u.id = tr.user_id
             WHERE tr.id = ? FOR UPDATE'
        );
        $requestStmt->execute([$requestId]);
        $request = $requestStmt->fetch();

        if (!$request || $request['status'] !== 'pending') {
            throw new RuntimeException('Yêu cầu đã được xử lý hoặc không còn tồn tại.');
        }

        $adminId = (int) $_SESSION['admin_user_id'];
        if ($action === 'reject') {
            $stmt = $pdo->prepare(
                "UPDATE topup_requests
                 SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), admin_note = ?
                 WHERE id = ?"
            );
            $stmt->execute([$adminId, $note ?: null, $requestId]);
            record_admin_activity(
                $pdo,
                'topup_rejected',
                'topup_request',
                $requestId,
                'Từ chối yêu cầu nạp tiền của @' . $request['username'],
                ['amount' => (float) $request['amount'], 'note' => $note]
            );
            $message = 'Đã từ chối yêu cầu #' . $requestId . '.';
        } else {
            $userStmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
            $userStmt->execute([(int) $request['user_id']]);
            $currentBalance = $userStmt->fetchColumn();
            if ($currentBalance === false) {
                throw new RuntimeException('Không tìm thấy tài khoản nhận tiền.');
            }

            $amount = (float) $request['amount'];
            $balanceAfter = (float) $currentBalance + $amount;
            $updateUser = $pdo->prepare('UPDATE users SET balance = ? WHERE id = ?');
            $updateUser->execute([$balanceAfter, (int) $request['user_id']]);

            $updateRequest = $pdo->prepare(
                "UPDATE topup_requests
                 SET status = 'completed', reviewed_by = ?, reviewed_at = NOW(), admin_note = ?
                 WHERE id = ?"
            );
            $updateRequest->execute([$adminId, $note ?: null, $requestId]);

            record_balance_transaction(
                $pdo,
                (int) $request['user_id'],
                'deposit',
                $amount,
                $balanceAfter,
                'manual_topup',
                $requestId,
                'Admin duyệt yêu cầu nạp tiền #' . $requestId,
                $adminId
            );
            record_admin_activity(
                $pdo,
                'topup_approved',
                'topup_request',
                $requestId,
                'Duyệt yêu cầu nạp tiền của @' . $request['username'],
                ['amount' => $amount, 'balance_after' => $balanceAfter, 'note' => $note]
            );
            $message = 'Đã duyệt và cộng ' . number_format($amount, 0, ',', '.') . 'đ cho @' . $request['username'] . '.';
        }

        $pdo->commit();
        set_flash('success', $message);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', $e->getMessage());
    }

    $returnQuery = http_build_query([
        'status' => $_POST['return_status'] ?? 'all',
        'search' => $_POST['return_search'] ?? '',
        'page' => max(1, (int) ($_POST['return_page'] ?? 1)),
    ]);
    header('Location: list.php?' . $returnQuery);
    exit;
}

$allowedStatuses = ['all', 'pending', 'completed', 'expired', 'rejected', 'cancelled'];
$requestedStatus = $_GET['status'] ?? 'all';
$status = in_array($requestedStatus, $allowedStatuses, true) ? $requestedStatus : 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = app_setting_int('admin_page_size', 20, 10, 100);

$where = [];
$params = [];
if ($status !== 'all') {
    $where[] = 'tr.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(u.username LIKE ? OR u.fullname LIKE ? OR tr.memo LIKE ?)';
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM topup_requests tr JOIN users u ON u.id = tr.user_id' . $whereSql
);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $pageSize));
$page = min($page, $totalPages);
$offset = ($page - 1) * $pageSize;

$listStmt = $pdo->prepare(
    'SELECT tr.*, u.username, u.fullname, reviewer.fullname AS reviewer_name
     FROM topup_requests tr
     JOIN users u ON u.id = tr.user_id
     LEFT JOIN users reviewer ON reviewer.id = tr.reviewed_by' .
    $whereSql . ' ORDER BY tr.id DESC LIMIT ' . $pageSize . ' OFFSET ' . $offset
);
$listStmt->execute($params);
$requests = $listStmt->fetchAll();

$summaryRows = $pdo->query(
    "SELECT status, COUNT(*) AS total, COALESCE(SUM(amount), 0) AS amount
     FROM topup_requests GROUP BY status"
)->fetchAll();
$summary = [];
foreach ($summaryRows as $row) {
    $summary[$row['status']] = $row;
}

$statusLabels = [
    'pending' => 'Đang chờ',
    'completed' => 'Đã hoàn tất',
    'expired' => 'Hết hạn',
    'rejected' => 'Đã từ chối',
    'cancelled' => 'Đã hủy',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu nạp tiền - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar admin-page-heading">
            <div>
                <span class="eyebrow">Vận hành số dư</span>
                <h1>Yêu cầu nạp tiền</h1>
                <p>Đối soát yêu cầu đang chờ và xử lý thủ công khi cần.</p>
            </div>
        </header>

        <div class="content-body">
            <?= render_flash() ?>

            <section class="stats-grid stats-grid-compact">
                <article class="stat-card">
                    <span class="stat-label">Đang chờ</span>
                    <strong class="stat-number"><?= (int) ($summary['pending']['total'] ?? 0) ?></strong>
                    <span class="stat-meta"><?= number_format($summary['pending']['amount'] ?? 0, 0, ',', '.') ?>đ cần đối soát</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Đã hoàn tất</span>
                    <strong class="stat-number"><?= (int) ($summary['completed']['total'] ?? 0) ?></strong>
                    <span class="stat-meta"><?= number_format($summary['completed']['amount'] ?? 0, 0, ',', '.') ?>đ đã ghi nhận</span>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Hết hạn / từ chối</span>
                    <strong class="stat-number"><?= (int) ($summary['expired']['total'] ?? 0) + (int) ($summary['rejected']['total'] ?? 0) ?></strong>
                    <span class="stat-meta">Không làm thay đổi số dư</span>
                </article>
            </section>

            <form method="GET" class="toolbar-card" aria-label="Bộ lọc yêu cầu nạp tiền">
                <div class="toolbar-search">
                    <label for="search">Tìm người dùng hoặc mã chuyển khoản</label>
                    <input id="search" name="search" type="search" value="<?= htmlspecialchars($search) ?>" placeholder="username, họ tên, mã NAP...">
                </div>
                <div class="toolbar-field">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status">
                        <option value="all">Tất cả</option>
                        <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Lọc dữ liệu</button>
                <?php if ($search !== '' || $status !== 'all'): ?>
                    <a class="btn btn-secondary" href="list.php">Xóa lọc</a>
                <?php endif; ?>
            </form>

            <section class="table-card">
                <div class="table-card-header">
                    <div>
                        <h2><?= number_format($totalRows) ?> yêu cầu</h2>
                        <p>Dữ liệu được lấy trực tiếp từ lịch sử nạp tiền.</p>
                    </div>
                </div>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>Yêu cầu</th>
                            <th>Khách hàng</th>
                            <th>Số tiền</th>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                            <th>Xử lý</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <strong>#<?= $request['id'] ?></strong>
                                    <code class="table-code"><?= htmlspecialchars($request['memo']) ?></code>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($request['fullname']) ?></strong>
                                    <span class="table-subtext">@<?= htmlspecialchars($request['username']) ?> · ID <?= $request['user_id'] ?></span>
                                </td>
                                <td class="money-positive"><?= number_format($request['amount'], 0, ',', '.') ?>đ</td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($request['created_at'])) ?>
                                    <?php if ($request['reviewed_at']): ?>
                                        <span class="table-subtext">Xử lý <?= date('d/m/Y H:i', strtotime($request['reviewed_at'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($request['status']) ?>">
                                        <?= $statusLabels[$request['status']] ?? $request['status'] ?>
                                    </span>
                                    <?php if ($request['reviewer_name']): ?>
                                        <span class="table-subtext">bởi <?= htmlspecialchars($request['reviewer_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <details class="row-actions-panel">
                                            <summary class="btn btn-small btn-secondary">Xử lý</summary>
                                            <form method="POST" class="row-actions-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                <input type="hidden" name="return_status" value="<?= htmlspecialchars($status) ?>">
                                                <input type="hidden" name="return_search" value="<?= htmlspecialchars($search) ?>">
                                                <input type="hidden" name="return_page" value="<?= $page ?>">
                                                <label for="note-<?= $request['id'] ?>">Ghi chú nội bộ</label>
                                                <input id="note-<?= $request['id'] ?>" name="admin_note" maxlength="255" placeholder="Lý do duyệt/từ chối (không bắt buộc)">
                                                <div class="row-actions-buttons">
                                                    <button type="submit" name="request_action" value="approve" class="btn btn-small btn-success" onclick="return confirm('Duyệt và cộng số dư cho người dùng?')">Duyệt & cộng tiền</button>
                                                    <button type="submit" name="request_action" value="reject" class="btn btn-small btn-delete" onclick="return confirm('Từ chối yêu cầu này?')">Từ chối</button>
                                                </div>
                                            </form>
                                        </details>
                                    <?php else: ?>
                                        <span class="table-subtext"><?= htmlspecialchars($request['admin_note'] ?: 'Đã khóa thao tác') ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$requests): ?>
                            <tr><td colspan="6" class="empty">Không có yêu cầu phù hợp bộ lọc.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Phân trang">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a class="page-link <?= $i === $page ? 'active' : '' ?>" href="?<?= http_build_query(['status' => $status, 'search' => $search, 'page' => $i]) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
