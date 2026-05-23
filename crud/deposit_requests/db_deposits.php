<?php
/**
 * Deposit Requests CRUD Data Access Layer
 */

require_once __DIR__ . '/../db.php';

/* =====================================================================
   STATUS CONSTANTS
   ===================================================================== */
define('DEPOSIT_STATUS_PENDING',   'pending');
define('DEPOSIT_STATUS_APPROVED', 'approved');
define('DEPOSIT_STATUS_REJECTED', 'rejected');

function deposit_get_status_map(): array {
    return [
        DEPOSIT_STATUS_PENDING   => ['label' => 'Chờ duyệt', 'class' => 'nexus-pill-pending',   'icon' => 'fa-clock'],
        DEPOSIT_STATUS_APPROVED => ['label' => 'Đã duyệt',  'class' => 'nexus-pill-success', 'icon' => 'fa-check-circle'],
        DEPOSIT_STATUS_REJECTED => ['label' => 'Từ chối',   'class' => 'nexus-pill-danger',  'icon' => 'fa-xmark-circle'],
    ];
}

/* =====================================================================
   READ
   ===================================================================== */
function deposit_get_all(array $opts = []): array {
    $defaults = ['limit' => 20, 'offset' => 0, 'status' => '', 'user_id' => null, 'search' => ''];
    $opts = array_merge($defaults, $opts);

    $where = ['1=1'];
    $params = [];
    $types = '';

    if ($opts['status']) {
        $where[] = 'd.status = ?';
        $params[] = $opts['status'];
        $types .= 's';
    }
    if ($opts['user_id']) {
        $where[] = 'd.user_id = ?';
        $params[] = $opts['user_id'];
        $types .= 'i';
    }
    if ($opts['search']) {
        $s = '%' . $opts['search'] . '%';
        $where[] = '(u.username LIKE ? OR d.transfer_note LIKE ?)';
        $params[] = $s;
        $params[] = $s;
        $types .= 'ss';
    }

    $whereClause = implode(' AND ', $where);

    $total = crud_count(
        "SELECT COUNT(*) as cnt FROM deposit_requests d LEFT JOIN users u ON d.user_id = u.id WHERE $whereClause",
        $params, $types
    );

    $dataParams = array_merge($params, [$opts['limit'], $opts['offset']]);
    $dataTypes = $types . 'ii';

    $rows = crud_select(
        "SELECT d.*, u.username
         FROM deposit_requests d
         LEFT JOIN users u ON d.user_id = u.id
         WHERE $whereClause
         ORDER BY d.created_at DESC
         LIMIT ? OFFSET ?",
        $dataParams, $dataTypes
    );

    return ['deposits' => $rows, 'total' => $total];
}

function deposit_get_by_id(int $id): ?array {
    return crud_select_one(
        "SELECT d.*, u.username FROM deposit_requests d LEFT JOIN users u ON d.user_id = u.id WHERE d.id = ?",
        [$id], 'i'
    );
}

function deposit_get_by_user(int $userId, array $opts = []): array {
    $opts['user_id'] = $userId;
    return deposit_get_all($opts);
}

function deposit_get_stats(): array {
    $conn = crud_conn();
    if (!$conn) return ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total_amount' => 0];

    $stats = crud_select("
        SELECT status, COUNT(*) as cnt
        FROM deposit_requests
        GROUP BY status
    ");

    $result = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total_amount' => 0];
    foreach ($stats as $row) {
        $key = $row['status'];
        if (isset($result[$key])) $result[$key] = intval($row['cnt']);
    }

    $row = crud_select_one(
        "SELECT COALESCE(SUM(amount), 0) as total FROM deposit_requests WHERE status = ?",
        [DEPOSIT_STATUS_APPROVED], 's'
    );
    $result['total_amount'] = intval($row['total'] ?? 0);

    return $result;
}

/* =====================================================================
   CREATE
   ===================================================================== */
function deposit_create(int $userId, float $amount, string $transferNote, string $bankId = '', string $status = DEPOSIT_STATUS_PENDING): int {
    return crud_insert(
        "INSERT INTO deposit_requests (user_id, amount, transfer_note, bank_id, status, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())",
        [$userId, $amount, $transferNote, $bankId, $status],
        'idsss'
    );
}

/* =====================================================================
   UPDATE STATUS
   ===================================================================== */
function deposit_update_status(int $id, string $status): bool {
    $valid = [DEPOSIT_STATUS_PENDING, DEPOSIT_STATUS_APPROVED, DEPOSIT_STATUS_REJECTED];
    if (!in_array($status, $valid, true)) return false;

    return crud_exec(
        "UPDATE deposit_requests SET status=?, updated_at=NOW() WHERE id=?",
        [$status, $id], 'si'
    ) > 0;
}

function deposit_approve(int $id): bool {
    return deposit_update_status($id, DEPOSIT_STATUS_APPROVED);
}

function deposit_reject(int $id): bool {
    return deposit_update_status($id, DEPOSIT_STATUS_REJECTED);
}

/* =====================================================================
   DELETE
   ===================================================================== */
function deposit_delete(int $id): bool {
    return crud_exec("DELETE FROM deposit_requests WHERE id = ?", [$id], 'i') > 0;
}
