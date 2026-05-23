<?php
/**
 * Checkout API - Mua ngay (buy now, không qua cart)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../crud/cart/cart_modules.php';
require_once __DIR__ . '/../../crud/users/user_modules.php';
require_once __DIR__ . '/../../crud/orders/order_modules.php';
require_once __DIR__ . '/../../config/db.php';

/* =====================================================================
   XỬ LÝ REQUEST
   ===================================================================== */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để mua hàng']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'buy':
        handleBuyProduct();
        break;
    case 'check_stock':
        handleCheckStock();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
}

/* =====================================================================
   MUA SẢN PHẨM
   ===================================================================== */
function handleBuyProduct() {
    global $conn;

    $productId = intval($_POST['product_id'] ?? 0);
    $username  = $_SESSION['username'];
    $userId    = intval($_SESSION['user_id']);

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
        return;
    }

    // Lấy thông tin sản phẩm
    $stmt = mysqli_prepare($conn, "SELECT id, title, price FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        return;
    }

    $product = mysqli_fetch_assoc($result);
    $price   = intval($product['price']);
    mysqli_stmt_close($stmt);

    // Lấy thông tin người dùng
    $stmt = mysqli_prepare($conn, "SELECT id, balance FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
        return;
    }

    $user     = mysqli_fetch_assoc($result);
    $balance  = intval($user['balance']);
    mysqli_stmt_close($stmt);

    // Kiểm tra số dư
    if ($balance < $price) {
        echo json_encode([
            'success' => false,
            'message' => 'Số dư không đủ',
            'balance' => $balance,
            'price'   => $price,
            'needed'  => $price - $balance,
        ]);
        return;
    }

    // Lấy tài khoản khả dụng từ kho
    $sql = "SELECT id, account_data FROM account_stock
            WHERE product_id = ? AND status = 'available'
            ORDER BY RAND() LIMIT 1 FOR UPDATE";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) === 0) {
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => false, 'message' => 'Tài khoản không khả dụng. Vui lòng thử lại sau']);
        return;
    }

    $account    = mysqli_fetch_assoc($result);
    $accountId  = intval($account['id']);
    $accountData = $account['account_data'];
    mysqli_stmt_close($stmt);

    mysqli_begin_transaction($conn);
    try {
        // Đánh dấu tài khoản đã bán
        $updStock = mysqli_prepare($conn, "UPDATE account_stock SET status = 'sold', sold_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($updStock, "i", $accountId);
        mysqli_stmt_execute($updStock);
        mysqli_stmt_close($updStock);

        // Tạo đơn hàng
        $sql = "INSERT INTO orders (user_id, product_id, account_id, price, account_data, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'completed', NOW())";
        $stmtOrder = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmtOrder, "iiiis", $userId, $productId, $accountId, $price, $accountData);
        if (!mysqli_stmt_execute($stmtOrder)) {
            throw new Exception('Lỗi tạo đơn hàng');
        }
        $orderId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtOrder);

        // Cập nhật số dư
        $newBalance = $balance - $price;
        $updUser = mysqli_prepare($conn, "UPDATE users SET balance = ? WHERE id = ?");
        mysqli_stmt_bind_param($updUser, "ii", $newBalance, $userId);
        mysqli_stmt_execute($updUser);
        mysqli_stmt_close($updUser);

        // Ghi giao dịch
        $desc    = 'Mua sản phẩm: ' . $product['title'];
        $insTrans = mysqli_prepare($conn, "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
                             VALUES (?, ?, ?, ?, 'purchase', ?, NOW())");
        $negPrice = -$price;
        mysqli_stmt_bind_param($insTrans, "iiiis", $userId, $negPrice, $balance, $newBalance, $desc);
        mysqli_stmt_execute($insTrans);
        mysqli_stmt_close($insTrans);

        mysqli_commit($conn);

        echo json_encode([
            'success'       => true,
            'message'       => 'Mua hàng thành công',
            'order_id'      => $orderId,
            'new_balance'   => $newBalance,
            'product_title'  => $product['title'],
            'price'         => $price,
            'account_data'  => json_decode($accountData, true),
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_query($conn, "UPDATE account_stock SET status = 'available', sold_at = NULL WHERE id = $accountId");
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/* =====================================================================
   KIỂM TRA TỒN KHO
   ===================================================================== */
function handleCheckStock() {
    global $conn;

    $productId = intval($_GET['product_id'] ?? 0);

    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
        return;
    }

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM account_stock WHERE product_id = ? AND status = 'available'");
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $count = 0;
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $count = intval($row['cnt']);
    }
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success'    => true,
        'product_id' => $productId,
        'available'  => $count > 0,
        'count'      => $count,
    ]);
}
