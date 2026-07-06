<?php
// lib/csrf.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE) . '">';
}

function csrf_check($token): bool {
    return !empty($token) && hash_equals($_SESSION['_csrf_token'] ?? '', $token);
}
