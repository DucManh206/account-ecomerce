<?php
require_once __DIR__ . "/../lib/admin_layout_modules.php";
require_once __DIR__ . "/../../lib/settings_modules.php";
require_once __DIR__ . "/../../lib/sepay_modules.php";

$success = false;
$errorMsg = '';
$activeTab = $_GET['tab'] ?? 'general';

// Xử lý lưu cấu hình chung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_general') {
    $fields = [
        'store_name',
        'store_icon',
        'store_email',
        'transaction_fee',
        'auto_hide_out_of_stock',
        'email_notifications_enabled',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $val = $_POST[$field];
            setSetting($field, $val);
        }
    }

    $success = true;
    $activeTab = 'general';
}

// Xử lý lưu cấu hình SePay
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_sepay') {
    $sepayData = [
        'api_token' => trim($_POST['api_token'] ?? ''),
        'account_number' => trim($_POST['account_number'] ?? ''),
        'account_holder' => trim($_POST['account_holder'] ?? ''),
        'bank_code' => trim($_POST['bank_code'] ?? ''),
        'auto_process' => isset($_POST['auto_process']) ? 1 : 0,
        'min_amount' => intval($_POST['min_amount'] ?? 10000),
        'max_amount' => intval($_POST['max_amount'] ?? 500000000),
        'webhook_secret' => trim($_POST['webhook_secret'] ?? ''),
        'status' => isset($_POST['sepay_enabled']) ? 1 : 0,
    ];
    
    sepay_saveConfig($sepayData);
    $success = true;
    $activeTab = 'sepay';
}

// Xử lý test API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_sepay') {
    $result = sepay_getTransactions(['limit' => 5]);
    if (isset($result['error'])) {
        $errorMsg = 'Lỗi: ' . $result['error'];
    } else {
        $success = true;
        $sepaySuccessMsg = 'Kết nối thành công! Tìm thấy ' . count($result['transactions'] ?? []) . ' giao dịch gần nhất.';
    }
    $activeTab = 'sepay';
}

// Xử lý sync thủ công
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_sepay') {
    $result = sepay_syncAndProcess(100);
    if ($result['success']) {
        $success = true;
        $sepaySuccessMsg = 'Đã đồng bộ: ' . $result['saved'] . ' giao dịch mới, xử lý: ' . $result['processed'] . ' giao dịch.';
    } else {
        $errorMsg = 'Lỗi: ' . ($result['message'] ?? 'Unknown error');
    }
    $activeTab = 'sepay';
}

$settings = getAllSettings();

$storeName = $settings['store_name'] ?? 'NEXUS STORE';
$storeIcon = $settings['store_icon'] ?? 'fa-ghost';
$storeEmail = $settings['store_email'] ?? '';
$transactionFee = $settings['transaction_fee'] ?? 0;
$autoHide = $settings['auto_hide_out_of_stock'] ?? false;
$emailEnabled = $settings['email_notifications_enabled'] ?? false;

// Lấy cấu hình SePay
$sepayConfig = sepay_getConfig();
$sepayStats = sepay_getStats();
$sepayBanks = sepay_getSupportedBanks();

ob_start();
?>

<?php if ($success && isset($sepaySuccessMsg)): ?>
<div class="nx-alert nx-alert-success mb-3">
    <i class="fa-solid fa-check-circle"></i> <?php echo $sepaySuccessMsg; ?>
</div>
<?php elseif ($success): ?>
<div class="nx-alert nx-alert-success mb-3">
    <i class="fa-solid fa-check-circle"></i> Đã lưu cấu hình thành công!
</div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="nx-alert nx-alert-danger mb-3">
    <i class="fa-solid fa-circle-xmark"></i> <?php echo $errorMsg; ?>
</div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nx-settings-tabs nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'general' ? 'active' : ''; ?>" href="?tab=general">
            <i class="fa-solid fa-gear me-1"></i> Cấu hình chung
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'sepay' ? 'active' : ''; ?>" href="?tab=sepay">
            <i class="fa-solid fa-university me-1"></i> SePay
            <?php if ($sepayConfig && $sepayConfig['status'] == 1): ?>
                <span class="nx-badge nx-badge-success ms-2">ON</span>
            <?php else: ?>
                <span class="nx-badge nx-badge-secondary ms-2">OFF</span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<!-- Tab: General Settings -->
<div class="tab-content <?php echo $activeTab !== 'general' ? 'd-none' : ''; ?>" id="generalTab">
    <form method="POST" action="settings.php">
        <input type="hidden" name="action" value="save_general">
        
        <!-- Cấu hình chung -->
        <div class="nx-settings-card">
            <div class="nx-settings-section">
                <i class="fa-solid fa-gear"></i> Cấu hình chung
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="nx-label">Tên cửa hàng</label>
                    <input type="text" class="nx-input" name="store_name" value="<?php echo htmlspecialchars($storeName); ?>" placeholder="VD: NEXUS STORE" required>
                    <div class="form-text">Tên hiển thị trên navbar và tiêu đề trình duyệt</div>
                </div>
                <div class="col-md-6">
                    <label class="nx-label">Email liên hệ</label>
                    <input type="email" class="nx-input" name="store_email" value="<?php echo htmlspecialchars($storeEmail); ?>" placeholder="contact@example.com">
                    <div class="form-text">Email nhận thông báo đơn hàng (hiện tại: tắt)</div>
                </div>
            </div>
        </div>

        <!-- Icon & Giao diện -->
        <div class="nx-settings-card">
            <div class="nx-settings-section">
                <i class="fa-solid fa-palette"></i> Giao diện
            </div>
            <label class="nx-label mb-3">Icon cửa hàng (navbar)</label>
            <div class="nx-icon-grid mb-3" id="iconGrid">
                <?php
                $icons = [
                    'fa-ghost' => 'Ghost',
                    'fa-store' => 'Store',
                    'fa-shop' => 'Shop',
                    'fa-bag-shopping' => 'Bag',
                    'fa-cart-shopping' => 'Cart',
                    'fa-box-open' => 'Box',
                    'fa-gamepad' => 'Game',
                    'fa-fire' => 'Fire',
                    'fa-bolt' => 'Bolt',
                    'fa-rocket' => 'Rocket',
                    'fa-gem' => 'Gem',
                    'fa-star' => 'Star',
                    'fa-crown' => 'Crown',
                    'fa-diamond' => 'Diamond',
                    'fa-fire-flame-curved' => 'Flame',
                    'fa-sparkles' => 'Sparkle',
                    'fa-shield-halved' => 'Shield',
                    'fa-skull-crossbones' => 'Skull',
                    'fa-dragon' => 'Dragon',
                    'fa-robot' => 'Robot',
                    'fa-n' => 'N',
                    'fa-spotify' => 'Spotify',
                    'fa-youtube' => 'YouTube',
                    'fa-play' => 'Play',
                ];
                foreach ($icons as $icon => $label): ?>
                    <label class="nx-icon-option <?php echo $storeIcon === $icon ? 'selected' : ''; ?>" title="<?php echo $label; ?>" onclick="selectIconOption(this)">
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                        <input type="radio" name="store_icon" value="<?php echo $icon; ?>" <?php echo $storeIcon === $icon ? 'checked' : ''; ?> required>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tài chính -->
        <div class="nx-settings-card">
            <div class="nx-settings-section">
                <i class="fa-solid fa-coins"></i> Tài chính
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="nx-label">Phí giao dịch (đ)</label>
                    <input type="number" class="nx-input" name="transaction_fee" value="<?php echo intval($transactionFee); ?>" min="0" placeholder="0">
                    <div class="form-text">Phí thêm vào mỗi giao dịch (VNĐ). Đặt 0 để tắt.</div>
                </div>
            </div>
        </div>

        <!-- Sản phẩm -->
        <div class="nx-settings-card">
            <div class="nx-settings-section">
                <i class="fa-solid fa-box"></i> Sản phẩm
            </div>
            <div class="toggle-wrapper mb-3">
                <label class="toggle-switch">
                    <input type="checkbox" name="auto_hide_out_of_stock" value="1" <?php echo $autoHide ? 'checked' : ''; ?>>
                    <span class="nx-toggle-slider"></span>
                </label>
                <div>
                    <div class="nx-label mb-0">Tự động ẩn sản phẩm hết hàng</div>
                    <div class="form-text mb-0">Khi bật, sản phẩm có 0 tài khoản trong kho sẽ không hiển thị ở trang chủ</div>
                </div>
            </div>
        </div>

        <!-- Thông báo -->
        <div class="nx-settings-card">
            <div class="nx-settings-section">
                <i class="fa-solid fa-bell"></i> Thông báo
            </div>
            <div class="toggle-wrapper mb-3">
                <label class="toggle-switch">
                    <input type="checkbox" name="email_notifications_enabled" value="1" <?php echo $emailEnabled ? 'checked' : ''; ?>>
                    <span class="nx-toggle-slider"></span>
                </label>
                <div>
                    <div class="nx-label mb-0">Bật thông báo email</div>
                    <div class="form-text mb-0">Gửi email khi có đơn hàng mới hoặc yêu cầu nạp tiền</div>
                </div>
            </div>
            <div class="nx-alert nx-alert-warning py-2 px-3" style="font-size:0.85rem;">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                Tính năng email đang tạm bỏ qua. Cấu hình SMTP chưa được thiết lập.
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="nx-btn nx-btn-primary px-4">
                <i class="fa-solid fa-check me-1"></i> Lưu cấu hình
            </button>
        </div>
    </form>
</div>

<!-- Tab: SePay Settings -->
<div class="tab-content <?php echo $activeTab !== 'sepay' ? 'd-none' : ''; ?>" id="sepayTab">
    <form method="POST" action="settings.php">
        <input type="hidden" name="action" value="save_sepay">
        
        <!-- SePay Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="nx-stat-card" style="background: linear-gradient(135deg, #6E56CF, #4F46E5); color: white;">
                    <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="nx-stat-value"><?php echo number_format($sepayStats['total_transactions']); ?></div>
                    <div class="nx-stat-label">Tổng giao dịch</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="nx-stat-card" style="background: linear-gradient(135deg, #F59E0B, #D97706); color: white;">
                    <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="nx-stat-value"><?php echo number_format($sepayStats['pending_count']); ?></div>
                    <div class="nx-stat-label">Đang chờ xử lý</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="nx-stat-card" style="background: linear-gradient(135deg, #10B981, #059669); color: white;">
                    <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div class="nx-stat-value"><?php echo number_format($sepayStats['matched_count']); ?></div>
                    <div class="nx-stat-label">Đã xử lý</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="nx-stat-card" style="background: linear-gradient(135deg, #38BDF8, #0EA5E9); color: white;">
                    <div class="nx-stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div class="nx-stat-value"><?php echo number_format($sepayStats['today_amount'], 0, ',', '.'); ?>đ</div>
                    <div class="nx-stat-label">Hôm nay</div>
                </div>
            </div>
        </div>

        <!-- SePay Configuration -->
        <div class="nx-settings-card mb-4">
            <div class="nx-settings-section">
                <i class="fa-solid fa-university"></i> Cấu hình SePay
            </div>
            
            <div class="toggle-wrapper mb-4">
                <label class="toggle-switch">
                    <input type="checkbox" name="sepay_enabled" value="1" <?php echo ($sepayConfig && $sepayConfig['status'] == 1) ? 'checked' : ''; ?>>
                    <span class="nx-toggle-slider"></span>
                </label>
                <div>
                    <div class="nx-label mb-0">Bật tích hợp SePay</div>
                    <div class="form-text mb-0">Khi bật, hệ thống sẽ tự động kiểm tra và xử lý giao dịch nạp tiền</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-12">
                    <label class="nx-label">API Token</label>
                    <input type="password" class="nx-input" name="api_token" value="<?php echo htmlspecialchars($sepayConfig['api_token'] ?? ''); ?>" placeholder="Nhập API Token từ SePay">
                    <div class="form-text">Lấy API Token từ <a href="https://my.sepay.vn/userapi/" target="_blank">my.sepay.vn</a> → Cài đặt → API Token</div>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <label class="nx-label">Mã ngân hàng</label>
                    <select class="nx-input" name="bank_code">
                        <option value="">-- Chọn ngân hàng --</option>
                        <?php foreach ($sepayBanks as $code => $bank): ?>
                            <option value="<?php echo $code; ?>" <?php echo ($sepayConfig && $sepayConfig['bank_code'] === $code) ? 'selected' : ''; ?>>
                                <?php echo $bank['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="nx-label">Số tài khoản</label>
                    <input type="text" class="nx-input" name="account_number" value="<?php echo htmlspecialchars($sepayConfig['account_number'] ?? ''); ?>" placeholder="VD: 1234567890">
                </div>
                <div class="col-md-4">
                    <label class="nx-label">Tên tài khoản</label>
                    <input type="text" class="nx-input" name="account_holder" value="<?php echo htmlspecialchars($sepayConfig['account_holder'] ?? ''); ?>" placeholder="VD: NGUYEN VAN A">
                </div>
            </div>
        </div>

        <!-- SePay Processing Options -->
        <div class="nx-settings-card mb-4">
            <div class="nx-settings-section">
                <i class="fa-solid fa-gear"></i> Tùy chọn xử lý
            </div>

            <div class="toggle-wrapper mb-3">
                <label class="toggle-switch">
                    <input type="checkbox" name="auto_process" value="1" <?php echo ($sepayConfig && $sepayConfig['auto_process'] == 1) ? 'checked' : ''; ?>>
                    <span class="nx-toggle-slider"></span>
                </label>
                <div>
                    <div class="nx-label mb-0">Tự động xử lý nạp tiền</div>
                    <div class="form-text mb-0">Khi bật, giao dịch khớp sẽ tự động được xử lý. Nội dung chuyển khoản phải có format: NapTien {user_id}</div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="nx-label">Số tiền tối thiểu (đ)</label>
                    <input type="number" class="nx-input" name="min_amount" value="<?php echo intval($sepayConfig['min_amount'] ?? 10000); ?>" min="1000" step="1000">
                    <div class="form-text">Giao dịch nhỏ hơn sẽ không được xử lý</div>
                </div>
                <div class="col-md-6">
                    <label class="nx-label">Số tiền tối đa (đ)</label>
                    <input type="number" class="nx-input" name="max_amount" value="<?php echo intval($sepayConfig['max_amount'] ?? 500000000); ?>" min="1000" step="1000">
                    <div class="form-text">Giao dịch lớn hơn sẽ không được xử lý</div>
                </div>
            </div>
        </div>

        <!-- Webhook Info -->
        <div class="nx-settings-card mb-4">
            <div class="nx-settings-section">
                <i class="fa-solid fa-webhook"></i> Webhook URL
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="nx-alert nx-alert-info mb-3">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        Thêm URL webhook bên dưới vào SePay Dashboard để nhận thông báo giao dịch tự động.
                    </div>
                    <div class="input-group">
                        <input type="text" class="nx-input" id="webhookUrl" value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/api/sepay-webhook.php" readonly>
                        <button type="button" class="nx-btn nx-btn-secondary" onclick="copyWebhookUrl()">
                            <i class="fa-solid fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <button type="submit" class="nx-btn nx-btn-primary px-4">
                <i class="fa-solid fa-check me-1"></i> Lưu cấu hình
            </button>
        </div>
    </form>

    <!-- Test & Sync Section -->
    <div class="nx-settings-card mt-4">
        <div class="nx-settings-section">
            <i class="fa-solid fa-bolt"></i> Thao tác nhanh
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="test_sepay">
                <button type="submit" class="nx-btn nx-btn-secondary">
                    <i class="fa-solid fa-plug me-1"></i> Kiểm tra kết nối
                </button>
            </form>
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="sync_sepay">
                <button type="submit" class="nx-btn nx-btn-warning">
                    <i class="fa-solid fa-sync me-1"></i> Đồng bộ giao dịch
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function selectIconOption(el) {
    document.querySelectorAll('.nx-icon-option').forEach(opt => opt.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input').checked = true;
}

function copyWebhookUrl() {
    const url = document.getElementById('webhookUrl').value;
    navigator.clipboard.writeText(url).then(function() {
        alert('Đã copy webhook URL!');
    }, function(err) {
        console.error('Lỗi copy: ', err);
    });
}
</script>

<?php
$content = ob_get_clean();
admin_renderLayout('Cấu hình hệ thống', 'settings');
