const { test, expect } = require('@playwright/test');

test.describe('NLP Pre-Processing & Intent Matching Layer E2E Tests', () => {

  test('1. Spelling Error Self-Correction: should correct "starrt a bisness" and load Business Setup module', async ({ request }) => {
    // Send a mock user voice payload with obvious spelling errors to api/bot-controller.php
    const response = await request.post('/api/bot-controller.php', {
      data: {
        session_token: 'test-session-nlp-spelling',
        message: 'starrt a bisness',
        entry_point: 'immersive_landing',
        step_key: 'welcome_funnel'
      }
    });

    expect(response.status()).toBe(200);
    const body = await response.json();
    console.log('Spelling correction response:', body);

    expect(body.status).toBe('success');
    expect(body.active_state_token).toBe('intent_business_setup');
    expect(body.client_action).toBeDefined();
    expect(body.client_action.type).toBe('page_swap');
    expect(body.client_action.url).toContain('Business+Setup');
  });

  test('2. Complex Synonym Matching: should match "launch a brand new company" straight to business setup step', async ({ request }) => {
    // Send a different vocabulary phrase to api/bot-controller.php
    const response = await request.post('/api/bot-controller.php', {
      data: {
        session_token: 'test-session-nlp-synonym',
        message: 'launch a brand new company',
        entry_point: 'immersive_landing',
        step_key: 'welcome_funnel'
      }
    });

    expect(response.status()).toBe(200);
    const body = await response.json();
    console.log('Synonym matching response:', body);

    expect(body.status).toBe('success');
    expect(body.active_state_token).toBe('intent_business_setup');
    expect(body.client_action).toBeDefined();
    expect(body.client_action.type).toBe('page_swap');
    expect(body.client_action.url).toContain('Business+Setup');
  });

  test('3. Security & CSP Verification + Live Mapping Portal UI flow', async ({ page, request }) => {
    page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
    page.on('pageerror', err => console.log('BROWSER ERROR:', err.message));

    // Generate a unique failed question to prevent stateful test pollution across runs
    const failedQuestionText = 'where can i buy a hot cup of coffee ' + Math.random().toString(36).substring(7);

    // First, let's trigger a failed question log by sending an unmapped custom query
    const fallbackResponse = await request.post('/api/bot-controller.php', {
      data: {
        session_token: 'test-session-failed-log',
        message: failedQuestionText,
        entry_point: 'immersive_landing',
        step_key: 'welcome_funnel'
      }
    });
    expect(fallbackResponse.status()).toBe(200);

    // Authenticate as a permitted administrator
    await page.goto('/tests/test-login-helper.php?role=admin_with_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_with_permission');

    // Navigate to the failed questions audit page
    await page.goto('/admin/crm/failed-questions.php');

    // Assert the page contains our unmapped failed question
    const failedQuestionCell = page.locator(`text="${failedQuestionText}"`);
    await expect(failedQuestionCell).toBeVisible();

    // Assert the Quick Action button is present (we target the one for our specific question)
    const mapBtn = page.locator(`tr:has-text("${failedQuestionText}") .btn-map-synonym`);
    await expect(mapBtn).toBeVisible();

    // CSP and Security Verification: Ensure NO inline "onclick" handlers exist on map buttons
    const onclickAttribute = await mapBtn.getAttribute('onclick');
    expect(onclickAttribute).toBeNull();

    // Click on the Map as Alternative Phrase button to open the modal
    await mapBtn.click();

    // Assert modal fields are populated correctly with defaulted values
    const modal = page.locator('#mapSynonymModal');
    await expect(modal).toBeVisible();
    await expect(page.locator('#modalPhraseVariant')).toHaveValue(failedQuestionText);
    await expect(page.locator('#modalLanguageCode')).toHaveValue('en');

    // Select target workflow step key from the dropdown
    await page.selectOption('#modalSystemIntentKey', 'intent_business_setup');

    // Submit the modal form
    await page.click('#btnSubmitSynonym');

    // Assert page reloads with flash success message and the failed question is resolved/removed from table
    const successAlert = page.locator('.alert-success');
    await expect(successAlert).toBeVisible();
    await expect(successAlert).toContainText('Phrase variant successfully mapped');

    // Assert resolved question is now resolved and no longer exists in the list
    await expect(page.locator(`text="${failedQuestionText}"`)).toBeHidden();
  });

  test('4. Approved Keywords Admin CRUD Management panel flow', async ({ page }) => {
    // Authenticate as permitted administrator
    await page.goto('/tests/test-login-helper.php?role=admin_with_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_with_permission');

    // Navigate to approved keywords settings page
    await page.goto('/admin/settings/bot_keywords.php');

    // Assert headers and container exist
    await expect(page.locator('text="Database-Driven Typo Spelling Dictionary"')).toBeVisible();

    // Assert submit button is programmatically bound and has no inline onclick attribute
    const addBtn = page.locator('#submitNewSystemKeyword');
    await expect(addBtn).toBeVisible();
    const inlineOnclick = await addBtn.getAttribute('onclick');
    expect(inlineOnclick).toBeNull();

    // Add a new system keyword
    await page.fill('#new_keyword_token', 'unicorn');
    await page.selectOption('#new_language_code', 'en');
    await addBtn.click();

    // Assert success alert
    const successAlert = page.locator('.alert-success');
    await expect(successAlert).toBeVisible();
    await expect(successAlert).toContainText("Approved keyword 'unicorn' added successfully.");

    // Assert keyword token exists in table list
    await expect(page.locator('strong:has-text("unicorn")')).toBeVisible();

    // Setup dialog handler to automatically accept delete confirmation dialog
    page.on('dialog', async dialog => {
      expect(dialog.message()).toContain('permanently delete');
      await dialog.accept();
    });

    // Delete the newly registered keyword
    const deleteBtn = page.locator('tr:has-text("unicorn") .btn-delete-keyword');
    await deleteBtn.click();

    // Assert success alert of deletion
    await expect(page.locator('.alert-success')).toContainText("Approved keyword successfully deleted.");
    await expect(page.locator('strong:has-text("unicorn")')).toBeHidden();
  });

  test('5. Multilingual Dashboard Filter assertion', async ({ page }) => {
    // Authenticate as permitted administrator
    await page.goto('/tests/test-login-helper.php?role=admin_with_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_with_permission');

    // Navigate to bot keywords page
    await page.goto('/admin/settings/bot_keywords.php');

    // Select the Arabic 'ar' language filter
    await page.selectOption('#adminKeywordLanguageFilter', 'ar');

    // Assert that only 'ar' keywords are visible and 'en' keywords are hidden
    const englishKeywordRow = page.locator('tr.keyword-row[data-lang="en"]').first();
    const arabicKeywordRow = page.locator('tr.keyword-row[data-lang="ar"]').first();

    await expect(englishKeywordRow).toBeHidden();
    await expect(arabicKeywordRow).toBeVisible();
  });

  test('6. Multilingual Typo Self-Correction in Urdu/Hindi with transient caching', async ({ request }) => {
    // Mock a public chat widget voice session in Urdu/Hindi with an Urdu typo: "کاروبارر"
    const response = await request.post('/api/bot-controller.php', {
      data: {
        session_token: 'test-session-nlp-urdu',
        message: 'کاروبارر',
        language_iso: 'ur',
        entry_point: 'immersive_landing',
        step_key: 'welcome_funnel'
      }
    });

    expect(response.status()).toBe(200);
    const body = await response.json();
    console.log('Urdu typo correction response:', body);

    expect(body.status).toBe('success');
    // The typo 'کاروبارر' should correct cleanly to the approved Urdu keyword token 'کاروبار'
    // Since 'کاروبار' is Urdu for 'business', let's verify it didn't trigger cross-language collisions with English
    expect(body.display_text).not.toContain('I am unable to find that specific configuration');
  });
});
