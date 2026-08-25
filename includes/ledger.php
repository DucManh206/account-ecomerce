<?php

function record_balance_transaction(
    PDO $pdo,
    int $userId,
    string $type,
    float $amount,
    ?float $balanceAfter,
    string $sourceType,
    ?int $sourceId,
    string $description,
    ?int $createdBy = null
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO balance_transactions
            (user_id, transaction_type, amount, balance_after, source_type, source_id, description, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $type,
        $amount,
        $balanceAfter,
        $sourceType,
        $sourceId,
        $description,
        $createdBy,
    ]);
}

function record_admin_activity(
    PDO $pdo,
    string $action,
    string $entityType,
    ?int $entityId,
    string $description,
    array $metadata = []
): void {
    $adminId = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare(
        'INSERT INTO admin_activity_logs
            (admin_id, action, entity_type, entity_id, description, metadata_json, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $adminId,
        $action,
        $entityType,
        $entityId,
        $description,
        $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        $ipAddress,
    ]);
}

