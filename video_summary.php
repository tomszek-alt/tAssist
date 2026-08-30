<?php
require_once __DIR__ . '/../../../.configs/config.php';

function extract_youtube_id($url) {
    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/shorts/)([A-Za-z0-9_-]{11})#', $url, $m)) {
        return $m[1];
    }
    return null;
}

// Holt das automatische/manuelle Transkript direkt über YouTubes eigene
// Untertitel-Schnittstelle (kein yt-dlp o.ä. nötig, läuft mit reinem curl).
function fetch_youtube_transcript($videoId) {
    $ch = curl_init("https://www.youtube.com/watch?v={$videoId}&hl=de");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: de-DE,de;q=0.9,en;q=0.8']);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $html = curl_exec($ch);
    curl_close($ch);
    if (!$html) return null;

    if (!preg_match('/"captionTracks":(\[.*?\])/', $html, $m)) {
        return null; // keine Untertitel vorhanden
    }
    $tracks = json_decode($m[1], true);
    if (!is_array($tracks) || empty($tracks)) return null;

    // Bevorzugt Deutsch, dann Englisch, sonst erste verfügbare Spur
    $track = null;
    foreach ($tracks as $t) { if (($t['languageCode'] ?? '') === 'de') { $track = $t; break; } }
    if (!$track) foreach ($tracks as $t) { if (($t['languageCode'] ?? '') === 'en') { $track = $t; break; } }
    if (!$track) $track = $tracks[0];

    $baseUrl = str_replace('\u0026', '&', $track['baseUrl'] ?? '');
    if (!$baseUrl) return null;

    $ch2 = curl_init($baseUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
    $xml = curl_exec($ch2);
    curl_close($ch2);
    if (!$xml) return null;

    if (!preg_match_all('/<text[^>]*>(.*?)<\/text>/s', $xml, $matches)) return null;
    $lines = array_map(function ($t) {
        $t = html_entity_decode($t, ENT_QUOTES | ENT_XML1, 'UTF-8');
        return trim($t);
    }, $matches[1]);
    $text = implode(' ', $lines);
    return trim($text) !== '' ? $text : null;
}

function claude_summarize_transcript($transcript) {
    $transcript = mb_substr($transcript, 0, 12000); // Kosten/Kontext begrenzen

    $body = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => 500,
        'messages' => [[
            'role' => 'user',
            'content' => "Hier ist das Transkript eines YouTube-Videos:\n\n{$transcript}\n\n" .
                "Fasse den Inhalt in 4-6 kurzen Stichpunkten auf Deutsch zusammen " .
                "(worum geht es, wichtigste Aussagen/Ergebnisse). Paraphrasiert, keine " .
                "wörtlichen Zitate. Antworte NUR mit den Stichpunkten, keine Einleitung.",
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
    return $data['content'][0]['text'] ?? "Konnte nicht zusammengefasst werden.";
}

// Komfortfunktion: URL rein, fertige Zusammenfassung (oder Fehlermeldung) raus
function summarize_youtube_url($url) {
    $id = extract_youtube_id($url);
    if (!$id) return "❌ Kein gültiger YouTube-Link erkannt.";
    $transcript = fetch_youtube_transcript($id);
    if (!$transcript) return "❌ Kein Transkript verfügbar (Video hat vermutlich keine Untertitel).";
    return claude_summarize_transcript($transcript);
}
