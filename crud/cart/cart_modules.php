<?php
/**
 * Cart Modules - Xử lý giỏ hàng
 * Mỗi sản phẩm trong giỏ = 1 tài khoản DUY NHẤT.
 * Tất cả truy vấn dùng prepared statements.
 */

require_once __DIR__ . '/../db_helpers/db_helpers.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../products/products_modules.php';

/* =====================================================================
   SESSION ID
   ===================================================================== */
function getCartSessionId(): string {
    if (!isset($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = session_id() . '_' . uniqid();
    }
    return $_SESSION['cart_session_id'];
}

/* =====================================================================
   HELPERS
   ===================================================================== */
function _cart_user_condition(?int $userId): array {
    if ($userId) {
        return ['cond' => 'c.user_id = ?', 'params' => [$userId], 'types' => 'i'];
    }
    $sessId = getCartSessionId();
    return ['cond' => 'c.session_id = ?', 'params' => [$sessId], 'types' => 's'];
}

/* =====================================================================
   READ
   ===================================================================== */
function getCartItems(?int $userId = null): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return [];

    $uc = _cart_user_condition($userId);
    $stmt = mysqli_prepare($conn,
        "SELECT c.id as cart_id, c.quantity,
                p.id as product_id, p.title, p.category, p.image_url,
                p.price, p.old_price, p.badge, p.color_class,
                p.icon_class, p.details,
                (p.price * c.quantity) as subtotal
         FROM cart c
         INNER JOIN products p ON c.product_id = p.id
         WHERE {$uc['cond']}
         ORDER BY c.created_at DESC"
    );
    if (!$stmt) return [];

    mysqli_stmt_bind_param($stmt, $uc['types'], ...$uc['params']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) return [];

    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stockQty = getAvailableStock($row['product_id']);
        $row['stock'] = $stockQty;
        $row['in_stock'] = $stockQty > 0;

        if ($stockQty <= 0) {
            crud_exec("DELETE FROM cart WHERE id = ?", [$row['cart_id']], 'i');
            continue;
        }

        if ($row['quantity'] > $stockQty) {
            $row['quantity'] = $stockQty;
            $row['subtotal'] = $row['price'] * $stockQty;
            crud_exec("UPDATE cart SET quantity = ? WHERE id = ?", [$stockQty, $row['cart_id']], 'ii');
        }

        $row['can_add_more'] = $row['quantity'] < $stockQty;
        $items[] = $row;
    }
    mysqli_free_result($result);
    return $items;
}

function getCartCount(?int $userId = null): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return ['items' => 0, 'quantity' => 0];

    $uc = _cart_user_condition($userId);
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) as cnt, COALESCE(SUM(quantity),0) as total_qty
         FROM cart c WHERE {$uc['cond']}"
    );
    if (!$stmt) return ['items' => 0, 'quantity' => 0];

    mysqli_stmt_bind_param($stmt, $uc['types'], ...$uc['params']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (!$res || !$row = mysqli_fetch_assoc($res)) {
        return ['items' => 0, 'quantity' => 0];
    }
    mysqli_free_result($res);
    return [
        'items' => intval($row['cnt']),
        'quantity' => intval($row['total_qty']),
    ];
}

function getCartTotal(?int $userId = null): int {
    $items = getCartItems($userId);
    $total = 0;
    foreach ($items as $item) {
        $total += intval($item['subtotal']);
    }
    return $total;
}

function isInCart(int $productId, ?int $userId = null): bool {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return false;

    $uc = _cart_user_condition($userId);
    $stmt = mysqli_prepare($conn,
        "SELECT id FROM cart WHERE product_id = ? AND {$uc['cond']} LIMIT 1"
    );
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, 'i' . $uc['types'], $productId, ...$uc['params']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (!$res) return false;
    $found = mysqli_num_rows($res) > 0;
    mysqli_free_result($res);
    return $found;
}

/* =====================================================================
   WRITE
   ===================================================================== */
function addToCart(int $productId, int $quantity = 1, ?int $userId = null): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];

    $productId = intval($productId);
    $quantity  = max(1, intval($quantity));
    $sessId    = getCartSessionId();

    $stmt = mysqli_prepare($conn, "SELECT id FROM products WHERE id = ?");
    if (!$stmt) return ['success' => false, 'message' => 'Lỗi database'];
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    if (!$res || mysqli_fetch_assoc($res) === null) {
        if ($res) mysqli_free_result($res);
        return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
    }
    mysqli_free_result($res);

    $availableStock = getAvailableStock($productId);
    if ($availableStock <= 0) {
        return ['success' => false, 'message' => 'Sản phẩm đã hết hàng'];
    }
    if ($quantity > $availableStock) {
        return ['success' => false, 'message' => 'Chỉ còn ' . $availableStock . ' tài khoản khả dụng'];
    }

    if ($userId) {
        $stmt = mysqli_prepare($conn,
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $sessId, $productId);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    if ($res && ($existing = mysqli_fetch_assoc($res)) !== null) {
        $newQty = intval($existing['quantity']) + $quantity;
        if ($newQty > $availableStock) {
            mysqli_free_result($res);
            return ['success' => false, 'message' => 'Trong kho chỉ còn ' . $availableStock . ' tài khoản. Giỏ hiện có ' . intval($existing['quantity']) . '.'];
        }
        $cartId = intval($existing['id']);
        mysqli_free_result($res);
        $stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $newQty, $cartId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok
            ? ['success' => true, 'message' => 'Đã cập nhật số lượng trong giỏ hàng', 'cart_count' => getCartCount($userId)]
            : ['success' => false, 'message' => 'Không thể cập nhật giỏ hàng'];
    }
    if ($res) mysqli_free_result($res);

    if ($userId) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iii', $userId, $productId, $quantity);
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sii', $sessId, $productId, $quantity);
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['success' => true, 'message' => 'Đã thêm vào giỏ hàng', 'cart_count' => getCartCount($userId)];
    }
    mysqli_stmt_close($stmt);
    return ['success' => false, 'message' => 'Không thể thêm vào giỏ hàng'];
}

function updateCartQuantity(int $cartId, int $quantity, ?int $userId = null): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];

    $cartId = intval($cartId);
    $quantity = max(1, intval($quantity));
    $uc = _cart_user_condition($userId);
    $stmt = mysqli_prepare($conn, "SELECT c.id, c.product_id FROM cart c WHERE c.id = ? AND {$uc['cond']} LIMIT 1");
    if (!$stmt) return ['success' => false, 'message' => 'Lỗi database'];
    mysqli_stmt_bind_param($stmt, 'i' . $uc['types'], $cartId, ...$uc['params']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if (!$row) return ['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ'];

    $availableStock = getAvailableStock(intval($row['product_id']));
    if ($availableStock <= 0) {
        removeFromCart($cartId, $userId);
        return ['success' => false, 'message' => 'Sản phẩm đã hết hàng và đã được xóa khỏi giỏ'];
    }
    if ($quantity > $availableStock) {
        $quantity = $availableStock;
    }

    $stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) return ['success' => false, 'message' => 'Lỗi database'];
    mysqli_stmt_bind_param($stmt, 'ii', $quantity, $cartId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok
        ? ['success' => true, 'message' => 'Đã cập nhật số lượng', 'quantity' => $quantity, 'cart_count' => getCartCount($userId), 'cart_total' => getCartTotal($userId)]
        : ['success' => false, 'message' => 'Không thể cập nhật số lượng'];
}

function removeFromCart(int $cartId, ?int $userId = null): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];

    $cartId = intval($cartId);
    $uc = _cart_user_condition($userId);
    $stmt = mysqli_prepare($conn,
        "DELETE FROM cart WHERE id = ? AND {$uc['cond']}"
    );
    if (!$stmt) return ['success' => false, 'message' => 'Lỗi database'];

    mysqli_stmt_bind_param($stmt, 'i' . $uc['types'], $cartId, ...$uc['params']);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok
        ? ['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng']
        : ['success' => false, 'message' => 'Không thể xóa sản phẩm'];
}

function clearCart(?int $userId = null): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];

    $uc = _cart_user_condition($userId);
    $cond = str_replace(['c.user_id', 'c.session_id'], ['user_id', 'session_id'], $uc['cond']);
    $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE {$cond}");
    if (!$stmt) return ['success' => false, 'message' => 'Lỗi database'];

    mysqli_stmt_bind_param($stmt, $uc['types'], ...$uc['params']);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok
        ? ['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng']
        : ['success' => false, 'message' => 'Không thể xóa giỏ hàng'];
}

function mergeGuestCart(int $userId): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return ['success' => false, 'message' => 'Lỗi kết nối database'];

    $sessId  = getCartSessionId();
    $userId  = intval($userId);

    $stmt = mysqli_prepare($conn,
        "SELECT product_id FROM cart WHERE session_id = ?"
    );
    if (!$stmt) return ['success' => false, 'message' => 'Lỗi database'];
    mysqli_stmt_bind_param($stmt, 's', $sessId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (!$res) return ['success' => false, 'message' => 'Lỗi truy vấn'];

    while ($item = mysqli_fetch_assoc($res)) {
        $pid = intval($item['product_id']);

        if (getAvailableStock($pid) <= 0) {
            $del = mysqli_prepare($conn,
                "DELETE FROM cart WHERE session_id = ? AND product_id = ?");
            mysqli_stmt_bind_param($del, 'si', $sessId, $pid);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);
            continue;
        }

        $ex = mysqli_prepare($conn,
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        mysqli_stmt_bind_param($ex, 'ii', $userId, $pid);
        mysqli_stmt_execute($ex);
        $exRes = mysqli_stmt_get_result($ex);
        mysqli_stmt_close($ex);

        if (!$exRes || mysqli_num_rows($exRes) === 0) {
            if ($exRes) mysqli_free_result($exRes);
            $ins = mysqli_prepare($conn,
                "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            mysqli_stmt_bind_param($ins, 'ii', $userId, $pid);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);
        } else {
            if ($exRes) mysqli_free_result($exRes);
        }
    }
    mysqli_free_result($res);

    crud_exec("DELETE FROM cart WHERE session_id = ?", [$sessId], 's');
    unset($_SESSION['cart_session_id']);

    return ['success' => true, 'message' => 'Đã gộp giỏ hàng'];
}

function transferGuestCartToUser(int $userId): array {
    return mergeGuestCart($userId);
}
