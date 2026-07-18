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

    private static function should_bypass_cache() {
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $script = $_SERVER['SCRIPT_NAME'];
            if (strpos($script, '/admin/') !== false ||
                strpos($script, '/customer/') !== false ||
                strpos($script, '/providers/') !== false ||
                strpos($script, '/vendor/') !== false) {
                return true;
            }
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '/admin/') !== false ||
                strpos($uri, '/customer/') !== false ||
                strpos($uri, '/providers/') !== false ||
                strpos($uri, '/vendor/') !== false) {
                return true;
            }
        }
        return false;
    }

    private static function get_file_path($key) {
        $safe_key = md5($key);
        return self::$cache_dir . '/' . $safe_key . '.cache';
    }

    public static function get($key) {
        if (self::should_bypass_cache()) {
            return null;
        }
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

        // 1. Clear APCu if available
        if (function_exists('apcu_clear_cache')) {
            @apcu_clear_cache();
        }

        // 2. Clear Redis if extension is available
        if (class_exists('Redis')) {
            try {
                $redis = new Redis();
                if (@$redis->connect('127.0.0.1', 6379)) {
                    @$redis->flushAll();
                }
            } catch (Throwable $t) {
                // Ignore Redis errors if it is not running
            }
        }

        // 3. Recursively delete file-based fragments inside var/cache/
        if (file_exists(self::$cache_dir)) {
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(self::$cache_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        @unlink($file->getRealPath());
                    } elseif ($file->isDir()) {
                        @rmdir($file->getRealPath());
                    }
                }
            } catch (Throwable $t) {
                // Fallback to glob if recursive iterator fails
                $files = glob(self::$cache_dir . '/*.cache');
                if (is_array($files)) {
                    foreach ($files as $file) {
                        @unlink($file);
                    }
                }
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