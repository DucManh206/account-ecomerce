<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../auth/admin_verifier_modules.php';

function admin_getDashboardStats()
{
    global $conn;

    $stats = [];

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
    $stats['total_products'] = intval(mysqli_fetch_assoc($r)['total'] ?? 0);

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = intval(mysqli_fetch_assoc($r)['total'] ?? 0);

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 1");
    $stats['total_admins'] = intval(mysqli_fetch_assoc($r)['total'] ?? 0);

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 0");
    $stats['total_clients'] = intval(mysqli_fetch_assoc($r)['total'] ?? 0);

    $r = mysqli_query($conn, "SELECT SUM(balance) as total FROM users");
    $stats['total_balance'] = intval(mysqli_fetch_assoc($r)['total'] ?? 0);

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE old_price > 0 AND old_price > price");
    $stats['discounted_products'] = intval(mysqli_fetch_assoc($r)['total'] ?? 0);

    $r = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE badge != ''");
    $stats['vip_products'] = intval(mysqli_fetch_assoc($r)['total'] ?? 0);

    $r = mysqli_query($conn, "SELECT id, title, price FROM products ORDER BY price DESC LIMIT 5");
    $stats['top_expensive'] = [];
    if ($r && mysqli_num_rows($r) > 0) {
        while ($row = mysqli_fetch_assoc($r)) {
            $stats['top_expensive'][] = $row;
        }
    }

    $r = mysqli_query($conn, "SELECT id, username, balance FROM users WHERE role = 0 ORDER BY balance DESC LIMIT 5");
    $stats['top_balance'] = [];
    if ($r && mysqli_num_rows($r) > 0) {
        while ($row = mysqli_fetch_assoc($r)) {
            $stats['top_balance'][] = $row;
        }
    }

    $r = mysqli_query($conn, "SELECT id, title, price, category, image_url FROM products ORDER BY id DESC LIMIT 5");
    $stats['latest_products'] = [];
    if ($r && mysqli_num_rows($r) > 0) {
        while ($row = mysqli_fetch_assoc($r)) {
            $stats['latest_products'][] = $row;
        }
    }

    $r = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC");
    $stats['categories'] = [];
    if ($r && mysqli_num_rows($r) > 0) {
        while ($row = mysqli_fetch_assoc($r)) {
            $stats['categories'][] = $row;
        }
    }

    return $stats;
}

function admin_getDailyStats()
{
    global $conn;
    return [];
}
