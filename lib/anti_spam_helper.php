<?php
/**
 * lib/anti_spam_helper.php
 * Anti-Spam, Bot Protection, and Security Helper Functions
 */

// Decoupled reCAPTCHA keys
if (!defined('RECAPTCHA_SITE_KEY')) {
    define('RECAPTCHA_SITE_KEY', '6LcH4VYtAAAAAKHSVcxZd4Vb6eiv6fHO0F0wN1uG');
}
if (!defined('RECAPTCHA_SECRET_KEY')) {
    define('RECAPTCHA_SECRET_KEY', '6LcH4VYtAAAAAB7PMfTAal-_1HLP0hl53ZRNUG7i');
}

/**
 * Robustly acquire the client's IP address, checking headers for proxies
 */
function get_client_ip(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return trim($_SERVER['HTTP_CLIENT_IP']);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Dynamically check and create the registration_attempts database table if it doesn't exist.
 */
function ensure_registration_attempts_table($mysqli) {
    $sql = "CREATE TABLE IF NOT EXISTS `registration_attempts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(45) NOT NULL,
        `attempt_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $mysqli->query($sql);
}

/**
 * Rate limiting: Checks if the IP has exceeded 3 registration attempts in 5 minutes.
 * Logs the current attempt in the database if not throttled.
 * Returns true if allowed, or false if blocked.
 */
function check_rate_limit($mysqli): bool {
    ensure_registration_attempts_table($mysqli);

    // Routine self-cleaning: Purge data older than 24 hours within the registration_attempts sliding window table
    $mysqli->query("DELETE FROM `registration_attempts` WHERE `attempt_time` < DATE_SUB(NOW(), INTERVAL 1 DAY)");

    $ip = get_client_ip();
    $minutes_threshold = 5;
    $max_attempts = 3;

    // Count attempts in the last 5 minutes from this IP
    $stmt = $mysqli->prepare("
        SELECT COUNT(*)
        FROM `registration_attempts`
        WHERE `ip_address` = ?
          AND `attempt_time` > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    if (!$stmt) {
        return true; // Graceful degradation if database check fails
    }
    $stmt->bind_param('si', $ip, $minutes_threshold);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count >= $max_attempts) {
        return false;
    }

    // Log this attempt
    $stmt_ins = $mysqli->prepare("INSERT INTO `registration_attempts` (`ip_address`) VALUES (?)");
    if ($stmt_ins) {
        $stmt_ins->bind_param('s', $ip);
        $stmt_ins->execute();
        $stmt_ins->close();
    }

    return true;
}

/**
 * Scans name strings for URLs and links.
 * Returns true if a link or URL is detected.
 */
function has_url_links(string $str): bool {
    $lower_str = strtolower($str);

    // Case-insensitive substring checks
    if (strpos($lower_str, 'http://') !== false ||
        strpos($lower_str, 'https://') !== false ||
        strpos($lower_str, 'www.') !== false) {
        return true;
    }

    // General URL/domain regex pattern
    $pattern = '/\b(?:https?:\/\/|www\.)[a-z0-9+&@#\/%?=~_|!:,.;]*[a-z0-9+&@#\/%=~_|]/i';
    if (preg_match($pattern, $str)) {
        return true;
    }

    return false;
}

/**
 * Dynamically check and create the login_attempts database table if it doesn't exist.
 */
function ensure_login_attempts_table($mysqli) {
    $sql = "CREATE TABLE IF NOT EXISTS `login_attempts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(45) NOT NULL,
        `attempt_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $mysqli->query($sql);
}

/**
 * Checks if the IP has exceeded 5 failed login attempts in 5 minutes.
 * Returns true if blocked, false if allowed.
 */
function is_login_throttled($mysqli): bool {
    require_once __DIR__ . '/cache_helper.php';
    $ip = get_client_ip();
    $attempts = cache_get('login_attempts_' . $ip) ?: [];

    // Filter attempts to keep only those within the last 5 minutes
    $now = time();
    $attempts = array_filter($attempts, function($timestamp) use ($now) {
        return ($now - $timestamp) <= 300;
    });

    cache_set('login_attempts_' . $ip, $attempts, 300);
    return count($attempts) >= 5;
}

/**
 * Logs a failed login attempt for the current IP.
 */
function log_failed_login($mysqli): void {
    require_once __DIR__ . '/cache_helper.php';
    $ip = get_client_ip();
    $attempts = cache_get('login_attempts_' . $ip) ?: [];
    $attempts[] = time();
    cache_set('login_attempts_' . $ip, $attempts, 300);
}

/**
 * Clears failed login attempts history for the current IP after a successful login.
 */
function clear_failed_logins($mysqli): void {
    require_once __DIR__ . '/cache_helper.php';
    $ip = get_client_ip();
    cache_delete('login_attempts_' . $ip);
}

/**
 * Detects if the email domain is in a blocklist of disposable email services.
 */
function is_disposable_email(string $email): bool {
    $parts = explode('@', $email);
    if (count($parts) < 2) {
        return false;
    }
    $domain = strtolower(trim($parts[1]));

    $disposable_domains = [
        'mailinator.com',
        '10minutemail.com',
        'yopmail.com',
        'tempmail.com',
        'temp-mail.org'
    ];

    return in_array($domain, $disposable_domains, true);
}

/**
 * Verifies reCAPTCHA v3 response token against Google siteverify API.
 * Scores below 0.5 will be rejected.
 */
function verify_recaptcha(string $token, string $ip = ''): bool {
    // If keys are not configured, gracefully allow development bypass
    if (RECAPTCHA_SECRET_KEY === 'YOUR_SECRET_KEY') {
        return true;
    }

    if (empty($token)) {
        return false;
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $ip
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];

    $context  = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false;
    }

    $result = json_decode($response, true);
    if (isset($result['success']) && $result['success']) {
        $score = $result['score'] ?? 0;
        return $score >= 0.5;
    }

    return false;
}
