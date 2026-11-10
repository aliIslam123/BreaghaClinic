<?php
// Keep Telegram credentials outside Git and outside the public document root.
$localConfig = __DIR__ . '/telegram-config.local.php';
$hostConfig = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'telegram-config.php';
$telegramConfig = is_file($localConfig) ? $localConfig : $hostConfig;

if (is_file($telegramConfig)) {
    require_once $telegramConfig;
} else {
    if (!defined('BOT_TOKEN')) {
        define('BOT_TOKEN', '');
    }
    if (!defined('CHAT_ID')) {
        define('CHAT_ID', '');
    }
}
