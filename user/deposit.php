<?php
session_start();
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../lib/ui_modules.php';
require_once __DIR__ . '/../lib/sepay_modules.php';

// Lazy sync SePay
if (function_exists('sepay_lazySync')) {
    sepay_lazySync();
}

if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'];
$sepayConfig = sepay_getConfig();
$sepayEnabled = $sepayConfig && $sepayConfig['status'] == 1;

$error = '';
$success = isset($_GET['success']) ? 'Đã tạo yêu cầu nạp tiền!' : '';

// Xử lý Hủy / Kiểm tra / Tạo yêu cầu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cancel_deposit') {
        $id = intval($_POST['deposit_id']);
        mysqli_query($conn, "UPDATE deposit_requests SET status = 'cancelled' WHERE id = $id AND user_id = $userId AND status = 'pending'");
        header("Location: deposit.php"); exit;
    } elseif ($_POST['action'] === 'sync_sepay') {
        sepay_syncAndProcess();
        header("Location: deposit.php"); exit;
    } elseif ($_POST['action'] === 'create') {
        $amount = intval($_POST['amount'] ?? 0);
        if ($amount < 10000) {
            $error = 'Tối thiểu 10,000đ';
        } else {
            mysqli_query($conn, "UPDATE deposit_requests SET status = 'cancelled' WHERE user_id = $userId AND status = 'pending'");
            $prefix = $sepayConfig['transfer_prefix'] ?? 'NT';
            $uniqueCode = $prefix . '_' . $userId . '_' . time();
            $expires = date('Y-m-d H:i:s', time() + (30 * 60));
            $stmt = $conn->prepare("INSERT INTO deposit_requests (user_id, username, amount, transfer_amount, transfer_note, unique_code, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->bind_param("isiisss", $userId, $username, $amount, $amount, $uniqueCode, $uniqueCode, $expires);
            $stmt->execute();
            header("Location: deposit.php?success=1"); exit;
        }
    }
}

// Lấy yêu cầu hiện tại
$current = null;
$res = mysqli_query($conn, "SELECT * FROM deposit_requests WHERE user_id = $userId AND status = 'pending' AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
if ($res && $row = mysqli_fetch_assoc($res)) {
    $current = $row;
}

// Lấy lịch sử
$history = mysqli_query($conn, "SELECT * FROM deposit_requests WHERE user_id = $userId ORDER BY created_at DESC LIMIT 10");

// Lấy số dư
$userRes = mysqli_query($conn, "SELECT balance FROM users WHERE id = $userId");
$balance = ($u = mysqli_fetch_assoc($userRes)) ? intval($u['balance']) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead('Nạp tiền'); ?>
    <style>
        .deposit-container { max-width: 500px; margin: 2rem auto; padding: 0 1rem; }
        .balance-card { 
            background: linear-gradient(135deg, #6e56cf, #4a38a7); 
            border-radius: 16px; padding: 1.25rem 1.5rem; color: #fff; margin-bottom: 1.5rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .qr-card { background: #fff; border-radius: 20px; padding: 1.5rem; text-align: center; color: #1a1a2e; margin-bottom: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .qr-img { width: 180px; height: 180px; margin: 10px auto; display: block; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 0.9rem; }
        .info-label { color: #64748b; }
        .info-value { font-weight: 700; color: #1e293b; }
        .history-item { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 12px 16px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; }
        .history-item.pending { cursor: pointer; border: 1px solid rgba(251,191,36,0.2); }
        .history-item.pending:hover { background: rgba(251,191,36,0.05); }
        .status-pill { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .status-pending { background: rgba(251,191,36,0.1); color: #fbbf24; }
        .status-approved { background: rgba(74,222,128,0.1); color: #4ade80; }
        .status-cancelled { background: rgba(248,113,113,0.1); color: #f87171; }
        .quick-btn { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 8px; border-radius: 10px; cursor: pointer; font-size: 0.85rem; flex: 1; text-align: center; }
        .quick-btn:hover { background: #6e56cf; border-color: #6e56cf; }
        
        /* Simple Modal */
        #qrModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center; padding:20px; }
        #qrModal .modal-content { background:#fff; border-radius:24px; padding:20px; width:100%; max-width:400px; color:#1a1a2e; }
    </style>
</head>
<body class="bg-nexus">
    <div class="deposit-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="m-0 fw-bold">Nạp tiền</h3>
            <a href="/" class="text-muted text-decoration-none small"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger py-2 small"><?php echo $error; ?></div><?php endif; ?>

        <div class="balance-card">
            <div>
                <div class="small opacity-75">Số dư của bạn</div>
                <div class="h3 fw-bold mb-0"><?php echo number_format($balance); ?> đ</div>
            </div>
            <i class="fa-solid fa-wallet fa-2x opacity-25"></i>
        </div>

        <?php if ($current): ?>
            <div class="qr-card">
                <div class="small text-warning fw-bold mb-2"><i class="fa-solid fa-clock"></i> Chờ chuyển khoản...</div>
                <img src="https://img.vietqr.io/image/<?php echo $sepayConfig['bank_code']; ?>-<?php echo $sepayConfig['account_number']; ?>-compact.png?amount=<?php echo $current['amount']; ?>&addInfo=<?php echo urlencode($current['unique_code']); ?>" class="qr-img">
                <div class="info-row"><span class="info-label">Số tiền</span><span class="info-value text-success"><?php echo number_format($current['amount']); ?>đ</span></div>
                <div class="info-row"><span class="info-label">Nội dung</span><span class="info-value text-primary"><?php echo $current['unique_code']; ?></span></div>
                <div class="mt-3 d-flex gap-2">
                    <form method="POST" class="flex-grow-1"><input type="hidden" name="action" value="sync_sepay"><button class="btn btn-primary w-100 fw-bold btn-sm py-2">Kiểm tra ngay</button></form>
                    <form method="POST"><input type="hidden" name="action" value="cancel_deposit"><input type="hidden" name="deposit_id" value="<?php echo $current['id']; ?>"><button class="btn btn-link text-danger text-decoration-none btn-sm">Hủy đơn</button></form>
                </div>
            </div>
        <?php else: ?>
            <div class="nexus-card p-3 mb-4">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="input-group mb-2">
                        <input type="number" name="amount" id="amount" class="form-control bg-dark border-secondary text-white" placeholder="Nhập số tiền nạp..." required>
                        <span class="input-group-text bg-dark border-secondary text-muted">đ</span>
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <?php foreach([20000, 50000, 100000, 200000] as $v): ?>
                            <div class="quick-btn" onclick="document.getElementById('amount').value=<?php echo $v; ?>"><?php echo number_format($v/1000); ?>k</div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn-nexus-success w-100 py-2 fw-bold">Tạo mã QR nạp tiền</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="small text-muted mb-3 fw-bold text-uppercase" style="letter-spacing:1px;">Lịch sử gần đây</div>
        <?php while($h = mysqli_fetch_assoc($history)): ?>
            <div class="history-item <?php echo $h['status']; ?>" 
                 <?php if($h['status'] === 'pending'): ?> 
                 onclick="openQR('<?php echo $h['amount']; ?>', '<?php echo $h['unique_code']; ?>')"
                 <?php endif; ?>>
                <div>
                    <div class="fw-bold"><?php echo number_format($h['amount']); ?>đ</div>
                    <div class="small text-muted" style="font-size:0.75rem;"><?php echo date('H:i d/m', strtotime($h['created_at'])); ?></div>
                </div>
                <div class="status-pill status-<?php echo $h['status']; ?>">
                    <?php 
                    $st = ['pending'=>'Chờ','approved'=>'Xong','cancelled'=>'Hủy','rejected'=>'Lỗi','expired'=>'Hết hạn']; 
                    echo $st[$h['status']] ?? $h['status']; 
                    ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- QR Modal -->
    <div id="qrModal" onclick="this.style.display='none'">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-0 fw-bold">Thông tin nạp tiền</h5>
                <button class="btn-close" onclick="document.getElementById('qrModal').style.display='none'"></button>
            </div>
            <img id="m-qr" src="" class="qr-img">
            <div class="info-row"><span class="info-label">Ngân hàng</span><span class="info-value"><?php echo $sepayConfig['bank_code']; ?></span></div>
            <div class="info-row"><span class="info-label">Số TK</span><span class="info-value"><?php echo $sepayConfig['account_number']; ?></span></div>
            <div class="info-row"><span class="info-label">Số tiền</span><span class="info-value text-success" id="m-amount"></span></div>
            <div class="info-row border-0"><span class="info-label">Nội dung</span><span class="info-value text-primary" id="m-code"></span></div>
            <button class="btn btn-dark w-100 mt-3" onclick="document.getElementById('qrModal').style.display='none'">Đóng</button>
        </div>
    </div>

    <script>
        function openQR(amount, code) {
            document.getElementById('m-qr').src = 'https://img.vietqr.io/image/<?php echo $sepayConfig['bank_code']; ?>-<?php echo $sepayConfig['account_number']; ?>-compact.png?amount=' + amount + '&addInfo=' + encodeURIComponent(code);
            document.getElementById('m-amount').innerText = new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
            document.getElementById('m-code').innerText = code;
            document.getElementById('qrModal').style.display = 'flex';
        }
    </script>
    <?php ui_renderScripts(); ?>
</body>
</html>
