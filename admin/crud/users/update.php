<?php
require_once __DIR__ . '/users.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: list.php?error=' . urlencode('ID thành viên không hợp lệ.'));
    exit;
}

$user = getUserById($pdo, $id);
if (!$user) {
    header('Location: list.php?error=' . urlencode('Thành viên không tồn tại.'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'fullname' => trim($_POST['fullname'] ?? ''),
        'balance' => trim($_POST['balance'] ?? 0),
        'role' => trim($_POST['role'] ?? 'user')
    ];

    if ($data['fullname'] === '') {
        $error = 'Vui lòng nhập họ và tên thành viên.';
    } else {
        if (updateUser($pdo, $id, $data)) {
            header('Location: list.php?success=' . urlencode('Cập nhật thông tin thành viên thành công.'));
            exit;
        } else {
            $error = 'Có lỗi xảy ra khi cập nhật thành viên.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa thành viên - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    
    <main class="main-content">
        <header class="topbar">
            <h1>Cập nhật thành viên (ID: #<?= htmlspecialchars($user['id']) ?>)</h1>
            <a href="list.php" class="btn btn-secondary">Quay lại</a>
        </header>
        
        <div class="content-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="fullname">Họ và tên</label>
                    <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="role">Vai trò</label>
                    <select id="role" name="role" required style="width: 100%; padding: 10px; background-color: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-white); border-radius: 4px;">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Khách hàng (User)</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="balance">Số dư tài khoản (đ)</label>
                    <input type="number" id="balance" name="balance" min="0" value="<?= htmlspecialchars($user['balance']) ?>" required>
                    <small style="color: var(--text-muted); margin-top: 4px;">Admin có thể trực tiếp cộng thêm hoặc thay đổi số tiền trong tài khoản của thành viên.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
