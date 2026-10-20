<?php

ob_start();

// ═══════════════════════════════════════════════════════
// UrlBeautifier — job que reescreve URLs feias → limpas
// (ex: /checkout?product=pro&price=9 → /checkout/pro/9)
// ═══════════════════════════════════════════════════════
$beautifierPath = __DIR__ . '/../app/Support/UrlBeautifier.php';
if (is_file($beautifierPath)) {
    require_once $beautifierPath;
    \App\Support\UrlBeautifier::registerDefaults();
    if (\App\Support\UrlBeautifier::apply()) {
        exit;
    }
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
