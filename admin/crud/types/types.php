<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getTypes()
{
    global $conn;
    $sql = "SELECT t.*, c.name as category_name, c.icon_class as category_icon
 FROM types t
 LEFT JOIN categories c ON t.category_id = c.id
 ORDER BY c.sort_order ASC, c.name ASC, t.sort_order ASC, t.name ASC";
    $result = mysqli_query($conn, $sql);
    $types = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $types[] = $row;
        }
    }
    return $types;
}

function admin_getTypesByCategory($categoryId)
{
    global $conn;
    $categoryId = intval($categoryId);
    $sql = "SELECT t.*, c.name as category_name
 FROM types t
 LEFT JOIN categories c ON t.category_id = c.id
 WHERE t.category_id = $categoryId
 ORDER BY t.sort_order ASC, t.name ASC";
    $result = mysqli_query($conn, $sql);
    $types = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $types[] = $row;
        }
    }
    return $types;
}

function admin_getTypeById($id)
{
    global $conn;
    $id = intval($id);
    $sql = "SELECT t.*, c.name as category_name
 FROM types t
 LEFT JOIN categories c ON t.category_id = c.id
 WHERE t.id = $id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_createType($data)
{
    global $conn;

    $name = trim($data['name'] ?? '');
    $category_id = isset($data['category_id']) && intval($data['category_id']) > 0 ? intval($data['category_id']) : null;
    $icon_class = mysqli_real_escape_string($conn, $data['icon_class'] ?? 'fa-tag');
    $sort_order = intval($data['sort_order'] ?? 0);

    if (empty($name)) {
        return ['success' => false, 'message' => 'Tên loại không được để trống'];
    }
    if (!$category_id) {
        return ['success' => false, 'message' => 'Vui lòng chọn danh mục'];
    }

    $nameEsc = mysqli_real_escape_string($conn, $name);

    $check = mysqli_query($conn, "SELECT id FROM types WHERE name = '$nameEsc' AND category_id = $category_id LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        return ['success' => false, 'message' => 'Loại đã tồn tại trong danh mục này'];
    }

    $sql = "INSERT INTO types (name, category, category_id, icon_class, sort_order) VALUES ('$nameEsc', '', $category_id, '$icon_class', $sort_order)";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Thêm loại thành công', 'id' => mysqli_insert_id($conn)];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_updateType($id, $data)
{
    global $conn;
    $id = intval($id);

    $name = trim($data['name'] ?? '');
    $category_id = isset($data['category_id']) && intval($data['category_id']) > 0 ? intval($data['category_id']) : null;
    $icon_class = mysqli_real_escape_string($conn, $data['icon_class'] ?? 'fa-tag');
    $sort_order = intval($data['sort_order'] ?? 0);

    if (empty($name)) {
        return ['success' => false, 'message' => 'Tên loại không được để trống'];
    }
    if (!$category_id) {
        return ['success' => false, 'message' => 'Vui lòng chọn danh mục'];
    }

    $nameEsc = mysqli_real_escape_string($conn, $name);

    $check = mysqli_query($conn, "SELECT id FROM types WHERE name = '$nameEsc' AND category_id = $category_id AND id != $id LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        return ['success' => false, 'message' => 'Loại đã tồn tại trong danh mục này'];
    }

    $sql = "UPDATE types SET name = '$nameEsc', category_id = $category_id, icon_class = '$icon_class', sort_order = $sort_order WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Cập nhật loại thành công'];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_getTypeProductCount($typeId)
{
    global $conn;
    $id = intval($typeId);
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE type_id = $id");
    return intval(mysqli_fetch_assoc($r)['c'] ?? 0);
}

function admin_getTypeIconChoices()
{
    global $conn;
    $sql = "SELECT MIN(name) as name, icon_class
 FROM types
 WHERE icon_class IS NOT NULL AND icon_class != ''
 GROUP BY icon_class
 ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    $icons = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $icons[] = [$row['icon_class'], $row['name']];
        }
    }

    if (empty($icons)) {
        return [['fa-tag', 'Mặc định']];
    }
    return $icons;
}

function admin_deleteType($id)
{
    global $conn;
    $id = intval($id);

    mysqli_query($conn, "UPDATE products SET type_id = NULL WHERE type_id = $id");

    $sql = "DELETE FROM types WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => mysqli_affected_rows($conn) > 0, 'message' => 'Xóa loại & gỡ tag khỏi sản phẩm thành công'];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_getCategoriesFromTypes()
{
    global $conn;
    $sql = "SELECT * FROM categories ORDER BY sort_order ASC, name ASC";
    $result = mysqli_query($conn, $sql);
    $cats = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cats[] = $row;
        }
    }
    return $cats;
}

function admin_handleTypeRequest()
{
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = admin_createType($_POST);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_updateType($id, $_POST) : ['success' => false, 'message' => 'ID không hợp lệ'];
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_deleteType($id) : ['success' => false, 'message' => 'ID không hợp lệ'];
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $type = admin_getTypeById($id);
            $result = ['success' => $type !== null, 'data' => $type];
            break;
        case 'list':
            $types = admin_getTypes();
            $result = ['success' => true, 'data' => $types];
            break;
        case 'by_category':
            $catId = intval($_GET['category_id'] ?? 0);
            $types = admin_getTypesByCategory($catId);
            $result = ['success' => true, 'data' => $types];
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
        admin_handleTypeRequest();
    }
}

/* Assignment-required generic CRUD wrappers for table: types */
if (!function_exists('types_columns')) {
function types_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `types`");
    if ($result) while ($row = mysqli_fetch_assoc($result)) $cols[$row['Field']] = $row;
    return $cols;
}
}
if (!function_exists('types_primary_key')) {
function types_primary_key() {
    foreach (types_columns() as $name => $meta) if (($meta['Key'] ?? '') === 'PRI') return $name;
    return array_key_exists('id', types_columns()) ? 'id' : null;
}
}
if (!function_exists('types_getAll')) {
function types_getAll() {
    global $conn;
    $pk = types_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `types`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}
}
if (!function_exists('types_add')) {
function types_add($data) {
    global $conn;
    $cols = types_columns(); $fields=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if (($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) { $fields[]=$name; $values[]=$data[$name]; }
    }
    if (!$fields) return ['success'=>false,'message'=>'Không có dữ liệu thêm mới'];
    $fieldSql='`'.implode('`,`',$fields).'`'; $ph=implode(',',array_fill(0,count($fields),'?'));
    $stmt=mysqli_prepare($conn,"INSERT INTO `types` ($fieldSql) VALUES ($ph)");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Thêm mới thành công','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('types_update')) {
function types_update($id,$data) {
    global $conn;
    $pk=types_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $cols=types_columns(); $sets=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if ($name===$pk || ($meta['Extra'] ?? '')==='auto_increment') continue;
        if (array_key_exists($name,$data)) { $sets[]="`$name` = ?"; $values[]=$data[$name]; }
    }
    if (!$sets) return ['success'=>false,'message'=>'Không có dữ liệu cập nhật'];
    $values[]=$id; $stmt=mysqli_prepare($conn,"UPDATE `types` SET ".implode(',',$sets)." WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cập nhật thành công'] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('types_delete')) {
function types_delete($id) {
    global $conn;
    $pk=types_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $stmt=mysqli_prepare($conn,"DELETE FROM `types` WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt,'s',$id); $ok=mysqli_stmt_execute($stmt); $affected=mysqli_stmt_affected_rows($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return ($ok && $affected>0) ? ['success'=>true,'message'=>'Xóa thành công'] : ['success'=>false,'message'=>$err ?: 'Không tìm thấy dữ liệu'];
}
}
