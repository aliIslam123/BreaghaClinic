<?php
// init.php

// Base URL: empty on production (root), subfolder path on localhost
$host = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (strpos($host, 'localhost') !== false);
$base_url = $is_local ? '/Breagha project' : '';

// Set a project-specific session save path to ensure it's always writable
$sessionPath = __DIR__ . '/../sessions';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0777, true);
}
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

// إعداد الكوكيز الخاصة بالجلسة قبل تشغيلها
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookieParams['path'] ?? '/',
    'domain' => $cookieParams['domain'] ?? ($_SERVER['HTTP_HOST'] ?? ''),
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax'
]);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
ini_set('session.gc_maxlifetime', 1440);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

if (empty($_SESSION['csrf_token'])) {
    generate_csrf_token();
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function verify_csrf(): ?string {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return null;
    }

    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return 'طلب غير صالح (CSRF) - توكن مفقود.';
    }

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        return 'طلب غير صالح (CSRF) - التوكن غير متطابق.';
    }

    return null;
}

function verify_csrf_or_die() {
    $error = verify_csrf();
    if ($error !== null) {
        die($error);
    }
    // Regenerate token after successful verification to prevent reuse
    generate_csrf_token();
}
?>
