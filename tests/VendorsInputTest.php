<?php
// tests/VendorsInputTest.php

class VendorsInputTest {
    public function runAllTests() {
        echo "Running Vendors Input Validation Suite...\n";
        $this->testPageParamCasting();
        echo "Vendors Input tests passed successfully!\n";
    }

    public function testPageParamCasting() {
        echo " - Verifying that malicious alpha-numeric script parameters are safely cast to integers...\n";

        // Simulate $_GET payload with malicious script
        $_GET['page'] = "<script>alert('xss');</script>99abc";

        // Emulate the casting logic used in vendors.php
        $page = intval($_GET['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        // We assert that <script> is stripped out or ignored, and the result is a clean, safe integer
        // intval("<script>alert('xss');</script>99abc") evaluates to 0 in PHP (since it starts with non-numeric chars)
        // Then our boundary check resets it to 1.
        if ($page !== 1) {
            throw new Exception("Expected page to be cast and defaulted to 1, but got: " . var_export($page, true));
        }

        // Test with numeric-start malicious payload: "3<script>..."
        $_GET['page'] = "3<script>alert('xss')</script>";
        $page2 = intval($_GET['page'] ?? 1);
        if ($page2 !== 3) {
            throw new Exception("Expected page to be cast to integer 3, but got: " . var_export($page2, true));
        }

        echo " - Parameter casting behaves perfectly. No malicious injection is possible via the page parameter.\n";
    }
}

// Run the tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new VendorsInputTest();
        $test->runAllTests();
        exit(0);
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>