<?php
// bot-landing.php
require_once __DIR__ . '/lib/db_mysqli.php';
// Check global AI Kill-Switch status
$ai_global_status = 'enabled';
if (isset($mysqli) && !$mysqli->connect_errno) {
    $stmt_kill = $mysqli->prepare("SELECT `value` FROM `site_settings` WHERE `key` = 'ai_bot_global_status' LIMIT 1");
    if ($stmt_kill) {
        $stmt_kill->execute();
        $res_kill = $stmt_kill->get_result();
        if ($row_kill = $res_kill->fetch_assoc()) {
            $ai_global_status = $row_kill['value'];
        }
        $stmt_kill->close();
    }
}

if ($ai_global_status === 'disabled') {
    http_response_code(403);
    header('HTTP/1.1 403 Forbidden');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>AI Workspace Offline — GlobalWays®</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
      <style>
        body {
          background-color: #f9fafb;
          font-family: system-ui, -apple-system, sans-serif;
          height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0;
        }
        .maintenance-card {
          background-color: #ffffff;
          border-radius: 20px;
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
          padding: 40px;
          max-width: 500px;
          text-align: center;
          border: 1px solid #e5e7eb;
        }
        .icon-box {
          width: 80px;
          height: 80px;
          border-radius: 50%;
          background-color: #fef3c7;
          color: #d97706;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 2.5rem;
          margin: 0 auto 24px;
        }
      </style>
    </head>
    <body>
      <div class="maintenance-card">
        <div class="icon-box">
          <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h1 class="h4 fw-bold text-dark mb-3">AI Workspace Offline</h1>
        <p class="text-secondary mb-4">
          The AI Assistant is currently undergoing routine maintenance. Please browse our website manually.
        </p>
        <div class="d-grid">
          <a href="index.php" class="btn btn-primary py-2.5 rounded-pill fw-medium">
            <i class="bi bi-house-door-fill me-1"></i> Return to Homepage
          </a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . '/lib/auth.php';

if (!isset($cspNonce)) {
    $cspNonce = base64_encode(random_bytes(16));
}

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

    /* SVG Waveform transitions */
    .wave-bar {
      transform-origin: center;
      transition: height 0.1s ease-in-out, y 0.1s ease-in-out;
    }

    @keyframes pulse-shimmer {
      0% { opacity: 0.6; }
      50% { opacity: 1; }
      100% { opacity: 0.6; }
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
        <button class="ws-mic-btn" id="wsMicTrigger">
          <i class="bi bi-mic-fill" id="wsMicIcon"></i>
        </button>

        <!-- Inline Animated SVG Audio Waveform Indicator Panel -->
        <div class="waveform-container d-flex align-items-center justify-content-center gap-1 my-2" style="height: 30px;">
          <svg id="waveformSvg" width="120" height="30" viewBox="0 0 120 30" style="display: none;">
            <rect class="wave-bar bar-1" x="10" y="12" width="6" height="6" rx="3" fill="#1165ef" />
            <rect class="wave-bar bar-2" x="26" y="12" width="6" height="6" rx="3" fill="#38bdf8" />
            <rect class="wave-bar bar-3" x="42" y="12" width="6" height="6" rx="3" fill="#10b981" />
            <rect class="wave-bar bar-4" x="58" y="12" width="6" height="6" rx="3" fill="#38bdf8" />
            <rect class="wave-bar bar-5" x="74" y="12" width="6" height="6" rx="3" fill="#1165ef" />
            <rect class="wave-bar bar-6" x="90" y="12" width="6" height="6" rx="3" fill="#10b981" />
            <rect class="wave-bar bar-7" x="106" y="12" width="6" height="6" rx="3" fill="#1165ef" />
          </svg>
        </div>

        <div class="small text-muted mt-2 fw-medium" id="wsStatusText">Mic is offline</div>
      </div>

      <div class="d-flex flex-column gap-2 mt-2">
        <button class="btn btn-outline-danger btn-sm w-100 py-2 rounded-pill" id="wsResetTrigger">
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

<script nonce="<?php echo $cspNonce;?>">
let botSessionToken = localStorage.getItem('globalways_bot_session') || '';
let currentLang = localStorage.getItem('globalways_bot_language') || 'en';
let isListening = false;
let recognition = null;
let activeSpeaker = null;
let pausedForSpeaking = false;
let activeOptions = [];

// Web Audio API Context variables
let audioCtx = null;
let analyser = null;
let dataArray = null;
let mediaStreamSource = null;
let animationFrameId = null;
let audioStream = null;

const langCodeMapping = {
  'en': 'en-US',
  'fr': 'fr-FR',
  'ar': 'ar-SA',
  'ur': 'ur-PK'
};

// URL validation for local routes
function isLocalRoute(url) {
  if (!url) return false;
  try {
    const parsed = new URL(url, window.location.origin);
    return parsed.origin === window.location.origin;
  } catch (e) {
    return !url.includes('://');
  }
}

// LocalStorage helpers for chat stream HTML persistence
function saveChatStreamToLocalStorage() {
  const stream = document.getElementById('wsChatStream');
  if (stream) {
    localStorage.setItem('globalways_chat_stream_html', stream.innerHTML);
  }
}

function loadChatStreamFromLocalStorage() {
  const stream = document.getElementById('wsChatStream');
  const cachedHtml = localStorage.getItem('globalways_chat_stream_html');
  if (stream && cachedHtml) {
    stream.innerHTML = cachedHtml;
    stream.scrollTop = stream.scrollHeight;
  }
}

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

  // Load context stream from LocalStorage to bypass sequential API lookups on refresh
  loadChatStreamFromLocalStorage();

  // Bind the mic and reset buttons cleanly using the exact architectural syntax pattern
  (function() {
      const targetElement = document.getElementById("wsMicTrigger");
      if (targetElement) {
          targetElement.onclick = toggleSpeechInput;
      }
  })();

  (function() {
      const targetElement = document.getElementById("wsResetTrigger");
      if (targetElement) {
          targetElement.onclick = resetWorkspace;
      }
  })();

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

      // Show waveform SVG
      const wave = document.getElementById('waveformSvg');
      if (wave) {
        wave.style.display = 'block';
      }

      // Initialize Web Audio API Analyser for real-time visualization of mic stream
      navigator.mediaDevices.getUserMedia({ audio: true })
        .then(stream => {
          audioStream = stream;
          startAudioVisualization(stream);
        })
        .catch(err => console.error('Microphone visualizer acquisition failed:', err));
    };

    recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript;

      // 1. Spoken Language Selection Parsing
      const matchedLang = parseSpokenLanguage(transcript);
      if (matchedLang) {
        const clicked = findAndClickLanguageButton(matchedLang);
        if (clicked) {
          return;
        }
      }

      // 2. Voice-Driven Option/Dynamic Menu Selection
      const matchedOptionBtn = findMatchingOption(transcript);
      if (matchedOptionBtn) {
        matchedOptionBtn.click();
        return;
      }

      addMessageToStream('user', transcript);
      sendQueryToController('', null, transcript);
    };

    recognition.onerror = (event) => {
      console.error('Speech recognition error:', event.error);
      if (event.error !== 'no-speech') {
        stopSpeechRecognition();
      }
    };

    recognition.onend = () => {
      if (isListening && !pausedForSpeaking) {
        try {
          recognition.start();
        } catch (e) {
          console.error('Failed to auto-restart recognition:', e);
        }
      } else if (!pausedForSpeaking) {
        stopSpeechRecognition();
      }
    };
  } else {
    document.getElementById('wsMicTrigger').style.display = 'none';
    document.getElementById('wsStatusText').innerText = 'Speech-to-text not supported';
  }

  // Auth Synchronization Listener
  const pendingState = localStorage.getItem('pending_active_state_token');
  const pendingContext = localStorage.getItem('pending_vendor_context');

  if (pendingState && pendingContext && <?php echo isset($_SESSION['user']['id']) ? 'true' : 'false'; ?>) {
      localStorage.removeItem('pending_active_state_token');
      localStorage.removeItem('pending_vendor_context');
      sendQueryToController('', null, '', false, pendingState);
  } else {
      // Sync / Resume exact conversational nodes
      if (window.botPageContext && window.botPageContext.page_name) {
        sendQueryToController('', null, '', true);
      } else if (botSessionToken) {
        sendQueryToController('', null, '');
      } else {
        sendQueryToController('', 1, '');
      }
  }
});

function startAudioVisualization(stream) {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;

    audioCtx = new AudioContext();
    analyser = audioCtx.createAnalyser();
    analyser.fftSize = 32;
    const bufferLength = analyser.frequencyBinCount;
    dataArray = new Uint8Array(bufferLength);

    mediaStreamSource = audioCtx.createMediaStreamSource(stream);
    mediaStreamSource.connect(analyser);

    const bars = document.querySelectorAll('#waveformSvg rect');

    function draw() {
      if (!isListening) return;
      animationFrameId = requestAnimationFrame(draw);

      analyser.getByteFrequencyData(dataArray);

      for (let i = 0; i < bars.length; i++) {
        const val = dataArray[i] || 0;
        const percent = val / 255;
        const newHeight = 4 + (percent * 22);
        const newY = 15 - (newHeight / 2);

        bars[i].setAttribute('height', newHeight);
        bars[i].setAttribute('y', newY);
      }
    }

    draw();
  } catch (e) {
    console.error('Web Audio API Initialization error:', e);
  }
}

function stopAudioVisualization() {
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
    animationFrameId = null;
  }
  if (mediaStreamSource) {
    mediaStreamSource.disconnect();
    mediaStreamSource = null;
  }
  if (audioCtx) {
    audioCtx.close();
    audioCtx = null;
  }
  if (audioStream) {
    audioStream.getTracks().forEach(track => track.stop());
    audioStream = null;
  }

  // Reset bars back to original state
  const bars = document.querySelectorAll('#waveformSvg rect');
  bars.forEach(bar => {
    bar.setAttribute('height', 6);
    bar.setAttribute('y', 12);
  });
}

function sendQueryToController(messageText, nodeId = null, userInputText = '', forceBadgeContext = false, stepKey = null) {
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

  if (stepKey) {
    payload.step_key = stepKey;
  }

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

  // State Lifecycle Management: Completely clear out and purge the stored active voice keywords
  activeOptions = [];

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

    // Store for voice-driven matching
    activeOptions.push({
      label: opt.label.toLowerCase(),
      element: btn
    });
  });
}

function addMessageToStream(sender, text) {
  const stream = document.getElementById('wsChatStream');
  const bubble = document.createElement('div');
  bubble.className = `ws-bubble ${sender}`;
  bubble.innerText = text;
  stream.appendChild(bubble);
  stream.scrollTop = stream.scrollHeight;
  saveChatStreamToLocalStorage();
}

function toggleSpeechInput() {
  if (isListening) {
    isListening = false;
    stopSpeechRecognition();
  } else {
    isListening = true;
    startSpeechRecognition();
  }
}

function startSpeechRecognition() {
  if (!recognition) return;
  const targetSpeechLang = langCodeMapping[currentLang] || 'en-US';
  recognition.lang = targetSpeechLang;
  try {
    recognition.start();
  } catch(e) {
    console.error('Recognition start error:', e);
  }
}

function stopSpeechRecognition() {
  if (!recognition) return;
  if (!pausedForSpeaking) {
    isListening = false;
    document.getElementById('wsMicTrigger').classList.remove('listening');
    document.getElementById('wsStatusText').innerText = 'Mic is offline';

    // Hide waveform SVG and stop animations
    const wave = document.getElementById('waveformSvg');
    if (wave) {
      wave.style.display = 'none';
    }

    stopAudioVisualization();
  }

  try {
    recognition.stop();
  } catch(e) {}
}

function speakNativeSpeech(text, callback) {
  if (!('speechSynthesis' in window)) {
    if (callback) callback();
    return;
  }
  window.speechSynthesis.cancel();
  const utterance = new SpeechSynthesisUtterance(text);
  const targetSpeechLang = langCodeMapping[currentLang] || 'en-US';
  utterance.lang = targetSpeechLang;
  const voices = window.speechSynthesis.getVoices();
  const matchedVoice = voices.find(v => v.lang.includes(targetSpeechLang));
  if (matchedVoice) {
    utterance.voice = matchedVoice;
  }
  utterance.onend = callback;
  utterance.onerror = callback;
  window.speechSynthesis.speak(utterance);
}

function speakOutLoud(text) {
  // Cancel previous playbacks
  if (activeSpeaker) {
    try { activeSpeaker.pause(); } catch(e) {}
    activeSpeaker = null;
  }
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel();
  }

  const wasListening = isListening;
  if (isListening) {
    pausedForSpeaking = true;
    try {
      recognition.stop();
    } catch(e) {}
    document.getElementById('wsStatusText').innerText = 'Assistant is speaking...';
  }

  const resumeRecognition = () => {
    if (wasListening) {
      pausedForSpeaking = false;
      isListening = true;
      startSpeechRecognition();
    } else {
      document.getElementById('wsStatusText').innerText = 'Mic is offline';
    }
  };

  // Try Premium ElevenLabs TTS Pipeline via local endpoint
  fetch('api/tts-processor.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ text: text })
  })
  .then(async response => {
    const contentType = response.headers.get('content-type') || '';
    if (response.ok && contentType.includes('audio/')) {
      const blob = await response.blob();
      const audioUrl = URL.createObjectURL(blob);
      activeSpeaker = new Audio(audioUrl);
      activeSpeaker.onended = resumeRecognition;
      activeSpeaker.onerror = () => {
        speakNativeSpeech(text, resumeRecognition);
      };
      activeSpeaker.play().catch(err => {
        console.warn('Playback block or error, falling back:', err);
        speakNativeSpeech(text, resumeRecognition);
      });
    } else {
      // JSON response indicating fallback
      speakNativeSpeech(text, resumeRecognition);
    }
  })
  .catch(err => {
    console.warn('ElevenLabs API fetch error, falling back:', err);
    speakNativeSpeech(text, resumeRecognition);
  });
}

function resetWorkspace() {
  window.speechSynthesis.cancel();
  stopSpeechRecognition();
  activeOptions = [];
  document.getElementById('wsChatStream').innerHTML = '';
  localStorage.removeItem('globalways_chat_stream_html');
  localStorage.removeItem('globalways_bot_session');
  localStorage.removeItem('globalways_bot_language');
  botSessionToken = '';
  currentLang = 'en';
  sendQueryToController('', 1, 'Reset');
}

function parseSpokenLanguage(transcript) {
  const text = transcript.toLowerCase().trim();

  const enPatterns = [
    /\benglish\b/, /\bselect english\b/,
    /\banglais\b/, /\bselectionner l'anglais\b/, /\bselectionner anglais\b/,
    /الانجليزية/, /الإنجليزية/, /انجليزي/, /إنجليزي/, /اختر الانجليزية/, /اختر الإنجليزية/,
    /انگریزی/, /انگلش/, /انگریزی منتخب کریں/, /अंग्रेजी/, /इंग्लिश/, /अंग्रेजी चुनें/
  ];

  const frPatterns = [
    /\bfrench\b/, /\bselect french\b/,
    /\bfrançais\b/, /\bfrancais\b/, /\bselectionner le français\b/, /\bselectionner le francais\b/, /\bselectionner français\b/, /\bselectionner francais\b/,
    /الفرنسية/, /فرنسي/, /اختر الفرنسية/,
    /فرانسیسی/, /فرانسیسی منتخب کریں/, /फ्रेंच/, /फ्रांसीसी/, /फ्रेंच चुनें/
  ];

  const arPatterns = [
    /\barabic\b/, /\bselect arabic\b/,
    /\barabe\b/, /\bselectionner l'arabe\b/, /\bselectionner l'arabe\b/, /\bselectionner arabe\b/,
    /العربية/, /عربي/, /اختر العربية/,
    /عربی/, /عربی منتخب کریں/, /अरबी/, /अरबी चुनें/
  ];

  const urPatterns = [
    /\burdu\b/, /\bhindi\b/, /\bselect urdu\b/, /\bselect hindi\b/, /\burdu hindi\b/,
    /\bourdou\b/, /\bselectionner l'ourdou\b/, /\bourdou hindi\b/,
    /الأردية/, /الأردو/, /اختر الأردية/,
    /اردو/, /ہندی/, /اردو منتخب کریں/, /उर्दू/, /हिंदी/, /उर्दू चुनें/, /हिंदी चुनें/
  ];

  for (let pattern of enPatterns) {
    if (pattern.test(text)) return 'en';
  }
  for (let pattern of frPatterns) {
    if (pattern.test(text)) return 'fr';
  }
  for (let pattern of arPatterns) {
    if (pattern.test(text)) return 'ar';
  }
  for (let pattern of urPatterns) {
    if (pattern.test(text)) return 'ur';
  }

  return null;
}

function findAndClickLanguageButton(langCode) {
  const rack = document.getElementById('wsOptionsRack');
  if (!rack) return false;
  const buttons = rack.querySelectorAll('button');
  for (let btn of buttons) {
    const text = btn.innerText.toLowerCase();
    if (langCode === 'en' && text.includes('english')) {
      btn.click();
      return true;
    }
    if (langCode === 'fr' && (text.includes('français') || text.includes('francais') || text.includes('french'))) {
      btn.click();
      return true;
    }
    if (langCode === 'ar' && (text.includes('العربية') || text.includes('arabic') || text.includes('عربي'))) {
      btn.click();
      return true;
    }
    if (langCode === 'ur' && (text.includes('اردو') || text.includes('urdu') || text.includes('हिंदी') || text.includes('hindi'))) {
      btn.click();
      return true;
    }
  }
  return false;
}

function findMatchingOption(transcript) {
  const normalizedTranscript = transcript.toLowerCase().trim();

  for (let opt of activeOptions) {
    const label = opt.label;

    // Direct or substring match
    if (label.includes(normalizedTranscript) || normalizedTranscript.includes(label)) {
      return opt.element;
    }

    const transcriptWords = normalizedTranscript.split(/\s+/).filter(w => w.length > 3);
    const labelWords = label.split(/\s+/).filter(w => w.length > 3);

    for (let tw of transcriptWords) {
      if (labelWords.includes(tw) || label.includes(tw)) {
        return opt.element;
      }
    }

    for (let lw of labelWords) {
      if (transcriptWords.includes(lw) || normalizedTranscript.includes(lw)) {
        return opt.element;
      }
    }
  }
  return null;
}

function handleClientAction(action) {
  if (!action) return;

  if (action.type === 'redirect_auth' && action.url) {
      localStorage.setItem('pending_active_state_token', action.state_token || 'category_selection');
      localStorage.setItem('pending_vendor_context', JSON.stringify(window.botPageContext || {}));
      window.location.href = action.url;
      return;
  }

  if (action.type === 'toast_success') {
      let toastContainer = document.getElementById('toastNotificationOverlay');
      if (!toastContainer) {
          toastContainer = document.createElement('div');
          toastContainer.id = 'toastNotificationOverlay';
          toastContainer.className = 'position-fixed top-0 end-0 p-3';
          toastContainer.style.zIndex = '9999';
          document.body.appendChild(toastContainer);
      }
      toastContainer.innerHTML = `
          <div class="toast show align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
              <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i> ${action.message || 'Action completed successfully!'}
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
          </div>
      `;
      setTimeout(() => {
          if (toastContainer) {
              toastContainer.remove();
          }
      }, 5000);
      return;
  }

  if (action.type === 'apply_filters' && action.url) {
       fetch(action.url)
       .then(r => r.text())
       .then(html => {
         const parser = new DOMParser();
         const doc = parser.parseFromString(html, 'text/html');
         const newMain = doc.querySelector('#main-content-layout') || doc.body;
         if (newMain) {
           document.getElementById('bot-workspace-view').innerHTML = newMain.innerHTML;
           const filterVal = (action.category_name || '').toLowerCase();
           const cards = document.getElementById('bot-workspace-view').querySelectorAll('.card, .gw-card, .service-card');
           cards.forEach(card => {
               if (filterVal && !card.innerText.toLowerCase().includes(filterVal)) {
                   card.style.opacity = '0.4';
               } else {
                   card.style.border = '2px solid var(--bot-primary)';
               }
           });
         }
       })
       .catch(err => console.error('Filter hydration failed:', err));
       return;
  }

  if (action.type === 'page_swap' && action.url) {
    // 3. Restrict handleClientAction page swapping scripts to local routes to prevent redirect hijacking
    if (!isLocalRoute(action.url)) {
      console.warn('Rejected non-local page swap route:', action.url);
      return;
    }

    // Show premium CSS pulsing skeleton loaders to eliminate Cumulative Layout Shift (CLS)
    document.getElementById('bot-workspace-view').innerHTML = `
      <div class="w-100 p-4" style="max-width: 800px;">
        <div class="shimmer-card p-4 bg-white border rounded-4 mb-4" style="animation: pulse-shimmer 1.5s infinite ease-in-out;">
          <div class="shimmer-line bg-secondary bg-opacity-10 rounded-pill mb-3" style="width: 40%; height: 24px;"></div>
          <div class="shimmer-line bg-secondary bg-opacity-10 rounded-pill mb-2" style="width: 90%; height: 16px;"></div>
          <div class="shimmer-line bg-secondary bg-opacity-10 rounded-pill mb-2" style="width: 80%; height: 16px;"></div>
          <div class="shimmer-line bg-secondary bg-opacity-10 rounded-pill mb-4" style="width: 50%; height: 16px;"></div>
          <div class="d-flex gap-3">
            <div class="shimmer-btn bg-secondary bg-opacity-10 rounded-pill" style="width: 120px; height: 38px;"></div>
            <div class="shimmer-btn bg-secondary bg-opacity-10 rounded-pill" style="width: 120px; height: 38px;"></div>
          </div>
        </div>
      </div>
    `;

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
