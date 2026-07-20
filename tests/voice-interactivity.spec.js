// tests/voice-interactivity.spec.js
const { test, expect } = require('@playwright/test');

test.describe('Voice Interactivity & Multilingual AI Workflow Checks', () => {

  test.beforeEach(async ({ page }) => {
    // Capture browser console logs
    page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));

    // Inject robust mocks for SpeechRecognition and SpeechSynthesis before any scripts load
    await page.addInitScript(() => {
      // 1. Mock SpeechRecognition
      class MockSpeechRecognition {
        constructor() {
          this.continuous = false;
          this.interimResults = false;
          this.lang = 'en-US';

          this.onstart = null;
          this.onresult = null;
          this.onerror = null;
          this.onend = null;

          window.activeMockRecognition = this;
        }

        start() {
          window.mockRecognitionActive = true;
          if (this.onstart) {
            setTimeout(() => {
              if (this.onstart) this.onstart();
            }, 10);
          }
        }

        stop() {
          window.mockRecognitionActive = false;
          if (this.onend) {
            setTimeout(() => {
              if (this.onend) this.onend();
            }, 10);
          }
        }

        mockResult(transcript) {
          if (this.onresult) {
            const event = {
              results: [
                [
                  { transcript: transcript }
                ]
              ]
            };
            this.onresult(event);
          }
          this.stop();
        }
      }
      window.SpeechRecognition = MockSpeechRecognition;
      window.webkitSpeechRecognition = MockSpeechRecognition;
      window.mockRecognitionActive = false;

      // 2. Mock SpeechSynthesis for headless environments
      const mockSpeechSynthesis = {
        speak: (utterance) => {
          // Immediately simulate the speaking completion asynchronously to trigger onend
          setTimeout(() => {
            if (utterance.onend) {
              utterance.onend();
            }
          }, 50);
        },
        cancel: () => {},
        getVoices: () => []
      };
      Object.defineProperty(window, 'speechSynthesis', {
        value: mockSpeechSynthesis,
        writable: true
      });
    });
  });

  test('1. Continuous Listening: should remain in active listening state after voice transcription delivery', async ({ page }) => {
    await page.goto('/bot-landing.php');

    // Reset workspace to clean starting state
    const resetBtn = page.locator('#wsResetTrigger');
    await expect(resetBtn).toBeVisible();
    await resetBtn.click();

    // Wait for language selection buttons to render to ensure reset request completed
    await page.waitForSelector('#wsOptionsRack button');
    const firstOption = page.locator('#wsOptionsRack button').first();
    await expect(firstOption).toBeVisible();

    // Verify microphone is initialized as offline
    const statusText = page.locator('#wsStatusText');
    await expect(statusText).toContainText('Mic is offline');

    // Toggle mic ON
    const micBtn = page.locator('#wsMicTrigger');
    await expect(micBtn).toBeVisible();
    await micBtn.click();

    // Verify status text transitions to active
    await expect(statusText).toContainText('Listening...');
    let isListening = await page.evaluate(() => window.mockRecognitionActive);
    expect(isListening).toBe(true);

    // Deliver a mock transcription
    await page.evaluate(() => {
      window.activeMockRecognition.mockResult("hello");
    });

    // Wait a brief moment for transcription dispatch and auto-restart loop
    await page.waitForTimeout(500);

    // Verify speech recognition auto-restarts and remains active
    isListening = await page.evaluate(() => window.mockRecognitionActive);
    expect(isListening).toBe(true);
    await expect(statusText).toContainText('Listening...');
  });

  test('2. Multilingual Parsing: should process translated language names ("Anglais") to trigger English routing', async ({ page }) => {
    await page.goto('/bot-landing.php');

    // Reset workspace to show language selection screen (node 1)
    const resetBtn = page.locator('#wsResetTrigger');
    await resetBtn.click();

    // Wait for the 4 language options to render
    await page.waitForSelector('#wsOptionsRack button');
    await expect(page.locator('#wsOptionsRack button')).toHaveCount(4);

    // Start mic
    const micBtn = page.locator('#wsMicTrigger');
    await micBtn.click();

    // Send French translation for English: "Anglais"
    await page.evaluate(() => {
      window.activeMockRecognition.mockResult("Anglais");
    });

    // Expect the system state to transition to the voice selection choice in English
    const chatStream = page.locator('#wsChatStream');
    await expect(chatStream).toContainText('assistance of our AI Voice Companion', { timeout: 10000 });
  });

  test('3. Option Selection: speaking button keyword programmatically dispatches the associated event', async ({ page }) => {
    await page.goto('/bot-landing.php');

    // Reset workspace
    const resetBtn = page.locator('#wsResetTrigger');
    await resetBtn.click();

    // Wait for language options to render
    await page.waitForSelector('#wsOptionsRack button');
    await expect(page.locator('#wsOptionsRack button')).toHaveCount(4);

    // Select English first to render next set of options
    await page.locator('#wsOptionsRack button:has-text("English")').click();

    // Assert that "AI Voice Companion" and "Browse Independently" options are rendered
    await page.waitForSelector('#wsOptionsRack button');
    const optionRack = page.locator('#wsOptionsRack');
    await expect(optionRack).toContainText('AI Voice Companion');
    await expect(optionRack).toContainText('Browse Independently');

    // Start mic
    const micBtn = page.locator('#wsMicTrigger');
    await micBtn.click();

    // Speak keyword "Browse" to match "Browse Independently"
    await page.evaluate(() => {
      window.activeMockRecognition.mockResult("Browse");
    });

    // Since Browse Independently collapses/silences the companion, verify response message
    const chatStream = page.locator('#wsChatStream');
    await expect(chatStream).toContainText('silently available in the bottom corner', { timeout: 10000 });
  });

  test('4. Redirection & Hydration: language selection workflow routes to bot-landing.php and hydrates state', async ({ page }) => {
    await page.goto('/index.php');

    // Check localStorage is clear initially
    await page.evaluate(() => {
      localStorage.removeItem('globalways_bot_session');
      localStorage.removeItem('globalways_bot_language');
    });

    // Open floating widget
    const badgeTrigger = page.locator('#botBadgeTrigger');
    await expect(badgeTrigger).toBeVisible();
    await badgeTrigger.click();

    // Wait for the options rack inside floating widget to render buttons
    await page.waitForSelector('#botOptionsRack button');

    // Start mic inside the widget
    const micBtn = page.locator('#botMicTrigger');
    await expect(micBtn).toBeVisible();
    await micBtn.click();

    // Speak "Select English" to trigger language select
    await page.evaluate(() => {
      window.activeMockRecognition.mockResult("Select English");
    });

    // Verify window routes to bot-landing.php
    await expect(page).toHaveURL(/bot-landing\.php/, { timeout: 10000 });

    // Verify LocalStorage contains session tokens and chosen language securely without URL leakage
    const storedLang = await page.evaluate(() => localStorage.getItem('globalways_bot_language'));
    expect(storedLang).toBe('en');

    const storedSession = await page.evaluate(() => localStorage.getItem('globalways_bot_session'));
    expect(storedSession).not.toBeNull();
    expect(storedSession.length).toBeGreaterThan(0);

    // Verify that bot-landing page hydrated state and successfully loaded voice companion screen
    const chatStream = page.locator('#wsChatStream');
    await expect(chatStream).toContainText('assistance of our AI Voice Companion', { timeout: 10000 });
  });

});
