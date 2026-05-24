<?php
require_once __DIR__ . '/admin/config/db.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php?error=' . urlencode('Vui lòng đăng nhập để sử dụng chức năng nạp tiền.'));
    exit;
}

$userId = $_SESSION['user_id'];

// Lấy số dư hiện tại của khách hàng
$stmtUser = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$myBalance = $stmtUser->fetchColumn() ?: 0;

$expectedMemo = SEPAY_MEMO_PREFIX . ' ' . $userId;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nạp tiền tài khoản - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .topup-container {
            max-width: 900px;
            margin: 40px auto;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 32px;
        }

        .topup-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 32px;
            border-radius: var(--radius-sm);
        }

        .topup-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-white);
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
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
            background-color: #161616;
            color: var(--primary) !important;
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
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 32px;
            border-radius: var(--radius-sm);
        }

        .qr-wrapper {
            background: #ffffff;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: inline-block;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.05);
            transition: var(--transition);
        }

        .qr-image {
            width: 220px;
            height: 220px;
            display: block;
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
            border-radius: 2px;
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
            color: #f59e0b;
            font-size: 0.85rem;
            margin-top: 12px;
            font-weight: 500;
        }

        .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(245, 158, 11, 0.2);
            border-top-color: #f59e0b;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .mock-alert {
            background-color: rgba(245, 158, 11, 0.05);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #fbd38d;
            padding: 14px;
            font-size: 0.85rem;
            margin-top: 20px;
            text-align: left;
            line-height: 1.5;
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
                    <input type="number" id="custom_amount" min="1000" step="1000" value="50000" oninput="updateCustomAmount(this.value)">
                </div>

                <div style="font-size: 0.85rem; color: var(--text-gray); line-height: 1.6; margin-top: 16px;">
                    <p style="color: var(--text-white); font-weight: 600; margin-bottom: 6px;">Lưu ý quan trọng:</p>
                    <ul style="padding-left: 16px;">
                        <li>Vui lòng chuyển khoản đúng số tiền và nội dung để hệ thống tự động cộng tiền sau 1 - 3 phút.</li>
                        <li>Nội dung chuyển khoản viết liền không dấu, có chứa ID thành viên của bạn (Hệ thống đã tạo sẵn chuẩn xác).</li>
                    </ul>
                </div>
            </div>

            <!-- Cột phải: VietQR và Thông tin chuyển khoản -->
            <div class="qr-side">
                <div class="qr-wrapper" id="qr_container">
                    <!-- Sẽ được điền động bằng Javascript -->
                    <img src="" alt="VietQR" class="qr-image" id="qr_img">
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

                <button type="button" id="btn_verify" class="btn btn-primary" style="width: 100%; margin-top: 10px;" onclick="manualCheck()">Kiểm tra giao dịch</button>
                
                <div class="polling-status">
                    <div class="spinner"></div>
                    <span>Đang chờ bạn quét mã thanh toán...</span>
                </div>

                <?php if (SEPAY_API_TOKEN === 'YOUR_SEPAY_API_TOKEN'): ?>
                    <div class="mock-alert">
                        <strong>Chế độ chạy thử đang bật:</strong> Bạn chỉ cần click nút <strong>"Kiểm tra giao dịch"</strong> phía trên, hệ thống sẽ tự động giả lập cộng số tiền bạn chọn vào tài khoản để chấm điểm bài làm mà không cần giao dịch ngân hàng thực tế.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        const bankCode = "<?= SEPAY_BANK_CODE ?>";
        const bankNum = "<?= SEPAY_BANK_NUM ?>";
        const bankName = "<?= SEPAY_BANK_NAME ?>";
        const memo = "<?= $expectedMemo ?>";
        let currentAmount = 50000;
        let isChecking = false;

        function updateQR() {
            // Định dạng hiển thị số tiền
            document.getElementById('val_money').innerText = currentAmount.toLocaleString('vi-VN') + 'đ';
            document.getElementById('val_money_raw').value = currentAmount;

            // Cập nhật link VietQR
            const qrImg = document.getElementById('qr_img');
            // VietQR API endpoint: https://img.vietqr.io/image/<bank>-<bank_num>-compact.jpg?amount=<amount>&addInfo=<content>&accountName=<accountName>
            const qrUrl = `https://img.vietqr.io/image/${bankCode}-${bankNum}-compact.jpg?amount=${currentAmount}&addInfo=${encodeURIComponent(memo)}&accountName=${encodeURIComponent(bankName)}`;
            
            // Tạo hiệu ứng mờ nhẹ khi đổi ảnh để tạo cảm giác mượt mà
            qrImg.style.opacity = '0.5';
            qrImg.src = qrUrl;
            qrImg.onload = function() {
                qrImg.style.opacity = '1';
            };
        }

        function selectAmount(value, btn) {
            currentAmount = value;
            document.getElementById('custom_amount').value = value;
            
            // Xử lý active state của nút
            document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('active'));
            if(btn) btn.classList.add('active');

            updateQR();
        }

        function updateCustomAmount(value) {
            const amount = parseInt(value) || 0;
            if (amount >= 1000) {
                currentAmount = amount;
                updateQR();
            }
            
            // Bỏ active của các nút chọn nhanh
            document.querySelectorAll('.amount-btn').forEach(b => {
                const btnVal = parseInt(b.innerText.replace(/\./g, '')) || 0;
                if (btnVal !== currentAmount) {
                    b.classList.remove('active');
                } else {
                    b.classList.add('active');
                }
            });
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

        // Kiểm tra nạp tiền qua AJAX
        function checkPayment() {
            if (isChecking) return;
            
            fetch(`check_topup.php?amount=${currentAmount}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        isChecking = true; // Ngăn không chạy tiếp
                        alert(data.message);
                        window.location.href = 'profile.php?success=' + encodeURIComponent(data.message);
                    }
                })
                .catch(err => console.error("Lỗi đồng bộ giao dịch:", err));
        }

        function manualCheck() {
            const btn = document.getElementById('btn_verify');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "Đang kiểm tra...";

            fetch(`check_topup.php?amount=${currentAmount}`)
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.href = 'profile.php?success=' + encodeURIComponent(data.message);
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

        // Tự động kiểm tra sau mỗi 4 giây (Auto Polling)
        setInterval(checkPayment, 4000);

        // Khởi tạo trang lần đầu
        updateQR();
    </script>
</body>
</html>
