<?php
// tests/WebhookSignatureTest.php

class WebhookSignatureTest {
    public function runAllTests() {
        echo "Running Webhook Signature Validation Suite...\n";
        $this->testIncorrectSignatureDrop();
        echo "Webhook Signature tests passed successfully!\n";
    }

    public function testIncorrectSignatureDrop() {
        echo " - Dispatching webhook with incorrect cryptographic signature token...\n";

        $url = 'http://127.0.0.1:8000/api/payment-webhook.php';
        $payload = json_encode([
            'event' => 'payment_intent.succeeded',
            'case_uuid' => 'test-case-uuid',
            'transaction_id' => 'tx_test_123'
        ]);

        // Sign with incorrect signature token
        $timestamp = time();
        $signed_payload = $timestamp . '.' . $payload;
        $incorrect_secret = 'whsec_WRONG_SECRET_TOKEN_abc';
        $signature = hash_hmac('sha256', $signed_payload, $incorrect_secret);
        $sig_header = "t=" . $timestamp . ",v1=" . $signature;

        // Perform curl check
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'HTTP_STRIPE_SIGNATURE: ' . $sig_header,
            'HTTP_X_STRIPE_SIGNATURE: ' . $sig_header
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Assert that the connection is dropped with an unauthorized 401 status code
        if ($http_code !== 401) {
            throw new Exception("Webhook Signature vulnerability: Expected 401 Unauthorized, but got HTTP " . $http_code . " with response: " . var_export($response, true));
        }

        echo " - Cryptographic verification succeeded! Connection was cleanly dropped with HTTP 401 Unauthorized.\n";
    }
}

// Run the tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $test = new WebhookSignatureTest();
        $test->runAllTests();
        exit(0);
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>