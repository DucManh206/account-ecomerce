<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getStockList($opts = []) {
    global $conn;

    $product_id = isset($opts['product_id']) && $opts['product_id'] !== '' ? intval($opts['product_id']) : null;
    $status = $opts['status'] ?? '';
    $page = max(1, intval($opts['page'] ?? 1));
    $per_page = max(1, min(100, intval($opts['per_page'] ?? 50)));
    $offset = ($page - 1) * $per_page;

    $where = ['1=1'];
    $params = [];
    $types = '';

    if ($product_id !== null && $product_id > 0) {
        $where[] = 's.product_id = ?';
        $params[] = $product_id;
        $types .= 'i';
    }
    if ($status !== '') {
        $where[] = 's.status = ?';
        $params[] = $status;
        $types .= 's';
    }

    $where_sql = implode(' AND ', $where);

    $count_sql = "SELECT COUNT(*) as total FROM account_stock s WHERE $where_sql";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    if ($params && $types) mysqli_stmt_bind_param($count_stmt, $types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total = intval(mysqli_fetch_assoc($count_result)['total'] ?? 0);
    $total_pages = $total > 0 ? ceil($total / $per_page) : 1;

    $sql = "SELECT s.*, p.title as product_title, p.category as product_category, p.image_url as product_image
    FROM account_stock s
    LEFT JOIN products p ON s.product_id = p.id
    WHERE $where_sql
    ORDER BY s.id DESC
    LIMIT $per_page OFFSET $offset";

    $stmt = mysqli_prepare($conn, $sql);
    if ($params && $types) mysqli_stmt_bind_param($stmt, $types, ...$params);
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

function admin_getStockById($id) {
    global $conn;
    $id = intval($id);
    $sql = "SELECT s.*, p.title as product_title
    FROM account_stock s
    LEFT JOIN products p ON s.product_id = p.id
    WHERE s.id = $id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_createStock($data) {
    global $conn;

    $product_id = intval($data['product_id'] ?? 0);
    $account_data = trim($data['account_data'] ?? '');

    if ($product_id <= 0) return ['success' => false, 'message' => 'Chọn sản phẩm'];
    if (empty($account_data)) return ['success' => false, 'message' => 'Nhập thông tin tài khoản'];

    $account_data_escaped = mysqli_real_escape_string($conn, $account_data);
    $product_id_escaped = intval($product_id);

    $sql = "INSERT INTO account_stock (product_id, account_data, status) VALUES ($product_id_escaped, '$account_data_escaped', 'available')";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Thêm tài khoản thành công', 'id' => mysqli_insert_id($conn)];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_createStockBulk($product_id, $count) {
    global $conn;
    $product_id = intval($product_id);
    $count = min(intval($count), 100);

    $added = 0;
    for ($i = 0; $i < $count; $i++) {
        $email = "acc_" . time() . "_" . rand(1000, 9999) . "@gmail.com";
        $pass = "Pass" . rand(100000, 999999) . "!";
        $adata = json_encode(["email" => $email, "password" => $pass]);
        $adata_escaped = mysqli_real_escape_string($conn, $adata);
        $sql = "INSERT INTO account_stock (product_id, account_data, status) VALUES ($product_id, '$adata_escaped', 'available')";
        if (mysqli_query($conn, $sql)) $added++;
    }
    return ['success' => true, 'message' => "Đã thêm $added tài khoản", 'count' => $added];
}

function admin_updateStock($id, $data) {
    global $conn;
    $id = intval($id);

    $account_data = trim($data['account_data'] ?? '');
    $status = $data['status'] ?? '';

    $fields = [];
    if ($account_data !== '') {
        $fields[] = "account_data = '" . mysqli_real_escape_string($conn, $account_data) . "'";
    }
    if (in_array($status, ['available', 'sold', 'reserved'])) {
        $fields[] = "status = '" . mysqli_real_escape_string($conn, $status) . "'";
    }

    if (empty($fields)) return ['success' => false, 'message' => 'Không có trường nào được cập nhật'];

    $sql = "UPDATE account_stock SET " . implode(', ', $fields) . " WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Cập nhật thành công'];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_deleteStock($id) {
    global $conn;
    $id = intval($id);
    $sql = "DELETE FROM account_stock WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => mysqli_affected_rows($conn) > 0, 'message' => 'Xóa thành công'];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_deleteStockBulk($ids) {
    global $conn;
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, fn($i) => $i > 0);
    if (empty($ids)) return ['success' => false, 'message' => 'Không có ID'];

    $in = implode(',', $ids);
    $sql = "DELETE FROM account_stock WHERE id IN ($in)";
    if (mysqli_query($conn, $sql)) {
        $count = mysqli_affected_rows($conn);
        return ['success' => true, 'message' => "Đã xóa $count tài khoản", 'count' => $count];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_getStockStats() {
    global $conn;
    $stats = ['total' => 0, 'available' => 0, 'sold' => 0, 'reserved' => 0];

    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM account_stock");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['total'] = intval($row['c']);

    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM account_stock WHERE status = 'available'");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['available'] = intval($row['c']);

    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM account_stock WHERE status = 'sold'");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['sold'] = intval($row['c']);

    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM account_stock WHERE status = 'reserved'");
    if ($r && $row = mysqli_fetch_assoc($r)) $stats['reserved'] = intval($row['c']);

    return $stats;
}

function admin_handleStockRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = admin_createStock($_POST);
            break;
        case 'create_bulk':
            $product_id = intval($_POST['product_id'] ?? 0);
            $count = intval($_POST['count'] ?? 1);
            $result = ($product_id > 0) ? admin_createStockBulk($product_id, $count) : ['success' => false, 'message' => 'Chọn sản phẩm'];
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_updateStock($id, $_POST) : ['success' => false, 'message' => 'ID không hợp lệ'];
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_deleteStock($id) : ['success' => false, 'message' => 'ID không hợp lệ'];
            break;
        case 'bulk_delete':
            $ids = $_POST['ids'] ?? [];
            $result = admin_deleteStockBulk($ids);
            break;
        case 'paginated':
            $opts = [
                'product_id' => $_POST['product_id'] ?? $_GET['product_id'] ?? null,
                'status' => $_POST['status'] ?? $_GET['status'] ?? '',
                'page' => intval($_POST['page'] ?? $_GET['page'] ?? 1),
                'per_page' => intval($_POST['per_page'] ?? $_GET['per_page'] ?? 50),
            ];
            $result = admin_getStockList($opts);
            $result['success'] = true;
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
        admin_handleStockRequest();
    }
}
?>
