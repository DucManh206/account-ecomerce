<?php
require_once __DIR__ . '/admin/config/db.php';

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Đăng ký tài khoản thành công! Hãy đăng nhập để tiếp tục.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng điền đủ tài khoản và mật khẩu.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password']) || $user['password'] === md5($password))) {
            login_user($user);
            
            if ($user['role'] === 'admin') {
                $success = 'Đăng nhập Quản trị viên thành công! Đang chuyển hướng...';
                header('refresh:1;url=admin/dashboard.php');
            } else {
                $success = 'Đăng nhập thành công!';
                header('refresh:1;url=index.php');
            }
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
    <title>Đăng nhập thành viên - Account Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container {
            max-width: 450px;
            margin: 80px auto;
            background-color: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 40px;
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.1);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .auth-header h2 {
            font-size: 2rem;
            color: var(--text-white);
            font-weight: 800;
        }
        .auth-header p {
            color: var(--text-gray);
            margin-top: 8px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-white);
        }
        .form-group input {
            padding: 12px 16px;
            background-color: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-white);
            font-family: inherit;
            font-size: 0.95rem;
            outline: none;
            transition: var(--transition);
        }
        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--primary-glow);
        }
        .auth-footer {
            text-align: center;
            margin-top: 24px;
            color: var(--text-gray);
            font-size: 0.9rem;
        }
        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container navbar-content">
            <a href="index.php" class="logo">
                AccountShop
            </a>
            <div class="nav-links">
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <a href="admin/dashboard.php" class="btn-nav" style="border-color: #f59e0b; color: #f59e0b !important;">Quản trị viên</a>
                <?php endif; ?>
                <a href="index.php" class="nav-link">Trang chủ</a>
                <a href="topup.php" class="nav-link">Nạp tiền</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h2>Đăng nhập</h2>
                <p>Nhập tài khoản của bạn để mua sắm</p>
            </div>

            <?php if ($error): ?>
                <div class="frontend-alert frontend-alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="frontend-alert" style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: #a7f3d0;"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" placeholder="Nhập username của bạn" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>

                <div class="form-group" style="flex-direction: row; align-items: center; gap: 8px; margin-top: -10px; margin-bottom: 20px;">
                    <input type="checkbox" id="show-password" style="width: auto; cursor: pointer;">
                    <label for="show-password" style="font-size: 0.85rem; color: var(--text-gray); cursor: pointer; user-select: none;">Hiện mật khẩu</label>
                </div>

                <button type="submit" class="btn-buy" style="margin-top: 10px;">Đăng nhập</button>
            </form>

            <div class="auth-footer">
                Chưa có tài khoản? <a href="register.php">Đăng ký thành viên</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="container footer-content">
            <p>&copy; 2026 AccountShop - Nhóm 5. Bài tập lớn Lập trình web và ứng dụng.</p>
        </div>
    </footer>

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
