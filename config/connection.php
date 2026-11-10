<?php
// Keep database credentials in an ignored local file or outside public_html.
$localConfig = __DIR__ . '/connection.local.php';
$hostConfig = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'connection.php';
$connectionConfig = is_file($localConfig) ? $localConfig : $hostConfig;

if (!is_file($connectionConfig)) {
    error_log('Breagha database configuration is missing.');
    http_response_code(500);
    exit('Database configuration is unavailable.');
}

require_once $connectionConfig;
