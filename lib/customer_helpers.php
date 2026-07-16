<?php
// lib/customer_helpers.php
require_once __DIR__ . '/db_mysqli.php';
require_once __DIR__ . '/uuid_helper.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/**
 * Ensure customer has dynamic mock data. Seeding is completely idempotent.
 */
function ensure_customer_seeded(int $userId): void {
    global $mysqli;

    // Check if user has applications. If so, they are already seeded.
    $res = $mysqli->query("SELECT id FROM customer_applications WHERE user_id = $userId LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $res->free();
        return;
    }
    if ($res && !is_bool($res)) $res->free();

    // 1. Seed applications
    $apps = [
        [
            'service_name' => 'Golden Visa',
            'tracking_id' => 'UAE-2026-000982',
            'vendor_name' => 'Emirates Pro Services',
            'status' => 'In Progress',
            'progress' => 65,
            'submitted_at' => '2026-06-01',
            'est_completion' => '2026-06-12',
            'last_update' => '2 hours ago',
            'next_action' => 'Medical Test',
            'amount' => 5000.00,
            'paid_amount' => 2500.00
        ],
        [
            'service_name' => 'Family Visa',
            'tracking_id' => 'UAE-2026-000975',
            'vendor_name' => 'FastTrack Visa Services',
            'status' => 'Document Review',
            'progress' => 45,
            'submitted_at' => '2026-05-28',
            'est_completion' => '2026-06-15',
            'last_update' => '1 day ago',
            'next_action' => 'Upload Passport Copy',
            'amount' => 3000.00,
            'paid_amount' => 3000.00
        ],
        [
            'service_name' => 'Emirates ID',
            'tracking_id' => 'UAE-2026-000968',
            'vendor_name' => 'Emirates Pro Services',
            'status' => 'Almost Complete',
            'progress' => 90,
            'submitted_at' => '2026-05-25',
            'est_completion' => '2026-06-10',
            'last_update' => '3 days ago',
            'next_action' => 'Collect Card',
            'amount' => 500.00,
            'paid_amount' => 500.00
        ],
        [
            'service_name' => 'Tourist Visa',
            'tracking_id' => 'UAE-2026-000945',
            'vendor_name' => 'Gulf Visa Experts',
            'status' => 'Completed',
            'progress' => 100,
            'submitted_at' => '2026-05-15',
            'est_completion' => '2026-05-20',
            'last_update' => '2 weeks ago',
            'next_action' => 'None',
            'amount' => 500.00,
            'paid_amount' => 500.00
        ]
    ];

    foreach ($apps as $a) {
        $uuid = generate_uuid();
        $stmt = $mysqli->prepare("INSERT INTO customer_applications (uuid, user_id, service_name, tracking_id, vendor_name, status, progress, submitted_at, est_completion, last_update, next_action, amount, paid_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sissssissssdd',
            $uuid,
            $userId,
            $a['service_name'],
            $a['tracking_id'],
            $a['vendor_name'],
            $a['status'],
            $a['progress'],
            $a['submitted_at'],
            $a['est_completion'],
            $a['last_update'],
            $a['next_action'],
            $a['amount'],
            $a['paid_amount']
        );
        $stmt->execute();
        $stmt->close();
    }

    // 2. Seed documents
    $docs = [
        [
            'name' => 'Passport Copy',
            'status' => 'Verified',
            'uploaded_at' => '2026-06-01',
            'expires_at' => '2030-12-15',
            'file_type' => 'PDF',
            'file_size' => '2.4 MB',
            'tags' => 'Golden Visa, Family Visa'
        ],
        [
            'name' => 'Bank Statement - May 2026',
            'status' => 'Verified',
            'uploaded_at' => '2026-06-01',
            'expires_at' => '2026-08-01',
            'file_type' => 'PDF',
            'file_size' => '1.8 MB',
            'tags' => 'Golden Visa'
        ],
        [
            'name' => 'Passport Photo',
            'status' => 'Verified',
            'uploaded_at' => '2026-06-01',
            'expires_at' => 'N/A',
            'file_type' => 'JPG',
            'file_size' => '450 KB',
            'tags' => 'Golden Visa, Emirates ID'
        ],
        [
            'name' => 'Emirates ID (Front)',
            'status' => 'Expiring Soon',
            'uploaded_at' => '2026-05-25',
            'expires_at' => '2029-05-25',
            'file_type' => 'JPG',
            'file_size' => '890 KB',
            'tags' => 'Emirates ID'
        ],
        [
            'name' => 'Medical Report',
            'status' => 'Required',
            'uploaded_at' => '0000-00-00',
            'expires_at' => 'N/A',
            'file_type' => '—',
            'file_size' => '—',
            'tags' => 'Golden Visa'
        ]
    ];

    foreach ($docs as $d) {
        $uuid = generate_uuid();
        $stmt = $mysqli->prepare("INSERT INTO customer_documents (uuid, user_id, name, status, uploaded_at, expires_at, file_type, file_size, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sisssssss',
            $uuid,
            $userId,
            $d['name'],
            $d['status'],
            $d['uploaded_at'],
            $d['expires_at'],
            $d['file_type'],
            $d['file_size'],
            $d['tags']
        );
        $stmt->execute();
        $stmt->close();
    }

    // 3. Seed payments
    $payments = [
        [
            'service_name' => 'Golden Visa',
            'amount' => 2500.00,
            'status' => 'Completed',
            'payment_date' => '2026-06-01',
            'method' => 'Credit Card',
            'invoice_num' => 'INV-2026-001'
        ],
        [
            'service_name' => 'Family Visa',
            'amount' => 3000.00,
            'status' => 'Completed',
            'payment_date' => '2026-05-28',
            'method' => 'Credit Card',
            'invoice_num' => 'INV-2026-002'
        ],
        [
            'service_name' => 'Emirates ID',
            'amount' => 500.00,
            'status' => 'Completed',
            'payment_date' => '2026-05-25',
            'method' => 'Debit Card',
            'invoice_num' => 'INV-2026-003'
        ],
        [
            'service_name' => 'Tourist Visa',
            'amount' => 500.00,
            'status' => 'Completed',
            'payment_date' => '2026-05-15',
            'method' => 'Credit Card',
            'invoice_num' => 'INV-2026-004'
        ]
    ];

    foreach ($payments as $p) {
        $uuid = generate_uuid();
        $stmt = $mysqli->prepare("INSERT INTO customer_payments (uuid, user_id, service_name, amount, status, payment_date, method, invoice_num) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sisdssss',
            $uuid,
            $userId,
            $p['service_name'],
            $p['amount'],
            $p['status'],
            $p['payment_date'],
            $p['method'],
            $p['invoice_num']
        );
        $stmt->execute();
        $stmt->close();
    }

    // 4. Seed messages
    $msgs = [
        ['sender' => 'Emirates Pro Services', 'text' => 'Hello! Thank you for choosing Emirates Pro Services.', 'time' => '2026-06-07 10:00:00'],
        ['sender' => 'You', 'text' => 'Hi, I wanted to check the status of my Golden Visa application.', 'time' => '2026-06-07 10:05:00'],
        ['sender' => 'Emirates Pro Services', 'text' => 'Your application is progressing well. We have successfully submitted it to the authorities.', 'time' => '2026-06-07 10:07:00'],
        ['sender' => 'Emirates Pro Services', 'text' => 'The next step is the medical test. Appointment on June 10th at 9:00 AM at Dubai Healthcare City.', 'time' => '2026-06-07 10:08:00'],
        ['sender' => 'You', 'text' => 'Perfect! What documents should I bring?', 'time' => '2026-06-07 10:10:00'],
        ['sender' => 'Emirates Pro Services', 'text' => 'Please bring your passport, application receipt, and the medical form we sent you.', 'time' => '2026-06-08 06:00:00']
    ];

    foreach ($msgs as $m) {
        $uuid = generate_uuid();
        $stmt = $mysqli->prepare("INSERT INTO customer_messages (uuid, user_id, sender, service_name, message_text, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $svc_name = 'Golden Visa';
        $stmt->bind_param('sissss',
            $uuid,
            $userId,
            $m['sender'],
            $svc_name,
            $m['text'],
            $m['time']
        );
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Fetch all applications for a customer
 */
function get_customer_applications(int $userId): array {
    global $mysqli;
    ensure_customer_seeded($userId);
    $apps = [];
    $stmt = $mysqli->prepare("SELECT * FROM customer_applications WHERE user_id = ? ORDER BY id DESC");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $apps[] = $row;
        }
        $stmt->close();
    }
    return $apps;
}

/**
 * Fetch single application
 */
function get_customer_application(int $userId, string $uuid): ?array {
    global $mysqli;
    ensure_customer_seeded($userId);
    $app = null;
    $stmt = $mysqli->prepare("SELECT * FROM customer_applications WHERE user_id = ? AND uuid = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('is', $userId, $uuid);
        $stmt->execute();
        $res = $stmt->get_result();
        $app = $res->fetch_assoc();
        $stmt->close();
    }
    return $app;
}

/**
 * Fetch all documents
 */
function get_customer_documents(int $userId): array {
    global $mysqli;
    ensure_customer_seeded($userId);
    $docs = [];
    $stmt = $mysqli->prepare("SELECT * FROM customer_documents WHERE user_id = ? ORDER BY status ASC, id DESC");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $docs[] = $row;
        }
        $stmt->close();
    }
    return $docs;
}

/**
 * Fetch all payments
 */
function get_customer_payments(int $userId): array {
    global $mysqli;
    ensure_customer_seeded($userId);
    $payments = [];
    $stmt = $mysqli->prepare("SELECT * FROM customer_payments WHERE user_id = ? ORDER BY payment_date DESC, id DESC");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $payments[] = $row;
        }
        $stmt->close();
    }
    return $payments;
}

/**
 * Fetch all chat messages
 */
function get_customer_messages(int $userId): array {
    global $mysqli;
    ensure_customer_seeded($userId);
    $messages = [];
    $stmt = $mysqli->prepare("SELECT * FROM customer_messages WHERE user_id = ? ORDER BY created_at ASC");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    }
    return $messages;
}

/**
 * Send a new chat message
 */
function add_customer_message(int $userId, string $text, string $sender = 'You', string $service = 'Golden Visa'): bool {
    global $mysqli;
    $uuid = generate_uuid();
    $stmt = $mysqli->prepare("INSERT INTO customer_messages (uuid, user_id, sender, service_name, message_text) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sisss', $uuid, $userId, $sender, $service, $text);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Pay remaining balance for application
 */
function pay_customer_application(int $userId, string $appUuid): bool {
    global $mysqli;
    $app = get_customer_application($userId, $appUuid);
    if (!$app) return false;

    $due = $app['amount'] - $app['paid_amount'];
    if ($due <= 0) return true;

    // Update application as fully paid
    $stmt = $mysqli->prepare("UPDATE customer_applications SET paid_amount = amount WHERE id = ?");
    $stmt->bind_param('i', $app['id']);
    $stmt->execute();
    $stmt->close();

    // Insert payment record
    $payUuid = generate_uuid();
    $invoiceNum = 'INV-2026-' . sprintf('%03d', rand(10, 99));
    $stmt_pay = $mysqli->prepare("INSERT INTO customer_payments (uuid, user_id, service_name, amount, status, payment_date, method, invoice_num) VALUES (?, ?, ?, ?, 'Completed', NOW(), 'Credit Card', ?)");
    $stmt_pay->bind_param('sisds', $payUuid, $userId, $app['service_name'], $due, $invoiceNum);
    $stmt_pay->execute();
    $stmt_pay->close();

    return true;
}

/**
 * Upload doc
 */
function upload_customer_document(int $userId, string $docUuid, string $filename): bool {
    global $mysqli;
    $status = 'Verified';
    $fileType = strtoupper(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'PDF';
    $fileSize = '1.2 MB';

    $stmt = $mysqli->prepare("UPDATE customer_documents SET status = ?, uploaded_at = NOW(), file_type = ?, file_size = ? WHERE user_id = ? AND uuid = ?");
    $stmt->bind_param('sssis', $status, $fileType, $fileSize, $userId, $docUuid);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
