<?php
require_once __DIR__ . '/products.php';

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
$result = product_delete($id);
product_redirect_after_action($result);
