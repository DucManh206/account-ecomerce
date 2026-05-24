<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';

$success = '';
$error = '';

// Xử lý cập nhật cấu hình
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'sepay_enabled',
        'sepay_api_token',
        'sepay_bank_code',
        'sepay_bank_num',
        'sepay_bank_name',
        'sepay_memo_prefix',
    ];

    // Checkbox gửi về '1' khi checked, không gửi gì khi unchecked
    if (!isset($_POST['sepay_enabled'])) {
        $_POST['sepay_enabled'] = '0';
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($fields as $key) {
            $val = trim($_POST[$key] ?? '');
            $stmt->execute([$key, $val]);
        }
        $success = 'Đã lưu cấu hình thanh toán thành công.';
    } catch (Exception $e) {
        $error = 'Lỗi khi lưu cấu hình: ' . $e->getMessage();
    }
}

// Đọc giá trị hiện tại
$currentSettings = [];
try {
    $rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    foreach ($rows as $r) {
        $currentSettings[$r['setting_key']] = $r['setting_value'];
    }
} catch (Exception $e) {
    $currentSettings = [];
}

$s = function($key, $default = '') use ($currentSettings) {
    return htmlspecialchars($currentSettings[$key] ?? $default);
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu hình thanh toán - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
    <style>
        .settings-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Toggle Switch */
        .toggle-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color, #2a2a2a);
            padding: 20px 24px;
            margin-bottom: 24px;
            transition: border-color 0.3s ease;
        }
        .toggle-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }
        .toggle-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .toggle-label {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .toggle-label .badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .toggle-label .badge-on {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .toggle-label .badge-off {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .toggle-desc {
            font-size: 0.82rem;
            color: var(--text-muted, #888);
            line-height: 1.5;
        }

        /* The actual toggle switch */
        .switch {
            position: relative;
            width: 56px;
            height: 30px;
            flex-shrink: 0;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #333;
            transition: 0.35s ease;
            border-radius: 30px !important;
            border: 1px solid #444;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: #888;
            transition: 0.35s ease;
            border-radius: 50% !important;
        }
        input:checked + .slider {
            background-color: rgba(16, 185, 129, 0.25);
            border-color: #10b981;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
            background-color: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
        }
        input:focus + .slider {
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }

        /* Disabled state for fields when SePay is off */
        .sepay-fields.disabled {
            opacity: 0.45;
            pointer-events: none;
            filter: grayscale(0.5);
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    
    <main class="main-content">
        <header class="topbar">
            <h1>Cấu hình thanh toán (SePay & VietQR)</h1>
        </header>
        
        <div class="content-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <?php $isEnabled = ($currentSettings['sepay_enabled'] ?? '1') === '1'; ?>
                <div class="toggle-card">
                    <div class="toggle-info">
                        <div class="toggle-label">
                            Kết nối SePay
                            <span class="badge <?= $isEnabled ? 'badge-on' : 'badge-off' ?>" id="sepay_badge">
                                <?= $isEnabled ? 'Đang bật' : 'Đã tắt' ?>
                            </span>
                        </div>
                        <div class="toggle-desc">
                            Khi <strong>tắt</strong>, hệ thống sẽ chuyển sang chế độ <strong>Mock Test</strong> — giả lập nạp tiền mà không kết nối API ngân hàng thật.
                        </div>
                    </div>
                    <label class="switch">
                        <input type="hidden" name="sepay_enabled" value="0">
                        <input type="checkbox" name="sepay_enabled" value="1" id="sepay_toggle" <?= $isEnabled ? 'checked' : '' ?> onchange="toggleSepayFields()">
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="sepay-fields <?= !$isEnabled ? 'disabled' : '' ?>" id="sepay_fields_wrapper">
                <div class="form-group">
                    <label for="sepay_api_token">SePay API Token</label>
                    <input type="text" id="sepay_api_token" name="sepay_api_token" value="<?= $s('sepay_api_token', 'YOUR_SEPAY_API_TOKEN') ?>">
                    <div class="settings-hint">Token xác thực lấy từ trang quản lý tại sepay.vn. Khi SePay tắt, token sẽ không được sử dụng.</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sepay_bank_code">Mã ngân hàng</label>
                        <input type="text" id="sepay_bank_code" name="sepay_bank_code" value="<?= $s('sepay_bank_code', 'MBBank') ?>" required placeholder="MBBank">
                        <div class="settings-hint">Ví dụ: MBBank, Vietcombank, ACB, TPBank, Techcombank...</div>
                    </div>
                    <div class="form-group">
                        <label for="sepay_bank_num">Số tài khoản ngân hàng</label>
                        <input type="text" id="sepay_bank_num" name="sepay_bank_num" value="<?= $s('sepay_bank_num') ?>" required placeholder="0123456789">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sepay_bank_name">Tên chủ tài khoản</label>
                        <input type="text" id="sepay_bank_name" name="sepay_bank_name" value="<?= $s('sepay_bank_name') ?>" required placeholder="NGUYEN VAN A">
                        <div class="settings-hint">Viết hoa, không dấu (theo đúng tên trên sổ ngân hàng).</div>
                    </div>
                    <div class="form-group">
                        <label for="sepay_memo_prefix">Tiền tố nội dung chuyển khoản</label>
                        <input type="text" id="sepay_memo_prefix" name="sepay_memo_prefix" value="<?= $s('sepay_memo_prefix', 'NAP') ?>" required placeholder="NAP">
                        <div class="settings-hint">Nội dung chuyển khoản sẽ là: <code>[Tiền tố] [User ID]</code>, ví dụ: NAP 5</div>
                    </div>
                </div>
                
                </div><!-- /.sepay-fields -->

                <button type="submit" class="btn btn-primary" style="margin-top: 12px;">Lưu cấu hình</button>
            </form>

            <script>
                function toggleSepayFields() {
                    const cb = document.getElementById('sepay_toggle');
                    const wrapper = document.getElementById('sepay_fields_wrapper');
                    const badge = document.getElementById('sepay_badge');
                    if (cb.checked) {
                        wrapper.classList.remove('disabled');
                        badge.className = 'badge badge-on';
                        badge.textContent = 'Đang bật';
                    } else {
                        wrapper.classList.add('disabled');
                        badge.className = 'badge badge-off';
                        badge.textContent = 'Đã tắt';
                    }
                }
            </script>
        </div>
    </main>
</div>
</body>
</html>
