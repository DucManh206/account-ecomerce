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


/* Assignment-required generic CRUD wrappers for table: sepay_transactions */
if (!function_exists('sepay_transactions_columns')) {
function sepay_transactions_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `sepay_transactions`");
    if ($result) while ($row = mysqli_fetch_assoc($result)) $cols[$row['Field']] = $row;
    return $cols;
}
}
if (!function_exists('sepay_transactions_primary_key')) {
function sepay_transactions_primary_key() {
    foreach (sepay_transactions_columns() as $name => $meta) if (($meta['Key'] ?? '') === 'PRI') return $name;
    return array_key_exists('id', sepay_transactions_columns()) ? 'id' : null;
}
}
if (!function_exists('sepay_transactions_getAll')) {
function sepay_transactions_getAll() {
    global $conn;
    $pk = sepay_transactions_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `sepay_transactions`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}
}
if (!function_exists('sepay_transactions_add')) {
function sepay_transactions_add($data) {
    global $conn;
    $cols = sepay_transactions_columns(); $fields=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if (($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) { $fields[]=$name; $values[]=$data[$name]; }
    }
    if (!$fields) return ['success'=>false,'message'=>'Không có dữ liệu thêm mới'];
    $fieldSql='`'.implode('`,`',$fields).'`'; $ph=implode(',',array_fill(0,count($fields),'?'));
    $stmt=mysqli_prepare($conn,"INSERT INTO `sepay_transactions` ($fieldSql) VALUES ($ph)");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Thêm mới thành công','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('sepay_transactions_update')) {
function sepay_transactions_update($id,$data) {
    global $conn;
    $pk=sepay_transactions_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $cols=sepay_transactions_columns(); $sets=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if ($name===$pk || ($meta['Extra'] ?? '')==='auto_increment') continue;
        if (array_key_exists($name,$data)) { $sets[]="`$name` = ?"; $values[]=$data[$name]; }
    }
    if (!$sets) return ['success'=>false,'message'=>'Không có dữ liệu cập nhật'];
    $values[]=$id; $stmt=mysqli_prepare($conn,"UPDATE `sepay_transactions` SET ".implode(',',$sets)." WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cập nhật thành công'] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('sepay_transactions_delete')) {
function sepay_transactions_delete($id) {
    global $conn;
    $pk=sepay_transactions_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $stmt=mysqli_prepare($conn,"DELETE FROM `sepay_transactions` WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt,'s',$id); $ok=mysqli_stmt_execute($stmt); $affected=mysqli_stmt_affected_rows($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return ($ok && $affected>0) ? ['success'=>true,'message'=>'Xóa thành công'] : ['success'=>false,'message'=>$err ?: 'Không tìm thấy dữ liệu'];
}
}
