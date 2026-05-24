<?php
require_once __DIR__ . '/admin/config/db.php';

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
    $error = 'Tài khoản không tồn tại hoặc đã bị ẩn.';
}

$myBalance = 0;
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $balStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $balStmt->execute([$_SESSION['user_id']]);
    $myBalance = $balStmt->fetchColumn();
}
// trả về ảnh dự phòng 
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $acc ? htmlspecialchars($acc['name']) : 'Lỗi' ?> - Account Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .balance-indicator {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
        }
        .balance-indicator:hover {
            background-color: rgba(16, 185, 129, 0.2);
        }
        .cart-badge-indicator {
            position: relative;
            display: flex;
            align-items: center;
            color: var(--text-white);
            text-decoration: none;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        .cart-badge-indicator:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        .cart-count {
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 6px;
        }
        
        /* Toast notification */
        .toast-notification {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background-color: #10b981;
            color: #ffffff;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }
        .toast-notification.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast-notification.toast-error {
            background-color: #ef4444;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container navbar-content">
            <a href="index.php" class="logo">
                AccountShop
            </a>
            
            <div class="nav-links">
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <a href="admin/dashboard.php" class="btn-nav" style="border-color: #f59e0b; color: #f59e0b !important;">Quản trị viên</a>
                <?php endif; ?>
                <a href="index.php" class="nav-link">Trang chủ</a>
                <a href="topup.php" class="nav-link">Nạp tiền</a>
                <a href="cart.php" class="cart-badge-indicator">
                    <span>Giỏ hàng</span>
                    <span class="cart-count" id="cartCount"><?= count($_SESSION['cart']) ?></span>
                </a>
                
                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
                    <a href="profile.php" class="balance-indicator">
                        Số dư: <?= number_format($myBalance, 0, ',', '.') ?>đ
                    </a>
                    <a href="profile.php" class="nav-link" style="color: var(--text-white); font-weight: 600;">
                        Hi, <?= htmlspecialchars($_SESSION['user_fullname']) ?>
                    </a>
                    <a href="logout.php" class="btn-nav" style="background: var(--danger);">Đăng xuất</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Đăng nhập</a>
                    <a href="register.php" class="btn-nav">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($error) && !$acc): ?>
            <div class="frontend-alert frontend-alert-error" style="margin-top: 40px;">
                <?= htmlspecialchars($error) ?>
            </div>
            <div style="text-align: center; margin: 40px 0;">
                <a href="index.php" class="tab-btn">Quay lại trang chủ</a>
            </div>
        <?php else: ?>
            
            <div class="detail-layout">
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
                    
                    <a href="index.php" class="tab-btn" style="text-align: center; text-decoration: none; display: block; border-radius: var(--radius-sm);">
                        &larr; Quay lại danh sách
                    </a>
                </aside>
            </div>
            
        <?php endif; ?>
    </div>

    <footer>
        <div class="container footer-content">
            <p>&copy; Nhóm 5. Bài tập lớn Lập trình web và ứng dụng.</p>
        </div>
    </footer>

    <div id="toastNotification" class="toast-notification">Da them vao gio hang thanh cong!</div>

    <script>
    function addToCart(accountId, element) {
        fetch('cart.php?action=add&id=' + accountId + '&ajax=1')
            .then(res => res.json())
            .then(data => {
                const toast = document.getElementById('toastNotification');
                if (data.success) {
                    // Update header badge count
                    const cartCount = document.getElementById('cartCount');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Update button UI
                    element.textContent = 'Xem gio hang';
                    element.style.background = '#059669';
                    element.style.boxShadow = '0 4px 14px rgba(5, 150, 105, 0.3)';
                    element.onclick = function() {
                        window.location.href = 'cart.php';
                    };
                    
                    // Show toast
                    toast.textContent = 'Da them san pham vao gio hang!';
                    toast.classList.remove('toast-error');
                    toast.classList.add('show');
                    setTimeout(() => {
                        toast.classList.remove('show');
                    }, 2500);
                } else {
                    // Show error toast
                    toast.textContent = data.error || 'Co loi xay ra!';
                    toast.classList.add('toast-error');
                    toast.classList.add('show');
                    setTimeout(() => {
                        toast.classList.remove('show');
                    }, 2500);
                }
            })
            .catch(err => {
                alert('Loi ket noi server!');
            });
    }
    </script>
</body>
</html>
