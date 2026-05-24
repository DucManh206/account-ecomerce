<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';

function getAllOrders($pdo) {
    $sql = "SELECT orders.*, users.username, users.fullname AS user_fullname, accounts.name AS account_name
            FROM orders
            LEFT JOIN users ON orders.user_id = users.id
            LEFT JOIN accounts ON orders.account_id = accounts.id
            ORDER BY orders.id DESC";
    return $pdo->query($sql)->fetchAll();
}

function getOrderById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function deleteOrder($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    return $stmt->execute([$id]);
}
?>
