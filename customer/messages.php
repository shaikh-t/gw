<?php
// customer/messages.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/permissions.php';
require_once __DIR__ . '/../lib/customer_helpers.php';
require_once __DIR__ . '/../lib/csrf.php';

require_login();

// Guard access: customer role only
if (is_role('provider') || is_role('admin') || is_role('Super Admin')) {
    header('Location: ../login.php');
    exit;
}

$user = current_user();
$userId = (int)$user['id'];

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_text'])) {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $text = trim($_POST['message_text']);
    if ($text !== '') {
        add_customer_message($userId, $text, 'You', 'Golden Visa');

        // Dynamic automated reply simulated for flawless demo experience!
        $lower_text = strtolower($text);
        $reply = '';
        if (str_contains($lower_text, 'hello') || str_contains($lower_text, 'hi')) {
            $reply = 'Hello! Thank you for messaging us. How can we help you with your Golden Visa today?';
        } else if (str_contains($lower_text, 'status') || str_contains($lower_text, 'update')) {
            $reply = 'Your Golden Visa application is currently in progress. We are scheduled for the Medical Test milestone next.';
        } else if (str_contains($lower_text, 'price') || str_contains($lower_text, 'cost')) {
            $reply = 'The total amount is AED 5,000. You have already paid AED 2,500. The remaining balance is AED 2,500.';
        } else {
            $reply = 'Thank you for your message! Our representative from Emirates Pro Services will review this and get back to you shortly.';
        }
        add_customer_message($userId, $reply, 'Emirates Pro Services', 'Golden Visa');
    }
}

// Fetch dynamic messages
$messages = get_customer_messages($userId);

// Unread messages count
$unread_msgs_count = 0;
foreach ($messages as $m) {
    if ($m['sender'] !== 'You') {
        $unread_msgs_count++;
    }
}

// Generate initials
$initials = '';
$words = explode(' ', $user['name'] ?? 'Customer');
foreach ($words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
if (empty($initials)) $initials = 'CU';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages — GlobalWays Customer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/globalways.css" rel="stylesheet">
</head>
<body class="bg-warm">
  <div class="dashboard-wrapper d-flex">
    <aside class="dashboard-sidebar d-flex flex-column">
      <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex align-items-center justify-content-between">
        <a href="../index.php" class="text-decoration-none d-flex align-items-center gap-2">
          <div class="rounded-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:linear-gradient(135deg,#1165EF,#3F83F4)"><i class="bi bi-globe2 text-white small"></i></div>
          <div><div class="text-white font-serif small">GlobalWays</div><div class="font-mono text-uppercase" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.4)">Customer Portal</div></div>
        </a>
        <button class="btn btn-link text-white-50 p-0 d-lg-none" data-sidebar-close><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="p-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:rgba(255,255,255,.05)">
          <span class="avatar-circle bg-dark border border-secondary"><?= htmlspecialchars($initials) ?></span>
          <div class="min-w-0"><div class="text-white small fw-semibold text-truncate"><?= htmlspecialchars($user['name']) ?></div><div class="font-mono text-truncate" style="font-size:10px;color:rgba(255,255,255,.4)"><?= htmlspecialchars($user['email']) ?></div></div>
        </div>
      </div>
      <nav class="nav flex-column p-3 gap-1 flex-grow-1">
        <div class="font-mono text-uppercase px-2 mb-2" style="font-size:9px;letter-spacing:.15em;color:rgba(255,255,255,.25)">Menu</div>
        <a class="nav-link" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="applications.php"><i class="bi bi-file-earmark-text"></i> Applications</a>
        <a class="nav-link" href="documents.php"><i class="bi bi-folder2-open"></i> Documents</a>
        <a class="nav-link active" href="messages.php"><i class="bi bi-chat-dots"></i> Messages <span class="badge rounded-pill"><?= $unread_msgs_count ?></span></a>
        <a class="nav-link" href="payments.php"><i class="bi bi-credit-card"></i> Payments</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
      </nav>
      <div class="p-3 border-top border-secondary border-opacity-25"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></div>
    </aside>
    <div class="sidebar-backdrop"></div>
    <div class="dashboard-main">
      <header class="dashboard-topbar d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
          <button class="btn btn-light d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button>
          <div class="cp-search d-none d-sm-flex">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Search applications, documents..">
          </div>
        </div>
        <div class="cp-top-actions">
          <button class="cp-bell" type="button" aria-label="Notifications"><i class="bi bi-bell"></i><span class="cp-dot"></span></button>
          <span class="avatar-circle bg-dark"><?= htmlspecialchars($initials) ?></span>
        </div>
      </header>
      <main class="cp-page">
        <h1 class="cp-page-title"><span class="text-gradient-blue">Messages</span></h1>
        <p class="cp-page-sub">Chat with vendors and support team</p>

        <div class="cp-msg-layout mt-4">
          <div class="cp-msg-list">
            <div class="cp-msg-search">
              <i class="bi bi-search text-muted"></i>
              <input type="search" placeholder="Search messages...">
            </div>

            <a href="#" class="cp-thread active">
              <div class="cp-thread-top">
                <span class="cp-thread-avatar">EP<span class="online"></span></span>
                <div class="min-w-0 flex-grow-1">
                  <p class="cp-thread-name">Emirates Pro Services</p>
                  <p class="cp-thread-svc">Golden Visa</p>
                  <p class="cp-thread-preview">Your medical test has been scheduled</p>
                </div>
                <div class="cp-thread-meta">
                  <span class="cp-thread-time">2 hours ago</span>
                  <span class="cp-thread-badge"><?= $unread_msgs_count ?></span>
                </div>
              </div>
            </a>

            <a href="#" class="cp-thread">
              <div class="cp-thread-top">
                <span class="cp-thread-avatar">FT</span>
                <div class="min-w-0 flex-grow-1">
                  <p class="cp-thread-name">FastTrack Visa Services</p>
                  <p class="cp-thread-svc">Family Visa</p>
                  <p class="cp-thread-preview">Please upload the passport copy</p>
                </div>
                <div class="cp-thread-meta">
                  <span class="cp-thread-time">1 day ago</span>
                </div>
              </div>
            </a>

            <a href="#" class="cp-thread">
              <div class="cp-thread-top">
                <span class="cp-thread-avatar">UD</span>
                <div class="min-w-0 flex-grow-1">
                  <p class="cp-thread-name">UAE Docs Hub Support</p>
                  <p class="cp-thread-svc">General Support</p>
                  <p class="cp-thread-preview">How can we help you today?</p>
                </div>
                <div class="cp-thread-meta">
                  <span class="cp-thread-time">3 days ago</span>
                </div>
              </div>
            </a>
          </div>

          <div class="cp-chat">
            <button type="button" class="cp-chat-back"><i class="bi bi-arrow-left"></i> Back to messages</button>
            <div class="cp-chat-head">
              <div class="cp-chat-user">
                <span class="cp-thread-avatar">EP<span class="online"></span></span>
                <div>
                  <strong>Emirates Pro Services</strong>
                  <span>Online • Golden Visa</span>
                </div>
              </div>
              <button class="cp-icon-btn" type="button" aria-label="More"><i class="bi bi-three-dots-vertical"></i></button>
            </div>

            <div class="cp-chat-body" id="chatBody">
              <?php foreach ($messages as $m):
                $is_mine = ($m['sender'] === 'You');
              ?>
                <div class="cp-bubble-row <?= $is_mine ? 'mine' : '' ?>">
                  <div>
                    <div class="cp-bubble <?= $is_mine ? 'mine' : 'theirs' ?>"><?= htmlspecialchars($m['message_text']) ?></div>
                    <div class="cp-bubble-time">
                      <?= date('g:i A', strtotime($m['created_at'])) ?>
                      <?php if ($is_mine): ?>
                        <i class="bi bi-check2-all text-primary"></i>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <form method="post" action="messages.php">
              <?= csrf_field(); ?>
              <div class="cp-chat-input">
                <button class="cp-icon-btn" type="button" aria-label="Attach"><i class="bi bi-paperclip"></i></button>
                <input class="cp-chat-field" name="message_text" type="text" placeholder="Type a message..." required autocomplete="off">
                <button class="cp-send" type="submit" aria-label="Send"><i class="bi bi-send-fill"></i></button>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/main.js"></script>
  <script>
    // Auto scroll chat to bottom
    const body = document.getElementById('chatBody');
    if (body) {
      body.scrollTop = body.scrollHeight;
    }
  </script>
</body>
</html>
