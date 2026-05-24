<?php
require_once __DIR__ . '/admin/config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn chưa đăng nhập.']);
    exit;
}

$userId = $_SESSION['user_id'];
$amount = isset($_REQUEST['amount']) ? intval($_REQUEST['amount']) : 0;

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Số tiền không hợp lệ.']);
    exit;
}

$expectedPrefix = defined('SEPAY_MEMO_PREFIX') ? SEPAY_MEMO_PREFIX : 'NAP';
$memo = $expectedPrefix . ' ' . $userId;

try {
    // 1. Kiểm tra yêu cầu nạp tiền
    $stmt = $pdo->prepare("
        SELECT id, amount, memo, created_at 
        FROM topup_requests 
        WHERE user_id = ? AND status = 'pending' AND amount = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 4 MINUTE) 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$userId, $amount]);
    $existing = $stmt->fetch();

    if ($existing) {
        $elapsed = time() - strtotime($existing['created_at']);
        $expiry_seconds = 240 - $elapsed;
        
        if ($expiry_seconds > 5) {
            echo json_encode([
                'status' => 'success',
                'request_id' => intval($existing['id']),
                'amount' => floatval($existing['amount']),
                'memo' => $existing['memo'],
                'expiry_seconds' => intval($expiry_seconds),
                'created_at' => $existing['created_at'],
                'reused' => true
            ]);
            exit;
        }
    }

    // 2. Nếu không trùng hoặc đã quá hạn, chuyển tất cả yêu cầu 'pending' cũ của user này sang 'expired'
    $stmtExpire = $pdo->prepare("UPDATE topup_requests SET status = 'expired' WHERE user_id = ? AND status = 'pending'");
    $stmtExpire->execute([$userId]);

    // 3. Tạo yêu cầu nạp tiền mới
    $stmtInsert = $pdo->prepare("INSERT INTO topup_requests (user_id, amount, memo, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmtInsert->execute([$userId, $amount, $memo]);
    $requestId = $pdo->lastInsertId();
    
    // Lấy lại thời gian vừa tạo từ CSDL để đồng bộ
    $stmtSelect = $pdo->prepare("SELECT created_at FROM topup_requests WHERE id = ?");
    $stmtSelect->execute([$requestId]);
    $createdAt = $stmtSelect->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'request_id' => intval($requestId),
        'amount' => floatval($amount),
        'memo' => $memo,
        'expiry_seconds' => 240,
        'created_at' => $createdAt,
        'reused' => false
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi tạo yêu cầu nạp tiền: ' . $e->getMessage()]);
}
