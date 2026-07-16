<?php
// bot-landing.php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db_mysqli.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$current_user = current_user();

// 1. Server-Side Context Verification: Read global bot_page_context tracking variables
$session_context = $_SESSION['bot_page_context'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GlobalWays® AI Workspace — Immersive Voice & Chat Assistant</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/globalways.css" rel="stylesheet">
  <style>
    :root {
      --ws-sidebar-width: 400px;
      --bot-primary: #1165ef;
      --bot-dark: #111827;
      --bot-light: #f3f4f6;
      --bot-bg: #ffffff;
      --bot-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
      --bot-border: #e5e7eb;
    }

    body, html {
      height: 100%;
      margin: 0;
      overflow: hidden;
      background-color: #f9fafb;
      font-family: system-ui, -apple-system, sans-serif;
    }

    /* Full Window Split Layout */
    .ws-container {
      display: flex;
      height: 100vh;
      width: 100vw;
    }

    /* Left Sidebar: Fixed 400px Chat & Controls */
    .ws-sidebar {
      width: var(--ws-sidebar-width);
      min-width: var(--ws-sidebar-width);
      max-width: var(--ws-sidebar-width);
      background-color: #ffffff;
      border-right: 1px solid var(--bot-border);
      display: flex;
      flex-direction: column;
      box-shadow: 4px 0 15px rgba(0, 0, 0, 0.02);
      z-index: 10;
    }

    /* Sidebar Header */
    .ws-sidebar-header {
      padding: 20px;
      border-bottom: 1px solid var(--bot-border);
      background-color: var(--bot-dark);
      color: #ffffff;
    }

    /* Sidebar Messages Viewport */
    .ws-chat-stream {
      flex: 1;
      overflow-y: auto;
      padding: 20px;
      background-color: #f9fafb;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    /* Option button racks */
    .ws-options-rack {
      padding: 16px;
      border-top: 1px solid var(--bot-border);
      background-color: #ffffff;
      display: flex;
      flex-direction: column;
      gap: 8px;
      max-height: 200px;
      overflow-y: auto;
    }

    /* Mic & Action controls */
    .ws-controls-area {
      padding: 16px 20px;
      border-top: 1px solid var(--bot-border);
      background-color: #ffffff;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    /* Right Main Panel: Flexible workspace */
    .ws-main-panel {
      flex: 1;
      overflow-y: auto;
      background-color: #f3f4f6;
      display: flex;
      flex-direction: column;
    }

    /* Inner Workspace Wrapper */
    .ws-workspace-container {
      flex: 1;
      padding: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Chat bubble styles */
    .ws-bubble {
      max-width: 85%;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 0.925rem;
      line-height: 1.45;
      word-wrap: break-word;
    }
    .ws-bubble.bot {
      background-color: #ffffff;
      color: var(--bot-dark);
      align-self: flex-start;
      border-bottom-left-radius: 4px;
      border: 1px solid var(--bot-border);
    }
    .ws-bubble.user {
      background-color: var(--bot-primary);
      color: #ffffff;
      align-self: flex-end;
      border-bottom-right-radius: 4px;
    }

    /* Mic button pulsing animations */
    .ws-mic-btn {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background-color: var(--bot-primary);
      color: #ffffff;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(17, 101, 239, 0.3);
      transition: all 0.2s ease-in-out;
      margin: 0 auto;
    }
    .ws-mic-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(17, 101, 239, 0.4);
    }
    .ws-mic-btn.listening {
      background-color: #ef4444;
      box-shadow: 0 6px 15px rgba(239, 68, 68, 0.4);
      animation: pulse-ws-mic 1.5s infinite;
    }

    @keyframes pulse-ws-mic {
      0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
      70% { transform: scale(1.08); box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
      100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    /* Onboarding default content card */
    .ws-default-card {
      background-color: #ffffff;
      border-radius: 20px;
      box-shadow: var(--bot-shadow);
      padding: 40px;
      max-width: 700px;
      text-align: center;
      border: 1px solid var(--bot-border);
    }

    .ws-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--bot-primary);
      background-color: rgba(17, 101, 239, 0.08);
      padding: 6px 12px;
      border-radius: 50px;
      margin-bottom: 20px;
    }

    .btn-ws-option {
      background-color: var(--bot-light);
      color: var(--bot-dark);
      border: 1px solid var(--bot-border);
      padding: 10px 14px;
      border-radius: 10px;
      font-size: 0.875rem;
      text-align: left;
      cursor: pointer;
      transition: all 0.15s ease;
      font-weight: 500;
    }
    .btn-ws-option:hover {
      background-color: var(--bot-primary);
      color: #ffffff;
      border-color: var(--bot-primary);
    }
  </style>
</head>
<body>

<!-- Global context object exported from server to javascript on page load -->
<script>
  window.botPageContext = <?php echo json_encode($session_context); ?>;
</script>

<div class="ws-container">
  <!-- Left Column: Chat & Speech controls -->
  <aside class="ws-sidebar">
    <div class="ws-sidebar-header">
      <div class="d-flex align-items-center justify-content-between">
        <h2 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
          <i class="bi bi-cpu-fill"></i> GlobalWays® AI Workspace
        </h2>
        <span class="badge bg-success rounded-pill px-2">Voice Live</span>
      </div>
    </div>

    <!-- Active Chat Stream Viewport -->
    <div class="ws-chat-stream" id="wsChatStream">
      <!-- Conversation history loads here -->
    </div>

    <!-- Interaction options rack -->
    <div class="ws-options-rack" id="wsOptionsRack">
      <!-- Response Options -->
    </div>

    <!-- Controls & Microphone area -->
    <div class="ws-controls-area">
      <div class="text-center">
        <button class="ws-mic-btn" id="wsMicTrigger" onclick="toggleSpeechInput()">
          <i class="bi bi-mic-fill" id="wsMicIcon"></i>
        </button>
        <div class="small text-muted mt-2 fw-medium" id="wsStatusText">Mic is offline</div>
      </div>

      <div class="d-flex flex-column gap-2 mt-2">
        <button class="btn btn-outline-danger btn-sm w-100 py-2 rounded-pill" onclick="resetWorkspace()">
          <i class="bi bi-arrow-counterclockwise"></i> 🔄 Start Completely Fresh
        </button>
        <a href="index.php" class="btn btn-dark btn-sm w-100 py-2 rounded-pill text-decoration-none text-center">
          <i class="bi bi-box-arrow-left"></i> Exit AI Workspace & Return to Home
        </a>
      </div>
    </div>
  </aside>

  <!-- Right Column: Interactive preview dashboard container -->
  <main class="ws-main-panel">
    <!-- Quick Workspace Header bar -->
    <div class="bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between">
      <div class="small fw-semibold text-secondary">
        Interactive Layout Helper Context: <span id="wsContextPage">None</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="status-dot"></span>
        <span class="small font-mono text-muted text-uppercase" style="font-size: 0.65rem">All Pipelines Operational</span>
      </div>
    </div>

    <div class="ws-workspace-container" id="bot-workspace-view">
      <!-- Dynamic workspace content hydrates here -->
      <div class="ws-default-card fade-in">
        <div class="ws-badge">
          <i class="bi bi-robot"></i> Immersive Experience
        </div>
        <h1 class="font-serif mb-3 text-gradient-blue" style="font-size: 2.2rem">Welcome to the AI Workspace</h1>
        <p class="text-secondary mb-4">
          Experience our ultra-secure, multilingual voice and chat guidance in real-time. Speak into your microphone or click conversational buttons to explore categories, discover similar vendors, check durations, or configure UAE services.
        </p>
        <img src="assets/logo.png" alt="globalways" style="height: 36px; opacity: 0.8;">
      </div>
    </div>
  </main>
</div>

<script>
let botSessionToken = localStorage.getItem('globalways_bot_session') || '';
let currentLang = 'en';
let isListening = false;
let recognition = null;

const langCodeMapping = {
  'en': 'en-US',
  'fr': 'fr-FR',
  'ar': 'ar-SA',
  'ur': 'ur-PK'
};

document.addEventListener('DOMContentLoaded', () => {
  // Sync state and resume if active session exists in LocalStorage or URL
  const urlParams = new URLSearchParams(window.location.search);
  const urlToken = urlParams.get('session_token');
  const urlLang = urlParams.get('language');

  if (urlToken) {
    botSessionToken = urlToken;
    localStorage.setItem('globalways_bot_session', botSessionToken);
  }
  if (urlLang) {
    currentLang = urlLang;
  }

  // 2. Immediate Panel Pre-Hydration: Check if user arrived from a specific page and pre-hydrate
  if (window.botPageContext && window.botPageContext.page_name) {
    const page = window.botPageContext.page_name;
    let preHydrateUrl = '';

    if (page === 'vendor-profile.php' && window.botPageContext.vendor_uuid) {
      preHydrateUrl = 'vendor-profile.php?id=' + encodeURIComponent(window.botPageContext.vendor_uuid);
    } else if (page === 'service-detail.php' && window.botPageContext.service_slug) {
      preHydrateUrl = 'service-detail.php?id=' + encodeURIComponent(window.botPageContext.service_slug);
    } else if (page === 'services.php') {
      preHydrateUrl = 'services.php';
    } else if (page === 'vendors.php') {
      preHydrateUrl = 'vendors.php';
    }

    if (preHydrateUrl) {
      document.getElementById('wsContextPage').innerText = `Pre-Hydrating Layout (${page})...`;
      fetch(preHydrateUrl)
        .then(response => response.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newMain = doc.querySelector('#main-content-layout');
          if (newMain) {
            document.getElementById('bot-workspace-view').innerHTML = newMain.innerHTML;
            document.getElementById('wsContextPage').innerText = `${page} (Synchronized)`;
          }
        })
        .catch(err => {
          console.error('Immediate layout pre-hydration failed:', err);
          document.getElementById('wsContextPage').innerText = `Failed to pre-hydrate (${page})`;
        });
    }
  }

  // Initialize Speech components
  if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = false;

    recognition.onstart = () => {
      isListening = true;
      document.getElementById('wsMicTrigger').classList.add('listening');
      document.getElementById('wsStatusText').innerText = 'Listening... Speak now';
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
    document.getElementById('wsMicTrigger').style.display = 'none';
    document.getElementById('wsStatusText').innerText = 'Speech-to-text not supported';
  }

  // Sync / Resume exact conversational nodes
  if (window.botPageContext && window.botPageContext.page_name) {
    // 3. Contextual Dialogue Handshake: Bypasses generic welcome, transmits contextual data immediately
    sendQueryToController('', null, '', true);
  } else if (botSessionToken) {
    sendQueryToController('', null, '');
  } else {
    sendQueryToController('', 1, '');
  }
});

function sendQueryToController(messageText, nodeId = null, userInputText = '', forceBadgeContext = false) {
  let pageName = 'bot-landing.php';
  let payload = {
    session_token: botSessionToken,
    node_id: nodeId,
    message: userInputText || messageText,
    entry_point: 'immersive_landing',
    page_context: {
      page_name: pageName,
      url: window.location.href
    }
  };

  if (forceBadgeContext && window.botPageContext) {
    payload.badge_click = true;
    payload.page_context = array_merge_payloads(payload.page_context, window.botPageContext);
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

      // Update workspace context preview bar
      const contextLabel = window.botPageContext ? window.botPageContext.page_name : 'General';
      document.getElementById('wsContextPage').innerText = `${contextLabel} (Active: ${currentLang.toUpperCase()})`;

      // Render Bot outcome message
      addMessageToStream('bot', data.display_text);
      speakOutLoud(data.display_text);
      renderOptions(data.next_options);

      // Handle client actions such as hydrating right panel
      if (data.client_action) {
        handleClientAction(data.client_action);
      }
    }
  })
  .catch(err => console.error('Workspace controller connection failure:', err));
}

function array_merge_payloads(obj1, obj2) {
  return Object.assign({}, obj1, obj2);
}

function renderOptions(options) {
  const rack = document.getElementById('wsOptionsRack');
  rack.innerHTML = '';
  if (!options || options.length === 0) return;

  options.forEach(opt => {
    const btn = document.createElement('button');
    btn.className = 'btn-ws-option';
    btn.innerText = opt.label;
    btn.onclick = () => {
      addMessageToStream('user', opt.label);
      sendQueryToController(opt.label, opt.node_id, opt.label);
    };
    rack.appendChild(btn);
  });
}

function addMessageToStream(sender, text) {
  const stream = document.getElementById('wsChatStream');
  const bubble = document.createElement('div');
  bubble.className = `ws-bubble ${sender}`;
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
  document.getElementById('wsMicTrigger').classList.remove('listening');
  document.getElementById('wsStatusText').innerText = 'Mic is offline';
  try {
    recognition.stop();
  } catch(e) {}
}

function speakOutLoud(text) {
  if (!('speechSynthesis' in window)) return;

  window.speechSynthesis.cancel();

  const utterance = new SpeechSynthesisUtterance(text);
  const targetSpeechLang = langCodeMapping[currentLang] || 'en-US';
  utterance.lang = targetSpeechLang;

  const voices = window.speechSynthesis.getVoices();
  const matchedVoice = voices.find(v => v.lang.includes(targetSpeechLang));
  if (matchedVoice) {
    utterance.voice = matchedVoice;
  }

  window.speechSynthesis.speak(utterance);
}

function resetWorkspace() {
  window.speechSynthesis.cancel();
  stopSpeechRecognition();
  document.getElementById('wsChatStream').innerHTML = '';
  // Purge to language selector menu
  sendQueryToController('', 1, 'Reset');
}

function handleClientAction(action) {
  if (action.type === 'page_swap' && action.url) {
    // Hydrate the right workspace view panel asynchronously
    fetch(action.url)
    .then(r => r.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const newMain = doc.querySelector('#main-content-layout');
      if (newMain) {
        document.getElementById('bot-workspace-view').innerHTML = newMain.innerHTML;
      }
    })
    .catch(err => console.error('Asynchronous workspace hydration failed:', err));
  }
}
</script>

</body>
</html>
