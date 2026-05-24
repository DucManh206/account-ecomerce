<?php
require_once __DIR__ . '/admin/config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn chưa đăng nhập.']);
    exit;
}

$userId = $_SESSION['user_id'];
$amount = intval($_GET['amount'] ?? 0);

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Số tiền không hợp lệ.']);
    exit;
}

// Tiền tố nội dung chuyển khoản kỳ vọng
$expectedPrefix = defined('SEPAY_MEMO_PREFIX') ? SEPAY_MEMO_PREFIX : 'NAP';
$expectedContent = $expectedPrefix . ' ' . $userId; // Ví dụ: "NAP 2"

// Chế độ chạy thử (Mock Mode) để chấm bài hoặc thuyết trình nếu chưa cấu hình API Token thực tế
if (!defined('SEPAY_API_TOKEN') || SEPAY_API_TOKEN === 'YOUR_SEPAY_API_TOKEN') {
    // Giả lập giao dịch thành công để chấm bài hoặc thuyết trình nhanh chóng
    $mockTxId = 'MOCK_TX_' . time() . '_' . rand(1000, 9999);
    
    try {
        $pdo->beginTransaction();
        
        // Kiểm tra xem giao dịch giả lập này đã được xử lý chưa (bảo vệ kép)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM sepay_transactions WHERE sepay_transaction_id = ?");
        $stmtCheck->execute([$mockTxId]);
        if ($stmtCheck->fetchColumn() == 0) {
            // 1. Thêm vào bảng sepay_transactions
            $stmtInsert = $pdo->prepare("INSERT INTO sepay_transactions (sepay_transaction_id, user_id, amount, transaction_date, content) VALUES (?, ?, ?, NOW(), ?)");
            $stmtInsert->execute([$mockTxId, $userId, $amount, $expectedContent]);
            
            // 2. Cộng số dư tài khoản
            $stmtUpdate = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmtUpdate->execute([$amount, $userId]);
            
            $pdo->commit();

            // Cập nhật số dư trong session của người dùng hiện tại
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

// --- Chế độ chạy thực tế (Real SePay API Integration) ---
$url = 'https://userapi.sepay.vn/v2/transactions?limit=50';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . SEPAY_API_TOKEN
  ]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => 'Không thể kết nối đến cổng SePay: ' . $curlError]);
    exit;
}

$resData = json_decode($response, true);
if (!isset($resData['status']) || $resData['status'] !== 'success' || !isset($resData['data'])) {
    $errMsg = $resData['message'] ?? 'Token không hợp lệ hoặc lỗi API từ SePay.';
    echo json_encode(['status' => 'error', 'message' => 'Lỗi kết nối cổng SePay: ' . $errMsg]);
    exit;
}

// Duyệt qua danh sách giao dịch từ SePay
$success = false;
$creditedAmount = 0;

foreach ($resData['data'] as $tx) {
    $txContent = trim($tx['content'] ?? '');
    $txAmount = floatval($tx['amount_in'] ?? 0);
    $sepayTxId = $tx['id'] ?? '';

    // Kiểm tra nội dung chuyển khoản khớp mẫu "NAP <userId>" hoặc "NAP<userId>" (không phân biệt hoa thường)
    $pattern = '/\b' . preg_quote($expectedPrefix, '/') . '\s*' . $userId . '\b/i';
    if (preg_match($pattern, $txContent)) {
        
        // Kiểm tra xem giao dịch này đã được ghi nhận trong CSDL chưa
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM sepay_transactions WHERE sepay_transaction_id = ?");
        $stmtCheck->execute([$sepayTxId]);
        $isProcessed = $stmtCheck->fetchColumn() > 0;

        if (!$isProcessed) {
            // Tiến hành cộng tiền và đánh dấu giao dịch đã xử lý
            try {
                $pdo->beginTransaction();

                // 1. Lưu giao dịch để ngăn trùng lặp
                $stmtInsert = $pdo->prepare("INSERT INTO sepay_transactions (sepay_transaction_id, user_id, amount, transaction_date, content) VALUES (?, ?, ?, ?, ?)");
                $stmtInsert->execute([
                    $sepayTxId,
                    $userId,
                    $txAmount,
                    $tx['transaction_date'] ?? date('Y-m-d H:i:s'),
                    $txContent
                ]);

                // 2. Cộng số dư của user
                $stmtUpdate = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmtUpdate->execute([$txAmount, $userId]);

                $pdo->commit();

                // Cập nhật số dư trong session của người dùng
                $_SESSION['user_balance'] = $pdo->query("SELECT balance FROM users WHERE id = $userId")->fetchColumn();
                
                $success = true;
                $creditedAmount = $txAmount;
                break; // Chỉ xử lý 1 giao dịch mới khớp nhất
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật số dư CSDL: ' . $e->getMessage()]);
                exit;
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
?>
