<?php
/**
 * Products Modules - Quản lý sản phẩm
 * Tất cả truy vấn dùng prepared statements.
 */

require_once __DIR__ . '/../db_helpers/db_helpers.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../settings/settings_modules.php';

/* =====================================================================
   HELPERS
   ===================================================================== */
function getAvailableStock(int $productId): int {
    return crud_count(
        "SELECT COUNT(*) as cnt FROM account_stock WHERE product_id = ? AND status = 'available'",
        [$productId], 'i'
    );
}

/* =====================================================================
   READ
   ===================================================================== */
function getProducts(bool $includeOutOfStock = true): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return [];

    $stmt = mysqli_prepare($conn,
        "SELECT p.*, COALESCE(t.name, p.category) as type_name,
                COALESCE(t.icon_class, p.icon_class) as type_icon
         FROM products p
         LEFT JOIN types t ON p.type_id = t.id
         ORDER BY p.id DESC"
    );
    if (!$stmt) return [];

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) return [];

    $autoHide = getAutoHideOutOfStock();
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $stockQty = getAvailableStock($row['id']);
        $row['stock'] = $stockQty;
        $row['in_stock'] = $stockQty > 0;

        if ($autoHide && $stockQty <= 0) continue;
        if (!$includeOutOfStock && $stockQty <= 0) continue;

        $products[] = $row;
    }
    mysqli_free_result($result);
    return $products;
}

function getProductById(int $id): ?array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return null;

    $stmt = mysqli_prepare($conn,
        "SELECT p.*, COALESCE(t.name, p.category) as type_name,
                COALESCE(t.icon_class, p.icon_class) as type_icon
         FROM products p
         LEFT JOIN types t ON p.type_id = t.id
         WHERE p.id = ?"
    );
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) return null;
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);

    if (!$row) return null;
    $row['stock'] = getAvailableStock($row['id']);
    $row['in_stock'] = $row['stock'] > 0;
    return $row;
}

function getCategories(): array {
    return crud_select("SELECT * FROM categories ORDER BY id DESC");
}

function getCategoryById(int $id): ?array {
    return crud_select_one("SELECT * FROM categories WHERE id = ?", [$id], 'i');
}

function getTypes(): array {
    return crud_select("SELECT * FROM types ORDER BY id DESC");
}

function getTypeById(int $id): ?array {
    return crud_select_one("SELECT * FROM types WHERE id = ?", [$id], 'i');
}

/* =====================================================================
   STATS
   ===================================================================== */
function getProductStats(): array {
    $total    = crud_count("SELECT COUNT(*) as cnt FROM products");
    $withStock = crud_count(
        "SELECT COUNT(DISTINCT product_id) as cnt FROM account_stock WHERE status = 'available'"
    );
    $sold     = crud_count(
        "SELECT COUNT(*) as cnt FROM account_stock WHERE status = 'sold'"
    );
    return [
        'total'      => $total,
        'in_stock'   => $withStock,
        'sold'       => $sold,
    ];
}
