<?php
require_once __DIR__ . '/../../../config/db.php';

function admin_getDepositRequests($status = null, $limit = 100) {
    global $conn;
    $requests = array();

    if (!$conn) return $requests;

    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'deposit_requests'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
        return $requests;
    }

    if ($status !== null && $status !== '') {
        $safeStatus = $conn->real_escape_string($status);
        $limit = intval($limit);
        $sql = "SELECT dr.*, u.username, b.bank_name
                FROM deposit_requests dr
                LEFT JOIN users u ON dr.user_id = u.id
                LEFT JOIN banks b ON dr.bank_id = b.id
                WHERE dr.status = '$safeStatus'
                ORDER BY dr.created_at DESC
                LIMIT $limit";
    } else {
        $limit = intval($limit);
        $sql = "SELECT dr.*, u.username, b.bank_name
                FROM deposit_requests dr
                LEFT JOIN users u ON dr.user_id = u.id
                LEFT JOIN banks b ON dr.bank_id = b.id
                ORDER BY dr.created_at DESC
                LIMIT $limit";
    }

    $result = mysqli_query($conn, $sql);
    if (!$result) return $requests;

    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    return $requests;
}

function admin_getDepositRequestById($id) {
    global $conn;
    $id = intval($id);

    $stmt = mysqli_prepare($conn,
        "SELECT dr.*, u.username, u.balance as current_balance, b.bank_name
         FROM deposit_requests dr
         LEFT JOIN users u ON dr.user_id = u.id
         LEFT JOIN banks b ON dr.bank_id = b.id
         WHERE dr.id = ?"
    );
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function admin_approveDeposit($id, $adminId) {
    global $conn;

    $id = intval($id);
    $adminId = intval($adminId);

    $request = admin_getDepositRequestById($id);
    if (!$request) {
        return array('success' => false, 'message' => 'Khong tim thay yeu cau');
    }
    if ($request['status'] !== 'pending') {
        return array('success' => false, 'message' => 'Yeu cau da duoc xu ly');
    }

    $userId = intval($request['user_id']);
    $amount = intval($request['amount']);
    $balanceBefore = intval($request['current_balance']);
    $balanceAfter = $balanceBefore + $amount;

    $stmt = mysqli_prepare($conn,
        "UPDATE deposit_requests SET status = 'approved', processed_by = ?, processed_at = NOW() WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $adminId, $id);
    if (!mysqli_stmt_execute($stmt)) {
        return array('success' => false, 'message' => 'Loi khi cap nhat trang thai');
    }

    $stmt2 = mysqli_prepare($conn, "UPDATE users SET balance = balance + ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "ii", $amount, $userId);
    mysqli_stmt_execute($stmt2);

    $bankName = $request['bank_name'] ?? 'N/A';
    $desc = "Nap tien qua ngan hang " . $bankName . " (#" . $id . ")";
    $stmt3 = mysqli_prepare($conn,
        "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
         VALUES (?, ?, ?, ?, 'topup', ?, NOW())"
    );
    mysqli_stmt_bind_param($stmt3, "iiiis", $userId, $amount, $balanceBefore, $balanceAfter, $desc);
    mysqli_stmt_execute($stmt3);

    return array('success' => true, 'message' => 'Da duyet thanh cong!', 'amount' => $amount);
}

function admin_rejectDeposit($id, $adminId, $reason = '') {
    global $conn;

    $id = intval($id);
    $adminId = intval($adminId);
    $reason = substr(trim($reason), 0, 255);

    $request = admin_getDepositRequestById($id);
    if (!$request) {
        return array('success' => false, 'message' => 'Khong tim thay yeu cau');
    }
    if ($request['status'] !== 'pending') {
        return array('success' => false, 'message' => 'Yeu cau da duoc xu ly');
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE deposit_requests SET status = 'rejected', admin_note = ?, processed_by = ?, processed_at = NOW() WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt, "sii", $reason, $adminId, $id);

    if (mysqli_stmt_execute($stmt)) {
        return array('success' => true, 'message' => 'Da tu choi yeu cau');
    }
    return array('success' => false, 'message' => 'Loi khi cap nhat trang thai');
}

function admin_getDepositStats() {
    global $conn;

    $stats = array(
        'total' => 0,
        'pending' => 0,
        'approved_today' => 0,
        'amount_today' => 0,
        'amount_month' => 0
    );

    if (!$conn) return $stats;

    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'deposit_requests'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
        return $stats;
    }

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM deposit_requests");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['total'] = intval($row['total']);

    $r = mysqli_query($conn, "SELECT COUNT(*) as pending FROM deposit_requests WHERE status = 'pending'");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['pending'] = intval($row['pending']);

    $r = mysqli_query($conn, "SELECT COUNT(*) as approved_today FROM deposit_requests WHERE status = 'approved' AND DATE(processed_at) = CURDATE()");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['approved_today'] = intval($row['approved_today']);

    $r = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as amount_today FROM deposit_requests WHERE status = 'approved' AND DATE(processed_at) = CURDATE()");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['amount_today'] = intval($row['amount_today']);

    $r = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as amount_month FROM deposit_requests WHERE status = 'approved' AND MONTH(processed_at) = MONTH(CURDATE()) AND YEAR(processed_at) = YEAR(CURDATE())");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['amount_month'] = intval($row['amount_month']);

    return $stats;
}
?>
