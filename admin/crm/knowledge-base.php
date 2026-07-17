<?php
// admin/crm/knowledge-base.php
require_once __DIR__ . '/../../lib/middleware.php';

// Build a comprehensive management view protected strictly behind Super Admin privileges
if (!is_role('Super Admin')) {
    http_response_code(403);
    die("Access denied. Super Admin privileges are required to manage the RAG Knowledge Base.");
}

$success_message = '';
$error_message = '';

// Handle Update action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $text_content = isset($_POST['text_content']) ? trim($_POST['text_content']) : '';
    $document_category = isset($_POST['document_category']) ? trim($_POST['document_category']) : '';

    if ($id <= 0) {
        $error_message = "Invalid element identifier.";
    } elseif ($text_content === '') {
        $error_message = "Text Content cannot be overwritten with empty content.";
    } else {
        $stmt_up = $mysqli->prepare("UPDATE `local_knowledge_base` SET `text_content` = ?, `document_category` = ? WHERE `id` = ?");
        if ($stmt_up) {
            $stmt_up->bind_param('ssi', $text_content, $document_category, $id);
            if ($stmt_up->execute()) {
                $success_message = "Knowledge base element ID #{$id} successfully updated.";
            } else {
                $error_message = "Failed to update database element: " . $mysqli->error;
            }
            $stmt_up->close();
        } else {
            $error_message = "Database prepared statement error.";
        }
    }
}

// Search and Paginate
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $search_param = '%' . $search . '%';
    // Count
    $stmt_cnt = $mysqli->prepare("SELECT COUNT(*) as total FROM `local_knowledge_base` WHERE `file_name` LIKE ? OR `text_content` LIKE ? OR `document_category` LIKE ?");
    $stmt_cnt->bind_param('sss', $search_param, $search_param, $search_param);
    $stmt_cnt->execute();
    $total = $stmt_cnt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_cnt->close();

    // Fetch
    $stmt_fetch = $mysqli->prepare("SELECT * FROM `local_knowledge_base` WHERE `file_name` LIKE ? OR `text_content` LIKE ? OR `document_category` LIKE ? ORDER BY `id` DESC LIMIT ? OFFSET ?");
    $stmt_fetch->bind_param('ssiii', $search_param, $search_param, $search_param, $perPage, $offset);
    $stmt_fetch->execute();
    $elements_res = $stmt_fetch->get_result();
    $knowledge_elements = [];
    while ($row = $elements_res->fetch_assoc()) {
        $knowledge_elements[] = $row;
    }
    $stmt_fetch->close();
} else {
    // Count
    $res_cnt = $mysqli->query("SELECT COUNT(*) as total FROM `local_knowledge_base`");
    $total = $res_cnt->fetch_assoc()['total'] ?? 0;

    // Fetch
    $stmt_fetch = $mysqli->prepare("SELECT * FROM `local_knowledge_base` ORDER BY `id` DESC LIMIT ? OFFSET ?");
    $stmt_fetch->bind_param('ii', $perPage, $offset);
    $stmt_fetch->execute();
    $elements_res = $stmt_fetch->get_result();
    $knowledge_elements = [];
    while ($row = $elements_res->fetch_assoc()) {
        $knowledge_elements[] = $row;
    }
    $stmt_fetch->close();
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';

echo '<main class="main-content p-4">';
?>

<div class="container-fluid mt-2">
  <div class="card shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="h4 mb-1 fw-bold text-dark"><i class="bi bi-database-fill-gear text-primary"></i> RAG Knowledge Base Management</h2>
        <p class="text-muted small mb-0">Search, review, and directly edit indexed text segments to fine-tune bot accuracy and guidelines.</p>
      </div>
      <a href="../import-pdf.php" class="btn btn-outline-primary d-flex align-items-center gap-1">
        <i class="bi bi-cloud-arrow-up-fill"></i> Upload/Ingest New Content
      </a>
    </div>

    <!-- Search filter -->
    <form method="get" class="row g-3 mb-4">
      <div class="col-md-6 col-lg-4">
        <div class="input-group">
          <input type="text" name="search" class="form-control" placeholder="Search guidelines or source files..." value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
          <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
          <?php if ($search !== ''): ?>
            <a href="knowledge-base.php" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <?php if ($success_message !== ''): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width: 5%;">ID</th>
            <th style="width: 25%;">Source / File Name</th>
            <th style="width: 15%;">Category</th>
            <th style="width: 10%;">Page No</th>
            <th style="width: 35%;">Content Preview</th>
            <th style="width: 10%;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($knowledge_elements)): ?>
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-database-dash fs-1 d-block mb-2"></i> No active knowledge base rows indexed yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($knowledge_elements as $elem): ?>
              <tr>
                <td class="font-mono small text-muted">#<?= $elem['id'] ?></td>
                <td class="fw-semibold text-dark text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($elem['file_name']) ?>">
                  <?= htmlspecialchars($elem['file_name']) ?>
                </td>
                <td>
                  <span class="badge bg-secondary-subtle text-secondary text-uppercase"><?= htmlspecialchars($elem['document_category']) ?></span>
                </td>
                <td class="font-mono fw-semibold text-muted">Pg. <?= $elem['page_number'] ?></td>
                <td class="small text-secondary">
                  <div class="text-truncate" style="max-width: 350px;" title="<?= htmlspecialchars($elem['text_content']) ?>">
                    <?= htmlspecialchars($elem['text_content']) ?>
                  </div>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#editModal-<?= $elem['id'] ?>">
                    <i class="bi bi-pencil-square"></i> Edit Raw Text
                  </button>

                  <!-- Edit Text Modal -->
                  <div class="modal fade" id="editModal-<?= $elem['id'] ?>" tabindex="-1" aria-hidden="true" style="text-align: left;">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                      <div class="modal-content">
                        <form action="knowledge-base.php" method="post">
                          <?= csrf_field(); ?>
                          <input type="hidden" name="id" value="<?= $elem['id'] ?>">
                          <div class="modal-header">
                            <h5 class="modal-title fw-bold">Edit Element ID #<?= $elem['id'] ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="mb-3 text-muted small">
                              <strong>Source document:</strong> <?= htmlspecialchars($elem['file_name']) ?> | <strong>Page:</strong> <?= $elem['page_number'] ?>
                            </div>
                            <div class="mb-3">
                              <label class="form-label fw-semibold">RAG Document Category</label>
                              <select name="document_category" class="form-select">
                                <option value="Immigration" <?= $elem['document_category'] === 'Immigration' ? 'selected' : '' ?>>Immigration Services</option>
                                <option value="Visit Visa" <?= $elem['document_category'] === 'Visit Visa' ? 'selected' : '' ?>>Visit Visa</option>
                                <option value="Business Setup" <?= $elem['document_category'] === 'Business Setup' ? 'selected' : '' ?>>Business Setup</option>
                                <option value="Government Policies" <?= $elem['document_category'] === 'Government Policies' ? 'selected' : '' ?>>Government Rules & Policies</option>
                                <option value="General" <?= $elem['document_category'] === 'General' ? 'selected' : '' ?>>General/Other</option>
                              </select>
                            </div>
                            <div class="mb-3">
                              <label class="form-label fw-semibold">Text Guideline Content</label>
                              <textarea name="text_content" class="form-control font-mono" rows="12" style="font-size: 0.85rem;" required><?= htmlspecialchars($elem['text_content']) ?></textarea>
                              <div class="form-text small text-muted">Overwriting this content will update RAG matching instantly on next chatbot interaction.</div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Update Raw Element</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php
      $pages = (int)ceil($total / $perPage);
      if ($pages > 1):
    ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
  </div>
</div>

</main>
<?php include __DIR__ . '/../../partials/footer.php'; ?>
