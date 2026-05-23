<?php
session_start();
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../lib/ui_modules.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit;
}

$username = $_SESSION['username'];
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$tableCheck = mysqli_query($GLOBALS['conn'], "SHOW TABLES LIKE 'deposit_requests'");
$depositTableExists = mysqli_num_rows($tableCheck) > 0;
$banksTableCheck = mysqli_query($GLOBALS['conn'], "SHOW TABLES LIKE 'banks'");
$banksTableExists = mysqli_num_rows($banksTableCheck) > 0;

$sql = "SELECT balance FROM users WHERE username = ?";
$stmt = mysqli_prepare($GLOBALS['conn'], $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$balance = intval($user['balance']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_deposit') {
    if (!$depositTableExists) {
        $error = 'Hệ thống nạp tiền chưa được kích hoạt.';
    } else {
        $amount = intval($_POST['amount'] ?? 0);
        $transfer_amount = intval($_POST['transfer_amount'] ?? 0);
        $transfer_note = mysqli_real_escape_string($GLOBALS['conn'], $_POST['transfer_note'] ?? '');
        $bank_id = intval($_POST['bank_id'] ?? 0);

        if ($amount <= 0) {
            $error = 'Số tiền nạp phải lớn hơn 0';
        } elseif ($transfer_amount != $amount) {
            $error = 'Số tiền chuyển khoản phải khớp với số tiền nạp';
        } else {
            $sql = "INSERT INTO deposit_requests (user_id, username, amount, transfer_amount, transfer_note, bank_id, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
            $stmt = mysqli_prepare($GLOBALS['conn'], $sql);
            mysqli_stmt_bind_param($stmt, "isiiss", $userId, $username, $amount, $transfer_amount, $transfer_note, $bank_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Yêu cầu nạp tiền đã được gửi! Vui lòng chờ admin duyệt.';
            } else {
                $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
}

$depositRequests = null;
if ($depositTableExists) {
    $sql = "SELECT * FROM deposit_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = mysqli_prepare($GLOBALS['conn'], $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $depositRequests = mysqli_stmt_get_result($stmt);
}

$banks = null;
if ($banksTableExists) {
    $banks = mysqli_query($GLOBALS['conn'], "SELECT * FROM banks WHERE status = 1 ORDER BY sort_order ASC");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead('Nạp tiền'); ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        .deposit-page {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
        }
        .quick-amounts {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .quick-amount {
            background: var(--purple-dim);
            border: 1px solid rgba(110,86,207,0.2);
            color: var(--purple);
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            cursor: pointer;
            transition: var(--transition-fast);
        }
        .quick-amount:hover {
            background: rgba(110,86,207,0.2);
            border-color: var(--purple);
        }
        .bank-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 1.1rem 1.25rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: var(--transition-fast);
        }
        .bank-card:hover { border-color: var(--border-accent); }
        .bank-card.selected { border-color: var(--green); background: var(--green-dim); }
        .bank-name { font-weight: 600; font-size: 0.95rem; margin-bottom: 2px; }
        .bank-holder { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 6px; }
        .bank-account-num { font-family: var(--font-mono); font-size: 1rem; font-weight: 700; color: var(--purple); }
        .request-item {
            background: var(--card-base);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .request-amount { font-weight: 700; font-size: 1rem; }
        .request-date { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-pending { background: var(--amber-dim); color: var(--amber); }
        .status-approved { background: var(--green-dim); color: var(--green); }
        .status-rejected { background: var(--red-dim); color: var(--red); }
        .nx-copy-btn {
            background: var(--purple);
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            transition: var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .nx-copy-btn:hover { background: #5a47b8; }
    </style>
</head>
<body>
    <?php ui_renderNavbar($username, 0, $balance, 'user'); ?>

    <div class="deposit-page">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fa-solid fa-plus-circle" style="color:var(--green);"></i>
                Nạp tiền
            </h1>
        </div>

        <div class="nexus-balance-card" style="margin-bottom:1.5rem;">
            <div class="nexus-balance-label">Số dư hiện tại</div>
            <div class="nexus-balance-value"><?php echo number_format($balance, 0, ',', '.'); ?>đ</div>
        </div>

        <?php if ($success): ?>
            <div class="nexus-alert nexus-alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="nexus-alert nexus-alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="nexus-card" style="margin-bottom:1.5rem;">
            <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-building-columns" style="color:var(--green);"></i>
                Nạp tiền qua chuyển khoản
            </h3>

            <form method="POST" action="">
                <input type="hidden" name="action" value="request_deposit">

                <div class="nexus-form-group">
                    <label class="nexus-label">Chọn ngân hàng</label>
                    <?php if ($banks !== null && mysqli_num_rows($banks) > 0): ?>
                        <?php mysqli_data_seek($banks, 0); ?>
                        <?php while ($bank = mysqli_fetch_assoc($banks)): ?>
                            <label class="bank-card" onclick="selectBank(<?php echo $bank['id']; ?>)">
                                <input type="radio" name="bank_id" value="<?php echo $bank['id']; ?>" required style="display:none;">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                                    <div>
                                        <div class="bank-name">
                                            <i class="<?php echo htmlspecialchars($bank['icon_class']); ?> me-2"></i>
                                            <?php echo htmlspecialchars($bank['bank_name']); ?>
                                        </div>
                                        <div class="bank-holder"><?php echo htmlspecialchars($bank['account_holder']); ?></div>
                                    </div>
                                    <div style="text-align:right;">
                                        <div class="bank-account-num" id="bank-account-<?php echo $bank['id']; ?>">
                                            <?php echo htmlspecialchars($bank['account_number']); ?>
                                        </div>
                                        <button type="button" class="nx-copy-btn" onclick="copyAccount(<?php echo $bank['id']; ?>, event)" style="margin-top:4px;">
                                            <i class="fa-solid fa-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>
                            </label>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="nexus-alert nexus-alert-danger">
                            <i class="fa-solid fa-exclamation-triangle"></i>
                            Hiện không có ngân hàng nào được cấu hình. Vui lòng liên hệ admin.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="nexus-form-group">
                    <label class="nexus-label">Số tiền muốn nạp</label>
                    <div class="nexus-input-wrap">
                        <i class="fa-solid fa-coins nexus-input-icon"></i>
                        <input type="number" class="nexus-input" name="amount" id="amount" min="10000" step="1000"
                               placeholder="Nhập số tiền (tối thiểu 10,000đ)" required>
                        <span style="position:absolute;right:16px;color:var(--text-muted);font-size:0.9rem;">đ</span>
                    </div>
                    <div class="quick-amounts">
                        <span class="quick-amount" onclick="setAmount(50000)">50,000đ</span>
                        <span class="quick-amount" onclick="setAmount(100000)">100,000đ</span>
                        <span class="quick-amount" onclick="setAmount(200000)">200,000đ</span>
                        <span class="quick-amount" onclick="setAmount(500000)">500,000đ</span>
                        <span class="quick-amount" onclick="setAmount(1000000)">1,000,000đ</span>
                    </div>
                </div>

                <div class="nexus-form-group">
                    <label class="nexus-label">Số tiền chuyển khoản</label>
                    <div class="nexus-input-wrap">
                        <i class="fa-solid fa-money-bill-transfer nexus-input-icon"></i>
                        <input type="number" class="nexus-input" name="transfer_amount" id="transfer_amount" min="10000" step="1000"
                               placeholder="Nhập số tiền đã chuyển khoản" required>
                        <span style="position:absolute;right:16px;color:var(--text-muted);font-size:0.9rem;">đ</span>
                    </div>
                </div>

                <div class="nexus-form-group">
                    <label class="nexus-label">Nội dung chuyển khoản</label>
                    <input type="text" class="nexus-input" name="transfer_note" id="transfer_note"
                           value="NapTien <?php echo $userId; ?>" readonly>
                    <div class="nexus-info-box-amber" style="margin-top:8px;">
                        <strong style="color:var(--amber);">Quan trọng:</strong>
                        Vui lòng copy nội dung chuyển khoản bên trên và dán vào ô "Nội dung" khi chuyển khoản. Nội dung phải khớp chính xác để hệ thống tự động xử lý.
                    </div>
                </div>

                <button type="submit" class="btn-nexus-success" style="width:100%;justify-content:center;">
                    <i class="fa-solid fa-paper-plane"></i> Gửi yêu cầu nạp tiền
                </button>
            </form>
        </div>

        <div class="nexus-card">
            <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-clock-rotate-left" style="color:var(--purple);"></i>
                Lịch sử nạp tiền
            </h3>

            <?php
            if ($depositRequests !== null && mysqli_num_rows($depositRequests) > 0):
                mysqli_data_seek($depositRequests, 0);
                while ($req = mysqli_fetch_assoc($depositRequests)):
            ?>
                <div class="request-item">
                    <div>
                        <div class="request-amount" style="color:var(--green);"><?php echo number_format($req['amount']); ?>đ</div>
                        <div class="request-date">
                            <i class="fa-solid fa-clock"></i>
                            <?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?>
                        </div>
                    </div>
                    <span class="status-badge status-<?php echo $req['status']; ?>">
                        <?php
                        switch($req['status']) {
                            case 'pending': echo '<i class="fa-solid fa-clock"></i> Chờ duyệt'; break;
                            case 'approved': echo '<i class="fa-solid fa-check"></i> Đã duyệt'; break;
                            case 'rejected': echo '<i class="fa-solid fa-times"></i> Từ chối'; break;
                        }
                        ?>
                    </span>
                </div>
            <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center;color:var(--text-muted);padding:1.5rem 0;">Chưa có yêu cầu nạp tiền nào</p>
            <?php endif; ?>
        </div>
    </div>

    <?php ui_renderFooter(); ?>
    <?php ui_renderToastContainer(); ?>
    <?php ui_renderScripts(); ?>
    <script>
        function setAmount(amount) {
            document.getElementById('amount').value = amount;
            document.getElementById('transfer_amount').value = amount;
        }

        function copyAccount(bankId, e) {
            e.stopPropagation();
            const account = document.getElementById('bank-account-' + bankId).textContent.trim();
            NexusUtils.copyToClipboard(account, 'Đã copy số tài khoản!');
        }

        function selectBank(bankId) {
            document.querySelectorAll('.bank-card').forEach(function(card) { card.classList.remove('selected'); });
            event.currentTarget.classList.add('selected');
            document.querySelector('input[name="bank_id"][value="' + bankId + '"]').checked = true;
        }

        document.getElementById('amount').addEventListener('input', function() {
            document.getElementById('transfer_amount').value = this.value;
        });
    </script>
</body>
</html>
