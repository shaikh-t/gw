<?php
require_once __DIR__ . '/../lib/settings_helper.php';
require_once __DIR__ . '/../lib/db_mysqli.php';

$footer_settings = get_all_settings();

function get_menu_items($name) {
    global $mysqli;
    $res = $mysqli->query("SELECT label, url FROM menu_items WHERE menu_id = (SELECT id FROM menus WHERE name = '$name') ORDER BY sort_order ASC");
    $items = [];
    while ($m = $res->fetch_assoc()) $items[] = $m;
    return $items;
}

$footer_pages = get_menu_items('footer_pages');
$footer_services = get_menu_items('footer_services');
$footer_utility = get_menu_items('footer_utility');
?>
  <!-- Footer -->
  <footer class="gw-footer">
  <div class="container-xl pt-5 pb-4">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-3">
        <a href="index.php" class="d-inline-block mb-4">
          <img src="assets/logo-white.png" alt="globalways" class="gw-logo-footer">
        </a>
        <p class="footer-heading">Headquarter</p>
        <p class="footer-text mb-4"><?= nl2br(htmlspecialchars($footer_settings['contact_address'] ?? '')) ?></p>
        <p class="footer-heading">Email</p>
        <p class="footer-text mb-4"><a href="mailto:<?= htmlspecialchars($footer_settings['contact_email'] ?? '') ?>" class="footer-link"><?= htmlspecialchars($footer_settings['contact_email'] ?? '') ?></a></p>
        <div class="d-flex gap-2">
          <a href="<?= htmlspecialchars($footer_settings['social_facebook'] ?? '#') ?>" class="social-btn" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="<?= htmlspecialchars($footer_settings['social_linkedin'] ?? '#') ?>" class="social-btn" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
          <a href="<?= htmlspecialchars($footer_settings['social_instagram'] ?? '#') ?>" class="social-btn" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="<?= htmlspecialchars($footer_settings['social_behance'] ?? '#') ?>" class="social-btn" aria-label="Behance"><i class="bi bi-behance"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h4 class="footer-col-title">Pages</h4>
        <ul class="list-unstyled footer-links">
          <?php foreach ($footer_pages as $item): ?>
            <li><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h4 class="footer-col-title">Services</h4>
        <ul class="list-unstyled footer-links">
          <?php foreach ($footer_services as $item): ?>
            <li><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h4 class="footer-col-title">Utility</h4>
        <ul class="list-unstyled footer-links">
          <?php foreach ($footer_utility as $item): ?>
            <li><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-lg-3">
        <h4 class="footer-col-title">Newsletter</h4>
        <p class="footer-text mb-3"><?= htmlspecialchars($footer_settings['footer_newsletter_text'] ?? '') ?></p>
        <div class="newsletter-input d-flex align-items-center gap-2">
          <input type="email" placeholder="yourmail@gmail.com" aria-label="Email for newsletter">
          <button class="btn btn-gw-blue btn-sm rounded-circle p-2 flex-shrink-0" type="button" aria-label="Subscribe"><i class="bi bi-arrow-right"></i></button>
        </div>
      </div>
    </div>
  </div>
  <div class="footer-bar">
    <div class="container-xl py-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
      <p class="footer-copy mb-0">© <?= date('Y') ?> GlobalWays®. All rights reserved.</p>
      <p class="footer-copy mb-0 d-flex align-items-center gap-2"><span class="status-dot"></span>All systems operational</p>
    </div>
  </div>
  <div class="footer-disclaimer">
    <div class="container-xl py-4">
      <p class="mb-0"><?= htmlspecialchars($footer_settings['footer_disclaimer'] ?? '') ?></p>
    </div>
  </div>
</footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/main.js"></script>
</body>
</html>
