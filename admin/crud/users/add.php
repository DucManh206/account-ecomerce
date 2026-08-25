<?php
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/../../../includes/flash.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'password' => trim($_POST['password'] ?? ''),
        'fullname' => trim($_POST['fullname'] ?? ''),
        'role' => in_array($_POST['role'] ?? '', ['user', 'admin'], true) ? $_POST['role'] : 'user',
        'balance' => max(0, (int) ($_POST['balance'] ?? 0))
    ];

    if ($data['username'] === '' || $data['password'] === '' || $data['fullname'] === '') {
        $error = 'Vui lòng điền đầy đủ các trường thông tin bắt buộc.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $data['username'])) {
        $error = 'Tên đăng nhập cần 3-50 ký tự và chỉ gồm chữ, số, dấu chấm, gạch ngang hoặc gạch dưới.';
    } elseif (strlen($data['password']) < 8) {
        $error = 'Mật khẩu phải từ 8 ký tự trở lên.';
    } else {
        // Kiểm tra xem tên đăng nhập đã được sử dụng chưa
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $error = 'Tên đăng nhập này đã tồn tại trong hệ thống.';
        } else {
            try {
                $pdo->beginTransaction();
                addUser($pdo, $data);
                $newUserId = (int) $pdo->lastInsertId();
                if ($data['balance'] > 0) {
                    record_balance_transaction($pdo, $newUserId, 'adjustment', $data['balance'], $data['balance'], 'opening_balance', null, 'Số dư khởi tạo bởi admin', (int) $_SESSION['admin_user_id']);
                }
                record_admin_activity($pdo, 'user_created', 'user', $newUserId, 'Tạo thành viên @' . $data['username'], ['role' => $data['role'], 'opening_balance' => $data['balance']]);
                $pdo->commit();
                set_flash('success', 'Thêm thành viên thành công.');
                header('Location: list.php');
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('add user: ' . $e->getMessage());
                $error = 'Có lỗi xảy ra khi thêm thành viên.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm thành viên - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    
    <main class="main-content">
        <header class="topbar">
            <h1>Thêm thành viên mới</h1>
            <a href="list.php" class="btn btn-secondary">Quay lại</a>
        </header>
        
        <div class="content-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Tên đăng nhập (Bắt buộc)</label>
                        <input type="text" id="username" name="username" placeholder="Nhập username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu đăng nhập (Bắt buộc)</label>
                        <input type="password" id="password" name="password" placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullname">Họ và tên thành viên (Bắt buộc)</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Nhập họ tên" required value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="role">Vai trò</label>
                        <select id="role" name="role" required style="width: 100%; padding: 10px; background-color: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-white); border-radius: 4px;">
                            <option value="user" <?= (isset($_POST['role']) && $_POST['role'] === 'user') ? 'selected' : '' ?>>Khách hàng (User)</option>
                            <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="balance">Số dư ban đầu (đ)</label>
                        <input type="number" id="balance" name="balance" min="0" value="<?= isset($_POST['balance']) ? intval($_POST['balance']) : 0 ?>" required>
                    </div>
                    <div class="form-group">
                        <!-- Spacer to keep layout balanced -->
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Thêm thành viên</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
