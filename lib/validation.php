<?php
// lib/validation.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_password_strength(string $pw): bool {
    if (strlen($pw) < 8) return false;
    if (!preg_match('/[A-Z]/', $pw)) return false;
    if (!preg_match('/[a-z]/', $pw)) return false;
    if (!preg_match('/[0-9]/', $pw)) return false;
    if (!preg_match('/[\W_]/', $pw)) return false;
    return true;
}

function sanitize_filename(string $name): string {
    $name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
    $name = preg_replace('/_+/', '_', $name);
    return trim($name, '_');
}
