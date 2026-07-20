<?php
// admin/messages/reply.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('messages.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

$uuid = $_GET['uuid'] ?? '';
$stmt = $mysqli->prepare("SELECT m.*, u.name as replied_by_name FROM contact_messages m LEFT JOIN users u ON u.id = m.replied_by WHERE m.uuid = ? LIMIT 1");
$stmt->bind_param('s', $uuid);
$stmt->execute();
$res = $stmt->get_result();
$message = $res->fetch_assoc();
$stmt->close();

if (!$message) {
    http_response_code(404);
    echo "Message not found";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $reply_text = trim($_POST['reply_text'] ?? '');
    if ($reply_text === '') {
        $_SESSION['flash_errors'] = 'Reply text cannot be empty.';
        header('Location: reply.php?uuid=' . $uuid);
        exit;
    }

    $current_user_id = $_SESSION['user']['id'] ?? null;
    if (!$current_user_id) {
        $user = current_user();
        $current_user_id = $user['id'];
    }

    $stmt = $mysqli->prepare("UPDATE contact_messages SET reply_text = ?, replied_at = NOW(), replied_by = ? WHERE id = ?");
    $stmt->bind_param('sii', $reply_text, $current_user_id, $message['id']);

    if ($stmt->execute()) {
        // Simulate email dispatch
        $to = $message['email'];
        $subject = "Re: [GlobalWays Support] " . $message['topic'];
        $headers = "From: support@globalways.ae\r\nReply-To: support@globalways.ae\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";

        $email_body = "
        <p>Hi " . htmlspecialchars($message['name']) . ",</p>
        <p>Thank you for contacting Global Ways. Here is our response to your inquiry regarding <strong>" . htmlspecialchars($message['topic']) . "</strong>:</p>
        <blockquote style='border-left: 3px solid #0C4AC7; padding-left: 10px; margin-left: 0; color: #555;'>
            " . nl2br(htmlspecialchars($reply_text)) . "
        </blockquote>
        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
        <p style='font-size: 12px; color: #888;'>Your original message:<br>
        " . nl2br(htmlspecialchars($message['message'])) . "</p>
        <p style='font-size: 12px; color: #888;'>Best regards,<br>Global Ways Team</p>
        ";

        // Logging Simulated Email Dispatch
        error_log("Simulated Email Sent to: $to\nSubject: $subject\nHeaders: $headers\nBody:\n$email_body\n");

        $_SESSION['flash_success'] = 'Reply saved and email sent to ' . htmlspecialchars($message['email']) . ' successfully.';
    } else {
        $_SESSION['flash_errors'] = 'Failed to save reply: ' . $mysqli->error;
    }
    $stmt->close();
    header('Location: index.php');
    exit;
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>
<div class="container-fluid mt-4">
  <div class="mb-3">
    <a href="index.php" class="btn btn-outline-secondary btn-sm">← Back to Inquiries</a>
  </div>

  <div class="row">
    <!-- Original Inquiry -->
    <div class="col-lg-6 mb-4">
      <div class="card bg-white p-4">
        <h5 class="text-primary border-bottom pb-2 mb-3">Original Inquiry</h5>

        <table class="table table-bordered table-sm small mb-3">
          <tr><th style="width:30%;">Name</th><td><?= htmlspecialchars($message['name']) ?></td></tr>
          <tr><th>Email</th><td><?= htmlspecialchars($message['email']) ?></td></tr>
          <tr><th>Phone</th><td><?= htmlspecialchars($message['phone'] ?: '-') ?></td></tr>
          <tr><th>Topic</th><td><span class="badge bg-info text-dark"><?= htmlspecialchars($message['topic']) ?></span></td></tr>
          <tr><th>Received At</th><td><?= htmlspecialchars(date('M d, Y H:i:s', strtotime($message['created_at']))) ?></td></tr>
        </table>

        <div class="p-3 bg-light border rounded">
          <h6>Message:</h6>
          <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($message['message']) ?></p>
        </div>
      </div>
    </div>

    <!-- Reply Section -->
    <div class="col-lg-6">
      <div class="card bg-white p-4">
        <h5 class="text-success border-bottom pb-2 mb-3">Send Response</h5>

        <?php if (!empty($message['replied_at'])): ?>
          <div class="alert alert-info p-2 small mb-3">
            <strong>Previous Reply sent by <?= htmlspecialchars($message['replied_by_name'] ?? 'System') ?></strong> on <?= htmlspecialchars(date('M d, Y H:i', strtotime($message['replied_at']))) ?>:
            <hr class="my-1">
            <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($message['reply_text']) ?></p>
          </div>
        <?php endif; ?>

        <form method="post">
          <?= csrf_field(); ?>
          <div class="mb-3">
            <label class="form-label font-weight-bold">Response Email Body *</label>
            <textarea name="reply_text" class="form-control" rows="8" required placeholder="Write your reply to the user..."><?= htmlspecialchars($message['reply_text'] ?? '') ?></textarea>
            <small class="text-muted">This reply will be sent via email to <?= htmlspecialchars($message['email']) ?> and archived in the database.</small>
          </div>

          <button type="submit" class="btn btn-success px-4">
            <?= !empty($message['replied_at']) ? 'Send Another Reply' : 'Send Reply' ?>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
