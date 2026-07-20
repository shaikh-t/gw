# GlobalWays Marketplace - Project Statement of Work (SOW) & Technical Capability Report
## Comprehensive Engineering Manual & System Architecture Blueprint

---

### 1. EXECUTIVE BRIEF & PLATFORM TAXONOMY

#### Strategic Mission
GlobalWays is an enterprise-grade, high-performance service booking, hybrid monetization, and immersive conversational marketplace. It is engineered specifically to cater to high-value global consultancy services (e.g., Golden Visa programs, corporate formations, PRO, and UAE-based immigration pathways). The platform connects clients with verified professional service providers ("vendors") while creating a self-sustaining monetization cycle through targeted layout context ads, conversational sponsored placements, and dynamic contract deductions.

Key operational pillars of the platform include:
1. **Dynamic Context-Aware Interface**: A low-latency conversational AI engine widget and a full-screen split-screen immersive workspace (`bot-landing.php`) that leverage real-time browser page contexts to adapt dialogue and guide customers cleanly through purchasing and booking workflows.
2. **Local, Air-Gapped Intelligence**: A shell-free native PDF document parsing pipeline (`admin/import-pdf.php`) that indexes regulatory guidelines into a local database schema using prepared MySQLi queries, powering Retrieval-Augmented Generation (RAG) capabilities with zero third-party lookup costs.
3. **Rigorous Defense-In-Depth Security**: Strict mitigation against OWASP Top 10 vulnerabilities, securing all transactional records and analytics behind strongly bound parameterized prepared statements, secure session bindings, and robust bot/spam-throttling layers.

#### Codebase Directory Blueprint
A comprehensive directory inventory mapping the repository structures to their respective business scopes:

*   `/admin` - **Administrative Governance Panel**: Contains the central configuration screens, client relationship managers (CRM), unresolved/failed AI question logs (`admin/crm/failed-questions.php`), provider verification moderators, and setting interfaces.
    *   `/admin/settings` - Stores modules for setting dynamic deductions, toggling global AI bot kill-switches, configuring payment gateways, and creating contextual ad campaigns.
*   `/api` - **API Dispatch Layer**: Acts as the system-wide programmatic routing engine. Dispatches real-time charts (`api/dashboard-charts.php`, `api/entry-point-charts.php`, `api/ad-revenue-charts.php`), tracks ad engagement, logs failed conversational sequences, and exposes secure endpoints.
*   `/customer` - **Client Workspace Portal**: Secured workspace facilitating direct document management (`customer/documents.php`), messages, billing ledgers, application histories (`customer/applications.php`), and payment processing pipelines.
*   `/providers` (and `/vendor`) - **Vendor Portal**: Secure dashboard that enables professionals to publish service cards, configure team member rosters (`vendor/team.php`), view flat or percentage contract deduction rates, and monitor net payables.
*   `/lib` - **Core Logic Core & Dependency Middleware**: Holds the foundation libraries. Contains database connector drivers (`lib/db_mysqli.php` and its mock development fallback `lib/mock_mysqli.php`), RBAC middleware (`lib/middleware.php`), caching drivers (`lib/cache_helper.php`), and payment gateway factories (`lib/payment_gateway_factory.php`).
*   `/partials` - **Reusable Layout Elements**: Handles global frontend hydration, rendering persistent headers (`partials/frontend_header.php`) with cryptographic CSP nonces, system theme toggles, and footers (`partials/frontend_footer.php`) that inject the voice assistant widget.
*   `/templates` - **UI Fragment Definitions**: Provides presentation logic, such as the voice assistant widget skeleton (`templates/bot-widget.php`).
*   `/tests` - **Validation Suite Directory**: Houses PHPUnit integration tests (including browser security assessments, rate limiting checks, and JIT/header confirmations) alongside Playwright E2E visual/functional suites.
*   `/var` - **Ephemeral File System Backend**: Contains transient directories, local serialised JSON cache blocks (`var/cache/`), PDF parsing queue folders (`var/scan_queue/`), and the stateful filesystem database JSON file (`var/mock_db.json`) used by the mock mysqli subsystem.

---

### 2. CORE APPLICATION ENGINE & SPECIFIC MODULE REGISTRY

#### A. Public Frontend Layer
Operating behind files like `index.php`, `vendors.php`, `vendor-profile.php`, `services.php`, and `service-detail.php`, the public frontend uses optimal query and assets patterns to maximize load performance and protect user interactions:

1. **Asset Lazy-Loading**: CSS dependencies are systematically loaded with non-blocking configurations, and high-resolution assets leverage native `loading="lazy"` attributes to prevent render blocking of critical Above-The-Fold elements.
2. **Dynamic Link Pre-fetching**: Public directory list pages (like `services.php`) contain JavaScript listeners that capture mouse hovering actions (`mouseenter`) over card elements. Upon detection, a `<link rel="prefetch">` tag is injected dynamically into the `<head>` of the browser for target detail views (e.g. `service-detail.php?id=golden-visa`), boosting navigation responsiveness.
3. **Compound MySQL Query Caching Lifecycle**: Read-heavy queries (e.g., retrieving verified vendors on `vendors.php` or listing published services in `services.php`) are wrapped in our environment-adaptive `CacheUtility` caching loops. Results are serialised and cached for **3600 seconds (1 hour)**, completely bypassing the physical database on subsequent requests.
4. **Aggregate De-normalization Columns Bypass**: Rather than calling compute-heavy `AVG()` and `COUNT()` SQL aggregates on critical page loads (which can trigger slow table-scans as reviews scale), average scores and total counts are de-normalized directly into `rating_avg` and `reviews_count` columns inside the `providers` table and updated only upon administrative review approval.
5. **Output String Mitigation & XSS Defenses**: To prevent Cross-Site Scripting (XSS), all dynamic database outputs displayed on frontend layouts are passed through `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`. No raw database echoes are permitted.
6. **Polymorphic UUID / Alphanumeric Prepared Statement Handlers**: Search queries on `vendors.php` and database fetches on `service-detail.php` utilize strictly bound prepared statement parameters. In `service-detail.php`, the query first accepts an input identifier, determines if it is a 36-character hexadecimal UUID or an alphanumeric slug using regular expressions, and binds it safely as a string parameter `'s'`:
   ```php
   $is_uuid = preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $raw_id);
   $query = $is_uuid ? "SELECT * FROM services WHERE uuid = ?" : "SELECT * FROM services WHERE slug = ?";
   ```
7. **Explicit Integer Sanitization**: Query parameters representing categories or page limits are aggressively type-cast or sanitized using `intval($_GET['cat_id'])` prior to query assembly.
8. **AJAX-driven Infinite Scrolling via Native IntersectionObserver**: In long-form vendor lists (`vendors.php`), sequential pagination is replaced with clean asynchronous infinite loading. A sentinel HTML element is watched by a native JavaScript `IntersectionObserver` instance; when it enters the viewport, it triggers a fetch request to an offset-based API chunk, loading the next set of vendor rows seamlessly.
9. **Image Boundary Processing Hooks (500x500px)**: Profile avatars and logo uploads processed under `/lib/upload.php` go through strict server-side processing checks. Uploaded images are passed to native PHP image modifiers (GD / Imagick), cropped, and resized to an absolute boundary of **500x500px** to limit disk storage footprints and enforce layout uniformity across dashboards.

#### B. Conversational AI Workspace
Running behind `bot-landing.php` and `api/bot-controller.php`, the conversational AI concierge merges continuous audio visualization with real-time text-to-speech feedback:

1. **HTML5 AudioContext/AnalyserNode Frequency Streams**: Inside `bot-landing.php`, recording interactions initiate an `AudioContext` and create an `AnalyserNode` frequency analyzer. It captures raw mic inputs via `navigator.mediaDevices.getUserMedia()`, processes frequency byte data using `analyser.getByteFrequencyData()`, and dynamically updates individual inline SVG path elements. The indicator transforms from static dots into a rippling waveform in real-time, corresponding to the speaker's vocal pitch and volume:
   ```javascript
   let audioCtx = new AudioContext();
   let analyser = audioCtx.createAnalyser();
   analyser.fftSize = 32;
   // ...
   analyser.getByteFrequencyData(dataArray);
   for (let i = 0; i < bars.length; i++) {
       const percent = dataArray[i] / 255;
       bars[i].setAttribute('height', 4 + (percent * 22));
   }
   ```
2. **Browser LocalStorage Caching**: Active chat transcripts, messages, and state metrics are saved in the client's `localStorage` (`globalways_chat_stream_html`, `globalways_bot_session`). When a user refreshes their tab or navigates across the site, the assistant viewport instantly hydrates from local storage to minimize sequential API lookups.
3. **Local Routing SSRF Guards**: As user choices trigger dynamic viewport swaps, the client-side JavaScript router executes strict Server-Side Request Forgery (SSRF) filters. The target action URLs are checked to ensure they resolve strictly to local path routes within the current host origin (rejecting any URLs containing remote schemes or cross-domain parameters like `://`) before performing dynamic AJAX fetches.
4. **SSE Tokenized Streams & Legacy JSON Fallbacks**: `api/bot-controller.php` evaluates incoming requests for an `Accept: text/event-stream` header. If present, it switches from standard JSON payloads to a chunked Server-Sent Events (SSE) connection, streaming response tokens character-by-character to the screen. If SSE is not supported, the controller falls back cleanly to generating a static single-packet JSON response.
5. **Control-Character Filtration**: Before compiling or logging raw user message inputs, they are parsed through a regex-based control character filter to remove terminal escape sequences and non-printable elements (`[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x1B]`), defending terminal logs against log injection attacks.

#### C. Gateway Protocol Suite
The gateway layer manages authentication and transactions via `register.php`, `register-vendor.php`, `login.php`, `login_post.php`, `customer/checkout.php`, and `api/payment-webhook.php`:

1. **Multi-Layer Bot & Anti-Spam Protections**:
   - **Invisible Honeypot**: Forms include an input named `website_url_verification` concealed from humans using absolute CSS `display:none; opacity:0; pointer-events:none;`. `lib/anti_spam_helper.php` checks this parameter; if populated, the submission is silently blackholed.
   - **Sliding-Window Registration Throttling**: Every user signup tracks the client's IP in the `registration_attempts` database table. A sliding window check blocks signups if the IP exceeds **3 attempts in 5 minutes**. Older logs are purged using automatic scheduled SQL deletions on incoming requests.
   - **Disposable Email and Name Validation**: Validates submitted email domains against a cached blocklist of disposable email generators and scans string variables for URLs to drop spam.
   - **Invisible Google reCAPTCHA v3**: Generates frontend action tokens validated via Curl backends with a secure development environment bypass.
2. **Cryptographic Remember Me Session Tracking**: Logins utilize cryptographic security tokens stored in a secure client-side cookie. The cookie token is verified on session boot against a SHA-256 hashed value in the database, re-establishing sessions securely.
3. **Subnet / User-Agent Session Binding**: Standard session setups are reinforced in `lib/auth.php`. Upon session creation, the user's `HTTP_USER_AGENT` and a masked subnet mask representing their IP range are encrypted and stored in `$_SESSION['auth_fingerprint']`. If any drift is detected on subsequent page loads, the session context is immediately terminated.
4. **Tokenized Stripe Elements Pipelines**: During invoice checkout (`customer/checkout.php`), credit card inputs leverage Stripe Elements JavaScript SDKs. Cardholder data is tokenized directly on Stripe's PCI-DSS compliant infrastructure, and only the secure token payload (`payment_method_id` or `payment_intent_id`) is submitted to our server, ensuring raw credit card details never touch server memory, PHP sessions, or persistent logs.
5. **Replay-Resistant Stripe Webhook Endpoint**: The public endpoint `api/payment-webhook.php` is protected against injection, transaction spoofing, and signature forgery:
   - **Signature Verification**: Verifies the webhook signature using the `HTTP_STRIPE_SIGNATURE` header against `STRIPE_WEBHOOK_SECRET` with strict cryptographic clock-drift tolerance of **5 minutes (300s)** to block replay attempts.
   - **Transaction De-duplication**: The database is queried before fulfillment to confirm the unique transaction ID has not already been processed.
   - **Status Validation**: Explicitly verifies status tags (e.g., `payment_intent.succeeded`) before updating the corresponding case status to 'Booked' in our transactions database.

---

### 3. PERSISTENT WORKSPACE CONTROL PANELS

#### A. Panel Isolation
To maintain real-time operational accuracy inside protected administration and customer panels, sequential caching must be disabled.
- All requests originating from or targeted to directories `/admin/`, `/customer/`, `/providers/`, or `/vendor/` programmatically bypass all cache retrieval and cache write routines inside `lib/cache_helper.php` (`CacheUtility::should_bypass_cache()`).
- This guarantees that dashboard metrics, transaction tables, case status rows, and messaging feeds are rendered directly from active, live database lookups.

#### B. Administrative Cache Clearance & RBAC
Super Admins can trigger a full application cache purge inside the administrative panel dashboard:
- **Authorization Verification**: The clearing action `admin/clear_cache_action.php` enforces strict Role-Based Access Control (RBAC). It evaluates the authenticated user's permissions and requires the explicit permission key `cache.clear` (mapped natively to 'admin' and 'Super Admin' roles inside the permissions database).
- **CSRF Token Validation**: Post submissions require a valid, session-matched cryptographically secure CSRF token, throwing an immediate HTTP 403 Forbidden on mismatch.
- **Cache Purge Sequence**: The utility executes a system-wide clearing sequence across:
  1. *APCu memory cache*: Executes `apcu_clear_cache()`.
  2. *Redis in-memory store*: Opens a Redis connection and invokes `$redis->flushAll()`.
  3. *File system serialization*: Recursively scans `/var/cache/` using PHP's `RecursiveIteratorIterator` and deletes all compiled `.cache` files on disk.

---

### 4. PLATFORM INFRASTRUCTURE & MULTI-LAYER SECURITY FRAMEWORK

#### A. Multi-Tier Connectivity Hierarchy
The platform's database integration is managed via `lib/db_mysqli.php` and its mock fallback companion `lib/mock_mysqli.php` to handle high-availability and failover scenario contexts:

```
                  [App Query Request]
                           │
                           ▼
              [Attempt Persistent Connection]
                 Host: "p:127.0.0.1" (port 3306)
                           │
             ┌─────────────┴─────────────┐
          Success                     Failure
             │                           │
             ▼                           ▼
    [Persistent Driver]       [Attempt Standard Handshake]
                               Host: "127.0.0.1" (port 3306)
                                         │
                           ┌─────────────┴─────────────┐
                        Success                     Failure
                           │                           │
                           ▼                           ▼
                  [Standard Driver]         [Load mock_mysqli.php]
                                            Uses var/mock_db.json
```

1. **Persistent Connection (Primary)**: The system attempts a highly optimized connection using the persistent `'p:'` host prefix prefix (e.g., `'p:' . DB_HOST`). This keeps database socket connections open across separate script executions, avoiding handshake overhead.
2. **Standard Handshake (First Fallback)**: If the persistent handshake throws an exception (due to connection limit caps or host configuration rules), the driver intercepts the error and falls back to opening a standard, non-persistent `new mysqli()` instance.
3. **Mock File Database Layer (Second Fallback)**: If both physical connection attempts fail (database offline or network connectivity drop), the system automatically loads `lib/mock_mysqli.php` and instantiates a stateful `MockMySQLi` fallback instance. This mock layer reads, queries, and writes database updates directly to a serialized local JSON file (`var/mock_db.json`), allowing the application layout to render completely crash-free during emergency database offline states.

#### B. Environment-Adaptive Caching Stack
The central cache configuration in `lib/cache_helper.php` automatically adapts to its surrounding runtime environment:
- **In-Memory Production Caches**: In production environments, the system evaluates if the APCu PHP module is enabled (`function_exists('apcu_fetch') && ini_get('apcu.enabled')`). If available, data is stored directly in RAM for microsecond retrieval.
- **Redis Cache**: Purge and write methods check for a local Redis extension, automatically routing keys to a Redis server if present.
- **Disk File Fallback**: In development or CI testing environments (where APCu and Redis are typically inactive), the system falls back to disk serialization. Data is converted into structured JSON formats containing an explicit UNIX `expires` timestamp and written to individual files named with a MD5-hash of the cache key under `/var/cache/[md5_key].cache`. Expired files are deleted on read attempts.

#### C. Server-Level Directives
Core runtime behaviors are enforced through physical environment configurations:

1. **PHP Runtime Directives (`php.ini`)**:
   - `opcache.enable=1` & `opcache.enable_cli=1`: Enables high-speed OPcache bytecode compilation.
   - `opcache.jit_buffer_size=100M` & `opcache.jit=tracing`: Activates PHP's JIT compilation using tracing mode, dynamically translating hot spot PHP pathways directly into machine native code.
2. **Web Server Rules (`.htaccess`)**:
   - **Gzip/Deflate Compression**: Enforces mod_deflate rules to compress plain texts, styles, XML, and script assets (`AddOutputFilterByType DEFLATE`).
   - **Cache-Control Headers**: Assets (e.g., JS, CSS, PNG, SVG, WOFF2) are assigned a long-lived cache header `max-age=31536000, public` to eliminate duplicate browser requests.
   - **HTTP Strict Transport Security (HSTS)**: Delivers a strict header `Strict-Transport-Security: max-age=63072000; includeSubDomains; preload` forcing transport over HTTPS.
3. **Content Security Policy (CSP)**: Delivered dynamically on every request via `partials/frontend_header.php`, the CSP prevents unauthorized code executions:
   - **Cryptographic Nonces**: Inline script blocks are authorized strictly using unique single-request nonces (`nonce-[base64_encoded]`), which are regenerated dynamically on every page load.
   - **Allowed Origins**: Script and style origins are locked down to trusted domains:
     ```http
     Content-Security-Policy: default-src 'self'; script-src 'self' https://google.com https://*.jsdelivr.net 'nonce-[nonce_val]'; style-src 'self' 'unsafe-inline' https://*.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://*.jsdelivr.net https://fonts.googleapis.com https://*.gstatic.com; img-src 'self' data:; connect-src 'self' https://*.jsdelivr.net; frame-src https://google.com;
     ```

---

### 5. INTEGRATED AUTOMATED TESTING PROFILE

#### Automated Validation Map
A comprehensive array of test frameworks are maintained under the `/tests` folder to ensure continuous delivery and prevent codebase regressions:

*   **`tests/InfrastructureTest.php`** - **Infrastructural Sanity Checker**: This PHP test script is executed directly to confirm that the server environment rules align with our security policies. It validates that:
    - JIT and OPcache settings are active in `php.ini`.
    - `.htaccess` contains compression, cache control, and security header directives.
    - `partials/frontend_header.php` explicitly delivers security headers (CSP and HSTS) and validates database driver connections.
*   **`tests/SessionHijackTest.php`** - Asserts that session identifiers are cryptographically bound to the user's browser User-Agent and subnet IP range, terminating immediately upon alteration.
*   **`tests/WebhookSignatureTest.php`** - Validates signature validations inside the Stripe checkout webhook, confirming it blocks spoofed transaction attempts.
*   **`tests/TerminalInjectionTest.php`** - Tests the terminal control-character filtration system to guarantee input fields are stripped of injection strings.
*   **`tests/PasswordStrengthTest.php`** - Enforces complexity validators for registering users, rejecting weak dictionary words.
*   **`tests/CacheTest.php`** - Verifies APCu, Redis, and disk fallback serialization caches, confirming write, read, and delete processes function as expected.
*   **`tests/HomepageCacheTest.php`** & **`tests/VendorReviewCacheTest.php`** - Confirms that read queries leverage cache configurations while dashboard interfaces bypass them.
*   **`tests/globalways_uat.spec.js`** - **Playwright E2E Suite**: Comprehensive visual regression and functional E2E tests executing a single-worker run targeting local development ports. It verifies:
    - Customer login, profile modification, and payment pipelines.
    - CSRF blocks, direct POST restrictions, and guest page access redirects.
    - Split-screen workspace hydration on `bot-landing.php`.
*   **`tests/admin-cache.spec.js`** - **RBAC & Admin E2E Validation**: Evaluates permissions and confirms that guest visits to administration paths are denied with a 403 Forbidden code.
*   **`tests/test-login-helper.php`** - **Test Credentials Seed**: A secure testing utility that injects mock roles and permissions into the PHP session array (`$_SESSION['mock_roles']`, `$_SESSION['mock_permissions']`) to run automated RBAC assertions without exposing live production accounts.

---
*End of Statement of Work (SOW)*
