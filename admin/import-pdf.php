<?php
// admin/import-pdf.php
require_once __DIR__ . '/../lib/middleware.php';
// Gated behind standard cms.manage permissions
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
        // Robust fallback: if BT/ET has no readable data, parse the raw streams for strings
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

    // Default top-level block grab if still empty
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
            // Extract text page-by-page using secure, shell-free helper
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

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';

echo '<main class="main-content p-4">';
?>

<div class="container mt-2">
  <div class="card shadow-sm p-4" style="max-width: 650px; margin: 0 auto;">
    <div class="mb-4">
      <h3 class="h4 mb-1 fw-bold text-dark"><i class="bi bi-file-earmark-pdf-fill text-danger"></i> Local PDF Knowledge Ingestion Pipeline</h3>
      <p class="text-muted small">Index documentation into our Local RAG Full-Text search engine using secure, native character extraction.</p>
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

    <form action="import-pdf.php" method="post" enctype="multipart/form-data">
      <?= csrf_field(); ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Select PDF Document</label>
        <input type="file" name="pdf_file" accept=".pdf" class="form-control" required>
        <div class="form-text small text-muted">Upload native PDF documents (max 8MB). Shell exec() functions are strictly bypassed for safety.</div>
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
        <div class="form-text small text-muted">Select categorization path to focus RAG contexts during matching.</div>
      </div>

      <div class="d-grid gap-2 border-top pt-3">
        <button type="submit" class="btn btn-primary py-2.5 fw-bold rounded-pill shadow-xs">
          <i class="bi bi-cloud-arrow-up-fill me-1"></i> Ingest & Index Document
        </button>
        <a href="dashboard.php" class="btn btn-outline-secondary py-2 rounded-pill">Return to Dashboard</a>
      </div>
    </form>
  </div>
</div>

</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
