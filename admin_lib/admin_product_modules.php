<?php
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/admin_verifier_modules.php';

function admin_getProductsPaginated($opts = [])
{
  global $conn;

  $search = trim($opts['search'] ?? '');
  $category = trim($opts['category'] ?? '');
  $type_id = isset($opts['type_id']) && $opts['type_id'] !== '' ? intval($opts['type_id']) : null;
  $page = max(1, intval($opts['page'] ?? 1));
  $per_page = max(1, min(100, intval($opts['per_page'] ?? 20)));
  $offset = ($page - 1) * $per_page;

  $where = ['1=1'];
  $params = [];
  $types = '';

  if ($search !== '') {
    $where[] = "(p.title LIKE ? OR p.category LIKE ? OR t.name LIKE ?)";
    $s = "%{$search}%";
    $params[] = $s;
    $params[] = $s;
    $params[] = $s;
    $types .= 'sss';
  }
  if ($category !== '') {
    $categoryIdFilter = intval($category);
    if ($categoryIdFilter > 0) {
      $where[] = "t.category_id = ?";
      $params[] = $categoryIdFilter;
      $types .= 'i';
    }
  }
  if ($type_id !== null && $type_id > 0) {
    $where[] = "p.type_id = ?";
    $params[] = $type_id;
    $types .= 'i';
  }

  $where_sql = implode(' AND ', $where);

  $count_sql = "SELECT COUNT(*) as total FROM products p LEFT JOIN types t ON p.type_id = t.id WHERE {$where_sql}";
  $count_stmt = mysqli_prepare($conn, $count_sql);
  if ($params) mysqli_stmt_bind_param($count_stmt, $types, ...$params);
  mysqli_stmt_execute($count_stmt);
  $count_result = mysqli_stmt_get_result($count_stmt);
  $total = intval(mysqli_fetch_assoc($count_result)['total'] ?? 0);
  $total_pages = $total > 0 ? ceil($total / $per_page) : 1;

  $sql = "SELECT p.*, COALESCE(t.name, p.category) as type_name, COALESCE(t.icon_class, p.icon_class) as type_icon, COALESCE(t.category, p.category) as type_category
  FROM products p
  LEFT JOIN types t ON p.type_id = t.id
  WHERE {$where_sql}
  ORDER BY p.id DESC
  LIMIT {$per_page} OFFSET {$offset}";

  $stmt = mysqli_prepare($conn, $sql);
  if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  $items = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
  }

  return [
    'items' => $items,
    'total' => $total,
    'page' => $page,
    'per_page' => $per_page,
    'total_pages' => $total_pages,
  ];
}

function admin_getProducts()
{
  global $conn;
  $sql = "SELECT p.*, COALESCE(t.name, p.category) as type_name, COALESCE(t.icon_class, p.icon_class) as type_icon
  FROM products p
  LEFT JOIN types t ON p.type_id = t.id
  ORDER BY p.id DESC";
  $result = mysqli_query($conn, $sql);
  $products = [];
  if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
      $products[] = $row;
    }
  }
  return $products;
}

function admin_getProductById($id)
{
  global $conn;
  $id = intval($id);
  $sql = "SELECT p.*, COALESCE(t.name, p.category) as type_name, COALESCE(t.icon_class, p.icon_class) as type_icon
  FROM products p
  LEFT JOIN types t ON p.type_id = t.id
  WHERE p.id = $id LIMIT 1";
  $result = mysqli_query($conn, $sql);
  if ($result && mysqli_num_rows($result) > 0) {
    return mysqli_fetch_assoc($result);
  }
  return null;
}

function admin_createProduct($data)
{
  global $conn;

  $required = ['title', 'price', 'category', 'image_url'];
  foreach ($required as $field) {
    if (!isset($data[$field]) || trim($data[$field]) === '') {
      return ['success' => false, 'message' => "Thiếu trường bắt buộc: $field"];
    }
  }

  $title = mysqli_real_escape_string($conn, $data['title']);
  $category = mysqli_real_escape_string($conn, $data['category']);
  $game_type  = mysqli_real_escape_string($conn, $data['game_type'] ?? '');
  $image_url = mysqli_real_escape_string($conn, $data['image_url']);
  $price = intval($data['price']);
  $old_price  = intval($data['old_price'] ?? 0);
  $badge = mysqli_real_escape_string($conn, $data['badge'] ?? '');
  $details_raw = trim($data['details'] ?? '');
  $details = $details_raw === '' ? '{}' : mysqli_real_escape_string($conn, $details_raw);
  $description = mysqli_real_escape_string($conn, $data['description'] ?? '');
  $color_class = mysqli_real_escape_string($conn, $data['color_class'] ?? 'bg-secondary');
  $icon_class = mysqli_real_escape_string($conn, $data['icon_class'] ?? 'fa-box');
  $gallery = mysqli_real_escape_string($conn, $data['gallery'] ?? '');
  $type_id = (isset($data['type_id']) && intval($data['type_id']) > 0) ? intval($data['type_id']) : 'NULL';

  $sql = "INSERT INTO products (title, category, game_type, image_url, price, old_price, badge, details, description, color_class, icon_class, gallery";
  $sql .= ($type_id !== 'NULL' ? ", type_id" : "");
  $sql .= ") VALUES ('$title', '$category', '$game_type', '$image_url', $price, $old_price, '$badge', '$details', '$description', '$color_class', '$icon_class', '$gallery'";
  $sql .= ($type_id !== 'NULL' ? ", $type_id" : "");
  $sql .= ")";

  if (mysqli_query($conn, $sql)) {
    return ['success' => true, 'message' => 'Thêm sản phẩm thành công', 'id' => mysqli_insert_id($conn)];
  }
  return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_updateProduct($id, $data)
{
  global $conn;
  $id = intval($id);

  $fields = [];
  $allowed = ['title', 'category', 'game_type', 'image_url', 'badge', 'details', 'description', 'color_class', 'icon_class', 'gallery'];

  foreach ($allowed as $field) {
    if (array_key_exists($field, $data)) {
      $val = trim($data[$field]);
      if ($field === 'details' && $val === '') {
        $val = '{}';
      }
      $val = mysqli_real_escape_string($conn, $val);
      $fields[] = "$field = '$val'";
    }
  }
  if (isset($data['price'])) $fields[] = "price = " . intval($data['price']);
  if (isset($data['old_price']))  $fields[] = "old_price = " . intval($data['old_price']);
  if (array_key_exists('type_id', $data)) {
    $type_id = (isset($data['type_id']) && intval($data['type_id']) > 0) ? intval($data['type_id']) : 'NULL';
    $fields[] = "type_id = $type_id";
  }

  if (empty($fields)) {
    return ['success' => false, 'message' => 'Không có trường nào được cập nhật'];
  }

  $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = $id LIMIT 1";
  if (mysqli_query($conn, $sql)) {
    return ['success' => true, 'message' => 'Cập nhật sản phẩm thành công'];
  }
  return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_deleteProduct($id)
{
  global $conn;
  $id = intval($id);
  $sql = "DELETE FROM products WHERE id = $id LIMIT 1";
  if (mysqli_query($conn, $sql)) {
    return ['success' => mysqli_affected_rows($conn) > 0, 'message' => 'Xóa sản phẩm thành công'];
  }
  return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_deleteProductsBulk($ids)
{
  global $conn;
  $ids = array_map('intval', $ids);
  $ids = array_filter($ids, fn($i) => $i > 0);
  if (empty($ids)) return ['success' => false, 'message' => 'Không có ID nào'];

  $in = implode(',', $ids);
  $sql = "DELETE FROM products WHERE id IN ($in)";
  if (mysqli_query($conn, $sql)) {
    $count = mysqli_affected_rows($conn);
    return ['success' => true, 'message' => "Đã xóa $count sản phẩm", 'count' => $count];
  }
  return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_countProducts()
{
  global $conn;
  $sql = "SELECT COUNT(*) as total FROM products";
  $result = mysqli_query($conn, $sql);
  $row = mysqli_fetch_assoc($result);
  return intval($row['total'] ?? 0);
}

function admin_getCategories()
{
  global $conn;
  $sql = "SELECT DISTINCT category FROM products ORDER BY category ASC";
  $result = mysqli_query($conn, $sql);
  $cats = [];
  if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
      $cats[] = $row['category'];
    }
  }
  return $cats;
}

function admin_handleProductRequest()
{
  $action = $_POST['action'] ?? $_GET['action'] ?? '';

  switch ($action) {
    case 'create':
      $result = admin_createProduct($_POST);
      break;
    case 'update':
      $id = intval($_POST['id'] ?? 0);
      $result = ($id > 0) ? admin_updateProduct($id, $_POST) : ['success' => false, 'message' => 'ID không hợp lệ'];
      break;
    case 'delete':
      $id = intval($_POST['id'] ?? 0);
      $result = ($id > 0) ? admin_deleteProduct($id) : ['success' => false, 'message' => 'ID không hợp lệ'];
      break;
    case 'bulk_delete':
      $ids = $_POST['ids'] ?? [];
      $result = admin_deleteProductsBulk($ids);
      break;
    case 'get':
      $id = intval($_GET['id'] ?? 0);
      $product = admin_getProductById($id);
      $result = ['success' => $product !== null, 'data' => $product];
      break;
    case 'list':
      $products = admin_getProducts();
      $result = ['success' => true, 'data' => $products];
      break;
    case 'paginated':
      $opts = [
        'search' => $_POST['search'] ?? $_GET['search'] ?? '',
        'category' => $_POST['category'] ?? $_GET['category'] ?? '',
        'type_id' => $_POST['type_id'] ?? $_GET['type_id'] ?? null,
        'page' => intval($_POST['page'] ?? $_GET['page'] ?? 1),
        'per_page' => intval($_POST['per_page'] ?? $_GET['per_page'] ?? 20),
      ];
      $result = admin_getProductsPaginated($opts);
      $result['success'] = true;
      break;
    default:
      $result = ['success' => false, 'message' => 'Hành động không hợp lệ'];
  }

  admin_respondJson(true, 'Thành công', $result);
}

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    admin_handleProductRequest();
  }
  exit;
}
