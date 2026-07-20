<?php
// api/tts-processor.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Error reporting setup for clean output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/db_mysqli.php';

// Strict session-based access lock to prevent unauthenticated metered API abuse
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user']) && empty($_SESSION['bot_page_context'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Session required.']);
    exit;
}

// Retrieve incoming text payload
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);
$text = isset($input['text']) ? trim($input['text']) : (isset($_GET['text']) ? trim($_GET['text']) : '');

if (empty($text)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'fallback', 'message' => 'No text provided.']);
    exit;
}

$chars_count = mb_strlen($text, 'UTF-8');

// Function to log voice telemetry cleanly
function log_telemetry($engine, $chars, $is_error, $error_msg) {
    global $mysqli;
    $load = 0.00;
    if (function_exists('sys_getloadavg')) {
        $avg = sys_getloadavg();
        if (is_array($avg) && isset($avg[0])) {
            $load = (float)$avg[0];
        }
    }

    if (isset($mysqli) && !$mysqli->connect_errno) {
        $stmt_log = $mysqli->prepare("INSERT INTO `voice_telemetry_logs` (engine, characters_used, is_error, error_message, server_load) VALUES (?, ?, ?, ?, ?)");
        if ($stmt_log) {
            $stmt_log->bind_param('siisd', $engine, $chars, $is_error, $error_msg, $load);
            $stmt_log->execute();
            $stmt_log->close();
        }
    }
}

// Session-based sliding window rate-limiting (Max 10 requests per minute)
if (!isset($_SESSION['tts_rate_limit'])) {
    $_SESSION['tts_rate_limit'] = [];
}
$now = time();
$_SESSION['tts_rate_limit'] = array_filter($_SESSION['tts_rate_limit'], function($ts) use ($now) {
    return $ts > ($now - 60);
});
if (count($_SESSION['tts_rate_limit']) >= 10) {
    log_telemetry('native', $chars_count, 1, 'Rate limit exceeded');
    header('Content-Type: application/json');
    echo json_encode(['status' => 'fallback', 'message' => 'Rate limit exceeded. Max 10 per minute.']);
    exit;
}
$_SESSION['tts_rate_limit'][] = $now;

// Fetch settings live with zero caching
$el_status = 'OFF';
$el_api_key = '';
$el_voice_id = '21m00Tcm4TlvDq8ikWAM';
$el_stability = 0.75;
$el_clarity = 0.75;

if (isset($mysqli) && !$mysqli->connect_errno) {
    $stmt = $mysqli->prepare("SELECT `key`, `value` FROM `site_settings` WHERE `key` IN ('elevenlabs_status', 'elevenlabs_api_key', 'elevenlabs_voice_id', 'elevenlabs_stability', 'elevenlabs_clarity')");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            if ($row['key'] === 'elevenlabs_status') $el_status = $row['value'];
            elseif ($row['key'] === 'elevenlabs_api_key') $el_api_key = $row['value'];
            elseif ($row['key'] === 'elevenlabs_voice_id') $el_voice_id = $row['value'];
            elseif ($row['key'] === 'elevenlabs_stability') $el_stability = (float)$row['value'];
            elseif ($row['key'] === 'elevenlabs_clarity') $el_clarity = (float)$row['value'];
        }
        $stmt->close();
    }
}

// If ElevenLabs is toggled OFF, immediately return fallback response
if ($el_status !== 'ON' || empty($el_api_key)) {
    log_telemetry('native', $chars_count, 0, 'ElevenLabs is OFF or key is empty');
    header('Content-Type: application/json');
    echo json_encode(['status' => 'fallback', 'message' => 'Premium engine is currently turned off or not configured.']);
    exit;
}

try {
    // Call ElevenLabs API using Curl
    $voice_url = "https://api.elevenlabs.io/v1/text-to-speech/" . urlencode($el_voice_id) . "/stream";

    $payload = [
        "text" => $text,
        "model_id" => "eleven_monolingual_v1",
        "voice_settings" => [
            "stability" => $el_stability,
            "similarity_boost" => $el_clarity
        ]
    ];

    $ch = curl_init($voice_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "xi-api-key: " . $el_api_key,
        "Content-Type: application/json",
        "accept: audio/mpeg"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);

    $audio_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception("Curl network error: " . $curl_error);
    }

    if ($http_code !== 200) {
        // Parse error message if possible
        $err_details = json_decode($audio_data, true);
        $err_msg = isset($err_details['detail']['message']) ? $err_details['detail']['message'] : 'HTTP Code ' . $http_code;
        throw new Exception("ElevenLabs API failure: " . $err_msg);
    }

    // Successful ElevenLabs response
    log_telemetry('elevenlabs', $chars_count, 0, '');
    header('Content-Type: audio/mpeg');
    echo $audio_data;
    exit;

} catch (Exception $e) {
    // Log exception and return fallback code
    $error_message = $e->getMessage();
    log_telemetry('native', $chars_count, 1, $error_message);

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'fallback',
        'message' => $error_message
    ]);
    exit;
}
