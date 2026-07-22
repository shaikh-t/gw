<?php
// api/bot-controller.php
header('Content-Type: application/json; charset=utf-8');

// Error reporting setup for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db_mysqli.php';
require_once __DIR__ . '/../lib/uuid_helper.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/users_helpers.php';

// Ensure database connection is active
if (!isset($mysqli) || $mysqli->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection is unavailable.'
    ]);
    exit;
}

// Global Super Admin AI Bot Kill-Switch Validation at absolute top
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
    http_response_code(403);
    header('HTTP/1.1 403 Forbidden');
    exit;
}

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

/**
 * Resolves any active 'bot_internal_chat' ad campaigns matching current context.
 *
 * @return array|null Ad payload or null.
 */
function get_matching_bot_internal_chat_ad(): ?array {
    global $mysqli;
    if (!isset($mysqli) || $mysqli->connect_errno || (get_class($mysqli) === 'MockMySQLi')) {
        return null;
    }

    // Resolve context parameters
    $active_page = 'bot-landing.php';
    $bot_page_context = $_SESSION['bot_page_context'] ?? [];
    if (isset($bot_page_context['page_name'])) {
        $active_page = basename($bot_page_context['page_name']);
    }

    $category_id = isset($bot_page_context['category_id']) ? (int)$bot_page_context['category_id'] : null;
    $language_iso = isset($bot_page_context['language_iso']) ? trim($bot_page_context['language_iso']) : 'en';
    if (empty($language_iso)) {
        $language_iso = 'en';
    }

    $zone_name = 'bot_internal_chat';

    $query = "
        SELECT * FROM bot_ads
        WHERE placement_zone = ?
          AND ad_source_type = 'direct_sponsor'
          AND language_iso = ?
          AND is_active = 1
          AND (target_page_context = ? OR target_page_context = 'global_fallback')
    ";

    if ($category_id !== null) {
        $query .= " AND (target_category_id = ? OR target_category_id IS NULL) ";
    } else {
        $query .= " AND target_category_id IS NULL ";
    }

    $query .= " ORDER BY CASE WHEN target_page_context = ? THEN 1 ELSE 2 END ASC, id DESC";

    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        if ($category_id !== null) {
            $stmt->bind_param('sssss', $zone_name, $language_iso, $active_page, $category_id, $active_page);
        } else {
            $stmt->bind_param('ssss', $zone_name, $language_iso, $active_page, $active_page);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        while ($ad = $res->fetch_assoc()) {
            $is_eligible = false;

            if ($ad['ad_billing_model'] === 'flat_rate_temporal') {
                $now = date('Y-m-d H:i:s');
                $start_valid = empty($ad['start_date']) || ($ad['start_date'] <= $now);
                $end_valid = empty($ad['end_date']) || ($ad['end_date'] >= $now);
                if ($start_valid && $end_valid) {
                    $is_eligible = true;
                }
            } else {
                $budget_ok = $ad['current_spend'] < $ad['max_budget'];
                $impressions_ok = ($ad['max_impressions'] == 0) || ($ad['current_impressions'] < $ad['max_impressions']);
                if ($budget_ok && $impressions_ok) {
                    $is_eligible = true;
                }
            }

            if ($is_eligible) {
                // Increment impression count atomically
                $stmt_imp = $mysqli->prepare("UPDATE bot_ads SET current_impressions = current_impressions + 1 WHERE id = ?");
                if ($stmt_imp) {
                    $stmt_imp->bind_param('i', $ad['id']);
                    $stmt_imp->execute();
                    $stmt_imp->close();
                }

                $stmt->close();
                return [
                    'banner_text' => $ad['banner_text'],
                    'destination_url' => "api/bot-ad-tracker.php?ad_id=" . $ad['id']
                ];
            }
        }
        $stmt->close();
    }

    return null;
}

// Helper to sanitize out terminal control characters to prevent terminal injection
function sanitize_terminal_characters($str) {
    if (!is_string($str)) return $str;
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x1B]/', '', $str);
}

// Helper function to safely send JSON responses
function send_json_response(array $data, int $status_code = 200) {
    global $input;
    if (isset($data['status']) && $data['status'] === 'success') {
        $ad = get_matching_bot_internal_chat_ad();
        if ($ad) {
            $data['ad_payload'] = [
                'banner_text' => $ad['banner_text'],
                'destination_url' => $ad['destination_url']
            ];
        } else {
            $data['ad_payload'] = null;
        }
    }

    // Check for Server-Sent Events (SSE) streaming request
    $is_sse = (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] === 'text/event-stream') ||
              (isset($input['stream']) && $input['stream'] === true) ||
              (isset($_GET['stream']) && $_GET['stream'] === 'true');

    if ($is_sse && isset($data['display_text'])) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable proxy buffering for nginx

        $text = $data['display_text'];
        $len = mb_strlen($text, 'UTF-8');

        // Stream metadata first
        $meta = $data;
        unset($meta['display_text']);
        echo "data: " . json_encode(['meta' => $meta], JSON_UNESCAPED_UNICODE) . "\n\n";
        @ob_flush();
        flush();

        // Stream text characters
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            echo "data: " . json_encode(['char' => $char], JSON_UNESCAPED_UNICODE) . "\n\n";
            @ob_flush();
            flush();
            usleep(10000); // 10ms smooth typing speed
        }
        echo "data: [DONE]\n\n";
        @ob_flush();
        flush();
        exit;
    }

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

// 2. Decode JSON input payload with terminal injection mitigation
$input_raw = file_get_contents('php://input');
$input_raw = sanitize_terminal_characters($input_raw);
$input = json_decode($input_raw, true);

if (!is_array($input)) {
    send_json_response([
        'status' => 'error',
        'message' => 'Invalid or missing JSON payload.'
    ], 400);
}

$session_token = isset($input['session_token']) ? trim($input['session_token']) : '';
$node_id = isset($input['node_id']) ? (int)$input['node_id'] : null;

// Strongly Type and Sanitize spoken and textual inputs to prevent XSS
$message_content = isset($input['message']) ? trim($input['message']) : '';
if ($message_content !== '') {
    $message_content = htmlspecialchars($message_content, ENT_QUOTES, 'UTF-8');
}

$spoken_input_message = isset($input['spoken_input_message']) ? trim($input['spoken_input_message']) : '';
if ($spoken_input_message !== '') {
    $spoken_input_message = htmlspecialchars($spoken_input_message, ENT_QUOTES, 'UTF-8');
    // If message is empty but spoken is set, sync them
    if ($message_content === '') {
        $message_content = $spoken_input_message;
    }
}

// Enforce strict regex validation for payload_value
$payload_value = isset($input['payload_value']) ? trim($input['payload_value']) : '';
if ($payload_value !== '') {
    $is_uuid = preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $payload_value);
    $is_alnum = preg_match('/^[a-zA-Z0-9_\-]+$/', $payload_value);
    if (!$is_uuid && !$is_alnum) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid payload format.'
        ]);
        exit;
    }
}

$page_context_input = isset($input['page_context']) && is_array($input['page_context']) ? $input['page_context'] : null;
$badge_click = isset($input['badge_click']) && (bool)$input['badge_click'];
$entry_point_input = isset($input['entry_point']) ? trim($input['entry_point']) : '';
if ($entry_point_input !== '') {
    $entry_point_input = htmlspecialchars($entry_point_input, ENT_QUOTES, 'UTF-8');
}

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

// --- CONVERSATIONAL REGISTRATION STATE MACHINE FOR GUESTS ---
$is_logged_in = isset($_SESSION['user']['id']);
$is_trigger_word = in_array(strtolower($message_content), ['book', 'register', 'signup', 'onboard']);

if (!$is_logged_in && ($is_trigger_word || !empty($_SESSION['registration_state']))) {
    if (empty($_SESSION['registration_state'])) {
        $_SESSION['registration_state'] = ['step' => 'first_name_input'];
        $display_text = "To initiate your booking, let's complete a quick customer registration. What is your First Name?";
        $spoken_text = "To initiate your booking, let us complete a quick customer registration. What is your First Name?";
        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => $display_text,
            'spoken_text' => $spoken_text,
            'language_iso' => 'en',
            'next_options' => []
        ]);
    }

    $state = &$_SESSION['registration_state'];
    $step = $state['step'];

    // Helper to validate Latin character diacritics and reject non-Latin characters (Arabic, Urdu, etc.)
    if (!function_exists('is_valid_latin_input')) {
        function is_valid_latin_input($str) {
            return preg_match('/^[\p{Latin}\s\'\-]+$/u', $str) && !preg_match('/[\p{Arabic}\p{Devanagari}\p{Bengali}\p{Urdu}\p{Han}]/u', $str);
        }
    }

    if ($step === 'first_name_input') {
        if (!is_valid_latin_input($message_content)) {
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Registration requires Latin characters only. Please type your First Name again.",
                'spoken_text' => "Registration requires Latin characters only. Please type your First Name again.",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        }
        $state['first_name'] = $message_content;
        $state['step'] = 'first_name_confirm';
        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => "I recorded your first name as '" . $message_content . "'. Is this correct?",
            'spoken_text' => "I recorded your first name as " . $message_content . ". Is this correct?",
            'language_iso' => 'en',
            'next_options' => [
                ['node_id' => 9001, 'label' => 'Confirm', 'payload_value' => 'confirm_first_name'],
                ['node_id' => 9002, 'label' => 'Correct Spelling', 'payload_value' => 'correct_first_name']
            ]
        ]);
    }

    if ($step === 'first_name_confirm') {
        $confirm = strtolower($payload_value ?: $message_content);
        if ($confirm === 'confirm_first_name' || strpos($confirm, 'confirm') !== false || strpos($confirm, 'yes') !== false) {
            $state['step'] = 'last_name_input';
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Great. Now, what is your Last Name?",
                'spoken_text' => "Great. Now, what is your Last Name?",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        } else {
            $state['step'] = 'first_name_input';
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Let's correct that. Please enter your First Name.",
                'spoken_text' => "Let us correct that. Please enter your First Name.",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        }
    }

    if ($step === 'last_name_input') {
        if (!is_valid_latin_input($message_content)) {
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Registration requires Latin characters only. Please type your Last Name again.",
                'spoken_text' => "Registration requires Latin characters only. Please type your Last Name again.",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        }
        $state['last_name'] = $message_content;
        $state['step'] = 'last_name_confirm';
        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => "I recorded your last name as '" . $message_content . "'. Is this correct?",
            'spoken_text' => "I recorded your last name as " . $message_content . ". Is this correct?",
            'language_iso' => 'en',
            'next_options' => [
                ['node_id' => 9003, 'label' => 'Confirm', 'payload_value' => 'confirm_last_name'],
                ['node_id' => 9004, 'label' => 'Correct Spelling', 'payload_value' => 'correct_last_name']
            ]
        ]);
    }

    if ($step === 'last_name_confirm') {
        $confirm = strtolower($payload_value ?: $message_content);
        if ($confirm === 'confirm_last_name' || strpos($confirm, 'confirm') !== false || strpos($confirm, 'yes') !== false) {
            $state['step'] = 'email_input';
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Excellent. What is your Email Address?",
                'spoken_text' => "Excellent. What is your Email Address?",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        } else {
            $state['step'] = 'last_name_input';
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Let's correct that. Please enter your Last Name.",
                'spoken_text' => "Let us correct that. Please enter your Last Name.",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        }
    }

    if ($step === 'email_input') {
        $clean_email = filter_var($message_content, FILTER_VALIDATE_EMAIL);
        $is_latin_email = preg_match('/^[a-zA-Z0-9\._%+-]+@[a-zA-Z0-9\.-]+\.[a-zA-Z]{2,}$/u', $message_content);
        if (!$clean_email || !$is_latin_email) {
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Please enter a valid, Latin-based Email Address.",
                'spoken_text' => "Please enter a valid, Latin based Email Address.",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        }

        $stmt_email = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmt_email) {
            $stmt_email->bind_param('s', $message_content);
            $stmt_email->execute();
            $res_email = $stmt_email->get_result();
            if ($res_email && $res_email->num_rows > 0) {
                $stmt_email->close();
                send_json_response([
                    'status' => 'success',
                    'session_token' => $session_token,
                    'display_text' => "This email address is already registered. Please enter a different Email Address.",
                    'spoken_text' => "This email address is already registered. Please enter a different Email Address.",
                    'language_iso' => 'en',
                    'next_options' => []
                ]);
            }
            $stmt_email->close();
        }

        $state['email'] = $message_content;
        $state['step'] = 'email_confirm';
        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => "I recorded your email as '" . $message_content . "'. Is this correct?",
            'spoken_text' => "I recorded your email as " . $message_content . ". Is this correct?",
            'language_iso' => 'en',
            'next_options' => [
                ['node_id' => 9005, 'label' => 'Confirm', 'payload_value' => 'confirm_email'],
                ['node_id' => 9006, 'label' => 'Correct Spelling', 'payload_value' => 'correct_email']
            ]
        ]);
    }

    if ($step === 'email_confirm') {
        $confirm = strtolower($payload_value ?: $message_content);
        if ($confirm === 'confirm_email' || strpos($confirm, 'confirm') !== false || strpos($confirm, 'yes') !== false) {
            $state['step'] = 'phone_input';
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Perfect. Finally, what is your Phone Number?",
                'spoken_text' => "Perfect. Finally, what is your Phone Number?",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        } else {
            $state['step'] = 'email_input';
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Let's correct that. Please enter your Email Address.",
                'spoken_text' => "Let us correct that. Please enter your Email Address.",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        }
    }

    if ($step === 'phone_input') {
        if (!preg_match('/^\+?[0-9\s\-]{7,20}$/', $message_content)) {
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Please enter a valid, Latin-based Phone Number.",
                'spoken_text' => "Please enter a valid, Latin based Phone Number.",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        }
        $state['phone'] = $message_content;
        $state['step'] = 'phone_confirm';
        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => "I recorded your phone number as '" . $message_content . "'. Is this correct?",
            'spoken_text' => "I recorded your phone number as " . $message_content . ". Is this correct?",
            'language_iso' => 'en',
            'next_options' => [
                ['node_id' => 9007, 'label' => 'Confirm', 'payload_value' => 'confirm_phone'],
                ['node_id' => 9008, 'label' => 'Correct Spelling', 'payload_value' => 'correct_phone']
            ]
        ]);
    }

    if ($step === 'phone_confirm') {
        $confirm = strtolower($payload_value ?: $message_content);
        if ($confirm === 'confirm_phone' || strpos($confirm, 'confirm') !== false || strpos($confirm, 'yes') !== false) {
            $stmt_r = $mysqli->prepare("SELECT id FROM roles WHERE name = 'viewer' LIMIT 1");
            $stmt_r->execute();
            $res_r = $stmt_r->get_result();
            $viewer_role_id = 3;
            if ($res_r && $row_r = $res_r->fetch_assoc()) {
                $viewer_role_id = (int)$row_r['id'];
            }
            $stmt_r->close();

            $full_name = $state['first_name'] . ' ' . $state['last_name'];
            $rand_password = bin2hex(random_bytes(12)) . '@W1a!';

            $create = user_create($full_name, $state['email'], $rand_password, [$viewer_role_id]);
            if (!$create['ok']) {
                send_json_response([
                    'status' => 'error',
                    'message' => 'Failed to create user account: ' . $create['error']
                ], 500);
            }

            $userId = (int)$create['id'];
            login_user_by_id($userId);
            session_regenerate_id(true);

            $first_name = $state['first_name'];
            unset($_SESSION['registration_state']);

            $display_text = "Congratulations, " . $first_name . "! Your customer registration is complete and you are now securely logged in. Let me show you our customized packages.";
            $spoken_text = "Congratulations, " . $first_name . "! Your customer registration is complete. You are now logged in.";
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => $display_text,
                'spoken_text' => $spoken_text,
                'language_iso' => 'en',
                'next_options' => [
                    ['node_id' => 1, 'label' => 'Explore Main Menu']
                ]
            ]);
        } else {
            $state['step'] = 'phone_input';
            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => "Let's correct that. Please enter your Phone Number.",
                'spoken_text' => "Let us correct that. Please enter your Phone Number.",
                'language_iso' => 'en',
                'next_options' => []
            ]);
        }
    }
}

// Define Helper Functions for State Machine
if (!function_exists('get_workflow_options')) {
    function get_workflow_options($step, $lang) {
        global $mysqli;
        $options = [];

        if ($step['step_key'] === 'welcome_funnel') {
            $res = $mysqli->query("SELECT * FROM service_categories ORDER BY name ASC");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $options[] = [
                        'step_key' => 'category_selection',
                        'label' => $row['name'],
                        'payload_value' => $row['name']
                    ];
                }
                $res->free();
            }
        }
        elseif ($step['step_key'] === 'category_selection') {
            $options[] = [
                'step_key' => 'business_setup_dispatch',
                'label' => 'Schedule Consultation Meeting'
            ];
            $options[] = [
                'step_key' => 'welcome_funnel',
                'label' => 'Start Fresh'
            ];
        }

        $stmt = $mysqli->prepare("SELECT * FROM bot_workflow_steps WHERE parent_step_id = ? ORDER BY step_order ASC");
        if ($stmt) {
            $stmt->bind_param('i', $step['id']);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $label_col = 'primary_question_' . $lang;
                if (empty($row[$label_col])) {
                    $label_col = 'primary_question_en';
                }
                $options[] = [
                    'step_key' => $row['step_key'],
                    'label' => substr($row[$label_col], 0, 40)
                ];
            }
            $stmt->close();
        }

        return $options;
    }
}

if (!function_exists('get_workflow_options_by_key')) {
    function get_workflow_options_by_key($step_key, $lang) {
        global $mysqli;
        $stmt = $mysqli->prepare("SELECT * FROM bot_workflow_steps WHERE step_key = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $step_key);
            $stmt->execute();
            $res = $stmt->get_result();
            $step = $res->fetch_assoc();
            $stmt->close();
            if ($step) {
                return get_workflow_options($step, $lang);
            }
        }
        return [];
    }
}

if (!function_exists('log_bot_interaction')) {
    function log_bot_interaction($session_id, $transcript, $response, $match_type, $state_token) {
        global $mysqli;
        $user_id = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
        $stmt = $mysqli->prepare("INSERT INTO bot_interaction_logs (session_id, user_id, spoken_text_transcript, bot_response_text, match_type, active_state_token) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sissss', $session_id, $user_id, $transcript, $response, $match_type, $state_token);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Check if we should execute via Conversational State-Machine Funnel
$is_immersive = ($entry_point_input === 'immersive_landing' || isset($input['step_key']) || isset($_SESSION['active_workflow_state_token']));

if ($is_immersive) {
    // Determine language
    $lang = isset($_SESSION['bot_page_context']['language_iso']) ? $_SESSION['bot_page_context']['language_iso'] : 'en';
    if (!in_array($lang, ['en', 'fr', 'ar', 'ur'])) {
        $lang = 'en';
    }

    // Handle resets
    if (strtolower($message_content) === 'reset' || strtolower($message_content) === 'start fresh') {
        $_SESSION['active_workflow_state_token'] = 'welcome_funnel';
        $_SESSION['selected_workflow_payload'] = null;
        unset($_SESSION['registration_state']);
    }

    $active_state_token = isset($input['step_key']) ? trim($input['step_key']) : ($_SESSION['active_workflow_state_token'] ?? 'welcome_funnel');

    // Process voice or click interaction based on matched option labels
    if ($message_content !== '' && strtolower($message_content) !== 'reset' && strtolower($message_content) !== 'start fresh') {
        // Resolve options for active step and match
        $stmt_step = $mysqli->prepare("SELECT * FROM bot_workflow_steps WHERE step_key = ? LIMIT 1");
        if ($stmt_step) {
            $stmt_step->bind_param('s', $active_state_token);
            $stmt_step->execute();
            $res_step = $stmt_step->get_result();
            $current_step = $res_step->fetch_assoc();
            $stmt_step->close();
        }

        $matched_opt = null;
        if ($current_step) {
            $options = get_workflow_options($current_step, $lang);
            foreach ($options as $opt) {
                if (strcasecmp($opt['label'], $message_content) === 0 || stripos($message_content, $opt['label']) !== false) {
                    $matched_opt = $opt;
                    break;
                }
            }
        }

        if ($matched_opt) {
            $active_state_token = $matched_opt['step_key'];
            $_SESSION['active_workflow_state_token'] = $active_state_token;
            if (isset($matched_opt['payload_value'])) {
                $_SESSION['selected_workflow_payload'] = $matched_opt['payload_value'];
            }
        } else {
            // Intent/Option did not match directly -> hand off to RAG or Fail-Closed
            $rag_context = "";
            $has_rag_matches = false;
            $source_files = [];
            $chunks = [];

            $stmt_rag = $mysqli->prepare("SELECT text_content, file_name, page_number FROM local_knowledge_base WHERE MATCH(text_content) AGAINST(?) LIMIT 3");
            if ($stmt_rag) {
                $stmt_rag->bind_param('s', $message_content);
                $stmt_rag->execute();
                $res_rag = $stmt_rag->get_result();
                if ($res_rag && $res_rag->num_rows > 0) {
                    $has_rag_matches = true;
                    while ($row_rag = $res_rag->fetch_assoc()) {
                        $chunks[] = $row_rag['text_content'];
                        $source_files[] = "[Source: " . $row_rag['file_name'] . ", Page " . $row_rag['page_number'] . "]";
                    }
                    $rag_context = implode("\n\n", $chunks);
                }
                $stmt_rag->close();
            }

            if ($has_rag_matches) {
                $top_match = $chunks[0];
                $display_text = "Verified Guidelines: " . $top_match;
                if (strlen($display_text) > 180) {
                    $display_text = substr($display_text, 0, 180) . "...";
                }
                if (!empty($source_files)) {
                    $display_text .= "\n\n" . implode(", ", array_unique($source_files));
                }

                log_bot_interaction($session_token, $message_content, $display_text, 'rag_fallback', $active_state_token);

                send_json_response([
                    'status' => 'success',
                    'session_token' => $session_token,
                    'display_text' => $display_text,
                    'spoken_text' => "According to our guidelines: " . substr($top_match, 0, 100),
                    'language_iso' => $lang,
                    'active_state_token' => $active_state_token,
                    'next_options' => get_workflow_options_by_key($active_state_token, $lang)
                ]);
            } else {
                // Fail-Closed
                $page_url = $current_page_context['url'] ?? 'bot-landing.php';
                $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

                $stmt_fail = $mysqli->prepare("INSERT INTO bot_failed_questions (session_id, user_id, language_iso, unanswered_question, page_context_url) VALUES (?, ?, ?, ?, ?)");
                if ($stmt_fail) {
                    $session_db_id = 1;
                    $stmt_fail->bind_param('iisss', $session_db_id, $userId, $lang, $message_content, $page_url);
                    $stmt_fail->execute();
                    $stmt_fail->close();
                }

                $fallback_display = "I am unable to find that specific configuration in my database right now, but I have logged your question for our support team to review. Let me loop you back to our primary menu options.";

                log_bot_interaction($session_token, $message_content, $fallback_display, 'rag_fallback', $active_state_token);

                send_json_response([
                    'status' => 'success',
                    'session_token' => $session_token,
                    'display_text' => $fallback_display,
                    'spoken_text' => $fallback_display,
                    'language_iso' => $lang,
                    'active_state_token' => $active_state_token,
                    'next_options' => get_workflow_options_by_key($active_state_token, $lang)
                ]);
            }
        }
    }

    // Check for Context Preservation & Automated Funnel Acceleration
    $has_category_context = !empty($current_page_context['category_name']) || !empty($current_page_context['service_title']);
    if ($active_state_token === 'welcome_funnel' && $has_category_context) {
        $active_state_token = 'category_selection';
        $_SESSION['active_workflow_state_token'] = 'category_selection';
        $category_name = $current_page_context['category_name'] ?? $current_page_context['service_title'] ?? 'Selected Service';
        $_SESSION['selected_workflow_payload'] = $category_name;

        $context_greeting = "I see you are looking for " . $category_name . ". Excellent! We have updated the right panel layout with customized service options. What would you like to do next?";
        log_bot_interaction($session_token, $message_content, $context_greeting, 'workflow_step', 'category_selection');

        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => $context_greeting,
            'spoken_text' => "I see you are looking for " . $category_name . ". We have updated the right panel layout. What would you like to do next?",
            'language_iso' => $lang,
            'active_state_token' => 'category_selection',
            'next_options' => get_workflow_options_by_key('category_selection', $lang),
            'client_action' => [
                'type' => 'apply_filters',
                'category_name' => $category_name,
                'url' => 'services.php?category=' . urlencode($category_name)
            ]
        ]);
    }

    // Retrieve active step details
    $stmt_step = $mysqli->prepare("SELECT * FROM bot_workflow_steps WHERE step_key = ? LIMIT 1");
    if ($stmt_step) {
        $stmt_step->bind_param('s', $active_state_token);
        $stmt_step->execute();
        $res_step = $stmt_step->get_result();
        $step = $res_step->fetch_assoc();
        $stmt_step->close();
    }

    if ($step) {
        $_SESSION['active_workflow_state_token'] = $step['step_key'];

        $question_col = 'primary_question_' . $lang;
        if (empty($step[$question_col])) {
            $question_col = 'primary_question_en';
        }
        $display_text = $step[$question_col];

        // Handle execution actions
        $client_action = null;
        if ($step['execution_action'] === 'hydrate_right_panel') {
            $cat_name = $_SESSION['selected_workflow_payload'] ?? 'all';
            $client_action = [
                'type' => 'page_swap',
                'url' => 'services.php?category=' . urlencode($cat_name)
            ];
        } elseif ($step['execution_action'] === 'apply_filters') {
            $cat_name = $_SESSION['selected_workflow_payload'] ?? 'all';
            $client_action = [
                'type' => 'apply_filters',
                'category_name' => $cat_name,
                'url' => 'services.php?category=' . urlencode($cat_name)
            ];
        } elseif ($step['execution_action'] === 'dispatch_case_meeting') {
            if (!isset($_SESSION['user']['id'])) {
                // Return redirect payload to register/login
                $_SESSION['redirect_after_login'] = 'bot-landing.php';
                $display_text = "To schedule a direct consultation meeting, please sign in or register an account first. We have saved your target context securely.";
                $client_action = [
                    'type' => 'redirect_auth',
                    'url' => 'login.php',
                    'state_token' => $step['step_key']
                ];
            } else {
                // Auto create the case
                $provider_id = 1;
                $service_id = 1;
                $case_uuid = generate_uuid();
                $customer_user_id = (int)$_SESSION['user']['id'];
                $status = 'Pending Appointment';
                $customer_message = "Automated meeting request initialized via AI Assistant";

                $stmt_case = $mysqli->prepare("INSERT INTO cases (uuid, customer_user_id, provider_id, service_id, status, customer_message) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt_case) {
                    $stmt_case->bind_param('siiiss', $case_uuid, $customer_user_id, $provider_id, $service_id, $status, $customer_message);
                    $stmt_case->execute();
                    $stmt_case->close();

                    require_once __DIR__ . '/../lib/notifications_helper.php';
                    notify_vendor($provider_id, "New Meeting Request", "Customer " . $_SESSION['user']['name'] . " has requested an automated appointment.", "vendor/index.php");

                    $display_text = "Thank you! Your automated consultation appointment request has been successfully dispatched to the provider. They will reach out to you shortly.";
                    $client_action = [
                        'type' => 'toast_success',
                        'message' => 'Consultation appointment request successfully dispatched!'
                    ];
                }
            }
        }

        log_bot_interaction($session_token, $message_content, $display_text, 'workflow_step', $step['step_key']);

        $options = get_workflow_options($step, $lang);

        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => $display_text,
            'spoken_text' => $display_text,
            'language_iso' => $lang,
            'active_state_token' => $step['step_key'],
            'next_options' => $options,
            'client_action' => $client_action
        ]);
    }
}

// --- Predefined dialog values ---
$lang_labels = [
    'en' => 'English',
    'fr' => 'Français (French)',
    'ar' => 'العربية (Arabic)',
    'ur' => 'اردو / hindi (Hindi/Urdu)'
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
            'display_text' => 'Welcome to Global Ways! Please select your preferred language to begin.',
            'spoken_text' => 'Welcome to Global Ways! Please select your preferred language to begin.', 'target_action' => null
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
            'display_text' => 'Hello! Welcome to Global Ways. We simplify UAE documentation. Please select a service category to get started.',
            'spoken_text' => 'Hello! Welcome to Global Ways. We simplify UAE documentation. Please select a service category to get started.', 'target_action' => 'fetch_categories'
        ],
        3 => [
            'id' => 3, 'parent_id' => 11, 'node_type' => 'category_select', 'language_iso' => 'fr',
            'display_text' => 'Bonjour! Bienvenue sur Global Ways. Nous simplifions les démarches administratives aux Émirats Arabes Unis. Veuillez sélectionner une catégorie de service pour commencer.',
            'spoken_text' => 'Bonjour! Bienvenue sur Global Ways. Nous simplifions les démarches administratives aux Émirats Arabes Unis. Veuillez sélectionner une catégorie de service pour commencer.', 'target_action' => 'fetch_categories'
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
        $session['chat_logs'] = [];
        $node_id = null;
        $_SESSION['bot_page_context'] = [];
        session_regenerate_id(true);
    }

    // Local Document Retrieval (RAG) System & Fail-Closed Logging Hook for Mock Mode
    $is_system_action = in_array(strtolower($message_content), ['reset', 'back', 'start fresh', 'ai voice companion', 'browse independently']);
    if ($node_id === null && $message_content !== '' && !$is_system_action) {
        $rag_context = "";
        $has_rag_matches = false;
        $source_files = [];
        $language_iso = $session['selected_language'] ?: 'en';
        $chunks = [];

        $stmt_rag = $mysqli->prepare("SELECT text_content, file_name, page_number FROM local_knowledge_base WHERE MATCH(text_content) AGAINST(?) LIMIT 3");
        if ($stmt_rag) {
            $stmt_rag->bind_param('s', $message_content);
            $stmt_rag->execute();
            $res_rag = $stmt_rag->get_result();
            if ($res_rag && $res_rag->num_rows > 0) {
                $has_rag_matches = true;
                while ($row_rag = $res_rag->fetch_assoc()) {
                    $chunks[] = $row_rag['text_content'];
                    $source_files[] = "[Source: " . $row_rag['file_name'] . ", Page " . $row_rag['page_number'] . "]";
                }
                $rag_context = implode("\n\n", $chunks);
            }
            $stmt_rag->close();
        }

        if ($has_rag_matches) {
            $top_match = $chunks[0];
            $display_text = "Verified Guidelines: " . $top_match;
            if (strlen($display_text) > 180) {
                $display_text = substr($display_text, 0, 180) . "...";
            }
            if (!empty($source_files)) {
                $display_text .= "\n\n" . implode(", ", array_unique($source_files));
            }
            $spoken_text = "According to our verified guidelines: " . substr($top_match, 0, 150);

            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => $display_text,
                'spoken_text' => $spoken_text,
                'language_iso' => $language_iso,
                'next_options' => [
                    ['node_id' => 1, 'label' => 'Start Fresh']
                ]
            ]);
        } else {
            $page_url = 'bot-landing.php';
            $userId = null;

            $stmt_fail = $mysqli->prepare("INSERT INTO bot_failed_questions (session_id, user_id, language_iso, unanswered_question, page_context_url) VALUES (?, ?, ?, ?, ?)");
            if ($stmt_fail) {
                $stmt_fail->bind_param('iisss', $session_id, $userId, $language_iso, $message_content, $page_url);
                $stmt_fail->execute();
                $stmt_fail->close();
            }

            $fallback_display = "I am unable to find that specific configuration in my database right now, but I have logged your question for our support team to review. Let me loop you back to our primary menu options.";

            send_json_response([
                'status' => 'success',
                'session_token' => $session_token,
                'display_text' => $fallback_display,
                'spoken_text' => "I am unable to find that specific configuration in my database right now, but I have logged your question for our support team. Let me loop you back.",
                'language_iso' => $language_iso,
                'next_options' => [
                    ['node_id' => 1, 'label' => 'Start Fresh']
                ]
            ]);
        }
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
    // Purge all active history nodes/chat logs for this session
    $stmt_del = $mysqli->prepare("DELETE FROM bot_chat_logs WHERE session_id = ?");
    if ($stmt_del) {
        $stmt_del->bind_param('i', $session_id);
        $stmt_del->execute();
        $stmt_del->close();
    }
    $session['current_node_id'] = 1;
    $session['selected_language'] = null;
    $node_id = null;
    $_SESSION['bot_page_context'] = [];
    session_regenerate_id(true);
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

// Local Document Retrieval (RAG) System & Fail-Closed Logging Hook
$is_system_action = in_array(strtolower($message_content), ['reset', 'back', 'start fresh', 'ai voice companion', 'browse independently']);
if ($node_id === null && $message_content !== '' && !$is_system_action) {
    // 1. Run local MATCH AGAINST query to retrieve top matching documentation chunks
    $rag_context = "";
    $has_rag_matches = false;
    $source_files = [];
    $language_iso = $session['selected_language'] ?: 'en';
    $chunks = [];

    $stmt_rag = $mysqli->prepare("SELECT text_content, file_name, page_number FROM local_knowledge_base WHERE MATCH(text_content) AGAINST(?) LIMIT 3");
    if ($stmt_rag) {
        $stmt_rag->bind_param('s', $message_content);
        $stmt_rag->execute();
        $res_rag = $stmt_rag->get_result();
        if ($res_rag && $res_rag->num_rows > 0) {
            $has_rag_matches = true;
            while ($row_rag = $res_rag->fetch_assoc()) {
                $chunks[] = $row_rag['text_content'];
                $source_files[] = "[Source: " . $row_rag['file_name'] . ", Page " . $row_rag['page_number'] . "]";
            }
            $rag_context = implode("\n\n", $chunks);
        }
        $stmt_rag->close();
    }

    if ($has_rag_matches) {
        // Cleanly inject that local text content into our prompt context
        $ai_concierge_prompt = AI_SYSTEM_PROMPT_BLUEPRINT . "\n\nAUTHORITATIVE LOCAL CONTEXT:\nAnswer the user using only this verified local data:\n" . $rag_context;

        // Log custom user message in chat logs
        $sender_user = 'user';
        $stmt_u_log = $mysqli->prepare("INSERT INTO bot_chat_logs (session_id, sender, message_content) VALUES (?, ?, ?)");
        if ($stmt_u_log) {
            $stmt_u_log->bind_param('iss', $session_id, $sender_user, $message_content);
            $stmt_u_log->execute();
            $stmt_u_log->close();
        }

        // Generate dynamic response using top matched chunk
        $top_match = $chunks[0];
        $display_text = "Verified Guidelines: " . $top_match;
        if (strlen($display_text) > 180) {
            $display_text = substr($display_text, 0, 180) . "...";
        }
        // Append source citation
        if (!empty($source_files)) {
            $display_text .= "\n\n" . implode(", ", array_unique($source_files));
        }

        $spoken_text = "According to our verified guidelines: " . substr($top_match, 0, 150);

        // Log bot response in chat logs
        $sender_bot = 'bot';
        $stmt_b_log = $mysqli->prepare("INSERT INTO bot_chat_logs (session_id, sender, message_content) VALUES (?, ?, ?)");
        if ($stmt_b_log) {
            $stmt_b_log->bind_param('iss', $session_id, $sender_bot, $display_text);
            $stmt_b_log->execute();
            $stmt_b_log->close();
        }

        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => $display_text,
            'spoken_text' => $spoken_text,
            'language_iso' => $language_iso,
            'next_options' => [
                ['node_id' => 1, 'label' => 'Start Fresh']
            ]
        ]);
    } else {
        // 2. Fail-Closed Hook: Log unmapped query to bot_failed_questions
        $page_url = $current_page_context['url'] ?? 'bot-landing.php';
        $userId = isset($session['user_id']) ? (int)$session['user_id'] : null;

        $stmt_fail = $mysqli->prepare("INSERT INTO bot_failed_questions (session_id, user_id, language_iso, unanswered_question, page_context_url) VALUES (?, ?, ?, ?, ?)");
        if ($stmt_fail) {
            $stmt_fail->bind_param('iisss', $session_id, $userId, $language_iso, $message_content, $page_url);
            $stmt_fail->execute();
            $stmt_fail->close();
        }

        // Log custom user message in chat logs
        $sender_user = 'user';
        $stmt_u_log = $mysqli->prepare("INSERT INTO bot_chat_logs (session_id, sender, message_content) VALUES (?, ?, ?)");
        if ($stmt_u_log) {
            $stmt_u_log->bind_param('iss', $session_id, $sender_user, $message_content);
            $stmt_u_log->execute();
            $stmt_u_log->close();
        }

        // Fail-Closed Fallback Response
        $fallback_display = "I am unable to find that specific configuration in my database right now, but I have logged your question for our support team to review. Let me loop you back to our primary menu options.";

        $sender_bot = 'bot';
        $stmt_b_log = $mysqli->prepare("INSERT INTO bot_chat_logs (session_id, sender, message_content) VALUES (?, ?, ?)");
        if ($stmt_b_log) {
            $stmt_b_log->bind_param('iss', $session_id, $sender_bot, $fallback_display);
            $stmt_b_log->execute();
            $stmt_b_log->close();
        }

        send_json_response([
            'status' => 'success',
            'session_token' => $session_token,
            'display_text' => $fallback_display,
            'spoken_text' => "I am unable to find that specific configuration in my database right now, but I have logged your question for our support team. Let me loop you back.",
            'language_iso' => $language_iso,
            'next_options' => [
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
