<?php
require_once __DIR__ . '/../../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getUsers() {
    global $conn;
    $sql = "SELECT id, username, role, balance FROM users ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    $users = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { $users[] = $row; }
    }
    return $users;
}

function admin_getUserById($id) {
    global $conn;
    $id = intval($id);
    $result = mysqli_query($conn, "SELECT id, username, role, balance FROM users WHERE id = $id LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_createUser($data) {
    global $conn;
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $role = intval($data['role'] ?? 0);
    $balance = intval($data['balance'] ?? 0);

    if (empty($username)) return ['success' => false, 'message' => 'Tên đăng nhập bắt buộc'];
    if (strlen($username) < 3) return ['success' => false, 'message' => 'Tên đăng nhập phải từ 3 ký tự'];
    if (empty($password)) return ['success' => false, 'message' => 'Mật khẩu bắt buộc'];
    if (strlen($password) < 6) return ['success' => false, 'message' => 'Mật khẩu phải từ 6 ký tự'];

    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];

    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role, balance) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssii", $username, $password, $role, $balance);
    if (mysqli_stmt_execute($stmt)) {
        return ['success' => true, 'message' => 'Thêm người dùng thành công', 'id' => mysqli_insert_id($conn)];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_updateUser($id, $data) {
    global $conn;
    $id = intval($id);
    $fields = [];

    if (isset($data['role'])) $fields[] = "role = " . intval($data['role']);
    if (isset($data['balance'])) $fields[] = "balance = " . intval($data['balance']);
    if (!empty($data['password'])) {
        if (strlen($data['password']) < 6) return ['success' => false, 'message' => 'Mật khẩu phải từ 6 ký tự'];
        $pw = mysqli_real_escape_string($conn, $data['password']);
        $fields[] = "password = '$pw'";
    }

    if (empty($fields)) return ['success' => false, 'message' => 'Không có trường nào được cập nhật'];

    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) return ['success' => true, 'message' => 'Cập nhật thành công'];
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_deleteUser($id) {
    global $conn;
    $id = intval($id);
    $current = admin_getUserById($id);
    if ($current && $current['username'] === ($_SESSION['username'] ?? '')) {
        return ['success' => false, 'message' => 'Không thể xóa tài khoản của chính bạn'];
    }
    $sql = "DELETE FROM users WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => mysqli_affected_rows($conn) > 0, 'message' => 'Xóa người dùng thành công'];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_topUpBalance($id, $amount) {
    global $conn;
    $id = intval($id);
    $amount = intval($amount);
    if ($amount <= 0) return ['success' => false, 'message' => 'Số tiền phải lớn hơn 0'];
    $sql = "UPDATE users SET balance = balance + $amount WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => "Đã nạp " . number_format($amount, 0, ',', '.') . "đ"];
    }
    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_countUsersByRole($role) {
    global $conn;
    $role = intval($role);
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role = $role");
    return intval(mysqli_fetch_assoc($r)['c'] ?? 0);
}

function admin_handleUserRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = admin_createUser($_POST);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_updateUser($id, $_POST) : ['success' => false, 'message' => 'ID khong hop le'];
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_deleteUser($id) : ['success' => false, 'message' => 'ID khong hop le'];
            break;
        case 'topup':
            $id = intval($_POST['id'] ?? 0);
            $amount = intval($_POST['amount'] ?? 0);
            $result = ($id > 0) ? admin_topUpBalance($id, $amount) : ['success' => false, 'message' => 'ID khong hop le'];
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $user = admin_getUserById($id);
            $result = ['success' => $user !== null, 'data' => $user];
            break;
        case 'list':
            $result = ['success' => true, 'data' => admin_getUsers()];
            break;
        default:
            $result = ['success' => false, 'message' => 'Hanh dong khong hop le'];
    }

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
        admin_handleUserRequest();
    }
}
