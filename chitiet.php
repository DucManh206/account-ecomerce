<?php
// ============================================================
// Chi tiết sản phẩm & Mua tài khoản (Frontend)
// ============================================================
require_once __DIR__ . '/admin/config/db.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin tài khoản
$stmt = $pdo->prepare("
    SELECT accounts.*, categories.name AS category_name 
    FROM accounts 
    LEFT JOIN categories ON accounts.category_id = categories.id 
    WHERE accounts.id = ?
");
$stmt->execute([$id]);
$acc = $stmt->fetch();

if (!$acc) {
    $error = 'Sản phẩm không tồn tại hoặc đã bị gỡ bỏ.';
}

$buySuccess = false;
$credentials = '';

// Xử lý khi nhấn Mua ngay
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_buy']) && $acc && $acc['status'] === 'available') {
    // Cập nhật trạng thái thành đã bán
    $updateStmt = $pdo->prepare("UPDATE accounts SET status = 'sold' WHERE id = ?");
    if ($updateStmt->execute([$id])) {
        $buySuccess = true;
        $credentials = $acc['account_detail'];
        // Cập nhật lại thông tin tài khoản để hiển thị trạng thái mới
        $acc['status'] = 'sold';
    } else {
        $error = 'Không thể thực hiện giao dịch. Vui lòng thử lại sau.';
    }
}

// Hàm lấy ảnh đại diện mặc định theo loại tài khoản
function getFallbackImage($categoryName) {
    $categoryName = mb_strtolower($categoryName, 'UTF-8');
    if (strpos($categoryName, 'game') !== false || strpos($categoryName, 'lmht') !== false || strpos($categoryName, 'steam') !== false) {
        return 'https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=600&auto=format&fit=crop';
    } elseif (strpos($categoryName, 'streaming') !== false || strpos($categoryName, 'netflix') !== false || strpos($categoryName, 'spotify') !== false) {
        return 'https://images.unsplash.com/photo-1574375927938-d5a98e8edd86?q=80&w=600&auto=format&fit=crop';
    } elseif (strpos($categoryName, 'software') !== false || strpos($categoryName, 'office') !== false || strpos($categoryName, 'adobe') !== false) {
        return 'https://images.unsplash.com/photo-1618401471353-b98aedd07871?q=80&w=600&auto=format&fit=crop';
    }
    return 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=600&auto=format&fit=crop';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $acc ? htmlspecialchars($acc['name']) : 'Lỗi' ?> - Account Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        function copyCredentials() {
            var copyText = document.getElementById("credentialsBox");
            navigator.clipboard.writeText(copyText.innerText).then(function() {
                var btn = document.getElementById("copyBtn");
                btn.innerText = "Đã sao chép! ✓";
                btn.style.background = "#10b981";
                setTimeout(function() {
                    btn.innerText = "Sao chép thông tin";
                    btn.style.background = "rgba(255, 255, 255, 0.08)";
                }, 2000);
            }, function(err) {
                alert("Không thể tự động sao chép. Vui lòng chọn và sao chép thủ công.");
            });
        }
    </script>
</head>
<body>

    <!-- Navigation Bar -->
    <header class="navbar">
        <div class="container navbar-content">
            <a href="index.php" class="logo">
                <span>&#x1F511;</span> AccountShop
            </a>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Trang chủ</a>
                <a href="admin/dashboard.php" class="btn-nav" target="_blank">Khu vực Admin</a>
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
                <!-- Main detail column -->
                <main class="detail-main">
                    <div class="detail-img-container">
                        <?php 
                            $img = !empty($acc['image']) ? $acc['image'] : getFallbackImage($acc['category_name'] ?? '');
                        ?>
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($acc['name']) ?>">
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
                    
                    <!-- Form hiển thị thông tin tài khoản đã mua thành công -->
                    <?php if ($buySuccess): ?>
                        <div class="purchase-success-panel">
                            <div class="success-header">
                                <span style="font-size: 1.5rem;">&#x2705;</span>
                                <h4>Mua tài khoản thành công!</h4>
                            </div>
                            <p style="color: var(--text-gray); margin-bottom: 12px; font-size: 0.95rem;">
                                Dưới đây là thông tin chi tiết đăng nhập của tài khoản bạn đã mua. Vui lòng bảo mật thông tin này:
                            </p>
                            <div id="credentialsBox" class="account-credentials-box"><?= htmlspecialchars($credentials) ?></div>
                            <button id="copyBtn" onclick="copyCredentials()" class="btn-copy">Sao chép thông tin</button>
                        </div>
                    <?php endif; ?>

                    <div class="detail-content">
                        <h3>Mô tả chi tiết sản phẩm</h3>
                        <p><?= htmlspecialchars($acc['description'] ?? 'Không có mô tả cho sản phẩm này.') ?></p>
                        
                        <h3>Hướng dẫn sử dụng & Bảo hành</h3>
                        <p style="color: var(--text-gray); font-size: 0.95rem; line-height: 1.6;">
                            1. Vui lòng đổi mật khẩu và liên kết thông tin cá nhân ngay sau khi nhận tài khoản nếu loại tài khoản hỗ trợ đổi thông tin.<br>
                            2. Đối với tài khoản dùng chung (Netflix, Spotify gia đình...), vui lòng không tự ý thay đổi mật khẩu hoặc cài đặt chung của tài khoản.<br>
                            3. Mọi vấn đề phát sinh trong quá trình sử dụng vui lòng liên hệ Ban Quản Trị Nhóm 5 để được hỗ trợ bảo hành theo quy định.
                        </p>
                    </div>
                </main>
                
                <!-- Purchase sidebar column -->
                <aside class="detail-sidebar">
                    <div class="purchase-card">
                        <div class="purchase-price-label">Giá bán chính thức</div>
                        <div class="purchase-price"><?= number_format($acc['price'], 0, ',', '.') ?>đ</div>
                        
                        <?php if (isset($error) && $acc): ?>
                            <div class="frontend-alert frontend-alert-error" style="margin-bottom: 16px; font-size: 0.85rem; padding: 8px 12px;">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="action_buy" value="1">
                            <?php if ($acc['status'] === 'available'): ?>
                                <button type="submit" class="btn-buy">Mua ngay</button>
                            <?php else: ?>
                                <button type="button" class="btn-buy" disabled>Tài khoản đã bán</button>
                            <?php endif; ?>
                        </form>
                        
                        <div style="margin-top: 20px; font-size: 0.85rem; color: var(--text-muted); text-align: center;">
                            Hệ thống giả lập thanh toán tự động.<br>Nhận tài khoản ngay lập tức sau khi nhấn Mua.
                        </div>
                    </div>
                    
                    <a href="index.php" class="tab-btn" style="text-align: center; text-decoration: none; display: block; border-radius: var(--radius-sm);">
                        &larr; Quay lại danh sách
                    </a>
                </aside>
            </div>
            
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <p>&copy; 2026 AccountShop - Nhóm 5. Dự án học tập Lập trình web và ứng dụng.</p>
            <p>Thành viên: Võ Anh Kiệt Hoàng, Trần Gia Bảo, Nguyễn Đức Mạnh, Nguyễn Hoàng Thái.</p>
        </div>
    </footer>

</body>
</html>
