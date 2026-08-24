<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Thêm vào giỏ hàng
if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $accountId = intval($_GET['id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ? AND status = 'available'");
    $stmt->execute([$accountId]);
    $account = $stmt->fetch();
    
    if ($account) {
        if (!in_array($accountId, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $accountId;
            if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
                exit;
            }
            set_flash('success', 'Đã thêm tài khoản vào giỏ hàng!');
            header('Location: cart.php');
            exit;
        } else {
            $errMsg = 'Sản phẩm đã có trong giỏ hàng!';
            if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $errMsg]);
                exit;
            }
            set_flash('error', $errMsg);
        }
    } else {
        $errMsg = 'Tài khoản không tồn tại hoặc đã bán.';
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errMsg]);
            exit;
        }
        set_flash('error', $errMsg);
    }
}

// Xóa khỏi giỏ hàng
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $accountId = intval($_GET['id'] ?? 0);
    $key = array_search($accountId, $_SESSION['cart']);
    if ($key !== false) {
        unset($_SESSION['cart'][$key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        set_flash('success', 'Đã xóa tài khoản khỏi giỏ hàng.');
        header('Location: cart.php');
        exit;
    }
}

// Lấy dữ liệu giỏ hàng
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

// Thực hiện thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_checkout'])) {
    verify_csrf();

    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    if (empty($_SESSION['cart'])) {
        set_flash('error', 'Giỏ hàng đang trống!');
    } else {
        try {
            // Sử dụng Transaction và khóa dòng (SELECT ... FOR UPDATE) để chống Race Condition tuyệt đối
            $pdo->beginTransaction();

            // 1. Khóa và kiểm tra số dư người dùng
            $userStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
            $userStmt->execute([$userId]);
            $userBalance = $userStmt->fetchColumn();

            if ($userBalance === false) {
                throw new Exception('Không tìm thấy thông tin tài khoản người dùng.');
            }

            // 2. Khóa và kiểm tra từng sản phẩm trong giỏ hàng
            $lockedAccounts = [];
            $realTotalPrice = 0;
            $soldItemNames = [];

            $accCheckStmt = $pdo->prepare("SELECT id, name, price, status FROM accounts WHERE id = ? FOR UPDATE");
            foreach ($_SESSION['cart'] as $accId) {
                $accCheckStmt->execute([$accId]);
                $accData = $accCheckStmt->fetch();

                if (!$accData || $accData['status'] !== 'available') {
                    $soldItemNames[] = $accData ? $accData['name'] : ("ID #" . $accId);
                } else {
                    $lockedAccounts[] = $accData;
                    $realTotalPrice += floatval($accData['price']);
                }
            }

            if (!empty($soldItemNames)) {
                $pdo->rollBack();
                set_flash('error', 'Sản phẩm: ' . implode(', ', $soldItemNames) . ' đã bị người khác mua mất. Vui lòng xóa khỏi giỏ hàng để tiếp tục.');
            } elseif ($userBalance < $realTotalPrice) {
                $pdo->rollBack();
                set_flash('error', 'Số dư tài khoản không đủ (Hiện có: ' . number_format($userBalance, 0, ',', '.') . 'đ, Cần: ' . number_format($realTotalPrice, 0, ',', '.') . 'đ). Vui lòng nạp thêm tiền!');
            } else {
                // 3. Trừ số dư người mua
                $deductStmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $deductStmt->execute([$realTotalPrice, $userId]);

                // 4. Tạo đơn hàng và đổi trạng thái tài khoản
                $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, account_id, price) VALUES (?, ?, ?)");
                $updateStatusStmt = $pdo->prepare("UPDATE accounts SET status = 'sold', hidden = 1 WHERE id = ?");

                foreach ($lockedAccounts as $accItem) {
                    $orderStmt->execute([$userId, $accItem['id'], $accItem['price']]);
                    $updateStatusStmt->execute([$accItem['id']]);
                }

                $pdo->commit();
                $_SESSION['cart'] = [];

                set_flash('success', 'Mua tài khoản thành công! Xem thông tin đăng nhập ở bảng bên dưới.');
                header('Location: profile.php');
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Giỏ hàng của bạn - Account Shop';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

    <div class="container" style="min-height: 70vh;">
        <?= render_flash() ?>

        <div class="cart-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; margin-top: 40px; margin-bottom: 60px;">
            <main class="cart-main-card" style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 30px;">
                <h2 style="font-size: 1.5rem; color: var(--text-white); font-weight: 700; margin-bottom: 24px;">Giỏ hàng của bạn</h2>
                
                <?php if (empty($cartAccounts)): ?>
                    <div style="text-align: center; padding: 40px 0; color: var(--text-gray);">
                        <p style="font-size: 1.15rem; font-style: italic;">Giỏ hàng của bạn đang trống.</p>
                        <a href="index.php" class="tab-btn" style="display: inline-block; margin-top: 16px;">Tiếp tục xem sản phẩm</a>
                    </div>
                <?php else: ?>
                    <div class="cart-items-list">
                        <?php foreach ($cartAccounts as $acc): 
                            $img = !empty($acc['image']) ? $acc['image'] : 'assets/images/default-product.png';
                            $isSold = ($acc['status'] !== 'available');
                        ?>
                            <div class="cart-item <?= $isSold ? 'cart-item-sold' : '' ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                <div class="cart-item-details" style="display: flex; align-items: center; gap: 16px;">
                                    <img src="<?= htmlspecialchars($img) ?>" class="cart-item-img" style="width: 70px; height: 45px; object-fit: cover; background: #1f2937; border-radius: var(--radius-sm);" alt="" onerror="this.src='assets/images/default-product.png'; this.onerror=null;">
                                    <div>
                                        <a href="chitiet.php?id=<?= $acc['id'] ?>" class="cart-item-title" style="font-weight: 600; color: var(--text-white); text-decoration: none;"><?= htmlspecialchars($acc['name']) ?></a>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">Danh mục: <?= htmlspecialchars($acc['category_name'] ?? 'Chưa phân loại') ?></div>
                                        <?php if ($isSold): ?>
                                            <div style="color: #ef4444; font-size: 0.8rem; font-weight: 700; margin-top: 6px; text-transform: uppercase;">Tài khoản này đã bị mua mất - Vui lòng xóa khỏi giỏ</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 24px;">
                                    <span style="font-weight: 700; color: #10b981;"><?= number_format($acc['price'], 0, ',', '.') ?>đ</span>
                                    <a href="cart.php?action=delete&id=<?= $acc['id'] ?>" class="btn-cart-delete" style="background-color: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); padding: 6px 12px; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 600; text-decoration: none;">Xóa</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>

            <aside class="cart-summary-card" style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; height: fit-content; position: sticky; top: 100px;">
                <h3 style="font-size: 1.15rem; color: var(--text-white); font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 12px;">Đơn hàng</h3>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.95rem; color: var(--text-gray);">
                    <span>Số lượng:</span>
                    <span style="color: var(--text-white); font-weight: 600;"><?= count($_SESSION['cart']) ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 16px; font-size: 1.25rem; font-weight: 800; color: #10b981; margin-top: 16px;">
                    <span>Tổng tiền:</span>
                    <span><?= number_format($totalPrice, 0, ',', '.') ?>đ</span>
                </div>

                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): 
                    $balStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
                    $balStmt->execute([$_SESSION['user_id']]);
                    $myBalance = $balStmt->fetchColumn();
                ?>
                    <div style="display: flex; justify-content: space-between; margin-top: 20px; font-size: 0.85rem; color: var(--text-gray);">
                        <span>Số dư hiện tại:</span>
                        <span style="font-weight: 600; color: #34d399;"><?= number_format($myBalance, 0, ',', '.') ?>đ</span>
                    </div>
                    
                    <form method="POST" style="margin-top: 20px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action_checkout" value="1">
                        <?php if (count($_SESSION['cart']) > 0): ?>
                            <button type="submit" class="btn-buy" style="width: 100%;">Thanh toán bằng số dư</button>
                        <?php else: ?>
                            <button type="button" class="btn-buy" style="width: 100%; opacity: 0.5;" disabled>Giỏ hàng trống</button>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
