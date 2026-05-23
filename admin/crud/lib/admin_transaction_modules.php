<?php
require_once __DIR__ . '/../../../admin/crud/layout/admin_layout_modules.php';

function transactions_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `transactions`");
    if ($result) while ($row = mysqli_fetch_assoc($result)) $cols[$row['Field']] = $row;
    return $cols;
}

function transactions_primary_key() {
    foreach (transactions_columns() as $name => $meta) if (($meta['Key'] ?? '') === 'PRI') return $name;
    return array_key_exists('id', transactions_columns()) ? 'id' : null;
}

function transactions_getAll() {
    global $conn;
    $pk = transactions_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `transactions`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

function transactions_getById($id) {
    global $conn;
    $pk = transactions_primary_key();
    if (!$pk) return null;
    $stmt = mysqli_prepare($conn, "SELECT * FROM `transactions` WHERE `$pk` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function transactions_add($data) {
    global $conn;
    $cols = transactions_columns();
    $fields = [];
    $values = [];
    foreach ($cols as $name => $meta) {
        if (($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) {
            $fields[] = $name;
            $values[] = $data[$name];
        }
    }
    if (!$fields) return ['success'=>false,'message'=>'Khong co du lieu them moi'];
    $fieldSql = '`' . implode('`,`', $fields) . '`';
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $stmt = mysqli_prepare($conn, "INSERT INTO `transactions` ($fieldSql) VALUES ($placeholders)");
    $types = str_repeat('s', count($values));
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Them moi thanh cong','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}

function transactions_update($id, $data) {
    global $conn;
    $pk = transactions_primary_key();
    if (!$pk) return ['success'=>false,'message'=>'Khong tim thay khoa chinh'];
    $cols = transactions_columns();
    $sets = [];
    $values = [];
    foreach ($cols as $name => $meta) {
        if ($name === $pk || ($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) {
            $sets[] = "`$name` = ?";
            $values[] = $data[$name];
        }
    }
    if (!$sets) return ['success'=>false,'message'=>'Khong co du lieu cap nhat'];
    $values[] = $id;
    $stmt = mysqli_prepare($conn, "UPDATE `transactions` SET " . implode(',', $sets) . " WHERE `$pk` = ? LIMIT 1");
    $types = str_repeat('s', count($values));
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cap nhat thanh cong'] : ['success'=>false,'message'=>$err];
}

function transactions_delete($id) {
    global $conn;
    $pk = transactions_primary_key();
    if (!$pk) return ['success'=>false,'message'=>'Khong tim thay khoa chinh'];
    $stmt = mysqli_prepare($conn, "DELETE FROM `transactions` WHERE `$pk` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $id);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return ($ok && $affected > 0) ? ['success'=>true,'message'=>'Xoa thanh cong'] : ['success'=>false,'message'=>$err ?: 'Khong tim thay du lieu'];
}

function transactions_handleRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    switch ($action) {
        case 'create':
            $result = transactions_add($_POST);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? transactions_update($id, $_POST) : ['success'=>false,'message'=>'ID khong hop le'];
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? transactions_delete($id) : ['success'=>false,'message'=>'ID khong hop le'];
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $item = transactions_getById($id);
            $result = ['success'=>$item !== null, 'data'=>$item];
            break;
        case 'list':
            $result = ['success'=>true, 'data'=>transactions_getAll()];
            break;
        default:
            $result = ['success'=>false,'message'=>'Hanh dong khong hop le'];
    }
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
        transactions_handleRequest();
    }
}
