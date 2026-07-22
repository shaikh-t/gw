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
    // Assert active state token is mapped to 'intent_business_setup'
    expect(body.active_state_token).toBe('intent_business_setup');
    // Assert it loads the Business Setup module via page swap
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

    // First, let's trigger a failed question log by sending an unmapped custom query
    const fallbackResponse = await request.post('/api/bot-controller.php', {
      data: {
        session_token: 'test-session-failed-log',
        message: 'how to fly to mars on a pink unicorn',
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
    const failedQuestionCell = page.locator('text="how to fly to mars on a pink unicorn"');
    await expect(failedQuestionCell).toBeVisible();

    // Assert the Quick Action button is present
    const mapBtn = page.locator('.btn-map-synonym').first();
    await expect(mapBtn).toBeVisible();

    // CSP and Security Verification: Ensure NO inline "onclick" handlers exist on map buttons
    const onclickAttribute = await mapBtn.getAttribute('onclick');
    expect(onclickAttribute).toBeNull();

    // Click on the Map as Alternative Phrase button to open the modal
    await mapBtn.click();

    // Assert modal fields are populated correctly with defaulted values
    const modal = page.locator('#mapSynonymModal');
    await expect(modal).toBeVisible();
    await expect(page.locator('#modalPhraseVariant')).toHaveValue('how to fly to mars on a pink unicorn');
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
    await expect(page.locator('text="how to fly to mars on a pink unicorn"')).toBeHidden();
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
});
