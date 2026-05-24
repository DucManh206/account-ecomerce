<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';

$success = '';
$error = '';

// Xử lý cập nhật cấu hình
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'sepay_api_token',
        'sepay_bank_code',
        'sepay_bank_num',
        'sepay_bank_name',
        'sepay_memo_prefix',
    ];

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
                <div class="form-group">
                    <label for="sepay_api_token">SePay API Token</label>
                    <input type="text" id="sepay_api_token" name="sepay_api_token" value="<?= $s('sepay_api_token', 'YOUR_SEPAY_API_TOKEN') ?>" required>
                    <div class="settings-hint">Token xác thực lấy từ trang quản lý tại sepay.vn. Để mặc định <code>YOUR_SEPAY_API_TOKEN</code> sẽ bật chế độ chạy thử (Mock).</div>
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
                
                <button type="submit" class="btn btn-primary" style="margin-top: 12px;">Lưu cấu hình</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>
