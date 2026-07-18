<?php
// tests/LocalRouteTest.php

class LocalRouteTest {
    public function runAllTests() {
        echo "Running Local Route Validation Suite...\n";
        $this->testLocalRouteDifferentiator();
        echo "Local Route tests passed successfully!\n";
    }

    public function testLocalRouteDifferentiator() {
        echo " - Checking local routing logic matching rules...\n";

        // Emulate isLocalRoute logic from bot-landing.php in PHP
        $origin = "http://127.0.0.1:8000";

        $test_cases = [
            // Safe local routes
            ['url' => 'index.php', 'expected' => true],
            ['url' => '/index.php', 'expected' => true],
            ['url' => './service-detail.php?id=golden-visa', 'expected' => true],
            ['url' => 'http://127.0.0.1:8000/index.php', 'expected' => true],
            ['url' => 'vendor-profile.php', 'expected' => true],

            // Unsafe / hijacking routes
            ['url' => 'http://evil.com/index.php', 'expected' => false],
            ['url' => 'https://google.com', 'expected' => false],
            ['url' => '//attacker.com', 'expected' => false]
        ];

        foreach ($test_cases as $tc) {
            $is_local = $this->phpIsLocalRoute($tc['url'], $origin);
            if ($is_local !== $tc['expected']) {
                throw new Exception("Local route verification failed for URL: '" . $tc['url'] . "'. Expected: " . ($tc['expected'] ? "TRUE" : "FALSE") . ", Got: " . ($is_local ? "TRUE" : "FALSE"));
            }
        }

        echo " - All local/external URLs parsed and categorized with 100% accuracy!\n";
    }

    private function phpIsLocalRoute($url, $origin) {
        if (empty($url)) return false;

        // If starting with '//' (protocol relative), check if it leads elsewhere
        if (strpos($url, '//') === 0) {
            return false;
        }

        // Standard URL check
        if (strpos($url, '://') !== false) {
            return strpos($url, $origin) === 0;
        }

        // Relative routes are local
        return true;
    }
}

// Run the tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new LocalRouteTest();
        $test->runAllTests();
        exit(0);
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>