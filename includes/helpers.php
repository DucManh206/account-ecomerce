<?php

function default_product_image(): string
{
    return rtrim(BASE_PATH, '/') . '/assets/images/default-product.svg';
}

function product_image_url(?string $image): string
{
    $image = trim((string) $image);
    return $image !== '' ? $image : default_product_image();
}

function is_valid_username(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username);
}

function safe_internal_path(?string $target, string $fallback = 'index.php'): string
{
    $target = trim((string) $target);
    if ($target === '' || str_contains($target, "\n") || str_contains($target, "\r")) {
        return $fallback;
    }
    if (preg_match('#^(https?:)?//#i', $target) || str_starts_with($target, '\\\\')) {
        return $fallback;
    }
    if (str_contains($target, '..')) {
        return $fallback;
    }
    if ($target[0] === '/') {
        $base = rtrim(BASE_PATH, '/');
        if ($base !== '' && $base !== '/' && !str_starts_with($target, $base . '/') && $target !== $base) {
            return $fallback;
        }
        return $target;
    }
    return $target;
}

function cart_contains(int $accountId): bool
{
    foreach ($_SESSION['cart'] ?? [] as $id) {
        if ((int) $id === $accountId) {
            return true;
        }
    }
    return false;
}

function order_product_name(array $order): string
{
    $name = trim((string) ($order['product_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    return trim((string) ($order['account_name'] ?? '')) ?: 'Sản phẩm không còn dữ liệu';
}

function order_product_category(array $order): string
{
    $category = trim((string) ($order['product_category'] ?? ''));
    if ($category !== '') {
        return $category;
    }
    return trim((string) ($order['category_name'] ?? '')) ?: 'Chưa phân loại';
}

function order_delivered_credentials(array $order): string
{
    $snapshot = trim((string) ($order['delivered_credentials'] ?? ''));
    if ($snapshot !== '') {
        return $snapshot;
    }
    return trim((string) ($order['account_detail'] ?? ''));
}
