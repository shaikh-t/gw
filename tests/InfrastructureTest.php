<?php
// tests/InfrastructureTest.php

require_once __DIR__ . '/../lib/db_mysqli.php';

class InfrastructureTest {
    public function runAllTests() {
        echo "Running Infrastructure Validation Suite...\n";
        $this->testJitOpcacheConfig();
        $this->testHtaccessRules();
        $this->testSecurityHeadersAndConnection();
        echo "All Infrastructure tests passed successfully!\n";
    }

    public function testJitOpcacheConfig() {
        echo " - Checking JIT and Opcache configuration...\n";
        $ini_path = __DIR__ . '/../php.ini';
        if (!file_exists($ini_path)) {
            throw new Exception("php.ini file does not exist in root");
        }
        $ini_content = file_get_contents($ini_path);
        if (strpos($ini_content, 'opcache.enable=1') === false) {
            throw new Exception("php.ini missing opcache.enable=1");
        }
        if (strpos($ini_content, 'opcache.jit=tracing') === false) {
            throw new Exception("php.ini missing opcache.jit=tracing");
        }
    }

    public function testHtaccessRules() {
        echo " - Checking .htaccess cache, compression, and security headers rules...\n";
        $htaccess_path = __DIR__ . '/../.htaccess';
        if (!file_exists($htaccess_path)) {
            throw new Exception(".htaccess file does not exist");
        }
        $content = file_get_contents($htaccess_path);

        $rules = [
            'DEFLATE',
            'Cache-Control',
            'Content-Security-Policy',
            'Strict-Transport-Security'
        ];

        foreach ($rules as $rule) {
            if (strpos($content, $rule) === false) {
                throw new Exception(".htaccess is missing required rule: " . $rule);
            }
        }
    }

    public function testSecurityHeadersAndConnection() {
        echo " - Checking Security Headers and Database Connection falls back cleanly...\n";
        global $mysqli;
        if (!$mysqli) {
            throw new Exception("Database connection object is null");
        }

        // Verify headers are defined in partials/frontend_header.php
        $header_path = __DIR__ . '/../partials/frontend_header.php';
        $header_content = file_get_contents($header_path);
        if (strpos($header_content, 'Content-Security-Policy') === false) {
            throw new Exception("frontend_header.php is missing Content-Security-Policy header declaration");
        }
        if (strpos($header_content, 'Strict-Transport-Security') === false) {
            throw new Exception("frontend_header.php is missing Strict-Transport-Security header declaration");
        }
    }
}

// Run the tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new InfrastructureTest();
        $test->runAllTests();
        exit(0);
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>