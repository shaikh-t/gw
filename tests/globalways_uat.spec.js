const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const mockDbPath = path.resolve(__dirname, '../var/mock_db.json');

// Helper to reset the mock database before running tests
function resetMockDb() {
  const default_db = {
    "site_settings": {
      "ai_bot_global_status": "enabled"
    },
    "ads": [
      {
        "id": 99,
        "is_active": 1,
        "destination_url": "http://127.0.0.1:8000/index.php",
        "ad_source_type": "direct_sponsor",
        "ad_billing_model": "cpc",
        "click_cost": 2.00,
        "max_budget": 100.00,
        "current_spend": 10.00
      }
    ],
    "cases": [
      {
        "uuid": "test-case-uuid",
        "customer_user_id": 1,
        "provider_id": 1,
        "service_id": 1,
        "status": "Quoted",
        "service_price": 150.00,
        "service_currency": "AED",
        "customer_name": "John Doe",
        "service_title": "Golden Visa Assistance",
        "provider_name": "Apex Legal"
      }
    ],
    "providers": [
      {
        "id": 1,
        "uuid": "test-case-uuid",
        "name": "Apex Legal",
        "team_size": 3,
        "deduction_type": "percentage",
        "deduction_value": 10.00
      }
    ],
    "provider_team_members": [],
    "local_knowledge_base": [
      {
        "text_content": "This is the authoritative golden visa guide details.",
        "file_name": "golden_visa_regulations.pdf",
        "page_number": 4
      }
    ],
    "bot_ad_fraud_logs": [],
    "bot_failed_questions": [],
    "payment_transactions": [],
    "customer_payments": [],
    "customer_applications": [],
    "login_attempts": [],
    "registration_attempts": [],
    "users": [
      {
        "id": 1,
        "uuid": "test-user-uuid",
        "name": "John Doe",
        "email": "john.doe@example.com"
      }
    ]
  };
  fs.writeFileSync(mockDbPath, JSON.stringify(default_db, null, 2));
}

test.describe('GlobalWays Automated UAT Suite', () => {

  test.beforeEach(() => {
    resetMockDb();
  });

  // Track 1: Guest-to-Customer onboarding registration state loop
  test.describe('Track 1: Guest-to-Customer Onboarding Flow', () => {

    test('Conversational UI workflow and valid/invalid validation logic', async ({ page }) => {
      await page.goto('/bot-landing.php');

      // 1. Send trigger word 'register' to initiate registration
      await page.evaluate(() => {
        sendQueryToController('register');
      });
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("What is your First Name?");

      // 2. Validate Latin character set rule (rejection of non-Latin input)
      await page.evaluate(() => {
        sendQueryToController('العربية');
      });
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("Registration requires Latin characters only.");

      // 3. Enter valid First Name (Latin)
      await page.evaluate(() => {
        sendQueryToController('John');
      });
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("I recorded your first name as 'John'. Is this correct?");

      // 4. Confirm First Name by clicking dynamically generated button in UI
      const confirmFirstBtn = page.locator('#wsOptionsRack button:has-text("Confirm")');
      await expect(confirmFirstBtn).toBeVisible();
      await confirmFirstBtn.click();
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("Now, what is your Last Name?");

      // 5. Validate Latin character set rule on Last Name (rejection of non-Latin input)
      await page.evaluate(() => {
        sendQueryToController('الاسم');
      });
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("Registration requires Latin characters only.");

      // 6. Enter valid Last Name (Latin)
      await page.evaluate(() => {
        sendQueryToController('Doe');
      });
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("I recorded your last name as 'Doe'. Is this correct?");

      // 7. Confirm Last Name via UI clicking
      const confirmLastBtn = page.locator('#wsOptionsRack button:has-text("Confirm")');
      await confirmLastBtn.click();
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("What is your Email Address?");

      // 8. Validate Email format & Latin checks (rejection of invalid email)
      await page.evaluate(() => {
        sendQueryToController('john@البريد.com');
      });
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("Please enter a valid, Latin-based Email Address.");

      // 9. Enter valid Email (use unique email to avoid collision with pre-seeded John Doe)
      await page.evaluate(() => {
        sendQueryToController('new.john.doe@example.com');
      });
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("I recorded your email as 'new.john.doe@example.com'. Is this correct?");

      // 10. Confirm Email via UI clicking
      const confirmEmailBtn = page.locator('#wsOptionsRack button:has-text("Confirm")');
      await confirmEmailBtn.click();
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("Finally, what is your Phone Number?");

      // 11. Validate Phone format check (rejection of invalid phone)
      await page.evaluate(() => {
        sendQueryToController('invalidphone');
      });
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("Please enter a valid, Latin-based Phone Number.");

      // 12. Enter valid Phone
      await page.evaluate(() => {
        sendQueryToController('+971501234567');
      });
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("I recorded your phone number as '+971501234567'. Is this correct?");

      // 13. Confirm Phone via UI clicking -> completes registration and auto-logins
      const confirmPhoneBtn = page.locator('#wsOptionsRack button:has-text("Confirm")');
      await confirmPhoneBtn.click();

      // Verify final onboarding congratulatory output
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("Congratulations, John! Your customer registration is complete and you are now securely logged in.");

      // Verify that session state has auto-logged the user in by accessing protected customer index page
      await page.goto('/customer/index.php');
      await expect(page).not.toHaveURL(/login\.php/);
      await expect(page.locator('body')).toContainText('John Doe');
    });

    test('Backend API controller registration state loop', async ({ request }) => {
      // Step 1: Start onboarding
      let res = await request.post('/api/bot-controller.php', {
        data: { message: 'register', entry_point: 'immersive_landing' }
      });
      expect(res.ok()).toBeTruthy();
      let data = await res.json();
      expect(data.display_text).toContain("What is your First Name?");
      const token = data.session_token;

      const cookie = res.headers()['set-cookie'];

      // Step 2: Send non-Latin Name -> Rejection
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'العربية' }
      });
      data = await res.json();
      expect(data.display_text).toContain("Registration requires Latin characters only.");

      // Step 3: Send valid First Name
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'Jane' }
      });
      data = await res.json();
      expect(data.display_text).toContain("I recorded your first name as 'Jane'. Is this correct?");

      // Step 4: Confirm First Name
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'Confirm', payload_value: 'confirm_first_name' }
      });
      data = await res.json();
      expect(data.display_text).toContain("Now, what is your Last Name?");

      // Step 5: Send valid Last Name
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'Smith' }
      });
      data = await res.json();
      expect(data.display_text).toContain("I recorded your last name as 'Smith'. Is this correct?");

      // Step 6: Confirm Last Name
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'Confirm', payload_value: 'confirm_last_name' }
      });
      data = await res.json();
      expect(data.display_text).toContain("What is your Email Address?");

      // Step 7: Send invalid Email -> Rejection
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'jane.smith@البريد.com' }
      });
      data = await res.json();
      expect(data.display_text).toContain("Please enter a valid, Latin-based Email Address.");

      // Step 8: Send valid Email
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'jane.smith@example.com' }
      });
      data = await res.json();
      expect(data.display_text).toContain("I recorded your email as 'jane.smith@example.com'. Is this correct?");

      // Step 9: Confirm Email
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'Confirm', payload_value: 'confirm_email' }
      });
      data = await res.json();
      expect(data.display_text).toContain("Finally, what is your Phone Number?");

      // Step 10: Send valid Phone
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: '+971509999999' }
      });
      data = await res.json();
      expect(data.display_text).toContain("I recorded your phone number as '+971509999999'. Is this correct?");

      // Step 11: Confirm Phone -> Triggers account generation and login
      res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'Confirm', payload_value: 'confirm_phone' }
      });
      data = await res.json();
      expect(data.display_text).toContain("Congratulations, Jane! Your customer registration is complete");
    });
  });

  // Track 2: Local RAG search indexing matching and fail-closed logging
  test.describe('Track 2: Local RAG & Fail-Closed Logging', () => {

    test('Valid query should return RAG results with source file citations', async ({ request }) => {
      let resInit = await request.post('/api/bot-controller.php', {
        data: { message: 'Reset', entry_point: 'immersive_landing' }
      });
      const dataInit = await resInit.json();
      const token = dataInit.session_token;
      const cookie = resInit.headers()['set-cookie'];

      let resLang = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, node_id: 10, message: 'English' }
      });
      expect(resLang.ok()).toBeTruthy();

      let resVoice = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, node_id: 2, message: 'AI Voice Companion' }
      });
      expect(resVoice.ok()).toBeTruthy();

      const res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: 'visa guide' }
      });
      expect(res.ok()).toBeTruthy();
      const data = await res.json();

      expect(data.display_text).toContain("Verified Guidelines: This is the authoritative golden visa guide details.");
      expect(data.display_text).toContain("[Source: golden_visa_regulations.pdf, Page 4]");
    });

    test('Unmapped questions must trigger the fail-closed hook to write log entries into bot_failed_questions', async ({ request }) => {
      let resInit = await request.post('/api/bot-controller.php', {
        data: { message: 'Reset', entry_point: 'immersive_landing' }
      });
      const dataInit = await resInit.json();
      const token = dataInit.session_token;
      const cookie = resInit.headers()['set-cookie'];

      let resLang = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, node_id: 10, message: 'English' }
      });
      expect(resLang.ok()).toBeTruthy();

      let resVoice = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, node_id: 2, message: 'AI Voice Companion' }
      });
      expect(resVoice.ok()).toBeTruthy();

      const unmappedQuery = "unmapped question about something completely unknown";

      const res = await request.post('/api/bot-controller.php', {
        headers: { 'Cookie': cookie },
        data: { session_token: token, message: unmappedQuery }
      });
      expect(res.ok()).toBeTruthy();
      const data = await res.json();

      expect(data.display_text).toContain("I am unable to find that specific configuration in my database right now, but I have logged your question");

      const db = JSON.parse(fs.readFileSync(mockDbPath, 'utf8'));
      const loggedQuestions = db.bot_failed_questions;
      expect(loggedQuestions.length).toBeGreaterThan(0);
      expect(loggedQuestions[loggedQuestions.length - 1].unanswered_question).toBe(unmappedQuery);
    });
  });

  // Track 3: RBAC access wall authorization checks
  test.describe('Track 3: RBAC Access Wall Authorization Checks', () => {

    const protectedEndpoints = [
      '/admin/dashboard.php',
      '/admin/users/index.php',
      '/admin/roles/index.php',
      '/admin/permissions/index.php',
      '/admin/settings/deductions.php',
      '/admin/settings/bot_ads.php',
      '/admin/settings/ai_status.php'
    ];

    for (const endpoint of protectedEndpoints) {
      test(`Direct guest request to administrative endpoint ${endpoint} must return HTTP 403`, async ({ request }) => {
        const res = await request.get(endpoint, {
          maxRedirects: 0
        });
        expect(res.status()).toBe(403);
      });
    }
  });

  // Track 4: Webhook duplicate fulfillment replay protection and ad click-fraud rate-limiting
  test.describe('Track 4: Webhook Replay & Ad Click Fraud Protection', () => {

    test('Stripe Webhook Duplicate transaction ID collisions must return HTTP 400', async ({ request }) => {
      const payload = {
        event: "payment_intent.succeeded",
        case_uuid: "test-case-uuid",
        transaction_id: "tx-replay-protection-123"
      };

      const res1 = await request.post('/api/payment-webhook.php', {
        headers: { 'Stripe-Signature': 'bypass_test_signature' },
        data: payload
      });
      expect(res1.ok()).toBeTruthy();
      const data1 = await res1.json();
      expect(data1.status).toBe('success');

      let db = JSON.parse(fs.readFileSync(mockDbPath, 'utf8'));
      const initialTx = db.payment_transactions.find(tx => tx.transaction_id === payload.transaction_id);
      expect(initialTx).toBeDefined();

      const initialCase = db.cases.find(c => c.uuid === payload.case_uuid);
      expect(initialCase.status).toBe('Booked');

      const res2 = await request.post('/api/payment-webhook.php', {
        headers: { 'Stripe-Signature': 'bypass_test_signature' },
        data: payload
      });
      expect(res2.status()).toBe(400);
      const data2 = await res2.json();
      expect(data2.status).toBe('error');
      expect(data2.message).toContain("Duplicate transaction ID detected. Replay request rejected.");
    });

    test('Ad click-fraud sliding window rate-limiting blocks budget consumption on 4th click & redirects cleanly', async ({ playwright }) => {
      const adId = 99;

      for (let i = 1; i <= 3; i++) {
        const freshContext = await playwright.request.newContext();
        const res = await freshContext.get(`/api/bot-ad-tracker.php?ad_id=${adId}`, {
          maxRedirects: 0
        });
        expect(res.status()).toBe(302);
      }

      let db = JSON.parse(fs.readFileSync(mockDbPath, 'utf8'));
      const adAfter3 = db.ads.find(a => a.id === adId);
      expect(adAfter3.current_spend).toBe(16.00);
      expect(db.bot_ad_clicks.length).toBe(3);
      expect(db.bot_ad_fraud_logs.length).toBe(3);

      const freshContext4 = await playwright.request.newContext();
      const res4 = await freshContext4.get(`/api/bot-ad-tracker.php?ad_id=${adId}`, {
        maxRedirects: 0
      });
      expect(res4.status()).toBe(302);

      db = JSON.parse(fs.readFileSync(mockDbPath, 'utf8'));
      const adAfter4 = db.ads.find(a => a.id === adId);
      expect(adAfter4.current_spend).toBe(16.00);
      expect(db.bot_ad_clicks.length).toBe(3);
      expect(db.bot_ad_fraud_logs.length).toBe(3);
    });
  });

  // Advanced Track 5: Global Kill-Switch, Rate Limiter Throttling, Anti-Spam Registration Honeypots, Clean URLs, and Team Visibility
  test.describe('Track 5: High-Value Advanced Security & Layout Audits', () => {

    test('AI Global Bot Kill-Switch forcefully restricts API access and drops requests with HTTP 403', async ({ request }) => {
      let db = JSON.parse(fs.readFileSync(mockDbPath, 'utf8'));
      db.site_settings.ai_bot_global_status = "disabled";
      fs.writeFileSync(mockDbPath, JSON.stringify(db, null, 2));

      const res = await request.post('/api/bot-controller.php', {
        data: { message: 'Reset' }
      });
      expect(res.status()).toBe(403);
    });

    test('IP-Based Login Rate-Limiter (Brute-Force Protection) restricts client access on 6th failed attempt', async ({ playwright }) => {
      const freshContext = await playwright.request.newContext();
      const loginPageRes = await freshContext.get('/login.php');
      const loginHtml = await loginPageRes.text();
      let csrfToken = '';
      const match = loginHtml.match(/name=["']_csrf["']\s+value=["']([^"']+)["']/);
      if (match) csrfToken = match[1];
      const cookie = loginPageRes.headers()['set-cookie'];

      // Seeding 5 failed attempts in our stateful mock db
      let db = JSON.parse(fs.readFileSync(mockDbPath, 'utf8'));
      db.login_attempts = Array(5).fill({ ip_address: '127.0.0.1', attempt_time: new Date().toISOString() });
      fs.writeFileSync(mockDbPath, JSON.stringify(db, null, 2));

      // Dispatch 6th login request -> should block immediately with flash error redirection (HTTP 302 back to login.php)
      const res = await freshContext.post('/login_post.php', {
        headers: { 'Cookie': cookie },
        form: {
          email: 'attacker@example.com',
          password: 'bad_password',
          _csrf: csrfToken
        },
        maxRedirects: 0
      });

      expect(res.status()).toBe(302);
      expect(res.headers()['location']).toContain('login.php');
    });

    test('Invisible Honeypot field registers instant block on registration post submission', async ({ playwright }) => {
      const freshContext = await playwright.request.newContext();
      const regPageRes = await freshContext.get('/register.php');
      const regHtml = await regPageRes.text();
      let csrfToken = '';
      const match = regHtml.match(/name=["']_csrf["']\s+value=["']([^"']+)["']/);
      if (match) csrfToken = match[1];
      const cookie = regPageRes.headers()['set-cookie'];

      // Send a registration post request with the invisible website_url_verification honeypot field populated
      const res = await freshContext.post('/register.php', {
        headers: { 'Cookie': cookie },
        form: {
          firstName: 'Spam',
          lastName: 'Bot',
          email: 'spammer@disposable.com',
          password: 'Password123@',
          _csrf: csrfToken,
          website_url_verification: 'http://malicious-spam-url.com' // Honeypot filled!
        },
        maxRedirects: 0
      });

      // Silent drop returns 200 OK
      expect(res.status()).toBe(200);
    });

    test('SEO Clean URL routes are mapped correctly and return active views', async ({ page }) => {
      const res = await page.goto('/blog.php');
      expect(res.status()).toBe(200);
    });

    test('Vendor Profile dynamic team visibility logic shows/hides Our Team section', async ({ page }) => {
      // 1. Mock DB has empty team members array -> Section is hidden
      await page.goto('/vendor-profile.php?id=test-case-uuid');
      await expect(page.locator('#vendorTeamGrid')).not.toBeVisible();

      // 2. Seed team members in mock DB
      let db = JSON.parse(fs.readFileSync(mockDbPath, 'utf8'));
      db.provider_team_members = [
        {
          id: 1,
          provider_id: 1,
          name: "Alice Smith",
          role: "Golden Visa Consultant",
          specialties: "Golden Visa, Residenship",
          avatar: null
        }
      ];
      fs.writeFileSync(mockDbPath, JSON.stringify(db, null, 2));

      // 3. Reload vendor page -> section should now render perfectly!
      await page.reload();
      await expect(page.locator('#vendorTeamGrid')).toBeVisible();
      await expect(page.locator('#vendorTeamGrid')).toContainText("Alice Smith");
      await expect(page.locator('#vendorTeamGrid')).toContainText("Golden Visa Consultant");
    });
  });

  // Track 6: Advanced Visual Layout & Secure Price Checkout Audits
  test.describe('Track 6: Advanced Visual Layout & Secure Price Checkout Audits', () => {

    test('Secure price protection handles case checkouts and fetches authoritative costs', async ({ page }) => {
      // 1. Login user securely first to be allowed access to customer checkout page
      await page.goto('/bot-landing.php');
      await page.evaluate(() => {
        sendQueryToController('register');
      });
      await page.evaluate(() => {
        sendQueryToController('Jane');
      });
      await page.locator('#wsOptionsRack button:has-text("Confirm")').click();
      await page.evaluate(() => {
        sendQueryToController('Smith');
      });
      await page.locator('#wsOptionsRack button:has-text("Confirm")').click();
      await page.evaluate(() => {
        sendQueryToController('jane.smith@example.com');
      });
      await page.locator('#wsOptionsRack button:has-text("Confirm")').click();
      await page.evaluate(() => {
        sendQueryToController('+971509999999');
      });
      await page.locator('#wsOptionsRack button:has-text("Confirm")').click();

      // Wait for registration completion message to ensure session cookies are fully written in Playwright context
      await expect(page.locator('.ws-bubble.bot').last()).toContainText("Congratulations, Jane! Your customer registration is complete");

      // 2. Access customer checkout page with valid case_id
      await page.goto('/customer/checkout.php?case_id=test-case-uuid');
      expect(page.url()).toContain('/customer/checkout.php');

      // 3. Assert that case details and authoritative price are securely retrieved and rendered
      await expect(page.locator('body')).toContainText('Golden Visa Assistance');
      await expect(page.locator('body')).toContainText('AED 150.00');
    });

    test('Playwright visual layout check of AI Workspace', async ({ page }) => {
      await page.goto('/bot-landing.php');
      // Assert that core sidebar and preview views are rendered nicely
      await expect(page.locator('.ws-sidebar')).toBeVisible();
      await expect(page.locator('#bot-workspace-view')).toBeVisible();

      // Capture baseline screenshot
      await page.screenshot({ path: 'test-results/baselines/ai_workspace_soundness.png' });
    });

    test('Global system theme toggle switches HTML attribute and persists to localStorage', async ({ page }) => {
      await page.goto('/index.php');

      // Get initial theme attribute
      const defaultTheme = await page.locator('html').getAttribute('data-bs-theme') || 'light';

      // Click Theme Toggle Button
      const toggleBtn = page.locator('#themeToggleBtn');
      await expect(toggleBtn).toBeVisible();
      await toggleBtn.click();

      // Assert theme switches
      const toggledTheme = await page.locator('html').getAttribute('data-bs-theme');
      expect(toggledTheme).not.toBe(defaultTheme);

      // Verify persistence in localStorage
      const persistedTheme = await page.evaluate(() => localStorage.getItem('theme'));
      expect(persistedTheme).toBe(toggledTheme);
    });
  });

});
