<?php
/**
 * Gio hang API
 * Supports quantity (mua nhiều tài khoản cùng lúc)
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../crud/cart/cart_modules.php';
require_once __DIR__ . '/../../crud/users/user_modules.php';
require_once __DIR__ . '/../../crud/orders/order_modules.php';
require_once __DIR__ . '/../../config/db.php';

$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$username = $_SESSION['username'] ?? '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = max(1, intval($_POST['quantity'] ?? 1));

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
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
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
        $title = $product['title'];
        $totalPrice = $price * $quantity;
        mysqli_stmt_close($stmt);

        $balance = getBalance($username);
        if ($balance === false || $balance === null) $balance = 0;

        if ($balance < $totalPrice) {
            echo json_encode([
                'success' => false,
                'message' => 'Số dư không đủ. Cần ' . number_format($totalPrice) . 'đ, hiện có ' . number_format($balance) . 'đ',
                'balance' => $balance,
                'price' => $totalPrice,
            ]);
            break;
        }

        $accounts = [];
        mysqli_begin_transaction($conn);
        try {
            // Lấy N tài khoản khả dụng và lock trong transaction
            $stmtStock = mysqli_prepare($conn, "SELECT id, account_data FROM account_stock WHERE product_id = ? AND status = 'available' ORDER BY id ASC LIMIT ? FOR UPDATE");
            mysqli_stmt_bind_param($stmtStock, 'ii', $productId, $quantity);
            mysqli_stmt_execute($stmtStock);
            $stockRes = mysqli_stmt_get_result($stmtStock);
            $accounts = [];
            while ($stockRes && ($acc = mysqli_fetch_assoc($stockRes))) {
                $accounts[] = $acc;
            }
            mysqli_stmt_close($stmtStock);

            if (count($accounts) < $quantity) {
                throw new Exception('Chỉ còn ' . count($accounts) . '/' . $quantity . ' tài khoản khả dụng');
            }
            $orderIds = [];
            $soldAccs = [];
            $newBalance = $balance;

            foreach ($accounts as $account) {
                $accountId = intval($account['id']);
                $accountData = $account['account_data'];

                mysqli_query($conn, "UPDATE account_stock SET status='sold', sold_at=NOW() WHERE id=$accountId AND status='available'");

                $accountEsc = mysqli_real_escape_string($conn, $accountData);
                $sql = "INSERT INTO orders (user_id, product_id, account_id, price, account_data, status, created_at)
                        VALUES ($userId, $productId, $accountId, $price, '$accountEsc', 'completed', NOW())";
                if (!mysqli_query($conn, $sql)) throw new Exception('Lỗi tạo đơn hàng');
                $orderId = mysqli_insert_id($conn);
                $orderIds[] = $orderId;

                $soldAccs[] = [
                    'order_id' => $orderId,
                    'product' => $title,
                    'account' => json_decode($accountData, true),
                ];
            }

            $newBalance = $balance - $totalPrice;
            mysqli_query($conn, "UPDATE users SET balance=$newBalance WHERE id=$userId");

            $desc = 'Mua: ' . mysqli_real_escape_string($conn, $title) . ' x' . $quantity;
            mysqli_query($conn, "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
                         VALUES ($userId, -$totalPrice, $balance, $newBalance, 'purchase', '$desc', NOW())");

            mysqli_commit($conn);

            $_SESSION['last_checkout'] = [
                'order_ids'   => $orderIds,
                'new_balance' => $newBalance,
                'total_paid'  => $totalPrice,
                'accounts'    => $soldAccs,
                'completed_at' => date('Y-m-d H:i:s'),
            ];

            echo json_encode([
                'success' => true,
                'message' => "Mua $quantity tài khoản thành công!",
                'order_ids' => $orderIds,
                'redirect' => '../cart/success.php',
                'new_balance' => $newBalance,
                'total_paid' => $totalPrice,
                'accounts' => $soldAccs,
            ]);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            foreach ($accounts as $acc) {
                mysqli_query($conn, "UPDATE account_stock SET status='available', sold_at=NULL WHERE id=" . intval($acc['id']));
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        $cartId = intval($_POST['cart_id'] ?? 0);
        $quantity = max(1, intval($_POST['quantity'] ?? 1));

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

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
}
