<?php
require_once __DIR__ . '/../../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getBanks() {
    global $conn;
    $sql = "SELECT * FROM banks ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    $items = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    }
    return $items;
}

function admin_getBankById($id) {
    global $conn;
    $id = intval($id);
    $result = mysqli_query($conn, "SELECT * FROM banks WHERE id = $id LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_createBank($data) {
    global $conn;
    $name         = trim($data['name'] ?? '');
    $account_no   = trim($data['account_no'] ?? '');
    $account_name = trim($data['account_name'] ?? '');
    $branch       = trim($data['branch'] ?? '');
    $qr_template = trim($data['qr_template'] ?? '');
    $status       = $data['status'] ?? 'active';

    if (!$name || !$account_no) {
        return ['success' => false, 'message' => 'Thieu thong tin bat buoc'];
    }

    $nameEsc       = mysqli_real_escape_string($conn, $name);
    $accountNoEsc  = mysqli_real_escape_string($conn, $account_no);
    $accountNameEsc = mysqli_real_escape_string($conn, $account_name);
    $branchEsc     = mysqli_real_escape_string($conn, $branch);
    $qrEsc         = mysqli_real_escape_string($conn, $qr_template);
    $statusEsc     = mysqli_real_escape_string($conn, $status);

    $sql = "INSERT INTO banks (name, account_no, account_name, branch, qr_template, status, created_at)
            VALUES ('$nameEsc', '$accountNoEsc', '$accountNameEsc', '$branchEsc', '$qrEsc', '$statusEsc', NOW())";

    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Them thanh cong', 'id' => mysqli_insert_id($conn)];
    }
    return ['success' => false, 'message' => 'Loi: ' . mysqli_error($conn)];
}

function admin_updateBank($id, $data) {
    global $conn;
    $id = intval($id);

    $name         = trim($data['name'] ?? '');
    $account_no   = trim($data['account_no'] ?? '');
    $account_name = trim($data['account_name'] ?? '');
    $branch       = trim($data['branch'] ?? '');
    $qr_template  = trim($data['qr_template'] ?? '');
    $status       = $data['status'] ?? 'active';

    if (!$name || !$account_no) {
        return ['success' => false, 'message' => 'Thieu thong tin bat buoc'];
    }

    $nameEsc       = mysqli_real_escape_string($conn, $name);
    $accountNoEsc  = mysqli_real_escape_string($conn, $account_no);
    $accountNameEsc = mysqli_real_escape_string($conn, $account_name);
    $branchEsc     = mysqli_real_escape_string($conn, $branch);
    $qrEsc         = mysqli_real_escape_string($conn, $qr_template);
    $statusEsc     = mysqli_real_escape_string($conn, $status);

    $sql = "UPDATE banks SET name='$nameEsc', account_no='$accountNoEsc', account_name='$accountNameEsc',
            branch='$branchEsc', qr_template='$qrEsc', status='$statusEsc', updated_at=NOW() WHERE id=$id LIMIT 1";

    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Cap nhat thanh cong'];
    }
    return ['success' => false, 'message' => 'Loi: ' . mysqli_error($conn)];
}

function admin_deleteBank($id) {
    global $conn;
    $id = intval($id);
    $sql = "DELETE FROM banks WHERE id = $id LIMIT 1";
    if (mysqli_query($conn, $sql)) {
        return ['success' => mysqli_affected_rows($conn) > 0, 'message' => 'Xoa thanh cong'];
    }
    return ['success' => false, 'message' => 'Loi: ' . mysqli_error($conn)];
}

function admin_handleBankRequest() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'create':
            $result = admin_createBank($_POST);
            break;
        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_updateBank($id, $_POST) : ['success' => false, 'message' => 'ID khong hop le'];
            break;
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $result = ($id > 0) ? admin_deleteBank($id) : ['success' => false, 'message' => 'ID khong hop le'];
            break;
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            $bank = admin_getBankById($id);
            $result = ['success' => $bank !== null, 'data' => $bank];
            break;
        case 'list':
            $banks = admin_getBanks();
            $result = ['success' => true, 'data' => $banks];
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
        admin_handleBankRequest();
    }
}
