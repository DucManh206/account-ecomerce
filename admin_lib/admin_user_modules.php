<?php
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/admin_verifier_modules.php';

function admin_getUsers()
{
    global $conn;
    $sql = "SELECT id, username, role, balance FROM users ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    $users = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
    }
    return $users;
}

function admin_getUserById($id)
{
    global $conn;
    $id = intval($id);
    $sql = "SELECT id, username, role, balance FROM users WHERE id = $id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_getUserByUsername($username)
{
    global $conn;
    $username = mysqli_real_escape_string($conn, $username);
    $sql = "SELECT id, username, role, balance FROM users WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_createUser($data)
{
    global $conn;

    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $role = intval($data['role'] ?? 0);
    $balance = intval($data['balance'] ?? 0);

    if (empty($username)) {
        return ['success' => false, 'message' => 'Tên đăng nhập không được để trống'];
    }
    if (strlen($username) < 3) {
        return ['success' => false, 'message' => 'Tên đăng nhập phải có ít nhất 3 ký tự'];
    }
    if (empty($password)) {
        return ['success' => false, 'message' => 'Mật khẩu không được để trống'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'];
    }

    $check = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $check);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
    }

    $sql = "INSERT INTO users (username, password, role, balance) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssii", $username, $password, $role, $balance);

    if (mysqli_stmt_execute($stmt)) {
        return ['success' => true, 'message' => 'Thêm người dùng thành công', 'id' => mysqli_insert_id($conn)];
    }

    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_updateUser($id, $data)
{
    global $conn;
    $id = intval($id);

    $fields = [];

    if (isset($data['role'])) {
        $fields[] = "role = " . intval($data['role']);
    }
    if (isset($data['balance'])) {
        $fields[] = "balance = " . intval($data['balance']);
    }
    if (!empty($data['password'])) {
        $pw = mysqli_real_escape_string($conn, $data['password']);
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'];
        }
        $fields[] = "password = '$pw'";
    }

    if (empty($fields)) {
        return ['success' => false, 'message' => 'Không có trường nào được cập nhật'];
    }

    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = $id LIMIT 1";

    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Cập nhật người dùng thành công'];
    }

    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_deleteUser($id)
{
    global $conn;
    $id = intval($id);

    $currentUser = admin_getUserById($id);
    if ($currentUser && $currentUser['username'] === ($_SESSION['username'] ?? '')) {
        return ['success' => false, 'message' => 'Không thể xóa tài khoản của chính bạn'];
    }

    $sql = "DELETE FROM users WHERE id = $id LIMIT 1";

    if (mysqli_query($conn, $sql)) {
        return ['success' => mysqli_affected_rows($conn) > 0, 'message' => 'Xóa người dùng thành công'];
    }

    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_topUpBalance($id, $amount)
{
    global $conn;
    $id = intval($id);
    $amount = intval($amount);

    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Số tiền phải lớn hơn 0'];
    }

    $sql = "UPDATE users SET balance = balance + $amount WHERE id = $id LIMIT 1";

    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => "Đã nạp " . number_format($amount, 0, ',', '.') . "đ thành công"];
    }

    return ['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)];
}

function admin_countUsers()
{
    global $conn;
    $sql = "SELECT COUNT(*) as total FROM users";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return intval($row['total'] ?? 0);
}

function admin_countUsersByRole($role)
{
    global $conn;
    $role = intval($role);
    $sql = "SELECT COUNT(*) as total FROM users WHERE role = $role";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    return intval($row['total'] ?? 0);
}

function admin_handleUserRequest()
{
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = admin_createUser($_POST);
            admin_respondJson($result);
            break;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                admin_respondJson(['success' => false, 'message' => 'ID không hợp lệ']);
            }
            $result = admin_updateUser($id, $_POST);
            admin_respondJson($result);
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                admin_respondJson(['success' => false, 'message' => 'ID không hợp lệ']);
            }
            $result = admin_deleteUser($id);
            admin_respondJson($result);
            break;

        case 'topup':
            $id = intval($_POST['id'] ?? 0);
            $amount = intval($_POST['amount'] ?? 0);
            if ($id <= 0) {
                admin_respondJson(['success' => false, 'message' => 'ID không hợp lệ']);
            }
            $result = admin_topUpBalance($id, $amount);
            admin_respondJson($result);
            break;

        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $user = admin_getUserById($id);
            admin_respondJson(['success' => $user !== null, 'data' => $user]);
            break;

        case 'list':
            $users = admin_getUsers();
            admin_respondJson(['success' => true, 'data' => $users]);
            break;

        default:
            admin_respondJson(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
}

function admin_respondJson($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
        admin_handleUserRequest();
    }
}
