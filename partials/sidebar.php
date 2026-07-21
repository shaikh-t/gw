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
$current = $req;// trim($req, '/');
$current_folder = dirname($req); 
?>
<nav id="appSidebar" class="app-sidebar bg-white border-end">
  <div class="sidebar-brand p-3 border-bottom">
    <a href="/dashboard.php" class="d-flex align-items-center text-decoration-none">
      <strong>GW Admin</strong>
    </a>
  </div>
  <?php
// echo $current .'==='. $domain.'/admin/users/users.php';
  ?>
  <ul class="nav flex-column p-2">
    <?php if (can('dashboard.view')): ?>
      <?php nav_item($domain.'/admin/dashboard.php', 'Dashboard', '<i class="bi bi-speedometer2"></i>', $current === $domain.'/admin/dashboard.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('users.view')): ?>
      <?php nav_item($domain.'/admin/users/index.php', 'Users', '<i class="bi bi-people"></i>', $current_folder === $domain.'/admin/users' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('roles.view')): ?>
      <?php nav_item($domain.'/admin/roles/index.php', 'Roles', '<i class="bi bi-shield-lock"></i>', $current_folder === $domain.'/admin/roles' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('roles.view')): ?>
      <?php nav_item($domain.'/admin/permissions/index.php', 'Permissions', '<i class="bi bi-key"></i>', $current_folder === $domain.'/admin/permissions' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('users.view')): ?>
      <?php nav_item($domain.'/admin/crm/index.php', 'Customers', '<i class="bi bi-cart4"></i>', $current_folder === $domain.'/admin/crm' && $current !== $domain.'/admin/import-pdf.php' && $current !== $domain.'/admin/crm/knowledge-base.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('providers.view')): ?>
      <?php nav_item($domain . '/admin/providers/index.php', 'Providers', '<i class="bi bi-person-workspace"></i>', $current_folder === $domain.'/admin/providers' && $current !== $domain.'/admin/providers/onboarding_list.php' ? 'active' : ''); ?>
    <?php endif; ?>

<?php if (can('providers.manage')): ?>
  <?php
    require_once __DIR__ . '/../lib/onboarding_helpers.php';
    $pending_onb = onboarding_pending_count();
    $label = 'Onboarding Queue' . ($pending_onb ? " ({$pending_onb})" : '');
    nav_item($domain . '/admin/providers/onboarding_list.php', $label, '<i class="bi bi-person-plus"></i>', $current === $domain.'/admin/providers/onboarding_list.php' ? 'active' : '');
  ?>
<?php endif; ?>
<?php if (can('providers.manage')): ?>
  <?php
    nav_item($domain . '/admin/provider_overview.php', 'Providers Overview', '<i class="bi bi-person-badge"></i>', $current === $domain.'/admin/provider_overview.php' ? 'active' : '');
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
    nav_item($domain . '/admin/services/index.php', 'Services', '<i class="bi bi-briefcase"></i>', $current_folder === $domain.'/admin/services' ? 'active' : '');
}

// Service Categories
if (can('services.view')) {
    nav_item($domain . '/admin/service_categories/index.php', 'Service Categories', '<i class="bi bi-grid"></i>', $current_folder === $domain.'/admin/service_categories' ? 'active' : '');
}

// Service Tags
if (can('services.view')) {
    nav_item($domain . '/admin/service_tags/index.php', 'Service Tags', '<i class="bi bi-tags"></i>', $current_folder === $domain.'/admin/service_tags' ? 'active' : '');
}
// Reviews
if (can('reviews.view')) {
    nav_item($domain . '/admin/reviews/index.php', 'Reviews', '<i class="bi bi-star"></i>', $current_folder === $domain.'/admin/reviews' ? 'active' : '');
}

// CMS Pages
if (can('cms.manage')) {
    nav_item($domain . '/admin/cms/index.php', 'Page CMS', '<i class="bi bi-file-earmark-richtext"></i>', $current_folder === $domain.'/admin/cms' ? 'active' : '');
}

// Blog Posts
if (can('blog.manage')) {
    nav_item($domain . '/admin/blog/index.php', 'Blog Posts', '<i class="bi bi-journal-text"></i>', $current_folder === $domain.'/admin/blog' ? 'active' : '');
}

// Contact Messages / Inquiries
if (can('messages.manage')) {
    nav_item($domain . '/admin/messages/index.php', 'Inquiries', '<i class="bi bi-envelope-paper"></i>', $current_folder === $domain.'/admin/messages' ? 'active' : '');
}
?>


    <?php if (can('bookings.view')): ?>
      <?php nav_item('bookings.php', 'Bookings', '<i class="bi bi-calendar-check"></i>', $current === $domain.'/admin/bookings.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('payments.view')): ?>
      <?php nav_item('payments.php', 'Payments', '<i class="bi bi-cash-stack"></i>', $current === $domain.'/admin/payments.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('reports.view')): ?>
      <?php nav_item('reports.php', 'Reports', '<i class="bi bi-graph-up-arrow"></i>', $current === $domain.'/admin/reports.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('settings.view')): ?>
      <?php nav_item($domain . '/admin/settings/landing_page.php', 'Website Settings', '<i class="bi bi-gear"></i>', $current === $domain.'/admin/settings/landing_page.php' ? 'active' : ''); ?>
      <?php nav_item($domain.'/admin/settings/payment-gateways.php', 'Payment Settings', '<i class="bi bi-credit-card"></i>', $current === $domain.'/admin/settings/payment-gateways.php' ? 'active' : ''); ?>
      <?php nav_item($domain . '/admin/settings/menus.php', 'Menu Builder', '<i class="bi bi-list-nested"></i>', $current === $domain.'/admin/settings/menus.php' ? 'active' : ''); ?>
      <?php nav_item($domain . '/admin/settings/features.php', 'Landing Features', '<i class="bi bi-lightning-charge"></i>', $current === $domain.'/admin/settings/features.php' ? 'active' : ''); ?>
      <?php nav_item($domain . '/admin/settings/testimonials.php', 'Testimonials', '<i class="bi bi-chat-square-quote"></i>', $current === $domain.'/admin/settings/testimonials.php' ? 'active' : ''); ?>
    <?php endif; ?>
    <?php if (can('can_edit_knowledge_base')): ?>
      <?php nav_item($domain.'/admin/crm/knowledge-base.php', 'RAG Knowledge Base', '<i class="bi bi-database-fill-gear"></i>', $current === $domain.'/admin/crm/knowledge-base.php' ? 'active' : ''); ?>
    <?php endif; ?>

    <?php if (can('cms.manage')): ?>
      <?php nav_item($domain.'/admin/import-pdf.php', 'Ingest Guidelines', '<i class="bi bi-cloud-arrow-up-fill"></i>', $current === $domain.'/admin/import-pdf.php' ? 'active' : ''); ?>
    <?php endif; ?>
    <?php if (is_role('Super Admin')): ?>
      <?php nav_item($domain . '/admin/settings/bot_ads.php', 'Monetization Ads', '<i class="bi bi-megaphone"></i>', $current === $domain.'/admin/settings/bot_ads.php' ? 'active' : ''); ?>
      <?php nav_item($domain . '/admin/settings/ai_status.php', 'AI Global Kill-Switch', '<i class="bi bi-robot"></i>', $current === $domain.'/admin/settings/ai_status.php' ? 'active' : ''); ?>
    <?php endif; ?>
    <?php if (can('view_voice_telemetry')): ?>
      <?php nav_item($domain . '/admin/settings/voice_analytics.php', 'Voice & Analytics', '<i class="bi bi-soundwave"></i>', $current === $domain.'/admin/settings/voice_analytics.php' ? 'active' : ''); ?>
    <?php endif; ?>
  </ul>

  <div class="sidebar-footer mt-auto p-3 border-top">
    <small class="text-muted">Signed in as <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Guest', ENT_QUOTES); ?></small>
  </div>
</nav>
