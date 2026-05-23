<?php
/**
 * Order Modules - Quản lý đơn hàng (DÙNG CHUNG: admin & user)
 * Tất cả hàm liên quan đến đơn hàng ở đây.
 * Dùng prepared statements cho tất cả truy vấn.
 */

require_once __DIR__ . '/../../config/db.php';

/* =====================================================================
   TRẠNG THÁI ĐƠN HÀNG
   ===================================================================== */
define('ORDER_STATUS_PENDING',    'pending');
define('ORDER_STATUS_PROCESSING', 'processing');
define('ORDER_STATUS_COMPLETED',  'completed');
define('ORDER_STATUS_CANCELLED',  'cancelled');
define('ORDER_STATUS_REFUNDED',   'refunded');

function order_getStatusMap() {
    return [
        ORDER_STATUS_PENDING    => ['label' => 'Chờ xử lý',  'class' => 'bg-secondary',   'icon' => 'fa-clock'],
        ORDER_STATUS_PROCESSING=> ['label' => 'Đang xử lý',  'class' => 'bg-warning',      'icon' => 'fa-spinner'],
        ORDER_STATUS_COMPLETED => ['label' => 'Hoàn tất',     'class' => 'bg-success',      'icon' => 'fa-check-circle'],
        ORDER_STATUS_CANCELLED => ['label' => 'Đã hủy',       'class' => 'bg-danger',       'icon' => 'fa-xmark-circle'],
        ORDER_STATUS_REFUNDED  => ['label' => 'Đã hoàn tiền', 'class' => 'bg-info',        'icon' => 'fa-rotate-left'],
    ];
}

/* =====================================================================
   ADMIN: THỐNG KÊ
   ===================================================================== */
function order_getStats() {
    global $conn;

    $stats = [
        'total'      => 0,
        'pending'    => 0,
        'completed'  => 0,
        'cancelled'  => 0,
        'revenue'    => 0,
        'today'      => 0,
    ];

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $stats['total'] = intval($row['cnt']);
    }

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $stats['pending'] = intval($row['cnt']);
    }

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE status = 'completed'");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $stats['completed'] = intval($row['cnt']);
    }

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE status = 'cancelled'");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $stats['cancelled'] = intval($row['cnt']);
    }

    $r = mysqli_query($conn, "SELECT COALESCE(SUM(price), 0) as total FROM orders WHERE status = 'completed'");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $stats['revenue'] = intval($row['total']);
    }

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = CURDATE()");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $stats['today'] = intval($row['cnt']);
    }

    return $stats;
}

/* =====================================================================
   ADMIN: LẤY DANH SÁCH ĐƠN HÀNG (có lọc, tìm kiếm, phân trang)
   ===================================================================== */
function order_getAll($opts = []) {
    global $conn;

    $defaults = [
        'limit'   => 20,
        'offset'  => 0,
        'status'  => '',
        'search'  => '',
        'user_id' => null,
    ];
    $opts = array_merge($defaults, $opts);

    $where = ['1=1'];
    $params = [];
    $types  = '';

    if ($opts['status'] !== '') {
        $where[] = "o.status = ?";
        $params[] = $opts['status'];
        $types  .= 's';
    }

    if ($opts['search'] !== '') {
        $where[] = "(u.username LIKE ? OR p.title LIKE ? OR o.id LIKE ?)";
        $s = '%' . $opts['search'] . '%';
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
        $types  .= 'sss';
    }

    if ($opts['user_id']) {
        $where[] = "o.user_id = ?";
        $params[] = $opts['user_id'];
        $types  .= 'i';
    }

    $whereClause = implode(' AND ', $where);

    // Count query
    $countSql = "SELECT COUNT(*) as total
                 FROM orders o
                 LEFT JOIN users u ON o.user_id = u.id
                 LEFT JOIN products p ON o.product_id = p.id
                 WHERE $whereClause";
    $countStmt = mysqli_prepare($conn, $countSql);
    if (!$countStmt) return ['orders' => [], 'total' => 0];
    if ($params) {
        mysqli_stmt_bind_param($countStmt, $types, ...$params);
    }
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $total = 0;
    if ($countResult && $row = mysqli_fetch_assoc($countResult)) {
        $total = intval($row['total']);
    }
    mysqli_stmt_close($countStmt);

    // Data query
    $dataSql = "SELECT o.*, u.username, p.title as product_title, p.image_url
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN products p ON o.product_id = p.id
                WHERE $whereClause
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?";
    $dataParams = array_merge($params, [$opts['limit'], $opts['offset']]);
    $dataTypes  = $types . 'ii';

    $dataStmt = mysqli_prepare($conn, $dataSql);
    if (!$dataStmt) return ['orders' => [], 'total' => $total];
    mysqli_stmt_bind_param($dataStmt, $dataTypes, ...$dataParams);
    mysqli_stmt_execute($dataStmt);
    $dataResult = mysqli_stmt_get_result($dataStmt);

    $orders = [];
    if ($dataResult) {
        while ($row = mysqli_fetch_assoc($dataResult)) {
            $orders[] = $row;
        }
    }
    mysqli_stmt_close($dataStmt);

    return ['orders' => $orders, 'total' => $total];
}

/* =====================================================================
   ADMIN: LẤY ĐƠN HÀNG THEO ID
   ===================================================================== */
function order_getById($orderId) {
    global $conn;

    $id = intval($orderId);
    $sql = "SELECT o.*, u.username, p.title as product_title, p.image_url,
                   p.category, p.icon_class, p.color_class
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN products p ON o.product_id = p.id
            WHERE o.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $order = null;
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $order = $row;
    }
    mysqli_stmt_close($stmt);

    return $order;
}

/* =====================================================================
   ADMIN: CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
   ===================================================================== */
function order_updateStatus($orderId, $status) {
    global $conn;

    $validStatuses = [
        ORDER_STATUS_PENDING,
        ORDER_STATUS_PROCESSING,
        ORDER_STATUS_COMPLETED,
        ORDER_STATUS_CANCELLED,
        ORDER_STATUS_REFUNDED,
    ];
    if (!in_array($status, $validStatuses, true)) {
        return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
    }

    $id = intval($orderId);
    $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return ['success' => false, 'message' => 'Lỗi database'];

    mysqli_stmt_bind_param($stmt, 'si', $status, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['success' => $ok, 'message' => $ok ? 'Cập nhật thành công' : 'Lỗi khi cập nhật'];
}

/* =====================================================================
   ADMIN: XÓA ĐƠN HÀNG (soft delete - chỉ admin)
   ===================================================================== */
function order_delete($orderId) {
    global $conn;

    $id = intval($orderId);
    $sql = "DELETE FROM orders WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return ['success' => false, 'message' => 'Lỗi database'];

    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['success' => $ok, 'message' => $ok ? 'Xóa thành công' : 'Lỗi khi xóa'];
}

/* =====================================================================
   ADMIN: HOÀN TIỀN ĐƠN HÀNG
   ===================================================================== */
function order_refund($orderId) {
    global $conn;

    $order = order_getById($orderId);
    if (!$order) {
        return ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
    }
    if ($order['status'] === ORDER_STATUS_REFUNDED) {
        return ['success' => false, 'message' => 'Đơn hàng đã được hoàn tiền'];
    }
    if ($order['status'] !== ORDER_STATUS_COMPLETED) {
        return ['success' => false, 'message' => 'Chỉ có thể hoàn tiền đơn đã hoàn tất'];
    }

    $userId = intval($order['user_id']);
    $price  = intval($order['price']);

    mysqli_begin_transaction($conn);
    try {
        // Cập nhật trạng thái đơn hàng
        $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        $refunded = ORDER_STATUS_REFUNDED;
        mysqli_stmt_bind_param($stmt, 'si', $refunded, $order['id']);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi cập nhật trạng thái');
        }
        mysqli_stmt_close($stmt);

        // Hoàn tiền cho user
        $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $price, $userId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi hoàn tiền');
        }
        mysqli_stmt_close($stmt);

        // Lấy số dư mới sau khi hoàn
        $sql = "SELECT balance FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $balResult = mysqli_stmt_get_result($stmt);
        $newBal = 0;
        if ($balResult && $balRow = mysqli_fetch_assoc($balResult)) {
            $newBal = intval($balRow['balance']);
        }
        mysqli_stmt_close($stmt);

        // Ghi nhận giao dịch hoàn tiền
        $sql = "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
                 VALUES (?, ?, ?, ?, 'refund', ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        $desc = 'Hoàn tiền đơn hàng #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
        $balBefore = $newBal - $price;
        mysqli_stmt_bind_param($stmt, 'iiiis', $userId, $price, $balBefore, $newBal, $desc);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Lỗi ghi giao dịch');
        }
        mysqli_stmt_close($stmt);

        // Hoàn tài khoản về kho (nếu có)
        if (!empty($order['account_id'])) {
            $accId = intval($order['account_id']);
            $sql = "UPDATE account_stock SET status = 'available', sold_at = NULL WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $accId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        mysqli_commit($conn);
        return ['success' => true, 'message' => 'Hoàn tiền thành công'];

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/* =====================================================================
   USER: LẤY LỊCH SỬ ĐƠN HÀNG
   ===================================================================== */
function order_getByUser($username) {
    global $conn;

    $sql = "SELECT o.*, p.title as product_title, p.image_url,
                   p.category, p.icon_class, p.color_class
            FROM orders o
            LEFT JOIN products p ON o.product_id = p.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE u.username = ?
            ORDER BY o.created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];

    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $orders = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $orders[] = $row;
        }
    }
    mysqli_stmt_close($stmt);

    return $orders;
}

/* =====================================================================
   USER: LẤY CHI TIẾT ĐƠN HÀNG (chỉ chủ sở hữu mới xem được)
   ===================================================================== */
function order_getDetailForUser($orderId, $username) {
    global $conn;

    $id = intval($orderId);
    $sql = "SELECT o.*, p.title as product_title, p.image_url,
                   p.category, p.icon_class, p.color_class
            FROM orders o
            LEFT JOIN products p ON o.product_id = p.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ? AND u.username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, 'is', $id, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $order = null;
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $order = $row;
    }
    mysqli_stmt_close($stmt);

    return $order;
}

/* =====================================================================
   USER: LẤY CHI TIẾT ĐƠN HÀNG (dùng cho user/orders.php & order-detail.php)
   ===================================================================== */
function user_getOrderDetail($orderId, $username) {
    return order_getDetailForUser($orderId, $username);
}

/* =====================================================================
   USER: LỊCH SỬ GIAO DỊCH
   ===================================================================== */
function user_getTransactionHistory($username) {
    global $conn;

    $sql = "SELECT t.*
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE u.username = ?
            ORDER BY t.created_at DESC
            LIMIT 100";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];

    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $transactions = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $transactions[] = $row;
        }
    }
    mysqli_stmt_close($stmt);

    return $transactions;
}

/* =====================================================================
   TẠO ĐƠN HÀNG (dùng khi mua)
   ===================================================================== */
function order_create($userId, $productId, $price, $accountData, $accountId = null) {
    global $conn;

    $uid   = intval($userId);
    $pid   = intval($productId);
    $price = intval($price);
    $accId = $accountId ? intval($accountId) : null;

    $sql = "INSERT INTO orders (user_id, product_id, account_id, price, account_data, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'completed', NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, 'iiids', $uid, $pid, $accId, $price, $accountData);
    $ok = mysqli_stmt_execute($stmt);
    $orderId = $ok ? mysqli_insert_id($conn) : false;
    mysqli_stmt_close($stmt);

    return $orderId;
}
