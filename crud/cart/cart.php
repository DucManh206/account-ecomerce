<?php
/**
 * Cart Modules - Xử lý giỏ hàng
 * Mỗi sản phẩm trong giỏ = 1 tài khoản DUY NHẤT
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../products/products.php';

// Lấy session ID cho guest users
function getCartSessionId() {
    if (!isset($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = session_id() . '_' . uniqid();
    }
    return $_SESSION['cart_session_id'];
}

/**
 * Lấy danh sách sản phẩm trong giỏ hàng
 */
function getCartItems($userId = null) {
    global $conn;
    
    if (!$conn) return [];
    
    $sessionId = getCartSessionId();
    $userCondition = $userId ? "c.user_id = " . intval($userId) : "c.session_id = '" . mysqli_real_escape_string($conn, $sessionId) . "'";
    
    $sql = "SELECT 
        c.id as cart_id,
        c.quantity,
        p.id as product_id,
        p.title,
        p.category,
        p.image_url,
        p.price,
        p.old_price,
        p.badge,
        p.color_class,
        p.icon_class,
        p.details,
        (p.price * c.quantity) as subtotal
    FROM cart c
    INNER JOIN products p ON c.product_id = p.id
    WHERE $userCondition
    ORDER BY c.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    $items = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Lấy số tài khoản có sẵn
        $stockQty = getAvailableStock($row['product_id']);
        $row['stock'] = $stockQty;
        $row['in_stock'] = ($stockQty > 0);
        
        // Nếu hết hàng thì xóa khỏi giỏ
        if ($stockQty <= 0) {
            mysqli_query($conn, "DELETE FROM cart WHERE id = " . $row['cart_id']);
            continue;
        }
        
        // Giới hạn số lượng = 1 (vì mỗi tài khoản là duy nhất)
        if ($row['quantity'] > 1) {
            $row['quantity'] = 1;
            $row['subtotal'] = $row['price'];
            mysqli_query($conn, "UPDATE cart SET quantity = 1 WHERE id = " . $row['cart_id']);
        }
        
        // Nếu đã có 1 trong giỏ rồi thì không cho thêm nữa
        if ($row['quantity'] >= 1) {
            $row['can_add_more'] = false;
        }
        
        $items[] = $row;
    }
    
    return $items;
}

/**
 * Đếm số sản phẩm trong giỏ hàng
 */
function getCartCount($userId = null) {
    global $conn;
    
    if (!$conn) return ['items' => 0, 'quantity' => 0];
    
    $sessionId = getCartSessionId();
    $userCondition = $userId ? "user_id = " . intval($userId) : "session_id = '" . mysqli_real_escape_string($conn, $sessionId) . "'";
    
    $sql = "SELECT COUNT(*) as count, SUM(quantity) as total_qty FROM cart WHERE $userCondition";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    return [
        'items' => intval($row['count']),
        'quantity' => intval($row['total_qty'])
    ];
}

/**
 * Tính tổng tiền giỏ hàng
 */
function getCartTotal($userId = null) {
    $items = getCartItems($userId);
    $total = 0;
    foreach ($items as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

/**
 * Thêm sản phẩm vào giỏ hàng
 */
function addToCart($productId, $quantity = 1, $userId = null) {
    global $conn;
    
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];
    
    $productId = intval($productId);
    $quantity = 1; // LUÔN = 1 vì mỗi tài khoản là duy nhất
    $sessionId = getCartSessionId();
    
    // Kiểm tra sản phẩm có tồn tại không
    $checkSql = "SELECT id FROM products WHERE id = $productId";
    $checkResult = mysqli_query($conn, $checkSql);
    if (!$checkResult || mysqli_fetch_assoc($checkResult) === null) {
        return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
    }
    
    // KIỂM TRA TỒN KHO
    $stockQty = getAvailableStock($productId);
    
    if ($stockQty <= 0) {
        return ['success' => false, 'message' => 'Sản phẩm đã hết hàng'];
    }
    
    // Kiểm tra đã có trong giỏ chưa
    if ($userId) {
        $checkCartSql = "SELECT id FROM cart WHERE user_id = $userId AND product_id = $productId";
    } else {
        $sessEsc = mysqli_real_escape_string($conn, $sessionId);
        $checkCartSql = "SELECT id FROM cart WHERE session_id = '$sessEsc' AND product_id = $productId";
    }
    
    $cartResult = mysqli_query($conn, $checkCartSql);
    
    if ($cartResult && mysqli_fetch_assoc($cartResult)) {
        return ['success' => false, 'message' => 'Sản phẩm đã có trong giỏ hàng'];
    }
    
    // Thêm mới vào giỏ
    if ($userId) {
        $insertSql = "INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, $productId, 1)";
    } else {
        $sessEsc = mysqli_real_escape_string($conn, $sessionId);
        $insertSql = "INSERT INTO cart (session_id, product_id, quantity) VALUES ('$sessEsc', $productId, 1)";
    }
    
    if (mysqli_query($conn, $insertSql)) {
        return ['success' => true, 'message' => 'Đã thêm vào giỏ hàng', 'cart_count' => getCartCount($userId)];
    }
    
    return ['success' => false, 'message' => 'Không thể thêm vào giỏ hàng'];
}

/**
 * Cập nhật số lượng (luôn = 1)
 */
function updateCartQuantity($cartId, $quantity, $userId = null) {
    // Không cho phép thay đổi số lượng > 1
    return ['success' => true, 'message' => 'Số lượng luôn là 1'];
}

/**
 * Xóa sản phẩm khỏi giỏ hàng
 */
function removeFromCart($cartId, $userId = null) {
    global $conn;
    
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];
    
    $cartId = intval($cartId);
    $userCondition = $userId ? "user_id = " . intval($userId) : "session_id = '" . mysqli_real_escape_string($conn, getCartSessionId()) . "'";
    
    $sql = "DELETE FROM cart WHERE id = $cartId AND $userCondition";
    
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng'];
    }
    
    return ['success' => false, 'message' => 'Không thể xóa sản phẩm'];
}

/**
 * Xóa toàn bộ giỏ hàng
 */
function clearCart($userId = null) {
    global $conn;
    
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];
    
    if ($userId) {
        $sql = "DELETE FROM cart WHERE user_id = " . intval($userId);
    } else {
        $sessEsc = mysqli_real_escape_string($conn, getCartSessionId());
        $sql = "DELETE FROM cart WHERE session_id = '$sessEsc'";
    }
    
    if (mysqli_query($conn, $sql)) {
        return ['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng'];
    }
    
    return ['success' => false, 'message' => 'Không thể xóa giỏ hàng'];
}

/**
 * Merge guest cart vào user cart
 */
function mergeGuestCart($userId) {
    global $conn;
    
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];
    
    $sessionId = getCartSessionId();
    $userId = intval($userId);
    
    $sessEsc = mysqli_real_escape_string($conn, $sessionId);
    $guestItems = mysqli_query($conn, "SELECT product_id FROM cart WHERE session_id = '$sessEsc'");
    
    while ($item = mysqli_fetch_assoc($guestItems)) {
        // Kiểm tra tồn kho
        if (getAvailableStock($item['product_id']) <= 0) {
            mysqli_query($conn, "DELETE FROM cart WHERE session_id = '$sessEsc' AND product_id = {$item['product_id']}");
            continue;
        }
        
        // Kiểm tra đã có trong user cart chưa
        $existing = mysqli_query($conn, "SELECT id FROM cart WHERE user_id = $userId AND product_id = {$item['product_id']}");
        
        if (!mysqli_fetch_assoc($existing)) {
            mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, {$item['product_id']}, 1)");
        }
    }
    
    mysqli_query($conn, "DELETE FROM cart WHERE session_id = '$sessEsc'");
    unset($_SESSION['cart_session_id']);
    
    return ['success' => true, 'message' => 'Đã gộp giỏ hàng'];
}

/**
 * Kiểm tra sản phẩm có trong giỏ không
 */
function isInCart($productId, $userId = null) {
    global $conn;
    
    if (!$conn) return false;
    
    $productId = intval($productId);
    $userCondition = $userId ? "user_id = " . intval($userId) : "session_id = '" . mysqli_real_escape_string($conn, getCartSessionId()) . "'";
    
    $sql = "SELECT id FROM cart WHERE product_id = $productId AND $userCondition LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Chuyển guest cart thành user cart
 */
function transferGuestCartToUser($userId) {
    return mergeGuestCart($userId);
}
