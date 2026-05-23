<?php
/**
 * Categories CRUD Data Access Layer
 */

require_once __DIR__ . '/../db.php';

/* =====================================================================
   READ
   ===================================================================== */
function category_get_all(): array {
    return crud_select("SELECT * FROM categories ORDER BY id DESC");
}

function category_get_by_id(int $id): ?array {
    return crud_select_one("SELECT * FROM categories WHERE id = ?", [$id], 'i');
}

function category_count(): int {
    return crud_count("SELECT COUNT(*) as cnt FROM categories");
}

function category_get_by_slug(string $slug): ?array {
    return crud_select_one("SELECT * FROM categories WHERE slug = ?", [$slug], 's');
}

/* =====================================================================
   CREATE
   ===================================================================== */
function category_create(array $data): int {
    $name        = trim($data['name'] ?? '');
    $slug        = trim($data['slug'] ?? '') ?: preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
    $description = trim($data['description'] ?? '');
    $status      = $data['status'] ?? 'active';

    if (!$name) return 0;

    return crud_insert(
        "INSERT INTO categories (name, slug, description, status, created_at) VALUES (?, ?, ?, ?, NOW())",
        [$name, $slug, $description, $status],
        'ssss'
    );
}

/* =====================================================================
   UPDATE
   ===================================================================== */
function category_update(int $id, array $data): bool {
    $name        = trim($data['name'] ?? '');
    $slug        = trim($data['slug'] ?? '');
    $description = trim($data['description'] ?? '');
    $status      = $data['status'] ?? 'active';

    if (!$name) return false;

    return crud_exec(
        "UPDATE categories SET name=?, slug=COALESCE(NULLIF(?,''),slug), description=?, status=?, updated_at=NOW() WHERE id=?",
        [$name, $slug, $description, $status, $id],
        'ssssi'
    ) > 0;
}

/* =====================================================================
   DELETE
   ===================================================================== */
function category_delete(int $id): bool {
    return crud_exec("DELETE FROM categories WHERE id = ?", [$id], 'i') > 0;
}
