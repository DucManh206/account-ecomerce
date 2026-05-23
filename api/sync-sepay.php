<?php
/**
 * API: Sync SePay Manually
 * GET/POST to trigger sync
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../lib/sepay_modules.php';
session_start();

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Force sync
$result = sepay_syncAndProcess();
sepay_expireOldRequests();

if ($result) {
    echo json_encode([
        'success' => true, 
        'message' => 'Đã cập nhật giao dịch mới nhất',
        'count' => count($result)
    ]);
} else {
    echo json_encode([
        'success' => true, 
        'message' => 'Không có giao dịch mới'
    ]);
}
