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
        // AJAX request -> JSON 403
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'forbidden']);
            exit;
        }
        // normal request -> redirect or show forbidden page
        http_response_code(403);
        // If it's a provider, redirect to their dashboard instead of a 404/403 page if they try to access admin
        if (is_role('provider')) {
            header('Location: '.$domain.'/vendor/index.php');
            exit;
        }
        // Option A: redirect to a no-access page or login
        header('Location: '.$domain.'/login.php');
        exit;
    }
}
?>