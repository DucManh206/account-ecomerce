<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function orders_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `orders`");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cols[$row['Field']] = $row;
        }
    }
    return $cols;
}

function orders_primary_key() {
    foreach (orders_columns() as $name => $meta) {
        if (($meta['Key'] ?? '') === 'PRI') return $name;
    }
    return array_key_exists('id', orders_columns()) ? 'id' : null;
}

function orders_getAll() {
    global $conn;
    $pk = orders_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `orders`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

function orders_getById($id) {
    global $conn;
    $pk = orders_primary_key();
    if (!$pk) return null;
    $stmt = mysqli_prepare($conn, "SELECT * FROM `orders` WHERE `$pk` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function orders_add($data) {
    global $conn;
    $cols = orders_columns();
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
    $stmt = mysqli_prepare($conn, "INSERT INTO `orders` ($fieldSql) VALUES ($placeholders)");
    $types = str_repeat('s', count($values));
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Thêm mới thành công','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}

function orders_update($id, $data) {
    global $conn;
    $pk = orders_primary_key();
    if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $cols = orders_columns();
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
    $stmt = mysqli_prepare($conn, "UPDATE `orders` SET " . implode(',', $sets) . " WHERE `$pk` = ? LIMIT 1");
    $types = str_repeat('s', count($values));
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cập nhật thành công'] : ['success'=>false,'message'=>$err];
}

function orders_delete($id) {
    global $conn;
    $pk = orders_primary_key();
    if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $stmt = mysqli_prepare($conn, "DELETE FROM `orders` WHERE `$pk` = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $id);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return ($ok && $affected > 0) ? ['success'=>true,'message'=>'Xóa thành công'] : ['success'=>false,'message'=>$err ?: 'Không tìm thấy dữ liệu'];
}


/* Assignment-required generic CRUD wrappers for table: orders */
if (!function_exists('orders_columns')) {
function orders_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `orders`");
    if ($result) while ($row = mysqli_fetch_assoc($result)) $cols[$row['Field']] = $row;
    return $cols;
}
}
if (!function_exists('orders_primary_key')) {
function orders_primary_key() {
    foreach (orders_columns() as $name => $meta) if (($meta['Key'] ?? '') === 'PRI') return $name;
    return array_key_exists('id', orders_columns()) ? 'id' : null;
}
}
if (!function_exists('orders_getAll')) {
function orders_getAll() {
    global $conn;
    $pk = orders_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `orders`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}
}
if (!function_exists('orders_add')) {
function orders_add($data) {
    global $conn;
    $cols = orders_columns(); $fields=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if (($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) { $fields[]=$name; $values[]=$data[$name]; }
    }
    if (!$fields) return ['success'=>false,'message'=>'Không có dữ liệu thêm mới'];
    $fieldSql='`'.implode('`,`',$fields).'`'; $ph=implode(',',array_fill(0,count($fields),'?'));
    $stmt=mysqli_prepare($conn,"INSERT INTO `orders` ($fieldSql) VALUES ($ph)");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Thêm mới thành công','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('orders_update')) {
function orders_update($id,$data) {
    global $conn;
    $pk=orders_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $cols=orders_columns(); $sets=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if ($name===$pk || ($meta['Extra'] ?? '')==='auto_increment') continue;
        if (array_key_exists($name,$data)) { $sets[]="`$name` = ?"; $values[]=$data[$name]; }
    }
    if (!$sets) return ['success'=>false,'message'=>'Không có dữ liệu cập nhật'];
    $values[]=$id; $stmt=mysqli_prepare($conn,"UPDATE `orders` SET ".implode(',',$sets)." WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cập nhật thành công'] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('orders_delete')) {
function orders_delete($id) {
    global $conn;
    $pk=orders_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $stmt=mysqli_prepare($conn,"DELETE FROM `orders` WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt,'s',$id); $ok=mysqli_stmt_execute($stmt); $affected=mysqli_stmt_affected_rows($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return ($ok && $affected>0) ? ['success'=>true,'message'=>'Xóa thành công'] : ['success'=>false,'message'=>$err ?: 'Không tìm thấy dữ liệu'];
}
}
