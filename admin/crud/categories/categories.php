<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';

function getAllCategories($pdo) {
    return $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
}

function getCategoryById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addCategory($pdo, $data) {
    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    return $stmt->execute([$data['name'], $data['description']]);
}

function updateCategory($pdo, $id, $data) {
    $stmt = $pdo->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
    return $stmt->execute([$data['name'], $data['description'], $id]);
}

function deleteCategory($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    return $stmt->execute([$id]);
}
?>
