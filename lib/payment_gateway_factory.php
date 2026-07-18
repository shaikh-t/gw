<?php
// lib/payment_gateway_factory.php
require_once __DIR__ . '/db_mysqli.php';

interface PaymentGatewayInterface {
    public function getName(): string;
    public function isEnabled(): bool;
    public function isSandbox(): bool;
    public function initializePayment(float $amount, string $currency, string $case_uuid): array;
    public function processPayment(array $post_data): array;
}

abstract class AbstractPaymentGateway implements PaymentGatewayInterface {
    protected $name;
    protected $public_key;
    protected $secret_key;
    protected $sandbox_mode;
    protected $is_enabled;

    public function __construct(string $name, ?array $configData = null) {
        global $mysqli;
        $this->name = $name;

        if ($configData !== null) {
            $this->public_key = $configData['public_key'] ?? '';
            $this->secret_key = $configData['secret_key'] ?? '';
            $this->sandbox_mode = isset($configData['sandbox_mode']) ? (bool)$configData['sandbox_mode'] : true;
            $this->is_enabled = isset($configData['is_enabled']) ? (bool)$configData['is_enabled'] : false;
        } else {
            // Fallback for single initialization
            $stmt = $mysqli->prepare("SELECT * FROM `payment_gateways` WHERE `name` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $name);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $this->public_key = $row['public_key'];
                    $this->secret_key = $row['secret_key'];
                    $this->sandbox_mode = (bool)$row['sandbox_mode'];
                    $this->is_enabled = (bool)$row['is_enabled'];
                } else {
                    $this->public_key = '';
                    $this->secret_key = '';
                    $this->sandbox_mode = true;
                    $this->is_enabled = false;
                }
                $stmt->close();
            } else {
                $this->public_key = '';
                $this->secret_key = '';
                $this->sandbox_mode = true;
                $this->is_enabled = false;
            }
        }

        // Production Escrow Override: Check and load configuration from central global constants if defined
        $const_name = $name;
        if ($const_name === 'Authorize.net') {
            $const_prefix = 'AUTHORIZENET';
        } else {
            $const_prefix = strtoupper($const_name);
        }

        if (defined($const_prefix . '_PUBLIC_KEY')) {
            $this->public_key = constant($const_prefix . '_PUBLIC_KEY');
        }
        if (defined($const_prefix . '_SECRET_KEY')) {
            $this->secret_key = constant($const_prefix . '_SECRET_KEY');
        }
        if (defined($const_prefix . '_SANDBOX_MODE')) {
            $this->sandbox_mode = (bool)constant($const_prefix . '_SANDBOX_MODE');
        }
    }

    public function getName(): string {
        return $this->name;
    }

    public function isEnabled(): bool {
        return $this->is_enabled;
    }

    public function isSandbox(): bool {
        return $this->sandbox_mode;
    }
}

class StripeGateway extends AbstractPaymentGateway {
    public function __construct(?array $configData = null) {
        parent::__construct('Stripe', $configData);
    }

    public function initializePayment(float $amount, string $currency, string $case_uuid): array {
        return [
            'gateway' => 'Stripe',
            'amount' => $amount,
            'currency' => $currency,
            'case_uuid' => $case_uuid,
            'payment_intent_id' => 'pi_mock_' . bin2hex(random_bytes(12)),
            'client_secret' => 'seti_mock_' . bin2hex(random_bytes(16))
        ];
    }

    public function processPayment(array $post_data): array {
        $force_result = $post_data['force_result'] ?? 'success';
        if ($force_result === 'fail') {
            return [
                'success' => false,
                'message' => 'Stripe payment was declined. Insufficient funds.'
            ];
        }
        return [
            'success' => true,
            'transaction_id' => 'ch_' . bin2hex(random_bytes(12)),
            'message' => 'Stripe payment authorized and completed successfully!'
        ];
    }
}

class PayPalGateway extends AbstractPaymentGateway {
    public function __construct(?array $configData = null) {
        parent::__construct('PayPal', $configData);
    }

    public function initializePayment(float $amount, string $currency, string $case_uuid): array {
        return [
            'gateway' => 'PayPal',
            'amount' => $amount,
            'currency' => $currency,
            'case_uuid' => $case_uuid,
            'order_id' => 'EC-MOCK' . strtoupper(bin2hex(random_bytes(8)))
        ];
    }

    public function processPayment(array $post_data): array {
        $force_result = $post_data['force_result'] ?? 'success';
        if ($force_result === 'fail') {
            return [
                'success' => false,
                'message' => 'PayPal checkout process was cancelled or failed.'
            ];
        }
        return [
            'success' => true,
            'transaction_id' => 'PAY-' . strtoupper(bin2hex(random_bytes(10))),
            'message' => 'PayPal order captured successfully!'
        ];
    }
}

class AuthorizeNetGateway extends AbstractPaymentGateway {
    public function __construct(?array $configData = null) {
        parent::__construct('Authorize.net', $configData);
    }

    public function initializePayment(float $amount, string $currency, string $case_uuid): array {
        return [
            'gateway' => 'Authorize.net',
            'amount' => $amount,
            'currency' => $currency,
            'case_uuid' => $case_uuid,
            'payment_profile_id' => 'prof_mock_' . bin2hex(random_bytes(8))
        ];
    }

    public function processPayment(array $post_data): array {
        $force_result = $post_data['force_result'] ?? 'success';
        if ($force_result === 'fail') {
            return [
                'success' => false,
                'message' => 'Authorize.net transaction error: 200 - The card was declined.'
            ];
        }
        return [
            'success' => true,
            'transaction_id' => 'trans_' . bin2hex(random_bytes(10)),
            'message' => 'Authorize.net transaction completed successfully!'
        ];
    }
}

class PaymentGatewayFactory {
    public static function getGateway(string $name, ?array $configData = null): ?PaymentGatewayInterface {
        switch ($name) {
            case 'Stripe':
                return new StripeGateway($configData);
            case 'PayPal':
                return new PayPalGateway($configData);
            case 'Authorize.net':
                return new AuthorizeNetGateway($configData);
            default:
                return null;
        }
    }

    public static function getEnabledGateways(): array {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['cached_enabled_gateways'])) {
            return $_SESSION['cached_enabled_gateways'];
        }
        global $mysqli;
        $gateways = [];
        // Single unified SQL query fetching all active gateways configurations at once to avoid N+1 query loops.
        $res = $mysqli->query("SELECT * FROM `payment_gateways` WHERE `is_enabled` = 1 ORDER BY id ASC");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $gw = self::getGateway($row['name'], $row);
                if ($gw) {
                    $gateways[] = $gw;
                }
            }
            $res->free();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['cached_enabled_gateways'] = $gateways;
        }
        return $gateways;
    }
}
?>