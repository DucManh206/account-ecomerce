<?php
/**
 * Order Modules - Quản lý đơn hàng (DÙNG CHUNG: admin & user)
 * Tất cả truy vấn dùng prepared statements.
 */

require_once __DIR__ . '/../db_helpers/db_helpers.php';
require_once __DIR__ . '/../../config/db.php';

/* =====================================================================
   TRẠNG THÁI ĐƠN HÀNG
   ===================================================================== */
define('ORDER_STATUS_PENDING',    'pending');
define('ORDER_STATUS_PROCESSING', 'processing');
define('ORDER_STATUS_COMPLETED',  'completed');
define('ORDER_STATUS_CANCELLED',  'cancelled');
define('ORDER_STATUS_REFUNDED',   'refunded');

function order_getStatusMap(): array {
    return [
        ORDER_STATUS_PENDING     => ['label' => 'Chờ xử lý',  'class' => 'nexus-pill-pending', 'icon' => 'fa-clock'],
        ORDER_STATUS_PROCESSING  => ['label' => 'Đang xử lý',  'class' => 'nexus-pill-pending', 'icon' => 'fa-spinner fa-spin'],
        ORDER_STATUS_COMPLETED   => ['label' => 'Hoàn tất',    'class' => 'nexus-pill-success', 'icon' => 'fa-check-circle'],
        ORDER_STATUS_CANCELLED   => ['label' => 'Đã hủy',      'class' => 'nexus-pill-danger',  'icon' => 'fa-xmark-circle'],
        ORDER_STATUS_REFUNDED    => ['label' => 'Đã hoàn tiền', 'class' => 'nexus-pill-danger',  'icon' => 'fa-rotate-left'],
    ];
}

/* =====================================================================
   ADMIN: THỐNG KÊ (dùng 1 truy vấn thay vì 6)
   ===================================================================== */
function order_getStats(): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return ['total' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0, 'revenue' => 0, 'today' => 0];

    $result = ['total' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0, 'revenue' => 0, 'today' => 0];

    $stmt = mysqli_prepare($conn,
        "SELECT status, COUNT(*) as cnt FROM orders GROUP BY status"
    );
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $s = $row['status'];
                if (isset($result[$s])) $result[$s] = intval($row['cnt']);
                if ($s === 'completed') $result['total'] += intval($row['cnt']);
            }
            mysqli_free_result($res);
        }
    }

    $row = crud_select_one(
        "SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = CURDATE()"
    );
    if ($row) $result['today'] = intval($row['cnt']);

    $row = crud_select_one(
        "SELECT COALESCE(SUM(price), 0) as total FROM orders WHERE status = ?",
        [ORDER_STATUS_COMPLETED], 's'
    );
    if ($row) $result['revenue'] = intval($row['total']);

    return $result;
}

/* =====================================================================
   ADMIN: LẤY DANH SÁCH ĐƠN HÀNG (có lọc, tìm kiếm, phân trang)
   ===================================================================== */
function order_getAll(array $opts = []): array {
    $defaults = ['limit' => 20, 'offset' => 0, 'status' => '', 'search' => '', 'user_id' => null];
    $opts = array_merge($defaults, $opts);

    $where = ['1=1'];
    $params = [];
    $types  = '';

    if ($opts['status'] !== '') {
        $where[] = "o.status = ?";
        $params[] = $opts['status'];
        $types .= 's';
    }
    if ($opts['search'] !== '') {
        $where[] = "(u.username LIKE ? OR p.title LIKE ? OR o.id LIKE ?)";
        $s = '%' . $opts['search'] . '%';
        $params[] = $s; $params[] = $s; $params[] = $s;
        $types .= 'sss';
    }
    if ($opts['user_id']) {
        $where[] = "o.user_id = ?";
        $params[] = $opts['user_id'];
        $types .= 'i';
    }

    $whereClause = implode(' AND ', $where);

    $total = crud_count(
        "SELECT COUNT(*) as cnt FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         LEFT JOIN products p ON o.product_id = p.id
         WHERE $whereClause",
        $params, $types
    );

    $dataParams = array_merge($params, [$opts['limit'], $opts['offset']]);
    $dataTypes  = $types . 'ii';

    $rows = crud_select(
        "SELECT o.*, u.username, p.title as product_title, p.image_url
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         LEFT JOIN products p ON o.product_id = p.id
         WHERE $whereClause
         ORDER BY o.created_at DESC
         LIMIT ? OFFSET ?",
        $dataParams, $dataTypes
    );

    return ['orders' => $rows, 'total' => $total];
}

/* =====================================================================
   ADMIN: LẤY ĐƠN HÀNG THEO ID
   ===================================================================== */
function order_getById(int $orderId): ?array {
    return crud_select_one(
        "SELECT o.*, u.username, p.title as product_title, p.image_url,
                p.category, p.icon_class, p.color_class
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         LEFT JOIN products p ON o.product_id = p.id
         WHERE o.id = ?",
        [$orderId], 'i'
    );
}

/* =====================================================================
   ADMIN: CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
   ===================================================================== */
function order_updateStatus(int $orderId, string $status): array {
    $validStatuses = [ORDER_STATUS_PENDING, ORDER_STATUS_PROCESSING, ORDER_STATUS_COMPLETED, ORDER_STATUS_CANCELLED, ORDER_STATUS_REFUNDED];
    if (!in_array($status, $validStatuses, true)) {
        return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
    }

    $affected = crud_exec(
        "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?",
        [$status, $orderId], 'si'
    );

    if ($affected > 0) {
        return ['success' => true, 'message' => 'Cập nhật thành công'];
    }
    return ['success' => false, 'message' => 'Không tìm thấy đơn hàng'];
}

/* =====================================================================
   ADMIN: XÓA ĐƠN HÀNG
   ===================================================================== */
function order_delete(int $orderId): array {
    $affected = crud_exec("DELETE FROM orders WHERE id = ?", [$orderId], 'i');
    if ($affected > 0) {
        return ['success' => true, 'message' => 'Xóa thành công'];
    }
    return ['success' => false, 'message' => 'Không tìm thấy đơn hàng'];
}

/* =====================================================================
   ADMIN: HOÀN TIỀN ĐƠN HÀNG (transaction)
   ===================================================================== */
function order_refund(int $orderId): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];

    $order = order_getById($orderId);
    if (!$order) return ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
    if ($order['status'] === ORDER_STATUS_REFUNDED) return ['success' => false, 'message' => 'Đơn hàng đã được hoàn tiền'];
    if ($order['status'] !== ORDER_STATUS_COMPLETED) return ['success' => false, 'message' => 'Chỉ có thể hoàn tiền đơn đã hoàn tất'];

    $userId = intval($order['user_id']);
    $price  = intval($order['price']);
    $accId  = !empty($order['account_id']) ? intval($order['account_id']) : null;

    mysqli_begin_transaction($conn);
    try {
        crud_exec(
            "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?",
            [ORDER_STATUS_REFUNDED, $order['id']], 'si'
        );

        crud_exec(
            "UPDATE users SET balance = balance + ? WHERE id = ?",
            [$price, $userId], 'ii'
        );

        $balRow = crud_select_one(
            "SELECT balance FROM users WHERE id = ?", [$userId], 'i'
        );
        $newBal  = intval($balRow['balance'] ?? 0);
        $balBefore = $newBal - $price;

        crud_insert(
            "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
             VALUES (?, ?, ?, ?, 'refund', ?, NOW())",
            [$userId, $price, $balBefore, $newBal,
             'Hoàn tiền đơn hàng #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT)],
            'iiiis'
        );

        if ($accId !== null) {
            crud_exec(
                "UPDATE account_stock SET status = 'available', sold_at = NULL WHERE id = ?",
                [$accId], 'i'
            );
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
function order_getByUser(string $username): array {
    return crud_select(
        "SELECT o.*, p.title as product_title, p.image_url,
                p.category, p.icon_class, p.color_class
         FROM orders o
         LEFT JOIN products p ON o.product_id = p.id
         LEFT JOIN users u ON o.user_id = u.id
         WHERE u.username = ?
         ORDER BY o.created_at DESC",
        [$username], 's'
    );
}

/* =====================================================================
   USER: CHI TIẾT ĐƠN HÀNG (chỉ chủ sở hữu)
   ===================================================================== */
function order_getDetailForUser(int $orderId, string $username): ?array {
    return crud_select_one(
        "SELECT o.*, p.title as product_title, p.image_url,
                p.category, p.icon_class, p.color_class
         FROM orders o
         LEFT JOIN products p ON o.product_id = p.id
         LEFT JOIN users u ON o.user_id = u.id
         WHERE o.id = ? AND u.username = ?",
        [$orderId, $username], 'is'
    );
}

function user_getOrderDetail(int $orderId, string $username): ?array {
    return order_getDetailForUser($orderId, $username);
}

/* =====================================================================
   USER: LỊCH SỬ GIAO DỊCH
   ===================================================================== */
function user_getTransactionHistory(string $username): array {
    return crud_select(
        "SELECT t.* FROM transactions t
         LEFT JOIN users u ON t.user_id = u.id
         WHERE u.username = ?
         ORDER BY t.created_at DESC
         LIMIT 100",
        [$username], 's'
    );
}

/* =====================================================================
   TẠO ĐƠN HÀNG
   ===================================================================== */
function order_create(int $userId, int $productId, int $price, string $accountData, ?int $accountId = null): int {
    return crud_insert(
        "INSERT INTO orders (user_id, product_id, account_id, price, account_data, status, created_at)
         VALUES (?, ?, ?, ?, ?, 'completed', NOW())",
        [$userId, $productId, $accountId, $price, $accountData],
        'iiids'
    );
}
