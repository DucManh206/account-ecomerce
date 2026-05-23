<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getStockList($opts = []) {
    global $conn;

    $product_id = isset($opts['product_id']) && $opts['product_id'] !== '' ? intval($opts['product_id']) : null;
    $status = trim($opts['status'] ?? '');
    $search = trim($opts['search'] ?? '');
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
    if ($search !== '') {
        $where[] = '(s.account_data LIKE ? OR p.title LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }

    $where_sql = implode(' AND ', $where);

    $count_sql = "SELECT COUNT(*) as total FROM account_stock s LEFT JOIN products p ON s.product_id = p.id WHERE $where_sql";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    if ($params && $types) mysqli_stmt_bind_param($count_stmt, $types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total = intval(mysqli_fetch_assoc($count_result)['total'] ?? 0);
    mysqli_stmt_close($count_stmt);
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
    while ($row = mysqli_fetch_assoc($result)) $items[] = $row;
    mysqli_stmt_close($stmt);

    return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'total_pages' => $total_pages];
}

function admin_getStockById($id) {
    global $conn;
    $id = intval($id);
    $sql = "SELECT s.*, p.title as product_title FROM account_stock s LEFT JOIN products p ON s.product_id = p.id WHERE s.id = $id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
}

function admin_normalizeAccountData($account_data) {
    $account_data = trim((string)$account_data);
    if ($account_data === '') return '';
    $decoded = json_decode($account_data, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return $account_data;
}

function admin_createStock($data) {
    global $conn;
    $product_id = intval($data['product_id'] ?? 0);
    $account_data = admin_normalizeAccountData($data['account_data'] ?? '');
    if ($product_id <= 0) return ['success' => false, 'message' => 'Chọn sản phẩm'];
    if ($account_data === '') return ['success' => false, 'message' => 'Nhập thông tin tài khoản'];

    $stmt = mysqli_prepare($conn, "INSERT INTO account_stock (product_id, account_data, status, created_at) VALUES (?, ?, 'available', NOW())");
    mysqli_stmt_bind_param($stmt, 'is', $product_id, $account_data);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $ok ? ['success' => true, 'message' => 'Thêm tài khoản thành công', 'id' => $id] : ['success' => false, 'message' => 'Lỗi: ' . $err];
}

function admin_createStockBulk($product_id, $count = 1, $bulk_json = '') {
    global $conn;
    $product_id = intval($product_id);
    if ($product_id <= 0) return ['success' => false, 'message' => 'Chọn sản phẩm'];

    $accounts = [];
    $bulk_json = trim((string)$bulk_json);
    if ($bulk_json !== '') {
        $decoded = json_decode($bulk_json, true);
        if (!is_array($decoded)) return ['success' => false, 'message' => 'Dữ liệu bulk_json không hợp lệ'];
        foreach ($decoded as $row) {
            if (is_array($row)) $accounts[] = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } else {
        $count = max(1, min(intval($count), 100));
        for ($i = 0; $i < $count; $i++) {
            $accounts[] = json_encode([
                'email' => 'acc_' . time() . '_' . rand(1000, 9999) . '@gmail.com',
                'password' => 'Pass' . rand(100000, 999999) . '!'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    if (empty($accounts)) return ['success' => false, 'message' => 'Không có tài khoản hợp lệ để nhập'];
    if (count($accounts) > 500) return ['success' => false, 'message' => 'Tối đa 500 tài khoản mỗi lần nhập'];

    mysqli_begin_transaction($conn);
    $added = 0;
    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO account_stock (product_id, account_data, status, created_at) VALUES (?, ?, 'available', NOW())");
        foreach ($accounts as $adata) {
            mysqli_stmt_bind_param($stmt, 'is', $product_id, $adata);
            if (mysqli_stmt_execute($stmt)) $added++;
        }
        mysqli_stmt_close($stmt);
        mysqli_commit($conn);
        return ['success' => true, 'message' => "Đã thêm $added tài khoản", 'count' => $added];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => 'Lỗi nhập hàng loạt: ' . $e->getMessage()];
    }
}

function admin_updateStock($id, $data) {
    global $conn;
    $id = intval($id);
    $account_data = admin_normalizeAccountData($data['account_data'] ?? '');
    $status = $data['status'] ?? '';
    if (!in_array($status, ['available', 'sold', 'reserved'], true)) $status = 'available';
    if ($id <= 0) return ['success' => false, 'message' => 'ID không hợp lệ'];
    if ($account_data === '') return ['success' => false, 'message' => 'Nhập thông tin tài khoản'];

    $stmt = mysqli_prepare($conn, "UPDATE account_stock SET account_data = ?, status = ? WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ssi', $account_data, $status, $id);
    $ok = mysqli_stmt_execute($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return $ok ? ['success' => true, 'message' => 'Cập nhật thành công'] : ['success' => false, 'message' => 'Lỗi: ' . $err];
}

function admin_deleteStock($id) {
    global $conn;
    $id = intval($id);
    $stmt = mysqli_prepare($conn, "DELETE FROM account_stock WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return ($ok && $affected > 0) ? ['success' => true, 'message' => 'Xóa thành công'] : ['success' => false, 'message' => $err ?: 'Không tìm thấy tài khoản'];
}

function admin_parseIds($ids) {
    if (is_string($ids)) {
        $decoded = json_decode($ids, true);
        $ids = is_array($decoded) ? $decoded : explode(',', $ids);
    }
    $ids = is_array($ids) ? $ids : [];
    return array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
}

function admin_deleteStockBulk($ids) {
    global $conn;
    $ids = admin_parseIds($ids);
    if (empty($ids)) return ['success' => false, 'message' => 'Không có ID'];
    $in = implode(',', $ids);
    $sql = "DELETE FROM account_stock WHERE id IN ($in)";
    if (mysqli_query($conn, $sql)) {
        $count = mysqli_affected_rows($conn);
        return ['success' => true, 'message' => "Đã xóa $count tài khoản", 'count' => $count];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_updateStockStatusBulk($ids, $status) {
    global $conn;
    $ids = admin_parseIds($ids);
    if (empty($ids)) return ['success' => false, 'message' => 'Không có ID'];
    if (!in_array($status, ['available', 'sold', 'reserved'], true)) return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
    $in = implode(',', $ids);
    $statusEsc = mysqli_real_escape_string($conn, $status);
    $sql = "UPDATE account_stock SET status = '$statusEsc' WHERE id IN ($in)";
    if (mysqli_query($conn, $sql)) {
        $count = mysqli_affected_rows($conn);
        return ['success' => true, 'message' => "Đã cập nhật $count tài khoản", 'count' => $count];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_getStockStats() {
    global $conn;
    $stats = ['total' => 0, 'available' => 0, 'sold' => 0, 'reserved' => 0];
    $r = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM account_stock GROUP BY status");
    $total = 0;
    while ($r && ($row = mysqli_fetch_assoc($r))) {
        $status = $row['status'] ?: 'available';
        $count = intval($row['c']);
        $total += $count;
        if (isset($stats[$status])) $stats[$status] = $count;
    }
    $stats['total'] = $total;
    return $stats;
}

function admin_handleStockRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = admin_createStock($_POST);
            break;
        case 'create_bulk':
            $result = admin_createStockBulk(intval($_POST['product_id'] ?? 0), intval($_POST['count'] ?? 1), $_POST['bulk_json'] ?? '');
            break;
        case 'update':
            $result = admin_updateStock(intval($_POST['id'] ?? 0), $_POST);
            break;
        case 'delete':
            $result = admin_deleteStock(intval($_POST['id'] ?? 0));
            break;
        case 'delete_bulk':
        case 'bulk_delete':
            $result = admin_deleteStockBulk($_POST['ids'] ?? []);
            break;
        case 'bulk_status':
            $result = admin_updateStockStatusBulk($_POST['ids'] ?? [], $_POST['status'] ?? 'available');
            break;
        case 'paginated':
            $opts = [
                'product_id' => $_POST['product_id'] ?? $_GET['product_id'] ?? null,
                'status' => $_POST['status'] ?? $_GET['status'] ?? '',
                'search' => $_POST['search'] ?? $_GET['search'] ?? '',
                'page' => intval($_POST['page'] ?? $_GET['page'] ?? 1),
                'per_page' => intval($_POST['per_page'] ?? $_GET['per_page'] ?? 50),
            ];
            $result = admin_getStockList($opts);
            $result['success'] = true;
            break;
        default:
            $result = ['success' => false, 'message' => 'Hành động không hợp lệ'];
    }
    return $result;
}

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(admin_handleStockRequest());
        exit;
    }
}


/* Assignment-required generic CRUD wrappers for table: account_stock */
if (!function_exists('account_stock_columns')) {
function account_stock_columns() {
    global $conn;
    $cols = [];
    $result = mysqli_query($conn, "DESCRIBE `account_stock`");
    if ($result) while ($row = mysqli_fetch_assoc($result)) $cols[$row['Field']] = $row;
    return $cols;
}
}
if (!function_exists('account_stock_primary_key')) {
function account_stock_primary_key() {
    foreach (account_stock_columns() as $name => $meta) if (($meta['Key'] ?? '') === 'PRI') return $name;
    return array_key_exists('id', account_stock_columns()) ? 'id' : null;
}
}
if (!function_exists('account_stock_getAll')) {
function account_stock_getAll() {
    global $conn;
    $pk = account_stock_primary_key();
    $order = $pk ? " ORDER BY `$pk` DESC" : '';
    $result = mysqli_query($conn, "SELECT * FROM `account_stock`" . $order);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}
}
if (!function_exists('account_stock_add')) {
function account_stock_add($data) {
    global $conn;
    $cols = account_stock_columns(); $fields=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if (($meta['Extra'] ?? '') === 'auto_increment') continue;
        if (array_key_exists($name, $data)) { $fields[]=$name; $values[]=$data[$name]; }
    }
    if (!$fields) return ['success'=>false,'message'=>'Không có dữ liệu thêm mới'];
    $fieldSql='`'.implode('`,`',$fields).'`'; $ph=implode(',',array_fill(0,count($fields),'?'));
    $stmt=mysqli_prepare($conn,"INSERT INTO `account_stock` ($fieldSql) VALUES ($ph)");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Thêm mới thành công','id'=>mysqli_insert_id($conn)] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('account_stock_update')) {
function account_stock_update($id,$data) {
    global $conn;
    $pk=account_stock_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $cols=account_stock_columns(); $sets=[]; $values=[];
    foreach ($cols as $name=>$meta) {
        if ($name===$pk || ($meta['Extra'] ?? '')==='auto_increment') continue;
        if (array_key_exists($name,$data)) { $sets[]="`$name` = ?"; $values[]=$data[$name]; }
    }
    if (!$sets) return ['success'=>false,'message'=>'Không có dữ liệu cập nhật'];
    $values[]=$id; $stmt=mysqli_prepare($conn,"UPDATE `account_stock` SET ".implode(',',$sets)." WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    $types=str_repeat('s',count($values)); mysqli_stmt_bind_param($stmt,$types,...$values);
    $ok=mysqli_stmt_execute($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return $ok ? ['success'=>true,'message'=>'Cập nhật thành công'] : ['success'=>false,'message'=>$err];
}
}
if (!function_exists('account_stock_delete')) {
function account_stock_delete($id) {
    global $conn;
    $pk=account_stock_primary_key(); if (!$pk) return ['success'=>false,'message'=>'Không tìm thấy khóa chính'];
    $stmt=mysqli_prepare($conn,"DELETE FROM `account_stock` WHERE `$pk` = ? LIMIT 1");
    if (!$stmt) return ['success'=>false,'message'=>mysqli_error($conn)];
    mysqli_stmt_bind_param($stmt,'s',$id); $ok=mysqli_stmt_execute($stmt); $affected=mysqli_stmt_affected_rows($stmt); $err=mysqli_stmt_error($stmt); mysqli_stmt_close($stmt);
    return ($ok && $affected>0) ? ['success'=>true,'message'=>'Xóa thành công'] : ['success'=>false,'message'=>$err ?: 'Không tìm thấy dữ liệu'];
}
}
