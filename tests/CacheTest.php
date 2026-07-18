<?php
// tests/CacheTest.php

require_once __DIR__ . '/../lib/cache_helper.php';

class CacheTest {
    public function runAllTests() {
        echo "Running CacheUtility Validation Suite...\n";
        $this->testCacheGetSet();
        $this->testCacheExpiry();
        echo "All CacheUtility tests passed successfully!\n";
    }

    public function testCacheGetSet() {
        echo " - Checking Cache Set and Get...\n";
        $key = 'test_key_abc';
        $val = ['name' => 'John Doe', 'roles' => ['admin', 'user']];

        cache_set($key, $val, 10);
        $retrieved = cache_get($key);

        if ($retrieved !== $val) {
            throw new Exception("Retrieved cache value did not match set value");
        }

        cache_delete($key);
        if (cache_get($key) !== null) {
            throw new Exception("Cache key was not deleted successfully");
        }
    }

    public function testCacheExpiry() {
        echo " - Checking Cache Expiry...\n";
        $key = 'test_key_expires';
        $val = 'some_data';

        cache_set($key, $val, 1); // 1 second expiry

        $retrieved = cache_get($key);
        if ($retrieved !== $val) {
            throw new Exception("Immediate fetch should return cached data");
        }

        // Wait 2 seconds for expiry
        sleep(2);

        $expired = cache_get($key);
        if ($expired !== null) {
            throw new Exception("Expired cache key should return null, but returned: " . var_export($expired, true));
        }
    }
}

// Run the tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new CacheTest();
        $test->runAllTests();
        exit(0);
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>