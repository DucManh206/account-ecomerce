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
$type_counts = ['all' => 0];
foreach ($all_products as $p) {
    $cat = $p['category'];
    if (!isset($cat_counts[$cat])) $cat_counts[$cat] = 0;
    $cat_counts[$cat]++;

    // Chỉ đếm các loại (types) thuộc danh mục đang chọn
    if ($category_filter === 'all' || $cat === $category_filter) {
        $type = $p['type_name'] ?? '';
        if (!empty($type)) {
            if (!isset($type_counts[$type])) $type_counts[$type] = 0;
            $type_counts[$type]++;
            $type_counts['all']++;
        }
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
        body { background: var(--bg-base); }
        
        /* 3-column layout */
        .store-layout {
            display: grid;
            grid-template-columns: 260px 1fr 300px;
            gap: 24px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        /* Left Sidebar - Categories */
        .sidebar-left {
            position: sticky;
            top: 80px;
            height: fit-content;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        .sidebar-card {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 16px;
        }
        .sidebar-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }
        .cat-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition-fast);
            margin-bottom: 4px;
        }
        .cat-item:hover {
            background: rgba(255,255,255,0.04);
            color: var(--text-primary);
        }
        .cat-item.active {
            background: var(--purple-dim);
            color: #A78BFA;
        }
        .cat-item i {
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }
        .cat-label {
            flex: 1;
        }
        .cat-count {
            font-size: 0.75rem;
            background: rgba(255,255,255,0.08);
            padding: 2px 8px;
            border-radius: 50px;
            font-weight: 600;
        }
        
        /* Hide mobile toggle on desktop */
        .cat-mobile-toggle {
            display: none;
        }

        /* Main Content */
        .main-content {
            min-width: 0;
        }
        /* Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, rgba(110,86,207,0.12) 0%, rgba(56,189,248,0.08) 100%);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 48px 40px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }
        .hero-glow {
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(110,86,207,0.25) 0%, transparent 70%);
            pointer-events: none;
            animation: pulse 8s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.1); }
        }
        .hero-content-wrap {
            position: relative;
            z-index: 1;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        .hero-badge i {
            color: #fbbf24;
        }
        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }
        .gradient-text {
            background: linear-gradient(135deg, #A78BFA 0%, #38BDF8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-desc {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.6;
            max-width: 680px;
            margin-bottom: 24px;
        }
        .hero-stats {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .hero-stat-item {
            text-align: center;
        }
        .hero-stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 4px;
        }
        .hero-stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .hero-stat-divider {
            width: 1px;
            height: 32px;
            background: var(--border-subtle);
        }

        /* Controls */
        .controls-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .search-box {
            position: relative;
            flex: 1;
            min-width: 200px;
        }
        .search-box input {
            width: 100%;
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: 50px;
            padding: 10px 16px 10px 42px;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: var(--transition-fast);
            outline: none;
        }
        .search-box input:focus { border-color: var(--accent); }
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
            padding: 10px 16px;
            color: var(--text-primary);
            font-size: 0.9rem;
            outline: none;
            cursor: pointer;
        }
        .sort-select:focus { border-color: var(--accent); }

        /* Type filter pills - compact */
        .type-filter {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .type-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: 50px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition-fast);
        }
        .type-pill:hover {
            background: rgba(255,255,255,0.04);
            color: var(--text-primary);
        }
        .type-pill.active {
            background: var(--purple-dim);
            color: #A78BFA;
            border-color: var(--purple);
        }

        /* Product count */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .product-count {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Right Sidebar - Stats & Promo */
        .sidebar-right {
            position: sticky;
            top: 80px;
            height: fit-content;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        .stat-mini {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .stat-mini-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .stat-mini-content {
            flex: 1;
            min-width: 0;
        }
        .stat-mini-value {
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }
        .stat-mini-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .promo-card {
            background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(6,182,212,0.08) 100%);
            border: 1px solid rgba(16,185,129,0.2);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 12px;
        }
        .promo-card h4 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--green);
        }
        .promo-card p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .store-layout {
                grid-template-columns: 240px 1fr;
            }
            .sidebar-right {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .store-layout {
                grid-template-columns: 1fr;
                gap: 16px;
                padding: 16px 12px;
            }
            .sidebar-left {
                position: static;
                max-height: none;
            }
            .sidebar-card {
                padding: 0;
                background: transparent;
                border: none;
            }
            .sidebar-title {
                display: none;
            }
            /* Mobile category dropdown */
            .cat-mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: var(--card-base);
                border: 1px solid var(--border-subtle);
                border-radius: var(--radius-md);
                padding: 12px 16px;
                cursor: pointer;
                margin-bottom: 12px;
            }
            .cat-mobile-toggle-text {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 0.9rem;
                font-weight: 600;
                color: var(--text-primary);
            }
            .cat-mobile-toggle i {
                color: var(--text-muted);
                transition: transform 0.2s;
            }
            .cat-mobile-list {
                display: none;
                background: var(--card-base);
                border: 1px solid var(--border-subtle);
                border-radius: var(--radius-md);
                padding: 8px;
                margin-bottom: 16px;
            }
            .cat-mobile-list.open {
                display: block;
            }
            .cat-mobile-toggle.open i {
                transform: rotate(180deg);
            }
            .hero-banner {
                padding: 32px 24px;
            }
            .hero-title {
                font-size: 1.75rem;
            }
            .hero-desc {
                font-size: 0.9rem;
            }
            .hero-stats {
                gap: 16px;
            }
            .hero-stat-value {
                font-size: 1.5rem;
            }
            .hero-stat-label {
                font-size: 0.7rem;
            }
            .controls-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php ui_renderNavbar($_SESSION['username'] ?? null, $cartCount['quantity'], $balance, 'store'); ?>

    <div class="store-layout">
        <!-- Left Sidebar: Categories -->
        <aside class="sidebar-left">
            <div class="sidebar-card">
                <!-- Mobile toggle -->
                <div class="cat-mobile-toggle" onclick="toggleCategoryMobile()">
                    <div class="cat-mobile-toggle-text">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Danh mục sản phẩm</span>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                
                <!-- Desktop title -->
                <div class="sidebar-title">Danh mục</div>
                
                <!-- Category list (wrapped for mobile) -->
                <div class="cat-mobile-list" id="catMobileList">
                    <a href="?category=all" class="cat-item <?php echo ($category_filter == 'all') ? 'active' : ''; ?>">
                        <i class="fa-solid fa-layer-group"></i>
                        <span class="cat-label">Tất cả</span>
                        <span class="cat-count"><?php echo $cat_counts['all']; ?></span>
                    </a>
                    <?php foreach ($cat_counts as $cat => $cnt): if ($cat === 'all') continue; ?>
                        <a href="?category=<?php echo urlencode($cat); ?>" class="cat-item <?php echo ($category_filter == $cat) ? 'active' : ''; ?>">
                            <i class="fa-solid fa-tag"></i>
                            <span class="cat-label"><?php echo htmlspecialchars($cat); ?></span>
                            <span class="cat-count"><?php echo $cnt; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Hero Banner -->
            <div class="hero-banner">
                <div class="hero-glow"></div>
                <div class="hero-content-wrap">
                    <div class="hero-badge">
                        <i class="fa-solid fa-sparkles"></i>
                        <span>Nền tảng mua bán tài khoản uy tín #1 Việt Nam</span>
                    </div>
                    <h1 class="hero-title">
                        Mua tài khoản <span class="gradient-text">chất lượng cao</span><br>
                        Giao dịch <span class="gradient-text">an toàn</span> & nhanh chóng
                    </h1>
                    <p class="hero-desc">
                        Hàng nghìn tài khoản Game, Netflix, Spotify, ChatGPT... được xác minh. 
                        Thanh toán qua ví điện tử, nhận hàng tức thì, bảo hành 7 ngày.
                    </p>
                    <div class="hero-stats">
                        <div class="hero-stat-item">
                            <div class="hero-stat-value"><?php echo $cat_counts['all']; ?>+</div>
                            <div class="hero-stat-label">Sản phẩm</div>
                        </div>
                        <div class="hero-stat-divider"></div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value">24/7</div>
                            <div class="hero-stat-label">Hỗ trợ</div>
                        </div>
                        <div class="hero-stat-divider"></div>
                        <div class="hero-stat-item">
                            <div class="hero-stat-value">100%</div>
                            <div class="hero-stat-label">Bảo mật</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <div class="controls-bar">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <select class="sort-select" id="sortSelect">
                    <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Mới nhất</option>
                    <option value="price_asc" <?php echo ($sort_by == 'price_asc') ? 'selected' : ''; ?>>Giá thấp → cao</option>
                    <option value="price_desc" <?php echo ($sort_by == 'price_desc') ? 'selected' : ''; ?>>Giá cao → thấp</option>
                    <option value="name" <?php echo ($sort_by == 'name') ? 'selected' : ''; ?>>Tên A → Z</option>
                </select>
            </div>

            <!-- Type Filter (only if category selected) -->
            <?php if ($category_filter !== 'all' && count($type_counts) > 1): ?>
                <div class="type-filter">
                    <a href="?category=<?php echo urlencode($category_filter); ?>&type=all" class="type-pill <?php echo ($type_filter == 'all') ? 'active' : ''; ?>">
                        Tất cả
                    </a>
                    <?php foreach ($type_counts as $type => $cnt): if ($type === 'all') continue; ?>
                        <a href="?category=<?php echo urlencode($category_filter); ?>&type=<?php echo urlencode($type); ?>" class="type-pill <?php echo ($type_filter == $type) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($type); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Section Header -->
            <div class="section-header">
                <div class="section-title">Sản phẩm</div>
                <div class="product-count"><?php echo $total_count; ?> kết quả</div>
            </div>

            <!-- Product Grid -->
            <div class="row g-3" id="productGrid">
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
                ?>
                        <div class="col-lg-4 col-md-6">
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
        </main>

        <!-- Right Sidebar: Stats & Promo -->
        <aside class="sidebar-right">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="background: rgba(110,86,207,0.15); color: #A78BFA;">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="stat-mini-content">
                    <div class="stat-mini-value"><?php echo $cat_counts['all']; ?></div>
                    <div class="stat-mini-label">Sản phẩm</div>
                </div>
            </div>

            <div class="stat-mini">
                <div class="stat-mini-icon" style="background: rgba(16,185,129,0.15); color: #34D399;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="stat-mini-content">
                    <div class="stat-mini-value">100%</div>
                    <div class="stat-mini-label">Bảo mật</div>
                </div>
            </div>

            <div class="stat-mini">
                <div class="stat-mini-icon" style="background: rgba(239,68,68,0.15); color: #F87171;">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div class="stat-mini-content">
                    <div class="stat-mini-value">24/7</div>
                    <div class="stat-mini-label">Hỗ trợ</div>
                </div>
            </div>

            <div class="promo-card">
                <h4><i class="fa-solid fa-gift"></i> Ưu đãi đặc biệt</h4>
                <p>Nạp tiền lần đầu nhận ngay 10% bonus vào tài khoản!</p>
            </div>
        </aside>
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

        // Mobile category toggle
        function toggleCategoryMobile() {
            const list = document.getElementById('catMobileList');
            const toggle = document.querySelector('.cat-mobile-toggle');
            list.classList.toggle('open');
            toggle.classList.toggle('open');
        }

        // Auto-open on desktop
        function checkMobileView() {
            const list = document.getElementById('catMobileList');
            if (window.innerWidth > 768) {
                list.classList.add('open');
            }
        }
        checkMobileView();
        window.addEventListener('resize', checkMobileView);
    </script>
</body>
</html>
