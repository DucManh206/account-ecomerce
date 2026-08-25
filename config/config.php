<?php
header('Content-Type: text/html; charset=utf-8');

define('DEFAULT_SITE_NAME', 'AccountShop - Hệ thống tài khoản Premium');

// Tự động nhận diện thư mục gốc của dự án
if (isset($_SERVER['DOCUMENT_ROOT'])) {
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $dir = str_replace('\\', '/', dirname(__DIR__));
    $basePath = '';
    if (strpos($dir, $docRoot) === 0) {
        $basePath = substr($dir, strlen($docRoot));
    } else {
        $basePath = str_replace($docRoot, '', $dir);
    }
    $basePath = '/' . trim(str_replace('\\', '/', $basePath), '/') . '/';
    $basePath = str_replace('//', '/', $basePath);
    if ($basePath === '/') {
        $basePath = '/';
    }
    define('BASE_PATH', $basePath);
} else {
    define('BASE_PATH', '/');
}

$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
define('BASE_URL', $protocol . '://' . $httpHost . (BASE_PATH !== '/' ? rtrim(BASE_PATH, '/') : ''));

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

$isDebug = filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL);
error_reporting(E_ALL);
ini_set('display_errors', $isDebug ? '1' : '0');
?>
