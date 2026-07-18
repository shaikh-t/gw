<?php
/**
 * lib/cache_helper.php
 * Intelligent, environment-adaptive cache utility supporting APCu and falling back to a file-based JSON cache.
 */

class CacheUtility {
    private static $use_apcu = null;
    private static $cache_dir = null;

    private static function init() {
        if (self::$use_apcu === null) {
            self::$use_apcu = function_exists('apcu_fetch') && ini_get('apcu.enabled');
            self::$cache_dir = __DIR__ . '/../var/cache';
            if (!self::$use_apcu) {
                if (!file_exists(self::$cache_dir)) {
                    @mkdir(self::$cache_dir, 0777, true);
                }
            }
        }
    }

    private static function get_file_path($key) {
        $safe_key = md5($key);
        return self::$cache_dir . '/' . $safe_key . '.cache';
    }

    public static function get($key) {
        self::init();
        if (self::$use_apcu) {
            $success = false;
            $val = apcu_fetch($key, $success);
            return $success ? $val : null;
        }

        // File-based fallback
        $path = self::get_file_path($key);
        if (file_exists($path)) {
            $data = json_decode(@file_get_contents($path), true);
            if ($data && isset($data['expires']) && isset($data['value'])) {
                if ($data['expires'] === 0 || $data['expires'] > time()) {
                    return unserialize($data['value']);
                }
                // Expired
                @unlink($path);
            }
        }
        return null;
    }

    public static function set($key, $value, $ttl = 3600) {
        self::init();
        if (self::$use_apcu) {
            return apcu_store($key, $value, $ttl);
        }

        // File-based fallback
        $path = self::get_file_path($key);
        $data = [
            'expires' => ($ttl === 0) ? 0 : (time() + $ttl),
            'value' => serialize($value)
        ];
        return @file_put_contents($path, json_encode($data)) !== false;
    }

    public static function delete($key) {
        self::init();
        if (self::$use_apcu) {
            return apcu_delete($key);
        }

        $path = self::get_file_path($key);
        if (file_exists($path)) {
            return @unlink($path);
        }
        return true;
    }

    public static function clear() {
        self::init();
        if (self::$use_apcu) {
            return apcu_clear_cache();
        }

        if (file_exists(self::$cache_dir)) {
            $files = glob(self::$cache_dir . '/*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        return true;
    }
}

// Global procedural functions for convenience
function cache_get($key) {
    return CacheUtility::get($key);
}

function cache_set($key, $value, $ttl = 3600) {
    return CacheUtility::set($key, $value, $ttl);
}

function cache_delete($key) {
    return CacheUtility::delete($key);
}

function cache_clear() {
    return CacheUtility::clear();
}
?>