<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../../includes/flash.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $values = [
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_short_name' => trim($_POST['site_short_name'] ?? ''),
        'site_tagline' => trim($_POST['site_tagline'] ?? ''),
        'storefront_heading' => trim($_POST['storefront_heading'] ?? ''),
        'storefront_description' => trim($_POST['storefront_description'] ?? ''),
        'storefront_notice' => trim($_POST['storefront_notice'] ?? ''),
        'support_contact' => trim($_POST['support_contact'] ?? ''),
        'storefront_page_size' => (string) max(6, min(48, (int) ($_POST['storefront_page_size'] ?? 12))),
        'admin_page_size' => (string) max(10, min(100, (int) ($_POST['admin_page_size'] ?? 20))),
        'low_stock_threshold' => (string) max(1, min(100, (int) ($_POST['low_stock_threshold'] ?? 3))),
        'min_topup_amount' => (string) max(1000, (int) ($_POST['min_topup_amount'] ?? 10000)),
        'max_topup_amount' => (string) max(1000, (int) ($_POST['max_topup_amount'] ?? 100000000)),
        'topup_expiry_minutes' => (string) max(5, min(60, (int) ($_POST['topup_expiry_minutes'] ?? 15))),
    ];

    $presetValues = array_values(array_unique(array_filter(
        array_map('intval', preg_split('/[\s,;]+/', trim($_POST['topup_presets'] ?? ''))),
        fn($value) => $value > 0
    )));
    sort($presetValues);
    $values['topup_presets'] = implode(',', $presetValues);

    $errors = [];
    foreach (['site_name', 'site_short_name', 'storefront_heading', 'storefront_description'] as $requiredKey) {
        if ($values[$requiredKey] === '') {
            $errors[] = 'Các trường tên cửa hàng và nội dung trang chủ không được để trống.';
            break;
        }
    }
    if ((int) $values['max_topup_amount'] < (int) $values['min_topup_amount']) {
        $errors[] = 'Số tiền nạp tối đa phải lớn hơn hoặc bằng số tiền tối thiểu.';
    }
    if (!$presetValues) {
        $errors[] = 'Cần ít nhất một mệnh giá nạp nhanh.';
    }
    foreach ($presetValues as $preset) {
        if ($preset < (int) $values['min_topup_amount'] || $preset > (int) $values['max_topup_amount']) {
            $errors[] = 'Mệnh giá nạp nhanh phải nằm trong giới hạn nạp tiền.';
            break;
        }
    }

    if ($errors) {
        set_flash('error', implode(' ', array_unique($errors)));
    } else {
        try {
            $pdo->beginTransaction();
            save_app_settings($pdo, $values);
            record_admin_activity(
                $pdo,
                'settings_updated',
                'settings',
                null,
                'Cập nhật cấu hình cửa hàng và quy tắc vận hành',
                ['keys' => array_keys($values)]
            );
            $pdo->commit();
            set_flash('success', 'Đã lưu cấu hình cửa hàng. Các trang sẽ dùng dữ liệu mới ngay từ lần tải tiếp theo.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('save general settings: ' . $e->getMessage());
            set_flash('error', 'Không thể lưu cấu hình lúc này.');
        }
    }

    header('Location: general.php');
    exit;
}

$value = fn(string $key, string $fallback = '') => htmlspecialchars((string) app_setting($key, $fallback));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt cửa hàng - <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../../../assets/admin/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../../sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar admin-page-heading">
            <div>
                <span class="eyebrow">Cấu hình động</span>
                <h1>Cài đặt cửa hàng</h1>
                <p>Thay đổi nội dung hiển thị và quy tắc vận hành mà không cần sửa mã nguồn.</p>
            </div>
            <a class="btn btn-secondary" href="<?= BASE_PATH ?>index.php" target="_blank" rel="noopener">Xem cửa hàng</a>
        </header>

        <div class="content-body">
            <?= render_flash() ?>
            <form method="POST" class="settings-layout">
                <?= csrf_field() ?>

                <section class="form-card settings-section">
                    <div class="section-heading">
                        <span class="section-index">01</span>
                        <div><h2>Nhận diện cửa hàng</h2><p>Tên và thông điệp dùng ở thanh điều hướng, tiêu đề và footer.</p></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="site_short_name">Tên ngắn</label>
                            <input id="site_short_name" name="site_short_name" maxlength="40" required value="<?= $value('site_short_name', 'AccountShop') ?>">
                        </div>
                        <div class="form-group">
                            <label for="site_name">Tên đầy đủ / SEO title</label>
                            <input id="site_name" name="site_name" maxlength="120" required value="<?= $value('site_name', DEFAULT_SITE_NAME) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="site_tagline">Dòng mô tả ngắn</label>
                        <input id="site_tagline" name="site_tagline" maxlength="160" value="<?= $value('site_tagline') ?>">
                    </div>
                    <div class="form-group">
                        <label for="support_contact">Kênh hỗ trợ</label>
                        <input id="support_contact" name="support_contact" maxlength="160" placeholder="Email, Zalo hoặc URL hỗ trợ" value="<?= $value('support_contact') ?>">
                    </div>
                </section>

                <section class="form-card settings-section">
                    <div class="section-heading">
                        <span class="section-index">02</span>
                        <div><h2>Nội dung trang chủ</h2><p>Admin có thể đổi nội dung hero và thông báo vận hành.</p></div>
                    </div>
                    <div class="form-group">
                        <label for="storefront_heading">Tiêu đề chính</label>
                        <input id="storefront_heading" name="storefront_heading" maxlength="120" required value="<?= $value('storefront_heading') ?>">
                    </div>
                    <div class="form-group">
                        <label for="storefront_description">Mô tả</label>
                        <textarea id="storefront_description" name="storefront_description" rows="3" maxlength="320" required><?= $value('storefront_description') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="storefront_notice">Thông báo cửa hàng</label>
                        <textarea id="storefront_notice" name="storefront_notice" rows="2" maxlength="240" placeholder="Để trống nếu không cần hiển thị"><?= $value('storefront_notice') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="storefront_page_size">Sản phẩm mỗi trang</label>
                            <input type="number" id="storefront_page_size" name="storefront_page_size" min="6" max="48" value="<?= $value('storefront_page_size', '12') ?>">
                        </div>
                        <div class="form-group">
                            <label for="admin_page_size">Bản ghi mỗi trang quản trị</label>
                            <input type="number" id="admin_page_size" name="admin_page_size" min="10" max="100" value="<?= $value('admin_page_size', '20') ?>">
                        </div>
                        <div class="form-group">
                            <label for="low_stock_threshold">Ngưỡng cảnh báo sắp hết hàng</label>
                            <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="1" max="100" value="<?= $value('low_stock_threshold', '3') ?>">
                        </div>
                    </div>
                </section>

                <section class="form-card settings-section">
                    <div class="section-heading">
                        <span class="section-index">03</span>
                        <div><h2>Quy tắc nạp tiền</h2><p>Giới hạn và mệnh giá được dùng trực tiếp ở trang nạp tiền.</p></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="min_topup_amount">Số tiền tối thiểu</label>
                            <input type="number" id="min_topup_amount" name="min_topup_amount" min="1000" step="1000" value="<?= $value('min_topup_amount', '10000') ?>">
                        </div>
                        <div class="form-group">
                            <label for="max_topup_amount">Số tiền tối đa</label>
                            <input type="number" id="max_topup_amount" name="max_topup_amount" min="1000" step="1000" value="<?= $value('max_topup_amount', '100000000') ?>">
                        </div>
                        <div class="form-group">
                            <label for="topup_expiry_minutes">Hiệu lực yêu cầu (phút)</label>
                            <input type="number" id="topup_expiry_minutes" name="topup_expiry_minutes" min="5" max="60" value="<?= $value('topup_expiry_minutes', '15') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="topup_presets">Mệnh giá nạp nhanh</label>
                        <input id="topup_presets" name="topup_presets" value="<?= $value('topup_presets', '20000,50000,100000,200000,500000,1000000') ?>" placeholder="20000, 50000, 100000">
                        <small>Nhập các số, phân cách bằng dấu phẩy. Các mệnh giá phải nằm trong giới hạn trên.</small>
                    </div>
                </section>

                <div class="sticky-form-actions">
                    <span>Thay đổi sẽ áp dụng sau khi tải lại trang.</span>
                    <button type="submit" class="btn btn-primary">Lưu cấu hình</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
