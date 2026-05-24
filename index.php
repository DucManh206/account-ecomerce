<?php
require_once __DIR__ . '/admin/config/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$search = trim($_GET['search'] ?? '');
$categoryId = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

$sql = "SELECT accounts.*, categories.name AS category_name 
        FROM accounts 
        LEFT JOIN categories ON accounts.category_id = categories.id 
        WHERE 1=1";
$params = [];

if ($categoryId !== '') {
    $sql .= " AND accounts.category_id = ?";
    $params[] = $categoryId;
}

if ($search !== '') {
    $sql .= " AND (accounts.name LIKE ? OR accounts.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$accounts = $stmt->fetchAll();

$myBalance = 0;
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $balStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $balStmt->execute([$_SESSION['user_id']]);
    $myBalance = $balStmt->fetchColumn();
}

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
    return 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=600&auto=format&fit=crop';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Shop - Hệ thống bán tài khoản tự động</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .header-user-badge {
            display: flex;
            align-items: center;
            gap: 16px;
        }
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
        .card-actions-wrapper {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .btn-add-cart {
            background-color: #ffffff;
            color: #000000;
            border: 1px solid #ffffff;
            padding: 8px 14px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            flex: 1;
            text-align: center;
            transition: var(--transition);
        }
        .btn-add-cart:hover {
            background-color: transparent;
            color: #ffffff;
            border-color: #ffffff;
        }
        .btn-add-cart-disabled {
            background-color: #4b5563;
            color: #9ca3af;
            border: none;
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
            flex: 1;
            text-align: center;
            cursor: not-allowed;
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
                    <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
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

    <section class="hero">
        <div class="container">
            <h1>Mua tài khoản <span>Premium</span> tự động</h1>
            <p>Chuyên cung cấp tài khoản Game, Netflix, Spotify, Key Office giá rẻ, uy tín. Nhận thông tin acc ngay lập tức.</p>
            
            <form action="index.php" method="GET" class="search-box">
                <input type="text" name="search" placeholder="Tìm tài khoản cần mua..." value="<?= htmlspecialchars($search) ?>">
                <?php if ($categoryId): ?>
                    <input type="hidden" name="category" value="<?= htmlspecialchars($categoryId) ?>">
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
                    <a href="index.php?category=&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" 
                       class="tab-btn <?= $categoryId === '' ? 'active' : '' ?>">
                        Tất cả
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?category=<?= $cat['id'] ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" 
                           class="tab-btn <?= (string)$categoryId === (string)$cat['id'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="sort-select">
                    <form action="index.php" method="GET" id="sortForm">
                        <?php if ($categoryId): ?>
                            <input type="hidden" name="category" value="<?= htmlspecialchars($categoryId) ?>">
                        <?php endif; ?>
                        <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        <select name="sort" onchange="document.getElementById('sortForm').submit();">
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
        <div class="accounts-grid">
            <?php foreach ($accounts as $acc): 
                $img = !empty($acc['image']) ? $acc['image'] : getFallbackImage($acc['category_name'] ?? '');
            ?>
                <article class="account-card">
                    <div class="card-image-wrapper">
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($acc['name']) ?>">
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
                                    <a href="cart.php" class="btn-add-cart" style="background-color: #059669;">Đã thêm</a>
                                <?php else: ?>
                                    <a href="cart.php?action=add&id=<?= $acc['id'] ?>" class="btn-add-cart">+ Thêm giỏ</a>
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
                <a href="index.php" class="tab-btn" style="display: inline-block; margin-top: 16px;">Xem tất cả</a>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container footer-content">
            <p>&copy; 2026 AccountShop - Nhóm 5. Bài tập lớn Lập trình web và ứng dụng.</p>
            <p>Thành viên: Võ Anh Kiệt Hoàng, Trần Gia Bảo, Nguyễn Đức Mạnh, Nguyễn Hoàng Thái.</p>
        </div>
    </footer>

</body>
</html>
