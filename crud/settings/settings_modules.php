<?php
/**
 * Settings & Global Helpers
 * Tất cả truy vấn dùng prepared statements.
 */

require_once __DIR__ . '/../db_helpers/db_helpers.php';
require_once __DIR__ . '/../../config/db.php';

/* =====================================================================
   SINGLE VALUE
   ===================================================================== */
function getSetting(string $key, mixed $default = null): mixed {
    $row = crud_select_one(
        "SELECT value FROM settings WHERE `key` = ?", [$key], 's'
    );
    if (!$row) return $default;

    $val = $row['value'];
    if ($val === '1' || $val === 'true') return true;
    if ($val === '0' || $val === 'false') return false;
    if (is_numeric($val)) return $val + 0;
    return $val;
}

function setSetting(string $key, mixed $value): bool {
    $val = is_bool($value) ? ($value ? '1' : '0') : strval($value);
    $affected = crud_exec(
        "INSERT INTO settings (`key`, value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE value = ?",
        [$key, $val, $val], 'sss'
    );
    return $affected > 0;
}

/* =====================================================================
   ALL SETTINGS
   ===================================================================== */
function getAllSettings(): array {
    $rows = crud_select("SELECT `key`, value FROM settings");
    $settings = [];
    foreach ($rows as $row) {
        $val = $row['value'];
        if ($val === '1' || $val === 'true') $val = true;
        elseif ($val === '0' || $val === 'false') $val = false;
        elseif (is_numeric($val)) $val = $val + 0;
        $settings[$row['key']] = $val;
    }
    return $settings;
}

/* =====================================================================
   STORE SETTINGS
   ===================================================================== */
function getStoreName(): string       { return strval(getSetting('store_name', 'NEXUS STORE')); }
function getStoreIcon(): string      { return strval(getSetting('store_icon', 'fa-ghost')); }
function getStoreEmail(): string     { return strval(getSetting('store_email', '')); }
function getStorePhone(): string      { return strval(getSetting('store_phone', '')); }
function getTransactionFee(): int     { return intval(getSetting('transaction_fee', 0)); }
function getAutoHideOutOfStock(): bool { return (bool) getSetting('auto_hide_out_of_stock', false); }
function getEmailNotificationsEnabled(): bool { return (bool) getSetting('email_notifications_enabled', false); }

/* =====================================================================
   SePay SETTINGS
   ===================================================================== */
function getSePayEnabled(): bool        { return (bool) getSetting('sepay_enabled', false); }
function getSePayApiToken(): string    { return strval(getSetting('sepay_api_token', '')); }
function getSePayAccountNumber(): string { return strval(getSetting('sepay_account_number', '')); }
function getSePayAutoProcess(): bool   { return (bool) getSetting('sepay_auto_process', true); }

/* =====================================================================
   ICON HELPERS
   ===================================================================== */
function _nexus_brand_icons(): array {
    return [
        'fa-n','fa-youtube','fa-spotify','fa-play','fa-tiktok','fa-instagram',
        'fa-facebook','fa-twitter','fa-x-twitter','fa-steam','fa-twitch',
        'fa-discord','fa-snapchat','fa-whatsapp','fa-telegram','fa-amazon',
        'fa-apple','fa-google','fa-microsoft','fa-windows','fa-android',
        'fa-github','fa-gitlab','fa-bitbucket','fa-firefox','fa-chrome',
        'fa-edge','fa-safari','fa-opera','fa-bootstrap','fa-css3','fa-html5','fa-js',
        'fa-node','fa-php','fa-python','fa-java','fa-swift','fa-angular','fa-react',
        'fa-vuejs','fa-laravel','fa-wordpress','fa-drupal',
    ];
}

function nexus_icon(?string $icon = null): string {
    if ($icon === null) $icon = getStoreIcon();
    $icon = trim(strval($icon));
    if (!$icon) $icon = 'fa-ghost';
    if (in_array($icon, _nexus_brand_icons())) return 'fa-brands ' . $icon;
    return 'fa-solid ' . $icon;
}

function getIconClass(?string $icon): string {
    if (!$icon) return 'fa-solid fa-box';
    if (in_array($icon, _nexus_brand_icons())) return 'fa-brands ' . $icon;
    return 'fa-solid ' . $icon;
}

function resolveIconClass(string $icon): string { return getIconClass($icon); }

function storeIconClass(): string { return nexus_icon(getStoreIcon()); }
