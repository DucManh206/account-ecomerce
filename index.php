<?php
// ============================================================
// Trang chủ - Shop bán tài khoản (Frontend)
// ============================================================
require_once __DIR__ . '/admin/config/db.php';

// Nhận tham số tìm kiếm, lọc, sắp xếp
$search = trim($_GET['search'] ?? '');
$categoryId = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

// Lấy danh mục để hiển thị ở bộ lọc
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Xây dựng câu truy vấn SQL lấy tài khoản
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

// Sắp xếp
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

// Hàm lấy ảnh đại diện mặc định theo loại tài khoản nếu không có ảnh
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Sở hữu tài khoản <span>Premium</span> chỉ trong vài giây</h1>
            <p>Hệ thống cung cấp tài khoản Game, Netflix, Spotify, Key phần mềm tự động uy tín, bảo mật và nhanh chóng hàng đầu Việt Nam.</p>
            
            <!-- Search Box -->
            <form action="index.php" method="GET" class="search-box">
                <input type="text" name="search" placeholder="Tìm kiếm tài khoản (Ví dụ: Netflix, LMHT...)" value="<?= htmlspecialchars($search) ?>">
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

    <!-- Filters & Sort -->
    <section class="search-filter-section">
        <div class="container">
            <div class="filter-wrapper">
                <!-- Categories Tabs -->
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

                <!-- Sort dropdown -->
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

    <!-- Accounts Grid -->
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
                        <div class="card-footer">
                            <span class="card-price"><?= number_format($acc['price'], 0, ',', '.') ?>đ</span>
                            <a href="chitiet.php?id=<?= $acc['id'] ?>" class="btn-view">Chi tiết</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (empty($accounts)): ?>
            <div class="empty" style="text-align: center; margin: 40px 0; color: var(--text-gray);">
                <p style="font-size: 1.2rem;">Không tìm thấy tài khoản nào phù hợp với yêu cầu của bạn.</p>
                <a href="index.php" class="tab-btn" style="display: inline-block; margin-top: 16px;">Xem tất cả</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <p>&copy; 2026 AccountShop - Nhóm 5. Dự án học tập Lập trình web và ứng dụng.</p>
            <p>Thành viên: Võ Anh Kiệt Hoàng, Trần Gia Bảo, Nguyễn Đức Mạnh, Nguyễn Hoàng Thái.</p>
        </div>
    </footer>

</body>
</html>
