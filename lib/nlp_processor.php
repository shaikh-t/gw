<?php
// lib/nlp_processor.php

class NlpProcessor {
    /**
     * Set of approved keywords for typo correction.
     */
    public static $approved_keywords = [
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
        // Strip punctuation using \p{P} Unicode selector
        $normalized = preg_replace('/\p{P}/u', '', $input);
        // Convert to lowercase
        $normalized = mb_strtolower($normalized, 'UTF-8');
        // Clear whitespace
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        if ($normalized === '') {
            return '';
        }

        // Step B (Levenshtein Distance Check): Compare input words against approved keywords word-by-word
        $words = explode(' ', $normalized);
        foreach ($words as &$word) {
            $word_len = mb_strlen($word, 'UTF-8');
            // Exclude short words (< 4 characters)
            if ($word_len < 4) {
                continue;
            }

            $best_match = null;
            $min_distance = 9999;

            foreach (self::$approved_keywords as $keyword) {
                $keyword_len = strlen($keyword);
                // Proportional formula: max_allowed = max(1, floor(keyword_length / 4))
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
            // Fallback if DB is not set or mock mode lacks mysqli connection
            return $corrected;
        }

        // Dual-Layer Evaluation
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
        // Fetch all synonym phrase variants for the current language
        $stmt_all = $mysqli->prepare("SELECT system_intent_key, phrase_variant FROM bot_intent_synonyms WHERE language_code = ?");
        if ($stmt_all) {
            $stmt_all->bind_param('s', $lang);
            $stmt_all->execute();
            $res_all = $stmt_all->get_result();
            if ($res_all) {
                while ($row = $res_all->fetch_assoc()) {
                    $variant = $row['phrase_variant'];
                    // Boundary check: variant must match cleanly as isolated concept block
                    // For english/latin-based languages we use word boundary \b.
                    // For Arabic/Urdu, we can use clean whitespace/edge boundaries or regex lookarounds.
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
