<?php
/**
 * Helper function to get bank icon class
 */
function getBankIcon($bankCode) {
    $icons = [
        'MB' => 'fa-solid fa-wallet',
        'TCB' => 'fa-solid fa-building-columns',
        'ICB' => 'fa-solid fa-building-columns',
        'BIDV' => 'fa-solid fa-landmark',
        'VIB' => 'fa-solid fa-v',
        'TPB' => 'fa-solid fa-piggy-bank',
        'ACB' => 'fa-solid fa-university',
        'VPB' => 'fa-solid fa-v',
        'STB' => 'fa-solid fa-building-columns',
    ];

    $bankNames = [
        'MB' => 'MB Bank',
        'TCB' => 'Techcombank',
        'ICB' => 'Vietcombank',
        'BIDV' => 'BIDV',
        'VIB' => 'VIB',
        'TPB' => 'TPBank',
        'ACB' => 'ACB',
        'VPB' => 'VPBank',
        'STB' => 'Sacombank',
    ];

    return [
        'icon' => $icons[$bankCode] ?? 'fa-solid fa-university',
        'name' => $bankNames[$bankCode] ?? 'Ngân hàng'
    ];
}

session_start();
require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../lib/ui_modules.php';
require_once __DIR__ . '/../lib/sepay_modules.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../auth/login.php');
    exit;
}

$username = $_SESSION['username'];
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$sepayConfig = sepay_getConfig();
$sepayEnabled = $sepayConfig && $sepayConfig['status'] == 1 && !empty($sepayConfig['api_token']);

// Lấy cấu hình
$transferPrefix = $sepayConfig['transfer_prefix'] ?? 'NT';
$cancelAfterMinutes = intval($sepayConfig['cancel_after_minutes'] ?? 30);

$tableCheck = mysqli_query($GLOBALS['conn'], "SHOW TABLES LIKE 'deposit_requests'");
$depositTableExists = mysqli_num_rows($tableCheck) > 0;

$sql = "SELECT balance FROM users WHERE username = ?";
$stmt = mysqli_prepare($GLOBALS['conn'], $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$balance = intval($user['balance']);

$success = '';
$error = '';

// Tạo unique_code mới
$timestamp = time();
$uniqueCode = $transferPrefix . '_' . $userId . '_' . $timestamp;

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_deposit_request') {
        if (!$depositTableExists) {
            $error = 'Hệ thống nạp tiền chưa được kích hoạt.';
        } else {
            $amount = intval($_POST['amount'] ?? 0);
            $customPrefix = trim($_POST['custom_prefix'] ?? '');
            $useCustomPrefix = isset($_POST['use_custom_prefix']) && !empty($customPrefix);

            if ($amount < 10000) {
                $error = 'Số tiền nạp tối thiểu là 10,000đ';
            } elseif ($useCustomPrefix && (!preg_match('/^[A-Z0-9]{2,10}$/', strtoupper($customPrefix)))) {
                $error = 'Prefix chỉ được chứa chữ cái và số (2-10 ký tự)';
            } else {
                // Tạo unique_code mới với prefix
                $finalPrefix = $useCustomPrefix ? strtoupper($customPrefix) : $transferPrefix;
                $finalUniqueCode = $finalPrefix . '_' . $userId . '_' . time();
                $expiresAt = date('Y-m-d H:i:s', time() + ($cancelAfterMinutes * 60));

                $sql = "INSERT INTO deposit_requests (user_id, username, amount, transfer_amount, transfer_note, unique_code, status, expires_at, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
                $stmt = mysqli_prepare($GLOBALS['conn'], $sql);
                $transferNote = $finalUniqueCode;
                mysqli_stmt_bind_param($stmt, "isiisss", $userId, $username, $amount, $amount, $transferNote, $finalUniqueCode, $expiresAt);

                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Đã tạo yêu cầu nạp tiền! Vui lòng chuyển khoản trong ' . $cancelAfterMinutes . ' phút.';
                    // Cập nhật uniqueCode để hiển thị
                    $uniqueCode = $finalUniqueCode;
                } else {
                    $error = 'Có lỗi xảy ra. Vui lòng thử lại.';
                }
            }
        }
    }
}

// Lấy danh sách ngân hàng
$banks = null;
$banksTableCheck = mysqli_query($GLOBALS['conn'], "SHOW TABLES LIKE 'banks'");
if (mysqli_num_rows($banksTableCheck) > 0) {
    $banks = mysqli_query($GLOBALS['conn'], "SELECT * FROM banks WHERE status = 1 ORDER BY sort_order ASC");
}

// Lấy lịch sử nạp tiền
$depositRequests = null;
if ($depositTableExists) {
    $sql = "SELECT * FROM deposit_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = mysqli_prepare($GLOBALS['conn'], $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $depositRequests = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php ui_renderHead('Nạp tiền'); ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        .deposit-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .page-title {
            font-size: 1.75rem;
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-title i {
            font-size: 1.5rem;
        }
        .balance-card {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .balance-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(110,86,207,0.3) 0%, transparent 70%);
            pointer-events: none;
        }
        .balance-card::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(56,189,248,0.2) 0%, transparent 70%);
            pointer-events: none;
        }
        .balance-label {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .balance-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: #fff;
            position: relative;
            z-index: 1;
        }
        .balance-value span {
            font-size: 1.25rem;
            font-weight: 600;
            opacity: 0.8;
        }
        
        /* Quick Amount Buttons */
        .quick-amounts {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .quick-amount {
            background: linear-gradient(135deg, rgba(110,86,207,0.15), rgba(110,86,207,0.05));
            border: 1px solid rgba(110,86,207,0.3);
            color: #9d8ccc;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .quick-amount:hover {
            background: linear-gradient(135deg, rgba(110,86,207,0.3), rgba(110,86,207,0.15));
            border-color: #6e56cf;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(110,86,207,0.3);
        }
        
        /* Bank Card */
        .bank-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .bank-card:hover { 
            border-color: rgba(110,86,207,0.5);
            background: rgba(110,86,207,0.05);
            transform: translateY(-2px);
        }
        .bank-card.selected { 
            border-color: #4ade80; 
            background: rgba(74,222,128,0.08);
            box-shadow: 0 0 20px rgba(74,222,128,0.1);
        }
        .bank-name { font-weight: 700; font-size: 1rem; margin-bottom: 4px; color: #fff; }
        .bank-holder { font-size: 0.85rem; color: rgba(255,255,255,0.5); margin-bottom: 8px; }
        .bank-account-num { font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; font-weight: 700; color: #9d8ccc; letter-spacing: 1px; }
        
        /* History Item */
        .request-item {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 1.1rem 1.5rem;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }
        .request-item:hover {
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.1);
        }
        .request-amount { font-weight: 700; font-size: 1.05rem; color: #4ade80; }
        .request-date { font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-top: 4px; }
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-pending { background: rgba(251,191,36,0.15); color: #fbbf24; }
        .status-approved { background: rgba(74,222,128,0.15); color: #4ade80; }
        .status-rejected { background: rgba(248,113,113,0.15); color: #f87171; }
        
        /* Copy Button */
        .nx-copy-btn {
            background: linear-gradient(135deg, #6e56cf, #8b5cf6);
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.78rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .nx-copy-btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(110,86,207,0.4);
        }
        
        /* QR Section */
        .qr-section {
            background: linear-gradient(145deg, rgba(26,26,46,0.9), rgba(15,52,96,0.6));
            border: 1px solid rgba(110,86,207,0.3);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .qr-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6e56cf, #38bdf8, #6e56cf);
        }
        .qr-title {
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
        }
        .qr-title i { color: #4ade80; font-size: 1.4rem; }
        .qr-container {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .qr-image-wrapper {
            flex-shrink: 0;
        }
        .qr-image {
            width: 220px;
            height: 220px;
            border-radius: 16px;
            border: 3px solid rgba(110,86,207,0.4);
            background: #fff;
            padding: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }
        .qr-info {
            flex: 1;
            min-width: 250px;
        }
        .qr-info-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .qr-info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .qr-info-label {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.4);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .qr-info-value {
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
        }
        .qr-info-value.amount {
            font-size: 1.5rem;
            color: #4ade80;
            font-weight: 800;
        }
        .qr-note {
            background: rgba(251,191,36,0.08);
            border: 1px solid rgba(251,191,36,0.2);
            border-radius: 14px;
            padding: 1.25rem;
            margin-top: 1.5rem;
        }
        .qr-note-title {
            font-weight: 700;
            color: #fbbf24;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qr-note-text {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
            line-height: 1.7;
        }
        
        /* Amount Input */
        .amount-input-wrapper {
            position: relative;
        }
        .amount-presets {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .amount-preset {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .amount-preset:hover, .amount-preset.active {
            background: linear-gradient(135deg, rgba(110,86,207,0.3), rgba(110,86,207,0.15));
            border-color: #6e56cf;
            color: #fff;
            transform: translateY(-2px);
        }
        
        /* Status Badge */
        .auto-badge {
            background: rgba(74,222,128,0.15);
            color: #4ade80;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .sepay-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .sepay-status.active {
            background: rgba(74,222,128,0.15);
            color: #4ade80;
            border: 1px solid rgba(74,222,128,0.3);
        }
        .sepay-status.inactive {
            background: rgba(248,113,113,0.15);
            color: #f87171;
            border: 1px solid rgba(248,113,113,0.3);
        }
        
        /* Card Title */
        .card-title {
            font-size: 1.15rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
        }
        .card-title i { font-size: 1.2rem; }
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
            <?php if ($sepayEnabled): ?>
                <span class="sepay-status active">
                    <i class="fa-solid fa-bolt"></i>
                    Tự động - SePay
                </span>
            <?php else: ?>
                <span class="sepay-status inactive">
                    <i class="fa-solid fa-clock"></i>
                    Thủ công - Chờ duyệt
                </span>
            <?php endif; ?>
        </div>

        <div class="balance-card">
            <div class="balance-label">Số dư hiện tại</div>
            <div class="balance-value"><?php echo number_format($balance, 0, ',', '.'); ?><span>đ</span></div>
        </div>

        <?php if ($success): ?>
            <div class="nexus-alert nexus-alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="nexus-alert nexus-alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($sepayEnabled): ?>
        <!-- SEPAY MODE: Auto Deposit with QR Code -->
        <form method="POST" id="sepayForm">
            <div class="qr-section">
                <div class="qr-title">
                    <i class="fa-solid fa-qrcode"></i>
                    Quét mã QR để nạp tiền
                </div>
                
                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <label class="nexus-label">Số tiền muốn nạp</label>
                        <div class="amount-input-wrapper">
                            <input type="number" class="nexus-input" name="amount" id="amount" min="10000" step="1000"
                                   placeholder="Nhập số tiền (tối thiểu 10,000đ)" required>
                            <span style="position:absolute;right:16px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.9rem;">đ</span>
                        </div>
                        <div class="amount-presets">
                            <button type="button" class="amount-preset" onclick="setAmount(50000)">50,000đ</button>
                            <button type="button" class="amount-preset" onclick="setAmount(100000)">100,000đ</button>
                            <button type="button" class="amount-preset" onclick="setAmount(200000)">200,000đ</button>
                            <button type="button" class="amount-preset" onclick="setAmount(500000)">500,000đ</button>
                            <button type="button" class="amount-preset" onclick="setAmount(1000000)">1,000,000đ</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="nexus-label">Ngân hàng nhận</label>
                        <div class="nexus-input" style="background: var(--card-hover); cursor: default;">
                            <i class="<?php echo getBankIcon($sepayConfig['bank_code'])['icon']; ?> me-2" style="color: var(--purple);"></i>
                            <?php echo getBankIcon($sepayConfig['bank_code'])['name']; ?>
                        </div>
                    </div>
                </div>

                <div class="qr-container mt-3">
                    <div class="qr-image-wrapper">
                        <img src="" id="qrCodeImage" class="qr-image" alt="QR Code" style="display:none;">
                        <div id="qrPlaceholder" class="qr-image d-flex align-items-center justify-content-center" style="background: var(--card-hover);">
                            <span class="text-muted">Nhập số tiền để hiển thị QR</span>
                        </div>
                    </div>
                    <div class="qr-info">
                        <div class="qr-info-item">
                            <div class="qr-info-label">Ngân hàng</div>
                            <div class="qr-info-value">
                                <i class="<?php echo getBankIcon($sepayConfig['bank_code'])['icon']; ?>"></i>
                                <?php echo getBankIcon($sepayConfig['bank_code'])['name']; ?>
                            </div>
                        </div>
                        <div class="qr-info-item">
                            <div class="qr-info-label">Số tài khoản</div>
                            <div class="qr-info-value" style="font-family: var(--font-mono);">
                                <?php echo htmlspecialchars($sepayConfig['account_number'] ?? ''); ?>
                                <button type="button" class="nx-copy-btn" onclick="copyAccount()">
                                    <i class="fa-solid fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        <div class="qr-info-item">
                            <div class="qr-info-label">Tên tài khoản</div>
                            <div class="qr-info-value">
                                <?php echo htmlspecialchars($sepayConfig['account_holder'] ?? ''); ?>
                            </div>
                        </div>
                        <div class="qr-info-item">
                            <div class="qr-info-label">Số tiền</div>
                            <div class="qr-info-value amount" id="qrAmountDisplay">
                                0đ
                            </div>
                        </div>
                        <div class="qr-info-item">
                            <div class="qr-info-label">Nội dung chuyển khoản <span class="auto-badge">QUAN TRỌNG</span></div>
                            <div class="qr-info-value" style="font-family: var(--font-mono); color: var(--amber);">
                                <?php echo htmlspecialchars($transferNote); ?>
                                <button type="button" class="nx-copy-btn" onclick="copyNote()" style="background: var(--amber);">
                                    <i class="fa-solid fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="qr-note">
                    <div class="qr-note-title">
                        <i class="fa-solid fa-info-circle"></i>
                        Hướng dẫn nạp tiền tự động
                    </div>
                    <div class="qr-note-text">
                        1. Nhập số tiền bạn muốn nạp<br>
                        2. Quét mã QR bằng ứng dụng ngân hàng<br>
                        3. <strong>QUAN TRỌNG:</strong> Copy và dán chính xác nội dung chuyển khoản "<strong><?php echo htmlspecialchars($transferNote); ?></strong>"<br>
                        4. Sau khi chuyển khoản thành công, tiền sẽ được cộng vào tài khoản trong vài giây!
                    </div>
                </div>
            </div>
        </form>
        <?php else: ?>
        <!-- MANUAL MODE: Old deposit request -->
        <div class="nexus-card" style="background:rgba(26,26,46,0.6);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:1.5rem;margin-bottom:1.5rem;">
            <h3 class="card-title">
                <i class="fa-solid fa-building-columns" style="color:var(--green);"></i>
                Nạp tiền qua chuyển khoản
            </h3>

            <div class="nexus-alert nexus-alert-warning mb-3">
                <i class="fa-solid fa-info-circle"></i>
                Tính năng nạp tiền tự động chưa được kích hoạt. Yêu cầu sẽ cần admin duyệt thủ công.
            </div>

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
                                        <button type="button" class="nx-copy-btn" onclick="copyAccountOld(<?php echo $bank['id']; ?>, event)" style="margin-top:4px;">
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
                        <span class="quick-amount" onclick="setAmountOld(50000)">50,000đ</span>
                        <span class="quick-amount" onclick="setAmountOld(100000)">100,000đ</span>
                        <span class="quick-amount" onclick="setAmountOld(200000)">200,000đ</span>
                        <span class="quick-amount" onclick="setAmountOld(500000)">500,000đ</span>
                        <span class="quick-amount" onclick="setAmountOld(1000000)">1,000,000đ</span>
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
        <?php endif; ?>

        <div class="nexus-card" style="background:rgba(26,26,46,0.6);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:1.5rem;">
            <h3 class="card-title">
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
                <p style="text-align:center;color:rgba(255,255,255,0.4);padding:2rem 0;font-size:0.95rem;">
                    <i class="fa-solid fa-receipt" style="font-size:2rem;display:block;margin-bottom:0.75rem;opacity:0.5;"></i>
                    Chưa có yêu cầu nạp tiền nào
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php ui_renderFooter(); ?>
    <?php ui_renderToastContainer(); ?>
    <?php ui_renderScripts(); ?>
    
    <?php if ($sepayEnabled): ?>
    <script>
        const sepayConfig = {
            bankCode: '<?php echo $sepayConfig['bank_code'] ?? ''; ?>',
            accountNumber: '<?php echo $sepayConfig['account_number'] ?? ''; ?>',
            transferNote: '<?php echo $transferNote; ?>',
            minAmount: <?php echo intval($sepayConfig['min_amount'] ?? 10000); ?>
        };

        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
        }

        function setAmount(amount) {
            document.getElementById('amount').value = amount;
            updateQRCode(amount);
        }

        function updateQRCode(amount) {
            const qrImage = document.getElementById('qrCodeImage');
            const qrPlaceholder = document.getElementById('qrPlaceholder');
            const amountDisplay = document.getElementById('qrAmountDisplay');

            amount = parseInt(amount) || 0;
            amountDisplay.textContent = formatMoney(amount);

            if (amount >= sepayConfig.minAmount) {
                const qrUrl = 'https://img.vietqr.io/image/' + sepayConfig.bankCode + '-' + sepayConfig.accountNumber + '-compact.png?amount=' + amount + '&addInfo=' + encodeURIComponent(sepayConfig.transferNote);
                qrImage.src = qrUrl;
                qrImage.style.display = 'block';
                qrPlaceholder.style.display = 'none';
            } else {
                qrImage.style.display = 'none';
                qrPlaceholder.style.display = 'flex';
                qrPlaceholder.innerHTML = '<span class="text-muted">Số tiền tối thiểu: ' + formatMoney(sepayConfig.minAmount) + '</span>';
            }
        }

        function copyAccount() {
            navigator.clipboard.writeText(sepayConfig.accountNumber).then(function() {
                NexusUtils.showToast('Đã copy số tài khoản!', 'success');
            });
        }

        function copyNote() {
            navigator.clipboard.writeText(sepayConfig.transferNote).then(function() {
                NexusUtils.showToast('Đã copy nội dung chuyển khoản!', 'success');
            });
        }

        document.getElementById('amount').addEventListener('input', function() {
            updateQRCode(this.value);
        });

        // Auto-refresh balance every 30 seconds when on this page
        setInterval(function() {
            fetch('/api/check-balance.php?user_id=<?php echo $userId; ?>')
                .then(res => res.json())
                .then(data => {
                    if (data.balance !== undefined) {
                        document.querySelector('.nexus-balance-value').textContent = formatMoney(data.balance).replace('đ', '') + 'đ';
                    }
                })
                .catch(() => {});
        }, 30000);
    </script>
    <?php else: ?>
    <script>
        function setAmountOld(amount) {
            document.getElementById('amount').value = amount;
            document.getElementById('transfer_amount').value = amount;
        }

        function copyAccountOld(bankId, e) {
            e.stopPropagation();
            const account = document.getElementById('bank-account-' + bankId).textContent.trim();
            navigator.clipboard.writeText(account).then(function() {
                NexusUtils.showToast('Đã copy số tài khoản!', 'success');
            });
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
    <?php endif; ?>
</body>
</html>
