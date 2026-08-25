<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth_helper.php';
require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/ledger.php';

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'account_shop';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    ensure_application_schema($pdo);

    // Đồng bộ session đăng nhập với trạng thái thực tế trong CSDL
    if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
        $checkUser = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND is_active = 1");
        $checkUser->execute([$_SESSION['user_id']]);
        if ($checkUser->fetchColumn() == 0) {
            unset($_SESSION['user_logged_in']);
            unset($_SESSION['user_id']);
            unset($_SESSION['user_username']);
            unset($_SESSION['user_fullname']);
        }
    }

    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'admin' AND is_active = 1");
        $checkAdmin->execute([$_SESSION['admin_user_id']]);
        if ($checkAdmin->fetchColumn() == 0) {
            unset($_SESSION['admin_logged_in']);
            unset($_SESSION['admin_user_id']);
            unset($_SESSION['admin_username']);
            unset($_SESSION['admin_fullname']);
        }
    }

    // Tải cấu hình ứng dụng và thanh toán từ bảng settings.
    try {
        $settingsMap = load_app_settings($pdo);
    } catch (Exception $e) {
        $settingsMap = [];
    }
    if (!defined('SITE_NAME'))         define('SITE_NAME',         app_setting('site_name', DEFAULT_SITE_NAME));
    if (!defined('SITE_SHORT_NAME'))   define('SITE_SHORT_NAME',   app_setting('site_short_name', 'AccountShop'));
    if (!defined('SEPAY_ENABLED'))     define('SEPAY_ENABLED',     ($settingsMap['sepay_enabled']    ?? '0') === '1');
    if (!defined('SEPAY_API_TOKEN'))   define('SEPAY_API_TOKEN',   $settingsMap['sepay_api_token']  ?? '');
    if (!defined('SEPAY_BANK_CODE'))   define('SEPAY_BANK_CODE',   $settingsMap['sepay_bank_code']  ?? 'MBBank');
    if (!defined('SEPAY_BANK_NUM'))    define('SEPAY_BANK_NUM',    $settingsMap['sepay_bank_num']   ?? '0000000000');
    if (!defined('SEPAY_BANK_NAME'))   define('SEPAY_BANK_NAME',   $settingsMap['sepay_bank_name']  ?? 'CHUA CAU HINH');
    if (!defined('SEPAY_MEMO_PREFIX')) define('SEPAY_MEMO_PREFIX', $settingsMap['sepay_memo_prefix'] ?? 'NAP');
    if (!defined('SEPAY_MOCK_MODE'))   define('SEPAY_MOCK_MODE',   app_setting_bool('allow_mock_topup', false));

} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    die('Hệ thống đang bảo trì kết nối dữ liệu. Vui lòng thử lại sau.');
}
?>
