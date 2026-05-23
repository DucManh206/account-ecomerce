<?php
/**
 * SePay Auto Sync Cron Job
 * 
 * Chạy định kỳ để đồng bộ giao dịch từ SePay
 * URL: https://yourdomain.com/api/sepay-sync.php?key=YOUR_WEBHOOK_SECRET
 * 
 * Khuyến nghị: Chạy mỗi 1-5 phút qua cron job hoặc systemd timer
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../sepay/sepay_modules.php';

$response = ['success' => false, 'message' => ''];

// Verify secret key
$secretKey = $_GET['key'] ?? '';
$config = sepay_getConfig();

if (empty($secretKey) || $secretKey !== ($config['webhook_secret'] ?? '')) {
    // Also allow API token as fallback
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

try {
    $result = sepay_syncAndProcess(100);
    
    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = 'Sync completed';
        $response['data'] = [
            'saved' => $result['saved'],
            'processed' => $result['processed'],
            'duplicates' => $result['duplicates'],
            'total_received' => $result['total_received']
        ];
        
        // Log nếu có giao dịch mới
        if ($result['processed'] > 0) {
            error_log("SePay Sync: Processed {$result['processed']} transactions");
        }
    } else {
        $response['message'] = $result['message'] ?? 'Unknown error';
        error_log("SePay Sync Error: " . ($result['message'] ?? 'Unknown'));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Server error: ' . $e->getMessage();
    error_log("SePay Sync Exception: " . $e->getMessage());
}

echo json_encode($response);
