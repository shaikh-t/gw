<?php
// lib/settings_helper.php
require_once __DIR__ . '/db_mysqli.php';

function get_setting($key, $default = '') {
    require_once __DIR__ . '/cache_helper.php';
    $cached = cache_get('setting_' . $key);
    if ($cached !== null) {
        return $cached;
    }
    global $mysqli;
    $key = $mysqli->real_escape_string($key);
    $res = $mysqli->query("SELECT value FROM site_settings WHERE `key` = '$key' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $val = $row['value'];
        cache_set('setting_' . $key, $val, 3600);
        return $val;
    }
    return $default;
}

function get_all_settings() {
    require_once __DIR__ . '/cache_helper.php';
    $cached = cache_get('all_site_settings');
    if ($cached !== null) {
        return $cached;
    }
    global $mysqli;
    $res = $mysqli->query("SELECT `key`, `value` FROM site_settings");
    $settings = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $settings[$row['key']] = $row['value'];
        }
    }
    cache_set('all_site_settings', $settings, 3600);
    return $settings;
}
