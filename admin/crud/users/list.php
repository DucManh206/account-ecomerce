<?php
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/../../../includes/flash.php';

$search = trim($_GET['search'] ?? '');
$requestedRole = $_GET['role'] ?? 'all';
$requestedState = $_GET['state'] ?? 'all';
$role = in_array($requestedRole, ['all', 'admin', 'user'], true) ? $requestedRole : 'all';
$state = in_array($requestedState, ['all', 'active', 'suspended'], true) ? $requestedState : 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = app_setting_int('admin_page_size', 20, 10, 100);
$offset = ($page - 1) * $pageSize;

$result = getFilteredUsers($pdo, $search, $role, $state, $pageSize, $offset);
$totalRows = $result['total'];
$totalPages = max(1, (int) ceil($totalRows / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $pageSize;
    $result = getFilteredUsers($pdo, $search, $role, $state, $pageSize, $offset);
}
$users = $result['rows'];
$stats = getUserStats($pdo);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thành viên - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar admin-page-heading">
            <div>
                <span class="eyebrow">Khách hàng & quyền truy cập</span>
                <h1>Thành viên</h1>
                <p>Tìm khách hàng, xem giá trị mua hàng và quản lý số dư có lịch sử.</p>
            </div>
            <a href="add.php" class="btn btn-primary">Thêm thành viên</a>
        </header>

        <div class="content-body">
            <?= render_flash() ?>

            <section class="stats-grid stats-grid-compact">
                <article class="stat-card"><span class="stat-label">Tổng tài khoản</span><strong class="stat-number"><?= number_format($stats['total']) ?></strong><span class="stat-meta"><?= number_format($stats['customers']) ?> khách hàng · <?= number_format($stats['admins']) ?> admin</span></article>
                <article class="stat-card"><span class="stat-label">Số dư đang lưu hành</span><strong class="stat-number stat-number-money"><?= number_format($stats['total_balance'], 0, ',', '.') ?>đ</strong><span class="stat-meta">Tổng số dư thực trong hệ thống</span></article>
                <article class="stat-card"><span class="stat-label">Đang tạm khóa</span><strong class="stat-number"><?= number_format($stats['suspended']) ?></strong><span class="stat-meta">Không thể đăng nhập hoặc mua hàng</span></article>
            </section>

            <form method="GET" class="toolbar-card">
                <div class="toolbar-search">
                    <label for="search">Tìm thành viên</label>
                    <input type="search" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tên đăng nhập hoặc họ tên">
                </div>
                <div class="toolbar-field">
                    <label for="role">Vai trò</label>
                    <select id="role" name="role">
                        <option value="all">Tất cả</option>
                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Khách hàng</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                    </select>
                </div>
                <div class="toolbar-field">
                    <label for="state">Truy cập</label>
                    <select id="state" name="state">
                        <option value="all">Tất cả</option>
                        <option value="active" <?= $state === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                        <option value="suspended" <?= $state === 'suspended' ? 'selected' : '' ?>>Tạm khóa</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Lọc dữ liệu</button>
                <?php if ($search !== '' || $role !== 'all' || $state !== 'all'): ?><a class="btn btn-secondary" href="list.php">Xóa lọc</a><?php endif; ?>
            </form>

            <section class="table-card">
                <div class="table-card-header">
                    <div><h2><?= number_format($totalRows) ?> thành viên</h2><p>Trang <?= $page ?> / <?= $totalPages ?></p></div>
                </div>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead><tr><th>Thành viên</th><th>Quyền</th><th>Số dư</th><th>Đơn hàng</th><th>Giá trị mua</th><th>Tham gia</th><th>Thao tác</th></tr></thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?= !$user['is_active'] ? 'row-muted' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($user['fullname']) ?></strong>
                                    <span class="table-subtext">@<?= htmlspecialchars($user['username']) ?> · ID <?= $user['id'] ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $user['role'] === 'admin' ? 'badge-warning' : 'badge-neutral' ?>"><?= $user['role'] === 'admin' ? 'Quản trị viên' : 'Khách hàng' ?></span>
                                    <?php if (!$user['is_active']): ?><span class="badge badge-rejected">Tạm khóa</span><?php endif; ?>
                                </td>
                                <td class="money-positive"><?= number_format($user['balance'], 0, ',', '.') ?>đ</td>
                                <td><?= number_format($user['order_count']) ?><span class="table-subtext"><?= $user['last_order_at'] ? 'Gần nhất ' . date('d/m/Y', strtotime($user['last_order_at'])) : 'Chưa mua hàng' ?></span></td>
                                <td><?= number_format($user['total_spent'], 0, ',', '.') ?>đ</td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                <td class="actions">
                                    <a href="update.php?id=<?= $user['id'] ?>" class="btn btn-small btn-edit">Chi tiết</a>
                                    <?php if ((int) $user['id'] !== (int) $_SESSION['admin_user_id']): ?>
                                        <form method="POST" action="delete.php" class="inline-form" onsubmit="return confirm('Chỉ có thể xóa tài khoản chưa phát sinh dữ liệu. Tiếp tục?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <button class="btn btn-small btn-delete" type="submit">Xóa</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$users): ?><tr><td colspan="7" class="empty">Không có thành viên phù hợp bộ lọc.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Phân trang">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a class="page-link <?= $i === $page ? 'active' : '' ?>" href="?<?= http_build_query(['search' => $search, 'role' => $role, 'state' => $state, 'page' => $i]) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
