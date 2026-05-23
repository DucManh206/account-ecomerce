<?php
/**
 * Account Stock
 */

require_once __DIR__ . '/../db.php';

define('ACCOUNT_AVAILABLE', 'available');
define('ACCOUNT_SOLD',      'sold');
define('ACCOUNT_RESERVED',  'reserved');

function account_stock_status_map(): array {
    return [
        ACCOUNT_AVAILABLE => ['label' => 'Khả dụng', 'class' => 'nexus-pill-success', 'icon' => 'fa-circle-check'],
        ACCOUNT_SOLD      => ['label' => 'Đã bán',   'class' => 'nexus-pill-danger',  'icon' => 'fa-bag-shopping'],
        ACCOUNT_RESERVED  => ['label' => 'Đã đặt',   'class' => 'nexus-pill-pending',  'icon' => 'fa-clock'],
    ];
}

/* =====================================================================
   READ
   ===================================================================== */
function account_stock_get_all(array $opts = []): array {
    $defaults = ['limit' => 20, 'offset' => 0, 'product_id' => null, 'status' => '', 'search' => ''];
    $opts = array_merge($defaults, $opts);

    $where = ['1=1'];
    $params = [];
    $types = '';

    if ($opts['product_id']) {
        $where[] = 'a.product_id = ?';
        $params[] = $opts['product_id'];
        $types .= 'i';
    }
    if ($opts['status']) {
        $where[] = 'a.status = ?';
        $params[] = $opts['status'];
        $types .= 's';
    }
    if ($opts['search']) {
        $s = '%' . $opts['search'] . '%';
        $where[] = '(a.account_data LIKE ? OR p.title LIKE ?)';
        $params[] = $s;
        $params[] = $s;
        $types .= 'ss';
    }

    $whereClause = implode(' AND ', $where);

    $total = crud_count(
        "SELECT COUNT(*) as cnt FROM account_stock a
         LEFT JOIN products p ON a.product_id = p.id
         WHERE $whereClause",
        $params, $types
    );

    $dataParams = array_merge($params, [$opts['limit'], $opts['offset']]);
    $dataTypes = $types . 'ii';

    $rows = crud_select(
        "SELECT a.*, p.title as product_title, p.category, p.icon_class
         FROM account_stock a
         LEFT JOIN products p ON a.product_id = p.id
         WHERE $whereClause
         ORDER BY a.id DESC
         LIMIT ? OFFSET ?",
        $dataParams, $dataTypes
    );

    return ['accounts' => $rows, 'total' => $total];
}

function account_stock_get_by_id(int $id): ?array {
    return crud_select_one(
        "SELECT a.*, p.title as product_title FROM account_stock a
         LEFT JOIN products p ON a.product_id = p.id
         WHERE a.id = ?",
        [$id], 'i'
    );
}

function account_stock_get_available(int $productId): array {
    return crud_select(
        "SELECT a.*, p.title as product_title FROM account_stock a
         LEFT JOIN products p ON a.product_id = p.id
         WHERE a.product_id = ? AND a.status = ?
         ORDER BY a.id ASC
         LIMIT 1 FOR UPDATE",
        [$productId, ACCOUNT_AVAILABLE], 'is'
    );
}

function account_stock_count_available(int $productId): int {
    return crud_count(
        "SELECT COUNT(*) as cnt FROM account_stock WHERE product_id = ? AND status = ?",
        [$productId, ACCOUNT_AVAILABLE], 'is'
    );
}

function account_stock_count_by_status(int $productId): array {
    $rows = crud_select(
        "SELECT status, COUNT(*) as cnt FROM account_stock WHERE product_id = ? GROUP BY status",
        [$productId], 'i'
    );
    $result = ['available' => 0, 'sold' => 0, 'reserved' => 0];
    foreach ($rows as $row) {
        if (isset($result[$row['status']])) $result[$row['status']] = intval($row['cnt']);
    }
    return $result;
}

function account_stock_get_stats(): array {
    $rows = crud_select("SELECT status, COUNT(*) as cnt FROM account_stock GROUP BY status");
    $result = ['available' => 0, 'sold' => 0, 'reserved' => 0, 'total' => 0];
    foreach ($rows as $row) {
        if (isset($result[$row['status']])) {
            $result[$row['status']] = intval($row['cnt']);
        }
    }
    $result['total'] = array_sum($result);
    return $result;
}

/* =====================================================================
   CREATE
   ===================================================================== */
function account_stock_create(int $productId, string $accountData, string $status = ACCOUNT_AVAILABLE): int {
    return crud_insert(
        "INSERT INTO account_stock (product_id, account_data, status, created_at) VALUES (?, ?, ?, NOW())",
        [$productId, $accountData, $status], 'iss'
    );
}

/* =====================================================================
   UPDATE
   ===================================================================== */
function account_stock_update(int $id, array $data): bool {
    $accountData = trim($data['account_data'] ?? '');
    $status      = $data['status'] ?? '';

    if (!$accountData) return false;

    return crud_exec(
        "UPDATE account_stock SET account_data=?, status=COALESCE(NULLIF(?,''),status), updated_at=NOW() WHERE id=?",
        [$accountData, $status, $id], 'ssi'
    ) > 0;
}

function account_stock_mark_sold(int $id): bool {
    return crud_exec(
        "UPDATE account_stock SET status=?, sold_at=NOW(), updated_at=NOW() WHERE id=? AND status=?",
        [ACCOUNT_SOLD, $id, ACCOUNT_AVAILABLE], 'sii'
    ) > 0;
}

function account_stock_mark_available(int $id): bool {
    return crud_exec(
        "UPDATE account_stock SET status=?, sold_at=NULL, updated_at=NOW() WHERE id=?",
        [ACCOUNT_AVAILABLE, $id], 'si'
    ) > 0;
}

/* =====================================================================
   DELETE
   ===================================================================== */
function account_stock_delete(int $id): bool {
    return crud_exec("DELETE FROM account_stock WHERE id = ?", [$id], 'i') > 0;
}
