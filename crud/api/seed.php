<?php
/**
 * NEXUS STORE - Database Seeder
 * Run: http://localhost/crud/api/seed.php
 *
 * Populates test data for development/demo without affecting existing records.
 * Uses ON DUPLICATE KEY UPDATE / IGNORE to be safe for re-runs.
 */

require_once __DIR__ . '/../database/connect.php';

echo "<html><head><meta charset=\"UTF-8\"><title>Nexus Store - Seed Data</title>";
echo "<style>
body{font-family:-apple-system,sans-serif;padding:32px;background:#0a0a0f;color:#e2e8f0;max-width:900px;margin:0 auto}
h1{color:#a78bfa;font-size:1.75rem;margin-bottom:8px}
.sub{color:#64748b;font-size:0.9rem;margin-bottom:32px}
h2{color:#818cf8;font-size:1rem;margin-top:32px;border-bottom:1px solid #1e293b;padding-bottom:8px}
.ok{color:#34d399}.skip{color:#475569}.err{color:#f87171}.info{color:#fbbf24}
pre{background:#0f172a;border:1px solid #1e293b;border-radius:8px;padding:16px;margin:8px 0;font-size:0.8rem;color:#94a3b8;max-height:200px;overflow:auto}
.summary{background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;margin-top:32px}
.summary h2{color:#a78bfa;border:none;margin-top:0}
.summary p{margin:6px 0;font-size:0.95rem}
a.btn{display:inline-block;background:#6d28d9;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;margin-top:16px;margin-right:8px}
a.btn:hover{background:#5b21b6}
a.btn.green{background:#059669}
a.btn.green:hover{background:#047857}
table{width:100%;border-collapse:collapse;margin:8px 0}
th,td{padding:8px 12px;text-align:left;border-bottom:1px solid #1e293b;font-size:0.85rem}
th{color:#818cf8}
</style></head><body>";
echo "<h1>NEXUS STORE</h1>";
echo "<p class=\"sub\">Seed Data - Development / Demo</p>";

$results = array();
function esc($s) { global $conn; return $conn->real_escape_string($s); }
function iid() { global $conn; return mysqli_insert_id($conn); }

function rs($sql, $desc) {
    global $conn, $results;
    $ok = mysqli_query($conn, $sql);
    if ($ok) {
        $results[] = array("ok", $desc, mysqli_affected_rows($conn));
        echo "<p class=\"ok\">[OK] " . htmlspecialchars($desc) . "</p>";
        return true;
    }
    $e = mysqli_error($conn);
    if (strpos($e, "Duplicate") !== false || strpos($e, "already exists") !== false || strpos($e, "truncated") !== false) {
        $results[] = array("skip", $desc);
        echo "<p class=\"skip\">[SKIP] " . htmlspecialchars($desc) . " (exists/duplicate)</p>";
        return true;
    }
    $results[] = array("err", "$desc: $e");
    echo "<p class=\"err\">[ERR] " . htmlspecialchars($desc) . "<br><pre>$e</pre></p>";
    return false;
}

function upsert($table, $keys, $data, $desc) {
    global $conn;
    $cols = implode(', ', $keys);
    $vals = implode(', ', array_map(function($v) { return "'" . esc($v) . "'"; }, $data));
    $updates = implode(', ', array_map(function($k, $v) { return "`$k` = '" . esc($v) . "'"; }, $keys, $data));
    $sql = "INSERT INTO `$table` ($cols) VALUES ($vals) ON DUPLICATE KEY UPDATE $updates";
    return rs($sql, $desc);
}

/* ================================================================
   TEST USERS
   ================================================================ */
echo "<h2>1. Test Users</h2>";

$testUsers = [
    ['testuser1', 'Test User 1', 50000,  0],
    ['testuser2', 'Test User 2', 200000, 0],
    ['john_gamer', 'John Gamer', 10000,  0],
    ['alice_music', 'Alice Music', 0, 0],
];

$userIds = [];
foreach ($testUsers as $u) {
    $pwHash = password_hash("test123", PASSWORD_BCRYPT);
    upsert('users', ['username', 'password', 'role', 'balance'],
           [$u[0], $pwHash, 0, $u[2]],
           "User: {$u[1]} (balance: " . number_format($u[2]) . "đ)");
    $res = mysqli_query($conn, "SELECT id FROM users WHERE username = '" . esc($u[0]) . "' LIMIT 1");
    if ($r = mysqli_fetch_assoc($res)) $userIds[$u[0]] = $r['id'];
}

/* ================================================================
   SAMPLE TRANSACTIONS
   ================================================================ */
echo "<h2>2. Sample Transactions</h2>";

if (!empty($userIds)) {
    $sampleTx = [
        [$userIds['testuser1'], 50000,  0,      50000,  'topup',    'Nap tien test 50,000đ',      '-1 day'],
        [$userIds['testuser1'], -15000, 50000,   35000,  'purchase', 'Mua tai khoan Valorant',    '-2 days'],
        [$userIds['testuser2'], 200000, 0,       200000, 'topup',    'Nap tien test 200,000đ',     '-3 days'],
        [$userIds['testuser2'], -35000, 200000,  165000, 'purchase', 'Mua tai khoan Netflix',      '-1 day'],
        [$userIds['john_gamer'], 10000, 0,       10000,  'topup',    'Nap tien test 10,000đ',      '-5 hours'],
    ];

    foreach ($sampleTx as $tx) {
        $ago = $tx[5];
        $created = date('Y-m-d H:i:s', strtotime($ago));
        rs("INSERT INTO transactions (user_id, amount, balance_before, balance_after, type, description, created_at)
            VALUES ({$tx[0]}, {$tx[1]}, {$tx[2]}, {$tx[3]}, '{$tx[4]}', '" . esc($tx[4]) . "', '$created')",
           "Tx: {$tx[5]}");
    }
}

/* ================================================================
   SAMPLE DEPOSIT REQUESTS (various statuses)
   ================================================================ */
echo "<h2>3. Sample Deposit Requests</h2>";

if (!empty($userIds)) {
    $prefix = 'NT';
    $deposits = [
        // [user_key, amount, status, created_ago, expires_ago]
        ['testuser1', 50000,  'approved',  '-2 days',   '-2 days +5min'],
        ['testuser1', 100000, 'cancelled', '-1 day',    '-1 day +35min'],
        ['testuser2', 200000, 'approved', '-3 days',   '-3 days +3min'],
        ['testuser2', 50000,  'pending',  '-5 minutes','+30 minutes'],
        ['john_gamer',10000,  'pending',  '-1 minute', '+30 minutes'],
    ];

    foreach ($deposits as $d) {
        $uid = $userIds[$d[0]];
        $amount = $d[1];
        $status = $d[2];
        $createdAt = date('Y-m-d H:i:s', strtotime($d[3]));
        $expiresAt = date('Y-m-d H:i:s', strtotime($d[4]));
        $code = strtoupper($prefix) . $uid . strtotime($d[3]);
        $processedAt = ($status === 'approved') ? date('Y-m-d H:i:s', strtotime($d[3]) + 300) : 'NULL';
        $adminNote = $status === 'cancelled' ? "'Auto cancel test'" : ($status === 'approved' ? "'Seed approved'" : 'NULL');

        rs("INSERT INTO deposit_requests
            (user_id, username, amount, transfer_amount, transfer_note, unique_code, status, admin_note, processed_at, expires_at, created_at)
            SELECT $uid, username, $amount, $amount, '$code', '$code', '$status',
                   " . ($adminNote === 'NULL' ? 'NULL' : $adminNote) . ",
                   " . ($processedAt === 'NULL' ? 'NULL' : "'$processedAt'") . ",
                   '$expiresAt', '$createdAt'
            FROM users WHERE id = $uid",
           "Deposit: {$d[0]} - " . number_format($amount) . "đ [$status]");
    }
}

/* ================================================================
   SAMPLE SEPay TRANSACTIONS (for testing auto-matching)
   ================================================================ */
echo "<h2>4. Sample SePay Transactions</h2>";

$sepayTx = [
    // [amount, content_contains, status, tx_date_ago]
    [50000,  'NT' . ($userIds['testuser1'] ?? 1), 'matched',   '-2 days +4min'],
    [200000, 'NT' . ($userIds['testuser2'] ?? 2), 'matched',   '-3 days +2min'],
    [100000, 'RANDOM123',                          'failed',    '-1 day'],
];

foreach ($sepayTx as $tx) {
    $amount = $tx[0];
    $content = $tx[1];
    $status = $tx[2];
    $txDate = date('Y-m-d H:i:s', strtotime($tx[3]));
    $processedAt = ($status === 'matched') ? date('Y-m-d H:i:s', strtotime($tx[3]) + 60) : ($status === 'failed' ? date('Y-m-d H:i:s', strtotime($tx[3]) + 30) : 'NULL');
    $sepayId = 'SEED_' . time() . '_' . rand(1000, 9999);

    rs("INSERT INTO sepay_transactions
        (sepay_id, account_number, bank_code, transaction_date, amount_in, amount_out,
         transaction_content, reference_number, status, processed_at, created_at)
        VALUES
        ('$sepayId', '1234567890', 'MB', '$txDate', $amount, 0,
         '$content', 'REF" . rand(100000, 999999) . "', '$status',
         " . ($processedAt === 'NULL' ? 'NULL' : "'$processedAt'") . ",
         '$txDate')",
       "SePay Tx: " . number_format($amount) . "đ [$status]");
}

/* ================================================================
   SEPay CONFIG (safe upsert — won't overwrite real API token)
   ================================================================ */
echo "<h2>5. SePay Configuration</h2>";

$res = mysqli_query($conn, "SELECT api_token FROM sepay_config LIMIT 1");
$row = mysqli_fetch_assoc($res);

if (!$row || empty($row['api_token'])) {
    rs("INSERT INTO sepay_config
        (api_token, account_number, account_holder, bank_code, auto_process,
         min_amount, max_amount, transfer_prefix, check_interval_minutes,
         cancel_after_minutes, webhook_secret, status)
        VALUES
        ('', '9704222226666688', 'TRAN VAN A', 'MB', 1,
         10000, 500000000, 'NT', 5,
         30, '', 1)",
       "SePay Config (status=ENABLED, auto_process=ON, cancel=30min, no API token set)");
    echo "<p class=\"info\">[INFO] SePay config created. You still need to add your API token and account number in the admin panel.</p>";
} else {
    echo "<p class=\"skip\">[SKIP] SePay config already has an API token — not overwriting.</p>";
}

/* ================================================================
   BANKS (seed if empty)
   ================================================================ */
echo "<h2>6. Banks</h2>";

$banks = [
    ['MB Bank',       '9704222226666688', 'TRAN VAN A', 'fas fa-university',      1],
    ['TPBank',        '8803666999888',    'TRAN VAN B', 'fas fa-landmark',         2],
    ['Vietcombank',   '0011002233445',    'TRAN VAN C', 'fas fa-building-columns', 3],
    ['ACB',           '1234567890',        'TRAN VAN D', 'fas fa-piggy-bank',       4],
    ['BIDV',          '1211000412345',    'TRAN VAN E', 'fas fa-university',        5],
];

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM banks");
$bankCount = $r ? intval(mysqli_fetch_assoc($r)['c']) : 0;

if ($bankCount == 0) {
    foreach ($banks as $b) {
        rs("INSERT INTO banks (bank_name, account_number, account_holder, icon_class, sort_order, status)
            VALUES ('" . esc($b[0]) . "','" . esc($b[1]) . "','" . esc($b[2]) . "','" . esc($b[3]) . "',{$b[4]},1)",
           "Bank: {$b[0]} - {$b[1]}");
    }
} else {
    echo "<p class=\"skip\">[SKIP] Banks already exist ($bankCount rows)</p>";
}

/* ================================================================
   ACCOUNT FIELD TYPES (seed if empty)
   ================================================================ */
echo "<h2>7. Account Field Types</h2>";

$fields = [
    ['account',  'Tai khoan',    'fa-user',           'VD: acc@gmail.com',       1, 1],
    ['password', 'Mat khau',     'fa-lock',            'VD: Matkhau123!',          1, 2],
    ['email',    'Email',        'fa-envelope',        'VD: email@example.com',    0, 3],
    ['pass',     'Pass',         'fa-key',             'VD: Pass123',              0, 4],
    ['cookie',   'Cookie',       'fa-cookie-bite',     'VD: abc123...',            0, 5],
    ['token',    'Token',        'fa-fingerprint',     'VD: eyJhbGc...',          0, 6],
    ['key',      'Key',          'fa-key',             'VD: XXXX-XXXX',            0, 7],
    ['pin',      'PIN',          'fa-hashtag',          'VD: 123456',              0, 8],
    ['2fa',      '2FA',          'fa-shield-halved',   'VD: ABC123',              0, 9],
    ['note',     'Ghi chu',      'fa-sticky-note',     'VD: Thong tin them...',    0, 10],
    ['link',     'Link',         'fa-link',            'VD: https://...',         0, 11],
    ['code',     'Ma',           'fa-barcode',         'VD: ABC123456',            0, 12],
];

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM account_field_types");
$fieldCount = $r ? intval(mysqli_fetch_assoc($r)['c']) : 0;

if ($fieldCount == 0) {
    foreach ($fields as $f) {
        rs("INSERT INTO account_field_types (`key`, label, icon_class, placeholder, is_default, sort_order)
            VALUES ('" . esc($f[0]) . "','" . esc($f[1]) . "','" . esc($f[2]) . "','" . esc($f[3]) . "',{$f[4]},{$f[5]})",
           "Field: {$f[1]}");
    }
} else {
    echo "<p class=\"skip\">[SKIP] Account field types already exist ($fieldCount rows)</p>";
}

/* ================================================================
   SUMMARY
   ================================================================ */
$okC = $skipC = $errC = 0;
foreach ($results as $r) {
    if ($r[0] === "ok") $okC++;
    elseif ($r[0] === "skip") $skipC++;
    elseif ($r[0] === "err") $errC++;
}

echo "<div class=\"summary\">";
echo "<h2>Seed Complete!</h2>";
echo "<p><span class=\"ok\">Inserted: $okC</span> | <span class=\"skip\">Skipped: $skipC</span>";
if ($errC > 0) echo " | <span class=\"err\">Errors: $errC</span>";
echo "</p>";

echo "<table>";
echo "<tr><th>Test User</th><th>Password</th><th>Balance</th><th>Use Case</th></tr>";
foreach ($testUsers as $u) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($u[0]) . "</td>";
    echo "<td>test123</td>";
    echo "<td>" . number_format($u[2]) . "đ</td>";
    echo "<td>" . htmlspecialchars($u[1]) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p>Admin: <code>admin</code> / <code>admin123</code> (balance: 1,000,000đ)</p>";

if ($errC > 0) {
    echo "<h3>Errors:</h3>";
    foreach ($results as $r) {
        if ($r[0] === "err") echo "<p class=\"err\">- " . htmlspecialchars($r[1]) . "</p>";
    }
}

echo "<a href=\"../index.php\" class=\"btn\">Home</a>";
echo "<a href=\"../../user/\" class=\"btn green\">User Panel</a>";
echo "<a href=\"../../admin/\" class=\"btn green\">Admin Panel</a>";
echo "</div></body></html>";
?>
