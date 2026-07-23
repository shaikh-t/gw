const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const mockDbPath = path.resolve(__dirname, '../var/mock_db.json');

function resetMockDb() {
  const default_db = {
    "site_settings": {
      "ai_bot_global_status": "enabled"
    },
    "ads": [],
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
        "deduction_value": 10.00,
        "owner_user_id": 1
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
    ],
    "bot_workflow_steps": [
        {
            "id": 1,
            "step_key": "welcome_funnel",
            "step_order": 10,
            "primary_question_en": "Welcome to GlobalWays! Please select your service category below to personalize your journey.",
            "primary_question_fr": "Bienvenue sur GlobalWays ! Veuillez sélectionner votre catégorie de service ci-dessous pour personnaliser votre parcours.",
            "primary_question_ar": "مرحباً بك في غلوبال وايز! يرجى تحديد فئة الخدمة الخاصة بك أدناه لتخصيص رحلتك.",
            "primary_question_ur": "گلوبل ویز میں خوش آمدید! برائے مہربانی اپنا سفر ذاتی بنانے کے لیے نیچے اپنی سروس کیٹیگری منتخب کریں۔",
            "interface_target": "left_window",
            "execution_action": "none",
            "parent_step_id": null
        },
        {
            "id": 2,
            "step_key": "category_selection",
            "step_order": 20,
            "primary_question_en": "Excellent! We have updated the right panel layout with customized service options. What would you like to do next?",
            "primary_question_fr": "Excellent ! Nous avons mis à jour la mise en page du panneau de droite avec des options de service personnalisées. Que souhaitez-vous faire ensuite ?",
            "primary_question_ar": "ممتاز! لقد قمنا بتحديث تخطيط اللوحة اليمنى بخيارات الخدمة المخصصة. ماذا تحب أن تفعل بعد ذلك؟",
            "primary_question_ur": "بہت خوب! ہم نے کسٹمائزڈ سروس آپشنز کے ساتھ دائیں پینل کا لے آؤٹ اپ ڈیٹ کر دیا ہے۔ اب آپ آگے کیا کرنا چاہیں گے؟",
            "interface_target": "right_window",
            "execution_action": "hydrate_right_panel",
            "parent_step_id": 1
        },
        {
            "id": 3,
            "step_key": "business_setup_dispatch",
            "step_order": 30,
            "primary_question_en": "We can dispatch an automated meeting request to schedule a business setup consultation. Would you like to proceed?",
            "primary_question_fr": "Nous pouvons envoyer une demande de rendez-vous automatique pour planifier une consultation sur la création d'entreprise. Souhaitez-vous continuer ?",
            "primary_question_ar": "يمكننا إرسال طلب اجتماع تلقائي لجدولة استشارة لتأسيس الشركة. هل ترغب في المتابعة؟",
            "primary_question_ur": "ہم بزنس سیٹ اپ مشاورت کے لیے ایک خودکار میٹنگ کی درخواست بھیج سکتے ہیں۔ کیا آپ آگے بڑھنا چاہیں گے؟",
            "interface_target": "right_window",
            "execution_action": "dispatch_case_meeting",
            "parent_step_id": 2
        }
    ],
    "bot_interaction_logs": []
  };
  fs.writeFileSync(mockDbPath, JSON.stringify(default_db, null, 2));
}

test.describe('Conversational Workflow & Telemetry Verification Suite', () => {

  test.beforeEach(async ({ page, context }) => {
    resetMockDb();
    page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
    page.on('pageerror', err => console.log('BROWSER ERROR:', err.message));
    await context.clearCookies();
    await context.addCookies([{
      name: 'force_mock_db',
      value: 'true',
      domain: '127.0.0.1',
      path: '/'
    }]);
    await page.goto('/tests/test-login-helper.php?role=logout');
    await page.evaluate(() => localStorage.clear());
    await page.evaluate(() => sessionStorage.clear());
  });

  test('1. Authorised Flow Mutation: Mutate translation, save, and check live assistant workspace rendering', async ({ page }) => {
    // Login as permitted administrator
    await page.goto('/tests/test-login-helper.php?role=admin_with_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_with_permission');

    // Access Workflow CRUD management screen
    await page.goto('/admin/settings/bot_steps.php');
    await expect(page.locator("h1:has-text('Conversational Funnel Steps')")).toBeVisible();

    // Click Edit on first step 'welcome_funnel'
    const editBtn = page.locator(".edit-step-btn").first();
    await expect(editBtn).toBeVisible();
    await editBtn.click();

    // Edit English question text
    const questionInput = page.locator("#primary_question_en");
    await questionInput.waitFor({ state: 'visible' });
    await expect(questionInput).toBeVisible();
    await questionInput.fill("MUTATED GREETING WORKFLOW GREETING");

    // Click Save Configuration
    const saveBtn = page.locator("#saveBotStepFlowConfiguration");
    await saveBtn.click();

    // Assert success alert
    await expect(page.locator(".alert-success")).toContainText("Workflow step updated successfully!");

    // Navigate to Assistant landing workspace and assert change renders live instantly
    await page.goto('/bot-landing.php');
    await expect(page.locator(".ws-bubble.bot").last()).toContainText("MUTATED GREETING WORKFLOW GREETING");
  });

  test('2. Multi-Tier Workflow Simulation: Voice / option category selection hydrates right container, unmapped triggers RAG fallback', async ({ page }) => {
    await page.goto('/bot-landing.php');

    // Assert first welcome step loaded by default
    await expect(page.locator(".ws-bubble.bot").last()).toContainText("Welcome to GlobalWays!");

    // Click dynamic option "Immigration Services"
    const catBtn = page.locator('#wsOptionsRack button:has-text("Immigration Services")');
    await expect(catBtn).toBeVisible();
    await catBtn.click();

    // Assert that the right window panel pre-hydrates or filters accordingly
    await expect(page.locator(".ws-bubble.bot").last()).toContainText("Excellent! We have updated the right panel layout");

    // Inject unmapped keyword to trigger RAG fallback guidelines route
    await page.evaluate(() => {
      sendQueryToController('', null, 'visa guide');
    });

    await expect(page.locator(".ws-bubble.bot").last()).toContainText("Verified Guidelines: This is the authoritative golden visa guide details.");
  });

  test('3. Guest-to-Auth Case Dispatch Verification: Save guest context, trigger simulated login, automatically dispatch case and toast success', async ({ page }) => {
    await page.goto('/bot-landing.php');

    // Choose Immigration Services category option
    const catBtn = page.locator('#wsOptionsRack button:has-text("Immigration Services")');
    await catBtn.click();

    // Choose Schedule Consultation Meeting which triggers auth redirection
    const schedBtn = page.locator('#wsOptionsRack button:has-text("Schedule Consultation Meeting")');
    await expect(schedBtn).toBeVisible();
    await schedBtn.click();

    // Verify redirected to login.php
    await expect(page).toHaveURL(/login\.php/);

    // Assert context has been saved in LocalStorage securely
    const savedToken = await page.evaluate(() => localStorage.getItem('pending_active_state_token'));
    expect(savedToken).toBe('business_setup_dispatch');

    // Simulate successful login and returning to bot-landing
    await page.goto('/tests/test-login-helper.php?role=admin_with_permission');
    await page.goto('/bot-landing.php');

    // Verify automatic capture, vendor case dispatch, and success toast confirmation overlay on user's screen
    await expect(page.locator('.toast-body')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('.toast-body')).toContainText("Consultation appointment request successfully dispatched!");
  });

  test('4. Database Security Gate Test: Unauthorized role gets blocked with HTTP 403 Forbidden', async ({ page }) => {
    // Authenticate as non-permitted admin (fails manage_bot_steps and view_bot_interaction_logs)
    await page.goto('/tests/test-login-helper.php?role=admin_no_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_no_permission');

    // Attempt direct path manipulation for steps screen
    const res1 = await page.goto('/admin/settings/bot_steps.php');
    expect(res1.status()).toBe(403);

    // Attempt direct path manipulation for logs screen
    const res2 = await page.goto('/admin/analytics/bot_logs.php');
    expect(res2.status()).toBe(403);
  });

});
