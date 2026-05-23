<?php
/**
 * Database connect
 */

require_once __DIR__ . '/../config/db.php';


function crud_conn() {
    global $conn;
    if (!$conn) {
        error_log('[CRUD] Database connection not available');
    }
    return $conn;
}


function crud_select(string $sql, array $params = [], string $types = ''): array {
    $conn = crud_conn();
    if (!$conn) return [];

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log('[CRUD] Prepare failed: ' . mysqli_error($conn) . " | SQL: $sql");
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


function crud_select_one(string $sql, array $params = [], string $types = ''): ?array {
    $rows = crud_select($sql, $params, $types);
    return $rows[0] ?? null;
}

function crud_insert(string $sql, array $params = [], string $types = ''): int {
    $conn = crud_conn();
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

function crud_exec(string $sql, array $params = [], string $types = ''): int {
    $conn = crud_conn();
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

function crud_count(string $sql, array $params = [], string $types = ''): int {
    $row = crud_select_one($sql, $params, $types);
    return $row ? intval($row['cnt'] ?? $row['total'] ?? 0) : 0;
}

function crud_json_success($data = [], string $message = 'Thành công') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true, 'message' => $message], is_array($data) ? $data : []));
    exit;
}

function crud_json_error(string $message = 'Lỗi', int $httpCode = 400, $extra = []) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => false, 'message' => $message], is_array($extra) ? $extra : []));
    exit;
}
