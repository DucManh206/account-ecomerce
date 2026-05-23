<?php
/**
 * NEXUS STORE - SePay Cron Job
 * 
 * Chạy định kỳ để:
 * 1. Đồng bộ giao dịch từ SePay
 * 2. Xử lý tự động các giao dịch khớp
 * 3. Huỷ các deposit request đã hết hạn
 * 
 * CÁCH SỬ DỤNG:
 * 1. Local/Manual: Truy cập trực tiếp file này hoặc gọi từ browser
 * 2. Server: Cấu hình cron job chạy mỗi X phút (theo cấu hình admin)
 *    Ví dụ: */5 * * * * curl -s https://domain.com/api/cron/sepay.php?key=YOUR_SECRET_KEY
 * 
 * BẢO MẬT:
 * - Nên đặt cron job secret key trong config
 * - Chỉ chạy qua HTTPS trên production
 */

// Config
define('ALLOWED_IPS', ['127.0.0.1', '::1']); // IP được phép gọi trực tiếp
define('CRON_SECRET_KEY', 'nexus-sepay-cron-2024'); // Đổi key này trong production

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Security check
function checkAccess() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $key = $_GET['key'] ?? '';
    $cronKey = CRON_SECRET_KEY;
    
    // Allow localhost
    if (in_array($ip, ALLOWED_IPS)) {
        return true;
    }
    
    // Check secret key
    if (!empty($key) && hash_equals($cronKey, $key)) {
        return true;
    }
    
    return false;
}

if (!checkAccess()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied'
    ]);
    exit;
}

// Load dependencies
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../lib/sepay_modules.php';

// Start output buffering for logging
ob_start();

$result = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'steps' => []
];

// Step 1: Huỷ các request đã hết hạn
$expired = sepay_expireOldRequests();
$result['steps']['expire_old_requests'] = [
    'expired_count' => $expired
];

// Step 2: Đồng bộ và xử lý giao dịch
$syncResult = sepay_syncAndProcess(100);

$result['steps']['sync_transactions'] = [
    'saved' => $syncResult['saved'] ?? 0,
    'processed' => $syncResult['processed'] ?? 0,
    'duplicates' => $syncResult['duplicates'] ?? 0,
    'total_received' => $syncResult['total_received'] ?? 0
];

if (!$syncResult['success'] && !empty($syncResult['message'])) {
    $result['steps']['sync_transactions']['error'] = $syncResult['message'];
}

// Summary
$result['summary'] = [
    'total_processed' => ($syncResult['processed'] ?? 0),
    'total_expired' => $expired
];

// Output
$output = ob_get_clean();

// Log to file
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/sepay_cron_' . date('Y-m-d') . '.log';
$logEntry = date('Y-m-d H:i:s') . ' | ' . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
