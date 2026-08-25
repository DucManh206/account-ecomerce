<?php

function app_schema_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function app_schema_index_exists(PDO $pdo, string $table, string $indexName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $indexName]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensure_application_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $versionStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'schema_version'");
    $versionStmt->execute();
    $currentVersion = (int) ($versionStmt->fetchColumn() ?: 0);

    $versionUpsert = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value) VALUES ('schema_version', ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );

    if ($currentVersion < 4) {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS balance_transactions (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                transaction_type VARCHAR(30) NOT NULL,
                amount DECIMAL(15,0) NOT NULL,
                balance_after DECIMAL(15,0) DEFAULT NULL,
                source_type VARCHAR(40) DEFAULT NULL,
                source_id BIGINT DEFAULT NULL,
                description VARCHAR(255) NOT NULL,
                created_by INT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_balance_source (source_type, source_id, transaction_type),
                KEY idx_balance_user_created (user_id, created_at),
                KEY idx_balance_type_created (transaction_type, created_at),
                CONSTRAINT fk_balance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_balance_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_activity_logs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT DEFAULT NULL,
                action VARCHAR(60) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id BIGINT DEFAULT NULL,
                description VARCHAR(255) NOT NULL,
                metadata_json JSON DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY idx_admin_activity_created (created_at),
                KEY idx_admin_activity_entity (entity_type, entity_id),
                CONSTRAINT fk_activity_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        if (!app_schema_column_exists($pdo, 'topup_requests', 'reviewed_by')) {
            $pdo->exec('ALTER TABLE topup_requests ADD COLUMN reviewed_by INT DEFAULT NULL AFTER status');
        }
        if (!app_schema_column_exists($pdo, 'topup_requests', 'reviewed_at')) {
            $pdo->exec('ALTER TABLE topup_requests ADD COLUMN reviewed_at DATETIME DEFAULT NULL AFTER reviewed_by');
        }
        if (!app_schema_column_exists($pdo, 'topup_requests', 'admin_note')) {
            $pdo->exec('ALTER TABLE topup_requests ADD COLUMN admin_note VARCHAR(255) DEFAULT NULL AFTER reviewed_at');
        }
        if (!app_schema_column_exists($pdo, 'users', 'is_active')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role');
        }

        $pdo->exec(
            "ALTER TABLE topup_requests
             MODIFY status ENUM('pending', 'completed', 'expired', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'"
        );

        $defaults = [
            'site_name' => 'AccountShop - Hệ thống tài khoản Premium',
            'site_short_name' => 'AccountShop',
            'site_tagline' => 'Tài khoản số, giao ngay sau thanh toán',
            'storefront_heading' => 'Mua tài khoản Premium tự động',
            'storefront_description' => 'Chọn sản phẩm phù hợp, thanh toán bằng số dư và nhận thông tin đăng nhập ngay trong tài khoản.',
            'storefront_notice' => '',
            'support_contact' => '',
            'storefront_page_size' => '12',
            'admin_page_size' => '20',
            'low_stock_threshold' => '3',
            'min_topup_amount' => '10000',
            'max_topup_amount' => '100000000',
            'topup_presets' => '20000,50000,100000,200000,500000,1000000',
            'topup_expiry_minutes' => '15',
            'allow_mock_topup' => '0',
        ];

        $defaultStmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = setting_value'
        );
        foreach ($defaults as $key => $value) {
            $defaultStmt->execute([$key, $value]);
        }

        $pdo->exec(
            "INSERT IGNORE INTO balance_transactions
                (user_id, transaction_type, amount, source_type, source_id, description, created_at)
             SELECT user_id, 'purchase', -price, 'order', id, CONCAT('Thanh toán đơn hàng #', id), created_at
             FROM orders"
        );

        $pdo->exec(
            "INSERT IGNORE INTO balance_transactions
                (user_id, transaction_type, amount, source_type, source_id, description, created_at)
             SELECT user_id, 'deposit', amount, 'sepay', id, CONCAT('Nạp tiền qua SePay #', sepay_transaction_id), transaction_date
             FROM sepay_transactions"
        );

        $versionUpsert->execute(['4']);
        $currentVersion = 4;
    }

    if ($currentVersion < 5) {
        if (!app_schema_column_exists($pdo, 'orders', 'product_name')) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN product_name VARCHAR(200) DEFAULT NULL AFTER price');
        }
        if (!app_schema_column_exists($pdo, 'orders', 'product_category')) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN product_category VARCHAR(100) DEFAULT NULL AFTER product_name');
        }
        if (!app_schema_column_exists($pdo, 'orders', 'delivered_credentials')) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN delivered_credentials TEXT DEFAULT NULL AFTER product_category');
        }

        $pdo->exec(
            "UPDATE orders o
             LEFT JOIN accounts a ON a.id = o.account_id
             LEFT JOIN categories c ON c.id = a.category_id
             SET o.product_name = COALESCE(NULLIF(o.product_name, ''), a.name),
                 o.product_category = COALESCE(NULLIF(o.product_category, ''), c.name),
                 o.delivered_credentials = COALESCE(NULLIF(o.delivered_credentials, ''), a.account_detail)
             WHERE o.product_name IS NULL
                OR o.product_name = ''
                OR o.delivered_credentials IS NULL
                OR o.delivered_credentials = ''"
        );

        if (!app_schema_index_exists($pdo, 'orders', 'uniq_orders_account')) {
            $duplicate = $pdo->query(
                'SELECT account_id FROM orders GROUP BY account_id HAVING COUNT(*) > 1 LIMIT 1'
            )->fetchColumn();
            if ($duplicate === false) {
                $pdo->exec('ALTER TABLE orders ADD UNIQUE KEY uniq_orders_account (account_id)');
            }
        }

        $versionUpsert->execute(['5']);
    }
}
