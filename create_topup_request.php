<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

verify_csrf(true);

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để tạo yêu cầu nạp tiền.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!SEPAY_ENABLED && !SEPAY_MOCK_MODE) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Cổng nạp tiền đang tạm dừng.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$amount = (int) ($_POST['amount'] ?? 0);
$minimumAmount = app_setting_int('min_topup_amount', 10000, 1000);
$maximumAmount = app_setting_int('max_topup_amount', 100000000, $minimumAmount);
$expiryMinutes = app_setting_int('topup_expiry_minutes', 15, 5, 60);
$cutoff = date('Y-m-d H:i:s', time() - ($expiryMinutes * 60));

if ($amount < $minimumAmount || $amount > $maximumAmount) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Số tiền nạp phải từ ' . number_format($minimumAmount, 0, ',', '.') . 'đ đến ' . number_format($maximumAmount, 0, ',', '.') . 'đ.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$expectedPrefix = preg_replace('/[^A-Za-z0-9]/', '', SEPAY_MEMO_PREFIX) ?: 'NAP';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT id, amount, memo, created_at
         FROM topup_requests
         WHERE user_id = ? AND status = 'pending' AND amount = ? AND created_at >= ?
         ORDER BY id DESC LIMIT 1 FOR UPDATE"
    );
    $stmt->execute([$userId, $amount, $cutoff]);
    $existing = $stmt->fetch();

    if ($existing) {
        $elapsed = max(0, time() - strtotime($existing['created_at']));
        $expirySeconds = ($expiryMinutes * 60) - $elapsed;
        if ($expirySeconds > 10) {
            $pdo->commit();
            echo json_encode([
                'status' => 'success',
                'request_id' => (int) $existing['id'],
                'amount' => (float) $existing['amount'],
                'memo' => $existing['memo'],
                'expiry_seconds' => $expirySeconds,
                'created_at' => $existing['created_at'],
                'reused' => true,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $stmtExpire = $pdo->prepare(
        "UPDATE topup_requests SET status = 'expired'
         WHERE user_id = ? AND status = 'pending' AND created_at < ?"
    );
    $stmtExpire->execute([$userId, $cutoff]);

    $stmtInsert = $pdo->prepare(
        "INSERT INTO topup_requests (user_id, amount, memo, status, created_at)
         VALUES (?, ?, '', 'pending', NOW())"
    );
    $stmtInsert->execute([$userId, $amount]);
    $requestId = (int) $pdo->lastInsertId();
    $memo = sprintf('%s %d R%d', strtoupper($expectedPrefix), $userId, $requestId);

    $stmtMemo = $pdo->prepare('UPDATE topup_requests SET memo = ? WHERE id = ?');
    $stmtMemo->execute([$memo, $requestId]);

    $stmtSelect = $pdo->prepare('SELECT created_at FROM topup_requests WHERE id = ?');
    $stmtSelect->execute([$requestId]);
    $createdAt = $stmtSelect->fetchColumn();
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'request_id' => $requestId,
        'amount' => (float) $amount,
        'memo' => $memo,
        'expiry_seconds' => $expiryMinutes * 60,
        'created_at' => $createdAt,
        'reused' => false,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('create_topup_request: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Không thể tạo yêu cầu nạp tiền lúc này.'], JSON_UNESCAPED_UNICODE);
}
