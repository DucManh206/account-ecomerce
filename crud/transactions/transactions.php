<?php
// CRUD Module for table: transactions
require_once __DIR__ . '/../../config/db.php';

// From admin_transaction_modules.php

/**
 * Admin Transaction Modules
 */

require_once __DIR__ . '/../../config/db.php';

/* =====================================================================
   SỐ DƯ
   ===================================================================== */
function admin_addBalance($userId, $amount) {
    global $conn;

    $userId = intval($userId);
    $amount = intval($amount);
    if ($amount <= 0) return false;

    $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'ii', $amount, $userId);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function admin_deductBalance($userId, $amount) {
    global $conn;

    $userId = intval($userId);
    $amount = intval($amount);
    if ($amount <= 0) return false;

    $sql = "UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'iii', $amount, $userId, $amount);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function admin_getBalance($userId) {
    global $conn;

    $sql = "SELECT balance FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return 0;

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $balance = 0;
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $balance = intval($row['balance']);
    }
    mysqli_stmt_close($stmt);

    return $balance;
}

/* =====================================================================
   GIAO DỊCH
   ===================================================================== */
function admin_recordTransaction($userId, $amount, $balanceBefore, $balanceAfter, $type, $description) {
    global $conn;

    $uid   = intval($userId);
    $amount = intval($amount);

    $sql = "INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, 'iiisss', $uid, $amount, $balanceBefore, $balanceAfter, $type, $description);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

/* =====================================================================
   LỊCH SỬ GIAO DỊCH (ADMIN)
   ===================================================================== */
function admin_getTransactions($limit = 50, $offset = 0) {
    global $conn;

    $limit  = intval($limit);
    $offset = intval($offset);

    $sql = "SELECT t.*, u.username
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];

    mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $transactions = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $transactions[] = $row;
        }
    }
    mysqli_stmt_close($stmt);

    return $transactions;
}

/* =====================================================================
   KHO TÀI KHOẢN
   ===================================================================== */
function admin_addAccountStock($productId, $accountData) {
    global $conn;

    $productId  = intval($productId);

    // Normalize common key aliases before saving
    $decoded = json_decode($accountData, true);
    if (is_array($decoded)) {
        if (isset($decoded['user']) && !isset($decoded['account'])) {
            $decoded['account'] = $decoded['user'];
            unset($decoded['user']);
        }
        if (isset($decoded['pass']) && !isset($decoded['password'])) {
            $decoded['password'] = $decoded['pass'];
            unset($decoded['pass']);
        }
        $accountData = json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }

    $accountEsc = mysqli_real_escape_string($conn, $accountData);
    $sql = "INSERT INTO account_stock (product_id, account_data, status) VALUES ($productId, '$accountEsc', 'available')";
    return mysqli_query($conn, $sql);
}

function admin_addAccountStockBulk($productId, $accountsArray) {
    global $conn;

    $productId = intval($productId);
    $count = 0;

    $stmt = mysqli_prepare($conn, "INSERT INTO account_stock (product_id, account_data, status) VALUES (?, ?, 'available')");
    if (!$stmt) return 0;

    foreach ($accountsArray as $accountData) {
        $accountData = trim($accountData);
        if (empty($accountData)) continue;
        mysqli_stmt_bind_param($stmt, 'is', $productId, $accountData);
        if (mysqli_stmt_execute($stmt)) {
            $count++;
        }
    }
    mysqli_stmt_close($stmt);

    return $count;
}

function admin_getAccountStockByProduct($productId) {
    global $conn;

    $productId = intval($productId);
    $sql = "SELECT id, product_id, account_data, status, created_at, sold_at
            FROM account_stock
            WHERE product_id = $productId
            ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);

    $accounts = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $accounts[] = $row;
        }
    }
    return $accounts;
}

function admin_getAccountStockStats() {
    global $conn;

    $stats = ['total_accounts' => 0, 'available_accounts' => 0, 'sold_accounts' => 0];

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM account_stock");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $stats['total_accounts'] = intval($row['cnt']);
    }

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM account_stock WHERE status = 'available'");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $stats['available_accounts'] = intval($row['cnt']);
    }

    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM account_stock WHERE status = 'sold'");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $stats['sold_accounts'] = intval($row['cnt']);
    }

    return $stats;
}

function admin_getAvailableAccount($productId) {
    global $conn;

    $productId = intval($productId);
    $sql = "SELECT id, product_id, account_data
            FROM account_stock
            WHERE product_id = $productId AND status = 'available'
            LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function admin_markAccountAsSold($accountId) {
    global $conn;

    $accountId = intval($accountId);
    $sql = "UPDATE account_stock SET status = 'sold', sold_at = NOW() WHERE id = $accountId";
    return mysqli_query($conn, $sql);
}

function admin_deleteAccountStock($accountId) {
    global $conn;

    $accountId = intval($accountId);
    $sql = "DELETE FROM account_stock WHERE id = $accountId LIMIT 1";
    return mysqli_query($conn, $sql);
}

function admin_getAccountById($accountId) {
    global $conn;

    $accountId = intval($accountId);
    $sql = "SELECT * FROM account_stock WHERE id = $accountId";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/* =====================================================================
   KHO TÀI KHOẢN: API HANDLER
   ===================================================================== */
function admin_handleAccountStockRequest() {
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'get_by_product':
            $productId = intval($_REQUEST['product_id'] ?? 0);
            $accounts = admin_getAccountStockByProduct($productId);
            admin_respondJson(true, 'Lấy dữ liệu thành công', $accounts);
            break;

        case 'stats':
            $stats = admin_getAccountStockStats();
            admin_respondJson(true, 'Thống kê thành công', $stats);
            break;

        case 'add':
            $productId  = intval($_POST['product_id'] ?? 0);
            $accountData = $_POST['account_data'] ?? '';

            if ($productId <= 0 || empty($accountData)) {
                admin_respondJson(false, 'Dữ liệu không hợp lệ');
            } elseif (admin_addAccountStock($productId, $accountData)) {
                admin_respondJson(true, 'Thêm tài khoản thành công');
            } else {
                admin_respondJson(false, 'Lỗi khi thêm tài khoản');
            }
            break;

        case 'bulk_add':
            $productId     = intval($_POST['product_id'] ?? 0);
            $accountsText   = $_POST['accounts_text'] ?? '';

            if ($productId <= 0 || empty($accountsText)) {
                admin_respondJson(false, 'Dữ liệu không hợp lệ');
            } else {
                // Support formats:
                //   account:user:pass              -> {account: user, password: pass}
                //   account:user:pass,cookie:ABC  -> {account: user, password: pass, cookie: ABC}
                //   account:user\npass:pass123     -> multiple lines, same account
                $lines = array_filter(array_map('trim', explode("\n", $accountsText)));
                $accountsArray = [];
                $pendingData = [];

                foreach ($lines as $line) {
                    if (empty($line)) continue;

                    // First, try to detect "account:user:pass" format (3 parts with 2 colons)
                    $colonParts = explode(':', $line);
                    if (count($colonParts) >= 3 && trim($colonParts[0]) === 'account') {
                        // Format: account:user:pass or account:user:pass,extra:val
                        $data = [
                            'account' => trim($colonParts[1]),
                        ];
                        // Last part might be comma-separated extra fields
                        $lastPart = implode(':', array_slice($colonParts, 2));
                        $extraParts = array_filter(array_map('trim', explode(',', $lastPart)));
                        if (count($extraParts) > 1) {
                            // Has extra fields: lastPart = "pass,cookie:ABC"
                            $passwordPart = $extraParts[0];
                            $data['password'] = $passwordPart;
                            for ($i = 1; $i < count($extraParts); $i++) {
                                $eq = strpos($extraParts[$i], ':');
                                if ($eq !== false) {
                                    $ek = trim(substr($extraParts[$i], 0, $eq));
                                    $ev = trim(substr($extraParts[$i], $eq + 1));
                                    if ($ek && $ev !== '') {
                                        $data[$ek] = $ev;
                                    }
                                }
                            }
                        } else {
                            // Just password
                            $data['password'] = $lastPart;
                        }
                        if (!empty($data['account'])) {
                            $accountsArray[] = json_encode($data, JSON_UNESCAPED_UNICODE);
                        }
                        continue;
                    }

                    // Standard key:value format (comma-separated for same account)
                    $pairs = array_filter(array_map('trim', explode(',', $line)));
                    $data = [];
                    foreach ($pairs as $pair) {
                        $eq = strpos($pair, ':');
                        if ($eq !== false && $eq > 0) {
                            $k = trim(substr($pair, 0, $eq));
                            $v = trim(substr($pair, $eq + 1));
                            if ($k && $v !== '') {
                                $data[$k] = $v;
                            }
                        }
                    }
                    if (!empty($data)) {
                        $accountsArray[] = json_encode($data, JSON_UNESCAPED_UNICODE);
                    }
                }
                $count = admin_addAccountStockBulk($productId, $accountsArray);
                admin_respondJson(true, "Đã thêm $count tài khoản thành công", ['count' => $count]);
            }
            break;

        case 'delete':
            $accountId = intval($_POST['account_id'] ?? 0);

            if ($accountId <= 0) {
                admin_respondJson(false, 'ID không hợp lệ');
            } elseif (admin_deleteAccountStock($accountId)) {
                admin_respondJson(true, 'Xóa tài khoản thành công');
            } else {
                admin_respondJson(false, 'Lỗi khi xóa tài khoản');
            }
            break;

        default:
            admin_respondJson(false, 'Action không hợp lệ');
    }
}

/* =====================================================================
   UTILITY
   ===================================================================== */
if (!function_exists('admin_respondJson')) {
function admin_respondJson($success, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
}

