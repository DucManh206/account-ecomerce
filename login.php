<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$redirectTarget = safe_internal_path($_GET['redirect'] ?? ($_POST['redirect'] ?? ''), '');

if (isset($_GET['registered'])) {
    set_flash('success', 'Đăng ký tài khoản thành công! Hãy đăng nhập để tiếp tục.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        set_flash('error', 'Vui lòng điền đủ tài khoản và mật khẩu.');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && !(bool) ($user['is_active'] ?? 1)) {
            set_flash('error', 'Tài khoản đang bị tạm khóa. Vui lòng liên hệ hỗ trợ.');
        } elseif ($user && (password_verify($password, $user['password']) || $user['password'] === md5($password))) {
            // Tự động nâng cấp hash mật khẩu lên Bcrypt an toàn nếu đang dùng MD5 hoặc hash lỗi thời
            if ($user['password'] === md5($password) || password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $updateHashStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateHashStmt->execute([$newHash, $user['id']]);
            }

            login_user($user);

            if ($user['role'] === 'admin') {
                set_flash('success', 'Đăng nhập Quản trị viên thành công!');
                header('Location: admin/dashboard.php');
                exit;
            }

            set_flash('success', 'Đăng nhập thành công!');
            header('Location: ' . ($redirectTarget !== '' ? $redirectTarget : 'index.php'));
            exit;
        } else {
            set_flash('error', 'Tài khoản hoặc mật khẩu không chính xác.');
        }
    }
}

$pageTitle = 'Đăng nhập - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

    <main id="main-content" class="container" style="min-height: 70vh;">
        <div style="max-width: 450px; margin: 60px auto; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 40px; box-shadow: 0 10px 30px rgba(139, 92, 246, 0.1);">
            <div style="text-align: center; margin-bottom: 24px;">
                <h2 style="font-size: 2rem; color: var(--text-white); font-weight: 800;">Đăng nhập</h2>
                <p style="color: var(--text-gray); margin-top: 8px;">Nhập tài khoản của bạn để mua sắm</p>
            </div>

            <?= render_flash() ?>

            <form method="POST">
                <?= csrf_field() ?>
                <?php if ($redirectTarget !== ''): ?>
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget) ?>">
                <?php endif; ?>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                    <label for="username" style="font-weight: 600; font-size: 0.9rem; color: var(--text-white);">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" placeholder="Nhập username của bạn" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" style="padding: 12px 16px; background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-white); font-family: inherit; font-size: 0.95rem; outline: none;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                    <label for="password" style="font-weight: 600; font-size: 0.9rem; color: var(--text-white);">Mật khẩu</label>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required style="padding: 12px 16px; background-color: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: var(--radius-sm); color: var(--text-white); font-family: inherit; font-size: 0.95rem; outline: none;">
                </div>

                <div style="display: flex; align-items: center; gap: 8px; margin-top: -10px; margin-bottom: 20px;">
                    <input type="checkbox" id="show-password" style="width: auto; cursor: pointer;">
                    <label for="show-password" style="font-size: 0.85rem; color: var(--text-gray); cursor: pointer; user-select: none;">Hiện mật khẩu</label>
                </div>

                <button type="submit" class="btn-buy" style="width: 100%; margin-top: 10px;">Đăng nhập</button>
            </form>

            <div style="text-align: center; margin-top: 24px; color: var(--text-gray); font-size: 0.9rem;">
                Chưa có tài khoản? <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Đăng ký thành viên</a>
            </div>
        </div>
    </main>

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
