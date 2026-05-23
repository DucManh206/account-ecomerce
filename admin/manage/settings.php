<?php
require_once __DIR__ . "/../../admin_lib/admin_layout_modules.php";
require_once __DIR__ . "/../../lib/settings_modules.php";

$success = false;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}

$settings = getAllSettings();

$storeName = $settings['store_name'] ?? 'NEXUS STORE';
$storeIcon = $settings['store_icon'] ?? 'fa-ghost';
$storeEmail = $settings['store_email'] ?? '';
$transactionFee = $settings['transaction_fee'] ?? 0;
$autoHide = $settings['auto_hide_out_of_stock'] ?? false;
$emailEnabled = $settings['email_notifications_enabled'] ?? false;

ob_start();
?>
<?php if ($success): ?>
<div class="saved-badge success mb-3" id="savedBadge">
    <i class="fa-solid fa-check-circle"></i>
    Đã lưu cấu hình thành công!
</div>
<script>setTimeout(() => { const b = document.getElementById('savedBadge'); if(b) b.remove(); }, 3000);</script>
<?php endif; ?>

<form method="POST" action="settings.php">
    <!-- Cấu hình chung -->
    <div class="settings-card">
        <div class="settings-section-title">
            <i class="fa-solid fa-gear"></i> Cấu hình chung
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label">Tên cửa hàng</label>
                <input type="text" class="form-control" name="store_name" value="<?php echo htmlspecialchars($storeName); ?>" placeholder="VD: NEXUS STORE" required>
                <div class="form-text">Tên hiển thị trên navbar và tiêu đề trình duyệt</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email liên hệ</label>
                <input type="email" class="form-control" name="store_email" value="<?php echo htmlspecialchars($storeEmail); ?>" placeholder="contact@example.com">
                <div class="form-text">Email nhận thông báo đơn hàng (hiện tại: tắt)</div>
            </div>
        </div>
    </div>

    <!-- Icon & Giao diện -->
    <div class="settings-card">
        <div class="settings-section-title">
            <i class="fa-solid fa-palette"></i> Giao diện
        </div>
        <label class="form-label mb-3">Icon cửa hàng (navbar)</label>
        <div class="icon-grid mb-3" id="iconGrid">
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
                <label class="icon-option <?php echo $storeIcon === $icon ? 'selected' : ''; ?>" title="<?php echo $label; ?>" onclick="selectIconOption(this)">
                    <i class="fa-solid <?php echo $icon; ?>"></i>
                    <input type="radio" name="store_icon" value="<?php echo $icon; ?>" <?php echo $storeIcon === $icon ? 'checked' : ''; ?> required>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tài chính -->
    <div class="settings-card">
        <div class="settings-section-title">
            <i class="fa-solid fa-coins"></i> Tài chính
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label">Phí giao dịch (đ)</label>
                <input type="number" class="form-control" name="transaction_fee" value="<?php echo intval($transactionFee); ?>" min="0" placeholder="0">
                <div class="form-text">Phí thêm vào mỗi giao dịch (VNĐ). Đặt 0 để tắt.</div>
            </div>
        </div>
    </div>

    <!-- Sản phẩm -->
    <div class="settings-card">
        <div class="settings-section-title">
            <i class="fa-solid fa-box"></i> Sản phẩm
        </div>
        <div class="toggle-wrapper mb-3">
            <label class="toggle-switch">
                <input type="checkbox" name="auto_hide_out_of_stock" value="1" <?php echo $autoHide ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
            </label>
            <div>
                <div class="form-label mb-0">Tự động ẩn sản phẩm hết hàng</div>
                <div class="form-text mb-0">Khi bật, sản phẩm có 0 tài khoản trong kho sẽ không hiển thị ở trang chủ</div>
            </div>
        </div>
    </div>

    <!-- Thông báo -->
    <div class="settings-card">
        <div class="settings-section-title">
            <i class="fa-solid fa-bell"></i> Thông báo
        </div>
        <div class="toggle-wrapper mb-3">
            <label class="toggle-switch">
                <input type="checkbox" name="email_notifications_enabled" value="1" <?php echo $emailEnabled ? 'checked' : ''; ?>>
                <span class="toggle-slider"></span>
            </label>
            <div>
                <div class="form-label mb-0">Bật thông báo email</div>
                <div class="form-text mb-0">Gửi email khi có đơn hàng mới hoặc yêu cầu nạp tiền</div>
            </div>
        </div>
        <div class="alert alert-warning py-2 px-3" style="font-size:0.85rem;">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            Tính năng email đang tạm bỏ qua. Cấu hình SMTP chưa được thiết lập.
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary px-4">
            <i class="fa-solid fa-check me-1"></i> Lưu cấu hình
        </button>
    </div>
</form>

<script>
function selectIconOption(el) {
    document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input').checked = true;
}
</script>

<?php
$content = ob_get_clean();
admin_renderLayout('Cấu hình hệ thống', 'settings');
