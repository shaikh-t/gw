<?php
// contact.php
require_once __DIR__ . '/lib/db_mysqli.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/uuid_helper.php';
require_once __DIR__ . '/lib/settings_helper.php';

// Fetch Contact page CMS content
$res = $mysqli->query("SELECT content FROM cms_pages WHERE page_name = 'contact' LIMIT 1");
$cms = [];
if ($res && $row = $res->fetch_assoc()) {
    $cms = json_decode($row['content'], true) ?: [];
}

$site_settings = get_all_settings();
$admin_email = $site_settings['contact_email'] ?? 'hello@globalways.ae';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $message_text = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $topic === '' || $message_text === '') {
        $error_message = 'Please fill all required fields marked with *';
    } else {
        $msg_uuid = generate_uuid();
        $stmt = $mysqli->prepare("INSERT INTO contact_messages (uuid, name, email, phone, topic, message) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $msg_uuid, $name, $email, $phone, $topic, $message_text);

        if ($stmt->execute()) {
            // Simulate sending email to admin
            $subject = "[GlobalWays Inquiry] " . $topic . " - from " . $name;
            $body = "
            <h2>New Contact Inquiry Received</h2>
            <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Phone:</strong> " . htmlspecialchars($phone ?: '-') . "</p>
            <p><strong>Topic:</strong> " . htmlspecialchars($topic) . "</p>
            <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message_text)) . "</p>
            ";

            $headers = "From: webmaster@globalways.ae\r\nReply-To: $email\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";

            // Log simulated email dispatch
            error_log("Simulated Email Sent to Admin ($admin_email):\nSubject: $subject\nHeaders: $headers\nBody:\n$body\n");

            $success_message = 'Thank you! Your message has been received. Our team will respond within 2 hours.';
        } else {
            $error_message = 'Failed to save message to database: ' . $mysqli->error;
        }
        $stmt->close();
    }
}

include __DIR__ . '/partials/frontend_header.php';
?>

  <main>
    <section class="contact-hero">
      <div class="container-xl">
        <p class="contact-hero-kicker"><?= htmlspecialchars($cms['hero_kicker'] ?? 'Contact') ?></p>
        <h1 class="contact-hero-title font-serif"><?= htmlspecialchars($cms['hero_title'] ?? 'Get in Touch') ?></h1>
        <p class="contact-hero-sub"><?= htmlspecialchars($cms['hero_sub'] ?? '') ?></p>
      </div>
    </section>

    <section class="contact-main">
      <div class="container-xl">

        <?php if (!empty($success_message)): ?>
          <div class="alert alert-success alert-dismissible fade show mb-4 py-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
          <div class="alert alert-danger alert-dismissible fade show mb-4 py-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="row g-4 g-xl-5">
          <div class="col-lg-4 fade-in">
            <div class="contact-side-stack">
              <a href="<?= htmlspecialchars($cms['whatsapp_url'] ?? '#') ?>" class="contact-channel-card" target="_blank">
                <span class="contact-channel-icon"><i class="bi bi-whatsapp"></i></span>
                <div>
                  <h3>WhatsApp</h3>
                  <p class="contact-channel-value"><?= htmlspecialchars($cms['whatsapp'] ?? '') ?></p>
                  <span class="contact-channel-meta"><?= htmlspecialchars($cms['whatsapp_meta'] ?? '') ?></span>
                </div>
              </a>

              <a href="mailto:<?= htmlspecialchars($cms['email'] ?? '') ?>" class="contact-channel-card">
                <span class="contact-channel-icon"><i class="bi bi-envelope"></i></span>
                <div>
                  <h3>Email Support</h3>
                  <p class="contact-channel-value"><?= htmlspecialchars($cms['email'] ?? '') ?></p>
                  <span class="contact-channel-meta"><?= htmlspecialchars($cms['email_meta'] ?? '') ?></span>
                </div>
              </a>

              <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $cms['phone'] ?? '')) ?>" class="contact-channel-card">
                <span class="contact-channel-icon"><i class="bi bi-telephone"></i></span>
                <div>
                  <h3>Phone</h3>
                  <p class="contact-channel-value"><?= htmlspecialchars($cms['phone'] ?? '') ?></p>
                  <span class="contact-channel-meta"><?= htmlspecialchars($cms['phone_meta'] ?? '') ?></span>
                </div>
              </a>

              <div class="contact-info-block">
                <p class="contact-block-label">Headquarter</p>
                <div class="contact-hq-card">
                  <div class="contact-hq-title">
                    <i class="bi bi-geo-alt-fill"></i>
                    <h3 class="font-serif"><?= htmlspecialchars($cms['hq_title'] ?? 'Dubai, UAE') ?></h3>
                  </div>
                  <p><?= nl2br(htmlspecialchars($cms['hq_address'] ?? '')) ?></p>
                  <a href="mailto:<?= htmlspecialchars($cms['email'] ?? 'hello@globalways.ae') ?>"><?= htmlspecialchars($cms['email'] ?? 'hello@globalways.ae') ?></a>
                </div>
              </div>

              <div class="contact-info-block">
                <p class="contact-block-label">Business Hours</p>
                <div class="contact-hours-card">
                  <div class="contact-hours-head">
                    <i class="bi bi-clock"></i>
                    <h3 class="font-serif">Working Hours</h3>
                  </div>
                  <ul class="contact-hours-list list-unstyled mb-0">
                    <?php foreach (($cms['hours'] ?? []) as $hr): ?>
                      <li>
                        <span><?= htmlspecialchars($hr['days'] ?? '') ?></span>
                        <strong class="<?= !empty($hr['closed']) ? 'closed' : '' ?>"><?= htmlspecialchars($hr['time'] ?? '') ?></strong>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-8 fade-in">
            <div class="contact-form-card">
              <p class="contact-form-kicker">Send a Message</p>
              <h2 class="contact-form-title font-serif">We’d love to <span class="text-gradient-blue">hear from you</span></h2>
              <form class="contact-form" method="post" action="contact.php">
                <?= csrf_field(); ?>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="contactName" class="contact-label">Full Name *</label>
                    <input type="text" name="name" class="form-control contact-input" id="contactName" placeholder="Your full name" required>
                  </div>
                  <div class="col-md-6">
                    <label for="contactEmail" class="contact-label">Email Address *</label>
                    <input type="email" name="email" class="form-control contact-input" id="contactEmail" placeholder="you@email.com" required>
                  </div>
                  <div class="col-md-6">
                    <label for="contactPhone" class="contact-label">Phone (Optional)</label>
                    <input type="tel" name="phone" class="form-control contact-input" id="contactPhone" placeholder="+971 50 000 0000">
                  </div>
                  <div class="col-md-6">
                    <label for="contactTopic" class="contact-label">Topic *</label>
                    <select name="topic" class="form-select contact-input" id="contactTopic" required>
                      <option value="">Select a topic</option>
                      <option value="General inquiry">General inquiry</option>
                      <option value="Golden Visa">Golden Visa</option>
                      <option value="Business setup">Business setup</option>
                      <option value="Vendor partnership">Vendor partnership</option>
                      <option value="Technical support">Technical support</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label for="contactMessage" class="contact-label">Message *</label>
                    <textarea name="message" class="form-control contact-input" id="contactMessage" rows="6" placeholder="How can we help?" required></textarea>
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-gw-dark w-100 contact-submit">Send Message <i class="bi bi-arrow-right ms-1"></i></button>
                  </div>
                </div>
              </form>
            </div>

            <div class="contact-quick-links">
              <a href="vendors.php" class="contact-quick-link">Browse Vendors <i class="bi bi-arrow-right"></i></a>
              <a href="how-it-works.php" class="contact-quick-link">How It Works <i class="bi bi-arrow-right"></i></a>
              <a href="vendor-onboard.php" class="contact-quick-link">Join as Vendor <i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('contact-page');
    // document.body.classList.add('has-custom-cursor');
    document.getElementById('gwNav').classList.add('dark-hero');
});
</script>
<?php include __DIR__ . '/partials/frontend_footer.php'; ?>
