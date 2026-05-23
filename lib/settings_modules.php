<?php
/**
 * Nexus Store - Settings & Global Helpers
 */

require_once __DIR__ . '/../database/connect.php';

function getSetting($key, $default = null) {
    global $conn;
    if (!$conn) return $default;
    $key = $conn->real_escape_string($key);
    $result = mysqli_query($conn, "SELECT value FROM settings WHERE `key` = '$key' LIMIT 1");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $val = $row['value'];
        if ($val === '1' || $val === 'true') return true;
        if ($val === '0' || $val === 'false') return false;
        if (is_numeric($val)) return $val + 0;
        return $val;
    }
    return $default;
}

function setSetting($key, $value) {
    global $conn;
    if (!$conn) return false;
    $key = $conn->real_escape_string($key);
    $val = is_bool($value) ? ($value ? '1' : '0') : strval($value);
    $val = $conn->real_escape_string($val);
    $sql = "INSERT INTO settings (`key`, value) VALUES ('$key', '$val') "
         . "ON DUPLICATE KEY UPDATE value = '$val'";
    return mysqli_query($conn, $sql);
}

function getAllSettings() {
    global $conn;
    $settings = [];
    if (!$conn) return $settings;
    $result = mysqli_query($conn, "SELECT `key`, value FROM settings");
    while ($row = mysqli_fetch_assoc($result)) {
        $val = $row['value'];
        if ($val === '1' || $val === 'true') $val = true;
        elseif ($val === '0' || $val === 'false') $val = false;
        elseif (is_numeric($val)) $val = $val + 0;
        $settings[$row['key']] = $val;
    }
    return $settings;
}

// Store name
function getStoreName() {
    return getSetting('store_name', 'NEXUS STORE');
}

// Store icon (raw value from DB)
function getStoreIcon() {
    return getSetting('store_icon', 'fa-ghost');
}

// Store icon with proper FA prefix
function getStoreIconClass() {
    return nexus_icon('store_icon');
}

// Brand icons list (shared)
function _nexus_brand_icons() {
    return ['fa-n','fa-youtube','fa-spotify','fa-play','fa-tiktok','fa-instagram',
            'fa-facebook','fa-twitter','fa-x-twitter','fa-steam','fa-twitch',
            'fa-discord','fa-snapchat','fa-whatsapp','fa-telegram','fa-amazon',
            'fa-apple','fa-google','fa-microsoft','fa-windows','fa-android',
            'fa-github','fa-gitlab','fa-bitbucket','fa-firefox','fa-chrome',
            'fa-edge','fa-safari','fa-opera','fa-bootstrap','fa-css3','fa-html5','fa-js',
            'fa-node','fa-php','fa-python','fa-java','fa-swift','fa-angular','fa-react',
            'fa-vuejs','fa-laravel','fa-wordpress','fa-drupal'];
}

/**
 * Get icon with proper FontAwesome prefix.
 * Usage: nexus_icon('store_icon') or nexus_icon($anyIconValue)
 * Returns full class like "fa-brands fa-n" or "fa-solid fa-ghost"
 */
function nexus_icon($icon = null) {
    if ($icon === null) $icon = getStoreIcon();
    $icon = trim(strval($icon));
    if (!$icon) $icon = 'fa-ghost';
    if (in_array($icon, _nexus_brand_icons())) return 'fa-brands ' . $icon;
    return 'fa-solid ' . $icon;
}

/**
 * Resolve any icon class to proper FA prefix (fa-solid or fa-brands)
 */
function getIconClass($icon) {
    if (!$icon) return 'fa-solid fa-box';
    if (in_array($icon, _nexus_brand_icons())) return 'fa-brands ' . $icon;
    return 'fa-solid ' . $icon;
}

function resolveIconClass($icon) { return getIconClass($icon); }

// Other settings
function getStoreEmail() {
    return getSetting('store_email', '');
}

function getTransactionFee() {
    return intval(getSetting('transaction_fee', 0));
}

function getAutoHideOutOfStock() {
    return getSetting('auto_hide_out_of_stock', false);
}

function getEmailNotificationsEnabled() {
    return getSetting('email_notifications_enabled', false);
}

function getSettingWithDefault($key, $default) {
    return getSetting($key, $default);
}
