<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

function topup_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalize_payment_memo(string $memo): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $memo));
}

function complete_topup(
    PDO $pdo,
    array $request,
    string $sourceType,
    ?array $sepayTransaction = null
): array {
    $requestId = (int) $request['id'];
    $userId = (int) $request['user_id'];
    $amount = (float) $request['amount'];

    $pdo->beginTransaction();
    try {
        $lockStmt = $pdo->prepare('SELECT status, amount FROM topup_requests WHERE id = ? AND user_id = ? FOR UPDATE');
        $lockStmt->execute([$requestId, $userId]);
        $lockedRequest = $lockStmt->fetch();

        if (!$lockedRequest) {
            throw new RuntimeException('Yêu cầu nạp tiền không còn tồn tại.');
        }
        if ($lockedRequest['status'] === 'completed') {
            $pdo->rollBack();
            $balanceStmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
            $balanceStmt->execute([$userId]);
            return [
                'already_completed' => true,
                'balance_after' => (float) ($balanceStmt->fetchColumn() ?: 0),
            ];
        }
        if ($lockedRequest['status'] !== 'pending') {
            throw new RuntimeException('Yêu cầu nạp tiền không còn ở trạng thái chờ xử lý.');
        }

        $userStmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
        $userStmt->execute([$userId]);
        $currentBalance = $userStmt->fetchColumn();
        if ($currentBalance === false) {
            throw new RuntimeException('Không tìm thấy tài khoản nhận tiền.');
        }

        $ledgerSourceType = 'topup_request';
        $ledgerSourceId = $requestId;
        if ($sourceType === 'sepay' && $sepayTransaction) {
            $insertSepay = $pdo->prepare(
                'INSERT INTO sepay_transactions
                    (sepay_transaction_id, user_id, amount, transaction_date, content)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $insertSepay->execute([
                $sepayTransaction['id'],
                $userId,
                $amount,
                $sepayTransaction['transaction_date'] ?: date('Y-m-d H:i:s'),
                $sepayTransaction['content'],
            ]);
            $ledgerSourceType = 'sepay';
            $ledgerSourceId = (int) $pdo->lastInsertId();
        }

        $balanceAfter = (float) $currentBalance + $amount;
        $updateUser = $pdo->prepare('UPDATE users SET balance = ? WHERE id = ?');
        $updateUser->execute([$balanceAfter, $userId]);

        $updateRequest = $pdo->prepare("UPDATE topup_requests SET status = 'completed' WHERE id = ?");
        $updateRequest->execute([$requestId]);

        record_balance_transaction(
            $pdo,
            $userId,
            'deposit',
            $amount,
            $balanceAfter,
            $ledgerSourceType,
            $ledgerSourceId,
            $sourceType === 'sepay' ? 'Nạp tiền qua SePay' : 'Nạp tiền ở chế độ kiểm thử'
        );

        $pdo->commit();
        return ['already_completed' => false, 'balance_after' => $balanceAfter];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    topup_json(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ.'], 405);
}
verify_csrf(true);

if (!is_logged_in()) {
    topup_json(['status' => 'error', 'message' => 'Bạn cần đăng nhập để kiểm tra giao dịch.'], 401);
}

$userId = (int) $_SESSION['user_id'];
$requestId = (int) ($_POST['request_id'] ?? 0);
if ($requestId <= 0) {
    topup_json(['status' => 'error', 'message' => 'Mã yêu cầu nạp tiền không hợp lệ.']);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM topup_requests WHERE id = ? AND user_id = ?');
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();
    if (!$request) {
        topup_json(['status' => 'error', 'message' => 'Không tìm thấy yêu cầu nạp tiền tương ứng.'], 404);
    }

    $amount = (float) $request['amount'];
    if ($request['status'] === 'completed') {
        topup_json([
            'status' => 'success',
            'message' => 'Yêu cầu đã được ghi nhận. Số dư đã cộng ' . number_format($amount, 0, ',', '.') . 'đ.',
            'amount' => $amount,
        ]);
    }
    if (in_array($request['status'], ['expired', 'rejected', 'cancelled'], true)) {
        topup_json([
            'status' => $request['status'] === 'expired' ? 'expired' : 'error',
            'message' => $request['status'] === 'expired'
                ? 'Yêu cầu nạp tiền đã hết hạn.'
                : 'Yêu cầu nạp tiền đã bị từ chối hoặc hủy.',
        ]);
    }

    $expiryMinutes = app_setting_int('topup_expiry_minutes', 15, 5, 60);
    if (time() - strtotime($request['created_at']) > ($expiryMinutes * 60)) {
        $expireStmt = $pdo->prepare("UPDATE topup_requests SET status = 'expired' WHERE id = ? AND status = 'pending'");
        $expireStmt->execute([$requestId]);
        topup_json(['status' => 'expired', 'message' => 'Yêu cầu nạp tiền đã quá thời gian xử lý.']);
    }

    if (!SEPAY_ENABLED || SEPAY_API_TOKEN === 'YOUR_SEPAY_API_TOKEN' || SEPAY_API_TOKEN === 'SEPAY_TOKEN_O_DAY') {
        if (!SEPAY_MOCK_MODE) {
            topup_json([
                'status' => 'error',
                'message' => 'Cổng thanh toán đang tạm dừng. Vui lòng liên hệ hỗ trợ hoặc thử lại sau.',
            ], 503);
        }

        $result = complete_topup($pdo, $request, 'mock');
        $_SESSION['user_balance'] = $result['balance_after'];
        topup_json([
            'status' => 'success',
            'message' => 'Đã cộng ' . number_format($amount, 0, ',', '.') . 'đ ở chế độ kiểm thử.',
            'amount' => $amount,
        ]);
    }

    $ch = curl_init('https://my.sepay.vn/userapi/transactions/list?limit=50');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SEPAY_API_TOKEN,
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        error_log('SePay connection failed: ' . $curlError . ' HTTP ' . $httpCode);
        topup_json(['status' => 'error', 'message' => 'Chưa thể đồng bộ giao dịch ngân hàng. Vui lòng thử lại sau.'], 502);
    }

    $responseData = json_decode($response, true);
    if (!is_array($responseData) || (int) ($responseData['status'] ?? 0) !== 200 || !isset($responseData['transactions'])) {
        topup_json(['status' => 'error', 'message' => 'Cấu hình kết nối SePay chưa hợp lệ.'], 502);
    }

    $expectedMemo = normalize_payment_memo($request['memo']);
    $createdAt = strtotime($request['created_at']);

    foreach ($responseData['transactions'] as $transaction) {
        $transactionId = trim((string) ($transaction['id'] ?? ''));
        $content = trim((string) ($transaction['transaction_content'] ?? ''));
        $transactionAmount = (float) ($transaction['amount_in'] ?? 0);
        $transactionDate = (string) ($transaction['transaction_date'] ?? '');
        $transactionTime = strtotime($transactionDate) ?: 0;

        if ($transactionId === '' || normalize_payment_memo($content) !== $expectedMemo) {
            continue;
        }
        if (abs($transactionAmount - $amount) >= 1 || $transactionTime < ($createdAt - 30)) {
            continue;
        }

        $duplicateStmt = $pdo->prepare('SELECT COUNT(*) FROM sepay_transactions WHERE sepay_transaction_id = ?');
        $duplicateStmt->execute([$transactionId]);
        if ((int) $duplicateStmt->fetchColumn() > 0) {
            continue;
        }

        $result = complete_topup($pdo, $request, 'sepay', [
            'id' => $transactionId,
            'transaction_date' => $transactionDate,
            'content' => $content,
        ]);
        $_SESSION['user_balance'] = $result['balance_after'];
        topup_json([
            'status' => 'success',
            'message' => 'Đã nhận thanh toán và cộng ' . number_format($amount, 0, ',', '.') . 'đ vào số dư.',
            'amount' => $amount,
        ]);
    }

    topup_json([
        'status' => 'pending',
        'message' => 'Chưa thấy giao dịch khớp chính xác số tiền và nội dung chuyển khoản.',
    ]);
} catch (Throwable $e) {
    error_log('check_topup: ' . $e->getMessage());
    topup_json(['status' => 'error', 'message' => 'Không thể kiểm tra giao dịch lúc này.'], 500);
}
