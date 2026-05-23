<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getSepayConfig() {
    global $conn;
    $sql = "SELECT * FROM sepay_config LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_saveSepayConfig($data) {
    global $conn;

    $fields = [
        'api_token' => "api_token = '" . mysqli_real_escape_string($conn, trim($data['api_token'] ?? '')) . "'",
        'account_number' => "account_number = '" . mysqli_real_escape_string($conn, trim($data['account_number'] ?? '')) . "'",
        'account_holder' => "account_holder = '" . mysqli_real_escape_string($conn, trim($data['account_holder'] ?? '')) . "'",
        'bank_code' => "bank_code = '" . mysqli_real_escape_string($conn, trim($data['bank_code'] ?? '')) . "'",
        'auto_process' => "auto_process = " . (isset($data['auto_process']) ? 1 : 0),
        'min_amount' => "min_amount = " . intval($data['min_amount'] ?? 10000),
        'max_amount' => "max_amount = " . intval($data['max_amount'] ?? 500000000),
        'transfer_prefix' => "transfer_prefix = '" . mysqli_real_escape_string($conn, trim($data['transfer_prefix'] ?? 'NT')) . "'",
        'check_interval_minutes' => "check_interval_minutes = " . intval($data['check_interval_minutes'] ?? 5),
        'cancel_after_minutes' => "cancel_after_minutes = " . intval($data['cancel_after_minutes'] ?? 30),
        'webhook_secret' => "webhook_secret = '" . mysqli_real_escape_string($conn, trim($data['webhook_secret'] ?? '')) . "'",
        'status' => "status = " . (isset($data['status']) ? 1 : 0),
    ];

    $sql = "UPDATE sepay_config SET " . implode(', ', $fields) . " LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Lưu cấu hình thành công'];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_getSepayTransactions($limit = 100) {
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

function admin_getSepayStats() {
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

function admin_handleSepayConfigRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'save':
            $result = admin_saveSepayConfig($_POST);
            break;
        case 'get':
            $result = ['success' => true, 'data' => admin_getSepayConfig()];
            break;
        case 'transactions':
            $result = ['success' => true, 'data' => admin_getSepayTransactions(intval($_GET['limit'] ?? 100))];
            break;
        case 'stats':
            $result = ['success' => true, 'data' => admin_getSepayStats()];
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
        admin_handleSepayConfigRequest();
    }
}
?>
