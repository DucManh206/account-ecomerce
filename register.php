<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($fullname) || empty($username) || empty($password) || empty($confirm_password)) {
        set_flash('error', 'Vui lòng nhập đầy đủ thông tin.');
    } elseif ($password !== $confirm_password) {
        set_flash('error', 'Mật khẩu xác nhận không khớp.');
    } elseif (strlen($password) < 6) {
        set_flash('error', 'Mật khẩu phải từ 6 ký tự trở lên.');
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            set_flash('error', 'Tên đăng nhập này đã được sử dụng.');
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, balance) VALUES (?, ?, ?, 'user', 0)");
            
            if ($insert_stmt->execute([$username, $hashed_password, $fullname])) {
                set_flash('success', 'Đăng ký tài khoản thành công! Hãy đăng nhập để tiếp tục.');
                header('Location: login.php');
                exit;
            } else {
                set_flash('error', 'Đăng ký không thành công. Vui lòng thử lại sau.');
            }
        }
    }
}

$pageTitle = 'Đăng ký thành viên - Account Shop';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

    <div class="container" style="min-height: 70vh;">
        <div style="max-width: 450px; margin: 50px auto; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 40px; box-shadow: 0 10px 30px rgba(139, 92, 246, 0.1);">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="font-size: 2rem; color: var(--text-white); font-weight: 800;">Tạo tài khoản</h2>
                <p style="color: var(--text-gray); margin-top: 8px;">Đăng ký tài khoản để mua sắm tự động</p>
            </div>

            <?= render_flash() ?>

            <form method="POST">
                <?= csrf_field() ?>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
                    <label for="fullname" style="font-weight: 600; font-size: 0.9rem; color: var(--text-white);">Họ và tên</label>
                    <input type="text" id="fullname" name="fullname" placeholder="Nhập họ tên của bạn" required value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>" style="padding: 12px 16px; background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-white); font-family: inherit; font-size: 0.95rem; outline: none;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
                    <label for="username" style="font-weight: 600; font-size: 0.9rem; color: var(--text-white);">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" placeholder="Nhập username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" style="padding: 12px 16px; background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-white); font-family: inherit; font-size: 0.95rem; outline: none;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
                    <label for="password" style="font-weight: 600; font-size: 0.9rem; color: var(--text-white);">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Tối thiểu 6 ký tự" required style="padding: 12px 16px; background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-white); font-family: inherit; font-size: 0.95rem; outline: none;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                    <label for="confirm_password" style="font-weight: 600; font-size: 0.9rem; color: var(--text-white);">Nhập lại mật khẩu</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Xác nhận lại mật khẩu" required style="padding: 12px 16px; background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-white); font-family: inherit; font-size: 0.95rem; outline: none;">
                </div>

                <button type="submit" class="btn-buy" style="width: 100%; margin-top: 10px;">Đăng ký</button>
            </form>

            <div style="text-align: center; margin-top: 24px; color: var(--text-gray); font-size: 0.9rem;">
                Đã có tài khoản? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Đăng nhập ngay</a>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
