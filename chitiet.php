<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/flash.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT accounts.*, categories.name AS category_name 
    FROM accounts 
    LEFT JOIN categories ON accounts.category_id = categories.id 
    WHERE accounts.id = ?
");
$stmt->execute([$id]);
$acc = $stmt->fetch();

if (!$acc) {
    set_flash('error', 'Tài khoản không tồn tại hoặc đã bị ẩn khỏi cửa hàng.');
}

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

$pageTitle = ($acc ? $acc['name'] : 'Chi tiết tài khoản') . ' - Account Shop';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

    <div class="container" style="min-height: 70vh;">
        <?= render_flash() ?>

        <?php if (!$acc): ?>
            <div style="text-align: center; margin: 60px 0;">
                <a href="index.php" class="tab-btn">&larr; Quay lại trang chủ</a>
            </div>
        <?php else: ?>
            
            <div class="detail-layout" style="margin-top: 40px; margin-bottom: 60px;">
                <main class="detail-main">
                    <div class="detail-img-container">
                        <?php 
                            $img = !empty($acc['image']) ? $acc['image'] : getFallbackImage($acc['category_name'] ?? '');
                        ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($acc['name']) ?>" onerror="this.src='assets/images/default-product.png'; this.onerror=null;">
                    </div>
                    
                    <h1 class="detail-title"><?= htmlspecialchars($acc['name']) ?></h1>
                    
                    <div class="detail-meta">
                        <div class="meta-item">Danh mục: <span><?= htmlspecialchars($acc['category_name'] ?? 'Chưa phân loại') ?></span></div>
                        <div class="meta-item">Trạng thái: 
                            <span style="color: <?= $acc['status'] === 'available' ? '#10b981' : '#ef4444' ?>">
                                <?= $acc['status'] === 'available' ? 'Đang bán' : 'Đã bán' ?>
                            </span>
                        </div>
                        <div class="meta-item">Ngày đăng: <span><?= date('d/m/Y', strtotime($acc['created_at'])) ?></span></div>
                    </div>

                    <div class="detail-content">
                        <h3>Mô tả tài khoản</h3>
                        <p><?= htmlspecialchars($acc['description'] ?? 'Không có mô tả cho sản phẩm này.') ?></p>
                        
                        <h3>Hướng dẫn & Bảo hành</h3>
                        <p style="color: var(--text-gray); font-size: 0.95rem; line-height: 1.6;">
                            1. Vui lòng đổi mật khẩu sau khi mua để tự bảo mật tài khoản.<br>
                            2. Đối với tài khoản dùng chung, vui lòng không đổi mật khẩu hoặc can thiệp cài đặt chung.<br>
                            3. Mọi vấn đề phát sinh vui lòng liên hệ Nhóm 5 để được hỗ trợ bảo hành.
                        </p>
                    </div>
                </main>
                
                <aside class="detail-sidebar">
                    <div class="purchase-card">
                        <div class="purchase-price-label">Giá bán chính thức</div>
                        <div class="purchase-price"><?= number_format($acc['price'], 0, ',', '.') ?>đ</div>
                        
                        <?php if ($acc['status'] === 'available'): ?>
                            <?php if (in_array($acc['id'], $_SESSION['cart'])): ?>
                                <a href="cart.php" class="btn-buy" id="btn-buy-detail" style="display: block; text-decoration: none; text-align: center; background: #059669; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);">
                                    Xem giỏ hàng
                                </a>
                            <?php else: ?>
                                <a href="javascript:void(0)" onclick="addToCart(<?= $acc['id'] ?>, this)" class="btn-buy" id="btn-buy-detail" style="display: block; text-decoration: none; text-align: center;">
                                    Thêm vào giỏ
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn-buy" disabled>Tài khoản đã bán</button>
                        <?php endif; ?>
                        
                        <div style="margin-top: 20px; font-size: 0.85rem; color: var(--text-muted); text-align: center;">
                            Hệ thống trừ số dư tự động.<br>Nhận acc ngay sau khi thanh toán giỏ hàng.
                        </div>
                    </div>
                    
                    <a href="index.php" class="tab-btn" style="text-align: center; text-decoration: none; display: block; border-radius: var(--radius-sm); margin-top: 16px;">
                        &larr; Quay lại danh sách
                    </a>
                </aside>
            </div>
            
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
