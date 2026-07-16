<!-- templates/bot-widget.php -->
<style>
:root {
  --bot-primary: #1165ef;
  --bot-dark: #111827;
  --bot-light: #f3f4f6;
  --bot-bg: #ffffff;
  --bot-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  --bot-border: #e5e7eb;
}

/* Floating helper badge */
.bot-badge-trigger {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  background-color: var(--bot-primary);
  color: #ffffff;
  padding: 12px 24px;
  border-radius: 50px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: var(--bot-shadow);
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease-in-out;
  border: none;
}
.bot-badge-trigger:hover {
  transform: translateY(-2px);
  background-color: #0d54c7;
}

/* Collapsible main chat drawer panel */
.bot-chat-container {
  position: fixed;
  bottom: 88px;
  right: 24px;
  width: 380px;
  max-width: calc(100vw - 48px);
  height: 550px;
  max-height: calc(100vh - 120px);
  background-color: var(--bot-bg);
  border-radius: 16px;
  box-shadow: var(--bot-shadow);
  z-index: 10000;
  display: flex;
  flex-direction: column;
  border: 1px solid var(--bot-border);
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  transform: translateY(20px) scale(0.95);
  opacity: 0;
  pointer-events: none;
}
.bot-chat-container.active {
  transform: translateY(0) scale(1);
  opacity: 1;
  pointer-events: auto;
}

/* Header */
.bot-chat-header {
  background-color: var(--bot-dark);
  color: #ffffff;
  padding: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.bot-chat-title {
  font-weight: 600;
  font-size: 1rem;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.bot-chat-header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}
.bot-reset-btn {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}
.bot-reset-btn:hover {
  color: #ffffff;
}
.bot-close-btn {
  background: none;
  border: none;
  color: #ffffff;
  font-size: 1.2rem;
  cursor: pointer;
  line-height: 1;
}

/* Messages stream */
.bot-chat-stream {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background-color: #f9fafb;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.bot-message {
  max-width: 80%;
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 0.9rem;
  line-height: 1.4;
  word-wrap: break-word;
}
.bot-message.bot {
  background-color: #ffffff;
  color: var(--bot-dark);
  align-self: flex-start;
  border-bottom-left-radius: 4px;
  border: 1px solid var(--bot-border);
}
.bot-message.user {
  background-color: var(--bot-primary);
  color: #ffffff;
  align-self: flex-end;
  border-bottom-right-radius: 4px;
}

/* Options/Interaction Area */
.bot-options-rack {
  padding: 12px;
  background-color: #ffffff;
  border-top: 1px solid var(--bot-border);
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 180px;
  overflow-y: auto;
}
.bot-option-btn {
  background-color: var(--bot-light);
  color: var(--bot-dark);
  border: 1px solid var(--bot-border);
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.85rem;
  text-align: left;
  cursor: pointer;
  transition: all 0.15s ease;
  font-weight: 500;
}
.bot-option-btn:hover {
  background-color: var(--bot-primary);
  color: #ffffff;
  border-color: var(--bot-primary);
}

/* Mic Controls & Indicator */
.bot-input-area {
  padding: 12px 16px;
  background-color: #ffffff;
  border-top: 1px solid var(--bot-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.bot-mic-trigger {
  background-color: var(--bot-light);
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1.2rem;
  color: var(--bot-dark);
  transition: all 0.2s ease;
}
.bot-mic-trigger.listening {
  background-color: #ef4444;
  color: #ffffff;
  animation: pulse-mic 1.5s infinite;
}
.bot-status-text {
  font-size: 0.8rem;
  color: #6b7280;
  flex: 1;
}

@keyframes pulse-mic {
  0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
  100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}
</style>

<!-- Floating Action Trigger -->
<button class="bot-badge-trigger" id="botBadgeTrigger" onclick="toggleBotChat()">
  <span>🤖 Ask AI Assistant</span>
</button>

<!-- Main Chat Drawer -->
<div class="bot-chat-container" id="botChatContainer">
  <div class="bot-chat-header">
    <div class="bot-chat-title">
      <i class="bi bi-robot"></i> AI Companion
    </div>
    <div class="bot-chat-header-actions">
      <button class="bot-reset-btn" onclick="resetBot()"><i class="bi bi-arrow-counterclockwise"></i> 🔄 Reset</button>
      <button class="bot-close-btn" onclick="toggleBotChat()">×</button>
    </div>
  </div>

  <div class="bot-chat-stream" id="botChatStream">
    <!-- Messages append here dynamically -->
  </div>

  <div class="bot-options-rack" id="botOptionsRack">
    <!-- Options append here dynamically -->
  </div>

  <div class="bot-input-area">
    <button class="bot-mic-trigger" id="botMicTrigger" onclick="toggleSpeechInput()">
      <i class="bi bi-mic-fill" id="botMicIcon"></i>
    </button>
    <div class="bot-status-text" id="botStatusText">Mic is offline</div>
  </div>
</div>

<script>
let botSessionToken = localStorage.getItem('globalways_bot_session') || '';
let currentLang = 'en';
let isListening = false;
let recognition = null;
let activeSpeaker = null;

const langCodeMapping = {
  'en': 'en-US',
  'fr': 'fr-FR',
  'ar': 'ar-SA',
  'ur': 'ur-PK'
};

document.addEventListener('DOMContentLoaded', () => {
  // Initialize standard web speech components
  if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = false;

    recognition.onstart = () => {
      isListening = true;
      document.getElementById('botMicTrigger').classList.add('listening');
      document.getElementById('botStatusText').innerText = 'Listening... Speak now';
    };

    recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      addMessageToStream('user', transcript);
      sendQueryToController('', null, transcript);
    };

    recognition.onerror = (event) => {
      console.error('Speech recognition error:', event.error);
      stopSpeechRecognition();
    };

    recognition.onend = () => {
      stopSpeechRecognition();
    };
  } else {
    document.getElementById('botMicTrigger').style.display = 'none';
    document.getElementById('botStatusText').innerText = 'Speech-to-text not supported';
  }

  // Load the initial welcome screen when page gets loaded or session is resolved
  loadInitialMenu();
});

function getEntryPoint() {
  const path = window.location.pathname.split('/').pop() || '';
  if (path === '' || path === 'index.php') return 'home_page';
  if (path.includes('vendor-profile')) return 'vendor_profile';
  if (path.includes('service-detail')) return 'service_detail';
  return path || 'general_page';
}

function toggleBotChat() {
  const container = document.getElementById('botChatContainer');
  const activeState = container.classList.toggle('active');

  if (activeState) {
    // If opening, track current page context first
    let pageName = window.location.pathname.split('/').pop() || 'index.php';
    let payload = {
      session_token: botSessionToken,
      badge_click: true,
      entry_point: getEntryPoint(),
      page_context: {
        page_name: pageName,
        url: window.location.href
      }
    };

    // Check if on vendor profile
    const vendorTitleEl = document.getElementById('vendorTitle');
    if (vendorTitleEl) {
      payload.page_context.vendor_name = vendorTitleEl.innerText;
    }

    // Check if on service detail
    const serviceTitleEl = document.getElementById('serviceTitle');
    if (serviceTitleEl) {
      payload.page_context.service_title = serviceTitleEl.innerText;
    }

    fetch('api/bot-controller.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success') {
        botSessionToken = data.session_token;
        localStorage.setItem('globalways_bot_session', botSessionToken);
        currentLang = data.language_iso || 'en';

        // Render response if dynamic page context was caught
        if (data.display_text && data.display_text !== 'Welcome!') {
          document.getElementById('botChatStream').innerHTML = '';
          addMessageToStream('bot', data.display_text);
          speakOutLoud(data.spoken_text);
          renderOptions(data.next_options);
        }
      }
    });
  }
}

function loadInitialMenu() {
  document.getElementById('botChatStream').innerHTML = '';
  // Call controller to initialize or restore session
  sendQueryToController('', 1, '');
}

function sendQueryToController(messageText, nodeId = null, userInputText = '') {
  let pageName = window.location.pathname.split('/').pop() || 'index.php';
  let payload = {
    session_token: botSessionToken,
    node_id: nodeId,
    message: userInputText || messageText,
    entry_point: getEntryPoint(),
    page_context: {
      page_name: pageName,
      url: window.location.href
    }
  };

  fetch('api/bot-controller.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'success') {
      botSessionToken = data.session_token;
      localStorage.setItem('globalways_bot_session', botSessionToken);
      currentLang = data.language_iso || 'en';

      // If user selected "Browse Independently" -> Collapse widget cleanly
      if (data.collapse_widget) {
        speakOutLoud(data.spoken_text);
        addMessageToStream('bot', data.display_text);
        setTimeout(() => {
          document.getElementById('botChatContainer').classList.remove('active');
        }, 3000);
        return;
      }

      // Check if client_action is requested for dynamic layout swap/hydration
      if (data.client_action) {
        handleClientAction(data.client_action);
      }

      // Render Bot response
      addMessageToStream('bot', data.display_text);
      speakOutLoud(data.spoken_text);
      renderOptions(data.next_options);
    }
  })
  .catch(err => console.error('Bot API connection error:', err));
}

function renderOptions(options) {
  const rack = document.getElementById('botOptionsRack');
  rack.innerHTML = '';
  if (!options || options.length === 0) return;

  options.forEach(opt => {
    const btn = document.createElement('button');
    btn.className = 'bot-option-btn';
    btn.innerText = opt.label;
    btn.onclick = () => {
      addMessageToStream('user', opt.label);
      sendQueryToController(opt.label, opt.node_id, opt.label);
    };
    rack.appendChild(btn);
  });
}

function addMessageToStream(sender, text) {
  const stream = document.getElementById('botChatStream');
  const bubble = document.createElement('div');
  bubble.className = `bot-message ${sender}`;
  bubble.innerText = text;
  stream.appendChild(bubble);
  stream.scrollTop = stream.scrollHeight;
}

function toggleSpeechInput() {
  if (isListening) {
    stopSpeechRecognition();
  } else {
    startSpeechRecognition();
  }
}

function startSpeechRecognition() {
  if (!recognition) return;
  const targetSpeechLang = langCodeMapping[currentLang] || 'en-US';
  recognition.lang = targetSpeechLang;
  recognition.start();
}

function stopSpeechRecognition() {
  if (!recognition) return;
  isListening = false;
  document.getElementById('botMicTrigger').classList.remove('listening');
  document.getElementById('botStatusText').innerText = 'Mic is offline';
  try {
    recognition.stop();
  } catch(e) {}
}

function speakOutLoud(text) {
  if (!('speechSynthesis' in window)) return;

  // Cancel previous voices
  window.speechSynthesis.cancel();

  const utterance = new SpeechSynthesisUtterance(text);
  const targetSpeechLang = langCodeMapping[currentLang] || 'en-US';
  utterance.lang = targetSpeechLang;

  // Attempt to select corresponding voice matching the language code
  const voices = window.speechSynthesis.getVoices();
  const matchedVoice = voices.find(v => v.lang.includes(targetSpeechLang));
  if (matchedVoice) {
    utterance.voice = matchedVoice;
  }

  window.speechSynthesis.speak(utterance);
}

function resetBot() {
  window.speechSynthesis.cancel();
  stopSpeechRecognition();
  sendQueryToController('', 1, 'Reset');
}

function handleClientAction(action) {
  if (action.type === 'page_swap' && action.url) {
    // Dynamic innerHTML swap to hydrate our main viewport element
    fetch(action.url)
    .then(r => r.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const newMain = doc.querySelector('#main-content-layout');
      if (newMain) {
        document.getElementById('main-content-layout').innerHTML = newMain.innerHTML;
        // Scroll to top safely
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    })
    .catch(err => console.error('Dynamic hydration failed:', err));
  }
}
</script>
