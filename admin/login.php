<?php
require_once __DIR__ . '/config/db.php';

if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng điền tài khoản và mật khẩu.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $user['password'] === md5($password))) {
            login_user($user);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Tài khoản hoặc mật khẩu không chính xác.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../assets/admin/css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Account Shop</h1>
                <p>Quản trị hệ thống</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                </div>
                 <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>
                <div class="form-group" style="flex-direction: row; align-items: center; gap: 8px; margin-top: -10px; margin-bottom: 20px;">
                    <input type="checkbox" id="show-password" style="width: auto; cursor: pointer;">
                    <label for="show-password" style="font-size: 0.85rem; color: #a3a3a3; cursor: pointer; user-select: none;">Hiện mật khẩu</label>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Đăng nhập</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('show-password').addEventListener('change', function() {
            var passwordInput = document.getElementById('password');
            if (this.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });
    </script>
</body>
</html>
