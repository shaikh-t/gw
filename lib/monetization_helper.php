<?php
// lib/monetization_helper.php
require_once __DIR__ . '/db_mysqli.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Renders an HTML ad placement or a programmatic script fallback for a specific zone.
 *
 * @param string $zone_name The target placement zone (e.g., 'site_header_leaderboard', 'site_sidebar_banner', 'site_footer_banner')
 * @return string The HTML content to be displayed or printed.
 */
function render_layout_ad_placement(string $zone_name): string {
    global $mysqli;

    // 1. Resolve context parameters
    $active_page = basename($_SERVER['PHP_SELF']);
    $bot_page_context = $_SESSION['bot_page_context'] ?? [];

    // Attempt to extract category ID and language ISO from the session page context
    $category_id = isset($bot_page_context['category_id']) ? (int)$bot_page_context['category_id'] : null;
    $language_iso = isset($bot_page_context['language_iso']) ? trim($bot_page_context['language_iso']) : 'en';
    if (empty($language_iso)) {
        $language_iso = 'en';
    }

    // 2. Query for matching direct sponsor ads
    // Sort by specificity: exact page context matches first, then global fallback
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

    // Sort to prioritize exact page matches over global fallback
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
            // Check budget or temporal criteria based on ad billing model
            $is_eligible = false;

            if ($ad['ad_billing_model'] === 'flat_rate_temporal') {
                // Check temporal date range strictly
                $now = date('Y-m-d H:i:s');
                $start_valid = empty($ad['start_date']) || ($ad['start_date'] <= $now);
                $end_valid = empty($ad['end_date']) || ($ad['end_date'] >= $now);
                if ($start_valid && $end_valid) {
                    $is_eligible = true;
                }
            } else {
                // PPC / PPI models
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

                // Build clean HTML direct sponsor banner markup
                $tracker_url = "api/bot-ad-tracker.php?ad_id=" . urlencode($ad['id']);

                return '
                <div class="ad-placement-container my-3 p-3 border rounded bg-white shadow-sm text-center" style="max-width: 100%;">
                    <div class="small text-muted mb-2 text-uppercase font-monospace" style="font-size: 0.7rem; tracking: 1px;">Sponsored Content</div>
                    <a href="' . htmlspecialchars($tracker_url) . '" target="_blank" class="text-decoration-none">
                        <div class="ad-banner-text fs-5 fw-bold text-primary mb-1">' . htmlspecialchars($ad['banner_text']) . '</div>
                        <div class="ad-banner-subtext small text-secondary">Click here to learn more <i class="bi bi-box-arrow-up-right small"></i></div>
                    </a>
                </div>';
            }
        }
        $stmt->close();
    }

    // 3. Fallback: Search for programmatic (network) ad script code for this zone
    $stmt_prog = $mysqli->prepare("
        SELECT network_script_code FROM bot_ads
        WHERE placement_zone = ?
          AND ad_source_type = 'network_programmatic'
          AND is_active = 1
        ORDER BY id DESC LIMIT 1
    ");
    if ($stmt_prog) {
        $stmt_prog->bind_param('s', $zone_name);
        $stmt_prog->execute();
        $res_prog = $stmt_prog->get_result();
        if ($row_prog = $res_prog->fetch_assoc()) {
            $script_code = $row_prog['network_script_code'];
            $stmt_prog->close();

            return '
            <div class="ad-placement-container ad-placement-programmatic my-3 text-center">
                ' . $script_code . '
            </div>';
        }
        $stmt_prog->close();
    }

    return '';
}
?>