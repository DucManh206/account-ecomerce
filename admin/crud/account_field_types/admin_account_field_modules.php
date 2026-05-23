<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getAccountFieldTypes() {
    global $conn;
    $sql = "SELECT * FROM account_field_types ORDER BY sort_order ASC, id ASC";
    $result = mysqli_query($conn, $sql);
    $items = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { $items[] = $row; }
    }
    return $items;
}

function admin_getAccountFieldTypeById($id) {
    global $conn;
    $id = intval($id);
    $sql = "SELECT * FROM account_field_types WHERE id = $id LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_createAccountFieldType($data) {
    global $conn;

    $key = mysqli_real_escape_string($conn, trim($data['key'] ?? ''));
    $label = mysqli_real_escape_string($conn, trim($data['label'] ?? ''));
    $icon_class = mysqli_real_escape_string($conn, trim($data['icon_class'] ?? 'fa-key'));
    $placeholder = mysqli_real_escape_string($conn, trim($data['placeholder'] ?? ''));
    $sort_order = intval($data['sort_order'] ?? 0);
    $is_default = isset($data['is_default']) ? 1 : 0;

    if (empty($key) || empty($label)) {
        return ['success' => false, 'message' => 'Key và Label bắt buộc'];
    }
    if (!preg_match('/^[a-z0-9_]+$/', $key)) {
        return ['success' => false, 'message' => 'Key chỉ chứa a-z, 0-9, dấu gạch dưới'];
    }

    $sql = "INSERT INTO account_field_types ( `key`, label, icon_class, placeholder, sort_order, is_default)
    VALUES ('$key', '$label', '$icon_class', '$placeholder', $sort_order, $is_default)";

    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Thêm thành công', 'id' => mysqli_insert_id($conn)];
    }
    $err = mysqli_error($conn);
    if (strpos($err, 'Duplicate') !== false || strpos($err, 'for key') !== false) {
        return ['success' => false, 'message' => 'Key đã tồn tại'];
    }
    return ['success' => false, 'message' => $err];
}

function admin_updateAccountFieldType($id, $data) {
    global $conn;
    $id = intval($id);

    $label = mysqli_real_escape_string($conn, trim($data['label'] ?? ''));
    $icon_class = mysqli_real_escape_string($conn, trim($data['icon_class'] ?? 'fa-key'));
    $placeholder = mysqli_real_escape_string($conn, trim($data['placeholder'] ?? ''));
    $sort_order = intval($data['sort_order'] ?? 0);

    if (empty($label)) return ['success' => false, 'message' => 'Label bắt buộc'];

    $sql = "UPDATE account_field_types SET label='$label', icon_class='$icon_class', placeholder='$placeholder', sort_order=$sort_order WHERE id=$id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Cập nhật thành công'];
    }
    return ['success' => false, 'message' => mysqli_error($conn)];
}

function admin_deleteAccountFieldType($id) {
    global $conn;
    $id = intval($id);

    $r = mysqli_query($conn, "SELECT is_default FROM account_field_types WHERE id=$id");
    if ($r && mysqli_fetch_assoc($r)['is_default']) {
        return ['success' => false, 'message' => 'Không thể xóa field mặc định'];
    }

    $sql = "DELETE FROM account_field_types WHERE id=$id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => mysqli_affected_rows($conn) > 0, 'message' => 'Xóa thành công'];
    }
    return ['success' => false, 'message' => mysqli_error($conn)];
}

function admin_handleAccountFieldTypeRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = admin_createAccountFieldType($_POST);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_updateAccountFieldType($id, $_POST) : ['success' => false, 'message' => 'ID không hợp lệ'];
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_deleteAccountFieldType($id) : ['success' => false, 'message' => 'ID không hợp lệ'];
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $result = ['success' => true, 'data' => admin_getAccountFieldTypeById($id)];
            break;
        case 'list':
            $result = ['success' => true, 'data' => admin_getAccountFieldTypes()];
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
        admin_handleAccountFieldTypeRequest();
    }
}
?>
