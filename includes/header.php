<?php
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/csrf.php';

$pageTitle = $pageTitle ?? SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/style.css">
    <?php if (!empty($extraCss)): ?>
        <style>
            <?= $extraCss ?>
        </style>
    <?php endif; ?>
</head>
<body>
