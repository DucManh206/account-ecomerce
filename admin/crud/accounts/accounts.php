<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';

function getAllAccounts($pdo) {
    $sql = "SELECT accounts.*, categories.name AS category_name
            FROM accounts
            LEFT JOIN categories ON accounts.category_id = categories.id
            ORDER BY accounts.id DESC";
    return $pdo->query($sql)->fetchAll();
}

function getAccountById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addAccount($pdo, $data) {
    $stmt = $pdo->prepare("INSERT INTO accounts (name, description, price, category_id, image, account_detail, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['name'], $data['description'], $data['price'], $data['category_id'] ?: null,
        $data['image'], $data['account_detail'], $data['status']
    ]);
}

function updateAccount($pdo, $id, $data) {
    $stmt = $pdo->prepare("UPDATE accounts SET name=?, description=?, price=?, category_id=?, image=?, account_detail=?, status=? WHERE id=?");
    return $stmt->execute([
        $data['name'], $data['description'], $data['price'], $data['category_id'] ?: null,
        $data['image'], $data['account_detail'], $data['status'], $id
    ]);
}

function deleteAccount($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
    return $stmt->execute([$id]);
}

function getCategories($pdo) {
    return $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
}
?>
