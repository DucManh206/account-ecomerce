<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/sepay_transactions.php';

function crud_redirect($result, $fallback = 'list.php') {
    if (php_sapi_name() === 'cli') return;
    $status = !empty($result['success']) ? 'success' : 'error';
    $msg = urlencode($result['message'] ?? 'Done');
    header("Location: {$fallback}?{$status}={$msg}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? $_POST['key'] ?? $_GET['id'] ?? $_GET['key'] ?? '';
    $result = $id !== '' ? sepay_transactions_update($id, $_POST) : ['success'=>false,'message'=>'ID không hợp lệ'];
    crud_redirect($result);
}
header('Location: list.php'); exit;
