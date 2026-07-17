<?php
// admin/import-pdf.php
require_once __DIR__ . '/../lib/middleware.php';
require_permission_or_die('cms.manage');

$success_message = '';
$error_message = '';

function strip_pdf_escapes($str) {
    // Unescape octal escape codes in PDF string streams (e.g. \343) and backslashes
    $str = preg_replace_callback('/\\\\([0-7]{3})/', function($m) {
        return chr(octdec($m[1]));
    }, $str);
    $str = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $str);
    return $str;
}

function parse_pdf_native($filename) {
    $content = file_get_contents($filename);
    if ($content === false) return [];

    // Attempt to segment by PDF page markers
    $pages_raw = preg_split('/\/Type\s*\/Page\b/', $content);
    $pages_text = [];

    if (count($pages_raw) <= 1) {
        // Fallback splitting if pages cannot be determined
        $pages_raw = str_split($content, 3000);
    }

    $page_num = 1;
    foreach ($pages_raw as $raw_page) {
        $text = '';
        // Grab content stream inside BT (Begin Text) and ET (End Text)
        preg_match_all('/BT\s*(.*?)\s*ET/s', $raw_page, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $text_block) {
                preg_match_all('/\((.*?)\)/s', $text_block, $str_matches);
                if (!empty($str_matches[1])) {
                    foreach ($str_matches[1] as $str) {
                        $text .= strip_pdf_escapes($str) . ' ';
                    }
                }
            }
        }

        $text = trim($text);
        // Fallback: if BT/ET has no readable data, parse the raw streams for strings
        if (strlen($text) < 10) {
            $clean_block = preg_replace('/[^\x20-\x7E\s]/', '', $raw_page);
            preg_match_all('/[a-zA-Z0-9\s,\.\-:\(\)\!]{10,200}/', $clean_block, $fallback_matches);
            if (!empty($fallback_matches[0])) {
                $text = implode("\n", array_map('trim', array_slice($fallback_matches[0], 0, 50)));
            }
        }

        $text = trim(preg_replace('/\s+/', ' ', $text));
        if (strlen($text) > 10) {
            $pages_text[$page_num] = $text;
            $page_num++;
        }
    }

    if (empty($pages_text)) {
        $clean_block = preg_replace('/[^\x20-\x7E\s]/', '', $content);
        preg_match_all('/[a-zA-Z0-9\s,\.\-:\(\)\!]{20,250}/', $clean_block, $fallback_matches);
        if (!empty($fallback_matches[0])) {
            $text = implode("\n", array_map('trim', array_slice($fallback_matches[0], 0, 100)));
            $pages_text[1] = trim(preg_replace('/\s+/', ' ', $text));
        }
    }

    return $pages_text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        die('Invalid CSRF');
    }

    $ingestion_type = isset($_POST['ingestion_type']) ? trim($_POST['ingestion_type']) : 'pdf';

    if ($ingestion_type === 'manual') {
        // Option A: Direct Manual Text Ingestion
        $section_title = trim($_POST['section_title'] ?? '');
        $document_category = trim($_POST['document_category'] ?? 'General');
        $text_content = trim($_POST['text_content'] ?? '');

        if ($section_title === '') {
            $error_message = "Section Title is required for manual text ingestion.";
        } elseif ($text_content === '') {
            $error_message = "Text Content cannot be left empty.";
        } else {
            $file_name = "Manual Section: " . $section_title;
            $page_number = 1;

            $stmt_ins = $mysqli->prepare("INSERT INTO `local_knowledge_base` (`file_name`, `document_category`, `page_number`, `text_content`) VALUES (?, ?, ?, ?)");
            if ($stmt_ins) {
                $stmt_ins->bind_param('ssis', $file_name, $document_category, $page_number, $text_content);
                if ($stmt_ins->execute()) {
                    $success_message = "Successfully ingested manual text asset: '{$section_title}' under '{$document_category}'.";
                } else {
                    $error_message = "Failed to ingest text asset: " . $mysqli->error;
                }
                $stmt_ins->close();
            } else {
                $error_message = "Database prepared statement error.";
            }
        }
    } else {
        // Option B: Secure PDF Document Ingestion
        $document_category = isset($_POST['document_category']) ? trim($_POST['document_category']) : 'General';
        if ($document_category === '') {
            $document_category = 'General';
        }

        if (!empty($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['pdf_file']['tmp_name'];
            $file_name = basename($_FILES['pdf_file']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($file_ext !== 'pdf') {
                $error_message = "Only PDF document files (.pdf) are allowed.";
            } else {
                $extracted_pages = parse_pdf_native($file_tmp);

                if (empty($extracted_pages)) {
                    $error_message = "Unable to extract any readable text elements from the uploaded PDF document.";
                } else {
                    $inserted_count = 0;
                    $mysqli->begin_transaction();
                    try {
                        $stmt_ins = $mysqli->prepare("INSERT INTO `local_knowledge_base` (`file_name`, `document_category`, `page_number`, `text_content`) VALUES (?, ?, ?, ?)");
                        if ($stmt_ins) {
                            foreach ($extracted_pages as $page_no => $content) {
                                $stmt_ins->bind_param('ssis', $file_name, $document_category, $page_no, $content);
                                $stmt_ins->execute();
                                $inserted_count++;
                            }
                            $stmt_ins->close();
                        }
                        $mysqli->commit();
                        $success_message = "Successfully ingested PDF file: '{$file_name}' as '{$document_category}'. Indexed {$inserted_count} page(s) into our Local RAG Knowledge Base.";
                    } catch (Exception $ex) {
                        $mysqli->rollback();
                        $error_message = "Database ingestion failed: " . $ex->getMessage();
                    }
                }
            }
        } else {
            $error_message = "Please select a valid PDF file to upload.";
        }
    }
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';

echo '<main class="main-content p-4">';
?>

<div class="container-fluid mt-2">
  <div class="mb-4">
    <h2 class="h4 mb-1 fw-bold text-dark"><i class="bi bi-cloud-arrow-up-fill text-primary"></i> Dual-Ingestion RAG Pipeline Workspace</h2>
    <p class="text-muted small">Choose between direct manual text ingestion or secure PDF text extraction to enrich the Local RAG search indexing.</p>
  </div>

  <?php if ($success_message !== ''): ?>
    <div class="alert alert-success shadow-sm alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($error_message !== ''): ?>
    <div class="alert alert-danger shadow-sm alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error_message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Option A: Direct Manual Text Ingestion -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm p-4 bg-white rounded-4 h-100">
        <h4 class="h5 fw-bold text-dark mb-3"><i class="bi bi-fonts text-primary"></i> Option A: Direct Manual Text Ingestion</h4>
        <p class="text-muted small mb-4">Enter guidelines, policies, or manual QA texts directly into the index database.</p>

        <form action="import-pdf.php" method="post">
          <?= csrf_field(); ?>
          <input type="hidden" name="ingestion_type" value="manual">

          <div class="mb-3">
            <label class="form-label fw-semibold">Section Title</label>
            <input type="text" name="section_title" class="form-control" placeholder="e.g. Dubai Golden Visa Eligibility Guidelines" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Document Category</label>
            <select name="document_category" class="form-select">
              <option value="Immigration">Immigration Services</option>
              <option value="Visit Visa">Visit Visa</option>
              <option value="Business Setup">Business Setup</option>
              <option value="Government Policies">Government Rules & Policies</option>
              <option value="General">General/Other</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Text Content</label>
            <textarea name="text_content" class="form-control font-mono" rows="8" placeholder="Type or paste the verified guideline text content here..." style="font-size: 0.85rem;" required></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold">
            <i class="bi bi-check-circle-fill me-1"></i> Index Manual Text Content
          </button>
        </form>
      </div>
    </div>

    <!-- Option B: Secure PDF Document Ingestion -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm p-4 bg-white rounded-4 h-100">
        <h4 class="h5 fw-bold text-dark mb-3"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> Option B: Secure PDF Document Ingestion</h4>
        <p class="text-muted small mb-4">Upload native PDF documents. The system parses content page-by-page and securely indexes elements.</p>

        <form action="import-pdf.php" method="post" enctype="multipart/form-data">
          <?= csrf_field(); ?>
          <input type="hidden" name="ingestion_type" value="pdf">

          <div class="mb-4">
            <label class="form-label fw-semibold">Select PDF Document</label>
            <input type="file" name="pdf_file" accept=".pdf" class="form-control" required>
            <div class="form-text small text-muted">Upload native PDF files (max 8MB). Pure-PHP parsed.</div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Document Category</label>
            <select name="document_category" class="form-select">
              <option value="Immigration">Immigration Services</option>
              <option value="Visit Visa">Visit Visa</option>
              <option value="Business Setup">Business Setup</option>
              <option value="Government Policies">Government Rules & Policies</option>
              <option value="General">General/Other</option>
            </select>
          </div>

          <div class="mb-4 pt-4"></div>

          <button type="submit" class="btn btn-danger w-100 py-2.5 rounded-pill fw-bold">
            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Ingest & Index PDF Document
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-start mt-4 pt-2">
    <a href="crm/knowledge-base.php" class="btn btn-outline-secondary rounded-pill me-2"><i class="bi bi-pencil-square"></i> Manage Knowledge Base</a>
    <a href="dashboard.php" class="btn btn-dark rounded-pill">Return to Dashboard</a>
  </div>
</div>

</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
