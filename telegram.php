<?php
require_once __DIR__ . '/../../../.configs/config.php';

// Escaped Text für Telegrams parse_mode=HTML. WICHTIG bei allem, was aus
// Nutzer-/Link-Inhalten stammt (z.B. URLs mit "&") — sonst lehnt Telegram
// die Nachricht komplett ab oder stellt sie falsch dar.
function tg_esc($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function telegram_send($text, $chat_id = null) {
    $chat_id = $chat_id ?: TELEGRAM_CHAT_ID;
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $payload = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML',
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
