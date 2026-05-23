<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function banks_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `banks`");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cols[$row['Field']] = $row;
        }
    }
    return $cols;
}

function banks_primary_key() {
    foreach (banks_columns() as $name => $meta) {
        if (($meta['Key'] ?? '') === 'PRI') return $name;
    }
    return array_key_exists('id', banks_columns()) ? 'id' : null;
}

function banks_getAll() {
    global $conn;
    $pk = banks_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `banks`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

function banks_getById($id) {
    global $conn;
    $pk = banks_primary_key();
    if (!$pk) return null;
    $stmt = mysqli_prepare($conn, "SELECT * FROM `banks` WHERE `$pk` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function banks_add($data) {
    global $conn;
    $cols = banks_columns();
    $fields = [];
    $values = [];
    foreach ($cols as $name => $meta) {
        if (($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) {
            $fields[] = $name;
            $values[] = $data[$name];
        }
    }
    if (!$fields) return ['success'=>false,'message'=>'Không có dữ liệu thêm mới'];
    $fieldSql = '`' . implode('`,`', $fields) . '`';
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $stmt = mysqli_prepare($conn, "INSERT INTO `banks` ($fieldSql) VALUES ($placeholders)");
    $types = str_repeat('s', count($values));
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Thêm mới thành công','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}

function banks_update($id, $data) {
    global $conn;
    $pk = banks_primary_key();
    if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $cols = banks_columns();
    $sets = [];
    $values = [];
    foreach ($cols as $name => $meta) {
        if ($name === $pk || ($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) {
            $sets[] = "`$name` = ?";
            $values[] = $data[$name];
        }
    }
    if (!$sets) return ['success'=>false,'message'=>'Không có dữ liệu cập nhật'];
    $values[] = $id;
    $stmt = mysqli_prepare($conn, "UPDATE `banks` SET " . implode(',', $sets) . " WHERE `$pk` = ? LIMIT 1");
    $types = str_repeat('s', count($values));
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cập nhật thành công'] : ['success'=>false,'message'=>$err];
}

function banks_delete($id) {
    global $conn;
    $pk = banks_primary_key();
    if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $stmt = mysqli_prepare($conn, "DELETE FROM `banks` WHERE `$pk` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $id);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return ($ok && $affected > 0) ? ['success'=>true,'message'=>'Xóa thành công'] : ['success'=>false,'message'=>$err ?: 'Không tìm thấy dữ liệu'];
}


/* Assignment-required generic CRUD wrappers for table: banks */
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
    if (!$fields) return ['success'=>false,'message'=>'Không có dữ liệu thêm mới'];
    $fieldSql='`'.implode('`,`',$fields).'`'; $ph=implode(',',array_fill(0,count($fields),'?'));
    $stmt=mysqli_prepare($conn,"INSERT INTO `banks` ($fieldSql) VALUES ($ph)");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Thêm mới thành công','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('banks_update')) {
function banks_update($id,$data) {
    global $conn;
    $pk=banks_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $cols=banks_columns(); $sets=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if ($name===$pk || ($meta['Extra'] ?? '')==='auto_increment') continue;
        if (array_key_exists($name,$data)) { $sets[]="`$name` = ?"; $values[]=$data[$name]; }
    }
    if (!$sets) return ['success'=>false,'message'=>'Không có dữ liệu cập nhật'];
    $values[]=$id; $stmt=mysqli_prepare($conn,"UPDATE `banks` SET ".implode(',',$sets)." WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cập nhật thành công'] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('banks_delete')) {
function banks_delete($id) {
    global $conn;
    $pk=banks_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $stmt=mysqli_prepare($conn,"DELETE FROM `banks` WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt,'s',$id); $ok=mysqli_stmt_execute($stmt); $affected=mysqli_stmt_affected_rows($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return ($ok && $affected>0) ? ['success'=>true,'message'=>'Xóa thành công'] : ['success'=>false,'message'=>$err ?: 'Không tìm thấy dữ liệu'];
}
}
