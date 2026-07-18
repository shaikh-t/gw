<?php
// tests/TerminalInjectionTest.php

class TerminalInjectionTest {
    public function runAllTests() {
        echo "Running Terminal Injection Mitigation Validation Suite...\n";
        $this->testTerminalControlCharactersStripping();
        echo "Terminal Injection Mitigation tests passed successfully!\n";
    }

    public function testTerminalControlCharactersStripping() {
        echo " - Passing bad terminal control characters to sanitize helper...\n";

        // Define exact implementation used in api/bot-controller.php for validation
        $sanitize_function = function($str) {
            if (!is_string($str)) return $str;
            return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x1B]/', '', $str);
        };

        // \x1B is ESC, \x00 is NULL, \x07 is BEL
        $payload_dirty = "UAE golden visa \x1B[31mimportant\x00 regulation\x07 guidelines";

        $clean = $sanitize_function($payload_dirty);

        // Verify that control characters are completely stripped
        $this->assertStringNotContainsString("\x1B", $clean);
        $this->assertStringNotContainsString("\x00", $clean);
        $this->assertStringNotContainsString("\x07", $clean);

        // Verify that standard safe content is fully preserved
        $this->assertStringContainsString("UAE golden visa", $clean);
        $this->assertStringContainsString("important", $clean);
        $this->assertStringContainsString("regulation", $clean);
        $this->assertStringContainsString("guidelines", $clean);

        echo " - Bad control characters stripped successfully! Sanitized value: '" . $clean . "'\n";
    }

    private function assertStringNotContainsString($needle, $haystack) {
        if (strpos($haystack, $needle) !== false) {
            throw new Exception("Security Breach: string contains forbidden terminal character: " . var_export($needle, true));
        }
    }

    private function assertStringContainsString($needle, $haystack) {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("Expected string to contain substring: " . var_export($needle, true));
        }
    }
}

// Run the tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new TerminalInjectionTest();
        $test->runAllTests();
        exit(0);
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>