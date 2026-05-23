<?php
require_once __DIR__ . '/../../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getAccountFieldTypes() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM account_field_types ORDER BY sort_order ASC, id ASC");
    $items = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) { $items[] = $row; }
    }
    return $items;
}

function admin_getAccountFieldTypeById($id) {
    global $conn;
    $id = intval($id);
    $result = mysqli_query($conn, "SELECT * FROM account_field_types WHERE id = $id LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_createAccountFieldType($data) {
    global $conn;
    $key = trim($data['key'] ?? '');
    $label = trim($data['label'] ?? '');
    $icon_class = trim($data['icon_class'] ?? 'fa-key');
    $placeholder = trim($data['placeholder'] ?? '');
    $sort_order = intval($data['sort_order'] ?? 0);
    $is_default = isset($data['is_default']) ? 1 : 0;

    if (empty($key) || empty($label)) {
        return ['success' => false, 'message' => 'Key va Label bat buoc'];
    }
    if (!preg_match('/^[a-z0-9_]+$/', $key)) {
        return ['success' => false, 'message' => 'Key chi chua a-z, 0-9, dau gach duoi'];
    }

    $keyEsc = mysqli_real_escape_string($conn, $key);
    $labelEsc = mysqli_real_escape_string($conn, $label);
    $iconEsc = mysqli_real_escape_string($conn, $icon_class);
    $phEsc = mysqli_real_escape_string($conn, $placeholder);

    $sql = "INSERT INTO account_field_types (`key`, label, icon_class, placeholder, sort_order, is_default)
            VALUES ('$keyEsc', '$labelEsc', '$iconEsc', '$phEsc', $sort_order, $is_default)";

    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Them thanh cong', 'id' => mysqli_insert_id($conn)];
    }
    $err = mysqli_error($conn);
    if (strpos($err, 'Duplicate') !== false || strpos($err, 'for key') !== false) {
        return ['success' => false, 'message' => 'Key da ton tai'];
    }
    return ['success' => false, 'message' => $err];
}

function admin_updateAccountFieldType($id, $data) {
    global $conn;
    $id = intval($id);
    $label = trim($data['label'] ?? '');
    $icon_class = trim($data['icon_class'] ?? 'fa-key');
    $placeholder = trim($data['placeholder'] ?? '');
    $sort_order = intval($data['sort_order'] ?? 0);
    $is_default = isset($data['is_default']) ? 1 : 0;

    if (empty($label)) return ['success' => false, 'message' => 'Label bat buoc'];

    $labelEsc = mysqli_real_escape_string($conn, $label);
    $iconEsc = mysqli_real_escape_string($conn, $icon_class);
    $phEsc = mysqli_real_escape_string($conn, $placeholder);

    $sql = "UPDATE account_field_types SET label='$labelEsc', icon_class='$iconEsc',
            placeholder='$phEsc', sort_order=$sort_order, is_default=$is_default WHERE id=$id LIMIT 1";

    if (mysqli_query($conn, $sql)) return ['success' => true, 'message' => 'Cap nhat thanh cong'];
    return ['success' => false, 'message' => mysqli_error($conn)];
}

function admin_deleteAccountFieldType($id) {
    global $conn;
    $id = intval($id);
    $r = mysqli_query($conn, "SELECT is_default FROM account_field_types WHERE id=$id");
    if ($r && mysqli_fetch_assoc($r)['is_default']) {
        return ['success' => false, 'message' => 'Khong the xoa field mac dinh'];
    }
    $sql = "DELETE FROM account_field_types WHERE id=$id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => mysqli_affected_rows($conn) > 0, 'message' => 'Xoa thanh cong'];
    }
    return ['success' => false, 'message' => mysqli_error($conn)];
}

function admin_handleAccountFieldRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = admin_createAccountFieldType($_POST);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_updateAccountFieldType($id, $_POST) : ['success' => false, 'message' => 'ID khong hop le'];
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_deleteAccountFieldType($id) : ['success' => false, 'message' => 'ID khong hop le'];
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $result = ['success' => true, 'data' => admin_getAccountFieldTypeById($id)];
            break;
        case 'list':
            $result = ['success' => true, 'data' => admin_getAccountFieldTypes()];
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
        admin_handleAccountFieldRequest();
    }
}
