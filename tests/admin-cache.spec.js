const { test, expect } = require('@playwright/test');

test.describe('Admin Cache Clearance RBAC & Integration Checks', () => {

  test('1. Authorised Admin Verification: should see cache-clear button and trigger click to get success notification', async ({ page }) => {
    // Listen for console logs
    page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
    page.on('pageerror', err => console.log('BROWSER ERROR:', err.message));

    // Authenticate as permitted admin using our local test-login-helper
    await page.goto('/tests/test-login-helper.php?role=admin_with_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_with_permission');

    // Navigate to admin dashboard
    await page.goto('/admin/dashboard.php');

    // Assert cache-clear button element is visible
    const clearButton = page.locator('#btnClearCache');
    await expect(clearButton).toBeVisible();

    // Trigger click event
    await page.evaluate(() => {
      const btn = document.getElementById('btnClearCache');
      if (btn) btn.click();
    });

    // Assert success toast/alert notification appears within the viewport
    const successToast = page.locator('#dynamicCacheAlert');
    await expect(successToast).toBeVisible();
    await expect(successToast).toContainText('Application cache cleared successfully.');
  });

  test('2. Unauthorised Admin Restriction: should hide cache-clear button for admin without permission', async ({ page }) => {
    // Authenticate as admin lacking cache.clear permission
    await page.goto('/tests/test-login-helper.php?role=admin_no_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_no_permission');

    // Navigate to admin dashboard
    await page.goto('/admin/dashboard.php');

    // Assert cache-clear button is detached or hidden from view
    const clearButton = page.locator('#btnClearCache');
    await expect(clearButton).toBeHidden();
  });

  test('3. Direct URL Access Block: direct POST request to backend clear-cache action from unauthorised admin must return HTTP 403', async ({ page, request }) => {
    // Log in as admin without permission first to establish the unauthorized context
    await page.goto('/tests/test-login-helper.php?role=admin_no_permission');
    await expect(page.locator('body')).toContainText('Session set for role: admin_no_permission');

    // Make a direct POST request to clear_cache_action.php
    const response = await request.post('/admin/clear_cache_action.php', {
      data: {
        _csrf: 'fake_or_missing_csrf'
      }
    });

    // Assert that the direct backend endpoint returns 403 Forbidden
    expect(response.status()).toBe(403);
  });
});
