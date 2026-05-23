<?php
session_start();
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../lib/order_modules.php';
require_once __DIR__ . '/../admin_lib/admin_account_field_modules.php';
require_once __DIR__ . '/../lib/ui_modules.php';

$username = $_SESSION['username'] ?? '';
$checkoutData = $_SESSION['last_checkout'] ?? null;
$base = ui_getBasePath();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead('Thanh toán thành công'); ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        .success-wrap {
            max-width: 680px;
            margin: 40px auto;
            padding: 0 1rem;
        }
        .success-hero {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            padding: 36px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .success-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #10b981, #059669);
        }
        .success-check-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
            color: white;
            animation: success-pop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes success-pop {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-heading {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .success-sub {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 28px;
        }
        .accounts-section {
            text-align: left;
            margin-bottom: 20px;
        }
        .accounts-heading {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--green);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .accounts-heading .count {
            margin-left: auto;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: none;
            letter-spacing: 0;
        }
        .warning-note {
            background: var(--amber-dim);
            border: 1px solid rgba(245, 158, 11, 0.25);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            color: var(--amber);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .success-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .no-accounts {
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        @media (max-width: 576px) {
            .success-hero { padding: 28px 20px; }
            .success-actions { flex-direction: column; }
            .success-actions a { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <?php ui_renderNavbar($username, 0, 0, 'store'); ?>

    <div class="success-wrap">
        <div class="success-hero">
            <div class="success-check-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success-heading">Thanh toán thành công!</h1>
            <p class="success-sub">Cảm ơn bạn đã mua hàng tại <?php echo htmlspecialchars(getStoreName()); ?></p>

            <?php
                $accounts = $checkoutData['accounts'] ?? [];
                if (!empty($accounts)):
            ?>
                <div class="accounts-section">
                    <div class="accounts-heading">
                        <i class="fas fa-key"></i>
                        Thông tin tài khoản đã mua
                        <span class="count"><?php echo count($accounts); ?> tài khoản</span>
                    </div>

                    <?php foreach ($accounts as $idx => $acc): ?>
                        <div class="nx-account-card">
                            <div class="nx-account-header">
                                <div class="nx-account-icon"><i class="fas fa-box-open"></i></div>
                                <div class="nx-account-product"><?php echo htmlspecialchars($acc['product']); ?></div>
                            </div>
                            <?php
                                $data = $acc['account'];
                                $fieldMap = fieldType_getMap();
                                if (is_array($data)):
                            ?>
                                <?php echo renderAccountFields(json_encode($data), $fieldMap); ?>
                            <?php else: ?>
                                <div class="nx-account-fields">
                                    <div class="nx-account-field">
                                        <div class="nx-account-field-value" style="font-family:monospace;white-space:pre-wrap;word-break:break-all;">
                                            <?php echo htmlspecialchars($data); ?>
                                        </div>
                                        <button class="nx-account-copy" onclick="copyTextRaw(this, '<?php echo addslashes($data); ?>')">
                                            <i class="fas fa-copy"></i> Sao chép
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-accounts">
                    <i class="fas fa-info-circle" style="opacity:0.4;margin-bottom:8px;display:block;"></i>
                    Không tìm thấy thông tin tài khoản.
                </div>
            <?php endif; ?>

            <div class="warning-note">
                <i class="fas fa-exclamation-triangle"></i>
                Vui lòng đổi mật khẩu ngay sau khi đăng nhập thành công!
            </div>

            <div class="success-actions">
                <a href="<?php echo $base; ?>/user/orders.php" class="btn-nexus-success">
                    <i class="fas fa-list"></i> Xem tất cả đơn hàng
                </a>
                <a href="<?php echo $base; ?>/index.php" class="btn-nexus-secondary">
                    <i class="fas fa-bag-shopping"></i> Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </div>

    <?php ui_renderFooter(); ?>
    <?php ui_renderToastContainer(); ?>
    <?php ui_renderScripts(); ?>
    <script>
        async function copyTextRaw(btn, text) {
            try {
                await navigator.clipboard.writeText(text);
                btn.classList.add('copied');
                btn.innerHTML = '<i class="fas fa-check"></i> Đã sao chép!';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<i class="fas fa-copy"></i> Sao chép';
                }, 1500);
            } catch {
                NexusToast.error('Không thể sao chép. Vui lòng sao chép thủ công.');
            }
        }
    </script>
</body>
</html>
<?php
unset($_SESSION['last_checkout']);
?>
