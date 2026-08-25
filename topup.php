<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/flash.php';

require_login();

$userId = $_SESSION['user_id'];

// Lấy số dư hiện tại của khách hàng
$stmtUser = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$myBalance = $stmtUser->fetchColumn() ?: 0;

$minimumTopup = app_setting_int('min_topup_amount', 10000, 1000);
$maximumTopup = app_setting_int('max_topup_amount', 100000000, $minimumTopup);
$expiryMinutes = app_setting_int('topup_expiry_minutes', 15, 5, 60);
$presetSource = (string) app_setting('topup_presets', '20000,50000,100000,200000,500000,1000000');
$topupPresets = array_values(array_unique(array_filter(
    array_map('intval', explode(',', $presetSource)),
    fn($value) => $value >= $minimumTopup && $value <= $maximumTopup
)));
if (!$topupPresets) {
    $topupPresets = [$minimumTopup];
}
$requestedAmount = (int) ($_GET['amount'] ?? 0);
$initialAmount = $requestedAmount >= $minimumTopup && $requestedAmount <= $maximumTopup
    ? $requestedAmount
    : ($topupPresets[min(1, count($topupPresets) - 1)] ?? $minimumTopup);
$expectedMemo = 'Tạo yêu cầu để nhận nội dung';

// Lấy lịch sử yêu cầu nạp tiền của khách hàng
$stmtHistory = $pdo->prepare("SELECT * FROM topup_requests WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$stmtHistory->execute([$userId]);
$topupHistory = $stmtHistory->fetchAll();

$pageTitle = 'Nạp tiền tài khoản - ' . SITE_NAME;
$extraCss = '
    .topup-container {
        max-width: 900px;
        margin: 40px auto;
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 32px;
    }
    .topup-card, .qr-side, .history-card {
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
    .qr-side {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
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
    }
    .qr-image {
        width: 220px;
        height: 220px;
        display: block;
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
    .timer-container {
        width: 100%;
        background: rgba(255, 255, 255, 0.1);
        overflow: hidden;
        height: 6px;
        margin-top: 8px;
    }
    .timer-bar {
        height: 100%;
        width: 100%;
        background: #ffffff;
        transition: width 1s linear;
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
    .info-label { color: var(--text-gray); }
    .info-value { color: var(--text-white); font-weight: 700; font-family: monospace; }
    .btn-copy-small {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border-color);
        color: var(--text-gray);
        padding: 2px 8px;
        font-size: 0.75rem;
        cursor: pointer;
    }
    .btn-copy-small:hover { background: #ffffff; color: #000000; }
    .polling-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: var(--text-gray);
        font-size: 0.85rem;
        margin-top: 12px;
    }
    .polling-status.expired { color: #ef4444; }
    .polling-status.success { color: #10b981; }
    .spinner {
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-top-color: #ffffff;
        border-radius: 50% !important;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
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
    .history-card { margin-top: 40px; margin-bottom: 60px; }
    .history-table { width: 100%; border-collapse: collapse; text-align: left; margin-top: 16px; }
    .history-table th { padding: 12px 16px; font-size: 0.85rem; text-transform: uppercase; color: var(--text-gray); border-bottom: 1px solid var(--border-color); }
    .history-table td { padding: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 0.95rem; color: #e5e7eb; vertical-align: middle; }
    .status-badge { display: inline-block; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
    .status-success { background-color: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid var(--success); }
    .status-pending { background-color: rgba(255, 255, 255, 0.05); color: var(--text-gray); border: 1px solid var(--border-color); }
    .status-expired { background-color: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--danger); }
    @media (max-width: 900px) { .topup-container { grid-template-columns: 1fr; } }
';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

    <main id="main-content" class="container" style="min-height: 70vh;">
        <?= render_flash() ?>

        <div class="topup-container">
            
            <!-- Cột trái: Form nhập số tiền -->
            <div class="topup-card">
                <h2 class="topup-title">Nạp tiền qua ngân hàng tự động</h2>
                
                <div class="form-group-topup">
                    <label>Chọn nhanh số tiền nạp</label>
                    <div class="amount-grid">
                        <?php foreach ($topupPresets as $preset): ?>
                            <button type="button" class="amount-btn <?= $preset === $initialAmount ? 'active' : '' ?>" onclick="selectAmount(<?= $preset ?>, this)">
                                <?= number_format($preset, 0, ',', '.') ?>đ
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group-topup">
                    <label for="custom_amount">Hoặc nhập số tiền mong muốn (đ)</label>
                    <input type="number" id="custom_amount" min="<?= $minimumTopup ?>" max="<?= $maximumTopup ?>" step="1000" value="<?= $initialAmount ?>" onchange="updateCustomAmount(this.value)">
                    <small>Tối thiểu <?= number_format($minimumTopup, 0, ',', '.') ?>đ · Tối đa <?= number_format($maximumTopup, 0, ',', '.') ?>đ</small>
                </div>

                <div style="font-size: 0.85rem; color: var(--text-gray); line-height: 1.6; margin-top: 16px;">
                    <p style="color: var(--text-white); font-weight: 600; margin-bottom: 6px;">Lưu ý quan trọng:</p>
                    <ul style="padding-left: 16px;">
                        <li>Vui lòng chuyển khoản đúng số tiền và nội dung để hệ thống tự động cộng tiền.</li>
                        <li>Yêu cầu chuyển khoản có hiệu lực trong <strong><?= $expiryMinutes ?> phút</strong>.</li>
                        <li>Mỗi yêu cầu có nội dung riêng. Vui lòng sao chép chính xác cả mã yêu cầu.</li>
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
                        <svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px;">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>GIAO DỊCH HẾT HẠN</span>
                    </div>
                </div>

                <!-- Thanh hiển thị đếm ngược thời gian -->
                <div class="timer-wrapper" id="timer_wrapper">
                    <div id="timer_text" style="color: #ffffff; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Thời gian còn lại: <strong id="countdown_timer">15:00</strong></span>
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
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="info-value" id="val_acc"><?= SEPAY_BANK_NUM ?></span>
                        <button class="btn-copy-small" onclick="copyVal('val_acc', this)">Copy</button>
                    </div>
                </div>
                <div class="info-row">
                    <span class="info-label">Chủ tài khoản</span>
                    <span class="info-value"><?= SEPAY_BANK_NAME ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số tiền</span>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="info-value" id="val_money">50,000đ</span>
                        <button class="btn-copy-small" onclick="copyValRaw('val_money_raw', this)">Copy</button>
                    </div>
                </div>
                <div class="info-row">
                    <span class="info-label">Nội dung chuyển</span>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="info-value" id="val_memo"><?= $expectedMemo ?></span>
                        <button class="btn-copy-small" onclick="copyVal('val_memo', this)">Copy</button>
                    </div>
                </div>

                <input type="hidden" id="val_money_raw" value="<?= $initialAmount ?>">

                <button type="button" id="btn_verify" class="btn-buy" style="width: 100%; margin-top: 12px;" onclick="manualCheck()">Kiểm tra giao dịch</button>
                
                <div class="polling-status" id="polling_status">
                    <div class="spinner" id="polling_spinner"></div>
                    <span id="polling_text">Đang chờ bạn quét mã thanh toán...</span>
                </div>

                <?php if (SEPAY_MOCK_MODE): ?>
                    <div class="mock-alert" id="mock_alert_box">
                        <strong>Chế độ kiểm thử đang bật.</strong> Nút “Kiểm tra giao dịch” sẽ ghi nhận số dư thử nghiệm mà không gọi ngân hàng.
                    </div>
                <?php elseif (!SEPAY_ENABLED): ?>
                    <div class="mock-alert"><strong>Cổng nạp tiền đang tạm dừng.</strong> Yêu cầu sẽ không được ghi nhận cho đến khi quản trị viên bật lại kết nối.</div>
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
                                            <span class="status-badge status-expired">Hết hạn</span>
                                        <?php elseif (in_array($h['status'], ['rejected', 'cancelled'], true)): ?>
                                            <span class="status-badge status-expired">Đã từ chối</span>
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
    </main>

    <script>
        const bankCode = "<?= SEPAY_BANK_CODE ?>";
        const bankNum = "<?= SEPAY_BANK_NUM ?>";
        const bankName = "<?= SEPAY_BANK_NAME ?>";
        const csrfToken = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE) ?>;
        const minimumTopup = <?= $minimumTopup ?>;
        const maximumTopup = <?= $maximumTopup ?>;
        const requestLifetimeSeconds = <?= $expiryMinutes * 60 ?>;
        let currentAmount = <?= $initialAmount ?>;
        let currentRequestId = 0;
        let currentMemo = "";
        let countdownSecs = 0;
        let countdownTimerInterval = null;
        let pollingInterval = null;
        let isSuccessState = false;

        function updateQR(amount, memo) {
            document.getElementById('val_money').innerText = amount.toLocaleString('vi-VN') + 'đ';
            document.getElementById('val_money_raw').value = amount;
            document.getElementById('val_memo').innerText = memo;

            const qrImg = document.getElementById('qr_img');
            const qrUrl = `https://img.vietqr.io/image/${bankCode}-${bankNum}-compact.jpg?amount=${amount}&addInfo=${encodeURIComponent(memo)}&accountName=${encodeURIComponent(bankName)}`;
            
            qrImg.style.opacity = '0.5';
            qrImg.src = qrUrl;
            qrImg.onload = function() {
                qrImg.style.opacity = '1';
            };
        }

        function createTopUpRequest(amount) {
            clearInterval(countdownTimerInterval);
            clearInterval(pollingInterval);
            
            document.getElementById('qr_expired_overlay').classList.remove('active');
            document.getElementById('btn_verify').disabled = false;
            document.getElementById('btn_verify').innerText = "Kiểm tra giao dịch";
            document.getElementById('polling_spinner').style.display = 'block';
            document.getElementById('polling_status').className = 'polling-status';
            document.getElementById('polling_text').innerText = "Đang chờ bạn quét mã thanh toán...";

            fetch('create_topup_request.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body: new URLSearchParams({amount: String(amount), csrf_token: csrfToken})
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        currentRequestId = data.request_id;
                        currentAmount = data.amount;
                        currentMemo = data.memo;
                        countdownSecs = data.expiry_seconds;
                        
                        updateQR(currentAmount, currentMemo);
                        startCountdown();
                        
                        pollingInterval = setInterval(checkPayment, 4000);
                    } else {
                        showToast(data.message || 'Lỗi khởi tạo yêu cầu nạp tiền.', true);
                    }
                })
                .catch(err => {
                    showToast("Không thể khởi tạo yêu cầu nạp tiền.", true);
                });
        }

        function startCountdown() {
            const timerBar = document.getElementById('timer_bar');
            const countdownEl = document.getElementById('countdown_timer');
            const maxSeconds = requestLifetimeSeconds;

            function updateUI() {
                if (countdownSecs <= 0) {
                    clearInterval(countdownTimerInterval);
                    clearInterval(pollingInterval);
                    
                    document.getElementById('qr_expired_overlay').classList.add('active');
                    document.getElementById('btn_verify').disabled = true;
                    countdownEl.innerText = "00:00";
                    timerBar.style.width = "0%";
                    
                    document.getElementById('polling_spinner').style.display = 'none';
                    document.getElementById('polling_status').className = 'polling-status expired';
                    document.getElementById('polling_text').innerText = "Giao dịch đã hết thời gian (" + <?= (int) $expiryMinutes ?> + " phút) và đã tự động hủy.";
                    
                    fetch('check_topup.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                        body: new URLSearchParams({request_id: String(currentRequestId), csrf_token: csrfToken})
                    }).catch(err => console.error(err));
                    return;
                }

                const mins = Math.floor(countdownSecs / 60);
                const secs = countdownSecs % 60;
                countdownEl.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                
                if (countdownSecs <= 60) {
                    document.getElementById('timer_text').style.color = '#ef4444';
                    timerBar.style.background = '#ef4444';
                } else {
                    document.getElementById('timer_text').style.color = '#ffffff';
                    timerBar.style.background = '#ffffff';
                }
                
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
            
            document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('active'));
            if(btn) {
                btn.classList.add('active');
            } else {
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
            if (amount >= minimumTopup && amount <= maximumTopup) {
                currentAmount = amount;
                
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
                showToast(`Số tiền phải từ ${minimumTopup.toLocaleString('vi-VN')}đ đến ${maximumTopup.toLocaleString('vi-VN')}đ`, true, "Số tiền không hợp lệ");
            }
        }

        function copyVal(id, btnElement) {
            const el = document.getElementById(id);
            if (!el) return;
            const txt = el.innerText;
            navigator.clipboard.writeText(txt).then(function() {
                showToast('Đã sao chép: ' + txt, false, 'Đã sao chép');
                if (btnElement) {
                    const oldText = btnElement.innerText;
                    btnElement.innerText = "✓ Copy";
                    btnElement.style.background = "#10b981";
                    btnElement.style.color = "#000000";
                    setTimeout(() => {
                        btnElement.innerText = oldText;
                        btnElement.style.background = "";
                        btnElement.style.color = "";
                    }, 1200);
                }
            });
        }

        function copyValRaw(id, btnElement) {
            const el = document.getElementById(id);
            if (!el) return;
            const txt = el.value;
            navigator.clipboard.writeText(txt).then(function() {
                showToast('Đã sao chép số tiền: ' + parseInt(txt).toLocaleString('vi-VN') + 'đ', false, 'Đã sao chép');
                if (btnElement) {
                    const oldText = btnElement.innerText;
                    btnElement.innerText = "✓ Copy";
                    btnElement.style.background = "#10b981";
                    btnElement.style.color = "#000000";
                    setTimeout(() => {
                        btnElement.innerText = oldText;
                        btnElement.style.background = "";
                        btnElement.style.color = "";
                    }, 1200);
                }
            });
        }

        function checkPayment() {
            if (currentRequestId <= 0 || isSuccessState) return;
            
            fetch('check_topup.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body: new URLSearchParams({request_id: String(currentRequestId), csrf_token: csrfToken})
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        isSuccessState = true;
                        clearInterval(countdownTimerInterval);
                        clearInterval(pollingInterval);
                        
                        document.getElementById('polling_spinner').style.display = 'none';
                        document.getElementById('polling_status').className = 'polling-status success';
                        document.getElementById('polling_text').innerText = "Thanh toán thành công!";
                        
                        showToast(data.message, false, 'Nạp tiền thành công');
                        setTimeout(() => {
                            window.location.href = 'profile.php';
                        }, 1200);
                    } else if (data.status === 'expired') {
                        clearInterval(countdownTimerInterval);
                        clearInterval(pollingInterval);
                        
                        document.getElementById('qr_expired_overlay').classList.add('active');
                        document.getElementById('btn_verify').disabled = true;
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

            fetch('check_topup.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body: new URLSearchParams({request_id: String(currentRequestId), csrf_token: csrfToken})
            })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    
                    if (data.status === 'success') {
                        isSuccessState = true;
                        clearInterval(countdownTimerInterval);
                        clearInterval(pollingInterval);
                        
                        showToast(data.message, false, 'Nạp tiền thành công');
                        setTimeout(() => {
                            window.location.href = 'profile.php';
                        }, 1200);
                    } else if (data.status === 'expired') {
                        clearInterval(countdownTimerInterval);
                        clearInterval(pollingInterval);
                        
                        document.getElementById('qr_expired_overlay').classList.add('active');
                        btn.disabled = true;
                        document.getElementById('polling_spinner').style.display = 'none';
                        document.getElementById('polling_status').className = 'polling-status expired';
                        document.getElementById('polling_text').innerText = data.message;
                        showToast(data.message, true, 'Hết hạn giao dịch');
                    } else {
                        showToast(data.message, true, 'Chưa nhận được');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    showToast("Có lỗi xảy ra khi kiểm tra giao dịch.", true, 'Lỗi kiểm tra');
                });
        }

        const initialAmount = <?= $initialAmount ?>;
        selectAmount(initialAmount, null);
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
