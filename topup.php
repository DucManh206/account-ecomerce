<?php
require_once __DIR__ . '/admin/config/db.php';

require_login();

$userId = $_SESSION['user_id'];

// Lấy số dư hiện tại của khách hàng
$stmtUser = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$myBalance = $stmtUser->fetchColumn() ?: 0;

$expectedMemo = SEPAY_MEMO_PREFIX . ' ' . $userId;

// Lấy lịch sử yêu cầu nạp tiền của khách hàng
$stmtHistory = $pdo->prepare("SELECT * FROM topup_requests WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmtHistory->execute([$userId]);
$topupHistory = $stmtHistory->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp tiền tài khoản - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Force square borders to match current minimalist theme */
        * {
            border-radius: 0 !important;
        }

        /* Nav Indicators Styles & Visited Color Fix */
        .cart-badge-indicator {
            position: relative;
            display: flex;
            align-items: center;
            color: var(--text-white) !important;
            text-decoration: none;
            font-weight: 600;
            padding: 6px 12px;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }
        .cart-badge-indicator:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #0a0a0a !important;
        }
        .cart-badge-indicator:visited {
            color: var(--text-white) !important;
        }
        .balance-indicator {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981 !important;
            padding: 6px 14px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
        }
        .balance-indicator:hover {
            background-color: rgba(16, 185, 129, 0.2);
        }
        .balance-indicator:visited {
            color: #10b981 !important;
        }
        .cart-count {
            background-color: #ef4444;
            color: white;
            border-radius: 50% !important; /* Keep circle shape for cart badge count */
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 6px;
        }

        /* Top-up layouts */
        .topup-container {
            max-width: 900px;
            margin: 40px auto;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 32px;
        }

        .topup-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 32px;
        }

        .topup-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-white);
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .amount-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .amount-btn {
            background-color: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            color: var(--text-white);
            padding: 14px 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            transition: var(--transition);
        }

        .amount-btn:hover, .amount-btn.active {
            border-color: #ffffff;
            background-color: #ffffff;
            color: #0a0a0a !important;
        }

        .form-group-topup {
            margin-bottom: 24px;
        }

        .form-group-topup label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-gray);
            font-weight: 600;
        }

        .form-group-topup input {
            width: 100%;
            background-color: rgba(255, 255, 255, 0.01);
            border: 1px solid var(--border-color);
            padding: 12px 16px;
            color: var(--text-white);
            font-size: 1.1rem;
            font-weight: 700;
            outline: none;
            transition: var(--transition);
        }

        .form-group-topup input:focus {
            border-color: #ffffff;
        }

        /* QR block side */
        .qr-side {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 32px;
        }

        .qr-relative-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .qr-wrapper {
            background: #ffffff;
            padding: 16px;
            display: inline-block;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .qr-image {
            width: 220px;
            height: 220px;
            display: block;
            transition: filter 0.3s ease;
        }

        .qr-expired-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(5px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ef4444;
            font-weight: 800;
            font-size: 1.1rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .qr-expired-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .qr-expired-overlay svg {
            margin-bottom: 12px;
            width: 44px;
            height: 44px;
            stroke: #ef4444;
        }

        /* Countdown timer styling */
        .timer-container {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            overflow: hidden;
            height: 6px;
            margin-top: 8px;
            position: relative;
        }

        .timer-bar {
            height: 100%;
            width: 100%;
            background: #ffffff;
            transition: width 1s linear;
        }

        .timer-text {
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
        }

        .timer-text.expired {
            color: #ef4444;
        }

        .timer-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            width: 100%;
            font-size: 0.95rem;
        }

        .info-row:last-of-type {
            border-bottom: none;
            margin-bottom: 16px;
        }

        .info-label {
            color: var(--text-gray);
        }

        .info-value-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            color: var(--text-white);
            font-weight: 700;
            font-family: monospace;
        }

        .btn-copy-small {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-gray);
            padding: 2px 8px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-copy-small:hover {
            background: #ffffff;
            color: #000000;
        }

        .polling-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--text-gray);
            font-size: 0.85rem;
            margin-top: 12px;
            font-weight: 500;
        }

        .polling-status.expired {
            color: #ef4444;
        }

        .polling-status.success {
            color: #10b981;
        }

        .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-top-color: #ffffff;
            border-radius: 50% !important; /* Spinner retains roundness to spin correctly */
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .mock-alert {
            background-color: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            color: var(--text-gray);
            padding: 14px;
            font-size: 0.85rem;
            margin-top: 20px;
            text-align: left;
            line-height: 1.5;
        }

        /* Custom Button verifying exactly matching index theme */
        #btn_verify {
            background-color: #ffffff;
            color: #0a0a0a;
            border: 1px solid #ffffff;
            width: 100%;
            padding: 16px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            transition: var(--transition);
        }

        #btn_verify:hover {
            background-color: transparent;
            color: #ffffff;
        }

        #btn_verify:disabled {
            background-color: #262626;
            border-color: #262626;
            color: #737373;
            cursor: not-allowed;
        }

        /* History layout */
        .history-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 32px;
            margin-top: 40px;
            margin-bottom: 60px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 16px;
        }

        .history-table th {
            padding: 12px 16px;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--text-gray);
            border-bottom: 1px solid var(--border-color);
        }

        .history-table td {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.95rem;
            color: #e5e7eb;
            vertical-align: middle;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid transparent;
            text-transform: uppercase;
        }

        .status-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-color: var(--success);
        }

        .status-pending {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-gray);
            border-color: var(--border-color);
        }

        .status-expired {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-color: var(--danger);
        }

        @media (max-width: 900px) {
            .topup-container {
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
                <a href="topup.php" class="nav-link active">Nạp tiền</a>
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

    <div class="container">
        <div class="topup-container">
            
            <!-- Cột trái: Form nhập số tiền -->
            <div class="topup-card">
                <h2 class="topup-title">Nạp tiền qua ngân hàng tự động</h2>
                
                <div class="form-group-topup">
                    <label>Chọn nhanh số tiền nạp</label>
                    <div class="amount-grid">
                        <button type="button" class="amount-btn" onclick="selectAmount(20000, this)">20.000đ</button>
                        <button type="button" class="amount-btn active" onclick="selectAmount(50000, this)">50.000đ</button>
                        <button type="button" class="amount-btn" onclick="selectAmount(100000, this)">100.000đ</button>
                        <button type="button" class="amount-btn" onclick="selectAmount(200000, this)">200.000đ</button>
                        <button type="button" class="amount-btn" onclick="selectAmount(500000, this)">500.000đ</button>
                        <button type="button" class="amount-btn" onclick="selectAmount(1000000, this)">1.000.000đ</button>
                    </div>
                </div>

                <div class="form-group-topup">
                    <label for="custom_amount">Hoặc nhập số tiền mong muốn (đ)</label>
                    <input type="number" id="custom_amount" min="1000" step="1000" value="50000" onchange="updateCustomAmount(this.value)">
                </div>

                <div style="font-size: 0.85rem; color: var(--text-gray); line-height: 1.6; margin-top: 16px;">
                    <p style="color: var(--text-white); font-weight: 600; margin-bottom: 6px;">Lưu ý quan trọng:</p>
                    <ul style="padding-left: 16px;">
                        <li>Vui lòng chuyển khoản đúng số tiền và nội dung để hệ thống tự động cộng tiền.</li>
                        <li>Yêu cầu chuyển khoản có hiệu lực tối đa là 4 phút. Sau 4 phút không khớp giao dịch sẽ tự động bị huỷ bỏ.</li>
                        <li>Nội dung chuyển khoản viết liền không dấu, có chứa ID thành viên của bạn (Hệ thống đã tạo sẵn chuẩn xác).</li>
                    </ul>
                </div>
            </div>

            <!-- Cột phải: VietQR và Thông tin chuyển khoản -->
            <div class="qr-side">
                <div class="qr-relative-container">
                    <div class="qr-wrapper" id="qr_container">
                        <img src="" alt="VietQR" class="qr-image" id="qr_img">
                    </div>
                    <div class="qr-expired-overlay" id="qr_expired_overlay">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>GIAO DỊCH HẾT HẠN</span>
                    </div>
                </div>

                <!-- Thanh hiển thị đếm ngược thời gian -->
                <div class="timer-wrapper" id="timer_wrapper">
                    <div class="timer-text" id="timer_text">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Thời gian thanh toán còn lại: <strong id="countdown_timer">04:00</strong></span>
                    </div>
                    <div class="timer-container">
                        <div class="timer-bar" id="timer_bar"></div>
                    </div>
                </div>

                <div class="info-row">
                    <span class="info-label">Ngân hàng</span>
                    <span class="info-value" style="color: var(--primary);"><?= SEPAY_BANK_CODE ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số tài khoản</span>
                    <div class="info-value-group">
                        <span class="info-value" id="val_acc"><?= SEPAY_BANK_NUM ?></span>
                        <button class="btn-copy-small" onclick="copyVal('val_acc')">Copy</button>
                    </div>
                </div>
                <div class="info-row">
                    <span class="info-label">Chủ tài khoản</span>
                    <span class="info-value"><?= SEPAY_BANK_NAME ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số tiền</span>
                    <div class="info-value-group">
                        <span class="info-value" id="val_money">50,000đ</span>
                        <button class="btn-copy-small" onclick="copyValRaw('val_money_raw')">Copy</button>
                    </div>
                </div>
                <div class="info-row">
                    <span class="info-label">Nội dung chuyển</span>
                    <div class="info-value-group">
                        <span class="info-value" id="val_memo"><?= $expectedMemo ?></span>
                        <button class="btn-copy-small" onclick="copyVal('val_memo')">Copy</button>
                    </div>
                </div>

                <input type="hidden" id="val_money_raw" value="50000">

                <button type="button" id="btn_verify" class="btn btn-primary" style="margin-top: 10px;" onclick="manualCheck()">Kiểm tra giao dịch</button>
                
                <div class="polling-status" id="polling_status">
                    <div class="spinner" id="polling_spinner"></div>
                    <span id="polling_text">Đang chờ bạn quét mã thanh toán...</span>
                </div>

                <?php if (SEPAY_API_TOKEN === 'YOUR_SEPAY_API_TOKEN'): ?>
                    <div class="mock-alert" id="mock_alert_box">
                        <strong>Chế độ chạy thử đang bật:</strong> Bạn chỉ cần click nút <strong>"Kiểm tra giao dịch"</strong> phía trên, hệ thống sẽ tự động giả lập cộng số tiền bạn chọn vào tài khoản để chấm điểm bài làm mà không cần giao dịch ngân hàng thực tế.
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Bảng lịch sử nạp tiền -->
        <div class="history-card">
            <h2 class="topup-title" style="margin-bottom: 20px;">Lịch sử yêu cầu nạp tiền</h2>
            
            <?php if (empty($topupHistory)): ?>
                <div style="text-align: center; padding: 40px 0; color: var(--text-gray); font-style: italic;">
                    Bạn chưa tạo yêu cầu nạp tiền nào.
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Mã GD (Nội dung)</th>
                                <th>Số tiền</th>
                                <th>Thời gian tạo</th>
                                <th>Cập nhật cuối</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topupHistory as $h): ?>
                                <tr>
                                    <td style="font-weight: 700; font-family: monospace;"><?= htmlspecialchars($h['memo']) ?></td>
                                    <td style="font-weight: 700; color: var(--text-white);"><?= number_format($h['amount'], 0, ',', '.') ?>đ</td>
                                    <td style="font-size: 0.85rem; color: var(--text-gray);"><?= date('d/m/Y H:i:s', strtotime($h['created_at'])) ?></td>
                                    <td style="font-size: 0.85rem; color: var(--text-gray);"><?= date('d/m/Y H:i:s', strtotime($h['updated_at'])) ?></td>
                                    <td>
                                        <?php if ($h['status'] === 'completed'): ?>
                                            <span class="status-badge status-success">Thành công</span>
                                        <?php elseif ($h['status'] === 'expired'): ?>
                                            <span class="status-badge status-expired">Đã hủy / Hết hạn</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Đang chờ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const bankCode = "<?= SEPAY_BANK_CODE ?>";
        const bankNum = "<?= SEPAY_BANK_NUM ?>";
        const bankName = "<?= SEPAY_BANK_NAME ?>";
        let currentAmount = 50000;
        let currentRequestId = 0;
        let currentMemo = "";
        let countdownSecs = 0;
        let countdownTimerInterval = null;
        let pollingInterval = null;
        let isSuccessState = false;

        function updateQR(amount, memo) {
            // Định dạng hiển thị số tiền
            document.getElementById('val_money').innerText = amount.toLocaleString('vi-VN') + 'đ';
            document.getElementById('val_money_raw').value = amount;
            document.getElementById('val_memo').innerText = memo;

            // Cập nhật link VietQR
            const qrImg = document.getElementById('qr_img');
            const qrUrl = `https://img.vietqr.io/image/${bankCode}-${bankNum}-compact.jpg?amount=${amount}&addInfo=${encodeURIComponent(memo)}&accountName=${encodeURIComponent(bankName)}`;
            
            qrImg.style.opacity = '0.5';
            qrImg.src = qrUrl;
            qrImg.onload = function() {
                qrImg.style.opacity = '1';
            };
        }

        function createTopUpRequest(amount) {
            // Xóa các interval cũ
            clearInterval(countdownTimerInterval);
            clearInterval(pollingInterval);
            
            // Đưa UI về trạng thái chờ
            document.getElementById('qr_expired_overlay').classList.remove('active');
            document.getElementById('btn_verify').disabled = false;
            document.getElementById('btn_verify').innerText = "Kiểm tra giao dịch";
            document.getElementById('timer_text').classList.remove('expired');
            document.getElementById('polling_spinner').style.display = 'block';
            document.getElementById('polling_status').className = 'polling-status';
            document.getElementById('polling_text').innerText = "Đang chờ bạn quét mã thanh toán...";

            fetch(`create_topup_request.php?amount=${amount}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        currentRequestId = data.request_id;
                        currentAmount = data.amount;
                        currentMemo = data.memo;
                        countdownSecs = data.expiry_seconds;
                        
                        updateQR(currentAmount, currentMemo);
                        startCountdown();
                        
                        // Bắt đầu tự động kiểm tra sau mỗi 4 giây
                        pollingInterval = setInterval(checkPayment, 4000);
                    } else {
                        alert(data.message || 'Lỗi khởi tạo yêu cầu nạp tiền.');
                    }
                })
                .catch(err => {
                    console.error("Lỗi kết nối API:", err);
                    alert("Không thể khởi tạo yêu cầu nạp tiền. Vui lòng thử lại.");
                });
        }

        function startCountdown() {
            const timerBar = document.getElementById('timer_bar');
            const countdownEl = document.getElementById('countdown_timer');
            const maxSeconds = 240; // 4 phút

            function updateUI() {
                if (countdownSecs <= 0) {
                    clearInterval(countdownTimerInterval);
                    clearInterval(pollingInterval);
                    
                    // Cập nhật trạng thái hết hạn trên UI
                    document.getElementById('qr_expired_overlay').classList.add('active');
                    document.getElementById('btn_verify').disabled = true;
                    document.getElementById('timer_text').classList.add('expired');
                    countdownEl.innerText = "00:00";
                    timerBar.style.width = "0%";
                    
                    document.getElementById('polling_spinner').style.display = 'none';
                    document.getElementById('polling_status').className = 'polling-status expired';
                    document.getElementById('polling_text').innerText = "Giao dịch đã hết thời gian (4 phút) và đã tự động hủy.";
                    
                    // Gọi API để cập nhật trạng thái hết hạn trong DB
                    fetch(`check_topup.php?request_id=${currentRequestId}`).catch(err => console.error(err));
                    return;
                }

                // Cập nhật thời gian
                const mins = Math.floor(countdownSecs / 60);
                const secs = countdownSecs % 60;
                countdownEl.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                
                // Thay đổi màu đếm ngược khi sắp hết giờ (dưới 60s)
                if (countdownSecs <= 60) {
                    document.getElementById('timer_text').style.color = '#ef4444';
                    timerBar.style.background = '#ef4444';
                } else {
                    document.getElementById('timer_text').style.color = '#ffffff';
                    timerBar.style.background = '#ffffff';
                }
                
                // Cập nhật thanh bar
                const pct = (countdownSecs / maxSeconds) * 100;
                timerBar.style.width = `${pct}%`;
                
                countdownSecs--;
            }

            updateUI();
            countdownTimerInterval = setInterval(updateUI, 1000);
        }

        function selectAmount(value, btn) {
            currentAmount = value;
            document.getElementById('custom_amount').value = value;
            
            // Xử lý active state của nút
            document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('active'));
            if(btn) {
                btn.classList.add('active');
            } else {
                // Tự động active nút tương ứng
                document.querySelectorAll('.amount-btn').forEach(b => {
                    const btnVal = parseInt(b.innerText.replace(/\./g, '')) || 0;
                    if (btnVal === currentAmount) {
                        b.classList.add('active');
                    }
                });
            }

            createTopUpRequest(currentAmount);
        }

        function updateCustomAmount(value) {
            const amount = parseInt(value) || 0;
            if (amount >= 1000) {
                currentAmount = amount;
                
                // Cập nhật trạng thái active cho nút chọn nhanh
                document.querySelectorAll('.amount-btn').forEach(b => {
                    const btnVal = parseInt(b.innerText.replace(/\./g, '')) || 0;
                    if (btnVal !== currentAmount) {
                        b.classList.remove('active');
                    } else {
                        b.classList.add('active');
                    }
                });

                createTopUpRequest(currentAmount);
            } else {
                alert("Số tiền nạp tối thiểu là 1.000đ");
            }
        }

        function copyVal(id) {
            const txt = document.getElementById(id).innerText;
            navigator.clipboard.writeText(txt).then(function() {
                alert('Đã sao chép: ' + txt);
            });
        }

        function copyValRaw(id) {
            const txt = document.getElementById(id).value;
            navigator.clipboard.writeText(txt).then(function() {
                alert('Đã sao chép số tiền: ' + txt);
            });
        }

        // Tự động kiểm tra nạp tiền qua AJAX (polling)
        function checkPayment() {
            if (currentRequestId <= 0 || isSuccessState) return;
            
            fetch(`check_topup.php?request_id=${currentRequestId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        isSuccessState = true;
                        clearInterval(countdownTimerInterval);
                        clearInterval(pollingInterval);
                        
                        document.getElementById('polling_spinner').style.display = 'none';
                        document.getElementById('polling_status').className = 'polling-status success';
                        document.getElementById('polling_text').innerText = "Thanh toán thành công!";
                        
                        alert(data.message);
                        window.location.href = 'profile.php?success=' + encodeURIComponent(data.message);
                    } else if (data.status === 'expired') {
                        clearInterval(countdownTimerInterval);
                        clearInterval(pollingInterval);
                        
                        document.getElementById('qr_expired_overlay').classList.add('active');
                        document.getElementById('btn_verify').disabled = true;
                        document.getElementById('timer_text').classList.add('expired');
                        document.getElementById('polling_spinner').style.display = 'none';
                        document.getElementById('polling_status').className = 'polling-status expired';
                        document.getElementById('polling_text').innerText = data.message;
                    }
                })
                .catch(err => console.error("Lỗi đồng bộ giao dịch:", err));
        }

        function manualCheck() {
            if (currentRequestId <= 0) return;
            
            const btn = document.getElementById('btn_verify');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "Đang kiểm tra...";

            fetch(`check_topup.php?request_id=${currentRequestId}`)
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    
                    if (data.status === 'success') {
                        isSuccessState = true;
                        clearInterval(countdownTimerInterval);
                        clearInterval(pollingInterval);
                        
                        alert(data.message);
                        window.location.href = 'profile.php?success=' + encodeURIComponent(data.message);
                    } else if (data.status === 'expired') {
                        clearInterval(countdownTimerInterval);
                        clearInterval(pollingInterval);
                        
                        document.getElementById('qr_expired_overlay').classList.add('active');
                        btn.disabled = true;
                        document.getElementById('timer_text').classList.add('expired');
                        document.getElementById('polling_spinner').style.display = 'none';
                        document.getElementById('polling_status').className = 'polling-status expired';
                        document.getElementById('polling_text').innerText = data.message;
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    alert("Có lỗi xảy ra khi kiểm tra giao dịch.");
                });
        }

        // Khởi tạo trang lần đầu với số tiền mặc định 50,000
        selectAmount(50000, null);
    </script>
</body>
</html>
