<?php
session_start();
require_once __DIR__ . '/../users/user_modules.php';
require_once __DIR__ . '/../cart/cart_modules.php';
require_once __DIR__ . '/../settings/settings_modules.php';
require_once __DIR__ . '/../ui/ui_modules.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $result = getLogin($user, $pass);

    if ($result) {
        $guestCartSessionId = $_SESSION['cart_session_id'] ?? null;
        session_regenerate_id(true);
        $_SESSION['username'] = $result['username'];
        $_SESSION['user_id'] = $result['id'];
        if ($guestCartSessionId) {
            $_SESSION['cart_session_id'] = $guestCartSessionId;
            mergeGuestCart((int)$result['id']);
            $_SESSION['cart_merged'] = (int)$result['id'];
        }

        if (isset($result['role']) && $result['role'] == 1) {
            $_SESSION['is_admin'] = true;
            header('Location: ../../admin/dashboard.php');
        } else {
            $_SESSION['is_admin'] = false;
            header('Location: ../../index.php');
        }
        exit();
    } else {
        $login_error = 'Tên đăng nhập hoặc mật khẩu không đúng';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | <?php echo htmlspecialchars(getStoreName()); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/nexus.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .auth-container {
            width: 100%;
            max-width: 400px;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            margin-bottom: 32px;
        }

        .brand-text {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-primary);
        }

        .auth-card {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 36px 32px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .auth-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-primary);
        }

        .auth-header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 0;
        }

        .alert-error {
            background: var(--red-dim);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            color: var(--red);
            padding: 12px 14px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            color: var(--text-primary);
            padding: 11px 14px 11px 40px;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 3px var(--purple-dim);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .form-input:focus ~ .input-icon {
            color: var(--purple);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            font-size: 0.85rem;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--text-secondary);
        }

        .btn-submit {
            width: 100%;
            background: var(--accent);
            color: var(--bg-base);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 12px 20px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: var(--accent-hover);
        }

        .btn-submit:active {
            background: var(--accent);
        }

        .link-group {
            text-align: center;
            margin-top: 24px;
        }

        .link-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .link-text a {
            color: var(--purple);
            text-decoration: none;
            font-weight: 600;
        }

        .link-text a:hover {
            color: var(--accent);
            text-decoration: underline;
        }

        .link-home {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.85rem;
            margin-top: 16px;
            padding: 8px 14px;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
        }

        .link-home:hover {
            color: var(--text-primary);
            background: var(--accent-glow);
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 28px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <a href="../index.php" class="brand">
            <span class="brand-text"><?php echo htmlspecialchars(getStoreName()); ?></span>
        </a>

        <div class="auth-card">
            <div class="auth-header">
                <h1>Đăng nhập</h1>
                <p>Chào mừng bạn quay trở lại</p>
            </div>

            <?php if ($login_error): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($login_error); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <input type="text" class="form-input" name="username" id="username"
                            placeholder="Nhập tên đăng nhập" required autofocus>
                        <i class="fa-regular fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" class="form-input" name="password" id="password"
                            placeholder="Nhập mật khẩu" required>
                        <i class="fa-solid fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Đăng nhập
                </button>
            </form>

            <div class="link-group">
                <p class="link-text">
                    Chưa có tài khoản? <a href="register.php">Đăng ký</a>
                </p>
                <a href="../index.php" class="link-home">
                    <i class="fa-solid fa-arrow-left"></i> Quay về trang chủ
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa-regular fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fa-regular fa-eye';
            }
        }
    </script>
</body>
</html>
