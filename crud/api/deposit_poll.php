<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../sepay/sepay_modules.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập', 'auth' => false]);
    exit;
}

$userId = intval($_SESSION['user_id']);
$depositId = intval($_POST['deposit_id'] ?? $_GET['deposit_id'] ?? 0);

if ($depositId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu mã yêu cầu nạp']);
    exit;
}

// Hết hạn trước, để trang tự đổi trạng thái sau 1 phút.
sepay_expireOldRequests();

$stmt = mysqli_prepare($conn, "SELECT * FROM deposit_requests WHERE id = ? AND user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ii', $depositId, $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$deposit = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$deposit) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu nạp']);
    exit;
}

if ($deposit['status'] === 'pending') {
    // Chỉ gọi SePay khi còn pending. Frontend gọi 3 giây/lần, backend cũng có lock rate-limit.
    sepay_syncAndProcess(50);

    $stmt = mysqli_prepare($conn, "SELECT * FROM deposit_requests WHERE id = ? AND user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $depositId, $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $deposit = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

$balance = 0;
$userRes = mysqli_query($conn, "SELECT balance FROM users WHERE id = $userId LIMIT 1");
if ($userRes && $u = mysqli_fetch_assoc($userRes)) $balance = intval($u['balance']);

$now = time();
$expiresAt = !empty($deposit['expires_at']) ? strtotime($deposit['expires_at']) : null;
$remaining = $expiresAt ? max(0, $expiresAt - $now) : 0;

echo json_encode([
    'success' => true,
    'deposit_id' => intval($deposit['id']),
    'status' => $deposit['status'],
    'amount' => intval($deposit['amount']),
    'unique_code' => $deposit['unique_code'],
    'remaining_seconds' => $remaining,
    'balance' => $balance,
    'message' => $deposit['status'] === 'approved' ? 'Nạp tiền thành công' : ($deposit['status'] === 'cancelled' ? 'Yêu cầu nạp đã bị hủy' : 'Đang chờ giao dịch')
], JSON_UNESCAPED_UNICODE);
