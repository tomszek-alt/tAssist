<?php
require_once __DIR__ . '/../../../.configs/config.php';

function claude_describe_image($base64, $mediaType) {
    $body = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => 500,
        'messages' => [[
            'role' => 'user',
            'content' => [
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mediaType, 'data' => $base64]],
                ['type' => 'text', 'text' =>
                    "Beschreibe kurz, was auf dem Bild zu sehen ist (1-2 Sätze). " .
                    "Falls Text/eine Liste im Bild steht (z.B. handschriftliche Notiz, Einkaufsliste, " .
                    "Screenshot), gib zusätzlich JEDEN Punkt einzeln in einer eigenen Zeile aus, " .
                    "mit '- ' davor. Antworte auf Deutsch, ohne Einleitung."],
            ],
        ]],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . CLAUDE_API_KEY,
        'anthropic-version: 2023-06-01',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $result = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($result, true);
    return $data['content'][0]['text'] ?? "Konnte Bild nicht analysieren.";
}

// Lädt ein Telegram-Foto herunter (größte verfügbare Größe) und gibt
// [bytes, mediaType, extension] zurück, oder null bei Fehler.
function telegram_download_photo($fileId) {
    $ch = curl_init("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getFile?file_id={$fileId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result, true);
    $filePath = $data['result']['file_path'] ?? null;
    if (!$filePath) return null;

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) ?: 'jpg';
    $mediaType = $ext === 'png' ? 'image/png' : 'image/jpeg';

    $ch2 = curl_init("https://api.telegram.org/file/bot" . TELEGRAM_BOT_TOKEN . "/{$filePath}");
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
    $bytes = curl_exec($ch2);
    curl_close($ch2);
    if (!$bytes) return null;

    return [$bytes, $mediaType, $ext];
}
