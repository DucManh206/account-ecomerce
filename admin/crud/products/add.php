<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/products.php';

function crud_redirect($result, $fallback = 'list.php') {
    if (php_sapi_name() === 'cli') return;
    $status = !empty($result['success']) ? 'success' : 'error';
    $msg = urlencode($result['message'] ?? 'Done');
    header("Location: {$fallback}?{$status}={$msg}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = products_add($_POST);
    crud_redirect($result);
}
header('Location: list.php'); exit;
