<?php
/**
 * lib/mock_mysqli.php
 * A stateful mock of mysqli to allow local page rendering and complete integration testing when the database is unavailable.
 */

class MockDbHelper {
    public static function get_path() {
        return __DIR__ . '/../var/mock_db.json';
    }

    public static function init() {
        $path = self::get_path();
        if (!file_exists(dirname($path))) {
            @mkdir(dirname($path), 0777, true);
        }
        if (!file_exists($path) || @filesize($path) === 0) {
            $default_db = [
                "ads" => [
                    [
                        "id" => 99,
                        "is_active" => 1,
                        "destination_url" => "http://127.0.0.1:8000/index.php",
                        "ad_source_type" => "direct_sponsor",
                        "ad_billing_model" => "cpc",
                        "click_cost" => 2.00,
                        "max_budget" => 100.00,
                        "current_spend" => 10.00
                    ]
                ],
                "cases" => [
                    [
                        "uuid" => "test-case-uuid",
                        "customer_user_id" => 1,
                        "provider_id" => 1,
                        "service_id" => 1,
                        "status" => "Quoted",
                        "service_price" => 150.00,
                        "service_currency" => "AED",
                        "customer_name" => "John Doe",
                        "service_title" => "Golden Visa Assistance",
                        "provider_name" => "Apex Legal"
                    ]
                ],
                "providers" => [
                    [
                        "id" => 1,
                        "deduction_type" => "percentage",
                        "deduction_value" => 10.00
                    ]
                ],
                "local_knowledge_base" => [
                    [
                        "text_content" => "This is the authoritative golden visa guide details.",
                        "file_name" => "golden_visa_regulations.pdf",
                        "page_number" => 4
                    ]
                ],
                "bot_ad_fraud_logs" => [],
                "bot_failed_questions" => [],
                "payment_transactions" => [],
                "customer_payments" => [],
                "customer_applications" => [],
                "users" => []
            ];
            @file_put_contents($path, json_encode($default_db, JSON_PRETTY_PRINT));
        }
    }

    public static function read() {
        self::init();
        $path = self::get_path();
        return json_decode(@file_get_contents($path), true) ?: [];
    }

    public static function write($data) {
        $path = self::get_path();
        @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }
}

class MockMySQLi {
    public $connect_errno = 0;
    public $connect_error = '';
    public $error = '';
    public $insert_id = 1;

    public function set_charset($charset) {
        return true;
    }

    public function query($sql) {
        $sql = trim(preg_replace('/\s+/', ' ', $sql));
        if (stripos($sql, 'CREATE TABLE') !== false) {
            return new MockMySQLiResult();
        }
        return new MockMySQLiResult();
    }

    public function prepare($sql) {
        return new MockMySQLiStmt($sql);
    }

    public function real_escape_string($str) {
        return addslashes($str);
    }

    public function begin_transaction() {
        return true;
    }

    public function commit() {
        return true;
    }

    public function rollback() {
        return true;
    }
}

class MockMySQLiResult {
    public $num_rows = 0;
    private $rows = [];
    private $currentIndex = 0;

    public function __construct($rows = []) {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_assoc() {
        if ($this->currentIndex < $this->num_rows) {
            return $this->rows[$this->currentIndex++];
        }
        return null;
    }

    public function fetch_row() {
        if ($this->currentIndex < $this->num_rows) {
            return array_values($this->rows[$this->currentIndex++]);
        }
        return null;
    }

    public function free() {
        return true;
    }
}

class MockMySQLiStmt {
    private $sql;
    private $params = [];
    public $insert_id = 1;
    public $error = '';
    private $result_rows = [];

    public function __construct($sql) {
        $this->sql = $sql;
    }

    public function bind_param($types, &...$args) {
        $this->params = [];
        foreach ($args as $arg) {
            $this->params[] = $arg;
        }
        return true;
    }

    public function execute() {
        $db = MockDbHelper::read();
        $sql = trim(preg_replace('/\s+/', ' ', $this->sql));

        if (stripos($sql, 'SELECT text_content, file_name, page_number FROM local_knowledge_base') !== false) {
            $search = $this->params[0] ?? '';
            $matched_rows = [];
            if (!empty($search)) {
                foreach ($db['local_knowledge_base'] as $row) {
                    if (stripos($row['text_content'], $search) !== false) {
                        $matched_rows[] = $row;
                    }
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'INSERT INTO bot_failed_questions') !== false) {
            $db['bot_failed_questions'][] = [
                'session_id' => $this->params[0] ?? null,
                'user_id' => $this->params[1] ?? null,
                'language_iso' => $this->params[2] ?? null,
                'unanswered_question' => $this->params[3] ?? null,
                'page_context_url' => $this->params[4] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['bot_failed_questions']);
        }
        elseif (stripos($sql, 'SELECT * FROM bot_ads WHERE id = ?') !== false) {
            $ad_id = (int)($this->params[0] ?? 0);
            $matched_rows = [];
            foreach ($db['ads'] as $ad) {
                if ($ad['id'] === $ad_id && $ad['is_active'] == 1) {
                    $matched_rows[] = $ad;
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT COUNT(*) AS click_count FROM bot_ad_fraud_logs') !== false) {
            $ad_id = (int)($this->params[0] ?? 0);
            $ip = $this->params[1] ?? '';
            $count = 0;
            foreach ($db['bot_ad_fraud_logs'] as $log) {
                if ($log['ad_id'] === $ad_id && $log['ip_address'] === $ip) {
                    $count++;
                }
            }
            $this->result_rows = [['click_count' => $count]];
        }
        elseif (stripos($sql, 'INSERT INTO bot_ad_fraud_logs') !== false) {
            $ad_id = (int)($this->params[0] ?? 0);
            $ip = $this->params[1] ?? '';
            $db['bot_ad_fraud_logs'][] = [
                'ad_id' => $ad_id,
                'ip_address' => $ip,
                'clicked_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['bot_ad_fraud_logs']);
        }
        elseif (stripos($sql, 'INSERT INTO bot_ad_clicks') !== false) {
            $ad_id = (int)($this->params[0] ?? 0);
            $session_id = $this->params[1] ?? null;
            $earned = (float)($this->params[2] ?? 0.0);
            $db['bot_ad_clicks'][] = [
                'ad_id' => $ad_id,
                'session_id' => $session_id,
                'earned_amount' => $earned,
                'clicked_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['bot_ad_clicks']);
        }
        elseif (stripos($sql, 'UPDATE bot_ads SET current_spend') !== false) {
            $cost = (float)($this->params[0] ?? 0.0);
            $ad_id = (int)($this->params[1] ?? 0);
            foreach ($db['ads'] as &$ad) {
                if ($ad['id'] === $ad_id) {
                    $ad['current_spend'] += $cost;
                    if ($ad['current_spend'] >= $ad['max_budget']) {
                        $ad['is_active'] = 0;
                    }
                }
            }
            MockDbHelper::write($db);
        }
        elseif (stripos($sql, 'SELECT id FROM payment_transactions WHERE transaction_id = ?') !== false) {
            $tx_id = $this->params[0] ?? '';
            $matched_rows = [];
            foreach ($db['payment_transactions'] as $tx) {
                if ($tx['transaction_id'] === $tx_id) {
                    $matched_rows[] = ['id' => 1];
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT c.*, p.name as provider_name') !== false) {
            $case_uuid = $this->params[0] ?? '';
            $matched_rows = [];
            foreach ($db['cases'] as $case) {
                if ($case['uuid'] === $case_uuid) {
                    $matched_rows[] = $case;
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT deduction_type, deduction_value FROM providers WHERE id = ?') !== false) {
            $provider_id = (int)($this->params[0] ?? 0);
            $matched_rows = [];
            foreach ($db['providers'] as $prov) {
                if ($prov['id'] === $provider_id) {
                    $matched_rows[] = $prov;
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'INSERT INTO payment_transactions') !== false) {
            $db['payment_transactions'][] = [
                'transaction_id' => $this->params[0] ?? '',
                'gross_amount' => $this->params[1] ?? 0.0,
                'platform_fee' => $this->params[2] ?? 0.0,
                'vendor_net_amount' => $this->params[3] ?? 0.0,
                'case_uuid' => $this->params[4] ?? '',
                'provider_id' => $this->params[5] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['payment_transactions']);
        }
        elseif (stripos($sql, 'UPDATE `cases` SET status =') !== false || stripos($sql, 'UPDATE cases SET status =') !== false) {
            $case_uuid = $this->params[0] ?? '';
            foreach ($db['cases'] as &$case) {
                if ($case['uuid'] === $case_uuid) {
                    $case['status'] = 'Booked';
                }
            }
            MockDbHelper::write($db);
        }
        elseif (stripos($sql, 'SELECT id FROM roles WHERE name = \'viewer\'') !== false) {
            $this->result_rows = [['id' => 3]];
        }
        elseif (stripos($sql, 'SELECT id FROM users WHERE email = ?') !== false) {
            $email = $this->params[0] ?? '';
            $matched_rows = [];
            foreach ($db['users'] as $user) {
                if ($user['email'] === $email) {
                    $matched_rows[] = $user;
                }
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'SELECT id, uuid, name, email, avatar FROM users') !== false) {
            $id_val = $this->params[0] ?? 1;
            $matched_rows = [];
            foreach ($db['users'] as $user) {
                if ($user['id'] == $id_val || $user['uuid'] === $id_val) {
                    $matched_rows[] = [
                        'id' => $user['id'],
                        'uuid' => $user['uuid'] ?? 'test-uuid-jane',
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'avatar' => null
                    ];
                }
            }
            if (empty($matched_rows)) {
                $matched_rows[] = [
                    'id' => 1,
                    'uuid' => 'test-user-uuid',
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'avatar' => null
                ];
            }
            $this->result_rows = $matched_rows;
        }
        elseif (stripos($sql, 'FROM user_roles') !== false) {
            $this->result_rows = [['name' => 'viewer']];
        }
        elseif (stripos($sql, 'INSERT INTO `users`') !== false || stripos($sql, 'INSERT INTO users') !== false) {
            $uuid = isset($this->params[0]) ? $this->params[0] : 'test-user-uuid';
            $name = isset($this->params[1]) ? $this->params[1] : '';
            $email = isset($this->params[2]) ? $this->params[2] : '';
            $db['users'][] = [
                'id' => count($db['users']) + 1,
                'uuid' => $uuid,
                'name' => $name,
                'email' => $email,
            ];
            MockDbHelper::write($db);
            $this->insert_id = count($db['users']);
        }
        else {
            $this->result_rows = [];
        }

        return true;
    }

    public function get_result() {
        return new MockMySQLiResult($this->result_rows);
    }

    public function bind_result(&...$args) {
        return true;
    }

    public function fetch() {
        return null;
    }

    public function close() {
        return true;
    }
}
?>