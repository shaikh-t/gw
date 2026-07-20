# GlobalWays Marketplace - Project Statement of Work (SOW)
## Comprehensive Technical Manual & System Blueprint

---

### 1. EXECUTIVE SUMMARY & PLATFORM OBJECTIVE

GlobalWays Marketplace is an enterprise-grade, context-aware, highly secure, multidimensional service delivery, hybrid monetization, and consultancy platform.

The primary objective of this platform is to bridge the gap between clients seeking bespoke global consultancy services (e.g., Golden Visa, Business Setup, Mainland/Free Zone licensing) and verified elite professional providers, while simultaneously establishing a self-sustaining monetization loop.

This objective is achieved through several main pillars:
1. **Dynamic Context-Aware Conversational Interface**: A zero-latency, multi-lingual chatbot and immersive voice workspace (`bot-landing.php`) that maintains persistent browser-level page context to guide users through complex workflows.
2. **Local, Self-Hosted Intelligence**: A localized RAG (Retrieval-Augmented Generation) ingestion pipeline that reads, parses, and searches corporate consultancy regulations on-premise without recurring API costs.
3. **Multi-Channel Monetization Framework**: Global layout-level ad zones and conversational promotional engines with dual-model ad delivery (PPC, PPI, and Temporal Sponsor campaigns) complete with automatic programmatic script fallbacks.
4. **Rigorous Defense-in-Depth Security**: Strict mitigation against OWASP Top 10 vulnerabilities, securing all transactional records and analytical metrics behind strongly bound, parameterized MySQLi prepared statements.

---

### 2. COMPLETED MULTI-LAYER SECURITY ARCHITECTURE

To ensure maximum operational integrity and absolute security of user and financial data, a multi-layer defense-in-depth security paradigm has been established across the codebase.

#### A. Parameterized Prepared Statements (SQL Injection Mitigation)
All database interactions across the entire platform—specifically including the original core setup, CRM controls, and newly introduced monetization modules—have been refactored. Rather than relying on standard concatenation or manual escape filters, they use strictly bound, strongly typed parameterized MySQLi prepared statements.
- **Implementation**: Managed via `lib/db_mysqli.php` and its development compatibility fallback companion `lib/mock_mysqli.php`.
- **Typing Strictness**: Integer elements are strictly bound as `'i'`, double/decimal values as `'d'`, and alphanumeric/textual payloads as `'s'`.
- **Application Scope**: This covers high-traffic controllers like `api/bot-controller.php`, `api/bot-ad-tracker.php`, `api/ad-revenue-charts.php`, and customer procedurals under `customer/` and `vendor/`.

#### B. Automated Cross-Site Request Forgery (CSRF) Verification Gates
State-modifying actions (such as adding services, updating credentials, modifying ad budgets, and changing deduction percentage limits) are guarded by a stateful CSRF verification engine.
- **Session Tokens**: Unique cryptographically secure CSRF tokens are injected dynamically via forms (`lib/csrf.php`).
- **Strict Verification**: State mutation endpoints strictly enforce `verify_csrf_token()` on POST requests, dropping session-less or mismatched tokens with an immediate HTTP 403 Forbidden response.

#### C. Proxy-Aware IP-Based Brute-Force Rate Limiting
To defend against automated credential-stuffing and password-guessing operations, a stateful rate limiting layer is embedded inside `login_post.php` (and the admin panel equivalent).
- **Tracking Schema**: Active failed login attempts are tracked via the `login_attempts` table.
- **Constraint Threshold**: Limits IP addresses to a maximum of **5 failed attempts within a 5-minute sliding window**.
- **IP Resolution**: Employs proxy-aware lookup logic (interpreting headers like `HTTP_X_FORWARDED_FOR` safely with fallbacks) to prevent spoofing.
- **Reset Logic**: A successful login cleanly purges historical failure logs for that authenticating IP.

#### D. Invisible Google reCAPTCHA v3 Verification
The customer registration page (`register.php`) and vendor counterpart (`register-vendor.php`) incorporate invisible reCAPTCHA v3 validations.
- **Action Tokens**: Submissions generate frontend action tokens that are evaluated on the backend via curl POST requests to Google's verification API.
- **Development Bypass**: Employs an intelligent development environment bypass that allows seamless testing without active internet connections or credential blocks.

#### E. Silent CSS Honeypots
To drop bot automated form-submissions without processing overhead:
- Forms include an invisible input field: `website_url_verification`.
- The field is masked using absolute visual concealment CSS (`display:none; opacity:0; pointer-events:none;`).
- Mappings in `lib/anti_spam_helper.php` check if this field contains any string content. If filled, the request is instantly blackholed, simulating a successful completion to the bot while executing zero database actions.

#### F. Click-Fraud Rate Limiting sliding-window Check
To prevent malicious click syndicates or botnets from draining sponsor budgets, the redirect click tracker inside `api/bot-ad-tracker.php` executes a sliding-window validation check:
- **Proxy-Aware IP Filtering**: Resolves the visitor's client IP address securely, evaluating both `REMOTE_ADDR` and proxied `HTTP_X_FORWARDED_FOR` headers to ensure accuracy.
- **Hourly Click Constraint**: Enforces a strict limit of **maximum 3 clicks per hour per IP address** for each unique campaign. If an IP exceeds this threshold, the system flags the click, bypasses the billing updates, and issues a redirect straight to the destination page without charging the sponsor.

#### G. Secure Session Initialization Configurations
The platform enforces the highest grade of session parameter attributes configured natively upon session initialization inside `lib/auth.php` and key entry files to block cross-site scripting (XSS) and token theft:
```php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);
```

#### H. Integrated Pre-existing RBAC Compliance
Three fresh permission parameters have been natively integrated with our pre-existing role-based tables:
1. `can_manage_ads` (Allows access to monetization controls and ad creation forms).
2. `can_view_failed_queries` (Grants access to failed question timelines).
3. `can_edit_knowledge_base` (Allows direct manual text or PDF ingestion overrides).

---

### 3. MULTILINGUAL VOICE-BOT & DYNAMIC SITE-WIDE CONTEXT ENGINE

#### A. Frontend-Backend Overlay Widget Layout
The platform embeds a global assistant widget (`templates/bot-widget.php`) integrated natively inside `partials/frontend_footer.php`. This collapsible widget overlays on every public page, providing dynamic, conversational support without interrupting the user's browsing experience.

#### B. Immersive Split-Workspace (`bot-landing.php`)
For an intensive, dedicated AI experience, the system provides a full-screen split-workspace at `bot-landing.php`.
- **Left Panel (Fixed 400px)**: Contains persistent user history, recording toggles, active speech transcripts, and reset triggers.
- **Right Panel (Responsive Fluid)**: Renders the active layout, service detail, or customer profile panel currently referenced.
- **Interlock and Hydration**: As users speak to the assistant, the chatbot returns JSON payloads containing a `client_action` object. The widget intercepts these actions and uses AJAX to swap out the content of `#main-content-layout` on the fly, avoiding hard page-reloads and preserving the active Web Speech synthesis stream.

#### C. Dynamic Multi-Page Context Tracking Filters
Global page tracking is established in `partials/frontend_header.php`.
- **Browser-Level Session Store**: The system listens to page transitions and updates `$_SESSION['bot_page_context']` with relevant attributes (e.g., `page_name`, `category_id`, `language_iso`, or `vendor_name`).
- **Context Injection**: When the bot controller (`api/bot-controller.php`) is queried, it reads this session array, allowing the AI engine to generate highly specific recommendations (e.g., offering Golden Visa help on `service-detail.php?id=golden-visa` instead of generic welcome dialogues).

#### D. Native HTML5 Web Speech Audio Pipelines
Voice synthesis and vocal input recognition are implemented directly via standard client-side browser interfaces:
- **Speech Recognition**: Leverages `webkitSpeechRecognition` to stream user speech directly into text.
- **Speech Synthesis**: Leverages `window.speechSynthesis` to speak bot responses.
- **Language Locale Mapping**: Automatically maps the active user language code:
  - English: `en-US`
  - French: `fr-FR`
  - Arabic: `ar-SA`
  - Urdu & Hindi: Shared Phonetic Layout mapped to `ur-PK`

#### E. Conversational Data Capture & Interactive Loops
- **Spell-Checking & Verification**: When capturing complex fields (e.g. user names or business emails), the bot initiates an interactive spell-checking verification loop, confirming the character inputs back to the user before committing them.
- **Character Set Sanity**: Strictly filters user inputs, allowing accented Western/diacritic elements while blocking non-Latin typography for user creation and naming processes.

#### F. Frontend Audio Waveform SVG Micro-interactions Framework
Inside `bot-landing.php`, a modern inline SVG audio waveform indicator is embedded right alongside the mic trigger badge.
- **Reactive Listeners**: The system binds client-side webkitSpeechRecognition event listeners (`onstart` / `onend`).
- **Dynamic Animation Toggle**: When recording begins, the browser injects `.waveform-rippling` to animate the SVG paths dynamically via smooth CSS transitions. Upon stopping, the indicator is cleanly hidden, preventing visual distraction or interface clutter.

#### G. Operational Guide: Frontend Website & Voice Bot Interlocking
```
  [User visits index.php]
         │
         ▼
  [partials/frontend_header.php logs "index.php" context to $_SESSION['bot_page_context']]
         │
         ▼
  [User opens widget and clicks "Speak" or selects an option]
         │
         ▼
  [AJAX post sent to api/bot-controller.php including session context]
         │
         ▼
  [Controller resolves active bot_node -> Checks Context Ads -> Formulates reply]
         │
         ▼
  [Client receives JSON response]
         ├─────────────────────────────────────────┐
         ▼                                         ▼
  [Web Speech synthesizes spoken_text]    [Client action swaps HTML viewport]
```

---

### 4. BUSINESS INTELLIGENCE, DATA EXTRACTION & LOCAL RAG PIPELINE

#### A. Local RAG Pipeline & Shell-Free PDF Ingestion
The platform utilizes a completely self-hosted, cloud-free Retrieval-Augmented Generation (RAG) system running against our local SQL database, bypassing third-party indexing fees.
- **Ingestion (`admin/import-pdf.php`)**: An admin uploads corporate or governmental policy PDFs. The system utilizes a shell-free, pure-PHP native PDF text extractor to segment the document page-by-page.
- **Database Storage**: The segmented text block is inserted page-by-page into the `local_knowledge_base` table using parameterized prepared statements.
- **Context Search**: When a user asks a custom conversational question, `api/bot-controller.php` executes a secure parameterized query using a standard MySQL `MATCH(text_content) AGAINST(?)` full-text search against the `local_knowledge_base` table, feeding the top 3 matching page segments into the context before compiling the answer.

#### B. Fail-Closed Unmapped Question Logging
To continuously improve the local knowledge base, a fail-closed logging hook is implemented.
- **Trigger**: When a custom conversational query is made and the MySQL full-text search returns zero matching results, the system logs this into the `bot_failed_questions` table.
- **Metadata Captured**: Records the `session_id`, `unanswered_question` text, `page_context_url`, and `created_at` timestamp.
- **Admin Review Panel (`admin/crm/failed-questions.php`)**: Admins can inspect these failed questions in chronological order, allowing them to upload relevant PDFs or manually map custom answers.

#### C. Advanced Deduction Engine
Commission rates for providers are controlled dynamically via the platform's multi-dimensional deduction system.
- **Super Admin Configurations (`admin/settings/deductions.php`)**: Super Admins can configure individual provider contract rates:
  - `deduction_type`: `'percentage'` or `'flat'`
  - `deduction_value`: Decimal commission amount
- **Vendor Read-Only Panel (`vendor/commission.php`)**: Vendors can inspect their current commission rates in a read-only panel, maintaining transparent operational contract terms.

#### D. Multidimensional Split Accounting Dashboards
The platform aggregates real-time financials and processes incoming transactions into split records representing:
- `gross_amount`: Total payment collected.
- `platform_fee`: Commission retained based on deduction configurations.
- `vendor_net_amount`: Balance payable to the vendor.

Accounting dashboards query the `payment_transactions` and related customer records to visualize cumulative metrics over a Chart.js JSON feed across **five distinct axes**:
1. **Overall Platform performance**: Aggregate totals.
2. **Vendor-wise metrics**: Providers can restrictedly view only their self-metrics, while Super Admins can view all.
3. **Service-wise performance**: Tracking top-earning service offerings.
4. **Category-wise metrics**: Performance broken down by consultancy categories.
5. **Date-wise chronological performance**: Custom date ranges.

---

### 5. UNIVERSAL SITE-WIDE MONETIZATION & AD PLACEMENT ENGINE

To create a robust, built-in monetization stream, the platform features a highly optimized ad placement engine operating globally.

#### A. Universal Layout Placements (`lib/monetization_helper.php`)
Ad spots are designated at key visual areas using layout helper tags:
- **`site_header_leaderboard`**: Renders a top horizontal banner inside `partials/frontend_header.php`.
- **`site_sidebar_banner`**: Renders inside context sidebars.
- **`site_footer_banner`**: Renders a bottom horizontal banner inside `partials/frontend_footer.php`.
- **`bot_internal_chat`**: Intercepted in `api/bot-controller.php` and injected as a conversational link.

#### B. Ad Matching & Specificity Cascade
The helper function `render_layout_ad_placement($zone_name)` resolves placement requests:
1. It reads the active script filename and category contexts from the session.
2. It queries `bot_ads` for direct sponsor campaigns matching the current user language (`language_iso`) and placement zone.
3. Specificity sorting prioritizes exact matches on `target_page_context = [active_page]` before falling back to `target_page_context = 'global_fallback'`.

#### C. Automated Programmatic Script Fallback
If no direct sponsor matches the context or if the active campaigns have depleted their budgets, the matching engine automatically falls back to programmatic script delivery. It queries `bot_ads` for `ad_source_type = 'network_programmatic'` and outputs the raw `network_script_code` (e.g. Google AdSense asynchronous script tags), ensuring ad slots are never empty.

#### D. Campaign Variables & Budget Management
The engine accommodates three billing configurations:
1. **Pay-Per-Click (PPC)**: Ads are charged based on user interactions. Every valid click logged via `api/bot-ad-tracker.php` deducts `click_cost` from `max_budget` atomically by incrementing `current_spend`. Once `current_spend >= max_budget`, the campaign is marked inactive.
2. **Pay-Per-Impression (PPI)**: Ads track views. Every placement match increments `current_impressions`. Once `current_impressions >= max_impressions`, the ad is disabled.
3. **Chronological Boundary / Temporal Override (`flat_rate_temporal`)**:
   - When set to `'flat_rate_temporal'`, the standard budget caps (`max_budget`) and impression limits are completely bypassed.
   - The engine validates strictly chronologically: checking that the system `NOW()` time is between `start_date` and `end_date`, allowing sponsors to lease ad zones flat-rate for fixed calendar durations.

#### E. Anti-Fraud Audit Validation Architecture (`bot_ad_fraud_logs`)
The platform implements an automated anti-fraud validation table:
- **Click-Fraud rate limiting logs**: Tracks historical click frequencies dynamically per IP address and ad identifier inside `bot_ad_fraud_logs` using parameterized statements.
- **Budget Protection**: When click limits are violated, transactions are intercepted dynamically, preserving advertiser's budgets while maintaining standard redirection loops.

---

### 6. MASTER CREDENTIALS SEEDING & API KEYS DIRECTORY

To transition the platform to a live environment, production credentials must be added directly into specific files. Below is the safe, production-grade directory of variables:

| Credential Name | File Path | Configuration Location / Constant Definition | Mock Safe Fallback Behavior |
| :--- | :--- | :--- | :--- |
| **OpenAI API Key** | `api/bot-controller.php` | Line 15 (approx) - `define('OPENAI_API_KEY', '...')` | If placeholder, falls back to static tree menu options without crashing page. |
| **OpenAI Assistant ID**| `api/bot-controller.php` | Line 16 (approx) - `define('OPENAI_ASSISTANT_ID', '...')` | Gracefully falls back to localized mock bot response. |
| **ElevenLabs Voice Token**| `templates/bot-widget.php`| Client-Side TTS Configuration / Web Speech | Fallback automatically defaults to native window HTML5 Web Speech Synthesis API. |
| **reCAPTCHA Site Key** | `lib/anti_spam_helper.php`| Line 6 (approx) - `define('RECAPTCHA_SITE_KEY', '...')` | Fallback displays standard captcha elements with passive automated submission success. |
| **reCAPTCHA Secret Key**| `lib/anti_spam_helper.php`| Line 9 (approx) - `define('RECAPTCHA_SECRET_KEY', '...')` | Graceful bypass if set to development standard `'YOUR_SECRET_KEY'`. |
| **Stripe Webhook Secret**| `api/payment-webhook.php`| Webhook Signature Verification Endpoint | Standard verification bypass implemented for development sandbox testing. |

---

### 7. ADMIN PANEL PERMISSION MATRIX & USER OPERATION GUIDE

#### A. Global Permission Matrix

| Module / Page | Permission Key | Super Admin | Provider / Vendor | Customer / Guest |
| :--- | :--- | :---: | :---: | :---: |
| **Deductions Configuration** | `settings.deductions` | Read / Write | Locked Out | Locked Out |
| **Ads Campaign Manager** | `settings.ads` | Read / Write | Locked Out | Locked Out |
| **Failed Questions Review** | `crm.failed_questions` | Read / Write | Locked Out | Locked Out |
| **Split Accounting Metrics** | `accounting.view` | View All | View Self Only | Locked Out |
| **Verification Moderation** | `providers.moderate` | Read / Write | Locked Out | Locked Out |
| **Service Builder CRUD** | `services.manage` | Read / Write | Edit Custom Only | Locked Out |
| **Team Member Manager** | `provider.team` | View Only | Read / Write | View Only (Public Profile) |
| **Interactive Voice Assistant**| Public Access | Active | Active | Active |

#### B. Step-by-Step User Operational Guide

##### 1. Super Admin (System Administrators)
*   **Step 1. Configure Global Commission Rules**: Navigate to `Admin Panel -> Settings -> Deductions`. Define flat or percentage commission metrics per vendor.
*   **Step 2. Manage Ad Campaigns**: Go to `Admin Panel -> Settings -> Monetization & Ads`. Click "Add Campaign". Select "Direct Sponsor" or "Network Programmatic". Set budget caps, billing models, targeted language, and placement page contexts.
*   **Step 3. Moderate Provider Verification**: Go to `Admin Panel -> Providers -> Verification Queue`. Audit uploaded legal registration documents and toggle "Verified Credentials Public Display" to display verified badges on vendor-profile.php.
*   **Step 4. Handle Failed Questions**: Open `Admin Panel -> CRM -> Unresolved Questions`. Inspect user conversational queries that returned zero results and upload corrective documentation.

##### 2. Provider / Vendor
*   **Step 1. Register and Complete Onboarding**: Register via `register-vendor.php`. Walk through the onboarding form to input specialties, starting price, and team sizes.
*   **Step 2. Access Team & Document Management**: Navigate to `Vendor Dashboard -> Our Team`. Add team members (which renders an "Our Team" slider on the public profile). Go to `Profile Settings` and upload trade licenses or certificates for Super Admin review.
*   **Step 3. Track Financial Earnings**: Access `Vendor Dashboard -> Commission & Accounts`. View read-only commission contracts and visual metrics tracking Gross, platform deduction, and net due amount.

##### 4. Customer / Guest
*   **Step 1. Create Account**: Register at `register.php`. Complete profile targeting (goals, target emirate, nationality).
*   **Step 2. Conversational Booking**: Click the floating "Ask AI Assistant" widget or open `bot-landing.php`. Talk or type questions. The assistant will navigate you directly to services or trigger checkout pages.
*   **Step 3. Submit Checkout & Document Upload**: Pay securely via `customer/checkout.php`. Upload required onboarding documents in `customer/documents.php` to initiate your case workflow.

---

### 8. TECHNICAL SYSTEM MANIFEST & MASTER SQL SCHEMA

#### A. Comprehensive Codebase Manifest
- `lib/db_mysqli.php` & `lib/mock_mysqli.php` - Secure database connections & mock fallbacks.
- `lib/monetization_helper.php` - Central layout ad placement router.
- `lib/anti_spam_helper.php` - Captcha configurations, rate limiting helper, and honeypot structures.
- `templates/bot-widget.php` - Multi-lingual Speech overlay assistant widget.
- `bot-landing.php` - Full-screen responsive split-screen conversational workspace.
- `api/bot-controller.php` - Core assistant response dispatch, context injector, and ad compiler.
- `api/bot-ad-tracker.php` - Redirection click tracker, duplicate filter, and atomic budget spend controller.
- `api/ad-revenue-charts.php` - Ad metric visual reporting feed.
- `api/payment-webhook.php` - Replay-resistant payment webhook handler.
- `admin/settings/bot_ads.php` - Super Admin campaign creator and interactive analytics dashboard.
- `admin/migrations/monetization_migration.php` - DB table builder.
- `admin/migrations/fraud_migration.php` - Click fraud table builder.
- `gpa_gw2.sql` - Permanent synchronized schema dump initialization script.

#### B. Master SQL DDL Schemas

```sql
-- 1. CASES
CREATE TABLE `CASES` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `UUID` CHAR(36) NOT NULL,
  `USER_ID` INT(10) UNSIGNED NOT NULL,
  `SERVICE_ID` INT(10) UNSIGNED DEFAULT NULL,
  `PROVIDER_ID` INT(10) UNSIGNED DEFAULT NULL,
  `STATUS` ENUM('PENDING','QUOTED','BOOKED','DECLINED') NOT NULL DEFAULT 'PENDING',
  `DUE_AMOUNT` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `PAID_AMOUNT` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `CREATED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `UPDATED_AT` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UUID` (`UUID`),
  KEY `USER_ID` (`USER_ID`),
  KEY `SERVICE_ID` (`SERVICE_ID`),
  KEY `PROVIDER_ID` (`PROVIDER_ID`)
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 2. PAYMENT_GATEWAYS
CREATE TABLE `PAYMENT_GATEWAYS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `GATEWAY_NAME` VARCHAR(100) NOT NULL,
  `PUBLIC_KEY` VARCHAR(255) DEFAULT NULL,
  `SECRET_KEY` VARCHAR(255) DEFAULT NULL,
  `SANDBOX_MODE` TINYINT(1) NOT NULL DEFAULT 1,
  `IS_ENABLED` TINYINT(1) NOT NULL DEFAULT 1,
  `UPDATED_AT` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `GATEWAY_NAME` (`GATEWAY_NAME`)
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 3. NOTIFICATIONS
CREATE TABLE `NOTIFICATIONS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `USER_ID` INT(10) UNSIGNED NOT NULL,
  `TITLE` VARCHAR(255) NOT NULL,
  `MESSAGE` TEXT NOT NULL,
  `IS_READ` TINYINT(1) NOT NULL DEFAULT 0,
  `TARGET_URL` VARCHAR(255) DEFAULT NULL,
  `CREATED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  KEY `USER_ID` (`USER_ID`)
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 4. BOT_NODES
CREATE TABLE `BOT_NODES` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `PARENT_ID` INT(10) UNSIGNED DEFAULT NULL,
  `NODE_TYPE` VARCHAR(50) NOT NULL,
  `LANGUAGE_ISO` VARCHAR(10) NOT NULL DEFAULT 'EN',
  `DISPLAY_TEXT` TEXT NOT NULL,
  `SPOKEN_TEXT` TEXT NOT NULL,
  `TARGET_ACTION` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PARENT_ID` (`PARENT_ID`),
  CONSTRAINT `FK_BOT_NODES_PARENT` FOREIGN KEY (`PARENT_ID`) REFERENCES `BOT_NODES` (`ID`) ON DELETE SET NULL
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 5. BOT_SESSIONS
CREATE TABLE `BOT_SESSIONS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `SESSION_TOKEN` VARCHAR(64) NOT NULL,
  `USER_ID` INT(10) UNSIGNED DEFAULT NULL,
  `SELECTED_LANGUAGE` VARCHAR(10) DEFAULT NULL,
  `CURRENT_NODE_ID` INT(10) UNSIGNED DEFAULT NULL,
  `ENTRY_POINT` VARCHAR(100) DEFAULT 'GENERAL_PAGE',
  `CREATED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `UPDATED_AT` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SESSION_TOKEN` (`SESSION_TOKEN`),
  KEY `USER_ID` (`USER_ID`),
  KEY `CURRENT_NODE_ID` (`CURRENT_NODE_ID`),
  CONSTRAINT `FK_BOT_SESSIONS_USER` FOREIGN KEY (`USER_ID`) REFERENCES `USERS` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `FK_BOT_SESSIONS_NODE` FOREIGN KEY (`CURRENT_NODE_ID`) REFERENCES `BOT_NODES` (`ID`) ON DELETE SET NULL
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 6. BOT_CHAT_LOGS
CREATE TABLE `BOT_CHAT_LOGS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `SESSION_ID` INT(10) UNSIGNED NOT NULL,
  `SENDER` ENUM('USER','BOT') NOT NULL,
  `MESSAGE_CONTENT` TEXT NOT NULL,
  `CREATED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  KEY `SESSION_ID` (`SESSION_ID`),
  CONSTRAINT `FK_BOT_CHAT_LOGS_SESSION` FOREIGN KEY (`SESSION_ID`) REFERENCES `BOT_SESSIONS` (`ID`) ON DELETE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 7. LOCAL_KNOWLEDGE_BASE
CREATE TABLE `LOCAL_KNOWLEDGE_BASE` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `FILE_NAME` VARCHAR(255) NOT NULL,
  `DOCUMENT_CATEGORY` VARCHAR(100) NOT NULL,
  `PAGE_NUMBER` INT(10) UNSIGNED NOT NULL,
  `TEXT_CONTENT` LONGTEXT NOT NULL,
  `CREATED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  FULLTEXT KEY `IDX_TEXT_CONTENT` (`TEXT_CONTENT`)
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 8. BOT_FAILED_QUESTIONS
CREATE TABLE `BOT_FAILED_QUESTIONS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `SESSION_ID` INT(10) UNSIGNED DEFAULT NULL,
  `UNANSWERED_QUESTION` TEXT NOT NULL,
  `PAGE_CONTEXT_URL` VARCHAR(255) NOT NULL,
  `CREATED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  KEY `SESSION_ID` (`SESSION_ID`),
  CONSTRAINT `FK_FAILED_QUESTIONS_SESSION` FOREIGN KEY (`SESSION_ID`) REFERENCES `BOT_SESSIONS` (`ID`) ON DELETE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 9. PAYMENT_TRANSACTIONS
CREATE TABLE `PAYMENT_TRANSACTIONS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `TRANSACTION_ID` VARCHAR(255) NOT NULL,
  `CASE_ID` INT(10) UNSIGNED DEFAULT NULL,
  `GROSS_AMOUNT` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `PLATFORM_FEE` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `VENDOR_NET_AMOUNT` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `CREATED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `TRANSACTION_ID` (`TRANSACTION_ID`),
  KEY `CASE_ID` (`CASE_ID`),
  CONSTRAINT `FK_TRANSACTIONS_CASE` FOREIGN KEY (`CASE_ID`) REFERENCES `CASES` (`ID`) ON DELETE SET NULL
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 10. BOT_ADS
CREATE TABLE `BOT_ADS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `CAMPAIGN_NAME` VARCHAR(255) NOT NULL,
  `AD_SOURCE_TYPE` ENUM('DIRECT_SPONSOR','NETWORK_PROGRAMMATIC') NOT NULL,
  `PLACEMENT_ZONE` ENUM('BOT_INTERNAL_CHAT','SITE_HEADER_LEADERBOARD','SITE_SIDEBAR_BANNER','SITE_FOOTER_BANNER') NOT NULL,
  `TARGET_PAGE_CONTEXT` VARCHAR(255) DEFAULT 'GLOBAL_FALLBACK',
  `TARGET_CATEGORY_ID` INT(10) UNSIGNED DEFAULT NULL,
  `LANGUAGE_ISO` VARCHAR(10) NOT NULL DEFAULT 'EN',
  `BANNER_TEXT` TEXT DEFAULT NULL,
  `AUDIO_SPEECH_TEXT` TEXT DEFAULT NULL,
  `DESTINATION_URL` VARCHAR(255) DEFAULT NULL,
  `NETWORK_SCRIPT_CODE` LONGTEXT DEFAULT NULL,
  `CLICK_COST` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `MAX_BUDGET` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `CURRENT_SPEND` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `AD_BILLING_MODEL` ENUM('PPC','PPI','FLAT_RATE_TEMPORAL') NOT NULL DEFAULT 'PPC',
  `MAX_IMPRESSIONS` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `CURRENT_IMPRESSIONS` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `START_DATE` DATETIME DEFAULT NULL,
  `END_DATE` DATETIME DEFAULT NULL,
  `IS_ACTIVE` TINYINT(1) NOT NULL DEFAULT 1,
  `CREATED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `UPDATED_AT` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  KEY `TARGET_CATEGORY_ID` (`TARGET_CATEGORY_ID`),
  CONSTRAINT `FK_BOT_ADS_CATEGORY` FOREIGN KEY (`TARGET_CATEGORY_ID`) REFERENCES `SERVICE_CATEGORIES` (`ID`) ON DELETE SET NULL
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 11. BOT_AD_CLICKS
CREATE TABLE `BOT_AD_CLICKS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `AD_ID` INT(10) UNSIGNED NOT NULL,
  `SESSION_ID` INT(10) UNSIGNED DEFAULT NULL,
  `EARNED_AMOUNT` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `CLICKED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  KEY `AD_ID` (`AD_ID`),
  KEY `SESSION_ID` (`SESSION_ID`),
  CONSTRAINT `FK_BOT_AD_CLICKS_AD` FOREIGN KEY (`AD_ID`) REFERENCES `BOT_ADS` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_BOT_AD_CLICKS_SESSION` FOREIGN KEY (`SESSION_ID`) REFERENCES `BOT_SESSIONS` (`ID`) ON DELETE SET NULL
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 12. PROVIDER_DOCUMENTS (BOT_UPLOADED_DOCUMENTS / CUSTOMER_DOCUMENTS)
CREATE TABLE `PROVIDER_DOCUMENTS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `UUID` CHAR(36) NOT NULL,
  `PROVIDER_ID` INT(10) UNSIGNED NOT NULL,
  `DOCUMENT_NAME` VARCHAR(255) NOT NULL,
  `FILE_PATH` VARCHAR(255) NOT NULL,
  `DOCUMENT_TYPE` VARCHAR(100) DEFAULT NULL,
  `VERIFICATION_STATUS` ENUM('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `IS_PUBLIC` TINYINT(1) NOT NULL DEFAULT 0,
  `CREATED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `UPDATED_AT` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UUID` (`UUID`),
  KEY `PROVIDER_ID` (`PROVIDER_ID`),
  CONSTRAINT `FK_PROVIDER_DOCUMENTS_PROVIDER` FOREIGN KEY (`PROVIDER_ID`) REFERENCES `PROVIDERS` (`ID`) ON DELETE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;

-- 13. BOT_AD_FRAUD_LOGS
CREATE TABLE `BOT_AD_FRAUD_LOGS` (
  `ID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `AD_ID` INT(10) UNSIGNED NOT NULL,
  `IP_ADDRESS` VARCHAR(45) NOT NULL,
  `CLICKED_AT` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`ID`),
  KEY `AD_ID` (`AD_ID`),
  CONSTRAINT `FK_BOT_AD_FRAUD_LOGS_AD` FOREIGN KEY (`AD_ID`) REFERENCES `BOT_ADS` (`ID`) ON DELETE CASCADE
) ENGINE=INNODB DEFAULT CHARSET=UTFF8MB4 COLLATE=UTF8MB4_UNICODE_CI;
```

---
