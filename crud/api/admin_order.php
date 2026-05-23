<?php
/**
 * Xử lý: get, update_status, refund, delete
 */
session_start();
require_once __DIR__ . '/../../admin/crud/auth/admin_verifier_modules.php';
require_once __DIR__ . '/../../crud/orders/order_modules.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }
        $order = order_getById($id);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $order]);
        break;

    case 'update_status':
        $id     = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        $result = order_updateStatus($id, $status);
        echo json_encode($result);
        break;

    case 'refund':
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        $result = order_refund($id);
        echo json_encode($result);
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit;
        }

        $result = order_delete($id);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}
