<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db.php';
session_start();

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$depositId = intval($_POST['deposit_id'] ?? 0);
if ($depositId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid deposit ID']);
    exit;
}

// Verify ownership and status
$stmt = $GLOBALS['conn']->prepare(
    "SELECT id, status FROM deposit_requests WHERE id = ? AND user_id = ?"
);
$stmt->bind_param("ii", $depositId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$dep = $result->fetch_assoc();

if (!$dep) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu']);
    exit;
}

if ($dep['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu này không thể hủy']);
    exit;
}

$stmt = $GLOBALS['conn']->prepare(
    "UPDATE deposit_requests SET status = 'cancelled' WHERE id = ?"
);
$stmt->bind_param("i", $depositId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đã hủy yêu cầu']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $GLOBALS['conn']->error]);
}
