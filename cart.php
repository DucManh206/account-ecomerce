<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/flash.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Thay đổi giỏ hàng qua POST để tránh các liên kết ngoài tự ý làm thay đổi phiên người dùng.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_action'])) {
    $isAjax = ($_POST['ajax'] ?? '') === '1';
    verify_csrf($isAjax);
    $accountId = (int) ($_POST['id'] ?? 0);
    $cartAction = $_POST['cart_action'];

    if ($cartAction === 'add') {
        $buyNow = ($_POST['buy_now'] ?? '') === '1';
        $stmt = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND status = 'available' AND hidden = 0");
        $stmt->execute([$accountId]);
        $account = $stmt->fetch();

        if ($account) {
            if (!cart_contains($accountId)) {
                $_SESSION['cart'][] = $accountId;
            }
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])], JSON_UNESCAPED_UNICODE);
                exit;
            }
            set_flash('success', $buyNow ? 'Sản phẩm đã sẵn sàng trong giỏ. Hãy hoàn tất thanh toán.' : 'Đã thêm sản phẩm vào giỏ hàng.');
            header('Location: cart.php');
            exit;
        }

        $errorMessage = 'Sản phẩm không tồn tại, đã bán hoặc đang bị ẩn.';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMessage], JSON_UNESCAPED_UNICODE);
            exit;
        }
        set_flash('error', $errorMessage);
    }

    if ($cartAction === 'remove') {
        $_SESSION['cart'] = array_values(array_filter(
            $_SESSION['cart'],
            fn($id) => (int) $id !== $accountId
        ));
        set_flash('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
        header('Location: cart.php');
        exit;
    }
}

// Lấy dữ liệu giỏ hàng
$cartAccounts = [];
$totalPrice = 0;
$payableTotal = 0;
$unavailableCount = 0;

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
    $existingIds = array_map(fn($account) => (int) $account['id'], $cartAccounts);
    $_SESSION['cart'] = array_values(array_filter(
        array_map('intval', $_SESSION['cart']),
        fn($id) => in_array($id, $existingIds, true)
    ));
    
    foreach ($cartAccounts as $acc) {
        $totalPrice += $acc['price'];
        if ($acc['status'] !== 'available' || (int) $acc['hidden'] === 1) {
            $unavailableCount++;
        } else {
            $payableTotal += $acc['price'];
        }
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

            // 1. Khóa và kiểm tra số dư + trạng thái tài khoản người dùng
            $userStmt = $pdo->prepare("SELECT id, balance, is_active FROM users WHERE id = ? FOR UPDATE");
            $userStmt->execute([$userId]);
            $lockedUser = $userStmt->fetch();

            if (!$lockedUser) {
                throw new Exception('Không tìm thấy thông tin tài khoản người dùng.');
            }
            if (!(int) $lockedUser['is_active']) {
                throw new Exception('Tài khoản đang bị tạm khóa nên không thể thanh toán.');
            }
            $userBalance = (float) $lockedUser['balance'];

            // 2. Khóa và kiểm tra từng sản phẩm trong giỏ hàng
            $lockedAccounts = [];
            $realTotalPrice = 0;
            $soldItemNames = [];

            $accCheckStmt = $pdo->prepare(
                "SELECT a.id, a.name, a.price, a.status, a.hidden, a.account_detail, c.name AS category_name
                 FROM accounts a
                 LEFT JOIN categories c ON c.id = a.category_id
                 WHERE a.id = ?
                 FOR UPDATE"
            );
            foreach ($_SESSION['cart'] as $accId) {
                $accCheckStmt->execute([$accId]);
                $accData = $accCheckStmt->fetch();

                if (!$accData || $accData['status'] !== 'available' || (int) $accData['hidden'] === 1) {
                    $soldItemNames[] = $accData ? $accData['name'] : ("ID #" . $accId);
                } else {
                    $lockedAccounts[] = $accData;
                    $realTotalPrice += (float) $accData['price'];
                }
            }

            if (!empty($soldItemNames)) {
                $pdo->rollBack();
                set_flash('error', 'Sản phẩm: ' . implode(', ', $soldItemNames) . ' không còn bán. Vui lòng xóa khỏi giỏ hàng để tiếp tục.');
            } elseif ($userBalance < $realTotalPrice) {
                $pdo->rollBack();
                $missing = $realTotalPrice - $userBalance;
                set_flash('error', 'Số dư không đủ. Bạn còn thiếu ' . number_format($missing, 0, ',', '.') . 'đ. Vui lòng nạp thêm tiền!');
            } else {
                $deductStmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $deductStmt->execute([$realTotalPrice, $userId]);

                $orderStmt = $pdo->prepare(
                    "INSERT INTO orders (user_id, account_id, price, product_name, product_category, delivered_credentials)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $updateStatusStmt = $pdo->prepare("UPDATE accounts SET status = 'sold', hidden = 1 WHERE id = ?");
                $runningBalance = $userBalance;

                foreach ($lockedAccounts as $accItem) {
                    $runningBalance -= (float) $accItem['price'];
                    $orderStmt->execute([
                        $userId,
                        $accItem['id'],
                        $accItem['price'],
                        $accItem['name'],
                        $accItem['category_name'] ?: 'Chưa phân loại',
                        $accItem['account_detail'],
                    ]);
                    $orderId = (int) $pdo->lastInsertId();
                    $updateStatusStmt->execute([$accItem['id']]);
                    record_balance_transaction(
                        $pdo,
                        (int) $userId,
                        'purchase',
                        -(float) $accItem['price'],
                        $runningBalance,
                        'order',
                        $orderId,
                        'Thanh toán đơn hàng #' . $orderId
                    );
                }

                $pdo->commit();
                $_SESSION['cart'] = [];

                set_flash('success', 'Mua tài khoản thành công! Thông tin đăng nhập đã sẵn sàng trong tài khoản của bạn.');
                header('Location: profile.php');
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('checkout failed: ' . $e->getMessage());
            set_flash('error', 'Không thể hoàn tất thanh toán lúc này. Vui lòng thử lại.');
        }
    }
}

$pageTitle = 'Giỏ hàng của bạn - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

    <main id="main-content" class="container" style="min-height: 70vh;">
        <?= render_flash() ?>

        <div class="cart-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; margin-top: 40px; margin-bottom: 60px;">
            <section class="cart-main-card" aria-labelledby="cart-heading" style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 16px;">
                    <h2 id="cart-heading" style="font-size: 1.4rem; color: var(--text-white); font-weight: 700;">Giỏ hàng của bạn</h2>
                    <span style="font-size: 0.9rem; color: var(--text-gray);"><?= count($_SESSION['cart']) ?> tài khoản đã chọn</span>
                </div>
                
                <?php if (empty($cartAccounts)): ?>
                    <div style="text-align: center; padding: 60px 0; color: var(--text-gray);">
                        <p style="font-size: 1.15rem; font-style: italic; margin-bottom: 8px;">Giỏ hàng của bạn hiện đang trống.</p>
                        <p style="font-size: 0.9rem; color: var(--text-muted);">Hãy chọn các tài khoản ưng ý từ trang chủ và thêm vào giỏ.</p>
                        <a href="index.php" class="tab-btn" style="display: inline-block; margin-top: 20px;">Khám phá sản phẩm ngay</a>
                    </div>
                <?php else: ?>
                    <div class="cart-items-list">
                        <?php foreach ($cartAccounts as $acc): 
                            $img = product_image_url($acc['image'] ?? '');
                            $isSold = ($acc['status'] !== 'available' || (int) $acc['hidden'] === 1);
                        ?>
                            <div class="cart-item <?= $isSold ? 'cart-item-sold' : '' ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 18px 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
                                <div class="cart-item-details" style="display: flex; align-items: center; gap: 16px;">
                                    <img src="<?= htmlspecialchars($img) ?>" class="cart-item-img" style="width: 76px; height: 48px; object-fit: cover; background: #1f2937; border-radius: var(--radius-sm);" alt="" onerror="this.src='<?= htmlspecialchars(default_product_image()) ?>'; this.onerror=null;">
                                    <div>
                                        <a href="chitiet.php?id=<?= $acc['id'] ?>" class="cart-item-title" style="font-weight: 600; color: var(--text-white); text-decoration: none; font-size: 1rem;"><?= htmlspecialchars($acc['name']) ?></a>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">Danh mục: <?= htmlspecialchars($acc['category_name'] ?? 'Chưa phân loại') ?></div>
                                        <?php if ($isSold): ?>
                                            <div style="color: #ef4444; font-size: 0.8rem; font-weight: 700; margin-top: 6px; text-transform: uppercase;">● Tài khoản này đã bị người khác mua mất - Vui lòng xóa khỏi giỏ</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 20px;">
                                    <span style="font-weight: 800; color: #10b981; font-size: 1.1rem;"><?= number_format($acc['price'], 0, ',', '.') ?>đ</span>
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="cart_action" value="remove">
                                        <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                                        <button type="submit" class="btn-cart-delete" title="Xóa khỏi giỏ">Xóa</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="cart-summary-card" style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 26px; height: fit-content; position: sticky; top: 100px;">
                <h3 style="font-size: 1.15rem; color: var(--text-white); font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 12px;">Tóm tắt đơn hàng</h3>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.95rem; color: var(--text-gray);">
                    <span>Tổng sản phẩm:</span>
                    <span style="color: var(--text-white); font-weight: 600;"><?= count($_SESSION['cart']) ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 16px; font-size: 1.3rem; font-weight: 800; color: #10b981; margin-top: 14px;">
                    <span>Tổng thanh toán:</span>
                    <span><?= number_format($payableTotal, 0, ',', '.') ?>đ</span>
                </div>

                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): 
                    $balStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
                    $balStmt->execute([$_SESSION['user_id']]);
                    $myBalance = floatval($balStmt->fetchColumn() ?: 0);
                    $hasEnoughBalance = ($unavailableCount === 0 && $payableTotal > 0 && $myBalance >= $payableTotal);
                    $missingBalance = max(0, $payableTotal - $myBalance);
                ?>
                    <div style="margin-top: 20px; padding: 14px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--text-gray);">
                            <span>Số dư khả dụng:</span>
                            <span style="font-weight: 700; color: <?= $hasEnoughBalance ? '#34d399' : '#ef4444' ?>;"><?= number_format($myBalance, 0, ',', '.') ?>đ</span>
                        </div>

                        <?php if ($unavailableCount > 0): ?>
                            <div style="font-size: 0.8rem; color: #ef4444; margin-top: 8px;">
                                ⚠️ Có <?= $unavailableCount ?> sản phẩm không còn bán. Hãy xóa khỏi giỏ trước khi thanh toán.
                            </div>
                        <?php elseif ($hasEnoughBalance && $payableTotal > 0): ?>
                            <div style="font-size: 0.8rem; color: #34d399; margin-top: 8px;">
                                ✓ Số dư đủ để thanh toán đơn hàng này. (Còn lại sau mua: <?= number_format($myBalance - $payableTotal, 0, ',', '.') ?>đ)
                            </div>
                        <?php elseif (!$hasEnoughBalance && $payableTotal > 0): ?>
                            <div style="font-size: 0.8rem; color: #ef4444; margin-top: 8px;">
                                ⚠️ Số dư còn thiếu: <strong><?= number_format($missingBalance, 0, ',', '.') ?>đ</strong>
                            </div>
                            <a href="topup.php?amount=<?= ceil($missingBalance) ?>" class="btn-buy" style="display: block; text-align: center; text-decoration: none; margin-top: 10px; padding: 8px; font-size: 0.85rem; background: #ffffff; color: #000000;">
                                + Nạp nhanh <?= number_format($missingBalance, 0, ',', '.') ?>đ
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" style="margin-top: 20px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action_checkout" value="1">
                        <?php if (count($_SESSION['cart']) > 0 && $unavailableCount > 0): ?>
                            <button type="button" class="btn-buy" style="width: 100%; opacity: 0.5; cursor: not-allowed;" disabled>Cần xóa sản phẩm đã bán</button>
                        <?php elseif (count($_SESSION['cart']) > 0 && $hasEnoughBalance): ?>
                            <button type="submit" class="btn-buy" style="width: 100%; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);">Thanh toán bằng số dư</button>
                        <?php elseif (count($_SESSION['cart']) > 0 && !$hasEnoughBalance): ?>
                            <button type="button" class="btn-buy" style="width: 100%; opacity: 0.5; cursor: not-allowed;" title="Vui lòng nạp thêm tiền" disabled>Số dư không đủ thanh toán</button>
                        <?php else: ?>
                            <button type="button" class="btn-buy" style="width: 100%; opacity: 0.5;" disabled>Giỏ hàng trống</button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <div style="margin-top: 24px; text-align: center;">
                        <a href="login.php" class="btn-buy" style="text-decoration: none; display: block;">Đăng nhập để mua acc</a>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px;">Đăng nhập thành viên để thanh toán tự động.</p>
                    </div>
                <?php endif; ?>
                
                <a href="index.php" class="tab-btn" style="text-align: center; text-decoration: none; display: block; border-radius: var(--radius-sm); margin-top: 16px;">
                    &larr; Chọn thêm tài khoản
                </a>
            </aside>
        </div>
    </main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
