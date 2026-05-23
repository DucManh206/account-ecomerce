<?php
/**
 * Cart API - Xử lý AJAX requests cho giỏ hàng
 * Dùng prepared statements cho tất cả truy vấn.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../cart/cart_modules.php';
require_once __DIR__ . '/../users/user_modules.php';
require_once __DIR__ . '/../orders/order_modules.php';
require_once __DIR__ . '/../db_helpers/db_helpers.php';

$userId   = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$username = $_SESSION['username'] ?? '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add': {
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity  = intval($_POST['quantity'] ?? 1);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
            break;
        }

        $result = addToCart($productId, $quantity, $userId);
        echo json_encode($result);
        break;
    }

    case 'buy_now': {
        if (!$userId || !$username) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để mua hàng', 'redirect' => '../auth/login.php']);
            break;
        }

        $productId = intval($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
            break;
        }

        $conn = $GLOBALS['conn'] ?? null;
        if (!$conn) {
            echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
            break;
        }

        $product = crud_select_one(
            "SELECT id, title, price FROM products WHERE id = ?",
            [$productId], 'i'
        );
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            break;
        }
        $price = intval($product['price']);

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

        $rows = crud_select(
            "SELECT id, account_data FROM account_stock
             WHERE product_id = ? AND status = 'available'
             ORDER BY RAND() LIMIT 1 FOR UPDATE",
            [$productId], 'i'
        );
        if (!$rows) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng']);
            break;
        }
        $account   = $rows[0];
        $accountId = intval($account['id']);
        $accountData = $account['account_data'];

        mysqli_begin_transaction($conn);
        try {
            crud_exec(
                "UPDATE account_stock SET status='sold', sold_at=NOW() WHERE id=?",
                [$accountId], 'i'
            );

            $orderId = crud_insert(
                "INSERT INTO orders (user_id, product_id, account_id, price, account_data, status, created_at)
                 VALUES (?, ?, ?, ?, ?, 'completed', NOW())",
                [$userId, $productId, $accountId, $price, $accountData],
                'iiids'
            );
            if (!$orderId) {
                throw new Exception('Lỗi tạo đơn hàng');
            }

            $newBalance = $balance - $price;
            crud_exec(
                "UPDATE users SET balance=? WHERE id=?",
                [$newBalance, $userId], 'ii'
            );

            crud_insert(
                "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
                 VALUES (?, ?, ?, ?, 'purchase', ?, NOW())",
                [$userId, -$price, $balance, $newBalance, 'Mua: ' . $product['title']],
                'iiiis'
            );

            mysqli_commit($conn);

            $_SESSION['last_checkout'] = [
                'order_ids'   => [$orderId],
                'new_balance' => $newBalance,
                'total_paid'  => $price,
                'accounts'    => [[
                    'order_id' => $orderId,
                    'product'   => $product['title'],
                    'account'   => json_decode($accountData, true),
                ]],
                'completed_at' => date('Y-m-d H:i:s'),
            ];

            echo json_encode([
                'success'     => true,
                'message'     => 'Mua hàng thành công!',
                'order_id'    => $orderId,
                'redirect'    => '../cart/success.php',
                'new_balance' => $newBalance,
            ]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            crud_exec(
                "UPDATE account_stock SET status='available', sold_at=NULL WHERE id=?",
                [$accountId], 'i'
            );
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
    }

    case 'update': {
        $cartId   = intval($_POST['cart_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID giỏ hàng không hợp lệ']);
            break;
        }

        $result = updateCartQuantity($cartId, $quantity, $userId);
        echo json_encode($result);
        break;
    }

    case 'remove': {
        $cartId = intval($_POST['cart_id'] ?? 0);

        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID giỏ hàng không hợp lệ']);
            break;
        }

        $result = removeFromCart($cartId, $userId);
        echo json_encode($result);
        break;
    }

    case 'clear': {
        $result = clearCart($userId);
        echo json_encode($result);
        break;
    }

    case 'list': {
        $items = getCartItems($userId);
        $count = getCartCount($userId);
        $total = getCartTotal($userId);

        echo json_encode([
            'success' => true,
            'items'   => $items,
            'count'   => $count,
            'total'   => $total,
        ]);
        break;
    }

    case 'count': {
        $count = getCartCount($userId);
        echo json_encode(['success' => true, 'count' => $count]);
        break;
    }

    case 'check': {
        $productId = intval($_POST['product_id'] ?? 0);

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'in_cart' => false]);
            break;
        }

        $inCart = isInCart($productId, $userId);
        echo json_encode(['success' => true, 'in_cart' => $inCart]);
        break;
    }

    case 'merge': {
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để gộp giỏ hàng']);
            break;
        }

        $result = mergeGuestCart($userId);
        echo json_encode($result);
        break;
    }

    case 'checkout': {
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
            'items'   => $items,
            'total'   => $total,
            'message' => 'Sẵn sàng thanh toán',
        ]);
        break;
    }

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        break;
}
