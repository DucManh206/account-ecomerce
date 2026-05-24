<?php
require_once __DIR__ . '/admin/config/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$error = '';
$success = '';

// Them vao gio hang
if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $accountId = intval($_GET['id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ? AND status = 'available'");
    $stmt->execute([$accountId]);
    $account = $stmt->fetch();
    
    if ($account) {
        if (!in_array($accountId, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $accountId;
            header('Location: cart.php');
            exit;
        } else {
            $error = 'Sản phẩm đã có trong giỏ hàng!';
        }
    } else {
        $error = 'Tài khoản không tồn tại hoặc đã bán.';
    }
}

// Xoa khoi gio hang
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $accountId = intval($_GET['id'] ?? 0);
    $key = array_search($accountId, $_SESSION['cart']);
    if ($key !== false) {
        unset($_SESSION['cart'][$key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        header('Location: cart.php');
        exit;
    }
}

// Lay du lieu gio hang
$cartAccounts = [];
$totalPrice = 0;

if (!empty($_SESSION['cart'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $stmt = $pdo->prepare("
        SELECT accounts.*, categories.name AS category_name 
        FROM accounts 
        LEFT JOIN categories ON accounts.category_id = categories.id 
        WHERE accounts.id IN ($placeholders)
    ");
    $stmt->execute($_SESSION['cart']);
    $cartAccounts = $stmt->fetchAll();
    
    foreach ($cartAccounts as $acc) {
        $totalPrice += $acc['price'];
    }
}

// Thuc hien thanh toan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_checkout'])) {
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    $userStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $userBalance = $userStmt->fetchColumn();
    
    if (empty($_SESSION['cart'])) {
        $error = 'Giỏ hàng đang trống!';
    } elseif ($userBalance < $totalPrice) {
        $error = 'Số dư tài khoản không đủ. Vui lòng nạp thêm tiền!';
    } else {
        // Kiem tra xem co acc nao bi nguoi khac mua truoc chua
        $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE id IN ($placeholders) AND status != 'available'");
        $checkStmt->execute($_SESSION['cart']);
        $soldCount = $checkStmt->fetchColumn();
        
        if ($soldCount > 0) {
            $error = 'Có tài khoản đã bị người khác mua mất. Vui lòng xóa khỏi giỏ hàng để tiếp tục.';
        } else {
            // Dung transaction dam bao an toan dong thoi
            try {
                $pdo->beginTransaction();
                
                $deductStmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $deductStmt->execute([$totalPrice, $userId]);
                
                $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, account_id, price) VALUES (?, ?, ?)");
                $updateStatusStmt = $pdo->prepare("UPDATE accounts SET status = 'sold' WHERE id = ?");
                
                foreach ($_SESSION['cart'] as $accId) {
                    $priceStmt = $pdo->prepare("SELECT price FROM accounts WHERE id = ?");
                    $priceStmt->execute([$accId]);
                    $price = $priceStmt->fetchColumn();
                    
                    $orderStmt->execute([$userId, $accId, $price]);
                    $updateStatusStmt->execute([$accId]);
                }
                
                $pdo->commit();
                $_SESSION['cart'] = [];
                
                header('Location: profile.php?success=' . urlencode('Mua tài khoản thành công! Xem thông tin đăng nhập ở bảng bên dưới.'));
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng của bạn - Account Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
            margin-top: 40px;
            margin-bottom: 60px;
        }
        .cart-main-card {
            background-color: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 30px;
        }
        .cart-summary-card {
            background-color: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-details {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .cart-item-img {
            width: 70px;
            height: 45px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background-color: #1f2937;
        }
        .cart-item-title {
            font-weight: 600;
            color: var(--text-white);
            font-size: 1rem;
            text-decoration: none;
            transition: var(--transition);
        }
        .cart-item-title:hover {
            color: var(--primary);
        }
        .btn-cart-delete {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            transition: var(--transition);
        }
        .btn-cart-delete:hover {
            background-color: var(--danger);
            color: white;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 0.95rem;
            color: var(--text-gray);
        }
        .summary-total {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 16px;
            font-size: 1.25rem;
            font-weight: 800;
            color: #10b981;
            margin-top: 16px;
        }
        @media (max-width: 900px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
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
                <?php if (isset($_SESSION['user_logged_in'])): ?>
                    <a href="profile.php" class="nav-link">Trang cá nhân</a>
                <?php endif; ?>
                <a href="cart.php" class="nav-link active">Giỏ hàng (<?= count($_SESSION['cart']) ?>)</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="frontend-alert frontend-alert-error" style="margin-top: 24px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="cart-layout">
            <main class="cart-main-card">
                <h2 style="font-size: 1.5rem; color: var(--text-white); font-weight: 700; margin-bottom: 24px;">Giỏ hàng</h2>
                
                <?php if (empty($cartAccounts)): ?>
                    <div style="text-align: center; padding: 40px 0; color: var(--text-gray);">
                        <p style="font-size: 1.15rem; font-style: italic;">Giỏ hàng của bạn đang trống.</p>
                        <a href="index.php" class="tab-btn" style="display: inline-block; margin-top: 16px;">Tiếp tục xem sản phẩm</a>
                    </div>
                <?php else: ?>
                    <div class="cart-items-list">
                        <?php foreach ($cartAccounts as $acc): 
                            $img = !empty($acc['image']) ? $acc['image'] : 'assets/images/default-product.png';
                        ?>
                            <div class="cart-item">
                                <div class="cart-item-details">
                                    <img src="<?= htmlspecialchars($img) ?>" class="cart-item-img" alt="" onerror="this.src='assets/images/default-product.png'; this.onerror=null;">
                                    <div>
                                        <a href="chitiet.php?id=<?= $acc['id'] ?>" class="cart-item-title"><?= htmlspecialchars($acc['name']) ?></a>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Danh mục: <?= htmlspecialchars($acc['category_name'] ?? 'Chưa phân loại') ?></div>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 24px;">
                                    <span style="font-weight: 700; color: #10b981;"><?= number_format($acc['price'], 0, ',', '.') ?>đ</span>
                                    <a href="cart.php?action=delete&id=<?= $acc['id'] ?>" class="btn-cart-delete">Xóa</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>

            <aside class="cart-summary-card">
                <h3 style="font-size: 1.15rem; color: var(--text-white); font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 12px;">Đơn hàng</h3>
                
                <div class="summary-row">
                    <span>Số lượng:</span>
                    <span style="color: var(--text-white); font-weight: 600;"><?= count($_SESSION['cart']) ?></span>
                </div>
                
                <div class="summary-row summary-total">
                    <span>Tổng tiền:</span>
                    <span><?= number_format($totalPrice, 0, ',', '.') ?>đ</span>
                </div>

                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): 
                    $balStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
                    $balStmt->execute([$_SESSION['user_id']]);
                    $myBalance = $balStmt->fetchColumn();
                ?>
                    <div class="summary-row" style="margin-top: 20px; font-size: 0.85rem;">
                        <span>Số dư của bạn:</span>
                        <span style="font-weight: 600; color: #34d399;"><?= number_format($myBalance, 0, ',', '.') ?>đ</span>
                    </div>
                    
                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action_checkout" value="1">
                        <?php if (count($_SESSION['cart']) > 0): ?>
                            <button type="submit" class="btn-buy">Thanh toán bằng số dư</button>
                        <?php else: ?>
                            <button type="button" class="btn-buy" disabled>Giỏ hàng trống</button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <div style="margin-top: 24px; text-align: center;">
                        <a href="login.php" class="btn-buy" style="text-decoration: none; display: block;">Đăng nhập để mua acc</a>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">Đăng nhập thành viên để thanh toán.</p>
                    </div>
                <?php endif; ?>
                
                <a href="index.php" class="tab-btn" style="text-align: center; text-decoration: none; display: block; border-radius: var(--radius-sm); margin-top: 16px;">
                    &larr; Chọn thêm tài khoản
                </a>
            </aside>
        </div>
    </div>

    <footer>
        <div class="container footer-content">
            <p>&copy; 2026 AccountShop - Nhóm 5. Bài tập lớn Lập trình web và ứng dụng.</p>
        </div>
    </footer>

</body>
</html>
