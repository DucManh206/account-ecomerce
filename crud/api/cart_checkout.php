<?php
/**
 * Cart Checkout API - Thanh toán giỏ hàng
 * Supports buying multiple accounts per product via cart quantity.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../crud/cart/cart_modules.php';
require_once __DIR__ . '/../../crud/users/user_modules.php';
require_once __DIR__ . '/../../crud/orders/order_modules.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thanh toán']);
    exit;
}

$userId   = intval($_SESSION['user_id']);
$username = $_SESSION['username'];
$cartItems = getCartItems($userId);

if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống hoặc sản phẩm đã hết hàng']);
    exit;
}

$total = getCartTotal($userId);
$userBalance = getBalance($username);
if ($userBalance === false || $userBalance === null) $userBalance = 0;

if ($userBalance < $total) {
    echo json_encode([
        'success' => false,
        'message' => 'Số dư không đủ. Số dư: ' . number_format($userBalance) . 'đ, Cần: ' . number_format($total) . 'đ',
        'balance' => $userBalance,
        'total'   => $total,
    ]);
    exit;
}

mysqli_begin_transaction($conn);
$orderIds = [];
$soldAccs = [];
$errors = [];
$newBalance = $userBalance;
$totalPaid = 0;

try {
    foreach ($cartItems as $item) {
        $productId = intval($item['product_id']);
        $cartId    = intval($item['cart_id']);
        $price     = intval($item['price']);
        $title     = $item['title'];
        $quantity  = max(1, intval($item['quantity']));

        $stmtStock = mysqli_prepare($conn, "SELECT id, account_data FROM account_stock WHERE product_id = ? AND status = 'available' ORDER BY id ASC LIMIT ? FOR UPDATE");
        if (!$stmtStock) throw new Exception('Lỗi truy vấn kho tài khoản');
        mysqli_stmt_bind_param($stmtStock, 'ii', $productId, $quantity);
        mysqli_stmt_execute($stmtStock);
        $stockResult = mysqli_stmt_get_result($stmtStock);
        $accounts = [];
        while ($stockResult && ($acc = mysqli_fetch_assoc($stockResult))) {
            $accounts[] = $acc;
        }
        mysqli_stmt_close($stmtStock);

        if (count($accounts) < $quantity) {
            throw new Exception($title . ': chỉ còn ' . count($accounts) . '/' . $quantity . ' tài khoản khả dụng');
        }

        foreach ($accounts as $account) {
            $accountId = intval($account['id']);
            $accountData = $account['account_data'];

            $upd = mysqli_prepare($conn, "UPDATE account_stock SET status = 'sold', sold_at = NOW() WHERE id = ? AND status = 'available'");
            mysqli_stmt_bind_param($upd, 'i', $accountId);
            mysqli_stmt_execute($upd);
            if (mysqli_stmt_affected_rows($upd) !== 1) {
                mysqli_stmt_close($upd);
                throw new Exception($title . ': tài khoản #' . $accountId . ' không còn khả dụng');
            }
            mysqli_stmt_close($upd);

            $stmtOrder = mysqli_prepare($conn, "INSERT INTO orders (user_id, product_id, account_id, price, account_data, status, created_at) VALUES (?, ?, ?, ?, ?, 'completed', NOW())");
            if (!$stmtOrder) throw new Exception('Lỗi tạo đơn hàng');
            mysqli_stmt_bind_param($stmtOrder, 'iiiis', $userId, $productId, $accountId, $price, $accountData);
            if (!mysqli_stmt_execute($stmtOrder)) {
                mysqli_stmt_close($stmtOrder);
                throw new Exception('Lỗi tạo đơn hàng');
            }
            $orderId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmtOrder);

            $orderIds[] = $orderId;
            $soldAccs[] = [
                'order_id' => $orderId,
                'product'  => $title,
                'account'  => json_decode($accountData, true),
            ];

            $newBalance -= $price;
            $totalPaid += $price;
        }

        $del = mysqli_prepare($conn, "DELETE FROM cart WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($del, 'ii', $cartId, $userId);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
    }

    $updUser = mysqli_prepare($conn, "UPDATE users SET balance = ? WHERE id = ?");
    mysqli_stmt_bind_param($updUser, 'ii', $newBalance, $userId);
    mysqli_stmt_execute($updUser);
    mysqli_stmt_close($updUser);

    $desc = 'Thanh toán giỏ hàng: ' . count($orderIds) . ' tài khoản';
    $amount = -$totalPaid;
    $stmtTrans = mysqli_prepare($conn, "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at) VALUES (?, ?, ?, ?, 'purchase', ?, NOW())");
    mysqli_stmt_bind_param($stmtTrans, 'iiiis', $userId, $amount, $userBalance, $newBalance, $desc);
    mysqli_stmt_execute($stmtTrans);
    mysqli_stmt_close($stmtTrans);

    mysqli_commit($conn);

    $_SESSION['last_checkout'] = [
        'order_ids'    => $orderIds,
        'new_balance'  => $newBalance,
        'total_paid'   => $totalPaid,
        'accounts'     => $soldAccs,
        'completed_at' => date('Y-m-d H:i:s'),
    ];

    echo json_encode([
        'success'     => true,
        'message'     => count($orderIds) . ' tài khoản đã được mua thành công!',
        'order_ids'   => $orderIds,
        'new_balance' => $newBalance,
        'total_paid'  => $totalPaid,
        'accounts'    => $soldAccs,
        'errors'      => $errors,
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'errors' => [$e->getMessage()]]);
}
