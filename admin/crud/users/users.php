<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';

function getUserById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT u.*,
                (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                (SELECT COALESCE(SUM(o.price), 0) FROM orders o WHERE o.user_id = u.id) AS total_spent
         FROM users u WHERE u.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getUserStats(PDO $pdo): array
{
    return $pdo->query(
        "SELECT COUNT(*) AS total,
                SUM(role = 'user') AS customers,
                SUM(role = 'admin') AS admins,
                SUM(is_active = 0) AS suspended,
                COALESCE(SUM(balance), 0) AS total_balance
         FROM users"
    )->fetch();
}

function getFilteredUsers(PDO $pdo, string $search, string $role, string $state, int $limit, int $offset): array
{
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(u.username LIKE ? OR u.fullname LIKE ?)';
        $term = '%' . $search . '%';
        array_push($params, $term, $term);
    }
    if (in_array($role, ['admin', 'user'], true)) {
        $where[] = 'u.role = ?';
        $params[] = $role;
    }
    if ($state === 'active') {
        $where[] = 'u.is_active = 1';
    } elseif ($state === 'suspended') {
        $where[] = 'u.is_active = 0';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users u' . $whereSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT u.*,
                COUNT(DISTINCT o.id) AS order_count,
                COALESCE(SUM(o.price), 0) AS total_spent,
                MAX(o.created_at) AS last_order_at
         FROM users u
         LEFT JOIN orders o ON o.user_id = u.id' .
        $whereSql .
        ' GROUP BY u.id ORDER BY u.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset
    );
    $stmt->execute($params);
    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

function addUser(PDO $pdo, array $data): bool
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password, fullname, role, balance, is_active)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    return $stmt->execute([
        $data['username'],
        password_hash($data['password'], PASSWORD_BCRYPT),
        $data['fullname'],
        $data['role'],
        $data['balance'],
    ]);
}

function deleteUser(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    return $stmt->execute([$id]);
}
