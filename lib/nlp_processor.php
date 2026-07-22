<?php
// lib/nlp_processor.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/cache_helper.php';

class NlpProcessor {
    /**
     * Fallback approved keywords for typo correction if DB is empty.
     */
    public static $fallback_keywords = [
        'business', 'setup', 'company', 'immigration', 'visa', 'office',
        'consultation', 'start', 'launch', 'open', 'incorporate', 'firm',
        'services', 'meeting', 'schedule', 'register', 'welcome', 'funnel',
        'selection', 'dispatch', 'visit', 'tourism', 'license', 'permit',
        'emirates', 'national', 'stamping', 'attestation', 'renewal',
        'consultant', 'advisory', 'partner', 'booking'
    ];

    /**
     * Cleans, corrects spelling, and maps synonyms dynamically.
     *
     * @param string $input Raw transcription or user input string.
     * @param string $lang ISO code of active language (en, fr, ar, ur).
     * @return string Normalized and corrected string (or system_intent_key).
     */
    public static function process(string $input, string $lang = 'en'): string {
        global $mysqli;

        // Step A (Normalization): Strip punctuation, convert to lowercase, and clear whitespace
        $normalized = preg_replace('/\p{P}/u', '', $input);
        $normalized = mb_strtolower($normalized, 'UTF-8');
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        if ($normalized === '') {
            return '';
        }

        // Determine if requested inside Admin Panels or public widget
        $is_admin = false;
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (stripos($script_name, '/admin/') !== false || stripos($request_uri, '/admin/') !== false) {
            $is_admin = true;
        }

        $approved_keywords = [];
        $cache_key = 'bot_approved_keywords_' . $lang;

        if (!$is_admin) {
            // Public Chat Widget: use short 5-minute (300s) performance cache block
            $approved_keywords = CacheUtility::get($cache_key) ?: [];
        }

        // Database I/O fallback/forced query
        if (empty($approved_keywords)) {
            if (isset($mysqli) && !$mysqli->connect_errno) {
                $stmt_keys = $mysqli->prepare("SELECT keyword_token FROM bot_approved_keywords WHERE language_code = ?");
                if ($stmt_keys) {
                    $stmt_keys->bind_param('s', $lang);
                    $stmt_keys->execute();
                    $res_keys = $stmt_keys->get_result();
                    if ($res_keys) {
                        while ($row_key = $res_keys->fetch_assoc()) {
                            $approved_keywords[] = $row_key['keyword_token'];
                        }
                    }
                    $stmt_keys->close();
                }
            }

            // Save to transient cache if not in admin lifecycle
            if (!$is_admin && !empty($approved_keywords)) {
                CacheUtility::set($cache_key, $approved_keywords, 300);
            }
        }

        // Apply fallback if still empty
        if (empty($approved_keywords)) {
            $approved_keywords = self::$fallback_keywords;
        }

        // Step B (Levenshtein Distance Check): Compare input words against approved keywords word-by-word
        $words = explode(' ', $normalized);
        foreach ($words as &$word) {
            $word_len = mb_strlen($word, 'UTF-8');
            if ($word_len < 4) {
                continue;
            }

            $best_match = null;
            $min_distance = 9999;

            foreach ($approved_keywords as $keyword) {
                $keyword_len = strlen($keyword);
                $max_allowed = max(1, (int)floor($keyword_len / 4));

                $dist = levenshtein($word, $keyword);
                if ($dist <= $max_allowed && $dist < $min_distance) {
                    $min_distance = $dist;
                    $best_match = $keyword;
                }
            }

            if ($best_match !== null) {
                $word = $best_match;
            }
        }
        $corrected = implode(' ', $words);

        // Step C (Synonym Resolution): Query the 'bot_intent_synonyms' table
        if (!isset($mysqli) || $mysqli->connect_errno) {
            return $corrected;
        }

        // Layer 1: Attempt exact clean match of the entire processed, normalized user input string
        $stmt = $mysqli->prepare("SELECT system_intent_key FROM bot_intent_synonyms WHERE phrase_variant = ? AND language_code = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('ss', $corrected, $lang);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $stmt->close();
                return $row['system_intent_key'];
            }
            $stmt->close();
        }

        // Layer 2: Substring boundary evaluation fallback
        $stmt_all = $mysqli->prepare("SELECT system_intent_key, phrase_variant FROM bot_intent_synonyms WHERE language_code = ?");
        if ($stmt_all) {
            $stmt_all->bind_param('s', $lang);
            $stmt_all->execute();
            $res_all = $stmt_all->get_result();
            if ($res_all) {
                while ($row = $res_all->fetch_assoc()) {
                    $variant = $row['phrase_variant'];
                    $pattern = '/(?<=^|\s)' . preg_quote($variant, '/') . '(?=$|\s)/u';
                    if (preg_match($pattern, $corrected)) {
                        $stmt_all->close();
                        return $row['system_intent_key'];
                    }
                }
            }
            $stmt_all->close();
        }

        return $corrected;
    }
}
