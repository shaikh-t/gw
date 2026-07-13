<?php
// partials/sidebar.php
// Renders a left sidebar. Requires lib/permissions.php (can/is_role) to be loaded earlier.
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../lib/permissions.php'; // ensure can() and is_role() exist

function nav_item(string $url, string $label, string $icon = '', string $active = ''): void {
    $safeUrl = htmlspecialchars($url, ENT_QUOTES);
    $safeLabel = htmlspecialchars($label, ENT_QUOTES);
    echo '<li class="nav-item">';
    echo '<a class="nav-link d-flex align-items-center ' . $active . '" href="' . $safeUrl . '">';
    if ($icon !== '') echo '<span class="me-2 nav-icon">' . $icon . '</span>';
    echo '<span class="nav-label">' . $safeLabel . '</span>';
    echo '</a></li>';
}

// Determine active path (simple)
$req = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$current = trim($req, '/');
?>
<nav id="appSidebar" class="app-sidebar bg-white border-end">
  <div class="sidebar-brand p-3 border-bottom">
    <a href="/dashboard.php" class="d-flex align-items-center text-decoration-none">
      <strong>GW Admin</strong>
    </a>
  </div>

  <ul class="nav flex-column p-2">
    <?php if (can('dashboard.view')): ?>
      <?php nav_item($domain.'/admin/dashboard.php', 'Dashboard', '<svg width="16" height="16" fill="currentColor" class="bi bi-speedometer2" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V8h2.5a.5.5 0 0 1 0 1H8a.5.5 0 0 1-.5-.5V4.5A.5.5 0 0 1 8 4z"/></svg>', $current === 'dashboard.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('users.view')): ?>
      <?php nav_item($domain.'/admin/users/index.php', 'Users', '<svg width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16"><path d="M5 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/></svg>', $current === 'users.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('roles.view')): ?>
  <?php nav_item($domain.'/admin/roles/index.php', 'Roles', '', $current === 'admin/roles/index.php' ? 'active' : ''); ?>
<?php endif; ?>
<?php if (can('roles.view')): ?>
  <?php nav_item($domain.'/admin/permissions/index.php', 'Permissions', '', $current === 'admin/permissions/index.php' ? 'active' : ''); ?>
<?php endif; ?>

<?php if (can('providers.view')): ?>
  <?php nav_item($domain . '/admin/providers/index.php', 'Providers', '', $current === 'admin/providers/index.php' ? 'active' : ''); ?>
<?php endif; ?>

<?php if (can('providers.manage')): ?>
  <?php
    require_once __DIR__ . '/../lib/onboarding_helpers.php';
    $pending_onb = onboarding_pending_count();
    $label = 'Onboarding Queue' . ($pending_onb ? " ({$pending_onb})" : '');
    nav_item($domain . '/admin/providers/onboarding_list.php', $label, '', $current === 'admin/providers/onboarding_list.php' ? 'active' : '');
  ?>
<?php endif; ?>
<?php if (can('providers.manage')): ?>
  <?php
    nav_item($domain . '/admin/provider_overview.php', 'Providers Overview', '', $current === 'admin/provider_overview.php' ? 'active' : '');
?>
  <!-- <li class="nav-item">
    <a class="nav-link" href="/admin/provider_overview.php">
      <i class="bi bi-building"></i> Providers
    </a>
  </li> -->
<?php endif; ?>

<?php
// Services
if (can('services.view')) {
    nav_item($domain . '/admin/services/index.php', 'Services', '', $current === 'admin/services/index.php' ? 'active' : '');
}

// Service Categories
if (can('services.view')) {
    nav_item($domain . '/admin/service_categories/index.php', 'Service Categories', '', $current === 'admin/service_categories/index.php' ? 'active' : '');
}

// Service Tags
if (can('services.view')) {
    nav_item($domain . '/admin/service_tags/index.php', 'Service Tags', '', $current === 'admin/service_tags/index.php' ? 'active' : '');
}
// Reviews
if (can('reviews.view')) {
    nav_item($domain . '/admin/reviews/index.php', 'Reviews', '', $current === 'admin/reviews/index.php' ? 'active' : '');
}

// CMS Pages
if (can('cms.manage')) {
    nav_item($domain . '/admin/cms/index.php', 'Page CMS', '', $current === 'admin/cms/index.php' ? 'active' : '');
}

// Blog Posts
if (can('blog.manage')) {
    nav_item($domain . '/admin/blog/index.php', 'Blog Posts', '', $current === 'admin/blog/index.php' ? 'active' : '');
}

// Contact Messages / Inquiries
if (can('messages.manage')) {
    nav_item($domain . '/admin/messages/index.php', 'Inquiries', '', $current === 'admin/messages/index.php' ? 'active' : '');
}
?>


    <?php if (can('bookings.view')): ?>
      <?php nav_item('bookings.php', 'Bookings', '', $current === 'bookings.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('payments.view')): ?>
      <?php nav_item('payments.php', 'Payments', '', $current === 'payments.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('reports.view')): ?>
      <?php nav_item('reports.php', 'Reports', '', $current === 'reports.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('settings.view')): ?>
      <?php nav_item($domain . '/admin/settings/landing_page.php', 'Website Settings', '', $current === 'admin/settings/landing_page.php' ? 'active' : ''); ?>
      <?php nav_item($domain . '/admin/settings/menus.php', 'Menu Builder', '', $current === 'admin/settings/menus.php' ? 'active' : ''); ?>
      <?php nav_item($domain . '/admin/settings/features.php', 'Landing Features', '', $current === 'admin/settings/features.php' ? 'active' : ''); ?>
      <?php nav_item($domain . '/admin/settings/testimonials.php', 'Testimonials', '', $current === 'admin/settings/testimonials.php' ? 'active' : ''); ?>
    <?php endif; ?>
  </ul>

  <div class="sidebar-footer mt-auto p-3 border-top">
    <small class="text-muted">Signed in as <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Guest', ENT_QUOTES); ?></small>
  </div>
</nav>
