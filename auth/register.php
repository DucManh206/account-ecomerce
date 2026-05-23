<?php
session_start();
require_once __DIR__ . '/../lib/user_modules.php';
require_once __DIR__ . '/../lib/cart_modules.php';
require_once __DIR__ . '/../lib/settings_modules.php';

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
                // Auto login after registration
                session_regenerate_id(true);
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $userId;
                $_SESSION['cart_session_id'] = session_id() . '_' . uniqid();
                
                // Merge guest cart
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
            background-clip: text;
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

        .alert-success {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.15);
            border-radius: 14px;
            color: #6EE7B7;
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
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            border-radius: 14px;
            color: var(--text-primary);
            padding: 15px 48px 15px 48px;
            font-size: 1rem;
            font-family: inherit;
            transition: 0.3s;
        }

        .field-input:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-light);
            outline: none;
        }

        .field-input:focus~.field-icon,
        .field-input:focus+.eye-btn,
        .field-input:focus~.eye-btn {
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

        .eye-btn:hover {
            color: var(--text-primary);
        }

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
            background: rgba(255, 255, 255, 0.03);
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
            .login-wrapper {
                flex-direction: column;
                max-width: 480px;
            }

            .login-left,
            .login-right {
                border-radius: 24px;
                border: 1px solid var(--border);
                max-width: 100%;
                padding: 40px 30px;
            }

            .login-right {
                border-top: none;
                border-radius: 0 0 24px 24px;
            }
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
                <h2>Tạo tài khoản mới</h2>
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
                <div class="field-group">
                    <label for="username" class="field-label">Tên đăng nhập</label>
                    <div class="field-input-wrap">
                        <input type="text" class="field-input" name="username" id="username"
                            placeholder="Chọn tên đăng nhập" autocomplete="off" required
                            value="<?php echo htmlspecialchars($old_username); ?>">
                        <i class="fa-regular fa-user field-icon"></i>
                    </div>
                </div>

                <div class="field-group">
                    <label for="password" class="field-label">Mật khẩu</label>
                    <div class="field-input-wrap">
                        <input type="password" class="field-input" name="password" id="password"
                            placeholder="Tạo mật khẩu (ít nhất 6 ký tự)" autocomplete="off" required>
                        <i class="fa-solid fa-lock field-icon"></i>
                        <button type="button" class="eye-btn" onclick="togglePassword('password', 'eyeIcon1')">
                            <i class="fa-regular fa-eye" id="eyeIcon1"></i>
                        </button>
                    </div>
                </div>

                <div class="field-group">
                    <label for="confirm_password" class="field-label">Xác nhận mật khẩu</label>
                    <div class="field-input-wrap">
                        <input type="password" class="field-input" name="confirm_password" id="confirm_password"
                            placeholder="Nhập lại mật khẩu" autocomplete="off" required>
                        <i class="fa-solid fa-shield-halved field-icon"></i>
                        <button type="button" class="eye-btn" onclick="togglePassword('confirm_password', 'eyeIcon2')">
                            <i class="fa-regular fa-eye" id="eyeIcon2"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-user-plus"></i>
                    Đăng ký ngay
                </button>
            </form>

            <div class="link-group">
                <p class="link-register">
                    Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
                </p>
                <a href="../index.php" class="link-home">
                    <i class="fa-solid fa-arrow-left"></i> Quay về trang chủ
                </a>
            </div>
        </div>

        <div class="login-right">
            <div class="welcome-text">
                <h3>Tham gia cộng đồng<br><?php echo htmlspecialchars(getStoreName()); ?> ngay hôm nay!</h3>
                <p>Đăng ký tài khoản để trải nghiệm mua sắm với hàng ngàn sản phẩm chất lượng.</p>
            </div>

            <ul class="feature-list">
                <li><i class="fa-solid fa-check"></i> Đăng ký miễn phí, nhanh chóng</li>
                <li><i class="fa-solid fa-check"></i> Bảo mật tài khoản tuyệt đối</li>
                <li><i class="fa-solid fa-check"></i> Theo dõi đơn hàng dễ dàng</li>
                <li><i class="fa-solid fa-check"></i> Ưu đãi hấp dẫn mỗi ngày</li>
            </ul>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            var inputField = document.getElementById(inputId);
            var eyeIcon = document.getElementById(iconId);
            if (inputField.type === 'password') {
                inputField.type = 'text';
                eyeIcon.className = 'fa-regular fa-eye-slash';
            } else {
                inputField.type = 'password';
                eyeIcon.className = 'fa-regular fa-eye';
            }
        }
    </script>
</body>

</html>