<?php
// lib/middleware.php
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

function require_permission_or_die(string $perm) {
    global $domain;
    if (!current_user()) {
        // not logged in
        http_response_code(403);
        header('Location: '.$domain.'/login.php', true, 403);
        exit;
    }
    if (!can($perm)) {
        http_response_code(403);
        // AJAX request -> JSON 403
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'forbidden']);
            exit;
        }
        // If logged in but unauthorized, terminate with 403 directly to avoid redirect loops
        if (current_user()) {
            die('Forbidden: You do not have permission to access this resource.');
        }
        // If it's a provider, redirect to their dashboard instead of a 404/403 page if they try to access admin
        if (is_role('provider')) {
            header('Location: '.$domain.'/vendor/index.php');
            exit;
        }
        // Option A: redirect to login
        header('Location: '.$domain.'/login.php');
        exit;
    }
}
?>