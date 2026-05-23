<?php
/**
 * NEXUS STORE - SePay API Integration Module
 * 
 * Tích hợp API SePay để tự động xử lý nạp tiền qua chuyển khoản ngân hàng
 * 
 * API Documentation: https://my.sepay.vn/userapi/
 */

require_once __DIR__ . '/../database/connect.php';

/**
 * Lấy cấu hình SePay từ database
 */
function sepay_getConfig() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM sepay_config LIMIT 1");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row;
    }
    return null;
}

/**
 * Cập nhật cấu hình SePay
 */
function sepay_saveConfig($data) {
    global $conn;

    $fields = ['api_token', 'account_number', 'account_holder', 'bank_code', 'auto_process',
                'min_amount', 'max_amount', 'webhook_secret', 'status',
                'transfer_prefix', 'check_interval_minutes', 'cancel_after_minutes'];
    $updates = [];

    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $val = $conn->real_escape_string($data[$field]);
            $updates[] = "`$field` = '$val'";
        }
    }

    if (empty($updates)) return false;

    $sql = "UPDATE sepay_config SET " . implode(', ', $updates) . ", updated_at = NOW() LIMIT 1";
    return mysqli_query($conn, $sql);
}

/**
 * Gọi API SePay với Bearer Token
 */
function sepay_apiCall($endpoint, $params = [], $method = 'GET') {
    $config = sepay_getConfig();
    if (!$config || empty($config['api_token'])) {
        return ['error' => 'SePay chưa được cấu hình API token'];
    }
    
    $url = 'https://my.sepay.vn/userapi/' . ltrim($endpoint, '/');
    
    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['api_token']
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => 'Lỗi cURL: ' . $error];
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode !== 200 || !isset($data['status']) || $data['status'] !== 200) {
        return [
            'error' => 'API Error',
            'http_code' => $httpCode,
            'message' => $data['messages'] ?? $data['error'] ?? 'Unknown error',
            'raw' => $response
        ];
    }
    
    return $data;
}

/**
 * Lấy danh sách giao dịch từ SePay
 */
function sepay_getTransactions($params = []) {
    $defaults = [
        'limit' => 100,
    ];
    
    $queryParams = array_merge($defaults, $params);
    return sepay_apiCall('transactions/list', $queryParams);
}

/**
 * Lấy giao dịch mới nhất chưa xử lý
 */
function sepay_getUnprocessedTransactions($limit = 50) {
    global $conn;
    
    $sql = "SELECT * FROM sepay_transactions 
            WHERE status = 'pending' 
            ORDER BY transaction_date DESC 
            LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $transactions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    
    return $transactions;
}

/**
 * Lưu giao dịch SePay vào database
 */
function sepay_saveTransaction($transaction) {
    global $conn;
    
    $sepayId = $conn->real_escape_string($transaction['id'] ?? '');
    $accountNumber = $conn->real_escape_string($transaction['account_number'] ?? '');
    $bankCode = $conn->real_escape_string($transaction['bank_code'] ?? '');
    $transactionDate = $conn->real_escape_string($transaction['transaction_date'] ?? '');
    $amountIn = floatval($transaction['amount_in'] ?? 0);
    $amountOut = floatval($transaction['amount_out'] ?? 0);
    $accumulated = floatval($transaction['accumulated'] ?? 0);
    $content = $conn->real_escape_string($transaction['transaction_content'] ?? '');
    $reference = $conn->real_escape_string($transaction['reference_number'] ?? '');
    $code = $conn->real_escape_string($transaction['code'] ?? '');
    $subAccount = $conn->real_escape_string($transaction['sub_account'] ?? '');
    $bankAccountId = $conn->real_escape_string($transaction['bank_account_id'] ?? '');
    $rawData = $conn->real_escape_string(json_encode($transaction));
    
    // Kiểm tra đã tồn tại chưa
    $check = mysqli_query($conn, "SELECT id, status FROM sepay_transactions WHERE sepay_id = '$sepayId' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        $existing = mysqli_fetch_assoc($check);
        return ['existed' => true, 'id' => $existing['id'], 'status' => $existing['status']];
    }
    
    $sql = "INSERT INTO sepay_transactions 
            (sepay_id, account_number, bank_code, transaction_date, amount_in, amount_out, 
             accumulated, transaction_content, reference_number, code, sub_account, 
             bank_account_id, raw_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssdddssssss", 
        $sepayId, $accountNumber, $bankCode, $transactionDate, 
        $amountIn, $amountOut, $accumulated, $content, 
        $reference, $code, $subAccount, $bankAccountId, $rawData);
    
    if (mysqli_stmt_execute($stmt)) {
        return ['existed' => false, 'id' => mysqli_insert_id($conn)];
    }
    
    return ['error' => mysqli_error($conn)];
}

/**
 * Xử lý một giao dịch SePay - tìm user và nạp tiền
 * QUY TẮC MỚI:
 * 1. Chỉ khớp nếu nội dung chuyển khoản chứa unique_code VÀ số tiền khớp với deposit request
 * 2. Không tự động cộng tiền nếu không tìm thấy request khớp
 * 3. Pending request hết hạn sẽ bị huỷ tự động
 */
function sepay_processTransaction($transactionId) {
    global $conn;

    $sql = "SELECT * FROM sepay_transactions WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $transactionId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $transaction = mysqli_fetch_assoc($result);

    if (!$transaction) {
        return ['success' => false, 'message' => 'Không tìm thấy giao dịch'];
    }

    if ($transaction['status'] !== 'pending') {
        return ['success' => false, 'message' => 'Giao dịch đã được xử lý trước đó'];
    }

    $amount = floatval($transaction['amount_in']);
    if ($amount <= 0) {
        mysqli_query($conn, "UPDATE sepay_transactions SET status = 'failed', processed_at = NOW() WHERE id = $transactionId");
        return ['success' => false, 'message' => 'Không phải giao dịch tiền vào'];
    }

    $content = trim($transaction['transaction_content'] ?? '');

    // Lấy cấu hình
    $config = sepay_getConfig();

    // Kiểm tra số tiền trong giới hạn
    $minAmount = intval($config['min_amount'] ?? 10000);
    $maxAmount = intval($config['max_amount'] ?? 500000000);
    $amountInt = intval($amount);

    if ($amountInt < $minAmount) {
        mysqli_query($conn, "UPDATE sepay_transactions SET status = 'failed', processed_at = NOW() WHERE id = $transactionId");
        return ['success' => false, 'message' => "Số tiền $amountInt nhỏ hơn giới hạn $minAmount"];
    }

    if ($amountInt > $maxAmount) {
        mysqli_query($conn, "UPDATE sepay_transactions SET status = 'failed', processed_at = NOW() WHERE id = $transactionId");
        return ['success' => false, 'message' => "Số tiền $amountInt vượt quá giới hạn $maxAmount"];
    }

    // TÌM KIẾM DEPOSIT REQUEST KHỚP
    // Format unique_code: {PREFIX}_{ID}_{TIMESTAMP}
    // Ví dụ: NT_1_1715432100
    $configPrefix = $config['transfer_prefix'] ?? 'NT';

    // Tìm tất cả deposit request pending chưa hết hạn với cùng số tiền
    $sql = "SELECT dr.*, u.username, u.balance as user_balance
            FROM deposit_requests dr
            JOIN users u ON dr.user_id = u.id
            WHERE dr.status = 'pending'
            AND dr.amount = ?
            AND (dr.expires_at IS NULL OR dr.expires_at > NOW())
            ORDER BY dr.created_at ASC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $amountInt);
    mysqli_stmt_execute($stmt);
    $depResult = mysqli_stmt_get_result($stmt);

    $matchedDeposit = null;

    // Tìm request khớp với unique_code trong nội dung
    while ($dep = mysqli_fetch_assoc($depResult)) {
        $uniqueCode = $dep['unique_code'];
        // Kiểm tra xem nội dung có chứa unique_code không
        if (stripos($content, $uniqueCode) !== false) {
            $matchedDeposit = $dep;
            break;
        }
    }

    // Nếu không khớp unique_code, không làm gì cả
    if (!$matchedDeposit) {
        // Giao dịch không khớp với request nào - đánh dấu failed
        mysqli_query($conn, "UPDATE sepay_transactions SET status = 'failed', processed_at = NOW() WHERE id = $transactionId");
        return [
            'success' => false,
            'message' => 'Không tìm thấy yêu cầu nạp tiền khớp với nội dung và số tiền',
            'amount' => $amountInt,
            'content' => $content
        ];
    }

    // TÌM THẤY REQUEST KHỚP - XỬ LÝ
    $userId = $matchedDeposit['user_id'];
    $depositId = $matchedDeposit['id'];
    $balanceBefore = intval($matchedDeposit['user_balance']);

    // Cập nhật deposit request thành approved
    mysqli_query($conn, "UPDATE deposit_requests SET status = 'approved', sepay_transaction_id = '" .
        $conn->real_escape_string($transaction['sepay_id']) . "', processed_at = NOW() WHERE id = $depositId");

    // Cập nhật giao dịch SePay
    $sql = "UPDATE sepay_transactions SET status = 'matched', user_id = ?, matched_deposit_id = ?, processed_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $userId, $depositId, $transactionId);
    mysqli_stmt_execute($stmt);

    // Cộng tiền cho user
    $balanceAfter = $balanceBefore + $amountInt;
    mysqli_query($conn, "UPDATE users SET balance = balance + $amountInt WHERE id = $userId");

    // Ghi log giao dịch
    $desc = "Nap tien tu SePay (#" . $transaction['sepay_id'] . ")";
    mysqli_query($conn, "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description)
                         VALUES ($userId, $amountInt, $balanceBefore, $balanceAfter, 'topup', '$desc')");

    return [
        'success' => true,
        'message' => 'Da xu ly thanh cong',
        'user_id' => $userId,
        'username' => $matchedDeposit['username'],
        'amount' => $amountInt,
        'deposit_id' => $depositId
    ];
}

/**
 * Huỷ các deposit request pending đã hết hạn
 */
function sepay_expireOldRequests() {
    global $conn;

    $sql = "UPDATE deposit_requests
            SET status = 'expired'
            WHERE status = 'pending'
            AND expires_at IS NOT NULL
            AND expires_at < NOW()";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        return mysqli_affected_rows($conn);
    }
    return 0;
}

/**
 * Đồng bộ giao dịch từ SePay và xử lý tự động
 */
function sepay_syncAndProcess($limit = 100) {
    global $conn;
    
    $config = sepay_getConfig();
    if (!$config || empty($config['api_token']) || $config['status'] != 1) {
        return ['success' => false, 'message' => 'SePay chua duoc kich hoat'];
    }
    
    // Lấy giao dịch từ SePay
    $response = sepay_getTransactions(['limit' => $limit]);
    
    if (isset($response['error'])) {
        return ['success' => false, 'message' => $response['error']];
    }
    
    $transactions = $response['transactions'] ?? [];
    $saved = 0;
    $processed = 0;
    $duplicates = 0;
    
    foreach ($transactions as $tx) {
        $result = sepay_saveTransaction($tx);
        
        if (isset($result['existed']) && $result['existed']) {
            $duplicates++;
        } elseif (isset($result['id'])) {
            $saved++;
            
            // Xử lý tự động nếu được bật
            if ($config['auto_process'] == 1) {
                $processResult = sepay_processTransaction($result['id']);
                if ($processResult['success']) {
                    $processed++;
                }
            }
        }
    }
    
    // Cập nhật last_sync_at
    mysqli_query($conn, "UPDATE sepay_config SET last_sync_at = NOW()");
    
    return [
        'success' => true,
        'saved' => $saved,
        'processed' => $processed,
        'duplicates' => $duplicates,
        'total_received' => count($transactions)
    ];
}

/**
 * Tạo URL QR Code cho VietQR
 * Format: img.vietqr.io/{bank_code}-{account_number}-{template}.png
 * 
 * @param string $bankCode Mã ngân hàng (MB, TCB, VIB,...)
 * @param string $accountNumber Số tài khoản
 * @param int $amount Số tiền (optional)
 * @param string $description Nội dung chuyển khoản (optional)
 * @return string URL QR
 */
function sepay_generateQRUrl($bankCode, $accountNumber, $amount = 0, $description = '') {
    $bankCode = strtoupper(trim($bankCode));
    $accountNumber = trim($accountNumber);
    
    // Template: compact | qr_only | embedded
    $template = 'compact';
    
    $url = "https://img.vietqr.io/image/{$bankCode}-{$accountNumber}-{$template}.png";
    
    $params = [];
    
    if ($amount > 0) {
        $params['amount'] = $amount;
    }
    
    if (!empty($description)) {
        $params['addInfo'] = urlencode($description);
    }
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Lấy danh sách bank codes được hỗ trợ
 */
function sepay_getSupportedBanks() {
    return [
        'MB' => ['name' => 'MB Bank', 'brand' => 'MB'],
        'TCB' => ['name' => 'Techcombank', 'brand' => 'TCB'],
        'ICB' => ['name' => 'Vietcombank', 'brand' => 'VCB'],
        'BIDV' => ['name' => 'BIDV', 'brand' => 'BIDV'],
        'VIB' => ['name' => 'VIB', 'brand' => 'VIB'],
        'TPB' => ['name' => 'TPBank', 'brand' => 'TPB'],
        'ACB' => ['name' => 'ACB', 'brand' => 'ACB'],
        'VPB' => ['name' => 'VPBank', 'brand' => 'VPB'],
        'STB' => ['name' => 'Sacombank', 'brand' => 'STB'],
        'AGB' => ['name' => 'Agribank', 'brand' => 'AGB'],
        'OceanBank' => ['name' => 'OceanBank', 'brand' => 'OceanBank'],
        'SCB' => ['name' => 'SCB', 'brand' => 'SCB'],
        'SHB' => ['name' => 'SHB', 'brand' => 'SHB'],
        'MSB' => ['name' => 'Maritime Bank', 'brand' => 'MSB'],
        'HDB' => ['name' => 'HDBank', 'brand' => 'HDB'],
        'LPB' => ['name' => 'LienVietPostBank', 'brand' => 'LPB'],
        'PGP' => ['name' => 'PGPBank', 'brand' => 'PGP'],
        'PVCB' => ['name' => 'PVCombank', 'brand' => 'PVCB'],
        'VBSP' => ['name' => 'VBSP', 'brand' => 'VBSP'],
        'VAB' => ['name' => 'VietABank', 'brand' => 'VAB'],
        'NAMA' => ['name' => 'NamA Bank', 'brand' => 'NAMA'],
        'COOPBANK' => ['name' => 'Co-opbank', 'brand' => 'COOPBANK'],
        'NCB' => ['name' => 'NCB', 'brand' => 'NCB'],
        'DOB' => ['name' => 'DongA Bank', 'brand' => 'DOB'],
        'EIB' => ['name' => 'Eximbank', 'brand' => 'EIB'],
        'GB' => ['name' => 'GPBank', 'brand' => 'GB'],
        'KLB' => ['name' => 'KienLongBank', 'brand' => 'KLB'],
        'MBB' => ['name' => 'Military Bank', 'brand' => 'MBB'],
        'PGB' => ['name' => 'PGBank', 'brand' => 'PGB'],
        'SGB' => ['name' => 'Saigon Bank', 'brand' => 'SGB'],
        'TPB' => ['name' => 'TPBank', 'brand' => 'TPB'],
        'VCCB' => ['name' => 'Vietnam Capital Bank', 'brand' => 'VCCB'],
        'VIETBANK' => ['name' => 'VietBank', 'brand' => 'VIETBANK'],
        'VRB' => ['name' => 'VietinBank', 'brand' => 'VRB'],
    ];
}

/**
 * Chuyển tên ngân hàng thành mã ngắn
 */
function sepay_bankNameToCode($bankName) {
    $bankName = strtolower(trim($bankName));
    
    $mapping = [
        'mb bank' => 'MB',
        'mbbank' => 'MB',
        'techcombank' => 'TCB',
        'techcom' => 'TCB',
        'vietcombank' => 'ICB',
        'vcb' => 'ICB',
        'bidv' => 'BIDV',
        'vib' => 'VIB',
        'tpbank' => 'TPB',
        'acb' => 'ACB',
        'vpbank' => 'VPB',
        'sacombank' => 'STB',
        'agribank' => 'AGB',
        'shb' => 'SHB',
        'maritime' => 'MSB',
        'hdbank' => 'HDB',
        'lienvietpostbank' => 'LPB',
        'nam a bank' => 'NAMA',
        'nama' => 'NAMA',
        'eximbank' => 'EIB',
        'vietinbank' => 'VRB',
    ];
    
    return $mapping[$bankName] ?? '';
}

/**
 * Lấy thống kê SePay
 */
function sepay_getStats() {
    global $conn;
    
    $stats = [
        'total_transactions' => 0,
        'pending_count' => 0,
        'matched_count' => 0,
        'today_amount' => 0,
        'month_amount' => 0,
    ];
    
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM sepay_transactions");
    if ($r) $stats['total_transactions'] = intval(mysqli_fetch_assoc($r)['c']);
    
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM sepay_transactions WHERE status = 'pending'");
    if ($r) $stats['pending_count'] = intval(mysqli_fetch_assoc($r)['c']);
    
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM sepay_transactions WHERE status = 'matched'");
    if ($r) $stats['matched_count'] = intval(mysqli_fetch_assoc($r)['c']);
    
    $r = mysqli_query($conn, "SELECT COALESCE(SUM(amount_in), 0) as total FROM sepay_transactions WHERE status = 'matched' AND DATE(transaction_date) = CURDATE()");
    if ($r) $stats['today_amount'] = floatval(mysqli_fetch_assoc($r)['total']);
    
    $r = mysqli_query($conn, "SELECT COALESCE(SUM(amount_in), 0) as total FROM sepay_transactions WHERE status = 'matched' AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())");
    if ($r) $stats['month_amount'] = floatval(mysqli_fetch_assoc($r)['total']);
    
    return $stats;
}

/**
 * Lấy danh sách giao dịch SePay đã xử lý
 */
function sepay_getProcessedTransactions($limit = 50, $offset = 0) {
    global $conn;
    
    $limit = intval($limit);
    $offset = intval($offset);
    
    $sql = "SELECT st.*, u.username 
            FROM sepay_transactions st
            LEFT JOIN users u ON st.user_id = u.id
            WHERE st.status = 'matched'
            ORDER BY st.processed_at DESC
            LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($conn, $sql);
    $transactions = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    
    return $transactions;
}
