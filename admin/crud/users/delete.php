<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/users.php';

function crud_redirect($result, $fallback = 'list.php') {
    if (php_sapi_name() === 'cli') return;
    $status = !empty($result['success']) ? 'success' : 'error';
    $msg = urlencode($result['message'] ?? 'Done');
    header("Location: {$fallback}?{$status}={$msg}");
    exit;
}

$id = $_GET['id'] ?? $_GET['key'] ?? $_POST['id'] ?? $_POST['key'] ?? '';
$result = $id !== '' ? users_delete($id) : ['success'=>false,'message'=>'ID không hợp lệ'];
crud_redirect($result);
