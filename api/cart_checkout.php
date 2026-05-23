<?php
/**
 * Cart Checkout API - Thanh toán giỏ hàng
 * Mỗi đơn hàng = 1 tài khoản duy nhất.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../lib/cart_modules.php';
require_once __DIR__ . '/../lib/user_modules.php';
require_once __DIR__ . '/../lib/order_modules.php';
require_once __DIR__ . '/../database/connect.php';

/* =====================================================================
   XỬ LÝ REQUEST
   ===================================================================== */
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

/* =====================================================================
   LẤY & KIỂM TRA GIỎ HÀNG
   ===================================================================== */
$cartItems = getCartItems($userId);

if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống hoặc sản phẩm đã hết hàng']);
    exit;
}

$total = getCartTotal($userId);

/* =====================================================================
   KIỂM TRA SỐ DƯ
   ===================================================================== */
$userBalance = getBalance($username);
if ($userBalance === false || $userBalance === null) {
    $userBalance = 0;
}

if ($userBalance < $total) {
    echo json_encode([
        'success' => false,
        'message' => 'Số dư không đủ. Số dư: ' . number_format($userBalance) . 'đ, Cần: ' . number_format($total) . 'đ',
        'balance' => $userBalance,
        'total'   => $total,
    ]);
    exit;
}

/* =====================================================================
   XỬ LÝ THANH TOÁN
   ===================================================================== */
$orderIds   = [];
$soldAccs   = [];
$errors     = [];
$newBalance = $userBalance;

foreach ($cartItems as $item) {
    $productId = intval($item['product_id']);
    $cartId    = intval($item['cart_id']);
    $price     = intval($item['price']);
    $title     = $item['title'];

    // Lấy 1 tài khoản ngẫu nhiên từ kho
    $sql = "SELECT id, account_data FROM account_stock
            WHERE product_id = $productId AND status = 'available'
            ORDER BY RAND() LIMIT 1 FOR UPDATE";
    $result = mysqli_query($conn, $sql);

    if (!$result || mysqli_num_rows($result) === 0) {
        $errors[] = "$title: đã hết hàng";
        continue;
    }

    $account    = mysqli_fetch_assoc($result);
    $accountId  = intval($account['id']);
    $accountData = $account['account_data'];

    mysqli_begin_transaction($conn);
    try {
        // 1. Đánh dấu tài khoản đã bán
        mysqli_query($conn, "UPDATE account_stock SET status = 'sold', sold_at = NOW() WHERE id = $accountId");

        // 2. Tạo đơn hàng
        $accountEsc = mysqli_real_escape_string($conn, $accountData);
        $sql = "INSERT INTO orders (user_id, product_id, account_id, price, account_data, status, created_at)
                VALUES ($userId, $productId, $accountId, $price, '$accountEsc', 'completed', NOW())";
        if (!mysqli_query($conn, $sql)) {
            throw new Exception('Lỗi tạo đơn hàng');
        }
        $orderId = mysqli_insert_id($conn);

        // 3. Trừ số dư
        $newBalance -= $price;
        mysqli_query($conn, "UPDATE users SET balance = $newBalance WHERE id = $userId");

        // 4. Ghi giao dịch
        $desc    = "Mua: " . mysqli_real_escape_string($conn, $title);
        $balBefore = $newBalance + $price;
        mysqli_query($conn, "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
                             VALUES ($userId, -$price, $balBefore, $newBalance, 'purchase', '$desc', NOW())");

        // 5. Xóa khỏi giỏ hàng
        mysqli_query($conn, "DELETE FROM cart WHERE id = $cartId");

        $orderIds[] = $orderId;
        $soldAccs[] = [
            'order_id' => $orderId,
            'product'  => $title,
            'account'  => json_decode($accountData, true),
        ];

        mysqli_commit($conn);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        // Hoàn lại trạng thái tài khoản
        mysqli_query($conn, "UPDATE account_stock SET status = 'available', sold_at = NULL WHERE id = $accountId");
        $errors[] = "$title: " . $e->getMessage();
    }
}

/* =====================================================================
   TRẢ KẾT QUẢ
   ===================================================================== */
if (empty($orderIds)) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể xử lý đơn hàng: ' . implode('; ', $errors),
        'errors'  => $errors,
    ]);
    exit;
}

// Lưu thông tin checkout vào session cho trang success
$_SESSION['last_checkout'] = [
    'order_ids'   => $orderIds,
    'new_balance' => $newBalance,
    'total_paid'  => $userBalance - $newBalance,
    'accounts'    => $soldAccs,
    'completed_at'=> date('Y-m-d H:i:s'),
];

echo json_encode([
    'success'     => true,
    'message'      => count($orderIds) . ' đơn hàng đã được xử lý!',
    'order_ids'    => $orderIds,
    'new_balance'  => $newBalance,
    'total_paid'   => $userBalance - $newBalance,
    'accounts'     => $soldAccs,
    'errors'       => $errors,
]);
