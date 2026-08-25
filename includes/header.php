<?php
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/csrf.php';

$pageTitle = $pageTitle ?? SITE_NAME;
$pageDescription = $pageDescription ?? app_setting('storefront_description', 'Cửa hàng tài khoản số giao tự động sau thanh toán.');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="theme-color" content="#08090a">
    <link rel="icon" href="<?= BASE_PATH ?>assets/images/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/style.css">
    <?php if (!empty($extraCss)): ?>
        <style>
            <?= $extraCss ?>
        </style>
    <?php endif; ?>
</head>
<body>
    <a class="skip-link" href="#main-content">Bỏ qua điều hướng</a>
