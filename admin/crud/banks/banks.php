<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

if (!function_exists('banks_columns')) {
function banks_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `banks`");
    if ($result) while ($row = mysqli_fetch_assoc($result)) $cols[$row['Field']] = $row;
    return $cols;
}
}

if (!function_exists('banks_primary_key')) {
function banks_primary_key() {
    foreach (banks_columns() as $name => $meta) if (($meta['Key'] ?? '') === 'PRI') return $name;
    return array_key_exists('id', banks_columns()) ? 'id' : null;
}
}

if (!function_exists('banks_getAll')) {
function banks_getAll() {
    global $conn;
    $pk = banks_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `banks`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}
}

if (!function_exists('banks_add')) {
function banks_add($data) {
    global $conn;
    $cols = banks_columns(); $fields=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if (($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) { $fields[]=$name; $values[]=$data[$name]; }
    }
    if (!$fields) return ['success'=>false,'message'=>'Khong co du lieu them moi'];
    $fieldSql='`'.implode('`,`',$fields).'`'; $ph=implode(',',array_fill(0,count($fields),'?'));
    $stmt=mysqli_prepare($conn,"INSERT INTO `banks` ($fieldSql) VALUES ($ph)");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Them moi thanh cong','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}
}

if (!function_exists('banks_update')) {
function banks_update($id,$data) {
    global $conn;
    $pk=banks_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Khong tim thay khoa chinh'];
    $cols=banks_columns(); $sets=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if ($name===$pk || ($meta['Extra'] ?? '')==='auto_increment') continue;
        if (array_key_exists($name,$data)) { $sets[]="`$name` = ?"; $values[]=$data[$name]; }
    }
    if (!$sets) return ['success'=>false,'message'=>'Khong co du lieu cap nhat'];
    $values[]=$id; $stmt=mysqli_prepare($conn,"UPDATE `banks` SET ".implode(',',$sets)." WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cap nhat thanh cong'] : ['success'=>false,'message'=>$err];
}
}

if (!function_exists('banks_delete')) {
function banks_delete($id) {
    global $conn;
    $pk=banks_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Khong tim thay khoa chinh'];
    $stmt=mysqli_prepare($conn,"DELETE FROM `banks` WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt,'s',$id); $ok=mysqli_stmt_execute($stmt); $affected=mysqli_stmt_affected_rows($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return ($ok && $affected>0) ? ['success'=>true,'message'=>'Xoa thanh cong'] : ['success'=>false,'message'=>$err ?: 'Khong tim thay du lieu'];
}
}
