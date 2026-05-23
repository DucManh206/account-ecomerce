<?php
/**
 * Cart API - Xử lý AJAX requests cho giỏ hàng
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../lib/cart_modules.php';
require_once __DIR__ . '/../lib/user_modules.php';
require_once __DIR__ . '/../lib/order_modules.php';
require_once __DIR__ . '/../database/connect.php';

$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$username = $_SESSION['username'] ?? '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
            break;
        }

        $result = addToCart($productId, $quantity, $userId);
        echo json_encode($result);
        break;

    case 'buy_now':
        if (!$userId || !$username) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để mua hàng', 'redirect' => '../auth/login.php']);
            break;
        }

        $productId = intval($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
            break;
        }

        // Lấy thông tin sản phẩm
        $stmt = mysqli_prepare($conn, "SELECT id, title, price FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $productId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (!$result || mysqli_num_rows($result) === 0) {
            mysqli_stmt_close($stmt);
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            break;
        }
        $product = mysqli_fetch_assoc($result);
        $price = intval($product['price']);
        mysqli_stmt_close($stmt);

        // Lấy số dư
        $balance = getBalance($username);
        if ($balance === false || $balance === null) $balance = 0;

        if ($balance < $price) {
            echo json_encode([
                'success' => false,
                'message' => 'Số dư không đủ. Cần thêm ' . number_format($price - $balance) . 'đ',
                'balance' => $balance,
                'price' => $price,
            ]);
            break;
        }

        // Lấy tài khoản khả dụng
        $sql = "SELECT id, account_data FROM account_stock
                WHERE product_id = $productId AND status = 'available'
                ORDER BY RAND() LIMIT 1 FOR UPDATE";
        $res = mysqli_query($conn, $sql);
        if (!$res || mysqli_num_rows($res) === 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng']);
            break;
        }

        $account = mysqli_fetch_assoc($res);
        $accountId = intval($account['id']);
        $accountData = $account['account_data'];

        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "UPDATE account_stock SET status='sold', sold_at=NOW() WHERE id=$accountId");

            $accountEsc = mysqli_real_escape_string($conn, $accountData);
            $sql = "INSERT INTO orders (user_id, product_id, account_id, price, account_data, status, created_at)
                    VALUES ($userId, $productId, $accountId, $price, '$accountEsc', 'completed', NOW())";
            if (!mysqli_query($conn, $sql)) {
                throw new Exception('Lỗi tạo đơn hàng');
            }
            $orderId = mysqli_insert_id($conn);

            $newBalance = $balance - $price;
            mysqli_query($conn, "UPDATE users SET balance=$newBalance WHERE id=$userId");

            $desc = 'Mua: ' . mysqli_real_escape_string($conn, $product['title']);
            mysqli_query($conn, "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
                         VALUES ($userId, -$price, $balance, $newBalance, 'purchase', '$desc', NOW())");

            mysqli_commit($conn);

            // Lưu vào session cho trang success
            $_SESSION['last_checkout'] = [
                'order_ids' => [$orderId],
                'new_balance' => $newBalance,
                'total_paid' => $price,
                'accounts' => [
                    [
                        'order_id' => $orderId,
                        'product' => $product['title'],
                        'account' => json_decode($accountData, true),
                    ]
                ],
                'completed_at' => date('Y-m-d H:i:s'),
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Mua hàng thành công!',
                'order_id' => $orderId,
                'redirect' => '../cart/success.php',
                'new_balance' => $newBalance,
            ]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            mysqli_query($conn, "UPDATE account_stock SET status='available', sold_at=NULL WHERE id=$accountId");
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    
    case 'update':
        $cartId = intval($_POST['cart_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        
        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID giỏ hàng không hợp lệ']);
            break;
        }
        
        $result = updateCartQuantity($cartId, $quantity, $userId);
        echo json_encode($result);
        break;
    
    case 'remove':
        $cartId = intval($_POST['cart_id'] ?? 0);
        
        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID giỏ hàng không hợp lệ']);
            break;
        }
        
        $result = removeFromCart($cartId, $userId);
        echo json_encode($result);
        break;
    
    case 'clear':
        $result = clearCart($userId);
        echo json_encode($result);
        break;
    
    case 'list':
        $items = getCartItems($userId);
        $count = getCartCount($userId);
        $total = getCartTotal($userId);
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'count' => $count,
            'total' => $total
        ]);
        break;
    
    case 'count':
        $count = getCartCount($userId);
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        break;
    
    case 'check':
        $productId = intval($_POST['product_id'] ?? 0);
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'in_cart' => false]);
            break;
        }
        
        $inCart = isInCart($productId, $userId);
        echo json_encode([
            'success' => true,
            'in_cart' => $inCart
        ]);
        break;
    
    case 'merge':
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để gộp giỏ hàng']);
            break;
        }
        
        $result = mergeGuestCart($userId);
        echo json_encode($result);
        break;
    
    case 'checkout':
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thanh toán']);
            break;
        }
        
        $items = getCartItems($userId);
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']);
            break;
        }
        
        $total = getCartTotal($userId);
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'total' => $total,
            'message' => 'Sẵn sàng thanh toán'
        ]);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}
