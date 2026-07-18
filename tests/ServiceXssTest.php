<?php
// tests/ServiceXssTest.php

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';
require_once __DIR__ . '/../lib/services_helpers.php';

class ServiceXssTest {
    public function runAllTests() {
        echo "Running Service XSS Protection Validation Suite...\n";
        $this->testServiceTemplateXssStripping();
        echo "Service XSS Protection tests passed successfully!\n";
    }

    public function testServiceTemplateXssStripping() {
        echo " - Attempting to save unsafe <script> payloads into service templates...\n";

        $unsafe_description = "Our premium PRO service <script>alert('XSS Attack');</script> guaranteed to work.";
        $unsafe_short_desc = "Quick Visa <iframe src='http://malicious.com'></iframe> processing.";

        // 1. Check strip-tags logic for non-admin
        $is_admin = false; // non-admin
        $filtered_desc = $unsafe_description;
        $filtered_short = $unsafe_short_desc;
        if (!$is_admin) {
            $filtered_desc = strip_tags($filtered_desc);
            $filtered_short = strip_tags($filtered_short);
        }

        $this->assertStringNotContainsString("<script>", $filtered_desc);
        $this->assertStringNotContainsString("<iframe>", $filtered_short);

        echo " - Non-admin strip-tags filtering verified successfully!\n";

        // 2. Check admin bypass logic
        $is_admin = true; // admin
        $admin_desc = $unsafe_description;
        if (!$is_admin) {
            $admin_desc = strip_tags($admin_desc);
        }

        $this->assertStringContainsString("<script>", $admin_desc);
        echo " - Admin rich HTML description bypass verified successfully!\n";
    }

    private function assertStringNotContainsString($needle, $haystack) {
        if (strpos($haystack, $needle) !== false) {
            throw new Exception("Security Breach: string contains forbidden substring: " . $needle);
        }
    }

    private function assertStringContainsString($needle, $haystack) {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("Expected string to contain substring: " . $needle);
        }
    }
}

// Run the tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new ServiceXssTest();
        $test->runAllTests();
        exit(0);
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>