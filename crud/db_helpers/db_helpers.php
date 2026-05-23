<?php
/**
 * Shared database helper functions.
 * Provides reusable CRUD helpers using prepared statements.
 * Included by all lib modules that need DB access.
 */

require_once __DIR__ . '/../../config/db.php';

/**
 * Trả về associative array hoặc [] nếu lỗi.
 */
function crud_select(string $sql, array $params = [], string $types = ''): array {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return [];

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log('[CRUD] Prepare failed: ' . mysqli_error($conn));
        return [];
    }

    if ($params && $types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (!$result) return [];

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    return $rows;
}

/**
 * Trả về 1 dòng hoặc null.
 */
function crud_select_one(string $sql, array $params = [], string $types = ''): ?array {
    $rows = crud_select($sql, $params, $types);
    return $rows[0] ?? null;
}

/**
 * Insert - trả về inserted_id hoặc 0.
 */
function crud_insert(string $sql, array $params = [], string $types = ''): int {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return 0;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log('[CRUD] Insert prepare failed: ' . mysqli_error($conn));
        return 0;
    }

    if ($params && $types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log('[CRUD] Insert execute failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return 0;
    }

    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $id;
}

/**
 * Update / Delete - trả về affected_rows.
 */
function crud_exec(string $sql, array $params = [], string $types = ''): int {
    $conn = $GLOBALS['conn'] ?? null;
    if (!$conn) return 0;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log('[CRUD] Exec prepare failed: ' . mysqli_error($conn));
        return 0;
    }

    if ($params && $types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log('[CRUD] Exec execute failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return 0;
    }

    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}

/**
 * Count helper - trả về integer.
 */
function crud_count(string $sql, array $params = [], string $types = ''): int {
    $row = crud_select_one($sql, $params, $types);
    return $row ? intval($row['cnt'] ?? $row['total'] ?? 0) : 0;
}
