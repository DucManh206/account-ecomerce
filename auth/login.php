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
        $login_error = 'Tài khoản hoặc mật khẩu chưa đúng';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | <?php echo htmlspecialchars(getStoreName()); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/nexus.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        body::before {
            content: '';
            position: fixed;
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 500px;
            background: radial-gradient(ellipse, rgba(110, 86, 207, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 960px;
        }

        .login-left {
            flex: 1;
            padding: 50px 60px;
            background: var(--card-base);
            border-radius: 24px 0 0 24px;
            border: 1px solid var(--border);
            border-right: none;
            max-width: 480px;
        }

        .login-right {
            flex: 1;
            padding: 50px 60px;
            background: linear-gradient(160deg, rgba(110, 86, 207, 0.08) 0%, rgba(56, 189, 248, 0.04) 100%);
            border-radius: 0 24px 24px 0;
            border: 1px solid var(--border);
            border-left: none;
            max-width: 480px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin-bottom: 50px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent), #4F46E5);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .brand-text {
            font-weight: 800;
            font-size: 1.4rem;
            background: linear-gradient(135deg, #8B74E6, #38BDF8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .login-heading {
            margin-bottom: 8px;
        }

        .login-heading h2 {
            font-weight: 700;
            font-size: 1.75rem;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .login-heading p {
            color: var(--text-secondary);
            font-size: 1rem;
            margin: 0;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.15);
            border-radius: 14px;
            color: #FCA5A5;
            padding: 14px 18px;
            font-size: 0.9rem;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .field-group {
            margin-bottom: 22px;
        }

        .field-label {
            display: block;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .field-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .field-icon {
            position: absolute;
            left: 16px;
            color: var(--text-secondary);
            font-size: 0.95rem;
            pointer-events: none;
            transition: color 0.3s;
            z-index: 2;
        }

        .field-input {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 14px;
            color: var(--text-primary);
            padding: 15px 16px 15px 48px;
            font-size: 1rem;
            font-family: inherit;
            transition: 0.3s;
        }

        .field-input:focus {
            background: rgba(255,255,255,0.05);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-light);
            outline: none;
        }

        .field-input:focus ~ .field-icon,
        .field-input:focus + .field-icon {
            color: var(--accent);
        }

        .field-input:focus ~ .eye-btn,
        .field-input:focus + .eye-btn {
            color: var(--accent);
        }

        .field-input::placeholder {
            color: rgba(139, 143, 153, 0.4);
        }

        .eye-btn {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px 8px;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .eye-btn:hover { color: var(--text-primary); }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent), #4F46E5);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1.05rem;
            padding: 16px 28px;
            width: 100%;
            font-family: inherit;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(110, 86, 207, 0.3);
        }

        .link-group {
            text-align: center;
            margin-top: 28px;
        }

        .link-register {
            color: var(--text-secondary);
            font-size: 0.95rem;
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
            font-size: 0.875rem;
            margin-top: 16px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .link-home:hover {
            color: var(--text-primary);
            background: rgba(255,255,255,0.03);
        }

        .welcome-text {
            margin-bottom: 40px;
        }

        .welcome-text h3 {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .welcome-text p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .feature-list li i {
            width: 28px;
            height: 28px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #10B981;
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; max-width: 480px; }
            .login-left, .login-right {
                border-radius: 24px;
                border: 1px solid var(--border);
                max-width: 100%;
                padding: 40px 30px;
            }
            .login-right { border-top: none; border-radius: 0 0 24px 24px; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">

        <div class="login-left">
            <a href="../index.php" class="brand">
                <div class="brand-icon"><i class="<?php echo nexus_icon(); ?>"></i></div>
                <span class="brand-text"><?php echo htmlspecialchars(getStoreName()); ?></span>
            </a>

            <div class="login-heading">
                <h2>Đăng nhập tài khoản</h2>
                <p>Chào mừng bạn quay trở lại!</p>
            </div>

            <?php if ($login_error): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($login_error); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="field-group">
                    <label for="username" class="field-label">Tên đăng nhập</label>
                    <div class="field-input-wrap">
                        <input type="text" class="field-input" name="username" id="username"
                            placeholder="Nhập tên đăng nhập" autocomplete="off" required>
                        <i class="fa-regular fa-user field-icon"></i>
                    </div>
                </div>

                <div class="field-group">
                    <label for="password" class="field-label">Mật khẩu</label>
                    <div class="field-input-wrap">
                        <input type="password" class="field-input" name="password" id="password"
                            placeholder="Nhập mật khẩu" autocomplete="off" required>
                        <i class="fa-solid fa-lock field-icon"></i>
                        <button type="button" class="eye-btn" onclick="togglePassword()">
                            <i class="fa-regular fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Đăng nhập ngay
                </button>
            </form>

            <div class="link-group">
                <p class="link-register">
                    Chưa có tài khoản? <a href="register.php">Đăng ký miễn phí</a>
                </p>
                <a href="../index.php" class="link-home">
                    <i class="fa-solid fa-arrow-left"></i> Quay về trang chủ
                </a>
            </div>
        </div>

        <div class="login-right">
            <div class="welcome-text">
                <h3>Mua sắm an toàn,<br>giao dịch nhanh chóng</h3>
                <p>Cộng đồng người dùng đang tin tưởng <?php echo htmlspecialchars(getStoreName()); ?> cho các giao dịch của mình.</p>
            </div>

            <ul class="feature-list">
                <li>
                    <i class="fa-solid fa-check"></i>
                    Tài khoản được xác minh & bảo mật
                </li>
                <li>
                    <i class="fa-solid fa-check"></i>
                    Hỗ trợ 24/7 từ đội ngũ chuyên nghiệp
                </li>
                <li>
                    <i class="fa-solid fa-check"></i>
                    Giao dịch nhanh chóng, đáng tin cậy
                </li>
                <li>
                    <i class="fa-solid fa-check"></i>
                    Hoàn tiền nếu có sự cố phát sinh
                </li>
            </ul>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
