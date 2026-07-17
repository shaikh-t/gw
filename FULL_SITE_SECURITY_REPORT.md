# GlobalWays Marketplace - Full-Site Security Audit & Penetration Testing Assessment

This security report is compiled following an exhaustive, codebase-wide adversarial security audit and penetration testing assessment across all user-segments (Admin, Provider/Vendor, and Customer/Guest frontends) of the GlobalWays Marketplace repository.

---

### PILLAR 1: BROKEN OBJECT-LEVEL AUTHORIZATION & IDOR/BOLA GAPS

#### 1. Vulnerability / Exploit Title
Privilege Escalation via BOLA/IDOR in CRM Customer Profile Editing

#### 2. Target File Path & Affected Line Ranges
`admin/crm/edit.php` (Lines 4-22), `admin/crm/update.php`

#### 3. Penetration Test Exploit Scenario & Business Impact
The application performs a permission check `require_permission_or_die('users.manage')` which grants administrative access to edit customer profiles. However, it fails to perform a role hierarchy verification.
An attacker possessing lower administrative or manager privileges can manipulate the POST payload or URL parameter `uuid` in `admin/crm/edit.php` to target a Super Admin's UUID. They can then modify the email or save a new password, effectively locking out and hijacking the Super Admin account, leading to a complete compromise of the platform.

#### 4. Remediation Code Patch
```php
// admin/crm/edit.php (Add role hierarchy validation)
require_once __DIR__ . '/../../lib/middleware.php';
require_permission_or_die('users.manage');

$uuid = isset($_GET['uuid']) ? trim($_GET['uuid']) : '';
$customer = null;

$stmt = $mysqli->prepare("SELECT * FROM users WHERE uuid = ? AND deleted_at IS NULL LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $uuid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $customer = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$customer) {
    $_SESSION['flash_errors'] = 'Customer profile not found.';
    header('Location: index.php');
    exit;
}

// SECURE REMEDIATION GATE: Check if target user has a Super Admin role
$target_user_id = (int)$customer['id'];
$stmt_check = $mysqli->prepare("
    SELECT r.name
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.id
    WHERE ur.user_id = ? AND r.name = 'Super Admin'
");
if ($stmt_check) {
    $stmt_check->bind_param('i', $target_user_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check && $res_check->num_rows > 0) {
        // Only a logged-in Super Admin is authorized to edit another Super Admin
        if (!is_role('Super Admin')) {
            http_response_code(403);
            die("Security Escalation Blocked: Non-Super Admin cannot modify a Super Admin profile.");
        }
    }
    $stmt_check->close();
}
```

---

### PILLAR 2: STORED & REFLECTED CROSS-SITE SCRIPTING (XSS) PROTECTION

#### 1. Vulnerability / Exploit Title
Stored XSS Vulnerability via Client-Side InnerHTML Dynamic Text Injections

#### 2. Target File Path & Affected Line Ranges
`templates/bot-widget.php` (Lines 371-385)

#### 3. Penetration Test Exploit Scenario & Business Impact
In the overlay chatbot widget, incoming responses from the bot are dynamically appended to the stream:
```javascript
function addMessageToStream(sender, text) {
  const stream = document.getElementById('botChatStream');
  const bubble = document.createElement('div');
  bubble.className = `bot-message ${sender}`;
  bubble.innerHTML = text; // Vulnerable Line
  stream.appendChild(bubble);
  ...
}
```
If an attacker exploits the local RAG pipeline or uses a custom direct-sponsor campaign to inject a payload with HTML markup (e.g. `<img src=x onerror=alert(document.cookie)>`), this script will execute directly in the browser of any user opening the assistant widget, resulting in session hijacking or malicious payload deliveries.

#### 4. Remediation Code Patch
```javascript
// templates/bot-widget.php (Remediation snippet)
function addMessageToStream(sender, text) {
  const stream = document.getElementById('botChatStream');
  const bubble = document.createElement('div');
  bubble.className = `bot-message ${sender}`;

  // SECURE REMEDIATION: Always use textContent to ensure raw tags are not parsed as active elements
  bubble.textContent = text;

  stream.appendChild(bubble);
  stream.scrollTop = stream.scrollHeight;
}
```

---

### PILLAR 3: HARDENED SESSION FIXATION & SESSION HIJACKING PREVENTION

#### 1. Vulnerability / Exploit Title
Insecure Global Session Settings (Missing Secure, HttpOnly, and SameSite Flags)

#### 2. Target File Path & Affected Line Ranges
`lib/auth.php` (Line 3), and globally before every `session_start()` declaration.

#### 3. Penetration Test Exploit Scenario & Business Impact
Standard PHP session starts without pre-defined cookie parameters allow browser cookies to be visible to client-side scripts. An attacker exploiting a minor reflected or stored XSS bug on any page can easily steal the active user session cookie (`PHPSESSID`) via `document.cookie` and gain immediate unauthorized portal access, bypassing multi-factor or standard passwords.

#### 4. Remediation Code Patch
```php
// lib/auth.php (Add strict global cookie attributes prior to session start)
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => true,     // Sent only over secure HTTPS
            'httponly' => true,   // Block access via document.cookie javascript
            'samesite' => 'Strict' // Protect against Cross-Site Request Forgeries
        ]);
    } else {
        session_set_cookie_params(0, '/; SameSite=Strict; Secure; HttpOnly');
    }
    session_start();
}
```

---

### PILLAR 4: BATCH MULTI-ROW PARAMETERIZED SQL INJECTION (SQLi) VERIFICATION

#### 1. Vulnerability / Exploit Title
Dynamic String-Concatenation & real_escape_string SQLi Risk in Provider Search Filter

#### 2. Target File Path & Affected Line Ranges
`vendors.php` (Lines 20-60)

#### 3. Penetration Test Exploit Scenario & Business Impact
Inside `vendors.php`, filter query strings are sanitized with `$mysqli->real_escape_string()` and directly concatenated inside double quotes to construct the SQL query:
```php
if ($city !== 'All Cities') {
    $where[] = "city = '" . $mysqli->real_escape_string($city) . "'";
}
```
Although escape-string mitigates simple payload injections, certain database charsets allow multi-byte escape bypasses, and string concatenation violates the platform constraint requiring strictly-typed parameterized MySQLi prepared statements across all database actions.

#### 4. Remediation Code Patch
```php
// vendors.php (Refactor to use strictly typed parameterized mysqli query)
$where = ["1=1"];
$types = "";
$params = [];

if ($q !== '') {
    $where[] = "(name LIKE ? OR description LIKE ? OR specialties LIKE ?)";
    $like_q = "%" . $q . "%";
    $types .= "sss";
    array_push($params, $like_q, $like_q, $like_q);
}

if ($city !== 'All Cities') {
    $where[] = "city = ?";
    $types .= "s";
    $params[] = $city;
}

if ($type !== 'All Types') {
    $where[] = "specialties LIKE ?";
    $like_type = "%" . $type . "%";
    $types .= "s";
    $params[] = $like_type;
}

$where_sql = implode(' AND ', $where);
$sql = "SELECT * FROM providers WHERE $where_sql ORDER BY $order_by";

$stmt = $mysqli->prepare($sql);
if ($stmt) {
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $providers = [];
    while ($row = $res->fetch_assoc()) {
        $providers[] = $row;
    }
    $stmt->close();
}
```

---
