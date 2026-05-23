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


/* Assignment-required generic CRUD wrappers for table: sepay_config */
if (!function_exists('sepay_config_columns')) {
function sepay_config_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `sepay_config`");
    if ($result) while ($row = mysqli_fetch_assoc($result)) $cols[$row['Field']] = $row;
    return $cols;
}
}
if (!function_exists('sepay_config_primary_key')) {
function sepay_config_primary_key() {
    foreach (sepay_config_columns() as $name => $meta) if (($meta['Key'] ?? '') === 'PRI') return $name;
    return array_key_exists('id', sepay_config_columns()) ? 'id' : null;
}
}
if (!function_exists('sepay_config_getAll')) {
function sepay_config_getAll() {
    global $conn;
    $pk = sepay_config_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `sepay_config`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}
}
if (!function_exists('sepay_config_add')) {
function sepay_config_add($data) {
    global $conn;
    $cols = sepay_config_columns(); $fields=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if (($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) { $fields[]=$name; $values[]=$data[$name]; }
    }
    if (!$fields) return ['success'=>false,'message'=>'Không có dữ liệu thêm mới'];
    $fieldSql='`'.implode('`,`',$fields).'`'; $ph=implode(',',array_fill(0,count($fields),'?'));
    $stmt=mysqli_prepare($conn,"INSERT INTO `sepay_config` ($fieldSql) VALUES ($ph)");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Thêm mới thành công','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('sepay_config_update')) {
function sepay_config_update($id,$data) {
    global $conn;
    $pk=sepay_config_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $cols=sepay_config_columns(); $sets=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if ($name===$pk || ($meta['Extra'] ?? '')==='auto_increment') continue;
        if (array_key_exists($name,$data)) { $sets[]="`$name` = ?"; $values[]=$data[$name]; }
    }
    if (!$sets) return ['success'=>false,'message'=>'Không có dữ liệu cập nhật'];
    $values[]=$id; $stmt=mysqli_prepare($conn,"UPDATE `sepay_config` SET ".implode(',',$sets)." WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cập nhật thành công'] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('sepay_config_delete')) {
function sepay_config_delete($id) {
    global $conn;
    $pk=sepay_config_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $stmt=mysqli_prepare($conn,"DELETE FROM `sepay_config` WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt,'s',$id); $ok=mysqli_stmt_execute($stmt); $affected=mysqli_stmt_affected_rows($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return ($ok && $affected>0) ? ['success'=>true,'message'=>'Xóa thành công'] : ['success'=>false,'message'=>$err ?: 'Không tìm thấy dữ liệu'];
}
}
