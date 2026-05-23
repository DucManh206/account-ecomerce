<?php
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . '/../../crud/settings/settings_modules.php';

// Đếm số tài khoản có sẵn của một sản phẩm
function getAvailableStock($productId) {
    global $conn;
    
    if (!$conn) return 0;
    
    $productId = intval($productId);
    $sql = "SELECT COUNT(*) as cnt FROM account_stock WHERE product_id = ? AND status = 'available'";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) return 0;
    
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return intval($row['cnt']);
    }
    
    return 0;
}

function getProducts($includeOutOfStock = true) {
    global $conn;
    $products = [];

    if (!$conn) {
        return $products;
    }

    $sql = "SELECT p.*, COALESCE(t.name, p.category) as type_name, COALESCE(t.icon_class, p.icon_class) as type_icon
            FROM products p
            LEFT JOIN types t ON p.type_id = t.id
            ORDER BY p.id DESC";
    $result = mysqli_query($conn, $sql);

    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            // Đếm số tài khoản có sẵn
            $stockQty = getAvailableStock($row['id']);
            $row['stock'] = $stockQty;
            $row['in_stock'] = ($stockQty > 0);
            
            // Auto-hide: nếu setting bật và hết hàng thì bỏ qua
            $autoHide = getAutoHideOutOfStock();
            if ($autoHide && $stockQty <= 0) {
                continue;
            }
            
            // Nếu không includeOutOfStock và hết hàng thì bỏ qua
            if (!$includeOutOfStock && $stockQty <= 0) {
                continue;
            }
            
            $products[] = $row;
        }
    }

    return $products;
}

function getCategories() {
    global $conn;
    $categories = [];

    if (!$conn) {
        return $categories;
    }

    $sql = "SELECT * FROM categories ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);

    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }

    return $categories;
}

function product_getAll($includeOutOfStock = true) {
    return getProducts($includeOutOfStock);
}

function product_getById($id) {
    global $conn;
    $id = intval($id);
    if (!$conn || $id <= 0) return null;

    $sql = "SELECT p.*, COALESCE(t.name, p.category) as type_name, COALESCE(t.icon_class, p.icon_class) as type_icon
            FROM products p
            LEFT JOIN types t ON p.type_id = t.id
            WHERE p.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    if ($row) {
        $row['stock'] = getAvailableStock($row['id']);
        $row['in_stock'] = $row['stock'] > 0;
    }
    return $row ?: null;
}

function product_add($data) {
    global $conn;
    if (!$conn) return ['success' => false, 'message' => 'Không có kết nối database'];

    $title = trim($data['title'] ?? '');
    $category = trim($data['category'] ?? '');
    $game_type = trim($data['game_type'] ?? '');
    $type_id = (isset($data['type_id']) && intval($data['type_id']) > 0) ? intval($data['type_id']) : null;
    $image_url = trim($data['image_url'] ?? '');
    $price = intval($data['price'] ?? 0);
    $old_price = intval($data['old_price'] ?? 0);
    $badge = trim($data['badge'] ?? '');
    $details = trim($data['details'] ?? '');
    $description = trim($data['description'] ?? '');
    $color_class = trim($data['color_class'] ?? 'bg-secondary');
    $icon_class = trim($data['icon_class'] ?? 'fa-box');
    $gallery = trim($data['gallery'] ?? '');

    if ($title === '' || $category === '' || $image_url === '') {
        return ['success' => false, 'message' => 'Thiếu title/category/image_url'];
    }
    if ($details === '') $details = '{}';

    $sql = "INSERT INTO products (title, category, game_type, type_id, image_url, price, old_price, badge, details, description, color_class, icon_class, gallery)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return ['success' => false, 'message' => mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt, 'sssisiissssss', $title, $category, $game_type, $type_id, $image_url, $price, $old_price, $badge, $details, $description, $color_class, $icon_class, $gallery);
    $ok = mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($conn);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? ['success' => true, 'message' => 'Thêm sản phẩm thành công', 'id' => $id] : ['success' => false, 'message' => $err];
}

function product_update($id, $data) {
    global $conn;
    $id = intval($id);
    if (!$conn || $id <= 0) return ['success' => false, 'message' => 'ID không hợp lệ'];

    $allowed = [
        'title' => 's', 'category' => 's', 'game_type' => 's', 'type_id' => 'i', 'image_url' => 's',
        'price' => 'i', 'old_price' => 'i', 'badge' => 's', 'details' => 's', 'description' => 's',
        'color_class' => 's', 'icon_class' => 's', 'gallery' => 's'
    ];
    $sets = [];
    $values = [];
    $types = '';
    foreach ($allowed as $field => $type) {
        if (!array_key_exists($field, $data)) continue;
        $value = $data[$field];
        if ($field === 'details' && trim((string)$value) === '') $value = '{}';
        if ($field === 'type_id') $value = intval($value) > 0 ? intval($value) : null;
        if ($type === 'i' && $field !== 'type_id') $value = intval($value);
        $sets[] = "$field = ?";
        $values[] = $value;
        $types .= $type;
    }
    if (!$sets) return ['success' => false, 'message' => 'Không có dữ liệu cập nhật'];

    $values[] = $id;
    $types .= 'i';
    $sql = "UPDATE products SET " . implode(', ', $sets) . " WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return ['success' => false, 'message' => mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? ['success' => true, 'message' => 'Cập nhật sản phẩm thành công'] : ['success' => false, 'message' => $err];
}

function product_delete($id) {
    global $conn;
    $id = intval($id);
    if (!$conn || $id <= 0) return ['success' => false, 'message' => 'ID không hợp lệ'];

    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ? LIMIT 1");
    if (!$stmt) return ['success' => false, 'message' => mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return ($ok && $affected > 0) ? ['success' => true, 'message' => 'Xóa sản phẩm thành công'] : ['success' => false, 'message' => $err ?: 'Không tìm thấy sản phẩm'];
}

function product_redirect_after_action($result, $fallback = 'list.php') {
    if (php_sapi_name() === 'cli') return;
    $status = !empty($result['success']) ? 'success' : 'error';
    $message = urlencode($result['message'] ?? 'Done');
    header("Location: {$fallback}?{$status}={$message}");
    exit;
}
