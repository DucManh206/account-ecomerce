<?php
require_once __DIR__ . '/admin/config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn chưa đăng nhập.']);
    exit;
}

$userId = $_SESSION['user_id'];
$requestId = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if ($requestId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Mã yêu cầu nạp tiền không hợp lệ.']);
    exit;
}

try {
    // Lấy yêu cầu nạp tiền từ CSDL
    $stmt = $pdo->prepare("SELECT * FROM topup_requests WHERE id = ? AND user_id = ?");
    $stmt->execute([$requestId, $userId]);
    $request = $stmt->fetch();

    if (!$request) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy yêu cầu nạp tiền tương ứng.']);
        exit;
    }

    $amount = floatval($request['amount']);

    // 1. Kiểm tra trạng thái hiện tại
    if ($request['status'] === 'completed') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Nạp tiền thành công! Đã cộng ' . number_format($amount, 0, ',', '.') . 'đ vào tài khoản.',
            'amount' => $amount
        ]);
        exit;
    }

    if ($request['status'] === 'expired') {
        echo json_encode([
            'status' => 'expired',
            'message' => 'Giao dịch này đã hết thời gian (4 phút) và đã bị huỷ.'
        ]);
        exit;
    }

    // 2. Kiểm tra nếu yêu cầu đã quá hạn 4 phút (240 giây)
    $createdAtTimestamp = strtotime($request['created_at']);
    $elapsed = time() - $createdAtTimestamp;

    if ($elapsed > 240) {
        // Cập nhật trạng thái thành expired
        $stmtUpdate = $pdo->prepare("UPDATE topup_requests SET status = 'expired' WHERE id = ?");
        $stmtUpdate->execute([$requestId]);
        echo json_encode([
            'status' => 'expired',
            'message' => 'Giao dịch đã quá thời gian 4 phút và đã bị huỷ tự động.'
        ]);
        exit;
    }

    // 3. Xử lý logic check giao dịch
    $expectedPrefix = defined('SEPAY_MEMO_PREFIX') ? SEPAY_MEMO_PREFIX : 'NAP';
    $expectedMemo = $request['memo'];

    // --- Chế độ chạy thử (Mock Mode) ---
    if (!defined('SEPAY_API_TOKEN') || SEPAY_API_TOKEN === 'YOUR_SEPAY_API_TOKEN') {
        $mockTxId = 'MOCK_TX_' . $requestId . '_' . time();
        
        try {
            $pdo->beginTransaction();
            
            // Bảo vệ kép: kiểm tra xem giao dịch mock này đã được lưu chưa
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM sepay_transactions WHERE sepay_transaction_id = ?");
            $stmtCheck->execute([$mockTxId]);
            if ($stmtCheck->fetchColumn() == 0) {
                
                // 1. Cập nhật trạng thái yêu cầu
                $stmtReq = $pdo->prepare("UPDATE topup_requests SET status = 'completed' WHERE id = ?");
                $stmtReq->execute([$requestId]);

                // 2. Thêm vào bảng sepay_transactions
                $stmtInsert = $pdo->prepare("INSERT INTO sepay_transactions (sepay_transaction_id, user_id, amount, transaction_date, content) VALUES (?, ?, ?, NOW(), ?)");
                $stmtInsert->execute([$mockTxId, $userId, $amount, $expectedMemo]);
                
                // 3. Cộng số dư tài khoản
                $stmtUpdate = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmtUpdate->execute([$amount, $userId]);
                
                $pdo->commit();

                // Cập nhật số dư trong session
                $_SESSION['user_balance'] = $pdo->query("SELECT balance FROM users WHERE id = $userId")->fetchColumn();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Nạp tiền thành công! Đã cộng ' . number_format($amount, 0, ',', '.') . 'đ vào tài khoản (Chế độ chạy thử).',
                    'amount' => $amount
                ]);
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Lỗi xử lý giao dịch giả lập: ' . $e->getMessage()]);
            exit;
        }
    }

    // --- Chế độ chạy thực tế (Sepay User API transactions/list) ---
    $url = 'https://my.sepay.vn/userapi/transactions/list?limit=50';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . SEPAY_API_TOKEN
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể kết nối đến cổng SePay: ' . $curlError]);
        exit;
    }

    $resData = json_decode($response, true);
    if (!isset($resData['status']) || intval($resData['status']) !== 200 || !isset($resData['transactions'])) {
        $errMsg = $resData['error'] ?? 'Token không hợp lệ hoặc lỗi API từ SePay.';
        echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối cổng SePay: ' . $errMsg]);
        exit;
    }

    $success = false;
    $creditedAmount = 0;

    foreach ($resData['transactions'] as $tx) {
        $txContent = trim($tx['transaction_content'] ?? '');
        $txAmount = floatval($tx['amount_in'] ?? 0);
        $sepayTxId = $tx['id'] ?? '';
        $txDateStr = $tx['transaction_date'] ?? '';

        // 1. Kiểm tra nội dung chuyển khoản khớp mẫu "NAP <userId>" hoặc "NAP<userId>" (không phân biệt hoa thường)
        $pattern = '/\b' . preg_quote($expectedPrefix, '/') . '\s*' . $userId . '\b/i';
        if (preg_match($pattern, $txContent)) {
            
            // 2. Giao dịch phải được thực hiện từ khi tạo yêu cầu nạp tiền trở đi
            $txTimestamp = strtotime($txDateStr);
            // Thêm 30s buffer trong trường hợp giờ máy chủ lệch nhẹ
            if ($txTimestamp >= ($createdAtTimestamp - 30)) {
                
                // 3. Kiểm tra xem giao dịch này đã được ghi nhận trong CSDL chưa
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM sepay_transactions WHERE sepay_transaction_id = ?");
                $stmtCheck->execute([$sepayTxId]);
                $isProcessed = $stmtCheck->fetchColumn() > 0;

                if (!$isProcessed) {
                    try {
                        $pdo->beginTransaction();

                        // Cập nhật trạng thái yêu cầu thành completed
                        $stmtReq = $pdo->prepare("UPDATE topup_requests SET status = 'completed' WHERE id = ?");
                        $stmtReq->execute([$requestId]);

                        // Lưu giao dịch để tránh trùng lặp
                        $stmtInsert = $pdo->prepare("INSERT INTO sepay_transactions (sepay_transaction_id, user_id, amount, transaction_date, content) VALUES (?, ?, ?, ?, ?)");
                        $stmtInsert->execute([
                            $sepayTxId,
                            $userId,
                            $txAmount,
                            $txDateStr ?: date('Y-m-d H:i:s'),
                            $txContent
                        ]);

                        // Cộng số dư của user
                        $stmtUpdate = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                        $stmtUpdate->execute([$txAmount, $userId]);

                        $pdo->commit();

                        // Cập nhật số dư trong session
                        $_SESSION['user_balance'] = $pdo->query("SELECT balance FROM users WHERE id = $userId")->fetchColumn();
                        
                        $success = true;
                        $creditedAmount = $txAmount;
                        break; // Dừng vòng lặp sau khi xử lý thành công giao dịch đầu tiên
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật CSDL: ' . $e->getMessage()]);
                        exit;
                    }
                }
            }
        }
    }

    if ($success) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Đã nhận được thanh toán! Tài khoản của bạn đã được cộng thêm ' . number_format($creditedAmount, 0, ',', '.') . 'đ.',
            'amount' => $creditedAmount
        ]);
    } else {
        echo json_encode([
            'status' => 'pending',
            'message' => 'Hệ thống chưa tìm thấy giao dịch chuyển khoản phù hợp. Vui lòng đợi trong giây lát hoặc kiểm tra lại thông tin chuyển khoản.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
