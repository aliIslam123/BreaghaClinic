<?php
require_once 'telegram-config.php';

function send_telegram_message($message) {
    // Build URL with parameters (like manual sending via GET)
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $url .= "?chat_id=" . urlencode(CHAT_ID);
    $url .= "&text=" . urlencode($message);
    $url .= "&parse_mode=HTML";

    // Use GET request via stream_context
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
        ]
    ]);

    $result = @file_get_contents($url, false, $context);

    if ($result === FALSE) {
        $errorMsg = date('Y-m-d H:i:s') . " - Failed to send Telegram: Connection error\n";
        error_log($errorMsg, 3, __DIR__ . '/../logs/telegram_errors.log');
        return false;
    }

    $response = json_decode($result, true);
    
    if (!isset($response['ok']) || !$response['ok']) {
        $desc = isset($response['description']) ? $response['description'] : 'Unknown error';
        $errorMsg = date('Y-m-d H:i:s') . " - Telegram error: $desc\n";
        error_log($errorMsg, 3, __DIR__ . '/../logs/telegram_errors.log');
        return false;
    }

    return true;

    $response = file_get_contents($url, false, $context);
    file_put_contents(__DIR__ . "/../logs/telegram_debug.log", date("Y-m-d H:i:s") . " RESPONSE: " . $response . "\n", FILE_APPEND);
}
?>