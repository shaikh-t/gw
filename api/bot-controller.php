<?php
// api/bot-controller.php
header('Content-Type: application/json; charset=utf-8');

// Error reporting setup for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/uuid_helper.php';

// Helper function to safely send JSON responses
function send_json_response(array $data, int $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// 1. Validate HTTP request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response([
        'status' => 'error',
        'message' => 'Only POST requests are allowed.'
    ], 405);
}

// 2. Decode JSON input payload
$input_raw = file_get_contents('php://input');
$input = json_decode($input_raw, true);

if (!is_array($input)) {
    send_json_response([
        'status' => 'error',
        'message' => 'Invalid or missing JSON payload.'
    ], 400);
}

// Ensure database connection is active/declared
if (!isset($mysqli)) {
    send_json_response([
        'status' => 'error',
        'message' => 'Database connection variable is not defined.'
    ], 500);
}

$session_token = isset($input['session_token']) ? trim($input['session_token']) : '';
$node_id = isset($input['node_id']) ? (int)$input['node_id'] : null;
$message_content = isset($input['message']) ? trim($input['message']) : '';

// 3. Determine if we are running in Mock Fallback mode (no real MySQL server)
$is_mock_mode = (get_class($mysqli) === 'MockMySQLi');

if ($is_mock_mode) {
    // ---- MOCK FALLBACK MODE ENGINE ----
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Baseline mock data structures
    $mock_nodes = [
        1 => [
            'id' => 1,
            'parent_id' => null,
            'node_type' => 'greeting',
            'language_iso' => 'en',
            'display_text' => 'Welcome to GlobalWays! Please select your preferred language to begin.',
            'spoken_text' => 'Welcome to GlobalWays! Please select your preferred language to begin.',
            'target_action' => null
        ],
        2 => [
            'id' => 2,
            'parent_id' => 1,
            'node_type' => 'category_select',
            'language_iso' => 'en',
            'display_text' => 'Hello! Welcome to GlobalWays. We simplify UAE documentation. Please select a service category to get started.',
            'spoken_text' => 'Hello! Welcome to GlobalWays. We simplify UAE documentation. Please select a service category to get started.',
            'target_action' => 'fetch_categories'
        ],
        3 => [
            'id' => 3,
            'parent_id' => 1,
            'node_type' => 'category_select',
            'language_iso' => 'fr',
            'display_text' => 'Bonjour! Bienvenue sur GlobalWays. Nous simplifions les démarches administratives aux Émirats Arabes Unis. Veuillez sélectionner une catégorie de service pour commencer.',
            'spoken_text' => 'Bonjour! Bienvenue sur GlobalWays. Nous simplifions les démarches administratives aux Émirats Arabes Unis. Veuillez sélectionner une catégorie de service pour commencer.',
            'target_action' => 'fetch_categories'
        ],
        4 => [
            'id' => 4,
            'parent_id' => 1,
            'node_type' => 'category_select',
            'language_iso' => 'ar',
            'display_text' => 'مرحباً بك في غلوبال وايز! نحن نبسط الإجراءات والمعاملات الرسمية في دولة الإمارات العربية المتحدة. يرجى اختيار قسم الخدمة للبدء.',
            'spoken_text' => 'مرحباً بك في غلوبال وايز! نحن نبسط الإجراءات والمعاملات الرسمية في دولة الإمارات العربية المتحدة. يرجى اختيار قسم الخدمة للبدء.',
            'target_action' => 'fetch_categories'
        ],
        5 => [
            'id' => 5,
            'parent_id' => 1,
            'node_type' => 'category_select',
            'language_iso' => 'ur',
            'display_text' => 'گلوبل ویز میں خوش آمدید! ہم متحدہ عرب امارات کے کاغذی کام کو آسان بناتے ہیں۔ شروع کرنے کے لیے برائے مہربانی ایک سروس کیٹیگری منتخب کریں۔',
            'spoken_text' => 'گلوبل ویز میں خوش آمدید! ہم متحدہ عرب امارات کے کاغذی کام کو آسان بناتے ہیں۔ شروع کرنے کے لیے برائے مہربانی ایک سروس کیٹیگری منتخب کریں۔',
            'target_action' => 'fetch_categories'
        ],
        6 => [
            'id' => 6,
            'parent_id' => null,
            'node_type' => 'category_handler',
            'language_iso' => 'en',
            'display_text' => 'Got it! You\'ve selected a service category. We are preparing the registration and booking context for you.',
            'spoken_text' => 'Got it! You\'ve selected a service category. We are preparing the registration and booking context for you.',
            'target_action' => 'handle_category'
        ]
    ];

    $mock_categories = [
        ['id' => 1, 'uuid' => 'cat-imm-123', 'name' => 'Immigration Services'],
        ['id' => 2, 'uuid' => 'cat-vis-456', 'name' => 'Visit Visa'],
        ['id' => 3, 'uuid' => 'cat-bus-789', 'name' => 'Business Setup']
    ];

    // Resolve or initialize session token
    if (empty($session_token) || !isset($_SESSION['mock_sessions'][$session_token])) {
        $session_token = bin2hex(random_bytes(32));
        $_SESSION['mock_sessions'][$session_token] = [
            'session_token' => $session_token,
            'selected_language' => null,
            'current_node_id' => 1,
            'chat_logs' => []
        ];
    }

    $session = &$_SESSION['mock_sessions'][$session_token];

    // Log user message
    if (!empty($message_content)) {
        $session['chat_logs'][] = [
            'sender' => 'user',
            'message_content' => $message_content,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    $current_node_id = (int)$session['current_node_id'];
    $next_node_id = $node_id ?: $current_node_id;

    // Fetch target node
    if (!isset($mock_nodes[$next_node_id])) {
        $next_node_id = 1;
    }
    $target_node = $mock_nodes[$next_node_id];

    // Handle language transition
    if ((int)$target_node['parent_id'] === 1 && $next_node_id !== 1) {
        $session['selected_language'] = $target_node['language_iso'];
    }
    $session['current_node_id'] = $next_node_id;

    $display_text = $target_node['display_text'];
    $spoken_text = $target_node['spoken_text'];
    $language_iso = $session['selected_language'] ?: $target_node['language_iso'];
    $target_action = $target_node['target_action'];

    $next_options = [];

    if ($next_node_id === 1) {
        // Child option nodes for node 1
        foreach ($mock_nodes as $n) {
            if ($n['parent_id'] === 1) {
                $lang_labels = [
                    'en' => 'English',
                    'fr' => 'Français (French)',
                    'ar' => 'العربية (Arabic)',
                    'ur' => 'اردو / हिंदी (Hindi/Urdu)'
                ];
                $label = isset($lang_labels[$n['language_iso']]) ? $lang_labels[$n['language_iso']] : strtoupper($n['language_iso']);
                $next_options[] = [
                    'node_id' => (int)$n['id'],
                    'label' => $label
                ];
            }
        }
    } elseif ($target_action === 'fetch_categories') {
        foreach ($mock_categories as $cat) {
            $next_options[] = [
                'node_id' => 6,
                'label' => $cat['name'],
                'payload_value' => $cat['uuid']
            ];
        }
    } else {
        foreach ($mock_nodes as $n) {
            if ($n['parent_id'] === $next_node_id) {
                $next_options[] = [
                    'node_id' => (int)$n['id'],
                    'label' => $n['display_text']
                ];
            }
        }
    }

    // Log bot response
    $session['chat_logs'][] = [
        'sender' => 'bot',
        'message_content' => $display_text,
        'created_at' => date('Y-m-d H:i:s')
    ];

    send_json_response([
        'status' => 'success',
        'session_token' => $session_token,
        'display_text' => $display_text,
        'spoken_text' => $spoken_text,
        'language_iso' => $language_iso,
        'next_options' => $next_options
    ]);
}

// ---- REAL DATABASE MODE ENGINE ----

$session = null;
if (!empty($session_token)) {
    $stmt = $mysqli->prepare("SELECT * FROM bot_sessions WHERE session_token = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $session_token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $session = $res->fetch_assoc();
        }
        $stmt->close();
    }
}

// If session is missing or invalid, generate a new secure session
if (!$session) {
    // Generate a secure 64-character hexadecimal session token string
    $session_token = bin2hex(random_bytes(32));

    // Check if user is logged in to associate user_id
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $user_id = null;
    if (isset($_SESSION['user']['id'])) {
        $user_id = (int)$_SESSION['user']['id'];
    }

    $stmt = $mysqli->prepare("INSERT INTO bot_sessions (session_token, user_id, current_node_id) VALUES (?, ?, ?)");
    if ($stmt) {
        $initial_node_id = 1; // Default greeting node
        $stmt->bind_param('sii', $session_token, $user_id, $initial_node_id);
        if ($stmt->execute()) {
            $session_id = $mysqli->insert_id;
            $session = [
                'id' => $session_id,
                'session_token' => $session_token,
                'user_id' => $user_id,
                'selected_language' => null,
                'current_node_id' => $initial_node_id
            ];
        } else {
            send_json_response([
                'status' => 'error',
                'message' => 'Failed to initialize session in database.'
            ], 500);
        }
        $stmt->close();
    } else {
        $session = [
            'id' => 1,
            'session_token' => $session_token,
            'user_id' => null,
            'selected_language' => null,
            'current_node_id' => 1
        ];
    }
}

$session_id = (int)$session['id'];

// Log the incoming user message if provided
if (!empty($message_content)) {
    $sender_user = 'user';
    $stmt = $mysqli->prepare("INSERT INTO bot_chat_logs (session_id, sender, message_content) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('iss', $session_id, $sender_user, $message_content);
        $stmt->execute();
        $stmt->close();
    }
}

// Determine the next node to execute
$current_node_id = (int)$session['current_node_id'];
$next_node_id = $node_id ?: $current_node_id;

// Load the target/requested node
$target_node = null;
$stmt = $mysqli->prepare("SELECT * FROM bot_nodes WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $next_node_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $target_node = $res->fetch_assoc();
    }
    $stmt->close();
}

// If requested node not found, fallback to root node
if (!$target_node) {
    $next_node_id = 1;
    $stmt = $mysqli->prepare("SELECT * FROM bot_nodes WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $next_node_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $target_node = $res->fetch_assoc();
        }
        $stmt->close();
    }
}

// Handle language transition (on root welcome node child transition)
$selected_language = $session['selected_language'];
if ($target_node && (int)$target_node['parent_id'] === 1 && $next_node_id !== 1) {
    // Transitioning from root node to a language onboarding subtree!
    $selected_language = $target_node['language_iso'];
    $stmt = $mysqli->prepare("UPDATE bot_sessions SET selected_language = ?, current_node_id = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('sii', $selected_language, $next_node_id, $session_id);
        $stmt->execute();
        $stmt->close();
    }
} else {
    // Update the session's current node tracking
    $stmt = $mysqli->prepare("UPDATE bot_sessions SET current_node_id = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $next_node_id, $session_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Refetch or prepare response based on the targeted node
$display_text = $target_node ? $target_node['display_text'] : 'Welcome!';
$spoken_text = $target_node ? $target_node['spoken_text'] : 'Welcome!';
$language_iso = $selected_language ?: ($target_node ? $target_node['language_iso'] : 'en');
$target_action = $target_node ? $target_node['target_action'] : null;

$next_options = [];

// Generate Options Block
if ($next_node_id === 1) {
    // Language agnostic root welcome node: provide the 4 target language selection options
    $stmt = $mysqli->prepare("SELECT * FROM bot_nodes WHERE parent_id = 1 ORDER BY id ASC");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $lang_labels = [
                    'en' => 'English',
                    'fr' => 'Français (French)',
                    'ar' => 'العربية (Arabic)',
                    'ur' => 'اردو / हिंदी (Hindi/Urdu)'
                ];
                $label = isset($lang_labels[$row['language_iso']]) ? $lang_labels[$row['language_iso']] : strtoupper($row['language_iso']);
                $next_options[] = [
                    'node_id' => (int)$row['id'],
                    'label' => $label
                ];
            }
        }
        $stmt->close();
    }
} elseif ($target_action === 'fetch_categories') {
    // Dynamic service category fetching triggered
    $stmt = $mysqli->query("SELECT * FROM service_categories ORDER BY name ASC");
    if ($stmt) {
        while ($cat = $stmt->fetch_assoc()) {
            $next_options[] = [
                'node_id' => 6,
                'label' => $cat['name'],
                'payload_value' => $cat['uuid']
            ];
        }
        $stmt->free();
    }
} else {
    // Standard relational parent-child matching of children nodes
    $stmt = $mysqli->prepare("SELECT * FROM bot_nodes WHERE parent_id = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param('i', $next_node_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $next_options[] = [
                    'node_id' => (int)$row['id'],
                    'label' => $row['display_text']
                ];
            }
        }
        $stmt->close();
    }
}

// Log the outgoing bot response in bot_chat_logs
$sender_bot = 'bot';
$stmt = $mysqli->prepare("INSERT INTO bot_chat_logs (session_id, sender, message_content) VALUES (?, ?, ?)");
if ($stmt) {
    $stmt->bind_param('iss', $session_id, $sender_bot, $display_text);
    $stmt->execute();
    $stmt->close();
}

// Send success JSON response
send_json_response([
    'status' => 'success',
    'session_token' => $session_token,
    'display_text' => $display_text,
    'spoken_text' => $spoken_text,
    'language_iso' => $language_iso,
    'next_options' => $next_options
]);
