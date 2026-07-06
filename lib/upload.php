<?php
// lib/upload.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/**
 * Placeholder virus scan hook.
 * Implement integration with ClamAV, VirusTotal, or other scanner.
 * Must return true if file is clean, false if infected.
 */
function virus_scan_file(string $filepath): bool {
    // TODO: integrate real virus scanner here.
    // For now, assume clean.
    return true;
}

/**
 * Enqueue a file for async scanning.
 * This writes a simple queue record to disk (or DB) for a worker to process.
 */
function enqueue_file_for_scan(string $filepath, array $meta = []): bool {
    $queueDir = __DIR__ . '/../var/scan_queue';
    if (!is_dir($queueDir)) mkdir($queueDir, 0755, true);
    $item = [
        'file' => $filepath,
        'meta' => $meta,
        'created_at' => date('c')
    ];
    $filename = $queueDir . '/' . bin2hex(random_bytes(8)) . '.json';
    return (bool)file_put_contents($filename, json_encode($item));
}

/**
 * Generic file upload handler supporting images and PDFs.
 * Returns ['ok'=>true,'path'=>..., 'filename'=>..., 'pending_scan'=>bool] or ['ok'=>false,'error'=>...]
 */
function file_upload_handle(array $file, string $destDir, int $maxBytes = 5 * 1024 * 1024, bool $asyncScan = true): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'Invalid upload'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload error code ' . $file['error']];
    }
    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'File too large'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedImages = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $allowedDocs = ['application/pdf' => 'pdf'];

    if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
        return ['ok' => false, 'error' => 'Failed to create upload directory'];
    }

    $ext = null;
    $isImage = false;
    if (isset($allowedImages[$mime])) {
        $ext = $allowedImages[$mime];
        $isImage = true;
    } elseif (isset($allowedDocs[$mime])) {
        $ext = $allowedDocs[$mime];
    } else {
        return ['ok' => false, 'error' => 'Unsupported file type'];
    }

    $base = bin2hex(random_bytes(12));
    $filename = $base . '.' . $ext;
    $target = rtrim($destDir, '/') . '/' . $filename;

    if ($isImage && function_exists('getimagesize') && function_exists('imagecreatetruecolor')) {
        $info = getimagesize($file['tmp_name']);
        if ($info) {
            list($w, $h) = $info;
            $max = 400;
            if ($w > $max || $h > $max) {
                $ratio = min($max / $w, $max / $h);
                $nw = (int)($w * $ratio);
                $nh = (int)($h * $ratio);
                switch ($mime) {
                    case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
                    case 'image/png': $src = imagecreatefrompng($file['tmp_name']); break;
                    case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
                    default: $src = null;
                }
                if ($src) {
                    $dst = imagecreatetruecolor($nw, $nh);
                    if ($mime === 'image/png' || $mime === 'image/webp') {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                    }
                    imagecopyresampled($dst, $src, 0,0,0,0, $nw, $nh, $w, $h);
                    switch ($mime) {
                        case 'image/jpeg': imagejpeg($dst, $target, 90); break;
                        case 'image/png': imagepng($dst, $target, 6); break;
                        case 'image/webp': imagewebp($dst, $target, 80); break;
                    }
                    imagedestroy($src);
                    imagedestroy($dst);
                    // attempt immediate scan
                    if (!virus_scan_file($target)) {
                        @unlink($target);
                        return ['ok' => false, 'error' => 'File failed virus scan'];
                    }
                    return ['ok' => true, 'path' => $target, 'filename' => $filename, 'pending_scan' => false];
                }
            }
        }
    }

    // For PDFs or images that didn't need resizing, move as-is
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'Failed to move uploaded file'];
    }

    // If asyncScan is true, enqueue for scanning and mark pending
    if ($asyncScan) {
        enqueue_file_for_scan($target, ['original_name' => $file['name'], 'mime' => $mime]);
        return ['ok' => true, 'path' => $target, 'filename' => $filename, 'pending_scan' => true];
    }

    // synchronous scan
    if (!virus_scan_file($target)) {
        @unlink($target);
        return ['ok' => false, 'error' => 'File failed virus scan'];
    }

    return ['ok' => true, 'path' => $target, 'filename' => $filename, 'pending_scan' => false];
}

/**
 * Backwards-compatible avatar upload wrapper
 */
function avatar_upload_handle(array $file, string $destDir, int $maxBytes = 2 * 1024 * 1024): array {
    return file_upload_handle($file, $destDir, $maxBytes, true);
}
