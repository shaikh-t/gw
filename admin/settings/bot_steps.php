<?php
// admin/settings/bot_steps.php
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('manage_bot_steps');
require_once __DIR__ . '/../../lib/db_mysqli.php';
require_once __DIR__ . '/../../lib/csrf.php';

if (!isset($cspNonce)) {
    $cspNonce = base64_encode(random_bytes(16));
}

$success_message = '';
$error_message = '';

// Handle CRUD POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $step_key = trim($_POST['step_key'] ?? '');
        $step_order = (int)($_POST['step_order'] ?? 0);
        $primary_question_en = trim($_POST['primary_question_en'] ?? '');
        $primary_question_fr = trim($_POST['primary_question_fr'] ?? '');
        $primary_question_ar = trim($_POST['primary_question_ar'] ?? '');
        $primary_question_ur = trim($_POST['primary_question_ur'] ?? '');
        $interface_target = $_POST['interface_target'] ?? 'left_window';
        $execution_action = $_POST['execution_action'] ?? 'none';
        $parent_step_id = !empty($_POST['parent_step_id']) ? (int)$_POST['parent_step_id'] : null;
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

        if (empty($step_key) || empty($primary_question_en)) {
            $error_message = 'Step Key and English Translation are required.';
        } else {
            if ($action === 'create') {
                $stmt = $mysqli->prepare("INSERT INTO bot_workflow_steps (step_key, step_order, primary_question_en, primary_question_fr, primary_question_ar, primary_question_ur, interface_target, execution_action, parent_step_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param('sissssssi', $step_key, $step_order, $primary_question_en, $primary_question_fr, $primary_question_ar, $primary_question_ur, $interface_target, $execution_action, $parent_step_id);
                    if ($stmt->execute()) {
                        $success_message = 'Workflow step created successfully!';
                    } else {
                        $error_message = 'Failed to create workflow step: ' . $mysqli->error;
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $mysqli->prepare("UPDATE bot_workflow_steps SET step_key = ?, step_order = ?, primary_question_en = ?, primary_question_fr = ?, primary_question_ar = ?, primary_question_ur = ?, interface_target = ?, execution_action = ?, parent_step_id = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param('sissssssii', $step_key, $step_order, $primary_question_en, $primary_question_fr, $primary_question_ar, $primary_question_ur, $interface_target, $execution_action, $parent_step_id, $id);
                    if ($stmt->execute()) {
                        $success_message = 'Workflow step updated successfully!';
                    } else {
                        $error_message = 'Failed to update workflow step: ' . $mysqli->error;
                    }
                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $mysqli->prepare("DELETE FROM bot_workflow_steps WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $success_message = 'Workflow step deleted successfully!';
            } else {
                $error_message = 'Failed to delete workflow step: ' . $mysqli->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all steps
$steps = [];
$res = $mysqli->query("SELECT * FROM bot_workflow_steps ORDER BY step_order ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $steps[] = $row;
    }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 fw-bold text-dark font-serif mb-1">Conversational Funnel Steps</h1>
      <p class="text-secondary small mb-0">List, create, edit, delete, and re-order assistant workflow steps live.</p>
    </div>
    <button class="btn btn-primary" id="btnCreateStep">
      <i class="bi bi-plus-lg me-1"></i> Add Workflow Step
    </button>
  </div>

  <?php if ($success_message !== ''): ?>
    <div class="alert alert-success shadow-sm"><?= htmlspecialchars($success_message) ?></div>
  <?php endif; ?>

  <?php if ($error_message !== ''): ?>
    <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error_message) ?></div>
  <?php endif; ?>

  <!-- CRUD Table List -->
  <div class="card border-0 shadow-sm p-3 mb-4 bg-white rounded-4">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Order</th>
          <th>Step Key</th>
          <th>Translations (EN / FR / AR / UR)</th>
          <th>UI Target</th>
          <th>Execution Action</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($steps)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">No workflow steps configured. Click "Add Workflow Step" to begin.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($steps as $st): ?>
            <tr>
              <td><span class="badge bg-secondary font-mono"><?= (int)$st['step_order'] ?></span></td>
              <td><strong class="text-primary"><?= htmlspecialchars($st['step_key']) ?></strong></td>
              <td style="max-width: 300px;">
                <div class="small text-truncate"><strong>EN:</strong> <?= htmlspecialchars($st['primary_question_en']) ?></div>
                <div class="small text-truncate text-secondary"><strong>FR:</strong> <?= htmlspecialchars($st['primary_question_fr']) ?></div>
                <div class="small text-truncate text-secondary"><strong>AR:</strong> <?= htmlspecialchars($st['primary_question_ar']) ?></div>
                <div class="small text-truncate text-secondary"><strong>UR:</strong> <?= htmlspecialchars($st['primary_question_ur']) ?></div>
              </td>
              <td><span class="badge bg-dark"><?= htmlspecialchars($st['interface_target']) ?></span></td>
              <td><span class="badge bg-info text-dark font-mono"><?= htmlspecialchars($st['execution_action']) ?></span></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-secondary edit-step-btn" data-step='<?= json_encode($st, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                  <i class="bi bi-pencil-square"></i> Edit
                </button>
                <form action="bot_steps.php" method="post" class="d-inline-block delete-step-form">
                  <?= csrf_field(); ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$st['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Create/Edit Modal Dialog overlay -->
  <div class="modal fade" id="workflowStepModal" tabindex="-1" aria-labelledby="workflowStepModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form action="bot_steps.php" method="post" id="workflowStepForm">
          <?= csrf_field(); ?>
          <input type="hidden" name="action" value="create" id="formActionField">
          <input type="hidden" name="id" value="" id="formIdField">

          <div class="modal-header">
            <h5 class="modal-title fw-bold" id="workflowStepModalLabel">Configure Workflow Step</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="step_key" class="form-label fw-bold">Step Unique Key *</label>
                <input type="text" class="form-control" name="step_key" id="step_key" placeholder="e.g. welcome_funnel" required>
              </div>
              <div class="col-md-6">
                <label for="step_order" class="form-label fw-bold">Sort Order (Integer) *</label>
                <input type="number" class="form-control" name="step_order" id="step_order" value="10" required>
              </div>

              <div class="col-md-12">
                <label for="primary_question_en" class="form-label fw-bold">Primary Question (English) *</label>
                <textarea class="form-control" name="primary_question_en" id="primary_question_en" rows="2" required></textarea>
              </div>
              <div class="col-md-12">
                <label for="primary_question_fr" class="form-label fw-bold">Primary Question (French)</label>
                <textarea class="form-control" name="primary_question_fr" id="primary_question_fr" rows="2"></textarea>
              </div>
              <div class="col-md-12">
                <label for="primary_question_ar" class="form-label fw-bold">Primary Question (Arabic)</label>
                <textarea class="form-control" name="primary_question_ar" id="primary_question_ar" rows="2"></textarea>
              </div>
              <div class="col-md-12">
                <label for="primary_question_ur" class="form-label fw-bold">Primary Question (Urdu/Hindi)</label>
                <textarea class="form-control" name="primary_question_ur" id="primary_question_ur" rows="2"></textarea>
              </div>

              <div class="col-md-6">
                <label for="interface_target" class="form-label fw-bold">UI Window Target *</label>
                <select class="form-select" name="interface_target" id="interface_target" required>
                  <option value="left_window">Left Sidebar (Chat Widget)</option>
                  <option value="right_window">Right Workspace Container</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="execution_action" class="form-label fw-bold">Execution Action *</label>
                <select class="form-select" name="execution_action" id="execution_action" required>
                  <option value="none">None</option>
                  <option value="redirect_landing">Redirect to Landing</option>
                  <option value="hydrate_right_panel">Hydrate Right Panel (Categories)</option>
                  <option value="apply_filters">Apply Filters Layout</option>
                  <option value="dispatch_case_meeting">Dispatch Meeting Request</option>
                </select>
              </div>

              <div class="col-md-12">
                <label for="parent_step_id" class="form-label fw-bold">Parent Step Link</label>
                <select class="form-select" name="parent_step_id" id="parent_step_id">
                  <option value="">-- No Parent (Root Sequence) --</option>
                  <?php foreach ($steps as $st): ?>
                    <option value="<?= (int)$st['id'] ?>"><?= htmlspecialchars($st['step_key']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="saveBotStepFlowConfiguration">Save Step Configuration</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script nonce="<?php echo $cspNonce; ?>">
(function() {
    const btnCreate = document.getElementById("btnCreateStep");
    const saveBtn = document.getElementById("saveBotStepFlowConfiguration");
    const editBtns = document.querySelectorAll(".edit-step-btn");
    const deleteForms = document.querySelectorAll(".delete-step-form");
    const modalElement = document.getElementById("workflowStepModal");
    const modalTitle = document.getElementById("workflowStepModalLabel");

    let modalInstance = null;
    function initModal() {
        if (modalElement && typeof bootstrap !== "undefined" && !modalInstance) {
            modalInstance = new bootstrap.Modal(modalElement);
        }
    }

    if (btnCreate) {
        btnCreate.onclick = function() {
            initModal();
            if (modalInstance) {
                document.getElementById("workflowStepForm").reset();
                document.getElementById("formActionField").value = "create";
                document.getElementById("formIdField").value = "";
                modalTitle.innerText = "Add Workflow Step";
                modalInstance.show();
            }
        };
    }

    editBtns.forEach(btn => {
        btn.onclick = function() {
            initModal();
            const data = JSON.parse(this.getAttribute("data-step"));
            if (data && modalInstance) {
                document.getElementById("formActionField").value = "edit";
                document.getElementById("formIdField").value = data.id;
                document.getElementById("step_key").value = data.step_key;
                document.getElementById("step_order").value = data.step_order;
                document.getElementById("primary_question_en").value = data.primary_question_en;
                document.getElementById("primary_question_fr").value = data.primary_question_fr;
                document.getElementById("primary_question_ar").value = data.primary_question_ar;
                document.getElementById("primary_question_ur").value = data.primary_question_ur;
                document.getElementById("interface_target").value = data.interface_target;
                document.getElementById("execution_action").value = data.execution_action;
                document.getElementById("parent_step_id").value = data.parent_step_id || "";
                modalTitle.innerText = "Edit Workflow Step: " + data.step_key;
                modalInstance.show();
            }
        };
    });

    deleteForms.forEach(form => {
        form.onsubmit = function(e) {
            if (!confirm("Are you sure you want to delete this workflow step?")) {
                e.preventDefault();
            }
        };
    });
})();
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
