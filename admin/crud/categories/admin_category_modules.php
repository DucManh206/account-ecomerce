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
