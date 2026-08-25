<?php

function load_app_settings(PDO $pdo): array
{
    $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function app_setting(string $key, $default = null)
{
    global $settingsMap;
    return array_key_exists($key, $settingsMap ?? []) ? $settingsMap[$key] : $default;
}

function app_setting_int(string $key, int $default, int $minimum = 0, ?int $maximum = null): int
{
    $value = filter_var(app_setting($key, $default), FILTER_VALIDATE_INT);
    $value = $value === false ? $default : $value;
    $value = max($minimum, $value);
    return $maximum === null ? $value : min($maximum, $value);
}

function app_setting_bool(string $key, bool $default = false): bool
{
    $value = app_setting($key, $default ? '1' : '0');
    return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
}

function save_app_settings(PDO $pdo, array $values): void
{
    global $settingsMap;
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    foreach ($values as $key => $value) {
        $stmt->execute([(string) $key, (string) $value]);
        $settingsMap[(string) $key] = (string) $value;
    }
}

