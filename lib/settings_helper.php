<?php
// lib/settings_helper.php
require_once __DIR__ . '/db_mysqli.php';

function get_setting($key, $default = '') {
    global $mysqli;
    $key = $mysqli->real_escape_string($key);
    $res = $mysqli->query("SELECT value FROM site_settings WHERE `key` = '$key' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        return $row['value'];
    }
    return $default;
}

function get_all_settings() {
    global $mysqli;
    $res = $mysqli->query("SELECT `key`, `value` FROM site_settings");
    $settings = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $settings[$row['key']] = $row['value'];
        }
    }
    return $settings;
}
