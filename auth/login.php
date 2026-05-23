<?php
session_start();
require_once __DIR__ . '/../lib/user_modules.php';
require_once __DIR__ . '/../lib/cart_modules.php';
require_once __DIR__ . '/../lib/settings_modules.php';
require_once __DIR__ . '/../lib/ui_modules.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $result = getLogin($user, $pass);

    if ($result) {
        session_regenerate_id(true);
        $_SESSION['username'] = $result['username'];
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['cart_session_id'] = session_id() . '_' . uniqid();

        // Merge guest cart to user cart
        if (isset($_SESSION['cart_merged']) && $_SESSION['cart_merged'] !== $result['id']) {
            mergeGuestCart($result['id']);
            $_SESSION['cart_merged'] = $result['id'];
        }

        if (isset($result['role']) && $result['role'] == 1) {
            $_SESSION['is_admin'] = true;
            header('Location: ../admin/dashboard.php');
        } else {
            $_SESSION['is_admin'] = false;
            header('Location: ../index.php');
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

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
        }

        .brand-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin-bottom: 32px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6e56cf, #4F46E5);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .brand-text {
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--text-primary);
        }

        .login-card {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 40px 32px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            color: #FCA5A5;
            padding: 12px 16px;
            font-size: 0.9rem;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-subtle);
            border-radius: 10px;
            color: var(--text-primary);
            padding: 12px 16px 12px 44px;
            font-size: 0.95rem;
            transition: all 0.2s;
            outline: none;
        }

        .form-input:focus {
            background: rgba(255,255,255,0.05);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(250, 250, 250, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .form-input:focus ~ .input-icon {
            color: var(--accent);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px 8px;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--text-primary);
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--accent), #4F46E5);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(110, 86, 207, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 24px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: var(--border-subtle);
        }

        .divider span {
            position: relative;
            background: var(--card-base);
            padding: 0 12px;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .link-group {
            text-align: center;
        }

        .link-register {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .link-register a {
            color: #8B74E6;
            text-decoration: none;
            font-weight: 600;
        }

        .link-register a:hover {
            color: #A99BEF;
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
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .link-home:hover {
            color: var(--text-primary);
            background: rgba(255,255,255,0.03);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <a href="../index.php" class="brand-link">
            <div class="brand-icon">
                <i class="<?php echo function_exists('getStoreIconClass') ? getStoreIconClass() : 'fa-solid fa-ghost'; ?>"></i>
            </div>
            <span class="brand-text"><?php echo htmlspecialchars(getStoreName()); ?></span>
        </a>

        <div class="login-card">
            <div class="login-header">
                <h1>Đăng nhập</h1>
                <p>Chào mừng bạn quay trở lại!</p>
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

                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Đăng nhập
                </button>
            </form>

            <div class="divider">
                <span>hoặc</span>
            </div>

            <div class="link-group">
                <p class="link-register">
                    Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
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
