<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';

function getAllUsers($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY id DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getUserById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateUser($pdo, $id, $data) {
    $stmt = $pdo->prepare("UPDATE users SET fullname = ?, balance = ?, role = ? WHERE id = ?");
    return $stmt->execute([
        $data['fullname'],
        $data['balance'],
        $data['role'],
        $id
    ]);
}

function deleteUser($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

function addUser($pdo, $data) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, balance) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['username'],
        password_hash($data['password'], PASSWORD_BCRYPT),
        $data['fullname'],
        $data['role'],
        $data['balance']
    ]);
}
?>
