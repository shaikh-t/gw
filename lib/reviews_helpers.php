<?php
// lib/reviews_helpers.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/notifier.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/**
 * Lightweight spam heuristic.
 * Replace with a third-party spam detection service in production.
 * Returns true if content looks like spam.
 */
function review_is_spam(array $data): bool {
    $title = strtolower(trim($data['title'] ?? ''));
    $body  = strtolower(trim($data['body'] ?? ''));
    if ($body === '' && $title === '') return true;
    if (strlen($body) < 10) return true;
    $linkCount = substr_count($body, 'http://') + substr_count($body, 'https://');
    if ($linkCount > 3) return true;
    $blacklist = ['buy now', 'free money', 'visit my', 'click here', 'work from home'];
    foreach ($blacklist as $b) {
        if ($b !== '' && (strpos($body, $b) !== false || strpos($title, $b) !== false)) return true;
    }
    return false;
}

/**
 * Create a review.
 * $data keys: user_id (required), provider_id OR service_id (one required), rating (1-5), title, body
 * Returns ['ok'=>true,'id'=>int] or ['ok'=>false,'error'=>string]
 */
function review_create(array $data) {
    global $mysqli;

    $user_id     = intval($data['user_id'] ?? 0);
    $provider_id = isset($data['provider_id']) ? intval($data['provider_id']) : null;
    $service_id  = isset($data['service_id']) ? intval($data['service_id']) : null;
    $rating      = intval($data['rating'] ?? 0);
    $title       = trim($data['title'] ?? '');
    $body        = trim($data['body'] ?? '');

    if ($user_id <= 0) return ['ok' => false, 'error' => 'Invalid user'];
    if ($rating < 1 || $rating > 5) return ['ok' => false, 'error' => 'Rating must be between 1 and 5'];

    // Target validation
    if (!$service_id && !$provider_id) return ['ok' => false, 'error' => 'Must target a provider or a service'];

    // Duplicate prevention: one review per user per service OR one provider-level review per user
    if ($service_id) {
        $s_id = intval($service_id);
        $sql = "SELECT id FROM reviews WHERE user_id = " . intval($user_id) . " AND service_id = $s_id LIMIT 1";
        if ($r = $mysqli->query($sql)) {
            if ($r->num_rows > 0) { $r->free(); return ['ok' => false, 'error' => 'You already reviewed this service']; }
            $r->free();
        }
    } else {
        $p_id = intval($provider_id);
        $sql = "SELECT id FROM reviews WHERE user_id = " . intval($user_id) . " AND provider_id = $p_id AND service_id IS NULL LIMIT 1";
        if ($r = $mysqli->query($sql)) {
            if ($r->num_rows > 0) { $r->free(); return ['ok' => false, 'error' => 'You already reviewed this provider']; }
            $r->free();
        }
    }

    // Spam check
    $isSpam = review_is_spam(['title' => $title, 'body' => $body]);

    // Auto-publish policy: default pending, auto-publish for trusted users
    $status = 'pending';
    if ($isSpam) {
        $status = 'hidden';
    } else {
        $trusted = false;
        $cntRes = $mysqli->query("SELECT COUNT(*) AS cnt FROM reviews WHERE user_id = " . intval($user_id) . " AND status = 'published'");
        if ($cntRes) {
            $row = $cntRes->fetch_assoc();
            if (intval($row['cnt']) >= 5) $trusted = true;
            $cntRes->free();
        }
        if ($trusted) $status = 'published';
    }

    // Prepare values for insertion (escape strings)
    $p_sql = $provider_id !== null ? intval($provider_id) : 'NULL';
    $s_sql = $service_id !== null ? intval($service_id) : 'NULL';
    $rating_sql = intval($rating);
    $title_sql = $mysqli->real_escape_string($title);
    $body_sql = $mysqli->real_escape_string($body);
    $status_sql = $mysqli->real_escape_string($status);

    $insertSql = "INSERT INTO reviews (user_id, provider_id, service_id, rating, title, body, status, created_at)
                  VALUES (" . intval($user_id) . ", $p_sql, $s_sql, $rating_sql, '$title_sql', '$body_sql', '$status_sql', NOW())";

    if (!$mysqli->query($insertSql)) {
        return ['ok' => false, 'error' => $mysqli->error];
    }
    $id = $mysqli->insert_id;

    // Recalculate aggregates if published
    if ($status === 'published') {
        review_recalculate_aggregates($provider_id, $service_id);
    }

    // Notify moderation team if pending or hidden
    if (in_array($status, ['pending', 'hidden'], true)) {
        notifier_send_email('admin@example.com', 'New review awaiting moderation', "A new review (#{$id}) requires moderation.");
    } else {
        // Optionally notify provider owner on publish
        if ($provider_id) {
            $q = "SELECT owner_user_id FROM providers WHERE id = " . intval($provider_id) . " LIMIT 1";
            if ($r = $mysqli->query($q)) {
                if ($row = $r->fetch_assoc()) {
                    $owner = intval($row['owner_user_id']);
                    if ($owner) {
                        $uRes = $mysqli->query("SELECT email, name FROM users WHERE id = " . $owner . " LIMIT 1");
                        if ($uRes && ($uRow = $uRes->fetch_assoc())) {
                            notifier_send_email($uRow['email'], 'New review published for your provider', "<p>A new review was published for your provider.</p>");
                            $uRes->free();
                        }
                    }
                }
                $r->free();
            }
        }
    }

    return ['ok' => true, 'id' => $id];
}

/**
 * Get a single review by id
 */
function review_get(int $id) {
    global $mysqli;
    $id = intval($id);
    $sql = "SELECT r.*, u.name AS user_name FROM reviews r LEFT JOIN users u ON u.id = r.user_id WHERE r.id = $id LIMIT 1";
    if ($res = $mysqli->query($sql)) {
        $row = $res->fetch_assoc();
        $res->free();
        return $row ?: null;
    }
    return null;
}

/**
 * List reviews for a provider or service with pagination.
 * $opts: provider_id|service_id, page, per_page, status ('published'|'all'|'pending')
 */
function review_list(array $opts = []) {
    global $mysqli;
    $provider_id = isset($opts['provider_id']) ? intval($opts['provider_id']) : null;
    $service_id  = isset($opts['service_id']) ? intval($opts['service_id']) : null;
    $page        = max(1, intval($opts['page'] ?? 1));
    $per         = max(1, min(100, intval($opts['per_page'] ?? 20)));
    $offset      = ($page - 1) * $per;
    $status      = $opts['status'] ?? 'published';

    $where = [];
    if ($service_id) $where[] = "r.service_id = " . intval($service_id);
    if ($provider_id) $where[] = "r.provider_id = " . intval($provider_id);
    if (empty($where)) return ['ok' => false, 'error' => 'No target specified'];

    if ($status === 'published') {
        $where[] = "r.status = 'published'";
    } elseif ($status === 'pending') {
        $where[] = "r.status = 'pending'";
    }

    $sqlWhere = implode(' AND ', $where);

    // total count
    $countSql = "SELECT COUNT(*) AS cnt FROM reviews r WHERE $sqlWhere";
    $total = 0;
    if ($res = $mysqli->query($countSql)) {
        $row = $res->fetch_assoc();
        $total = intval($row['cnt']);
        $res->free();
    }

    // fetch rows
    $rows = [];
    $sql = "SELECT r.*, u.name AS user_name FROM reviews r LEFT JOIN users u ON u.id = r.user_id WHERE $sqlWhere ORDER BY r.created_at DESC LIMIT " . intval($offset) . ", " . intval($per);
    if ($res = $mysqli->query($sql)) {
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $res->free();
    }

    return ['ok' => true, 'total' => $total, 'page' => $page, 'per_page' => $per, 'reviews' => $rows];
}

/**
 * Recalculate rating aggregates for provider and/or service.
 * Pass provider_id and/or service_id (nullable).
 */
function review_recalculate_aggregates($provider_id = null, $service_id = null) {
    global $mysqli;
    if ($service_id) {
        $s = intval($service_id);
        $sql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE service_id = $s AND status = 'published'";
        if ($res = $mysqli->query($sql)) {
            $row = $res ? $res->fetch_assoc() : null;
            $avgVal = ($row && isset($row['avg_rating']) && $row['avg_rating'] !== null) ? round(floatval($row['avg_rating']), 2) : null;
            $cntVal = ($row && isset($row['cnt'])) ? intval($row['cnt']) : 0;
            if ($res && !is_bool($res)) $res->free();
            $avg_sql = $avgVal === null ? "NULL" : "'" . $mysqli->real_escape_string((string)$avgVal) . "'";
            $mysqli->query("UPDATE services SET rating_avg = $avg_sql, rating_count = $cntVal WHERE id = $s");
        }
    }
    if ($provider_id) {
        $p = intval($provider_id);
        $sql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE provider_id = $p AND status = 'published'";
        if ($res = $mysqli->query($sql)) {
            $row = $res ? $res->fetch_assoc() : null;
            $avgVal = ($row && isset($row['avg_rating']) && $row['avg_rating'] !== null) ? round(floatval($row['avg_rating']), 2) : null;
            $cntVal = ($row && isset($row['cnt'])) ? intval($row['cnt']) : 0;
            if ($res && !is_bool($res)) $res->free();
            $avg_sql = $avgVal === null ? "NULL" : "'" . $mysqli->real_escape_string((string)$avgVal) . "'";
            $mysqli->query("UPDATE providers SET rating_avg = $avg_sql, rating_count = $cntVal WHERE id = $p");
        }
    }
}

/**
 * Admin moderation action.
 * $action: approve|reject|hide|unhide|flag_spam
 * Returns ['ok'=>true] or ['ok'=>false,'error'=>...]
 */
function review_moderate(int $review_id, string $action, int $actor_user_id = null, string $note = '') {
    global $mysqli;
    $valid = ['approve', 'reject', 'hide', 'unhide', 'flag_spam'];
    if (!in_array($action, $valid, true)) return ['ok' => false, 'error' => 'Invalid action'];

    $review = review_get($review_id);
    if (!$review) return ['ok' => false, 'error' => 'Review not found'];

    $newStatus = $review['status'];
    if ($action === 'approve') $newStatus = 'published';
    if ($action === 'reject') $newStatus = 'rejected';
    if ($action === 'hide') $newStatus = 'hidden';
    if ($action === 'unhide') $newStatus = 'published';
    if ($action === 'flag_spam') $newStatus = 'hidden';

    $status_sql = $mysqli->real_escape_string($newStatus);
    $updateSql = "UPDATE reviews SET status = '$status_sql', updated_at = NOW() WHERE id = " . intval($review_id);
    if (!$mysqli->query($updateSql)) return ['ok' => false, 'error' => $mysqli->error];

    $actor_sql = $actor_user_id ? intval($actor_user_id) : 'NULL';
    $note_sql = $mysqli->real_escape_string($note);
    $logSql = "INSERT INTO review_moderation_logs (review_id, actor_user_id, action, note, created_at) VALUES (" . intval($review_id) . ", " . ($actor_sql === 'NULL' ? "NULL" : $actor_sql) . ", '" . $mysqli->real_escape_string($action) . "', '$note_sql', NOW())";
    $mysqli->query($logSql);

    // Recalculate aggregates if status changed to/from published
    if (in_array($action, ['approve', 'reject', 'hide', 'unhide'], true)) {
        review_recalculate_aggregates($review['provider_id'], $review['service_id']);
    }

    // Notify author on approve/reject
    if (in_array($action, ['approve', 'reject'], true)) {
        $uRes = $mysqli->query("SELECT email, name FROM users WHERE id = " . intval($review['user_id']) . " LIMIT 1");
        if ($uRes && ($uRow = $uRes->fetch_assoc())) {
            $statusLabel = $action === 'approve' ? 'published' : 'rejected';
            notifier_send_email($uRow['email'], "Your review has been {$statusLabel}", "<p>Hello " . htmlspecialchars($uRow['name'], ENT_QUOTES) . ",</p><p>Your review has been {$statusLabel} by our moderation team.</p>");
            $uRes->free();
        }
    }

    return ['ok' => true];
}

/**
 * Flag a review (user action).
 * Returns ['ok'=>true] or ['ok'=>false,'error'=>...]
 */
function review_flag(int $review_id, int $user_id = null, string $reason = '') {
    global $mysqli;
    $uid_sql = $user_id ? intval($user_id) : 'NULL';
    $reason_sql = $mysqli->real_escape_string($reason);
    $sql = "INSERT INTO review_flags (review_id, user_id, reason, created_at) VALUES (" . intval($review_id) . ", " . ($uid_sql === 'NULL' ? "NULL" : $uid_sql) . ", '$reason_sql', NOW())";
    if (!$mysqli->query($sql)) return ['ok' => false, 'error' => $mysqli->error];

    notifier_send_email('admin@example.com', 'Review flagged', "Review #{$review_id} was flagged. Reason: " . htmlspecialchars($reason, ENT_QUOTES));
    return ['ok' => true];
}


/**
 * Create a review as an admin
 * $data keys: admin_user_id, user_id (author), provider_id OR service_id, rating, title, body, publish_now (bool), bypass_duplicates (bool)
 * Returns ['ok'=>true,'id'=>int] or ['ok'=>false,'error'=>string]
 */
function review_create_admin(array $data) {
    global $mysqli;

    $admin_id        = intval($data['admin_user_id'] ?? 0);
    $user_id         = intval($data['user_id'] ?? 0);
    $provider_id     = isset($data['provider_id']) ? intval($data['provider_id']) : null;
    $service_id      = isset($data['service_id']) ? intval($data['service_id']) : null;
    $rating          = intval($data['rating'] ?? 0);
    $title           = trim($data['title'] ?? '');
    $body            = trim($data['body'] ?? '');
    $publish_now     = !empty($data['publish_now']);
    $bypass_dups     = !empty($data['bypass_duplicates']);

    if ($admin_id <= 0) return ['ok' => false, 'error' => 'Invalid admin'];
    if ($user_id <= 0) return ['ok' => false, 'error' => 'Invalid author user'];
    if ($rating < 1 || $rating > 5) return ['ok' => false, 'error' => 'Rating must be 1-5'];
    if (!$service_id && !$provider_id) return ['ok' => false, 'error' => 'Must target a provider or a service'];

    // Duplicate prevention unless bypassed
    if (!$bypass_dups) {
        if ($service_id) {
            $sql = "SELECT id FROM reviews WHERE user_id = " . intval($user_id) . " AND service_id = " . intval($service_id) . " LIMIT 1";
            if ($r = $mysqli->query($sql)) {
                if ($r->num_rows > 0) { $r->free(); return ['ok' => false, 'error' => 'User already reviewed this service']; }
                $r->free();
            }
        } else {
            $sql = "SELECT id FROM reviews WHERE user_id = " . intval($user_id) . " AND provider_id = " . intval($provider_id) . " AND service_id IS NULL LIMIT 1";
            if ($r = $mysqli->query($sql)) {
                if ($r->num_rows > 0) { $r->free(); return ['ok' => false, 'error' => 'User already reviewed this provider']; }
                $r->free();
            }
        }
    }

    // Decide status
    $status = $publish_now ? 'published' : 'pending';

    // Escape strings
    $p_sql = $provider_id !== null ? intval($provider_id) : 'NULL';
    $s_sql = $service_id !== null ? intval($service_id) : 'NULL';
    $rating_sql = intval($rating);
    $title_sql = $mysqli->real_escape_string($title);
    $body_sql = $mysqli->real_escape_string($body);
    $status_sql = $mysqli->real_escape_string($status);

    $insertSql = "INSERT INTO reviews (user_id, provider_id, service_id, rating, title, body, status, created_at)
                  VALUES (" . intval($user_id) . ", $p_sql, $s_sql, $rating_sql, '$title_sql', '$body_sql', '$status_sql', NOW())";

    if (!$mysqli->query($insertSql)) {
        return ['ok' => false, 'error' => $mysqli->error];
    }
    $id = $mysqli->insert_id;

    // Log admin action in moderation logs for audit
    $note = "Created by admin id " . intval($admin_id) . ($publish_now ? " and published" : " as pending");
    $note_sql = $mysqli->real_escape_string($note);
    $logSql = "INSERT INTO review_moderation_logs (review_id, actor_user_id, action, note, created_at)
               VALUES (" . intval($id) . ", " . intval($admin_id) . ", 'approve', '$note_sql', NOW())";
    $mysqli->query($logSql);

    // Recalculate aggregates if published
    if ($status === 'published') {
        review_recalculate_aggregates($provider_id, $service_id);
    }

    // Notify provider owner if published
    if ($status === 'published' && $provider_id) {
        $q = "SELECT owner_user_id FROM providers WHERE id = " . intval($provider_id) . " LIMIT 1";
        if ($r = $mysqli->query($q)) {
            if ($row = $r->fetch_assoc()) {
                $owner = intval($row['owner_user_id']);
                if ($owner) {
                    $uRes = $mysqli->query("SELECT email, name FROM users WHERE id = " . $owner . " LIMIT 1");
                    if ($uRes && ($uRow = $uRes->fetch_assoc())) {
                        notifier_send_email($uRow['email'], 'New review published for your provider', "<p>An admin added a review for your provider.</p>");
                        $uRes->free();
                    }
                }
            }
            $r->free();
        }
    }

    return ['ok' => true, 'id' => $id];
}
