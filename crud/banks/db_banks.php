<?php
/**
 * Banks CRUD 
 */

require_once __DIR__ . '/../db.php';

/* =====================================================================
   READ
   ===================================================================== */
function bank_get_all(): array {
    return crud_select("SELECT * FROM banks ORDER BY id DESC");
}

function bank_get_by_id(int $id): ?array {
    return crud_select_one("SELECT * FROM banks WHERE id = ?", [$id], 'i');
}

function bank_count(): int {
    return crud_count("SELECT COUNT(*) as cnt FROM banks");
}

/* =====================================================================
   CREATE
   ===================================================================== */
function bank_create(array $data): int {
    $name        = trim($data['name'] ?? '');
    $account_no  = trim($data['account_no'] ?? '');
    $account_name = trim($data['account_name'] ?? '');
    $branch      = trim($data['branch'] ?? '');
    $qr_template = trim($data['qr_template'] ?? '');
    $status      = $data['status'] ?? 'active';

    if (!$name || !$account_no) return 0;

    return crud_insert(
        "INSERT INTO banks (name, account_no, account_name, branch, qr_template, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [$name, $account_no, $account_name, $branch, $qr_template, $status],
        'ssssss'
    );
}

/* =====================================================================
   UPDATE
   ===================================================================== */
function bank_update(int $id, array $data): bool {
    $name        = trim($data['name'] ?? '');
    $account_no  = trim($data['account_no'] ?? '');
    $account_name = trim($data['account_name'] ?? '');
    $branch      = trim($data['branch'] ?? '');
    $qr_template = trim($data['qr_template'] ?? '');
    $status      = $data['status'] ?? 'active';

    if (!$name || !$account_no) return false;

    return crud_exec(
        "UPDATE banks SET name=?, account_no=?, account_name=?, branch=?, qr_template=?, status=?, updated_at=NOW() WHERE id=?",
        [$name, $account_no, $account_name, $branch, $qr_template, $status, $id],
        'ssssssi'
    ) > 0;
}

/* =====================================================================
   DELETE
   ===================================================================== */
function bank_delete(int $id): bool {
    return crud_exec("DELETE FROM banks WHERE id = ?", [$id], 'i') > 0;
}
