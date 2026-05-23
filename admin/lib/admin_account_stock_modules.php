<?php
/**
 * Admin Account Stock Modules - Kho tài khoản (Route-only)
 * Chỉ xử lý HTTP requests. Logic đã chuyển sang admin_transaction_modules.php.
 * Để giữ backward compatibility cho các file gọi trực tiếp file này.
 */

require_once __DIR__ . '/../../database/connect.php';
require_once __DIR__ . '/admin_transaction_modules.php';

// Xử lý request nếu được gọi trực tiếp (AJAX từ trang admin)
if (basename($_SERVER['PHP_SELF']) === 'admin_account_stock_modules.php') {
    require_once __DIR__ . '/admin_verifier_modules.php';
    admin_handleAccountStockRequest();
}
?>
