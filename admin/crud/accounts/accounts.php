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

function getManagedAccounts(PDO $pdo, array $filters, int $limit, int $offset): array
{
    $where = [];
    $params = [];
    if (($filters['status'] ?? 'all') !== 'all') {
        $where[] = 'a.status = ?';
        $params[] = $filters['status'];
    }
    if (($filters['visibility'] ?? 'all') === 'visible') {
        $where[] = 'a.hidden = 0';
    } elseif (($filters['visibility'] ?? 'all') === 'hidden') {
        $where[] = 'a.hidden = 1';
    }
    if (!empty($filters['category'])) {
        $where[] = 'a.category_id = ?';
        $params[] = (int) $filters['category'];
    }
    $health = $filters['health'] ?? 'all';
    if ($health === 'missing_details') {
        $where[] = "a.status = 'available' AND TRIM(COALESCE(a.account_detail, '')) = ''";
    } elseif ($health === 'missing_image') {
        $where[] = "TRIM(COALESCE(a.image, '')) = ''";
    } elseif ($health === 'unclassified') {
        $where[] = 'a.category_id IS NULL';
    }
    if (($filters['search'] ?? '') !== '') {
        $searchParts = ['a.name LIKE ?', 'a.description LIKE ?', 'c.name LIKE ?'];
        $term = '%' . $filters['search'] . '%';
        array_push($params, $term, $term, $term);
        if (ctype_digit($filters['search'])) {
            $searchParts[] = 'a.id = ?';
            $params[] = (int) $filters['search'];
        }
        $where[] = '(' . implode(' OR ', $searchParts) . ')';
    }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $fromSql =
        ' FROM accounts a
          LEFT JOIN categories c ON c.id = a.category_id
          LEFT JOIN (SELECT account_id, MAX(id) AS order_id FROM orders GROUP BY account_id) o ON o.account_id = a.id';

    $sortOptions = [
        'newest' => 'COALESCE(a.updated_at, a.created_at) DESC, a.id DESC',
        'oldest' => 'a.created_at ASC, a.id ASC',
        'price_desc' => 'a.price DESC, a.id DESC',
        'price_asc' => 'a.price ASC, a.id DESC',
        'name' => 'a.name ASC, a.id DESC',
    ];
    $orderSql = $sortOptions[$filters['sort'] ?? 'newest'] ?? $sortOptions['newest'];

    $countStmt = $pdo->prepare('SELECT COUNT(*)' . $fromSql . $whereSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT a.*, c.name AS category_name, o.order_id' . $fromSql .
        $whereSql . ' ORDER BY ' . $orderSql . ' LIMIT ' . $limit . ' OFFSET ' . $offset
    );
    $stmt->execute($params);
    return ['rows' => $stmt->fetchAll(), 'total' => $total];
}

function getAccountCounts(PDO $pdo): array
{
    $row = $pdo->query(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(status = 'available'), 0) AS available,
                COALESCE(SUM(status = 'sold'), 0) AS sold,
                COALESCE(SUM(hidden = 1), 0) AS hidden,
                COALESCE(SUM(status = 'available' AND TRIM(COALESCE(account_detail, '')) = ''), 0) AS missing_details,
                COALESCE(SUM(status = 'available' AND hidden = 0), 0) AS visible_available,
                COALESCE(SUM(CASE WHEN status = 'available' THEN price ELSE 0 END), 0) AS inventory_value
         FROM accounts"
    )->fetch();

    return [
        'total' => (int) ($row['total'] ?? 0),
        'available' => (int) ($row['available'] ?? 0),
        'sold' => (int) ($row['sold'] ?? 0),
        'hidden' => (int) ($row['hidden'] ?? 0),
        'missing_details' => (int) ($row['missing_details'] ?? 0),
        'visible_available' => (int) ($row['visible_available'] ?? 0),
        'inventory_value' => (float) ($row['inventory_value'] ?? 0),
    ];
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
    try {
        return $pdo->query("SELECT templates.*, categories.name AS category_name FROM templates LEFT JOIN categories ON templates.category_id = categories.id ORDER BY templates.id DESC")->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function addTemplate($pdo, $data) {
    try {
        $stmt = $pdo->prepare("INSERT INTO templates (name, price, category_id, image, description) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'], $data['price'], $data['category_id'] ?: null, $data['image'], $data['description']
        ]);
    } catch (Exception $e) {
        return false;
    }
}

function deleteTemplate($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        return false;
    }
}
?>
