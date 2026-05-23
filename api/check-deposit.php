<?php
/**
 * API: Kiểm tra trạng thái nạp tiền
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

$userId = intval($_GET['user_id'] ?? 0);

if ($userId <= 0) {
    echo json_encode(['approved' => false]);
    exit;
}

require_once __DIR__ . '/../database/connect.php';

// Kiểm tra xem có deposit request nào vừa được approved không
$sql = "SELECT id, amount FROM deposit_requests 
        WHERE user_id = ? 
        AND status = 'approved' 
        AND processed_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
        ORDER BY processed_at DESC LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode([
        'approved' => true,
        'amount' => $row['amount']
    ]);
} else {
    echo json_encode(['approved' => false]);
}
