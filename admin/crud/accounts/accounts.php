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

function getFilteredAccounts($pdo, $statusFilter = 'all') {
    $sql = "SELECT accounts.*, categories.name AS category_name
            FROM accounts
            LEFT JOIN categories ON accounts.category_id = categories.id";
    $params = [];

    if ($statusFilter === 'available') {
        $sql .= " WHERE accounts.status = 'available'";
    } elseif ($statusFilter === 'sold') {
        $sql .= " WHERE accounts.status = 'sold'";
    }

    $sql .= " ORDER BY accounts.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAccountCounts($pdo) {
    $total = $pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
    $available = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status = 'available'")->fetchColumn();
    $sold = $pdo->query("SELECT COUNT(*) FROM accounts WHERE status = 'sold'")->fetchColumn();
    $hidden = $pdo->query("SELECT COUNT(*) FROM accounts WHERE hidden = 1")->fetchColumn();
    return ['total' => $total, 'available' => $available, 'sold' => $sold, 'hidden' => $hidden];
}

function toggleHidden($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE accounts SET hidden = IF(hidden = 1, 0, 1) WHERE id = ?");
    $stmt->execute([$id]);
    // Return new hidden state
    $stmt2 = $pdo->prepare("SELECT hidden FROM accounts WHERE id = ?");
    $stmt2->execute([$id]);
    return $stmt2->fetchColumn();
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
    $stmt = $pdo->prepare("UPDATE accounts SET name=?, description=?, price=?, category_id=?, image=?, account_detail=?, status=?, created_at=? WHERE id=?");
    return $stmt->execute([
        $data['name'], $data['description'], $data['price'], $data['category_id'] ?: null,
        $data['image'], $data['account_detail'], $data['status'], $data['created_at'], $id
    ]);
}

function deleteAccount($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = ?");
    return $stmt->execute([$id]);
}

function getCategories($pdo) {
    return $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
}

// Templates functions
function getTemplates($pdo) {
    return $pdo->query("SELECT templates.*, categories.name AS category_name FROM templates LEFT JOIN categories ON templates.category_id = categories.id ORDER BY templates.id DESC")->fetchAll();
}

function addTemplate($pdo, $data) {
    $stmt = $pdo->prepare("INSERT INTO templates (name, price, category_id, image, description) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['name'], $data['price'], $data['category_id'] ?: null, $data['image'], $data['description']
    ]);
}

function deleteTemplate($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
    return $stmt->execute([$id]);
}
?>
