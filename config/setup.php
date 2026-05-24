<?php
/**
 * NEXUS STORE 
 *
 * Chi can mo: http://localhost/migrations/setup.php
 */

require_once __DIR__ . '/../config/db.php';

echo "<html><head><meta charset=\"UTF-8\"><title>Nexus Store Setup</title>";
echo "<style>
body{font-family:-apple-system,sans-serif;padding:32px;background:#0a0a0f;color:#e2e8f0;max-width:900px;margin:0 auto}
h1{color:#a78bfa;font-size:1.75rem;margin-bottom:8px}
.sub{color:#64748b;font-size:0.9rem;margin-bottom:32px}
h2{color:#818cf8;font-size:1rem;margin-top:32px;border-bottom:1px solid #1e293b;padding-bottom:8px}
.ok{color:#34d399}.skip{color:#475569}.err{color:#f87171}
.info{color:#fbbf24}
pre{background:#0f172a;border:1px solid #1e293b;border-radius:8px;padding:16px;margin:8px 0;font-size:0.8rem;color:#94a3b8;max-height:300px;overflow:auto}
.summary{background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;margin-top:32px}
.summary h2{color:#a78bfa;border:none;margin-top:0}
.summary p{margin:6px 0;font-size:0.95rem}
a.btn{display:inline-block;background:#6d28d9;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;margin-top:16px;margin-right:8px}
a.btn:hover{background:#5b21b6}
a.btn.green{background:#059669}
a.btn.green:hover{background:#047857}
</style></head><body>";
echo "<h1>NEXUS STORE</h1>";
echo "<p class=\"sub\">Database Seed</p>";

$results = array();

function ce($t, $c) {
    global $conn;
    $r = mysqli_query($conn, "SHOW COLUMNS FROM `$t` LIKE '" . $conn->real_escape_string($c) . "'");
    return $r && mysqli_num_rows($r) > 0;
}
function te($t) {
    global $conn;
    $r = mysqli_query($conn, "SHOW TABLES LIKE '" . $conn->real_escape_string($t) . "'");
    return $r && mysqli_num_rows($r) > 0;
}
function rs($sql, $desc) {
    global $conn, $results;
    $ok = mysqli_query($conn, $sql);
    if ($ok) {
        $results[] = array("ok", $desc);
        echo "<p class=\"ok\">[OK] " . htmlspecialchars($desc) . "</p>";
        return true;
    }
    $e = mysqli_error($conn);
    if (strpos($e, "Duplicate") !== false || strpos($e, "already exists") !== false) {
        $results[] = array("skip", $desc);
        echo "<p class=\"skip\">[SKIP] " . htmlspecialchars($desc) . " (exists)</p>";
        return true;
    }
    $results[] = array("err", "$desc: $e");
    echo "<p class=\"err\">[ERR] " . htmlspecialchars($desc) . "<br><pre>$e</pre></p>";
    return false;
}
function ac($t, $c, $a, $def) {
    global $conn;
    if (!ce($t, $c)) {
        $t2 = $conn->real_escape_string($t);
        $c2 = $conn->real_escape_string($c);
        $a2 = $conn->real_escape_string($a);
        return rs("ALTER TABLE `$t2` ADD COLUMN `$c2` $def AFTER `$a2`", "Add col $c to $t");
    }
    echo "<p class=\"skip\">[SKIP] col $c exists in $t</p>";
    $results[] = array("skip", "col $c exists");
    return true;
}
function ai($t, $idx, $col) {
    global $conn;
    $t2 = $conn->real_escape_string($t);
    $i2 = $conn->real_escape_string($idx);
    $c2 = $conn->real_escape_string($col);
    $r = mysqli_query($conn, "SHOW INDEX FROM `$t2` WHERE Key_name = '$i2'");
    if ($r && mysqli_num_rows($r) > 0) {
        echo "<p class=\"skip\">[SKIP] index $idx exists</p>";
        return true;
    }
    return rs("ALTER TABLE `$t2` ADD INDEX `$i2` (`$c2`)", "Add index $idx");
}
function esc($s) { global $conn; return $conn->real_escape_string($s); }
function iid() { global $conn; return mysqli_insert_id($conn); }

/* ================================================================
   1. CREATE TABLES (IF NOT EXISTS)
   ================================================================ */
echo "<h2>1. Schema Setup</h2>";

if (!te("users")) {
    rs("CREATE TABLE `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` TINYINT(1) NOT NULL DEFAULT 0,
        `balance` INT(11) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_role` (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table users");
}
if (!te("categories")) {
    rs("CREATE TABLE `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL UNIQUE,
        `icon_class` VARCHAR(50) DEFAULT 'fa-folder',
        `description` VARCHAR(255) DEFAULT '',
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table categories");
}
if (!te("types")) {
    rs("CREATE TABLE `types` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `category` VARCHAR(100) NOT NULL,
        `category_id` INT DEFAULT NULL,
        `icon_class` VARCHAR(50) DEFAULT 'fa-tag',
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_types_cat` (`category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table types");
}
if (!te("products")) {
    rs("CREATE TABLE `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `category` VARCHAR(100) NOT NULL,
        `game_type` VARCHAR(100) DEFAULT '',
        `type_id` INT DEFAULT NULL,
        `image_url` VARCHAR(255) NOT NULL,
        `price` INT NOT NULL DEFAULT 0,
        `old_price` INT DEFAULT 0,
        `badge` VARCHAR(50) DEFAULT '',
        `details` TEXT,
        `description` TEXT,
        `color_class` VARCHAR(50) DEFAULT 'bg-secondary',
        `icon_class` VARCHAR(50) DEFAULT 'fa-box',
        `gallery` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_prod_cat` (`category`),
        INDEX `idx_prod_type` (`type_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table products");
}
if (!te("account_stock")) {
    rs("CREATE TABLE `account_stock` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `account_data` TEXT NOT NULL,
        `status` ENUM('available','sold','reserved') NOT NULL DEFAULT 'available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `sold_at` TIMESTAMP NULL,
        `sold_to_user_id` INT DEFAULT NULL,
        INDEX `idx_stock_prod` (`product_id`),
        INDEX `idx_stock_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table account_stock");
}
if (!te("orders")) {
    rs("CREATE TABLE `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `product_id` INT DEFAULT NULL,
        `account_id` INT DEFAULT NULL,
        `account_data` TEXT,
        `price` INT NOT NULL DEFAULT 0,
        `status` ENUM('pending','processing','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_ord_user` (`user_id`),
        INDEX `idx_ord_prod` (`product_id`),
        INDEX `idx_ord_status` (`status`),
        INDEX `idx_ord_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table orders");
}
if (!te("transactions")) {
    rs("CREATE TABLE `transactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `amount` INT NOT NULL,
        `balance_before` INT NOT NULL DEFAULT 0,
        `balance_after` INT NOT NULL DEFAULT 0,
        `type` VARCHAR(50) NOT NULL,
        `description` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_tx_user` (`user_id`),
        INDEX `idx_tx_type` (`type`),
        INDEX `idx_tx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table transactions");
}
if (!te("cart")) {
    rs("CREATE TABLE `cart` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT DEFAULT NULL,
        `session_id` VARCHAR(100) DEFAULT NULL,
        `product_id` INT NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_cart_user` (`user_id`),
        INDEX `idx_cart_sess` (`session_id`),
        INDEX `idx_cart_prod` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table cart");
}
if (!te("banks")) {
    rs("CREATE TABLE `banks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `bank_name` VARCHAR(100) NOT NULL,
        `account_number` VARCHAR(50) NOT NULL,
        `account_holder` VARCHAR(100) NOT NULL,
        `icon_class` VARCHAR(50) DEFAULT 'fas fa-university',
        `sort_order` INT DEFAULT 0,
        `status` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table banks");
}
if (!te("deposit_requests")) {
    rs("CREATE TABLE `deposit_requests` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `username` VARCHAR(50) NOT NULL,
        `amount` INT NOT NULL,
        `transfer_amount` INT NOT NULL,
        `transfer_note` VARCHAR(100) DEFAULT NULL,
        `unique_code` VARCHAR(50) NOT NULL COMMENT 'Unique code for matching (prefix_id_timestamp)',
        `status` ENUM('pending','approved','rejected','expired','cancelled') NOT NULL DEFAULT 'pending',
        `admin_note` VARCHAR(255) DEFAULT NULL,
        `processed_by` INT DEFAULT NULL,
        `processed_at` TIMESTAMP NULL,
        `expires_at` TIMESTAMP NULL COMMENT 'When this request expires',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_dep_user` (`user_id`),
        INDEX `idx_dep_status` (`status`),
        INDEX `idx_dep_created` (`created_at`),
        INDEX `idx_dep_unique_code` (`unique_code`),
        INDEX `idx_dep_expires` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table deposit_requests");
}
if (!te("settings")) {
    rs("CREATE TABLE `settings` (
        `key` VARCHAR(100) PRIMARY KEY,
        `value` TEXT,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table settings");
}
if (!te("account_field_types")) {
    rs("CREATE TABLE `account_field_types` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `key` VARCHAR(50) NOT NULL UNIQUE,
        `label` VARCHAR(100) NOT NULL,
        `icon_class` VARCHAR(50) DEFAULT 'fa-key',
        `placeholder` VARCHAR(100) DEFAULT '',
        `is_default` TINYINT(1) DEFAULT 0,
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table account_field_types");
}

// SePay Configuration
if (!te("sepay_config")) {
    rs("CREATE TABLE `sepay_config` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `api_token` VARCHAR(255) NOT NULL DEFAULT '',
        `account_number` VARCHAR(50) NOT NULL DEFAULT '',
        `account_holder` VARCHAR(100) NOT NULL DEFAULT '',
        `bank_code` VARCHAR(20) NOT NULL DEFAULT '',
        `auto_process` TINYINT(1) DEFAULT 1 COMMENT '1=auto process deposits',
        `min_amount` INT DEFAULT 10000 COMMENT 'Minimum deposit amount',
        `max_amount` INT DEFAULT 500000000 COMMENT 'Maximum deposit amount',
        `transfer_prefix` VARCHAR(20) DEFAULT 'NT' COMMENT 'Prefix for transfer note',
        `check_interval_minutes` INT DEFAULT 5 COMMENT 'Minutes between auto-check',
        `cancel_after_minutes` INT DEFAULT 30 COMMENT 'Auto-cancel pending after X minutes',
        `webhook_secret` VARCHAR(100) NOT NULL DEFAULT '',
        `last_sync_at` TIMESTAMP NULL,
        `status` TINYINT(1) DEFAULT 1 COMMENT '0=disabled 1=enabled',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table sepay_config");
}

// SePay Transactions Log
if (!te("sepay_transactions")) {
    rs("CREATE TABLE `sepay_transactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `sepay_id` VARCHAR(50) NOT NULL UNIQUE,
        `account_number` VARCHAR(50) NOT NULL DEFAULT '',
        `bank_code` VARCHAR(20) NOT NULL DEFAULT '',
        `transaction_date` DATETIME NOT NULL,
        `amount_in` DECIMAL(15,2) DEFAULT 0.00,
        `amount_out` DECIMAL(15,2) DEFAULT 0.00,
        `accumulated` DECIMAL(15,2) DEFAULT 0.00,
        `transaction_content` TEXT,
        `reference_number` VARCHAR(100) NOT NULL DEFAULT '',
        `code` VARCHAR(50) DEFAULT NULL,
        `sub_account` VARCHAR(50) DEFAULT NULL,
        `bank_account_id` VARCHAR(50) DEFAULT NULL,
        `status` ENUM('pending','matched','failed','duplicate') DEFAULT 'pending',
        `matched_deposit_id` INT DEFAULT NULL,
        `user_id` INT DEFAULT NULL,
        `processed_at` TIMESTAMP NULL,
        `raw_data` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_sepay_status` (`status`),
        INDEX `idx_sepay_user` (`user_id`),
        INDEX `idx_sepay_date` (`transaction_date`),
        INDEX `idx_sepay_ref` (`reference_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "Table sepay_transactions");
}

/* ================================================================
   2. MIGRATE COLUMNS (if tables existed before)
   ================================================================ */
echo "<h2>2. Column Migration</h2>";

// users
ac("users", "role", "password", "TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=user 1=admin'");
ac("users", "balance", "role", "INT(11) NOT NULL DEFAULT 0");
ac("users", "created_at", "balance", "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
ai("users", "idx_role", "role");

// products
ac("products", "old_price", "price", "INT DEFAULT 0");
ac("products", "badge", "old_price", "VARCHAR(50) DEFAULT ''");
ac("products", "details", "badge", "TEXT");
ac("products", "description", "details", "TEXT");
ac("products", "color_class", "description", "VARCHAR(50) DEFAULT 'bg-secondary'");
ac("products", "icon_class", "color_class", "VARCHAR(50) DEFAULT 'fa-box'");
ac("products", "gallery", "icon_class", "TEXT");
ac("products", "type_id", "category", "INT DEFAULT NULL");
ac("products", "created_at", "gallery", "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
ai("products", "idx_prod_cat", "category");
ai("products", "idx_prod_type", "type_id");

// types
ac("types", "category_id", "category", "INT DEFAULT NULL");
ac("types", "sort_order", "icon_class", "INT DEFAULT 0");
ai("types", "idx_types_cat", "category_id");

// orders - fix status enum
$r = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'status'");
if ($r && $row = mysqli_fetch_assoc($r)) {
    $hasProcessing = strpos($row["Type"], "processing") !== false;
    $hasCancelled  = strpos($row["Type"], "cancelled") !== false;
    if (!$hasProcessing || !$hasCancelled) {
        rs("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','processing','completed','cancelled','refunded') NOT NULL DEFAULT 'pending'", "Fix orders.status enum");
    }
}
ac("orders", "product_id", "user_id", "INT DEFAULT NULL");
ac("orders", "account_id", "product_id", "INT DEFAULT NULL");
ac("orders", "account_data", "account_id", "TEXT");
ac("orders", "price", "account_data", "INT NOT NULL DEFAULT 0");
ac("orders", "updated_at", "status", "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
ai("orders", "idx_ord_user", "user_id");
ai("orders", "idx_ord_prod", "product_id");
ai("orders", "idx_ord_status", "status");
ai("orders", "idx_ord_created", "created_at");

// transactions
ac("transactions", "balance_before", "amount", "INT NOT NULL DEFAULT 0");
ac("transactions", "balance_after", "balance_before", "INT NOT NULL DEFAULT 0");
ai("transactions", "idx_tx_user", "user_id");
ai("transactions", "idx_tx_type", "type");

// account_stock
ac("account_stock", "sold_to_user_id", "sold_at", "INT DEFAULT NULL");
ai("account_stock", "idx_stock_prod", "product_id");
ai("account_stock", "idx_stock_status", "status");

// deposit_requests
ac("deposit_requests", "amount", "user_id", "INT NOT NULL");
ac("deposit_requests", "transfer_amount", "amount", "INT NOT NULL");
ac("deposit_requests", "transfer_note", "transfer_amount", "VARCHAR(100) DEFAULT NULL");
ac("deposit_requests", "bank_id", "transfer_note", "INT DEFAULT NULL");
ac("deposit_requests", "admin_note", "status", "VARCHAR(255) DEFAULT NULL");
ac("deposit_requests", "processed_by", "admin_note", "INT DEFAULT NULL");
ac("deposit_requests", "processed_at", "processed_by", "TIMESTAMP NULL");
ac("deposit_requests", "reference_number", "transfer_note", "VARCHAR(100) DEFAULT NULL");
ac("deposit_requests", "sepay_transaction_id", "reference_number", "VARCHAR(50) DEFAULT NULL");
ai("deposit_requests", "idx_dep_user", "user_id");
ai("deposit_requests", "idx_dep_status", "status");

// Drop deprecated
if (te("product_stock")) {
    mysqli_query($conn, "DROP TABLE IF EXISTS `product_stock`");
    echo "<p class=\"ok\">[OK] Dropped deprecated product_stock</p>";
}

// sepay_config extra columns (post-create migration)
ac("sepay_config", "transfer_prefix", "webhook_secret", "VARCHAR(20) DEFAULT 'NT'");
ac("sepay_config", "check_interval_minutes", "transfer_prefix", "INT DEFAULT 5");
ac("sepay_config", "cancel_after_minutes", "check_interval_minutes", "INT DEFAULT 30");

// deposit_requests extra columns for auto matching
ac("deposit_requests", "unique_code", "amount", "VARCHAR(50) DEFAULT NULL");
ac("deposit_requests", "expires_at", "unique_code", "TIMESTAMP NULL");
ai("deposit_requests", "idx_dep_unique_code", "unique_code");
ai("deposit_requests", "idx_dep_expires", "expires_at");

/* ================================================================
   3. SEED DATA (insert only if empty)
   ================================================================ */
echo "<h2>3. Seed Data</h2>";

// Admin
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role = 1");
if ($r && mysqli_fetch_assoc($r)["c"] == 0) {
    $hp = password_hash("admin123", PASSWORD_BCRYPT);
    mysqli_query($conn, "INSERT INTO users (username, password, role, balance) VALUES ('admin', '$hp', 1, 1000000)");
    echo "<p class=\"ok\">[OK] Admin: admin / admin123 (1,000,000đ)</p>";
} else {
    echo "<p class=\"skip\">[SKIP] Admin exists</p>";
}

// Banks
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM banks");
if ($r && mysqli_fetch_assoc($r)["c"] == 0) {
    $banks = array(
        array("MB Bank", "1234567890", "NGUYEN VAN A", "fas fa-university"),
        array("TPBank", "9876543210", "NGUYEN VAN A", "fas fa-landmark"),
        array("Vietcombank", "4321876509", "NGUYEN VAN A", "fas fa-building-columns"),
        array("ACB", "5678901234", "NGUYEN VAN A", "fas fa-piggy-bank"),
    );
    foreach ($banks as $i => $b) {
        mysqli_query($conn, "INSERT INTO banks (bank_name, account_number, account_holder, icon_class, sort_order, status) VALUES ('" . esc($b[0]) . "','" . esc($b[1]) . "','" . esc($b[2]) . "','" . esc($b[3]) . "',$i,1)");
    }
    echo "<p class=\"ok\">[OK] 4 sample banks</p>";
} else {
    echo "<p class=\"skip\">[SKIP] Banks exist</p>";
}

// Default settings
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM settings");
if ($r && mysqli_fetch_assoc($r)["c"] == 0) {
    $defaults = [
        ['store_name', 'NEXUS STORE'],
        ['store_icon', 'fa-ghost'],
        ['store_email', ''],
        ['transaction_fee', '0'],
        ['auto_hide_out_of_stock', '0'],
        ['email_notifications_enabled', '0'],
    ];
    foreach ($defaults as $d) {
        mysqli_query($conn, "INSERT INTO settings (`key`, value) VALUES ('" . esc($d[0]) . "', '" . esc($d[1]) . "')");
    }
    echo "<p class=\"ok\">[OK] Default settings</p>";
} else {
    echo "<p class=\"skip\">[SKIP] Settings exist</p>";
}

// SePay Config - chỉ seed nếu chưa có
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM sepay_config");
if ($r && mysqli_fetch_assoc($r)["c"] == 0) {
    mysqli_query($conn, "INSERT INTO sepay_config (api_token, account_number, account_holder, bank_code, auto_process, min_amount, max_amount, status, transfer_prefix, check_interval_minutes, cancel_after_minutes) 
        VALUES ('', '', '', '', 1, 10000, 500000000, 0, 'NT', 5, 30)");
    echo "<p class=\"ok\">[OK] SePay config initialized (prefix NT, auto-cancel 30 minutes)</p>";
} else {
    echo "<p class=\"skip\">[SKIP] SePay config exists</p>";
}
rs("UPDATE sepay_config SET cancel_after_minutes = 30 WHERE cancel_after_minutes IS NULL OR cancel_after_minutes < 5", "Normalize SePay auto-cancel >= 30 minutes");
rs("UPDATE sepay_config SET transfer_prefix = 'NT' WHERE transfer_prefix IS NULL OR transfer_prefix = ''", "Normalize SePay transfer prefix");

// Account field types
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
        mysqli_query($conn, "INSERT INTO account_field_types (`key`, label, icon_class, placeholder, is_default, sort_order) VALUES ('" . esc($f[0]) . "','" . esc($f[1]) . "','" . esc($f[2]) . "','" . esc($f[3]) . "',$f[4],$f[5])");
    }
    echo "<p class=\"ok\">[OK] Account field types</p>";
} else {
    echo "<p class=\"skip\">[SKIP] Account field types exist</p>";
}

// Categories
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM categories");
if ($r && mysqli_fetch_assoc($r)["c"] == 0) {
    $cats = array(
        array(1, "Game", "fa-gamepad", "Tai khoan Game & Gaming", 1),
        array(2, "Netflix", "fa-n", "Netflix Premium Accounts", 2),
        array(3, "YouTube", "fa-youtube", "YouTube Premium & Music", 3),
        array(4, "Spotify", "fa-spotify", "Spotify Premium", 4),
        array(5, "Disney+", "fa-play", "Disney+ Premium", 5),
        array(6, "GPT / AI", "fa-robot", "AI & Chatbot Tools", 6),
        array(7, "AI Tools", "fa-wand-magic-sparkles", "Design & Creative AI", 7),
        array(8, "Cloud", "fa-cloud", "Cloud Storage Services", 8),
        array(9, "Social", "fa-share-nodes", "Social Media Accounts", 9),
        array(10, "Khac", "fa-ellipsis", "Other Products", 10),
    );
    $catMap = array();
    foreach ($cats as $c) {
        mysqli_query($conn, "INSERT INTO categories (id, name, icon_class, description, sort_order) VALUES ($c[0],'" . esc($c[1]) . "','" . esc($c[2]) . "','" . esc($c[3]) . "',$c[4])");
        $catMap[$c[1]] = $c[0];
    }
    echo "<p class=\"ok\">[OK] " . count($cats) . " categories</p>";
} else {
    echo "<p class=\"skip\">[SKIP] Categories exist</p>";
    $res = mysqli_query($conn, "SELECT id, name FROM categories");
    $catMap = array();
    while ($row = mysqli_fetch_assoc($res)) { $catMap[$row["name"]] = $row["id"]; }
}

// Types
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM types");
if ($r && mysqli_fetch_assoc($r)["c"] == 0) {
    $types = array(
        array("Valorant", "Game", "fa-gamepad", 1, 1), array("CS2 / CSGO", "Game", "fa-gamepad", 2, 1),
        array("Minecraft", "Game", "fa-cubes", 3, 1), array("Genshin Impact", "Game", "fa-dragon", 4, 1),
        array("Lien Quan Mobile", "Game", "fa-gamepad", 5, 1), array("Free Fire", "Game", "fa-fire", 6, 1),
        array("PUBG Mobile", "Game", "fa-crosshairs", 7, 1), array("Roblox", "Game", "fa-robot", 8, 1),
        array("FIFA / EA FC", "Game", "fa-futbol", 9, 1), array("League of Legends", "Game", "fa-chess", 10, 1),
        array("Netflix Premium", "Netflix", "fa-n", 11, 2), array("Netflix Standard", "Netflix", "fa-n", 12, 2),
        array("YouTube Premium", "YouTube", "fa-youtube", 13, 3), array("YouTube Music", "YouTube", "fa-youtube", 14, 3),
        array("Spotify Premium", "Spotify", "fa-spotify", 15, 4), array("Spotify Family", "Spotify", "fa-spotify", 16, 4),
        array("Disney+ Premium", "Disney+", "fa-play", 17, 5), array("Prime Video", "Disney+", "fa-amazon", 18, 5),
        array("Apple TV+", "Disney+", "fa-apple", 19, 5),
        array("ChatGPT Plus", "GPT", "fa-robot", 20, 6), array("Claude Pro", "GPT", "fa-robot", 21, 6),
        array("Gemini Advanced", "GPT", "fa-gem", 22, 6),
        array("Midjourney", "AI Tools", "fa-wand-magic-sparkles", 23, 7), array("Canva Pro", "AI Tools", "fa-palette", 24, 7),
        array("Notion", "AI Tools", "fa-note-sticky", 25, 7), array("CapCut Pro", "AI Tools", "fa-video", 26, 7),
        array("Google One", "Cloud", "fa-cloud", 27, 8), array("iCloud+", "Cloud", "fa-cloud", 28, 8),
        array("Dropbox", "Cloud", "fa-cloud-arrow-up", 29, 8),
        array("Facebook", "Social", "fa-facebook", 30, 9), array("TikTok", "Social", "fa-tiktok", 31, 9),
        array("Twitter / X", "Social", "fa-x-twitter", 32, 9), array("Instagram", "Social", "fa-instagram", 33, 9),
        array("Khac", "Khac", "fa-ellipsis", 34, 10),
    );
    $typeMap = array();
    foreach ($types as $t) {
        mysqli_query($conn, "INSERT INTO types (name, category, icon_class, sort_order, category_id) VALUES ('" . esc($t[0]) . "','" . esc($t[1]) . "','" . esc($t[2]) . "',$t[4],$t[3])");
        $typeMap[$t[0]] = iid();
    }
    echo "<p class=\"ok\">[OK] " . count($types) . " types</p>";
} else {
    echo "<p class=\"skip\">[SKIP] Types exist</p>";
    $res = mysqli_query($conn, "SELECT id, name FROM types");
    $typeMap = array();
    while ($row = mysqli_fetch_assoc($res)) { $typeMap[$row["name"]] = $row["id"]; }
}

// Products
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM products");
if ($r && mysqli_fetch_assoc($r)["c"] == 0) {
    $products = array(
        array("Tai khoan Valorant Rank Bac 2 - 50 ACC VP", "Game", "Valorant", 15000, 25000, "Hot", "https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&q=80", "Tai khoan Valorant rank Bac 2, da tich luy 50 acc VP, bao mat 2 lop.", '{"Rank":"Bac 2","VP":"50 ACC VP","Tuong":"15/26","Skin":"5 Blade"}', "bg-danger", "fa-gamepad"),
        array("Tai khoan Valorant Rank Vang 1 - Full Tuong", "Game", "Valorant", 45000, 60000, "VIP", "https://images.unsplash.com/photo-1552820728-8b83bb6b773f?w=600&q=80", "Tai khoan Valorant rank Vang 1, full tuong, nhieu skin gia tri.", '{"Rank":"Vang 1","VP":"200 ACC VP","Tuong":"26/26","Skin":"20+ Blade"}', "bg-danger", "fa-gamepad"),
        array("Tai khoan Valorant Rank Bach Kim 2 - Rank Cao", "Game", "Valorant", 85000, 120000, "VIP", "https://images.unsplash.com/photo-1552820728-8b83bb6b773f?w=600&q=80", "Tai khoan Valorant rank Bach Kim 2, account chat luong cao.", '{"Rank":"Bach Kim 2","VP":"500 ACC VP","Tuong":"26/26","Skin":"35+ Blade"}', "bg-danger", "fa-gamepad"),
        array("Tai khoan CS2 Prime - Rank Silver 3", "Game", "CS2 / CSGO", 12000, 0, "", "https://images.unsplash.com/photo-1493711662062-fa541adb3fc8?w=600&q=80", "Tai khoan CS2 Prime Status, rank Silver 3.", '{"Rank":"Silver 3","Prime":"Co","Hours":"120h","VAC":"Sach"}', "bg-dark", "fa-gamepad"),
        array("Tai khoan CS2 Rank Nova 3 - 500h", "Game", "CS2 / CSGO", 35000, 50000, "Deal", "https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&q=80", "Tai khoan CS2 Nova 3 voi 500 gio choi.", '{"Rank":"Nova 3","Prime":"Co","Hours":"500h","VAC":"Sach"}', "bg-dark", "fa-gamepad"),
        array("Tai khoan CS2 Global Elite - Premium", "Game", "CS2 / CSGO", 250000, 350000, "VIP", "https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&q=80", "Tai khoan CS2 rank Global Elite, inventory day, nhieu knife.", '{"Rank":"Global Elite","Hours":"2000h+","Skins":"50+ Blade","Knife":"Co"}', "bg-dark", "fa-gamepad"),
        array("Tai khoan Minecraft Realms Premium 1 Thang", "Game", "Minecraft", 25000, 35000, "", "https://images.unsplash.com/photo-1587573089734-599d584352eb?w=600&q=80", "Minecraft Java Edition + Realms Premium 30 ngay.", '{"Edition":"Java","Realms":"30 ngay","Profile":"Sach"}', "bg-success", "fa-cubes"),
        array("Tai khoan Minecraft Java Premium 6 Thang", "Game", "Minecraft", 120000, 180000, "Deal", "https://images.unsplash.com/photo-1587573089734-599d584352eb?w=600&q=80", "Minecraft Java Edition full quyen, Realms Premium 6 thang.", '{"Edition":"Java","Realms":"6 thang"}', "bg-success", "fa-cubes"),
        array("Tai khoan Genshin Impact AR55 - Co Raiden", "Game", "Genshin Impact", 180000, 250000, "VIP", "https://images.unsplash.com/photo-1534423861386-85a16f5d13fd?w=600&q=80", "Tai khoan Genshin AR55, so huu Raiden Shogun C2.", '{"AR":"55","Raiden":"C2","5-Star":"8 Blade"}', "bg-info", "fa-dragon"),
        array("Tai khoan Lien Quan Rank Kim Cuong 3", "Game", "Lien Quan Mobile", 35000, 0, "Hot", "https://images.unsplash.com/photo-1511512578047-dfb367046420?w=600&q=80", "Tai khoan Lien Quan rank Kim Cuong 3, tuong day du.", '{"Rank":"Kim Cuong 3","Tuong":"90+","Skin SL":"10+"}', "bg-warning", "fa-gamepad"),
        array("Tai khoan Free Fire Max OB44 - Rank Huyen Thoai", "Game", "Free Fire", 80000, 120000, "VIP", "https://images.unsplash.com/photo-1551103782-8ab07afd45c1?w=600&q=80", "Tai khoan Free Fire Max rank Huyen Thoai.", '{"Rank":"Huyen Thoai","Version":"Max OB44","Skins Sung":"15+"}', "bg-orange", "fa-fire"),
        array("Tai khoan LoL Rank Bac - Account Moi", "Game", "League of Legends", 18000, 0, "", "https://images.unsplash.com/photo-1511512578047-dfb367046420?w=600&q=80", "Tai khoan League of Legends rank Bac, account moi tao.", '{"Rank":"Bac","IP":"5000+","Tuong":"10+"}', "bg-primary", "fa-chess"),
        array("Tai khoan Netflix Premium 1 Thang - 4K HDR", "Netflix", "Netflix Premium", 35000, 55000, "Hot", "https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?w=600&q=80", "Netflix Premium chat luong 4K HDR, xem tren 4 thiet bi.", '{"Chat luong":"4K HDR","Thiet bi":"4 cung luc","Profile":"5/5"}', "bg-danger", "fa-n"),
        array("Tai khoan Netflix Premium 3 Thang - Tiet kiem", "Netflix", "Netflix Premium", 90000, 150000, "Deal", "https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80", "Goi Netflix Premium 3 thang, tiet kiem hon 40%.", '{"Chat luong":"4K HDR","Thiet bi":"4 cung luc","Thoi han":"3 thang"}', "bg-danger", "fa-n"),
        array("Tai khoan Netflix Standard 1 Thang - Full HD", "Netflix", "Netflix Standard", 25000, 0, "", "https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?w=600&q=80", "Netflix Standard chat luong Full HD, xem tren 2 thiet bi.", '{"Chat luong":"Full HD 1080p","Thiet bi":"2 cung luc"}', "bg-danger", "fa-n"),
        array("Tai khoan YouTube Premium 1 Thang", "YouTube", "YouTube Premium", 18000, 28000, "Hot", "https://images.unsplash.com/photo-1611162616305-c69b3fa7fbe0?w=600&q=80", "YouTube Premium - xem khong quang cao, tai offline, phat nen.", '{"Loai":"Ca nhan","Quang cao":"Khong","Tai offline":"Co"}', "bg-danger", "fa-youtube"),
        array("Tai khoan YouTube Premium 3 Thang", "YouTube", "YouTube Premium", 45000, 70000, "Deal", "https://images.unsplash.com/photo-1611162616305-c69b3fa7fbe0?w=600&q=80", "YouTube Premium 3 thang - tiet kiem hon.", '{"Loai":"Ca nhan","Thoi han":"3 thang","Quang cao":"Khong"}', "bg-danger", "fa-youtube"),
        array("Tai khoan YouTube Music Premium 6 Thang", "YouTube", "YouTube Music", 55000, 90000, "Deal", "https://images.unsplash.com/photo-1614680376408-81e91ffe3db7?w=600&q=80", "YouTube Music Premium 6 thang - nghe nhac khong quang cao.", '{"Loai":"Ca nhan","Thoi han":"6 thang"}', "bg-danger", "fa-youtube"),
        array("Tai khoan Spotify Premium 1 Thang", "Spotify", "Spotify Premium", 15000, 22000, "", "https://images.unsplash.com/photo-1614680376408-81e91ffe3db7?w=600&q=80", "Spotify Premium - chat luong 320kbps, khong quang cao.", '{"Chat luong":"320kbps","Quang cao":"Khong","Offline":"Co"}', "bg-dark", "fa-spotify"),
        array("Tai khoan Spotify Premium Family 6 Thang", "Spotify", "Spotify Family", 120000, 180000, "VIP", "https://images.unsplash.com/photo-1614680376408-81e91ffe3db7?w=600&q=80", "Spotify Family - toi da 6 thanh vien.", '{"Loai":"Family","Thanh vien":"6/6","Thoi han":"6 thang"}', "bg-dark", "fa-spotify"),
        array("Tai khoan Spotify Premium 6 Thang", "Spotify", "Spotify Premium", 70000, 120000, "Deal", "https://images.unsplash.com/photo-1614680376408-81e91ffe3db7?w=600&q=80", "Spotify Premium 6 thang - tiet kiem 30%.", '{"Chat luong":"320kbps","Thoi han":"6 thang"}', "bg-dark", "fa-spotify"),
        array("Tai khoan Disney+ Premium 1 Thang", "Disney+", "Disney+ Premium", 25000, 40000, "Hot", "https://images.unsplash.com/photo-1618828665011-0a5c4da5c08d?w=600&q=80", "Disney+ Premium - xem Disney, Marvel, Star Wars.", '{"Chat luong":"4K HDR","Quang cao":"Khong","Thiet bi":"4 cung luc"}', "bg-info", "fa-play"),
        array("Tai khoan Disney+ Premium 3 Thang", "Disney+", "Disney+ Premium", 65000, 110000, "Deal", "https://images.unsplash.com/photo-1618828665011-0a5c4da5c08d?w=600&q=80", "Disney+ Premium 3 thang - tiet kiem 35%.", '{"Chat luong":"4K HDR","Thoi han":"3 thang"}', "bg-info", "fa-play"),
        array("Tai khoan ChatGPT Plus 1 Thang", "GPT", "ChatGPT Plus", 60000, 90000, "VIP", "https://images.unsplash.com/photo-1677442136019-21780ecad995?w=600&q=80", "ChatGPT Plus - GPT-4o, DALL-E, browsing, Advanced Data Analysis.", '{"Model":"GPT-4o","DALL-E":"Co","Browsing":"Co"}', "bg-success", "fa-robot"),
        array("Tai khoan ChatGPT Plus 3 Thang", "GPT", "ChatGPT Plus", 160000, 250000, "VIP", "https://images.unsplash.com/photo-1677442136019-21780ecad995?w=600&q=80", "ChatGPT Plus 3 thang - tiet kiem 20%.", '{"Model":"GPT-4o","Thoi han":"3 thang","DALL-E":"Co"}', "bg-success", "fa-robot"),
        array("Tai khoan Claude Pro 1 Thang", "GPT", "Claude Pro", 80000, 120000, "", "https://images.unsplash.com/photo-1620712943543-bcc4688e7485?w=600&q=80", "Claude Pro - truy cap Claude 3 Opus, Sonnet, Haiku.", '{"Models":"3 Opus/Sonnet/Haiku","Context":"200K tokens"}', "bg-warning", "fa-robot"),
        array("Tai khoan Gemini Advanced 1 Thang", "GPT", "Gemini Advanced", 45000, 70000, "Hot", "https://images.unsplash.com/photo-1677442136019-21780ecad995?w=600&q=80", "Gemini Advanced - truy cap Ultra AI, 1.5 Pro, Deep Research.", '{"Model":"Gemini Ultra","Deep Research":"Co"}', "bg-primary", "fa-gem"),
        array("Tai khoan Midjourney 1 Thang - Standard", "AI Tools", "Midjourney", 120000, 150000, "", "https://images.unsplash.com/photo-1683160735206-8a8c5e2e8e9e?w=600&q=80", "Midjourney Standard - 15 gio fast GPU moi thang.", '{"Plan":"Standard","Fast GPU":"15h/thang"}', "bg-secondary", "fa-wand-magic-sparkles"),
        array("Tai khoan Canva Pro 1 Nam", "AI Tools", "Canva Pro", 250000, 400000, "Deal", "https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80", "Canva Pro ban quyen 1 nam - 610,000+ templates.", '{"Templates":"610,000+","Brand Kit":"Co","Downloads":"Unlimited"}', "bg-secondary", "fa-palette"),
        array("Tai khoan Notion Plus 1 Nam", "AI Tools", "Notion", 180000, 280000, "Deal", "https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80", "Notion Plus 1 nam - unlimited file, 30 days history.", '{"Plan":"Plus","File uploads":"Unlimited","History":"30 days"}', "bg-dark", "fa-note-sticky"),
        array("Tai khoan CapCut Pro 1 Nam", "AI Tools", "CapCut Pro", 150000, 220000, "", "https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80", "CapCut Pro 1 nam - pro transitions, unlimited exports.", '{"Plan":"Pro","Exports":"Unlimited","Watermark":"Khong"}', "bg-info", "fa-video"),
        array("Tai khoan Google One 200GB - 6 Thang", "Cloud", "Google One", 45000, 70000, "Deal", "https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=600&q=80", "Google One 200GB - luu tru Drive, Gmail, Photos.", '{"Dung luong":"200GB","Share":"5 nguoi"}', "bg-light", "fa-cloud"),
        array("Tai khoan Google One 2TB - 1 Nam", "Cloud", "Google One", 550000, 800000, "VIP", "https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=600&q=80", "Google One 2TB 1 nam - full Google storage.", '{"Dung luong":"2TB","Share":"5 nguoi"}', "bg-light", "fa-cloud"),
        array("Tai khoan iCloud+ 200GB - 1 Thang", "Cloud", "iCloud+", 22000, 0, "", "https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=600&q=80", "iCloud+ 200GB - luu tru iPhone, iPad, MacBook.", '{"Dung luong":"200GB","Family":"6 nguoi"}', "bg-primary", "fa-cloud"),
        array("Tai khoan Dropbox Plus 1 Nam", "Cloud", "Dropbox", 280000, 400000, "Deal", "https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=600&q=80", "Dropbox Plus 1 nam - 2TB cloud storage.", '{"Dung luong":"2TB","History":"30 days"}', "bg-primary", "fa-cloud-arrow-up"),
        array("Tai khoan TikTok Premium 1 Thang", "Social", "TikTok", 25000, 0, "Hot", "https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80", "TikTok Premium - khong quang cao, download video.", '{"Quang cao":"Khong","Download":"Co"}', "bg-dark", "fa-tiktok"),
        array("Tai khoan Twitter/X Premium 1 Thang", "Social", "Twitter / X", 35000, 55000, "", "https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80", "X Premium - verified badge, reply boost, edit post.", '{"Badge":"Verified","Reply Boost":"Co"}', "bg-dark", "fa-x-twitter"),
        array("Tai khoan Instagram Premium 1 Thang", "Social", "Instagram", 30000, 0, "", "https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80", "Instagram Premium - story features, reels analytics.", '{"Stories":"Tat ca","Reels Analytics":"Co"}', "bg-warning", "fa-instagram"),
        array("Tai khoan Adobe Creative Cloud 1 Nam", "Khac", "Khac", 1200000, 1800000, "VIP", "https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80", "Adobe CC All Apps 1 nam - Photoshop, Premiere, After Effects.", '{"Apps":"All 20+","Storage":"1TB"}', "bg-danger", "fa-palette"),
    );

    $prodMap = array();
    $stmtProd = mysqli_prepare($conn, "INSERT INTO products (title, category, game_type, type_id, price, old_price, badge, image_url, description, details, color_class, icon_class) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");

    foreach ($products as $p) {
        $typeId = isset($typeMap[$p[2]]) ? $typeMap[$p[2]] : NULL;
        mysqli_stmt_bind_param($stmtProd, "sssiisssssss", $p[0], $p[1], $p[2], $typeId, $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10]);
        if (mysqli_stmt_execute($stmtProd)) {
            $prodMap[$p[0]] = iid();
        }
    }
    echo "<p class=\"ok\">[OK] " . count($prodMap) . " products</p>";

    // Account stock
    $stockConfig = array(
        $prodMap["Tai khoan Valorant Rank Bac 2 - 50 ACC VP"] => 5,
        $prodMap["Tai khoan Valorant Rank Vang 1 - Full Tuong"] => 3,
        $prodMap["Tai khoan CS2 Prime - Rank Silver 3"] => 8,
        $prodMap["Tai khoan CS2 Rank Nova 3 - 500h"] => 5,
        $prodMap["Tai khoan Minecraft Realms Premium 1 Thang"] => 10,
        $prodMap["Tai khoan Genshin Impact AR55 - Co Raiden"] => 2,
        $prodMap["Tai khoan Lien Quan Rank Kim Cuong 3"] => 4,
        $prodMap["Tai khoan Netflix Premium 1 Thang - 4K HDR"] => 15,
        $prodMap["Tai khoan Netflix Premium 3 Thang - Tiet kiem"] => 10,
        $prodMap["Tai khoan YouTube Premium 1 Thang"] => 12,
        $prodMap["Tai khoan Spotify Premium 1 Thang"] => 20,
        $prodMap["Tai khoan Spotify Premium Family 6 Thang"] => 4,
        $prodMap["Tai khoan Disney+ Premium 1 Thang"] => 10,
        $prodMap["Tai khoan ChatGPT Plus 1 Thang"] => 15,
        $prodMap["Tai khoan Claude Pro 1 Thang"] => 6,
        $prodMap["Tai khoan Gemini Advanced 1 Thang"] => 10,
        $prodMap["Tai khoan Canva Pro 1 Nam"] => 3,
        $prodMap["Tai khoan Notion Plus 1 Nam"] => 4,
        $prodMap["Tai khoan Google One 200GB - 6 Thang"] => 8,
        $prodMap["Tai khoan TikTok Premium 1 Thang"] => 10,
    );

    $totalStock = 0;
    $stmtStock = mysqli_prepare($conn, "INSERT INTO account_stock (product_id, account_data, status) VALUES (?,?,'available')");
    foreach ($stockConfig as $pid => $qty) {
        for ($i = 0; $i < $qty; $i++) {
            $email = "acc_" . time() . "_" . rand(1000, 9999) . "@gmail.com";
            $pass = "Pass" . rand(100000, 999999) . "!";
            $adata = json_encode(array("email" => $email, "password" => $pass));
            mysqli_stmt_bind_param($stmtStock, "is", $pid, $adata);
            mysqli_stmt_execute($stmtStock);
            $totalStock++;
        }
    }
    echo "<p class=\"ok\">[OK] $totalStock accounts in stock</p>";
} else {
    echo "<p class=\"skip\">[SKIP] Products exist (skip seed)</p>";
}

/* ================================================================
   4. SHOW CURRENT SCHEMA
   ================================================================ */
echo "<h2>4. Current Schema</h2>";
$allT = array("users","products","categories","types","orders","transactions","cart","account_stock","deposit_requests","banks");
foreach ($allT as $t) {
    if (te($t)) {
        $cols = mysqli_query($conn, "SHOW COLUMNS FROM `$t`");
        $names = array();
        while ($c = mysqli_fetch_assoc($cols)) { $names[] = $c["Field"]; }
        echo "<p class=\"ok\">- <strong>$t</strong>: " . htmlspecialchars(implode(", ", $names)) . "</p>";
    }
}

/* ================================================================
   SUMMARY
   ================================================================ */
$okC = $skipC = $errC = 0;
foreach ($results as $r) {
    if ($r[0] === "ok") $okC++;
    elseif ($r[0] === "skip") $skipC++;
    elseif ($r[0] === "err") $errC++;
}
echo "<div class=\"summary\">";
echo "<h2>Xong!</h2>";
echo "<p><span class=\"ok\">Thanh cong: $okC</span> | <span class=\"skip\">Bo qua: $skipC</span>";
if ($errC > 0) echo " | <span class=\"err\">Loi: $errC</span>";
echo "</p>";
if ($errC > 0) {
    echo "<h3>Cac loi:</h3>";
    foreach ($results as $r) {
        if ($r[0] === "err") echo "<p class=\"err\">- " . htmlspecialchars($r[1]) . "</p>";
    }
}
echo "<p>Dang nhap admin: <code>admin</code> / <code>admin123</code></p>";
echo "<a href=\"../index.php\" class=\"btn\">Trang chu</a>";
echo "<a href=\"../admin/\" class=\"btn green\">Admin Panel</a>";
echo "</div></body></html>";
?>
