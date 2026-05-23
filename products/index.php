<?php
session_start();
require_once __DIR__ . '/../lib/products_modules.php';
require_once __DIR__ . '/../lib/user_modules.php';
require_once __DIR__ . '/../lib/cart_modules.php';
require_once __DIR__ . '/../lib/ui_modules.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$cartCount = getCartCount($userId);
$balance = isset($_SESSION['username']) ? getBalance($_SESSION['username']) : 0;

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$all_products = getProducts();
$product = null;
foreach ($all_products as $p) {
    if (intval($p['id']) === $product_id) {
        $product = $p;
        break;
    }
}

if (!$product) {
    header('Location: /index.php');
    exit;
}

$detailsArr = [];
if ($product['details'] != "") {
    $decoded = json_decode($product['details'], true);
    if (is_array($decoded)) $detailsArr = $decoded;
}

$related = [];
foreach ($all_products as $p) {
    if (intval($p['id']) !== $product_id && $p['category'] === $product['category']) {
        $related[] = $p;
    }
}
$related = array_slice($related, 0, 4);

$priceStr = number_format($product['price'], 0, ',', '.') . 'đ';
$oldPriceStr = ($product['old_price'] > 0) ? number_format($product['old_price'], 0, ',', '.') . 'đ' : '';
$discount = ($product['old_price'] > 0 && $product['price'] > 0)
    ? round((1 - $product['price'] / $product['old_price']) * 100)
    : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead(htmlspecialchars($product['title'])); ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        .page { padding: 28px 0 80px; }
        /* Layout */
        .layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 48px;
            align-items: start;
        }
        @media (max-width: 1080px) {
            .layout { grid-template-columns: 1fr 360px; gap: 32px; }
        }
        @media (max-width: 992px) {
            .layout { grid-template-columns: 1fr; }
        }
        /* Cat Tag */
        .cat-tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 5px 12px;
            border-radius: 6px;
            margin-bottom: 18px;
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            background: var(--card-base);
        }
        /* Gallery */
        .gallery-container { margin-bottom: 24px; }
        .img-frame {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            aspect-ratio: 16/10;
        }
        .img-frame img { width: 100%; height: 100%; object-fit: cover; }
        .badge-vip {
            position: absolute;
            top: 16px; left: 16px;
            z-index: 2;
            background: var(--card-hover);
            border: 1px solid var(--border-subtle);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .badge-discount {
            position: absolute;
            top: 16px; right: 16px;
            z-index: 2;
            background: var(--text-primary);
            color: var(--bg-base);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 700;
        }
        .thumb-strip {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: none;
        }
        .thumb-strip::-webkit-scrollbar { display: none; }
        .thumb {
            width: 76px; height: 54px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--border-subtle);
            cursor: pointer;
            flex-shrink: 0;
            transition: border-color var(--transition-fast);
            background: var(--card-base);
        }
        .thumb:hover { border-color: var(--border-accent); }
        .thumb.active { border-color: var(--text-primary); }
        .thumb img { width: 100%; height: 100%; object-fit: cover; }
        /* Section Label */
        .sec-label {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 600;
            margin: 32px 0 16px;
        }
        .sec-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-subtle);
        }
        /* Specs Grid */
        .specs-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        @media (max-width: 600px) { .specs-grid { grid-template-columns: 1fr; } }
        .spec-chip {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .spec-icon {
            width: 32px; height: 32px;
            border-radius: 6px;
            background: var(--card-hover);
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        .spec-info { min-width: 0; }
        .spec-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 2px;
            font-weight: 500;
        }
        .spec-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Feature Grid */
        .feat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        @media (max-width: 768px) { .feat-grid { grid-template-columns: 1fr; } }
        .feat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 12px 14px;
        }
        .feat-icon {
            width: 32px; height: 32px;
            border-radius: 6px;
            background: var(--card-hover);
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        .feat-title { font-size: 0.8rem; font-weight: 600; color: var(--text-primary); }
        .feat-sub { font-size: 0.65rem; color: var(--text-muted); }
        /* Description */
        .desc-card {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 24px;
            line-height: 1.6;
        }
        .desc-text { font-size: 0.9rem; color: var(--text-secondary); }
        /* Product Panel */
        .product-panel { padding: 32px; }
        .panel-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .panel-cat {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            background: var(--bg-base);
        }
        .stock-pill {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .stock-pill::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--green);
        }
        .panel-title {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 6px;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }
        .panel-id {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
            letter-spacing: 0.5px;
            margin-bottom: 24px;
        }
        /* Price Box */
        .price-box {
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .price-original {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .price-original-label { font-size: 0.72rem; color: var(--text-muted); }
        .price-original-val { font-size: 0.95rem; color: var(--text-muted); text-decoration: line-through; }
        .price-tag {
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--green);
            line-height: 1;
            letter-spacing: -1px;
        }
        .price-savings {
            display: inline-block;
            margin-left: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            background: var(--red-dim);
            color: var(--red);
            padding: 3px 9px;
            border-radius: 6px;
            vertical-align: middle;
        }
        .price-note {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .price-note i { color: var(--green); }
        /* Quantity */
        .qty-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .qty-label { font-size: 0.8rem; color: var(--text-secondary); font-weight: 600; }
        /* Trust Strip */
        .trust-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 18px; }
        .trust-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-align: center;
            padding: 14px 8px;
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-subtle);
        }
        .trust-cell i { color: var(--text-primary); font-size: 1.1rem; }
        .trust-cell span {
            font-size: 0.65rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Detail Rows */
        .detail-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 10px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-subtle);
            font-size: 0.84rem;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-key {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .detail-key i { font-size: 0.7rem; }
        .detail-val { color: var(--text-primary); font-weight: 600; }
        /* Login Note */
        .login-note {
            margin-top: 14px;
            padding: 14px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        .login-note a { color: var(--purple); font-weight: 600; }
        /* Related */
        .related { margin-top: 60px; }
        .related-title {
            font-size: 1.15rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 24px;
            color: var(--text-primary);
        }
        .related-title i { color: var(--text-secondary); }
        .rel-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        @media (max-width: 1200px) { .rel-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .rel-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .rel-grid { grid-template-columns: 1fr; } }
        /* Animations */
        .anim-in {
            animation: nexus-fade-in 0.45s ease forwards;
            opacity: 0;
        }
        .fade-up {
            animation: nexus-fade-up 0.4s ease forwards;
            opacity: 0;
        }
        /* Mobile */
        @media (max-width: 768px) {
            .page { padding: 20px 0 60px; }
            .img-frame { border-radius: 16px; aspect-ratio: 16/11; }
            .product-panel { border-radius: 16px; padding: 20px; position: static !important; }
            .panel-title { font-size: 1.2rem; }
            .price-tag { font-size: 1.75rem; }
            .related { margin-top: 40px; }
        }
    </style>
</head>
<body>
    <?php ui_renderNavbar($_SESSION['username'] ?? null, $cartCount['quantity'], $balance, 'store'); ?>

    <div class="container">
        <div class="page">
            <?php ui_renderBreadcrumb([
                ['label' => 'Cửa hàng', 'url' => '../index.php'],
            ], $product['title']); ?>

            <div class="layout">
                <!-- LEFT -->
                <div>
                    <div class="cat-tag anim-in">
                        <i class="<?php echo getIconClass(htmlspecialchars($product['icon_class'])); ?>"></i>
                        <?php echo htmlspecialchars($product['category']); ?>
                        <?php if (!empty($product['type_name'])): ?>
                            <span class="badge" style="background:var(--border-subtle);color:var(--text-primary);font-size:0.65rem;padding:3px 8px;border-radius:4px;font-weight:600;border:1px solid var(--border-subtle);">
                                <i class="fa-solid <?php echo htmlspecialchars($product['type_icon'] ?? 'fa-tag'); ?> me-1"></i><?php echo htmlspecialchars($product['type_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="gallery-container anim-in">
                        <div class="img-frame" id="mainImage">
                            <?php if (!empty($product['badge'])): ?>
                                <span class="badge-vip"><i class="fa-solid fa-crown me-1"></i><?php echo htmlspecialchars($product['badge']); ?></span>
                            <?php endif; ?>
                            <?php if ($discount > 0): ?>
                                <span class="badge-discount">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" id="mainImg" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'" />
                        </div>
                        <div class="thumb-strip">
                            <div class="thumb active" onclick="changeImage(this, '<?php echo htmlspecialchars($product['image_url']); ?>')">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Thumb 1" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'" />
                            </div>
                            <?php
                            $thumbs = [];
                            if (!empty($product['gallery'])) {
                                $g = json_decode($product['gallery'], true);
                                if (is_array($g)) $thumbs = $g;
                            }
                            foreach ($thumbs as $i => $t): ?>
                                <div class="thumb" onclick="changeImage(this, '<?php echo htmlspecialchars($t); ?>')">
                                    <img src="<?php echo htmlspecialchars($t); ?>" alt="Thumb <?php echo $i + 2; ?>" />
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($product['description'])): ?>
                        <div class="sec-label">Mô tả</div>
                        <div class="desc-card anim-in">
                            <p class="desc-text"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (count($detailsArr) > 0): ?>
                        <div class="sec-label">Thông số kỹ thuật</div>
                        <div class="specs-grid">
                            <?php
                            $specIcons = [
                                'cpu' => 'fa-microchip', 'ram' => 'fa-memory', 'storage' => 'fa-database',
                                'gpu' => 'fa-desktop', 'battery' => 'fa-battery-full', 'display' => 'fa-desktop',
                                'os' => 'fa-brands fa-windows', 'network' => 'fa-wifi', 'weight' => 'fa-scale',
                                'size' => 'fa-ruler', 'color' => 'fa-palette', 'warranty' => 'fa-shield-halved',
                                'port' => 'fa-plug', 'camera' => 'fa-camera', 'audio' => 'fa-volume-high',
                            ];
                            $iconIdx = 0;
                            $defaultIcons = ['fa-server', 'fa-gear', 'fa-box', 'fa-layer-group', 'fa-cube', 'fa-puzzle-piece', 'fa-bolt', 'fa-star'];
                            foreach ($detailsArr as $key => $val):
                                $slug = strtolower(str_replace([' ', '-'], '', $key));
                                $icon = isset($specIcons[$slug]) ? $specIcons[$slug] : $defaultIcons[$iconIdx % count($defaultIcons)];
                                $iconIdx++;
                            ?>
                                <div class="spec-chip fade-up">
                                    <div class="spec-icon"><i class="fa-solid <?php echo $icon; ?>"></i></div>
                                    <div class="spec-info">
                                        <div class="spec-label"><?php echo htmlspecialchars($key); ?></div>
                                        <div class="spec-value"><?php echo htmlspecialchars($val); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="sec-label">Cam kết dịch vụ</div>
                    <div class="feat-grid">
                        <div class="feat-item anim-in">
                            <div class="feat-icon"><i class="fa-solid fa-shield-halved"></i></div>
                            <div>
                                <div class="feat-title">Bảo hành 7 ngày</div>
                                <div class="feat-sub">Đổi trả nếu lỗi</div>
                            </div>
                        </div>
                        <div class="feat-item anim-in">
                            <div class="feat-icon"><i class="fa-solid fa-bolt"></i></div>
                            <div>
                                <div class="feat-title">Giao tức thì</div>
                                <div class="feat-sub">Nhận tài khoản ngay</div>
                            </div>
                        </div>
                        <div class="feat-item anim-in">
                            <div class="feat-icon"><i class="fa-solid fa-headset"></i></div>
                            <div>
                                <div class="feat-title">Hỗ trợ 24/7</div>
                                <div class="feat-sub">Luôn sẵn sàng</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT Panel -->
                <div class="nx-panel product-panel anim-in" style="animation-delay:0.08s;">
                    <div class="panel-meta">
                        <span class="panel-cat <?php echo htmlspecialchars($product['color_class']); ?>">
                            <i class="<?php echo getIconClass(htmlspecialchars($product['icon_class'])); ?>"></i>
                            <?php echo htmlspecialchars($product['category']); ?>
                        </span>
                        <?php if (!empty($product['type_name'])): ?>
                            <span class="badge" style="background:var(--border-subtle);color:var(--text-primary);border:1px solid var(--border-subtle);padding:2px 8px;border-radius:4px;font-size:0.65rem;font-weight:600;">
                                <i class="fa-solid <?php echo htmlspecialchars($product['type_icon'] ?? 'fa-tag'); ?> me-1"></i><?php echo htmlspecialchars($product['type_name']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="stock-pill">Còn hàng</span>
                    </div>

                    <h1 class="panel-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                    <div class="panel-id">ID #<?php echo str_pad($product['id'], 5, '0', STR_PAD_LEFT); ?></div>

                    <div class="price-box">
                        <?php if ($oldPriceStr): ?>
                            <div class="price-original">
                                <span class="price-original-label">Giá gốc</span>
                                <span class="price-original-val"><?php echo $oldPriceStr; ?></span>
                                <?php if ($discount > 0): ?>
                                    <span style="background:var(--red);color:white;font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:4px;">-<?php echo $discount; ?>%</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <span class="price-tag"><?php echo $priceStr; ?></span>
                            <?php if ($discount > 0 && $product['old_price'] > 0): ?>
                                <span class="price-savings"><i class="fa-solid fa-tags me-1"></i>Tiết kiệm <?php echo number_format($product['old_price'] - $product['price'], 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <div class="price-note"><i class="fa-solid fa-check-circle"></i> Đã bao gồm phí chuyển giao</div>
                    </div>

                    <div class="qty-row">
                        <span class="qty-label">Số lượng</span>
                        <div class="nx-qty" style="gap:0;">
                            <button class="nx-qty-btn" onclick="changeQty(-1)"><i class="fa-solid fa-minus"></i></button>
                            <span class="nx-qty-val" id="qtyVal">1</span>
                            <button class="nx-qty-btn" onclick="changeQty(1)"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>

                    <a href="#" class="btn-nexus-primary" id="buyBtn" style="width:100%;justify-content:center;margin-bottom:10px;">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Mua ngay — <?php echo $priceStr; ?></span>
                    </a>
                    <button class="btn-nexus-secondary add-to-cart-btn" data-product-id="<?php echo $product_id; ?>" style="width:100%;justify-content:center;">
                        <i class="fa-solid fa-cart-plus"></i>
                        Thêm vào giỏ hàng
                    </button>

                    <?php if (!isset($_SESSION['username'])): ?>
                        <div class="login-note">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Cần <a href="../auth/login.php">đăng nhập</a> để mua hàng
                        </div>
                    <?php endif; ?>

                    <div class="nexus-divider" style="margin:18px 0;"></div>

                    <div class="detail-label">Chi tiết sản phẩm</div>
                    <div class="detail-row">
                        <span class="detail-key"><i class="fa-solid fa-layer-group"></i> Mã sản phẩm</span>
                        <span class="detail-val">#<?php echo str_pad($product['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key"><i class="fa-solid fa-folder"></i> Danh mục</span>
                        <span class="detail-val"><?php echo htmlspecialchars($product['category']); ?></span>
                    </div>
                    <?php if (!empty($product['type_name'])): ?>
                        <div class="detail-row">
                            <span class="detail-key"><i class="fa-solid fa-tag"></i> Loại</span>
                            <span class="detail-val" style="border:1px solid var(--border-subtle);padding:1px 6px;border-radius:4px;font-size:0.8rem;"><?php echo htmlspecialchars($product['type_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-key"><i class="fa-solid fa-circle-check"></i> Tình trạng</span>
                        <span class="detail-val" style="color:var(--green);">Còn hàng</span>
                    </div>
                    <?php foreach ($detailsArr as $key => $val): ?>
                        <div class="detail-row">
                            <span class="detail-key"><?php echo htmlspecialchars($key); ?></span>
                            <span class="detail-val"><?php echo htmlspecialchars($val); ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div class="trust-strip">
                        <div class="trust-cell"><i class="fa-solid fa-lock"></i><span>Bảo mật</span></div>
                        <div class="trust-cell"><i class="fa-solid fa-rotate-left"></i><span>Đổi trả</span></div>
                        <div class="trust-cell"><i class="fa-solid fa-rocket"></i><span>Tức thì</span></div>
                    </div>
                </div>
            </div>

            <!-- Related -->
            <?php if (count($related) > 0): ?>
                <div class="related">
                    <h2 class="related-title">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        Có thể bạn cũng thích
                    </h2>
                    <div class="rel-grid">
                        <?php foreach ($related as $r): ?>
                            <a href="index.php?id=<?php echo $r['id']; ?>" class="nx-rel-card">
                                <div class="nx-rel-img">
                                    <img src="<?php echo htmlspecialchars($r['image_url']); ?>" alt="" onerror="this.src='https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=600&q=80'">
                                </div>
                                <div class="nx-rel-body">
                                    <div class="nx-rel-category">
                                        <i class="<?php echo getIconClass(htmlspecialchars($r['icon_class'])); ?>"></i>
                                        <?php echo htmlspecialchars($r['category']); ?>
                                    </div>
                                    <div class="nx-rel-name"><?php echo htmlspecialchars($r['title']); ?></div>
                                    <div class="nx-rel-price"><?php echo number_format($r['price'], 0, ',', '.'); ?>đ</div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php ui_renderFooter(); ?>
    <?php ui_renderToastContainer(); ?>
    <?php ui_renderScripts(); ?>
    <script>
        let qty = 1;
        const price = <?php echo $product['price']; ?>;
        const priceFormatted = '<?php echo $priceStr; ?>';
        const productId = <?php echo $product_id; ?>;

        function changeQty(delta) {
            qty = Math.max(1, Math.min(99, qty + delta));
            document.getElementById('qtyVal').textContent = qty;
            const total = (price * qty).toLocaleString('vi-VN');
            document.getElementById('buyBtn').querySelector('span').textContent = 'Mua ngay — ' + total + 'đ';
        }

        function changeImage(el, src) {
            document.getElementById('mainImg').src = src;
            document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
        }

        document.getElementById('buyBtn').addEventListener('click', async function(e) {
            e.preventDefault();

            <?php if (!isset($_SESSION['username'])): ?>
                window.location.href = '../auth/login.php?redirect=../products/index.php?id=<?php echo $product_id; ?>';
                return;
            <?php endif; ?>

            const btn = e.currentTarget;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';

            try {
                const formData = new FormData();
                formData.append('action', 'buy_now');
                formData.append('product_id', productId);

                const response = await fetch('../api/cart.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    Cart.showToast('error', result.message);
                    if (result.redirect) {
                        setTimeout(() => { window.location.href = result.redirect; }, 500);
                    }
                }
            } catch (err) {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                Cart.showToast('error', 'Đã xảy ra lỗi!');
            }
        });
    </script>
</body>
</html>
