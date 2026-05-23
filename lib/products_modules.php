<?php
require_once __DIR__ . "/../database/connect.php";
require_once __DIR__ . '/settings_modules.php';

// Đếm số tài khoản có sẵn của một sản phẩm
function getAvailableStock($productId) {
    global $conn;
    
    if (!$conn) return 0;
    
    $productId = intval($productId);
    $sql = "SELECT COUNT(*) as cnt FROM account_stock WHERE product_id = ? AND status = 'available'";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) return 0;
    
    mysqli_stmt_bind_param($stmt, "i", $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return intval($row['cnt']);
    }
    
    return 0;
}

function getProducts($includeOutOfStock = true) {
    global $conn;
    $products = [];

    if (!$conn) {
        return $products;
    }

    $sql = "SELECT p.*, COALESCE(t.name, p.category) as type_name, COALESCE(t.icon_class, p.icon_class) as type_icon
            FROM products p
            LEFT JOIN types t ON p.type_id = t.id
            ORDER BY p.id DESC";
    $result = mysqli_query($conn, $sql);

    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            // Đếm số tài khoản có sẵn
            $stockQty = getAvailableStock($row['id']);
            $row['stock'] = $stockQty;
            $row['in_stock'] = ($stockQty > 0);
            
            // Auto-hide: nếu setting bật và hết hàng thì bỏ qua
            $autoHide = getAutoHideOutOfStock();
            if ($autoHide && $stockQty <= 0) {
                continue;
            }
            
            // Nếu không includeOutOfStock và hết hàng thì bỏ qua
            if (!$includeOutOfStock && $stockQty <= 0) {
                continue;
            }
            
            $products[] = $row;
        }
    }

    return $products;
}

function getCategories() {
    global $conn;
    $categories = [];

    if (!$conn) {
        return $categories;
    }

    $sql = "SELECT * FROM categories ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);

    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }

    return $categories;
}
