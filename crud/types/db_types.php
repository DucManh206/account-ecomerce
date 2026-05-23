<?php
/**
 * Types CRUD Data Access Layer
 */

require_once __DIR__ . '/../db.php';

/* =====================================================================
   READ
   ===================================================================== */
function type_get_all(): array {
    return crud_select("SELECT * FROM types ORDER BY id DESC");
}

function type_get_by_id(int $id): ?array {
    return crud_select_one("SELECT * FROM types WHERE id = ?", [$id], 'i');
}

function type_count(): int {
    return crud_count("SELECT COUNT(*) as cnt FROM types");
}

/* =====================================================================
   CREATE
   ===================================================================== */
function type_create(array $data): int {
    $name      = trim($data['name'] ?? '');
    $icon_class = trim($data['icon_class'] ?? 'fa-box');
    $color     = trim($data['color'] ?? '#6e56cf');
    $status    = $data['status'] ?? 'active';

    if (!$name) return 0;

    return crud_insert(
        "INSERT INTO types (name, icon_class, color, status, created_at) VALUES (?, ?, ?, ?, NOW())",
        [$name, $icon_class, $color, $status],
        'ssss'
    );
}

/* =====================================================================
   UPDATE
   ===================================================================== */
function type_update(int $id, array $data): bool {
    $name       = trim($data['name'] ?? '');
    $icon_class = trim($data['icon_class'] ?? 'fa-box');
    $color      = trim($data['color'] ?? '#6e56cf');
    $status     = $data['status'] ?? 'active';

    if (!$name) return false;

    return crud_exec(
        "UPDATE types SET name=?, icon_class=?, color=?, status=?, updated_at=NOW() WHERE id=?",
        [$name, $icon_class, $color, $status, $id],
        'ssssi'
    ) > 0;
}

/* =====================================================================
   DELETE
   ===================================================================== */
function type_delete(int $id): bool {
    return crud_exec("DELETE FROM types WHERE id = ?", [$id], 'i') > 0;
}
