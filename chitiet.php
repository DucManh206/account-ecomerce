<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT accounts.*, categories.name AS category_name
     FROM accounts
     LEFT JOIN categories ON accounts.category_id = categories.id
     WHERE accounts.id = ?'
);
$stmt->execute([$id]);
$acc = $stmt->fetch();

if ($acc && (int) $acc['hidden'] === 1 && !is_admin_logged_in()) {
    $acc = false;
}

if (!$acc) {
    set_flash('error', 'Tài khoản không tồn tại hoặc đã bị ẩn khỏi cửa hàng.');
}

$relatedAccounts = [];
if ($acc && !empty($acc['category_id'])) {
    $relatedStmt = $pdo->prepare(
        "SELECT a.*, c.name AS category_name
         FROM accounts a
         LEFT JOIN categories c ON c.id = a.category_id
         WHERE a.category_id = ? AND a.id <> ? AND a.hidden = 0 AND a.status = 'available'
         ORDER BY a.id DESC
         LIMIT 4"
    );
    $relatedStmt->execute([$acc['category_id'], $acc['id']]);
    $relatedAccounts = $relatedStmt->fetchAll();
}

$inCart = $acc ? cart_contains((int) $acc['id']) : false;
$isAvailable = $acc && $acc['status'] === 'available' && (int) $acc['hidden'] === 0;

$pageTitle = ($acc ? $acc['name'] : 'Chi tiết tài khoản') . ' - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main id="main-content" class="container" style="min-height: 70vh;">
    <?= render_flash() ?>

    <?php if (!$acc): ?>
        <div style="text-align: center; margin: 60px 0;">
            <a href="index.php" class="tab-btn">&larr; Quay lại trang chủ</a>
        </div>
    <?php else: ?>
        <div class="detail-layout">
            <section class="detail-main">
                <div class="detail-img-container">
                    <img src="<?= htmlspecialchars(product_image_url($acc['image'] ?? '')) ?>" alt="<?= htmlspecialchars($acc['name']) ?>" onerror="this.src='<?= htmlspecialchars(default_product_image()) ?>'; this.onerror=null;">
                </div>

                <h1 class="detail-title"><?= htmlspecialchars($acc['name']) ?></h1>

                <div class="detail-meta">
                    <div class="meta-item">Danh mục: <span><?= htmlspecialchars($acc['category_name'] ?? 'Chưa phân loại') ?></span></div>
                    <div class="meta-item">Trạng thái:
                        <span style="color: <?= $isAvailable ? '#10b981' : '#ef4444' ?>">
                            <?= $isAvailable ? 'Đang bán' : 'Đã bán' ?>
                        </span>
                    </div>
                    <div class="meta-item">Ngày đăng: <span><?= date('d/m/Y', strtotime($acc['created_at'])) ?></span></div>
                </div>

                <div class="detail-content">
                    <h3>Mô tả tài khoản</h3>
                    <p><?= htmlspecialchars($acc['description'] ?? 'Không có mô tả cho sản phẩm này.') ?></p>

                    <h3>Hướng dẫn sau khi mua</h3>
                    <p style="color: var(--text-gray); font-size: 0.95rem; line-height: 1.6;">
                        1. Đổi mật khẩu ngay sau khi nhận thông tin đăng nhập.<br>
                        2. Không chia sẻ thông tin tài khoản đã mua.<br>
                        3. Mọi vấn đề phát sinh vui lòng liên hệ hỗ trợ<?php if (app_setting('support_contact', '') !== ''): ?>: <?= htmlspecialchars((string) app_setting('support_contact')) ?><?php endif; ?>.
                    </p>
                </div>
            </section>

            <aside class="detail-sidebar">
                <div class="purchase-card">
                    <div class="purchase-price-label">Giá bán chính thức</div>
                    <div class="purchase-price"><?= number_format($acc['price'], 0, ',', '.') ?>đ</div>

                    <?php if ($isAvailable): ?>
                        <?php if ($inCart): ?>
                            <a href="cart.php" class="btn-buy" style="display: block; text-decoration: none; text-align: center; background: #059669; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);">
                                Thanh toán giỏ hàng
                            </a>
                        <?php else: ?>
                            <form method="POST" action="cart.php" class="buy-now-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="cart_action" value="add">
                                <input type="hidden" name="id" value="<?= (int) $acc['id'] ?>">
                                <input type="hidden" name="buy_now" value="1">
                                <button type="submit" class="btn-buy">Mua ngay</button>
                            </form>
                            <button type="button" onclick="addToCart(<?= (int) $acc['id'] ?>, this)" class="btn-view detail-add-cart">
                                Thêm vào giỏ
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn-buy" disabled>Tài khoản đã bán</button>
                    <?php endif; ?>

                    <div style="margin-top: 20px; font-size: 0.85rem; color: var(--text-muted); text-align: center;">
                        Thanh toán bằng số dư.<br>Nhận thông tin đăng nhập ngay trong hồ sơ.
                    </div>
                </div>

                <a href="index.php<?= $acc['category_id'] ? ('?category=' . (int) $acc['category_id']) : '' ?>" class="tab-btn" style="text-align: center; text-decoration: none; display: block;">
                    &larr; Quay lại danh sách
                </a>
            </aside>
        </div>

        <?php if ($relatedAccounts): ?>
            <section class="related-section">
                <div class="profile-section-heading">
                    <div>
                        <span class="section-kicker">Cùng danh mục</span>
                        <h2>Tài khoản tương tự</h2>
                    </div>
                </div>
                <div class="accounts-grid related-grid">
                    <?php foreach ($relatedAccounts as $related): ?>
                        <article class="account-card">
                            <a href="chitiet.php?id=<?= (int) $related['id'] ?>" class="card-image-wrapper">
                                <img src="<?= htmlspecialchars(product_image_url($related['image'] ?? '')) ?>" alt="<?= htmlspecialchars($related['name']) ?>" loading="lazy" onerror="this.src='<?= htmlspecialchars(default_product_image()) ?>'; this.onerror=null;">
                                <span class="card-badge"><?= htmlspecialchars($related['category_name'] ?? 'Chưa phân loại') ?></span>
                            </a>
                            <div class="card-body">
                                <h3 class="card-title"><a href="chitiet.php?id=<?= (int) $related['id'] ?>"><?= htmlspecialchars($related['name']) ?></a></h3>
                                <div class="card-purchase-row">
                                    <strong><?= number_format($related['price'], 0, ',', '.') ?>đ</strong>
                                    <span>Giao ngay</span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
