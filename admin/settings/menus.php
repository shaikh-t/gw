<?php
// admin/settings/menus.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('settings.manage');
require_once __DIR__ . '/../../lib/db_mysqli.php';

$menus_res = $mysqli->query("SELECT * FROM menus ORDER BY id ASC");
$menus = [];
while ($m = $menus_res->fetch_assoc()) {
    $m['items'] = [];
    $items_res = $mysqli->query("SELECT * FROM menu_items WHERE menu_id = " . (int)$m['id'] . " ORDER BY sort_order ASC");
    while ($item = $items_res->fetch_assoc()) {
        $m['items'][] = $item;
    }
    $menus[] = $m;
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<main class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Menu Management</h2>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_errors'])): ?>
        <div class="alert alert-danger"><?php foreach($_SESSION['flash_errors'] as $e) echo htmlspecialchars($e)."<br>"; unset($_SESSION['flash_errors']); ?></div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($menus as $menu): ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong class="text-primary"><?= htmlspecialchars($menu['label']) ?></strong>
                    <span class="badge bg-secondary text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($menu['name']) ?></span>
                </div>
                <div class="card-body">
                    <form action="<?= $domain ?>/admin/settings/menus_update.php" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">
                        
                        <div id="items-container-<?= $menu['id'] ?>">
                            <?php foreach ($menu['items'] as $index => $item): ?>
                            <div class="menu-item-row border rounded p-2 mb-2 bg-light">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <label class="small text-muted">Label</label>
                                        <input type="text" name="items[<?= $index ?>][label]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['label']) ?>" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="small text-muted">URL</label>
                                        <input type="text" name="items[<?= $index ?>][url]" class="form-control form-control-sm" value="<?= htmlspecialchars($item['url']) ?>" required>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.menu-item-row').remove()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="items[<?= $index ?>][sort_order]" value="<?= $item['sort_order'] ?>">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addMenuItem(<?= $menu['id'] ?>)">
                            <i class="bi bi-plus-lg"></i> Add Item
                        </button>
                        
                        <hr>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
function addMenuItem(menuId) {
    const container = document.getElementById('items-container-' + menuId);
    const index = container.children.length;
    const html = `
        <div class="menu-item-row border rounded p-2 mb-2 bg-light">
            <div class="row g-2">
                <div class="col-md-5">
                    <label class="small text-muted">Label</label>
                    <input type="text" name="items[${index}][label]" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-5">
                    <label class="small text-muted">URL</label>
                    <input type="text" name="items[${index}][url]" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.menu-item-row').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <input type="hidden" name="items[${index}][sort_order]" value="${index}">
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
