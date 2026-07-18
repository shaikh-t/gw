<?php
// seed_ad_test.php
require_once __DIR__ . '/lib/db_mysqli.php';

$campaign_name = 'Golden Visa Express Promotion';
$ad_source_type = 'direct_sponsor';
$placement_zone = 'site_header_leaderboard';
$target_page_context = 'global_fallback';
$target_category_id = null;
$language_iso = 'en';
$banner_text = 'Get Your Golden Visa Today - Fast & Secure by GoProAlpha';
$audio_speech_text = 'Special Promotion for Golden Visas.';
$destination_url = 'services.php';
$click_cost = 5.00;
$max_budget = 100.00;
$current_spend = 0.00;
$ad_billing_model = 'ppc';
$is_active = 1;

$stmt = $mysqli->prepare("
    INSERT INTO bot_ads (
        campaign_name, ad_source_type, placement_zone, target_page_context,
        target_category_id, language_iso, banner_text, audio_speech_text,
        destination_url, click_cost, max_budget, current_spend,
        ad_billing_model, is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
if ($stmt) {
    $stmt->bind_param(
        'ssssissssdddsi',
        $campaign_name, $ad_source_type, $placement_zone, $target_page_context,
        $target_category_id, $language_iso, $banner_text, $audio_speech_text,
        $destination_url, $click_cost, $max_budget, $current_spend,
        $ad_billing_model, $is_active
    );
    if ($stmt->execute()) {
        echo "Successfully seeded test direct sponsor ad.\n";
    } else {
        echo "Error: " . $mysqli->error . "\n";
    }
    $stmt->close();
} else {
    echo "Prepare failed: " . $mysqli->error . "\n";
}
?>