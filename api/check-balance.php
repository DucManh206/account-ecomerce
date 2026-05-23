<?php
/**
 * API Check Balance
 * Dùng để auto-refresh balance trên trang nạp tiền
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/connect.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = intval($_SESSION['user_id']);

$sql = "SELECT balance FROM users WHERE id = ?";
$stmt = mysqli_prepare($GLOBALS['conn'], $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'balance' => intval($row['balance'])
    ]);
} else {
    echo json_encode(['error' => 'User not found']);
}
