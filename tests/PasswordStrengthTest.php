<?php
// tests/PasswordStrengthTest.php

class PasswordStrengthTest {
    public function runAllTests() {
        echo "Running Password Strength Policy Validation Suite...\n";
        $this->testPasswordPolicyRegex();
        echo "Password Strength Policy tests passed successfully!\n";
    }

    public function testPasswordPolicyRegex() {
        echo " - Checking password policy rules...\n";

        $policy_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

        $test_cases = [
            // Strong / Valid passwords
            ['password' => 'P@ssword123', 'expected' => true],
            ['password' => 'UAE_Success_2026!', 'expected' => true],
            ['password' => 'Strong#Pass4', 'expected' => true],
            ['password' => 'bAnk_TrAnsfer_99#', 'expected' => true],

            // Weak / Invalid passwords
            ['password' => 'short', 'expected' => false],            // too short
            ['password' => 'PlainPassword123', 'expected' => false], // missing special char
            ['password' => 'plainpassword#', 'expected' => false],   // missing uppercase and digit
            ['password' => 'PLAINPASSWORD123!', 'expected' => false],// missing lowercase
            ['password' => '12345678#Aa', 'expected' => true],        // Valid
            ['password' => '12345678', 'expected' => false]          // missing alpha
        ];

        foreach ($test_cases as $tc) {
            $is_valid = (bool)preg_match($policy_regex, $tc['password']);
            if ($is_valid !== $tc['expected']) {
                throw new Exception("Password strength verification failed for: '" . $tc['password'] . "'. Expected: " . ($tc['expected'] ? "TRUE" : "FALSE") . ", Got: " . ($is_valid ? "TRUE" : "FALSE"));
            }
        }

        echo " - All strong and weak formats validated and filtered correctly.\n";
    }
}

// Run the tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new PasswordStrengthTest();
        $test->runAllTests();
        exit(0);
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>