<?php
header('Content-Type: text/html; charset=utf-8');

define('SITE_NAME', 'Account Shop - Nhóm 5');

// Tu dong nhan dien thu muc goc cua du an 
if (isset($_SERVER['DOCUMENT_ROOT'])) {
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $dir = str_replace('\\', '/', dirname(__DIR__, 2));
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

define('BASE_URL', 'http://localhost' . (BASE_PATH !== '/' ? rtrim(BASE_PATH, '/') : ''));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
