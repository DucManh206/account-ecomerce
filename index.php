<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/flash.php';

$search = trim($_GET['search'] ?? '');
$categoryId = max(0, (int) ($_GET['category'] ?? 0));
$minPrice = max(0, (int) ($_GET['min_price'] ?? 0));
$maxPrice = max(0, (int) ($_GET['max_price'] ?? 0));
if ($maxPrice > 0 && $minPrice > $maxPrice) {
    [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
}
$allowedSorts = ['newest', 'price_asc', 'price_desc', 'name'];
$requestedSort = $_GET['sort'] ?? 'newest';
$sort = in_array($requestedSort, $allowedSorts, true) ? $requestedSort : 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = app_setting_int('storefront_page_size', 12, 6, 48);

$categories = $pdo->query(
    "SELECT c.*, COALESCE(SUM(a.status = 'available' AND a.hidden = 0), 0) AS available_count
     FROM categories c LEFT JOIN accounts a ON a.category_id = c.id
     GROUP BY c.id ORDER BY c.name"
)->fetchAll();

$where = ['a.hidden = 0'];
$params = [];
if ($categoryId > 0) {
    $where[] = 'a.category_id = ?';
    $params[] = $categoryId;
}
if ($search !== '') {
    $where[] = '(a.name LIKE ? OR a.description LIKE ? OR c.name LIKE ?)';
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term);
}
if ($minPrice > 0) {
    $where[] = 'a.price >= ?';
    $params[] = $minPrice;
}
if ($maxPrice > 0) {
    $where[] = 'a.price <= ?';
    $params[] = $maxPrice;
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM accounts a LEFT JOIN categories c ON c.id = a.category_id WHERE ' . $whereSql);
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalProducts / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

$orderSql = match ($sort) {
    'price_asc' => 'a.price ASC, a.id DESC',
    'price_desc' => 'a.price DESC, a.id DESC',
    'name' => 'a.name ASC, a.id DESC',
    default => 'a.id DESC',
};
$stmt = $pdo->prepare(
    'SELECT a.*, c.name AS category_name
     FROM accounts a LEFT JOIN categories c ON c.id = a.category_id
     WHERE ' . $whereSql . ' ORDER BY ' . $orderSql . ' LIMIT ' . $limit . ' OFFSET ' . $offset
);
$stmt->execute($params);
$accounts = $stmt->fetchAll();

$catalogStats = $pdo->query(
    "SELECT SUM(status = 'available' AND hidden = 0) AS available,
            COUNT(DISTINCT CASE WHEN status = 'available' AND hidden = 0 THEN category_id END) AS categories
     FROM accounts"
)->fetch();

$selectedCategory = null;
foreach ($categories as $category) {
    if ((int) $category['id'] === $categoryId) {
        $selectedCategory = $category;
        break;
    }
}

$baseFilters = [
    'search' => $search,
    'category' => $categoryId ?: '',
    'min_price' => $minPrice ?: '',
    'max_price' => $maxPrice ?: '',
    'sort' => $sort,
];
$pageTitle = SITE_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main id="main-content">
    <?php if ($notice = trim((string) app_setting('storefront_notice', ''))): ?>
        <div class="store-notice"><div class="container"><span>Thông báo</span><p><?= htmlspecialchars($notice) ?></p></div></div>
    <?php endif; ?>

    <section class="hero storefront-hero">
        <div class="container hero-layout">
            <div class="hero-copy">
                <span class="hero-kicker"><?= htmlspecialchars((string) app_setting('site_tagline', 'Tài khoản số, giao ngay sau thanh toán')) ?></span>
                <h1><?= htmlspecialchars((string) app_setting('storefront_heading', 'Mua tài khoản Premium tự động')) ?></h1>
                <p><?= htmlspecialchars((string) app_setting('storefront_description', 'Chọn sản phẩm phù hợp, thanh toán bằng số dư và nhận thông tin đăng nhập ngay trong tài khoản.')) ?></p>
                <form action="index.php" method="GET" class="search-box storefront-search">
                    <label class="sr-only" for="store-search">Tìm sản phẩm</label>
                    <input id="store-search" type="search" name="search" placeholder="Tìm Netflix, Spotify, Steam..." value="<?= htmlspecialchars($search) ?>">
                    <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
                    <?php if ($minPrice): ?><input type="hidden" name="min_price" value="<?= $minPrice ?>"><?php endif; ?>
                    <?php if ($maxPrice): ?><input type="hidden" name="max_price" value="<?= $maxPrice ?>"><?php endif; ?>
                    <button type="submit">Tìm kiếm</button>
                </form>
            </div>
            <div class="hero-stats" aria-label="Thống kê cửa hàng">
                <div><strong><?= number_format($catalogStats['available']) ?></strong><span>sản phẩm sẵn sàng</span></div>
                <div><strong><?= number_format($catalogStats['categories']) ?></strong><span>danh mục đang bán</span></div>
                <div><strong>24/7</strong><span>giao thông tin tự động</span></div>
            </div>
        </div>
    </section>

    <section class="catalog-section">
        <div class="container">
            <?= render_flash() ?>

            <div class="catalog-header">
                <div>
                    <span class="section-kicker">Danh mục sản phẩm</span>
                    <h2><?= $selectedCategory ? htmlspecialchars($selectedCategory['name']) : 'Tất cả tài khoản' ?></h2>
                    <p><?= number_format($totalProducts) ?> kết quả<?= $search !== '' ? ' cho “' . htmlspecialchars($search) . '”' : '' ?></p>
                </div>
                <div class="catalog-sort">
                    <label for="sort">Sắp xếp</label>
                    <select id="sort" name="sort" form="catalogFilters" onchange="this.form.submit()">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới cập nhật</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá thấp trước</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá cao trước</option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Tên A–Z</option>
                    </select>
                </div>
            </div>

            <div class="catalog-layout">
                <aside class="catalog-filters">
                    <form action="index.php" method="GET" id="catalogFilters">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <fieldset>
                            <legend>Danh mục</legend>
                            <label class="filter-radio"><input type="radio" name="category" value="" <?= $categoryId === 0 ? 'checked' : '' ?> onchange="this.form.submit()"><span>Tất cả</span></label>
                            <?php foreach ($categories as $category): ?>
                                <label class="filter-radio"><input type="radio" name="category" value="<?= $category['id'] ?>" <?= $categoryId === (int) $category['id'] ? 'checked' : '' ?> onchange="this.form.submit()"><span><?= htmlspecialchars($category['name']) ?></span><small><?= number_format($category['available_count']) ?></small></label>
                            <?php endforeach; ?>
                        </fieldset>
                        <fieldset>
                            <legend>Khoảng giá</legend>
                            <div class="price-range-fields">
                                <label>Từ<input type="number" name="min_price" min="0" step="1000" value="<?= $minPrice ?: '' ?>" placeholder="0"></label>
                                <label>Đến<input type="number" name="max_price" min="0" step="1000" value="<?= $maxPrice ?: '' ?>" placeholder="Không giới hạn"></label>
                            </div>
                            <button type="submit" class="filter-apply">Áp dụng giá</button>
                        </fieldset>
                        <?php if ($search || $categoryId || $minPrice || $maxPrice): ?><a class="clear-filters" href="index.php">Xóa tất cả bộ lọc</a><?php endif; ?>
                    </form>
                </aside>

                <div class="catalog-results">
                    <div class="accounts-grid">
                        <?php foreach ($accounts as $account):
                            $image = product_image_url($account['image'] ?? '');
                            $inCart = cart_contains((int) $account['id']);
                        ?>
                            <article class="account-card <?= $account['status'] !== 'available' ? 'is-sold' : '' ?>">
                                <a href="chitiet.php?id=<?= $account['id'] ?>" class="card-image-wrapper" aria-label="Xem <?= htmlspecialchars($account['name']) ?>">
                                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($account['name']) ?>" loading="lazy" onerror="this.src='<?= htmlspecialchars(default_product_image()) ?>'; this.onerror=null;">
                                    <span class="card-badge"><?= htmlspecialchars($account['category_name'] ?? 'Chưa phân loại') ?></span>
                                    <span class="card-status <?= $account['status'] === 'available' ? 'status-available' : 'status-sold' ?>"><?= $account['status'] === 'available' ? 'Sẵn sàng' : 'Đã bán' ?></span>
                                </a>
                                <div class="card-body">
                                    <h3 class="card-title"><a href="chitiet.php?id=<?= $account['id'] ?>"><?= htmlspecialchars($account['name']) ?></a></h3>
                                    <p class="card-desc"><?= htmlspecialchars($account['description'] ?: 'Chưa có mô tả chi tiết.') ?></p>
                                    <div class="card-purchase-row">
                                        <strong><?= number_format($account['price'], 0, ',', '.') ?>đ</strong>
                                        <span>Giao ngay</span>
                                    </div>
                                    <div class="card-actions-wrapper">
                                        <a href="chitiet.php?id=<?= $account['id'] ?>" class="btn-view">Chi tiết</a>
                                        <?php if ($account['status'] === 'available'): ?>
                                            <?php if ($inCart): ?>
                                                <a href="cart.php" class="btn-add-cart is-added" id="btn-cart-<?= $account['id'] ?>">Trong giỏ</a>
                                            <?php else: ?>
                                                <button type="button" onclick="addToCart(<?= $account['id'] ?>, this)" class="btn-add-cart" id="btn-cart-<?= $account['id'] ?>">Thêm giỏ</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn-add-cart-disabled" disabled>Đã bán</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!$accounts): ?>
                        <div class="catalog-empty"><span>Không có kết quả</span><h3>Chưa tìm thấy tài khoản phù hợp</h3><p>Hãy thử từ khóa ngắn hơn hoặc bỏ bớt điều kiện giá.</p><a href="index.php" class="tab-btn">Xem toàn bộ cửa hàng</a></div>
                    <?php endif; ?>

                    <?php if ($totalPages > 1): ?>
                        <nav class="pagination" aria-label="Phân trang sản phẩm">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?<?= http_build_query(array_merge($baseFilters, ['page' => $i])) ?>" class="tab-btn <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
