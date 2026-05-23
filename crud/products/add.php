<?php
require_once __DIR__ . '/products.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    product_redirect_after_action(['success' => false, 'message' => 'Phương thức không hợp lệ']);
}

$result = product_add($_POST);
product_redirect_after_action($result);
