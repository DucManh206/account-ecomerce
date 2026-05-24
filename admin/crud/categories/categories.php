<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getCategories()
{
    global $conn;
    $sql = "SELECT c.*,
 (SELECT COUNT(*) FROM types t WHERE t.category_id = c.id) as type_count
 FROM categories c
 ORDER BY c.sort_order ASC, c.name ASC";
    $result = mysqli_query($conn, $sql);
    $cats = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cats[] = $row;
        }
    }
    return $cats;
}

function admin_getCategoryById($id)
{
    global $conn;
    $id = intval($id);
    $sql = "SELECT * FROM categories WHERE id = $id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_getCategoryIconChoices()
{
    return [
        ['fa-folder', 'Folder'],
        ['fa-gamepad', 'Game'],
        ['fa-robot', 'AI'],
        ['fa-youtube', 'YouTube'],
        ['fa-spotify', 'Spotify'],
        ['fa-n', 'Netflix'],
        ['fa-play', 'Disney+'],
        ['fa-cloud', 'Cloud'],
        ['fa-share-nodes', 'Social'],
        ['fa-wand-magic-sparkles', 'Magic'],
        ['fa-fire', 'Fire'],
    ];
}

function admin_createCategory($data)
{
    global $conn;

    $name = trim($data['name'] ?? '');
    $icon_class = mysqli_real_escape_string($conn, $data['icon_class'] ?? 'fa-folder');
    $description = mysqli_real_escape_string($conn, $data['description'] ?? '');
    $sort_order = intval($data['sort_order'] ?? 0);

    if (empty($name)) {
        return ['success' => false, 'message' => 'Tên danh mục không được để trống'];
    }

    $nameEsc = mysqli_real_escape_string($conn, $name);

    $check = mysqli_query($conn, "SELECT id FROM categories WHERE name = '$nameEsc' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        return ['success' => false, 'message' => 'Danh mục đã tồn tại'];
    }

    $sql = "INSERT INTO categories (name, icon_class, description, sort_order) VALUES ('$nameEsc', '$icon_class', '$description', $sort_order)";
    if (mysqli_query($conn, $sql)) {
        $catId = mysqli_insert_id($conn);
        return ['success' => true, 'message' => 'Thêm danh mục thành công', 'id' => $catId];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_updateCategory($id, $data)
{
    global $conn;
    $id = intval($id);

    $name = trim($data['name'] ?? '');
    if (empty($name)) {
        return ['success' => false, 'message' => 'Tên danh mục không được để trống'];
    }

    $nameEsc = mysqli_real_escape_string($conn, $name);
    $icon_class = mysqli_real_escape_string($conn, $data['icon_class'] ?? 'fa-folder');
    $description = mysqli_real_escape_string($conn, $data['description'] ?? '');
    $sort_order = intval($data['sort_order'] ?? 0);

    $check = mysqli_query($conn, "SELECT id FROM categories WHERE name = '$nameEsc' AND id != $id LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        return ['success' => false, 'message' => 'Tên danh mục đã bị trùng'];
    }

    $sql = "UPDATE categories SET name = '$nameEsc', icon_class = '$icon_class', description = '$description', sort_order = $sort_order WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Cập nhật danh mục thành công'];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_deleteCategory($id)
{
    global $conn;
    $id = intval($id);

    // Gỡ type_id khỏi sản phẩm trước khi xóa các loại con để tránh tag treo.
    mysqli_query($conn, "UPDATE products SET type_id = NULL WHERE type_id IN (SELECT id FROM types WHERE category_id = $id)");
    mysqli_query($conn, "DELETE FROM types WHERE category_id = $id");

    $sql = "DELETE FROM categories WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => mysqli_affected_rows($conn) > 0, 'message' => 'Xóa danh mục và các loại con thành công'];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_updateCategoryOrders($orders)
{
    global $conn;
    $updated = 0;
    foreach ($orders as $id => $order) {
        $id = intval($id);
        $order = intval($order);
        if ($id > 0) {
            mysqli_query($conn, "UPDATE categories SET sort_order = $order WHERE id = $id");
            $updated++;
        }
    }
    return ['success' => true, 'message' => "Đã cập nhật $updated danh mục"];
}

function admin_handleCategoryRequest()
{
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = admin_createCategory($_POST);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_updateCategory($id, $_POST) : ['success' => false, 'message' => 'ID không hợp lệ'];
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_deleteCategory($id) : ['success' => false, 'message' => 'ID không hợp lệ'];
            break;
        case 'reorder':
            $orders = $_POST['orders'] ?? [];
            $result = admin_updateCategoryOrders($orders);
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $cat = admin_getCategoryById($id);
            $result = ['success' => $cat !== null, 'data' => $cat];
            break;
        case 'list':
            $cats = admin_getCategories();
            $result = ['success' => true, 'data' => $cats];
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
        admin_handleCategoryRequest();
    }
}

if (!function_exists('categories_columns')) {
function categories_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `categories`");
    if ($result) while ($row = mysqli_fetch_assoc($result)) $cols[$row['Field']] = $row;
    return $cols;
}
}
if (!function_exists('categories_primary_key')) {
function categories_primary_key() {
    foreach (categories_columns() as $name => $meta) if (($meta['Key'] ?? '') === 'PRI') return $name;
    return array_key_exists('id', categories_columns()) ? 'id' : null;
}
}
if (!function_exists('categories_getAll')) {
function categories_getAll() {
    global $conn;
    $pk = categories_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `categories`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}
}
if (!function_exists('categories_add')) {
function categories_add($data) {
    global $conn;
    $cols = categories_columns(); $fields=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if (($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) { $fields[]=$name; $values[]=$data[$name]; }
    }
    if (!$fields) return ['success'=>false,'message'=>'Khong co du lieu them moi'];
    $fieldSql='`'.implode('`,`',$fields).'`'; $ph=implode(',',array_fill(0,count($fields),'?'));
    $stmt=mysqli_prepare($conn,"INSERT INTO `categories` ($fieldSql) VALUES ($ph)");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Them moi thanh cong','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('categories_update')) {
function categories_update($id,$data) {
    global $conn;
    $pk=categories_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Khong tim thay khoa chinh'];
    $cols=categories_columns(); $sets=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if ($name===$pk || ($meta['Extra'] ?? '')==='auto_increment') continue;
        if (array_key_exists($name,$data)) { $sets[]="`$name` = ?"; $values[]=$data[$name]; }
    }
    if (!$sets) return ['success'=>false,'message'=>'Khong co du lieu cap nhat'];
    $values[]=$id; $stmt=mysqli_prepare($conn,"UPDATE `categories` SET ".implode(',',$sets)." WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cap nhat thanh cong'] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('categories_delete')) {
function categories_delete($id) {
    global $conn;
    $pk=categories_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Khong tim thay khoa chinh'];
    $stmt=mysqli_prepare($conn,"DELETE FROM `categories` WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt,'s',$id); $ok=mysqli_stmt_execute($stmt); $affected=mysqli_stmt_affected_rows($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return ($ok && $affected>0) ? ['success'=>true,'message'=>'Xoa thanh cong'] : ['success'=>false,'message'=>$err ?: 'Khong tim thay du lieu'];
}
}
