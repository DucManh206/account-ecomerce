<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../auth.php';

function buildOrderFilters(string $search, string $dateFrom, string $dateTo): array
{
    $where = [];
    $params = [];
    if ($search !== '') {
        $conditions = ['u.username LIKE ?', 'u.fullname LIKE ?', 'a.name LIKE ?', 'o.product_name LIKE ?'];
        $term = '%' . $search . '%';
        array_push($params, $term, $term, $term, $term);
        if (ctype_digit($search)) {
            $conditions[] = 'o.id = ?';
            $params[] = (int) $search;
        }
        $where[] = '(' . implode(' OR ', $conditions) . ')';
    }
    if ($dateFrom !== '') {
        $where[] = 'o.created_at >= ?';
        $params[] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $where[] = 'o.created_at <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function getFilteredOrders(PDO $pdo, string $search, string $dateFrom, string $dateTo, int $limit, int $offset): array
{
    [$whereSql, $params] = buildOrderFilters($search, $dateFrom, $dateTo);
    $fromSql = ' FROM orders o LEFT JOIN users u ON o.user_id = u.id LEFT JOIN accounts a ON o.account_id = a.id LEFT JOIN categories c ON a.category_id = c.id ';

    $summaryStmt = $pdo->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(o.price), 0) AS revenue, COALESCE(AVG(o.price), 0) AS average' . $fromSql . $whereSql);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();

    $stmt = $pdo->prepare(
        'SELECT o.*, u.username, u.fullname AS user_fullname,
                COALESCE(o.product_name, a.name) AS account_name,
                COALESCE(o.product_category, c.name) AS product_category,
                COALESCE(o.delivered_credentials, a.account_detail) AS delivered_credentials' .
        $fromSql . $whereSql . ' ORDER BY o.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset
    );
    $stmt->execute($params);
    return ['rows' => $stmt->fetchAll(), 'summary' => $summary];
}

function getOrdersForExport(PDO $pdo, string $search, string $dateFrom, string $dateTo): array
{
    [$whereSql, $params] = buildOrderFilters($search, $dateFrom, $dateTo);
    $stmt = $pdo->prepare(
        'SELECT o.id, o.created_at, o.price, u.username, u.fullname AS user_fullname,
                COALESCE(o.product_name, a.name) AS account_name
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         LEFT JOIN accounts a ON o.account_id = a.id' . $whereSql . ' ORDER BY o.id DESC'
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getOrderById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
