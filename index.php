<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/flash.php';

$search = trim($_GET['search'] ?? '');
$categoryId = trim($_GET['category'] ?? '');
$priceRange = trim($_GET['price_range'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

$whereClauses = ["accounts.hidden = 0"];
$params = [];

if ($categoryId !== '') {
    $whereClauses[] = "accounts.category_id = ?";
    $params[] = $categoryId;
}

if ($search !== '') {
    $whereClauses[] = "(accounts.name LIKE ? OR accounts.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($priceRange !== '') {
    if ($priceRange === 'under_100k') {
        $whereClauses[] = "accounts.price < 100000";
    } elseif ($priceRange === '100k_300k') {
        $whereClauses[] = "accounts.price BETWEEN 100000 AND 300000";
    } elseif ($priceRange === '300k_500k') {
        $whereClauses[] = "accounts.price BETWEEN 300000 AND 500000";
    } elseif ($priceRange === 'over_500k') {
        $whereClauses[] = "accounts.price > 500000";
    }
}

$whereSql = implode(" AND ", $whereClauses);

// Đếm tổng số sản phẩm để phân trang
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM accounts LEFT JOIN categories ON accounts.category_id = categories.id WHERE $whereSql");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

$sql = "SELECT accounts.*, categories.name AS category_name 
        FROM accounts 
        LEFT JOIN categories ON accounts.category_id = categories.id 
        WHERE $whereSql";

switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY accounts.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY accounts.price DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY accounts.id DESC";
        break;
}

$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accounts = $stmt->fetchAll();

// Lấy ảnh default nếu chưa up ảnh
function getFallbackImage($categoryName) {
    $categoryName = mb_strtolower($categoryName, 'UTF-8');
    if (strpos($categoryName, 'game') !== false || strpos($categoryName, 'lmht') !== false || strpos($categoryName, 'steam') !== false) {
        return 'https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=600&auto=format&fit=crop';
    } elseif (strpos($categoryName, 'streaming') !== false || strpos($categoryName, 'netflix') !== false || strpos($categoryName, 'spotify') !== false) {
        return 'https://images.unsplash.com/photo-1574375927938-d5a98e8edd86?q=80&w=600&auto=format&fit=crop';
    } elseif (strpos($categoryName, 'software') !== false || strpos($categoryName, 'office') !== false || strpos($categoryName, 'adobe') !== false) {
        return 'https://images.unsplash.com/photo-1618401471353-b98aedd07871?q=80&w=600&auto=format&fit=crop';
    }
    return 'assets/images/default-product.png';
}

$pageTitle = 'Account Shop - Hệ thống bán tài khoản tự động';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

    <section class="hero">
        <div class="container">
            <h1>Mua tài khoản <span>Premium</span> tự động</h1>
            <p>Chuyên cung cấp tài khoản Game, Netflix, Spotify, Key Office giá rẻ, uy tín. Nhận thông tin acc ngay lập tức.</p>
            
            <form action="index.php" method="GET" class="search-box">
                <input type="text" name="search" placeholder="Tìm tài khoản cần mua..." value="<?= htmlspecialchars($search) ?>">
                <?php if ($categoryId): ?>
                    <input type="hidden" name="category" value="<?= htmlspecialchars($categoryId) ?>">
                <?php endif; ?>
                <?php if ($priceRange): ?>
                    <input type="hidden" name="price_range" value="<?= htmlspecialchars($priceRange) ?>">
                <?php endif; ?>
                <?php if ($sort): ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <?php endif; ?>
                <button type="submit">Tìm kiếm</button>
            </form>
        </div>
    </section>

    <section class="search-filter-section">
        <div class="container">
            <div class="filter-wrapper">
                <div class="categories-tabs">
                    <a href="index.php?category=&search=<?= urlencode($search) ?>&price_range=<?= urlencode($priceRange) ?>&sort=<?= $sort ?>" 
                       class="tab-btn <?= $categoryId === '' ? 'active' : '' ?>">
                        Tất cả
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?category=<?= $cat['id'] ?>&search=<?= urlencode($search) ?>&price_range=<?= urlencode($priceRange) ?>&sort=<?= $sort ?>" 
                           class="tab-btn <?= (string)$categoryId === (string)$cat['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="sort-select" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <form action="index.php" method="GET" id="filterForm" style="display: flex; gap: 10px;">
                        <?php if ($categoryId): ?>
                            <input type="hidden" name="category" value="<?= htmlspecialchars($categoryId) ?>">
                        <?php endif; ?>
                        <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        
                        <select name="price_range" onchange="document.getElementById('filterForm').submit();">
                            <option value="" <?= $priceRange === '' ? 'selected' : '' ?>>Tất cả mức giá</option>
                            <option value="under_100k" <?= $priceRange === 'under_100k' ? 'selected' : '' ?>>Dưới 100.000đ</option>
                            <option value="100k_300k" <?= $priceRange === '100k_300k' ? 'selected' : '' ?>>100.000đ - 300.000đ</option>
                            <option value="300k_500k" <?= $priceRange === '300k_500k' ? 'selected' : '' ?>>300.000đ - 500.000đ</option>
                            <option value="over_500k" <?= $priceRange === 'over_500k' ? 'selected' : '' ?>>Trên 500.000đ</option>
                        </select>

                        <select name="sort" onchange="document.getElementById('filterForm').submit();">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá từ thấp đến cao</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá từ cao đến thấp</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <main class="container">
        <?= render_flash() ?>

        <div class="accounts-grid">
            <?php foreach ($accounts as $acc): 
                $img = !empty($acc['image']) ? $acc['image'] : getFallbackImage($acc['category_name'] ?? '');
            ?>
                <article class="account-card">
                    <div class="card-image-wrapper">
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($acc['name']) ?>" onerror="this.src='assets/images/default-product.png'; this.onerror=null;">
                        <span class="card-badge"><?= htmlspecialchars($acc['category_name'] ?? 'Chưa phân loại') ?></span>
                        <span class="card-status <?= $acc['status'] === 'available' ? 'status-available' : 'status-sold' ?>">
                            <?= $acc['status'] === 'available' ? 'Đang bán' : 'Đã bán' ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h2 class="card-title"><?= htmlspecialchars($acc['name']) ?></h2>
                        <p class="card-desc"><?= htmlspecialchars($acc['description'] ?? 'Chưa có mô tả chi tiết.') ?></p>
                        
                        <div style="font-size: 1.25rem; font-weight: 800; color: #10b981; margin-bottom: 12px;">
                            <?= number_format($acc['price'], 0, ',', '.') ?>đ
                        </div>
                        
                        <div class="card-actions-wrapper">
                            <a href="chitiet.php?id=<?= $acc['id'] ?>" class="btn-view" style="flex: 1; text-align: center;">Chi tiết</a>
                            
                            <?php if ($acc['status'] === 'available'): ?>
                                <?php if (in_array($acc['id'], $_SESSION['cart'])): ?>
                                    <a href="cart.php" class="btn-add-cart" id="btn-cart-<?= $acc['id'] ?>" style="background-color: #059669;">Đã thêm</a>
                                <?php else: ?>
                                    <a href="javascript:void(0)" onclick="addToCart(<?= $acc['id'] ?>, this)" class="btn-add-cart" id="btn-cart-<?= $acc['id'] ?>">+ Thêm giỏ</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn-add-cart-disabled" disabled>Đã bán</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (empty($accounts)): ?>
            <div class="empty" style="text-align: center; margin: 40px 0; color: var(--text-gray);">
                <p style="font-size: 1.2rem;">Không tìm thấy tài khoản phù hợp.</p>
                <a href="index.php" class="tab-btn" style="display: inline-block; margin-top: 16px;">Xem tất cả sản phẩm</a>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; gap: 8px; margin: 40px 0;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="index.php?category=<?= urlencode($categoryId) ?>&search=<?= urlencode($search) ?>&price_range=<?= urlencode($priceRange) ?>&sort=<?= urlencode($sort) ?>&page=<?= $i ?>" 
                       class="tab-btn <?= $page === $i ? 'active' : '' ?>" style="padding: 8px 16px;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
