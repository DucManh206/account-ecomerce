<?php
session_start();
require_once __DIR__ . '/../users/user_modules.php';
require_once __DIR__ . '/../cart/cart_modules.php';
require_once __DIR__ . '/../settings/settings_modules.php';
require_once __DIR__ . '/../ui/ui_modules.php';

$error_message = '';
$success_message = '';
$old_username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $old_username = $username;

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = 'Vui lòng điền đầy đủ thông tin';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Mật khẩu xác nhận không khớp';
    } elseif (strlen($password) < 6) {
        $error_message = 'Mật khẩu phải có ít nhất 6 ký tự';
    } else {
        if (checkUserExists($username)) {
            $error_message = 'Tên đăng nhập đã được sử dụng';
        } else {
            $userId = registerUser($username, $password);

            if ($userId) {
                session_regenerate_id(true);
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $userId;
                $_SESSION['cart_session_id'] = session_id() . '_' . uniqid();
                
                if (isset($_SESSION['cart_merged'])) {
                    mergeGuestCart($userId);
                    $_SESSION['cart_merged'] = $userId;
                }
                
                $success_message = 'Đăng ký thành công! Đang chuyển hướng...';
                header("Refresh: 1.5; url=login.php");
            } else {
                $error_message = 'Đăng ký thất bại. Vui lòng thử lại.';
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
    <title>Đăng ký | <?php echo htmlspecialchars(getStoreName()); ?></title>

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

        .alert-success {
            background: var(--green-dim);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 8px;
            color: var(--green);
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
            padding: 11px 40px 11px 40px;
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
            pointer-events: none;
            transition: color 0.2s;
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
                <h1>Tạo tài khoản</h1>
                <p>Đăng ký để bắt đầu mua sắm</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <div class="input-wrapper">
                        <input type="text" class="form-input" name="username" id="username"
                            placeholder="Chọn tên đăng nhập" autocomplete="off" required
                            value="<?php echo htmlspecialchars($old_username); ?>">
                        <i class="fa-regular fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" class="form-input" name="password" id="password"
                            placeholder="Tạo mật khẩu (ít nhất 6 ký tự)" autocomplete="off" required>
                        <i class="fa-solid fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', 'eyeIcon1')">
                            <i class="fa-regular fa-eye" id="eyeIcon1"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" class="form-input" name="confirm_password" id="confirm_password"
                            placeholder="Nhập lại mật khẩu" autocomplete="off" required>
                        <i class="fa-solid fa-shield-halved input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'eyeIcon2')">
                            <i class="fa-regular fa-eye" id="eyeIcon2"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-user-plus"></i>
                    Đăng ký
                </button>
            </form>

            <div class="link-group">
                <p class="link-text">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </p>
                <a href="../index.php" class="link-home">
                    <i class="fa-solid fa-arrow-left"></i> Quay về trang chủ
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
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
