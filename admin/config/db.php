<?php
require_once __DIR__ . '/config.php';

$host = 'localhost';
$dbname = 'account_shop';
$username = 'web';
$password = '123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Đồng bộ session đăng nhập với trạng thái thực tế trong CSDL
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        $checkUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
        $checkUser->execute([$_SESSION['user_id']]);
        if ($checkUser->fetchColumn() == 0) {
            unset($_SESSION['user_logged_in']);
            unset($_SESSION['user_id']);
            unset($_SESSION['user_username']);
            unset($_SESSION['user_fullname']);
        }
    }

    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'admin'");
        $checkAdmin->execute([$_SESSION['admin_user_id']]);
        if ($checkAdmin->fetchColumn() == 0) {
            unset($_SESSION['admin_logged_in']);
            unset($_SESSION['admin_user_id']);
            unset($_SESSION['admin_username']);
            unset($_SESSION['admin_fullname']);
        }
    }

    // Tải cấu hình thanh toán SePay từ bảng settings
    try {
        $settingsRows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
        $settingsMap = [];
        foreach ($settingsRows as $row) {
            $settingsMap[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        $settingsMap = [];
    }
    if (!defined('SEPAY_API_TOKEN'))   define('SEPAY_API_TOKEN',   $settingsMap['sepay_api_token']  ?? 'YOUR_SEPAY_API_TOKEN');
    if (!defined('SEPAY_BANK_CODE'))   define('SEPAY_BANK_CODE',   $settingsMap['sepay_bank_code']  ?? 'MBBank');
    if (!defined('SEPAY_BANK_NUM'))    define('SEPAY_BANK_NUM',    $settingsMap['sepay_bank_num']   ?? '0000000000');
    if (!defined('SEPAY_BANK_NAME'))   define('SEPAY_BANK_NAME',   $settingsMap['sepay_bank_name']  ?? 'CHUA CAU HINH');
    if (!defined('SEPAY_MEMO_PREFIX')) define('SEPAY_MEMO_PREFIX', $settingsMap['sepay_memo_prefix'] ?? 'NAP');

} catch (PDOException $e) {
    die('Ket noi CSDL that bai: ' . $e->getMessage());
}
?>
