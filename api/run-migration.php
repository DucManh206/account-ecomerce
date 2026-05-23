<?php
/**
 * Migration Runner - HTTP endpoint
 * Chạy: http://localhost/api/run-migration.php
 */
require_once __DIR__ . '/../database/connect.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Running Migrations...</h2>";
echo "<style>body{font-family:monospace;background:#0f0f1e;color:#fafafa;padding:20px;} a{color:#A78BFA;} h2{color:#A78BFA;}</style>";

// account_field_types table
$r = mysqli_query($conn, "SHOW TABLES LIKE 'account_field_types'");
if (mysqli_num_rows($r) == 0) {
    $ok = mysqli_query($conn, "CREATE TABLE `account_field_types` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `key` VARCHAR(50) NOT NULL UNIQUE,
        `label` VARCHAR(100) NOT NULL,
        `icon_class` VARCHAR(50) DEFAULT 'fa-key',
        `placeholder` VARCHAR(100) DEFAULT '',
        `is_default` TINYINT(1) DEFAULT 0,
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo $ok ? "<p>[OK] account_field_types table created</p>" : "<p style='color:red'>[FAIL] " . mysqli_error($conn) . "</p>";
} else {
    echo "<p>[SKIP] account_field_types already exists</p>";
}

// Seed default field types
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM account_field_types");
if ($r && mysqli_fetch_assoc($r)["c"] == 0) {
    $fields = [
        ['account', 'Tài khoản', 'fa-user', 'VD: acc@gmail.com', 1, 1],
        ['password', 'Mật khẩu', 'fa-lock', 'VD: Matkhau123!', 1, 2],
        ['email', 'Email', 'fa-envelope', 'VD: email@example.com', 0, 3],
        ['pass', 'Pass', 'fa-key', 'VD: Pass123', 0, 4],
        ['cookie', 'Cookie', 'fa-cookie-bite', 'VD: abc123...', 0, 5],
        ['token', 'Token', 'fa-fingerprint', 'VD: eyJhbGc...', 0, 6],
        ['key', 'Key', 'fa-key', 'VD: XXXX-XXXX', 0, 7],
        ['pin', 'PIN', 'fa-hashtag', 'VD: 123456', 0, 8],
        ['2fa', '2FA', 'fa-shield-halved', 'VD: ABC123', 0, 9],
        ['note', 'Ghi chú', 'fa-sticky-note', 'VD: Thông tin thêm...', 0, 10],
        ['link', 'Link', 'fa-link', 'VD: https://...', 0, 11],
        ['code', 'Mã', 'fa-barcode', 'VD: ABC123456', 0, 12],
    ];
    foreach ($fields as $f) {
        mysqli_query($conn, "INSERT INTO account_field_types (`key`, label, icon_class, placeholder, is_default, sort_order) VALUES ('" . mysqli_real_escape_string($conn, $f[0]) . "','" . mysqli_real_escape_string($conn, $f[1]) . "','" . mysqli_real_escape_string($conn, $f[2]) . "','" . mysqli_real_escape_string($conn, $f[3]) . "',$f[4],$f[5])");
    }
    echo "<p>[OK] Seeded " . count($fields) . " field types</p>";
} else {
    echo "<p>[SKIP] Field types already seeded</p>";
}

echo "<hr><p><a href='../index.php'>Quay về trang chủ</a></p>";
?>
