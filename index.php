<?php
session_start();
require_once __DIR__ . '/lib/products_modules.php';
require_once __DIR__ . '/lib/user_modules.php';
require_once __DIR__ . '/lib/cart_modules.php';
require_once __DIR__ . '/lib/ui_modules.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$cartCount = getCartCount($userId);
$balance = isset($_SESSION['username']) ? getBalance($_SESSION['username']) : 0;

$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$all_products = getProducts(false);

$cat_counts = ['all' => count($all_products)];
$type_counts = ['all' => count($all_products)];
foreach ($all_products as $p) {
    $cat = $p['category'];
    if (!isset($cat_counts[$cat])) $cat_counts[$cat] = 0;
    $cat_counts[$cat]++;

    $type = $p['type_name'] ?? '';
    if (!empty($type)) {
        if (!isset($type_counts[$type])) $type_counts[$type] = 0;
        $type_counts[$type]++;
    }
}

$products = [];
foreach ($all_products as $item) {
    $matchCat = ($category_filter == 'all' || $item['category'] == $category_filter);
    $matchType = ($type_filter == 'all' || ($item['type_name'] ?? '') == $type_filter);
    if ($matchCat && $matchType) {
        $products[] = $item;
    }
}

if ($search_query !== '') {
    $q = mb_strtolower($search_query, 'UTF-8');
    $products = array_filter($products, function ($p) use ($q) {
        return mb_strpos(mb_strtolower($p['title'] ?? '', 'UTF-8'), $q) !== false
            || mb_strpos(mb_strtolower($p['category'] ?? '', 'UTF-8'), $q) !== false
            || mb_strpos(mb_strtolower($p['type_name'] ?? '', 'UTF-8'), $q) !== false;
    });
    $products = array_values($products);
}

usort($products, function ($a, $b) use ($sort_by) {
    switch ($sort_by) {
        case 'price_asc':   return $a['price'] <=> $b['price'];
        case 'price_desc':  return $b['price'] <=> $a['price'];
        case 'name':        return strcmp($a['title'], $b['title']);
        default:            return $b['id'] <=> $a['id'];
    }
});

$total_count = count($products);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php $s = getStoreName(); ui_renderHead($s . ' | Chợ tài khoản chuyên nghiệp'); ?>
    <style>
        /* Hero Section */
        .hero-section {
            position: relative;
            padding: 80px 0;
            border-radius: var(--radius-lg);
            margin-top: 30px;
            overflow: hidden;
            background: url('https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=2071&auto=format&grayscale') center/cover;
            border: 1px solid var(--border-subtle);
        }
        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(9, 9, 11, 0.4);
            pointer-events: none;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, #09090b 0%, rgba(9, 9, 11, 0.7) 100%);
        }
        .hero-content { position: relative; z-index: 2; }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
        }
        .hero-title {
            font-size: 2.8rem;
            font-weight: 700;
            line-height: 1.15;
            margin: 16px 0;
            letter-spacing: -0.02em;
        }
        .hero-sub {
            color: var(--text-secondary);
            font-size: 1.05rem;
            max-width: 480px;
        }
        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: -40px;
            position: relative;
            z-index: 10;
        }
        .stat-card {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            transition: var(--transition-normal);
        }
        .stat-card:hover {
            border-color: var(--border-accent);
            transform: translateY(-4px);
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 12px;
        }
        .stat-value { font-size: 1.5rem; font-weight: 800; }
        .stat-label { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; }
        /* Controls Bar */
        .controls-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin: 40px 0 24px;
            justify-content: space-between;
        }
        .controls-left {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .search-box { position: relative; }
        .search-box input {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: 50px;
            padding: 9px 16px 9px 42px;
            color: var(--text-primary);
            font-size: 0.9rem;
            width: 260px;
            transition: var(--transition-fast);
            outline: none;
            font-family: var(--font-sans);
        }
        .search-box input:focus { border-color: var(--accent); }
        .search-box input::placeholder { color: var(--text-muted); }
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        .sort-select {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: 50px;
            padding: 9px 16px;
            color: var(--text-primary);
            font-size: 0.9rem;
            outline: none;
            transition: var(--transition-fast);
            cursor: pointer;
            font-family: var(--font-sans);
        }
        .sort-select:focus { border-color: var(--accent); }
        .filter-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: 50px;
            padding: 8px 20px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.88rem;
            transition: var(--transition-fast);
        }
        .filter-pill:hover {
            background: rgba(255, 255, 255, 0.04);
            color: white;
            border-color: rgba(255, 255, 255, 0.15);
        }
        .filter-pill.active {
            background: var(--purple-dim);
            color: #A78BFA;
            border-color: var(--purple);
        }
        .filter-count {
            font-size: 0.72rem;
            background: rgba(255, 255, 255, 0.08);
            padding: 2px 7px;
            border-radius: 50px;
        }
        .product-count-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 400;
        }
        /* Section header */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title { font-size: 2rem; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); margin-top: -20px; }
            .controls-bar { flex-direction: column; align-items: stretch; }
            .search-box input { width: 100%; }
            .filter-group { justify-content: center; }
        }
    </style>
</head>
<body>
    <?php ui_renderNavbar($_SESSION['username'] ?? null, $cartCount['quantity'], $balance, 'store'); ?>

    <div class="container pb-5">
        <!-- Hero Banner -->
        <div class="hero-section">
            <div class="hero-overlay"></div>
            <div class="hero-content px-5">
                <div class="row align-items-center">
                    <div class="col-lg-7 py-4">
                        <div class="hero-badge">
                            <i class="fa-solid fa-sparkles"></i> Chợ tài khoản chuyên nghiệp nhất VN
                        </div>
                        <h1 class="hero-title">
                            Sống trọn đam mê<br>
                            <span style="color: var(--text-primary);">Giao dịch an toàn.</span>
                        </h1>
                        <p class="hero-sub">Hàng trăm tài khoản chất lượng cao, giao dịch nhanh chóng qua ví tích hợp, bảo mật tuyệt đối 100%.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(110,86,207,0.15); color: #A78BFA;"><i class="fa-solid fa-layer-group"></i></div>
                <div class="stat-value"><?php echo $cat_counts['all']; ?></div>
                <div class="stat-label">Tổng sản phẩm</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(239,68,68,0.15); color: #F87171;"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="stat-value"><?php echo isset($cat_counts['Valorant']) ? $cat_counts['Valorant'] : 0; ?></div>
                <div class="stat-label">Valorant</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: #34D399;"><i class="fa-brands fa-steam"></i></div>
                <div class="stat-value"><?php echo isset($cat_counts['Steam']) ? $cat_counts['Steam'] : 0; ?></div>
                <div class="stat-label">Steam</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(56,189,248,0.15); color: #38BDF8;"><i class="fa-solid fa-users"></i></div>
                <div class="stat-value"><?php echo isset($cat_counts['Mạng xã hội']) ? $cat_counts['Mạng xã hội'] : 0; ?></div>
                <div class="stat-label">Social MXH</div>
            </div>
        </div>

        <!-- Controls Bar -->
        <div class="controls-bar">
            <div class="controls-left">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <select class="sort-select" id="sortSelect">
                    <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                    <option value="price_asc" <?php echo ($sort_by == 'price_asc') ? 'selected' : ''; ?>>Giá: Thấp → Cao</option>
                    <option value="price_desc" <?php echo ($sort_by == 'price_desc') ? 'selected' : ''; ?>>Giá: Cao → Thấp</option>
                    <option value="name" <?php echo ($sort_by == 'name') ? 'selected' : ''; ?>>Tên A → Z</option>
                </select>
            </div>
            <div class="filter-group">
                <a href="?category=all" class="filter-pill <?php echo ($category_filter == 'all') ? 'active' : ''; ?>">
                    Tất cả <span class="filter-count"><?php echo $cat_counts['all']; ?></span>
                </a>
                <?php foreach ($cat_counts as $cat => $cnt): if ($cat === 'all') continue; ?>
                    <a href="?category=<?php echo urlencode($cat); ?>" class="filter-pill <?php echo ($category_filter == $cat) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat); ?> <span class="filter-count"><?php echo $cnt; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (count($type_counts) > 1): ?>
                <div class="filter-group mt-2">
                    <span style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-right:4px;">Loại:</span>
                    <a href="?category=<?php echo urlencode($category_filter); ?>&type=all" class="filter-pill <?php echo ($type_filter == 'all') ? 'active' : ''; ?>" style="padding:6px 14px;font-size:0.8rem;">Tất cả</a>
                    <?php foreach ($type_counts as $type => $cnt): if ($type === 'all') continue; ?>
                        <a href="?category=<?php echo urlencode($category_filter); ?>&type=<?php echo urlencode($type); ?>" class="filter-pill <?php echo ($type_filter == $type) ? 'active' : ''; ?>" style="padding:6px 14px;font-size:0.8rem;">
                            <i class="fa-solid fa-tag me-1" style="font-size:0.7rem;"></i><?php echo htmlspecialchars($type); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Grid -->
        <div class="section-header">
            <div class="section-title">
                <i class="fa-solid fa-bolt text-warning"></i> Cửa Hàng
                <span class="product-count-label">— <?php echo $total_count; ?> sản phẩm</span>
            </div>
        </div>

        <div class="row g-4" id="productGrid">
            <?php
            if (count($products) > 0) {
                foreach ($products as $row) {
                    $detailsHTML = "";
                    if ($row['details'] != "") {
                        $detailsArr = json_decode($row['details'], true);
                        if (is_array($detailsArr)) {
                            foreach ($detailsArr as $key => $val) {
                                $detailsHTML .= "<li>{$key} <span>{$val}</span></li>";
                            }
                        }
                    }
                    $priceStr = number_format($row['price'], 0, ',', '.') . 'đ';
                    $badgeHTML = ($row['badge'] != "") ? "<span class='nx-card-badge'>{$row['badge']}</span>" : "";
            ?>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="nx-card">
                            <div class="nx-card-img">
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
                                <?php if ($row['badge'] != ""): ?>
                                <span class="nx-card-badge"><?php echo htmlspecialchars($row['badge']); ?></span>
                                <?php endif; ?>
                                <div class="nx-card-overlay">
                                    <a href="products/index.php?id=<?php echo $row['id']; ?>" class="nx-btn nx-btn-primary" style="flex:1;justify-content:center;">
                                        <i class="fa-solid fa-eye"></i> Xem chi tiết
                                    </a>
                                    <button class="nx-btn nx-btn-outline add-to-cart-btn" data-product-id="<?php echo $row['id']; ?>"><i class="fa-solid fa-cart-plus"></i></button>
                                </div>
                            </div>
                            <div class="nx-card-body">
                                <div class="nx-card-category">
                                    <i class="<?php echo getIconClass($row['icon_class']); ?>"></i>
                                    <?php echo htmlspecialchars($row['category']); ?>
                                    <?php if (!empty($row['type_name'])): ?>
                                        <span style="background:rgba(255,255,255,0.05);color:var(--text-muted);border:1px solid var(--border-subtle);font-size:0.62rem;font-weight:500;padding:2px 7px;border-radius:5px;margin-left:2px;">
                                            <?php echo htmlspecialchars($row['type_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h5 class="nx-card-title">
                                    <a href="products/index.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a>
                                </h5>
                                <?php if ($detailsHTML): ?>
                                <div class="nx-card-details"><ul><?php echo $detailsHTML; ?></ul></div>
                                <?php endif; ?>
                                <div class="nx-card-footer">
                                    <div class="nx-card-price-wrap">
                                        <?php if ($row['old_price'] > 0): ?>
                                        <div class="nx-card-price-old"><?php echo number_format($row['old_price'], 0, ',', '.'); ?>đ</div>
                                        <?php endif; ?>
                                        <div class="nx-card-price"><?php echo $priceStr; ?></div>
                                    </div>
                                    <?php if (!empty($row['in_stock'])): ?>
                                        <a href="products/index.php?id=<?php echo $row['id']; ?>" class="nx-btn nx-btn-primary nx-btn-sm">
                                            <i class="fa-solid fa-bolt"></i> Mua
                                        </a>
                                    <?php else: ?>
                                        <span class="nx-btn nx-btn-sm" style="opacity:0.4;cursor:not-allowed;background:rgba(255,255,255,0.05);color:var(--text-muted);">
                                            <i class="fa-solid fa-ban"></i> Hết hàng
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="col-12">
                    <div class="nx-empty">
                        <div class="nx-empty-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                        <div class="nx-empty-title">Không tìm thấy sản phẩm nào</div>
                        <div class="nx-empty-desc">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm khác nhé!</div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <?php ui_renderFooter(); ?>
    <?php ui_renderToastContainer(); ?>
    <?php ui_renderScripts(); ?>
    <script>
        let searchTimer;
        const searchInput = document.getElementById('searchInput');
        const sortSelect = document.getElementById('sortSelect');

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(updateURL, 400);
        });

        sortSelect.addEventListener('change', updateURL);

        function updateURL() {
            const params = new URLSearchParams(window.location.search);
            const q = searchInput.value.trim();
            const sort = sortSelect.value;
            const cat = params.get('category') || 'all';
            params.set('category', cat);
            if (q) params.set('q', q);
            else params.delete('q');
            if (sort !== 'newest') params.set('sort', sort);
            else params.delete('sort');
            window.location.search = params.toString();
        }
    </script>
</body>
</html>
