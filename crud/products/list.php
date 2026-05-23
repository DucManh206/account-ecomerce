<?php
require_once __DIR__ . '/products.php';

$products = product_getAll();

if (php_sapi_name() === 'cli') {
    print_r($products);
    return;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'data' => $products], JSON_UNESCAPED_UNICODE);
