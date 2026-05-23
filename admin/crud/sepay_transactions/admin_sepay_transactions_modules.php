<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../crud/sepay/sepay_modules.php';

function admin_sepay_getTransactions($limit = 100) {
    global $conn;
    $limit = intval($limit);
    $sql = "SELECT st.*, u.username
    FROM sepay_transactions st
    LEFT JOIN users u ON st.user_id = u.id
    ORDER BY st.id DESC
    LIMIT $limit";
    $result = mysqli_query($conn, $sql);
    $txs = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { $txs[] = $row; }
    }
    return $txs;
}

function admin_sepay_getStats() {
    global $conn;
    $stats = ['total' => 0, 'pending' => 0, 'matched' => 0, 'today_amount' => 0];

    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM sepay_transactions");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['total'] = intval($row['c']);

    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM sepay_transactions WHERE status = 'pending'");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['pending'] = intval($row['c']);

    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM sepay_transactions WHERE status = 'matched'");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['matched'] = intval($row['c']);

    $r = mysqli_query($conn, "SELECT COALESCE(SUM(amount_in), 0) as total FROM sepay_transactions WHERE DATE(transaction_date) = CURDATE() AND status = 'matched'");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['today_amount'] = intval($row['total']);

    return $stats;
}

function admin_sepay_handleRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            $result = ['success' => true, 'data' => admin_sepay_getTransactions(intval($_GET['limit'] ?? 100))];
            break;
        case 'stats':
            $result = ['success' => true, 'data' => admin_sepay_getStats()];
            break;
        case 'sync':
            $result = sepay_syncAndProcess(intval($_POST['limit'] ?? 100));
            break;
        default:
            $result = ['success' => false, 'message' => 'Hành động không hợp lệ'];
    }

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
        admin_sepay_handleRequest();
    }
}
?>
