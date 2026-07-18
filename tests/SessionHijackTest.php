<?php
// tests/SessionHijackTest.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/auth.php';

class SessionHijackTest {
    public function runAllTests() {
        echo "Running Session Hijacking Prevention Validation Suite...\n";
        $this->testSessionHijackingPrevention();
        echo "Session Hijacking Prevention tests passed successfully!\n";
    }

    public function testSessionHijackingPrevention() {
        echo " - Checking session binding drift checks...\n";

        // Clear existing session user
        $_SESSION = [];

        // 1. Establish a secure session with current bindings
        $_SESSION['user'] = [
            'id' => 1,
            'uuid' => 'test-user-uuid',
            'name' => 'Secure User',
            'email' => 'secure.user@example.com'
        ];

        $_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64)";
        $_SERVER['REMOTE_ADDR'] = "127.0.0.1";

        // Bind the session
        $bound = validate_session_bindings();
        $this->assertTrue($bound, "Initial session binding should succeed");
        $this->assertEquals($_SESSION['session_user_agent'], "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");

        // Retrieve current user
        $user = current_user();
        $this->assertNotNull($user, "Retrieved user should not be null under matching bindings");

        // 2. Simulate session hijacking by drifting/modifying the User-Agent string
        echo " - Simulating User-Agent tampering...\n";
        $_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1";

        // Call current_user() or validate_session_bindings()
        $user_drifed = current_user();

        // The session must be instantly cleared, and returned user must be null!
        $this->assertNull($user_drifed, "Drifted user agent should invalidate session and return null");
        $this->assertTrue(empty($_SESSION['user']), "Session user context should have been completely purged");

        echo " - Session hijacking detected and neutralized perfectly on User-Agent drift!\n";
    }

    private function assertTrue($condition, $msg = '') {
        if (!$condition) {
            throw new Exception($msg ?: "Expected true, got false");
        }
    }

    private function assertEquals($val1, $val2, $msg = '') {
        if ($val1 !== $val2) {
            throw new Exception($msg ?: "Expected " . var_export($val1, true) . " to equal " . var_export($val2, true));
        }
    }

    private function assertNotNull($val, $msg = '') {
        if ($val === null) {
            throw new Exception($msg ?: "Expected non-null value");
        }
    }

    private function assertNull($val, $msg = '') {
        if ($val !== null) {
            throw new Exception($msg ?: "Expected null value, but got: " . var_export($val, true));
        }
    }
}

// Run the tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new SessionHijackTest();
        $test->runAllTests();
        exit(0);
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>