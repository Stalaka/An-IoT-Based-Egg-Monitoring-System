<?php

require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Escape text before printing it in HTML (stops script injection).
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function current_user(): ?array { return $_SESSION['user'] ?? null; }

function require_login(): void {
    if (!current_user()) { header('Location: login.php'); exit; }
}

function require_role(array $roles): void {
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied: your role cannot open this page.');
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void {
    $expected = $_SESSION['csrf'] ?? '';
    $given    = $_POST['csrf'] ?? '';
    // An empty expected token must never match an empty submitted token.
    if ($expected === '' || !is_string($given) || !hash_equals($expected, $given)) {
        http_response_code(400);
        exit('Invalid form token. Go back, refresh the page and try again.');
    }
}

// flash('text') stores a message; flash() reads it once and clears it.
function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
