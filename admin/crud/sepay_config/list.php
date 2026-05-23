<?php
require_once __DIR__ . "/../../../admin/crud/layout/admin_layout_modules.php";
require_once __DIR__ . "/sepay_config.php";
require_once __DIR__ . "/../../../crud/sepay/sepay_modules.php";

$config = admin_getSepayConfig();
$stats = admin_getSepayStats();
$sepayBanks = sepay_getSupportedBanks();

$success = false;
$errorMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    $result = admin_saveSepayConfig($_POST);
    if ($result['success']) {
        $success = true;
        $config = admin_getSepayConfig();
    } else {
        $errorMsg = $result['message'];
    }
}

ob_start();
?>

<?php if ($success): ?>
<div class="nx-alert nx-alert-success mb-3">
    <i class="fa-solid fa-check-circle"></i> Lưu cấu hình SePay thành công!
</div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="nx-alert nx-alert-danger mb-3">
    <i class="fa-solid fa-circle-xmark"></i> <?php echo $errorMsg; ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #6E56CF, #4F46E5); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fa-solid fa-receipt"></i></div>
            <div class="nx-stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="nx-stat-label">Tổng giao dịch</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #F59E0B, #D97706); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fa-solid fa-clock"></i></div>
            <div class="nx-stat-value"><?php echo number_format($stats['pending']); ?></div>
            <div class="nx-stat-label">Đang chờ</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #10B981, #059669); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fa-solid fa-check-circle"></i></div>
            <div class="nx-stat-value"><?php echo number_format($stats['matched']); ?></div>
            <div class="nx-stat-label">Đã khớp</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="nx-stat-card" style="background: linear-gradient(135deg, #38BDF8, #0EA5E9); color: white;">
            <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="fa-solid fa-coins"></i></div>
            <div class="nx-stat-value"><?php echo number_format($stats['today_amount'], 0, ',', '.'); ?>đ</div>
            <div class="nx-stat-label">Hôm nay</div>
        </div>
    </div>
</div>

<form method="POST">
    <input type="hidden" name="action" value="save_config">

    <!-- Main Config -->
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <h5 class="mb-0"><i class="fa-solid fa-university me-2"></i>Cấu hình SePay</h5>
        </div>
        <div class="nx-card-body">
            <div class="toggle-wrapper mb-4">
                <label class="toggle-switch">
                    <input type="checkbox" name="status" value="1" <?php echo ($config && $config['status'] == 1) ? 'checked' : ''; ?>>
                    <span class="nx-toggle-slider"></span>
                </label>
                <div>
                    <div class="nx-label mb-0">Bật tích hợp SePay</div>
                    <div class="form-text mb-0">Khi bật, hệ thống sẽ tự động kiểm tra giao dịch</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-12">
                    <label class="nx-label">API Token</label>
                    <input type="password" class="nx-input" name="api_token" value="<?php echo htmlspecialchars($config['api_token'] ?? ''); ?>" placeholder="Nhập API Token">
                    <div class="form-text">Lấy tại <a href="https://my.sepay.vn/userapi/" target="_blank">my.sepay.vn</a></div>
                </div>
                <div class="col-md-4">
                    <label class="nx-label">Mã ngân hàng</label>
                    <select class="nx-input" name="bank_code">
                        <option value="">-- Chọn ngân hàng --</option>
                        <?php foreach ($sepayBanks as $code => $bank): ?>
                            <option value="<?php echo $code; ?>" <?php echo ($config && $config['bank_code'] === $code) ? 'selected' : ''; ?>>
                                <?php echo $bank['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="nx-label">Số tài khoản</label>
                    <input type="text" class="nx-input" name="account_number" value="<?php echo htmlspecialchars($config['account_number'] ?? ''); ?>" placeholder="VD: 1234567890">
                </div>
                <div class="col-md-4">
                    <label class="nx-label">Tên tài khoản</label>
                    <input type="text" class="nx-input" name="account_holder" value="<?php echo htmlspecialchars($config['account_holder'] ?? ''); ?>" placeholder="VD: NGUYEN VAN A">
                </div>
            </div>
        </div>
    </div>

    <!-- Processing Options -->
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <h5 class="mb-0"><i class="fa-solid fa-gear me-2"></i>Tùy chọn xử lý</h5>
        </div>
        <div class="nx-card-body">
            <div class="toggle-wrapper mb-3">
                <label class="toggle-switch">
                    <input type="checkbox" name="auto_process" value="1" <?php echo ($config && $config['auto_process'] == 1) ? 'checked' : ''; ?>>
                    <span class="nx-toggle-slider"></span>
                </label>
                <div>
                    <div class="nx-label mb-0">Tự động xử lý nạp tiền</div>
                    <div class="form-text mb-0">Tự động cộng tiền khi có giao dịch khớp mã nạp</div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="nx-label">Prefix nội dung CK</label>
                    <input type="text" class="nx-input" name="transfer_prefix" value="<?php echo htmlspecialchars($config['transfer_prefix'] ?? 'NT'); ?>" placeholder="NT" maxlength="20">
                </div>
                <div class="col-md-4">
                    <label class="nx-label">Kiểm tra mỗi (phút)</label>
                    <input type="number" class="nx-input" name="check_interval_minutes" value="<?php echo intval($config['check_interval_minutes'] ?? 5); ?>" min="1" max="60">
                </div>
                <div class="col-md-4">
                    <label class="nx-label">Hủy sau (phút)</label>
                    <input type="number" class="nx-input" name="cancel_after_minutes" value="<?php echo intval($config['cancel_after_minutes'] ?? 30); ?>" min="5" max="1440">
                </div>
                <div class="col-md-6">
                    <label class="nx-label">Số tiền tối thiểu (đ)</label>
                    <input type="number" class="nx-input" name="min_amount" value="<?php echo intval($config['min_amount'] ?? 10000); ?>" min="1000" step="1000">
                </div>
                <div class="col-md-6">
                    <label class="nx-label">Số tiền tối đa (đ)</label>
                    <input type="number" class="nx-input" name="max_amount" value="<?php echo intval($config['max_amount'] ?? 500000000); ?>" min="1000" step="1000">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="nx-btn nx-btn-primary px-4">
            <i class="fa-solid fa-check me-1"></i> Lưu cấu hình
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
admin_renderLayout('Cấu hình SePay', 'sepay-config');
?>
