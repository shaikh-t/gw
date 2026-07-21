// tests/voice-analytics-integration.spec.js
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

test.describe('Unified Voice & Analytics Integration Suite', () => {

  test.beforeEach(async ({ page }) => {
    // Capture browser console logs
    page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
    page.on('pageerror', err => console.log('BROWSER ERROR:', err.message));
  });

  test('1. Authorised Permission Verification: access configuration screen, save, and verify live database updates', async ({ page }) => {
    // Authenticate as permitted admin using test-login-helper
    await page.goto('/tests/test-login-helper.php?role=admin_with_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_with_permission');

    // Go to unified Voice & Analytics configuration screen
    await page.goto('/admin/settings/voice_analytics.php');
    await expect(page.locator("h2:has-text('Voice & Analytics Control Panel')")).toBeVisible();

    // Check Google Analytics checkbox and enter measurement ID
    const gaToggle = page.locator('#gaStatusToggle');
    await gaToggle.check();

    const gaInput = page.locator('#gaMeasurementId');
    await gaInput.fill('G-PLAYWRIGHT-999');

    // Submit configuration variables
    const submitBtn = page.locator('#btnSubmitSettings');
    await submitBtn.click();

    // Assert successful update notification
    const alertSuccess = page.locator('.alert-success');
    await expect(alertSuccess).toBeVisible();
    await expect(alertSuccess).toContainText('Voice and Analytics Configuration saved successfully.');

    // Reload the page directly and verify updates are persistent and rendered live immediately
    await page.goto('/admin/settings/voice_analytics.php');
    await expect(page.locator('#gaMeasurementId')).toHaveValue('G-PLAYWRIGHT-999');
  });

  test('2. Unauthorised Gate Test: non-permitted admin attempt to access settings results in 403 block', async ({ page, request }) => {
    // Authenticate as non-permitted admin
    await page.goto('/tests/test-login-helper.php?role=admin_no_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_no_permission');

    // Attempt direct URL access to voice_analytics settings page
    const response = await page.goto('/admin/settings/voice_analytics.php');

    // Assert 403 Forbidden status code is returned
    expect(response.status()).toBe(403);
  });

  test('3. Fallback Pipeline Mock: simulated ElevenLabs API rate-limit error (429) triggers browser native TTS fallback', async ({ page }) => {
    // Log in as authorized admin
    await page.goto('/tests/test-login-helper.php?role=admin_with_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_with_permission');

    // Inject mocks and trace synthesis calls
    await page.addInitScript(() => {
      window.speechSynthesisSpeakCalled = false;
      const mockSpeechSynthesis = {
        speak: (utterance) => {
          window.speechSynthesisSpeakCalled = true;
          setTimeout(() => {
            if (utterance.onend) utterance.onend();
          }, 10);
        },
        cancel: () => {},
        getVoices: () => []
      };
      Object.defineProperty(window, 'speechSynthesis', {
        value: mockSpeechSynthesis,
        writable: true
      });
    });

    // Mock api/tts-processor.php to simulate rate-limit HTTP 429 error
    await page.route('**/api/tts-processor.php', route => {
      route.fulfill({
        status: 429,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'fallback', message: 'ElevenLabs Rate Limit Exceeded' })
      });
    });

    // Navigate to bot-landing page
    await page.goto('/bot-landing.php');

    // Trigger workspace reset to generate bot dialog/options
    const resetBtn = page.locator('#wsResetTrigger');
    await expect(resetBtn).toBeVisible();
    await resetBtn.click();

    // Verify browser native speechSynthesis speak fallback was triggered on error
    const speakWasCalled = await page.evaluate(() => window.speechSynthesisSpeakCalled);
    expect(speakWasCalled).toBe(true);
  });

  test('4. SQL Restoration Consistency: verify consolidated master gpa_gw2.sql matches cumulative migration schemas', () => {
    const sqlPath = path.join(__dirname, '../gpa_gw2.sql');
    expect(fs.existsSync(sqlPath)).toBe(true);

    const sqlContent = fs.readFileSync(sqlPath, 'utf-8');

    // Assert that the newly added database structures exist inside gpa_gw2.sql
    expect(sqlContent).toContain('voice_telemetry_logs');
    expect(sqlContent).toContain('bot_nodes');
    expect(sqlContent).toContain('bot_sessions');
    expect(sqlContent).toContain('bot_chat_logs');
    expect(sqlContent).toContain('bot_failed_questions');
    expect(sqlContent).toContain('customer_applications');
    expect(sqlContent).toContain('customer_documents');
    expect(sqlContent).toContain('customer_payments');
    expect(sqlContent).toContain('customer_messages');
    expect(sqlContent).toContain('payment_transactions');

    // Assert that new RBAC permissions exist inside permissions dump
    expect(sqlContent).toContain('manage_system_analytics');
    expect(sqlContent).toContain('view_voice_telemetry');

    // Assert that default site settings exist
    expect(sqlContent).toContain('google_analytics_status');
    expect(sqlContent).toContain('google_analytics_measurement_id');
    expect(sqlContent).toContain('elevenlabs_status');
  });

});
