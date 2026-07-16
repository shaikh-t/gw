<?php
// api/bot-controller.php
header('Content-Type: application/json; charset=utf-8');

// Error reporting setup for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/uuid_helper.php';

// Appendix B: AI System Prompt Blueprint Master Constant
define('AI_SYSTEM_PROMPT_BLUEPRINT', "ROLE AND CONTEXT:
You are the highly sophisticated, multilingual AI Global Concierge for our premium consultancy and service marketplace. Your voice is welcoming, authoritative, concise, and professional. You operate exclusively as an intelligent router and helpful assistant, guiding customers through options before they book specialized service packages (Immigration, Visit Visas, and Business Setup).

CORE OPERATION RULES:
1. TARGET LANGUAGES: You must communicate exclusively in the user's selected language: English (en), French (fr), Arabic (ar), or Urdu/Hindi (ur). Match the tone and dialect perfectly.

2. CONTEXT AWARENESS: You will receive an operational metadata object named 'page_context'. You must dynamically tailor your opening sentence based on what the user is looking at.
   - If 'page_context' indicates the user is on a vendor profile, bypass generic platform introductions. Ask exactly: \"Welcome back. I see you are exploring this vendor's profile. What would you like help with?\"
   - Provide highly contextual choice selections. Do NOT prompt them for transactions or raw checkouts immediately. Instead, ask: \"Would you like me to show similar vendors?\" or \"Would you like to know something specific about this vendor's history?\", alongside a distinct \"Start Fresh\" path.

3. DATA RESOLUTION AND INTEGRITY: You have access to real-time functions to fetch service offerings. Never fabricate, invent, or guess a service price, duration, or country policy. If a customer asks about packages, costs, or vendor details, trigger the appropriate function call (e.g., fetch_categories) and read the database array response. If the data is missing from the database response, say: \"I am unable to find that specific package configuration right now. Let me loop you back to our primary menu options.\"

4. FUNCTION CALLING & WORKFLOW FULFILLMENT:
   - When a user confirms they wish to initiate a formal process (such as a profile setup or booking), do not attempt to write data yourself. Output the exact target JSON function block mapping to our secure PHP backend endpoints.
   - If the user is an unauthenticated guest, guide them step-by-step to capture their Name, Email Address, and International Phone Number sequentially, then trigger the profile creation token.

5. PERFORMANCE AND LOAD RESTRAINT:
   - Keep your text sentences short, scannable, and under 25 words per speech turn. This ensures our Text-to-Speech (TTS) audio streaming loops run with low latency and do not cause browser performance lag.
   - Never output raw HTML layout blocks or verbose markdown code blocks. Structure your choices inside clean arrays that our frontend interface can easily parse into slick button elements.");

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

// Ensure database connection is active
if (!isset($mysqli)) {
    send_json_response([
        'status' => 'error',
        'message' => 'Database connection variable is not defined.'
    ], 500);
}

// Global Super Admin AI Bot Kill-Switch Validation
$ai_global_status = 'enabled';
$stmt_kill = $mysqli->prepare("SELECT `value` FROM `site_settings` WHERE `key` = 'ai_bot_global_status' LIMIT 1");
if ($stmt_kill) {
    $stmt_kill->execute();
    $res_kill = $stmt_kill->get_result();
    if ($row_kill = $res_kill->fetch_assoc()) {
        $ai_global_status = $row_kill['value'];
    }
    $stmt_kill->close();
}

if ($ai_global_status === 'disabled') {
    send_json_response([
        'status' => 'error',
        'message' => 'The AI Assistant is currently disabled globally by the system administrator.'
    ], 403);
}

$session_token = isset($input['session_token']) ? trim($input['session_token']) : '';
$node_id = isset($input['node_id']) ? (int)$input['node_id'] : null;
$message_content = isset($input['message']) ? trim($input['message']) : '';
$page_context_input = isset($input['page_context']) && is_array($input['page_context']) ? $input['page_context'] : null;
$badge_click = isset($input['badge_click']) && (bool)$input['badge_click'];
$entry_point_input = isset($input['entry_point']) ? trim($input['entry_point']) : '';

// Start session to access page context tracking or login user state
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Resolve dynamic cross-page context (merge input context and session context)
$current_page_context = $_SESSION['bot_page_context'] ?? [];
if ($page_context_input) {
    $current_page_context = array_merge($current_page_context, $page_context_input);
}

// Determine if we are running in Mock Fallback mode
$is_mock_mode = (get_class($mysqli) === 'MockMySQLi');

// --- Predefined dialog values ---
$lang_labels = [
    'en' => 'English',
    'fr' => 'Français (French)',
    'ar' => 'العربية (Arabic)',
    'ur' => 'اردو / हिंदी (Hindi/Urdu)'
];

// Helper to check if a node triggers independent browse collapse
function is_independent_browse_node(?string $target_action, int $node_id): bool {
    return ($target_action === 'independent_browse' || in_array($node_id, [20, 21, 22, 23]));
}

// 3. --- MOCK FALLBACK MODE ENGINE ---
if ($is_mock_mode) {
    // Mock Nodes Tree
    $mock_nodes = [
        1 => [
            'id' => 1, 'parent_id' => null, 'node_type' => 'greeting', 'language_iso' => 'en',
            'display_text' => 'Welcome to GlobalWays! Please select your preferred language to begin.',
            'spoken_text' => 'Welcome to GlobalWays! Please select your preferred language to begin.', 'target_action' => null
        ],
        10 => [
            'id' => 10, 'parent_id' => 1, 'node_type' => 'voice_selection', 'language_iso' => 'en',
            'display_text' => 'Thank you. Would you prefer to explore our platform with the assistance of our AI Voice Companion, or would you like to browse the site independently at your own pace?',
            'spoken_text' => 'Thank you. Would you prefer to explore our platform with the assistance of our AI Voice Companion, or would you like to browse the site independently at your own pace?', 'target_action' => null
        ],
        11 => [
            'id' => 11, 'parent_id' => 1, 'node_type' => 'voice_selection', 'language_iso' => 'fr',
            'display_text' => 'Merci. Préféreriez-vous explorer notre plateforme avec l\'aide de notre compagnon vocal IA, ou préférez-vous naviguer sur le site de manière indépendante à votre propre rythme ?',
            'spoken_text' => 'Merci. Préféreriez-vous explorer notre plateforme avec l\'aide de notre compagnon vocal IA, ou préférez-vous naviguer sur le site de manière indépendante à votre propre rythme ?', 'target_action' => null
        ],
        12 => [
            'id' => 12, 'parent_id' => 1, 'node_type' => 'voice_selection', 'language_iso' => 'ar',
            'display_text' => 'شكراً لك. هل تفضل استكشاف منصتنا بمساعدة الرفيق الصوتي المدعوم بالذكاء الاصطناعي، أم ترغب في تصفح الموقع بشكل مستقل وبسرعتك الخاصة؟',
            'spoken_text' => 'شكراً لك. هل تفضل استكشاف منصتنا بمساعدة الرفيق الصوتي المدعوم بالذكاء الاصطناعي، أم ترغب في تصفح الموقع بشكل مستقل وبسرعتك الخاصة؟', 'target_action' => null
        ],
        13 => [
            'id' => 13, 'parent_id' => 1, 'node_type' => 'voice_selection', 'language_iso' => 'ur',
            'display_text' => 'شکریہ۔ کیا آپ ہمارے AI وائس ساتھی کی مدد سے ہمارے پلیٹ فارم کو دریافت کرنا پسند کریں گے، یا آپ اپنی رفتار سے خود ویب سائٹ دیکھنا پسند کریں گے؟',
            'spoken_text' => 'شکریہ۔ کیا آپ ہمارے AI وائس ساتھی کی مدد سے ہمارے پلیٹ فارم کو دریافت کرنا پسند کریں گے، یا آپ اپنی رفتار سے خود ویب سائٹ دیکھنا پسند کریں گے؟', 'target_action' => null
        ],
        2 => [
            'id' => 2, 'parent_id' => 10, 'node_type' => 'category_select', 'language_iso' => 'en',
            'display_text' => 'Hello! Welcome to GlobalWays. We simplify UAE documentation. Please select a service category to get started.',
            'spoken_text' => 'Hello! Welcome to GlobalWays. We simplify UAE documentation. Please select a service category to get started.', 'target_action' => 'fetch_categories'
        ],
        3 => [
            'id' => 3, 'parent_id' => 11, 'node_type' => 'category_select', 'language_iso' => 'fr',
            'display_text' => 'Bonjour! Bienvenue sur GlobalWays. Nous simplifions les démarches administratives aux Émirats Arabes Unis. Veuillez sélectionner une catégorie de service pour commencer.',
            'spoken_text' => 'Bonjour! Bienvenue sur GlobalWays. Nous simplifions les démarches administratives aux Émirats Arabes Unis. Veuillez sélectionner une catégorie de service pour commencer.', 'target_action' => 'fetch_categories'
        ],
        4 => [
            'id' => 4, 'parent_id' => 12, 'node_type' => 'category_select', 'language_iso' => 'ar',
            'display_text' => 'مرحباً بك في غلوبال وايز! نحن نبسط الإجراءات والمعاملات الرسمية في دولة الإمارات العربية المتحدة. يرجى اختيار قسم الخدمة للبدء.',
            'spoken_text' => 'مرحباً بك في غلوبال وايز! نحن نبسط الإجراءات والمعاملات الرسمية في دولة الإمارات العربية المتحدة. يرجى اختيار قسم الخدمة للبدء.', 'target_action' => 'fetch_categories'
        ],
        5 => [
            'id' => 5, 'parent_id' => 13, 'node_type' => 'category_select', 'language_iso' => 'ur',
            'display_text' => 'گلوبل ویز میں خوش آمدید! ہم متحدہ عرب امارات کے کاغذی کام کو آسان بناتے ہیں۔ شروع کرنے کے لیے برائے مہربانی ایک سروس کیٹیگری منتخب کریں۔',
            'spoken_text' => 'گلوبل ویز میں خوش آمدید! ہم متحدہ عرب امارات کے کاغذی کام کو آسان بناتے ہیں۔ شروع کرنے کے لیے برائے مہربانی ایک سروس کیٹیگری منتخب کریں۔', 'target_action' => 'fetch_categories'
        ],
        20 => [
            'id' => 20, 'parent_id' => 10, 'node_type' => 'independent_browse', 'language_iso' => 'en',
            'display_text' => 'Understood. I will remain silently available in the bottom corner of your screen. As you browse our services, I will automatically keep track of your progress so that whenever you require guidance, we can seamlessly pick up exactly where you left off.',
            'spoken_text' => 'Understood. I will remain silently available in the bottom corner of your screen. As you browse our services, I will automatically keep track of your progress so that whenever you require guidance, we can seamlessly pick up exactly where you left off.', 'target_action' => 'independent_browse'
        ],
        21 => [
            'id' => 21, 'parent_id' => 11, 'node_type' => 'independent_browse', 'language_iso' => 'fr',
            'display_text' => 'Compris. Je resterai discrètement disponible dans le coin inférieur de votre écran. Pendant votre navigation, je suivrai automatiquement votre progression afin que, dès que vous aurez besoin de conseils, nous puissions reprendre exactement là où vous vous étiez arrêté.',
            'spoken_text' => 'Compris. Je resterai discrètement disponible dans le coin inférieur de votre écran. Pendant votre navigation, je suivrai automatiquement votre progression afin que, dès que vous aurez besoin de conseils, nous puissions reprendre exactement là où vous vous étiez arrêté.', 'target_action' => 'independent_browse'
        ],
        22 => [
            'id' => 22, 'parent_id' => 12, 'node_type' => 'independent_browse', 'language_iso' => 'ar',
            'display_text' => 'مفهوم. سأظل متاحاً بصمت في الزاوية السفلية من شاشتك. بينما تتصفح خدماتنا، سأتابع تقدمك تلقائياً حتى نتمكن من المتابعة من حيث توقفت تماماً كلما احتجت إلى توجيه.',
            'spoken_text' => 'مفهوم. سأظل متاحاً بصمت في الزاوية السفلية من شاشتك. بينما تتصفح خدماتنا، سأتابع تقدمك تلقائياً حتى نتمكن من المتابعة من حيث توقفت تماماً كلما احتجت إلى توجيه.', 'target_action' => 'independent_browse'
        ],
        23 => [
            'id' => 23, 'parent_id' => 13, 'node_type' => 'independent_browse', 'language_iso' => 'ur',
            'display_text' => 'سمجھ گیا۔ میں آپ کی اسکرین کے نچلے کونے میں خاموشی سے دستیاب رہوں گا۔ جیسے ہی آپ ہماری خدمات کو براؤز کریں گے، میں خود بخود آپ کی پیشرفت پر نظر رکھوں گا تاکہ جب بھی آپ کو رہنمائی کی ضرورت ہو، ہم وہیں سے بغیر کسی رکاوٹ کے شروع کر سکیں جہاں سے آپ نے چھوڑا تھا۔',
            'spoken_text' => 'سمجھ گیا۔ میں آپ کی اسکرین کے نچلے کونے میں خاموشی سے دستیاب رہوں گا۔ جیسے ہی آپ ہماری خدمات کو براؤز کریں گے، میں خود بخود آپ کی پیشرفت پر نظر رکھوں گا تاکہ جب بھی آپ کو رہنمائی کی ضرورت ہو، ہم وہیں سے بغیر کسی رکاوٹ کے شروع کر سکیں جہاں سے آپ نے چھوڑا تھا۔', 'target_action' => 'independent_browse'
        ],
        6 => [
            'id' => 6, 'parent_id' => null, 'node_type' => 'category_handler', 'language_iso' => 'en',
            'display_text' => 'Got it! You\'ve selected a service category. We are preparing the registration and booking context for you.',
            'spoken_text' => 'Got it! You\'ve selected a service category. We are preparing the registration and booking context for you.', 'target_action' => 'handle_category'
        ]
    ];

    $mock_categories = [
        ['id' => 1, 'uuid' => 'cat-imm-123', 'name' => 'Immigration Services'],
        ['id' => 2, 'uuid' => 'cat-vis-456', 'name' => 'Visit Visa'],
        ['id' => 3, 'uuid' => 'cat-bus-789', 'name' => 'Business Setup']
    ];

    // Initialize or resolve session
    if (empty($session_token) || !isset($_SESSION['mock_sessions'][$session_token])) {
        $session_token = bin2hex(random_bytes(32));
        $_SESSION['mock_sessions'][$session_token] = [
            'session_token' => $session_token,
            'selected_language' => null,
            'current_node_id' => 1,
            'entry_point' => $entry_point_input,
            'chat_logs' => []
        ];
    }
    $session = &$_SESSION['mock_sessions'][$session_token];

    // Reset session on "Start Fresh"
    if ($node_id === 1) {
        $session['current_node_id'] = 1;
        $session['selected_language'] = null;
        $node_id = null;
    }

    // Dynamic Badge Click Context Restore
    if ($badge_click) {
        $page_name = $current_page_context['page_name'] ?? '';
        if (strpos($page_name, 'vendor-profile.php') !== false) {
            $v_name = $current_page_context['vendor_name'] ?? 'this vendor';
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Welcome back. I see you are exploring $v_name's profile. What would you like help with?",
                'spoken_text' => "Welcome back. I see you are exploring $v_name's profile. What would you like help with?",
                'language_iso' => 'en',
                'next_options' => [
                    ['node_id' => 101, 'label' => 'Would you like me to show similar vendors?'],
                    ['node_id' => 102, 'label' => 'Would you like to know something about this vendor?'],
                    ['node_id' => 1, 'label' => 'Start Fresh']
                ]
            ]);
        } elseif (strpos($page_name, 'service-detail.php') !== false) {
            $s_title = $current_page_context['service_title'] ?? 'our service';
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Welcome back. I see you are looking at $s_title details. What would you like help with?",
                'spoken_text' => "Welcome back. I see you are looking at $s_title details. What would you like help with?",
                'language_iso' => 'en',
                'next_options' => [
                    ['node_id' => 201, 'label' => 'Would you like to know about pricing?'],
                    ['node_id' => 202, 'label' => 'Would you like to check processing duration?'],
                    ['node_id' => 1, 'label' => 'Start Fresh']
                ]
            ]);
        }
    }

    // Check if Independent Browse node is triggered -> do not write heavy logs
    $is_independent = is_independent_browse_node(null, $node_id ?: $session['current_node_id']);
    if (!$is_independent && !empty($message_content)) {
        $session['chat_logs'][] = [
            'sender' => 'user',
            'message_content' => $message_content,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    $current_node_id = (int)$session['current_node_id'];
    $next_node_id = $node_id ?: $current_node_id;

    if (!isset($mock_nodes[$next_node_id])) {
        $next_node_id = 1;
    }
    $target_node = $mock_nodes[$next_node_id];

    // Update language transitions
    if ($target_node['parent_id'] === 1 && $next_node_id !== 1) {
        $session['selected_language'] = $target_node['language_iso'];
    }
    $session['current_node_id'] = $next_node_id;

    $display_text = $target_node['display_text'];
    $spoken_text = $target_node['spoken_text'];
    $language_iso = $session['selected_language'] ?: $target_node['language_iso'];
    $target_action = $target_node['target_action'];

    $next_options = [];

    if ($next_node_id === 1) {
        foreach ($mock_nodes as $n) {
            if ($n['parent_id'] === 1) {
                $label = isset($lang_labels[$n['language_iso']]) ? $lang_labels[$n['language_iso']] : strtoupper($n['language_iso']);
                $next_options[] = [
                    'node_id' => (int)$n['id'],
                    'label' => $label
                ];
            }
        }
    } elseif ($target_node['node_type'] === 'voice_selection') {
        // Voice companion selection options
        $next_options[] = [
            'node_id' => (int)$target_node['id'] - 8, // maps English (10) -> 2, French (11) -> 3, Arabic (12) -> 4, Urdu (13) -> 5
            'label' => 'AI Voice Companion'
        ];
        $next_options[] = [
            'node_id' => (int)$target_node['id'] + 10, // maps English (10) -> 20, French (11) -> 21, Arabic (12) -> 22, Urdu (13) -> 23
            'label' => 'Browse Independently'
        ];
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

    if (!$is_independent) {
        $session['chat_logs'][] = [
            'sender' => 'bot',
            'message_content' => $display_text,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    send_json_response([
        'status' => 'success',
        'session_token' => $session_token,
        'display_text' => $display_text,
        'spoken_text' => $spoken_text,
        'language_iso' => $language_iso,
        'next_options' => $next_options,
        'collapse_widget' => $is_independent
    ]);
}

// 4. --- REAL DATABASE MODE ENGINE ---
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

if (!$session) {
    $session_token = bin2hex(random_bytes(32));
    $user_id = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

    $stmt = $mysqli->prepare("INSERT INTO bot_sessions (session_token, user_id, current_node_id, entry_point) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $initial_node_id = 1;
        $stmt->bind_param('siis', $session_token, $user_id, $initial_node_id, $entry_point_input);
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
            send_json_response(['status' => 'error', 'message' => 'Failed to initialize session.'], 500);
        }
        $stmt->close();
    } else {
        $session = [
            'id' => 1, 'session_token' => $session_token, 'user_id' => null, 'selected_language' => null, 'current_node_id' => 1
        ];
    }
}

$session_id = (int)$session['id'];

// Reset on Fresh Start
if ($node_id === 1) {
    $stmt = $mysqli->prepare("UPDATE bot_sessions SET current_node_id = 1, selected_language = NULL WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $session_id);
        $stmt->execute();
        $stmt->close();
    }
    $session['current_node_id'] = 1;
    $session['selected_language'] = null;
    $node_id = null;
}

// Badge Click Dynamic Restorative Interaction Logic
if ($badge_click) {
    $page_name = $current_page_context['page_name'] ?? '';
    if (strpos($page_name, 'vendor-profile.php') !== false) {
        $v_name = $current_page_context['vendor_name'] ?? 'this vendor';
        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => "Welcome back. I see you are exploring $v_name's profile. What would you like help with?",
            'spoken_text' => "Welcome back. I see you are exploring $v_name's profile. What would you like help with?",
            'language_iso' => 'en',
            'next_options' => [
                ['node_id' => 101, 'label' => 'Would you like me to show similar vendors?'],
                ['node_id' => 102, 'label' => 'Would you like to know something about this vendor?'],
                ['node_id' => 1, 'label' => 'Start Fresh']
            ]
        ]);
    } elseif (strpos($page_name, 'service-detail.php') !== false) {
        $s_title = $current_page_context['service_title'] ?? 'our service';
        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => "Welcome back. I see you are looking at $s_title details. What would you like help with?",
            'spoken_text' => "Welcome back. I see you are looking at $s_title details. What would you like help with?",
            'language_iso' => 'en',
            'next_options' => [
                ['node_id' => 201, 'label' => 'Would you like to know about pricing?'],
                ['node_id' => 202, 'label' => 'Would you like to check processing duration?'],
                ['node_id' => 1, 'label' => 'Start Fresh']
            ]
        ]);
    }
}

$current_node_id = (int)$session['current_node_id'];
$next_node_id = $node_id ?: $current_node_id;

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

// Log incoming user interaction ONLY if not independent browse node
$is_independent = is_independent_browse_node($target_node ? $target_node['target_action'] : null, $next_node_id);
if (!$is_independent && !empty($message_content)) {
    $sender_user = 'user';
    $stmt_log = $mysqli->prepare("INSERT INTO bot_chat_logs (session_id, sender, message_content) VALUES (?, ?, ?)");
    if ($stmt_log) {
        $stmt_log->bind_param('iss', $session_id, $sender_user, $message_content);
        $stmt_log->execute();
        $stmt_log->close();
    }
}

$selected_language = $session['selected_language'];
if ($target_node && (int)$target_node['parent_id'] === 1 && $next_node_id !== 1) {
    $selected_language = $target_node['language_iso'];
    $stmt_up = $mysqli->prepare("UPDATE bot_sessions SET selected_language = ?, current_node_id = ? WHERE id = ?");
    if ($stmt_up) {
        $stmt_up->bind_param('sii', $selected_language, $next_node_id, $session_id);
        $stmt_up->execute();
        $stmt_up->close();
    }
} else {
    $stmt_up = $mysqli->prepare("UPDATE bot_sessions SET current_node_id = ? WHERE id = ?");
    if ($stmt_up) {
        $stmt_up->bind_param('ii', $next_node_id, $session_id);
        $stmt_up->execute();
        $stmt_up->close();
    }
}

$display_text = $target_node ? $target_node['display_text'] : 'Welcome!';
$spoken_text = $target_node ? $target_node['spoken_text'] : 'Welcome!';
$language_iso = $selected_language ?: ($target_node ? $target_node['language_iso'] : 'en');
$target_action = $target_node ? $target_node['target_action'] : null;

$next_options = [];

if ($next_node_id === 1) {
    $stmt_opt = $mysqli->prepare("SELECT * FROM bot_nodes WHERE parent_id = 1 ORDER BY id ASC");
    if ($stmt_opt) {
        $stmt_opt->execute();
        $res = $stmt_opt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $label = isset($lang_labels[$row['language_iso']]) ? $lang_labels[$row['language_iso']] : strtoupper($row['language_iso']);
                $next_options[] = [
                    'node_id' => (int)$row['id'],
                    'label' => $label
                ];
            }
        }
        $stmt_opt->close();
    }
} elseif ($target_node && $target_node['node_type'] === 'voice_selection') {
    $next_options[] = [
        'node_id' => (int)$target_node['id'] - 8,
        'label' => 'AI Voice Companion'
    ];
    $next_options[] = [
        'node_id' => (int)$target_node['id'] + 10,
        'label' => 'Browse Independently'
    ];
} elseif ($target_action === 'fetch_categories') {
    $stmt_cat = $mysqli->query("SELECT * FROM service_categories ORDER BY name ASC");
    if ($stmt_cat) {
        while ($cat = $stmt_cat->fetch_assoc()) {
            $next_options[] = [
                'node_id' => 6,
                'label' => $cat['name'],
                'payload_value' => $cat['uuid']
            ];
        }
        $stmt_cat->free();
    }
} else {
    $stmt_opt = $mysqli->prepare("SELECT * FROM bot_nodes WHERE parent_id = ? ORDER BY id ASC");
    if ($stmt_opt) {
        $stmt_opt->bind_param('i', $next_node_id);
        $stmt_opt->execute();
        $res = $stmt_opt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $next_options[] = [
                    'node_id' => (int)$row['id'],
                    'label' => $row['display_text']
                ];
            }
        }
        $stmt_opt->close();
    }
}

if (!$is_independent) {
    $sender_bot = 'bot';
    $stmt_log = $mysqli->prepare("INSERT INTO bot_chat_logs (session_id, sender, message_content) VALUES (?, ?, ?)");
    if ($stmt_log) {
        $stmt_log->bind_param('iss', $session_id, $sender_bot, $display_text);
        $stmt_log->execute();
        $stmt_log->close();
    }
}

send_json_response([
    'status' => 'success',
    'session_token' => $session_token,
    'display_text' => $display_text,
    'spoken_text' => $spoken_text,
    'language_iso' => $language_iso,
    'next_options' => $next_options,
    'collapse_widget' => $is_independent
]);
