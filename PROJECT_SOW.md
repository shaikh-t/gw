# Project Statement of Work (SOW) - Site-Wide Contextual Ads & Hybrid Monetization Architecture

## 1. Executive Summary
This document registers the complete architectural definitions, design patterns, schema blueprints, and click-tracking loops implemented for the Site-Wide Contextual Ads, Global Layout Placements, and Hybrid Monetization Architecture in the GlobalWays portal.

All database operations and analytical controllers use strictly bound, parameterized MySQLi prepared statements to guarantee maximum transactional integrity and prevent SQL injection vectors.

---

## 2. Database Schema Blueprint (`bot_ads` & `bot_ad_clicks`)

### A. Table: `bot_ads`
Holds direct sponsor promotional campaigns and programmatic fallback script elements.
- **`id`**: INT UNSIGNED NOT NULL AUTO_INCREMENT Primary Key
- **`campaign_name`**: VARCHAR(255) NOT NULL
- **`ad_source_type`**: ENUM('direct_sponsor', 'network_programmatic') NOT NULL
- **`placement_zone`**: ENUM('bot_internal_chat', 'site_header_leaderboard', 'site_sidebar_banner', 'site_footer_banner') NOT NULL
- **`target_page_context`**: VARCHAR(255) DEFAULT 'global_fallback' (targeted page filename)
- **`target_category_id`**: INT UNSIGNED NULL DEFAULT NULL (Foreign key to `service_categories.id`)
- **`language_iso`**: VARCHAR(10) NOT NULL DEFAULT 'en' ('en', 'fr', 'ar', 'ur')
- **`banner_text`**: TEXT DEFAULT NULL (Direct sponsor promotional content)
- **`audio_speech_text`**: TEXT DEFAULT NULL (TTS voice prompt text)
- **`destination_url`**: VARCHAR(255) DEFAULT NULL (Target redirect link)
- **`network_script_code`**: LONGTEXT DEFAULT NULL (HTML/JS fallback code blocks like Google AdSense)
- **`click_cost`**: DECIMAL(10,2) NOT NULL DEFAULT 0.00
- **`max_budget`**: DECIMAL(10,2) NOT NULL DEFAULT 0.00
- **`current_spend`**: DECIMAL(10,2) NOT NULL DEFAULT 0.00
- **`ad_billing_model`**: ENUM('ppc', 'ppi', 'flat_rate_temporal') NOT NULL DEFAULT 'ppc'
- **`max_impressions`**: INT UNSIGNED NOT NULL DEFAULT 0 (Impression limits for PPI billing)
- **`current_impressions`**: INT UNSIGNED NOT NULL DEFAULT 0
- **`start_date`**: DATETIME DEFAULT NULL
- **`end_date`**: DATETIME DEFAULT NULL
- **`is_active`**: TINYINT(1) NOT NULL DEFAULT 1
- **`created_at`**: TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### B. Table: `bot_ad_clicks`
Logs user ad interactions.
- **`id`**: INT UNSIGNED NOT NULL AUTO_INCREMENT Primary Key
- **`ad_id`**: INT UNSIGNED NOT NULL (Foreign key to `bot_ads.id`)
- **`session_id`**: INT UNSIGNED DEFAULT NULL (Foreign key to `bot_sessions.id`)
- **`earned_amount`**: DECIMAL(10,2) NOT NULL DEFAULT 0.00
- **`clicked_at`**: TIMESTAMP DEFAULT CURRENT_TIMESTAMP

---

## 3. Ad Router & Matching Engine (`lib/monetization_helper.php`)
- **Category & Language Matching**: Dynamically resolves language and category information from `$_SESSION['bot_page_context']` and active page URI.
- **Specificity Sorting**: Direct sponsor queries prioritize matching `target_page_context = [active_filename]` before falling back to `'global_fallback'`.
- **Temporal Date-Only Run-Time Overrides**:
  - If `ad_billing_model` is `'flat_rate_temporal'`, budget ceilings (`current_spend < max_budget`) and impression counts (`current_impressions < max_impressions`) are completely ignored. Instead, the router evaluates strictly chronologically: checking that the system `NOW()` date is between `start_date` and `end_date`.
  - For standard models (`ppc` or `ppi`), budget ceilings are validated.
- **Programmatic Fallbacks**: If no eligible direct sponsor is returned, the engine fetches and outputs the raw code string assigned to `network_script_code` for that placement zone.

---

## 4. Secure Tracking & Atomic Draindown (`api/bot-ad-tracker.php`)
- Checks click authenticity by looking up the corresponding active session.
- Employs double-click and click-abuse duplicate protection mechanisms (using session registries and rate filters).
- Atomically charges budgets and increments spend:
  ```sql
  UPDATE bot_ads
  SET current_spend = current_spend + ?,
      is_active = CASE WHEN current_spend + ? >= max_budget THEN 0 ELSE 1 END
  WHERE id = ?
  ```
- Triggers a clean HTTP 302 redirect header straight to the direct sponsor's `destination_url`.

---

## 5. Conversational Bot Ad Delivery (`api/bot-controller.php`)
Successfully integrated dynamic `bot_internal_chat` ad selection. The matching direct sponsor's parameters are returned in a separate, clean JSON root-level element attribute named `ad_payload`:
```json
{
  "status": "success",
  "display_text": "...",
  "spoken_text": "...",
  "ad_payload": {
    "banner_text": "Sponsor text message",
    "destination_url": "api/bot-ad-tracker.php?ad_id=..."
  }
}
```

---

## 6. Super Admin Control Center (`admin/settings/bot_ads.php`)
- Restricts view access exclusively to **Super Admins** via strict role assertion checks (`is_role("Super Admin")`).
- Provides complete campaign configuration, edit forms, status toggling, and campaign deletion.
- Hosts visual analytics widgets backed by **Chart.js**, consuming reporting outputs directly from `/api/ad-revenue-charts.php`.
