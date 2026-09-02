<?php
require_once __DIR__ . '/../../../.configs/config.php';

// Holt aktuelle Top-Weltnachrichten über die Brave News Search API
// (kostenlos bis 2.000 Suchen/Monat) und lässt Claude daraus 5 kurze,
// paraphrasierte Zusammenfassungssätze formulieren (normaler Text-Call,
// keine zusätzliche Such-Tool-Gebühr).
function brave_fetch_news_multi($count_per_query = 8) {
    $queries = ['world politics news', 'international conflict news', 'global economy news'];
    $all = [];
    $seenTitles = [];
    foreach ($queries as $q) {
        $url = 'https://api.search.brave.com/res/v1/news/search?' . http_build_query([
            'q' => $q, 'count' => $count_per_query, 'freshness' => 'pd',
        ]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'X-Subscription-Token: ' . BRAVE_API_KEY]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $result = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($result, true);
        foreach (($data['results'] ?? []) as $item) {
            $title = $item['title'] ?? '';
            $desc = $item['description'] ?? '';
            if ($title && !isset($seenTitles[$title])) {
                $seenTitles[$title] = true;
                $all[] = "- {$title}: {$desc}";
            }
        }
    }
    return $all;
}

function claude_summarize_news($snippets) {
    if (empty($snippets)) {
        return "Konnte gerade keine Nachrichten abrufen.";
    }
    $source = implode("\n", array_slice($snippets, 0, 24));

    $body = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => 400,
        'messages' => [[
            'role' => 'user',
            'content' => "Hier sind aktuelle Nachrichtenmeldungen (Titel: Beschreibung):\n\n{$source}\n\n" .
                "Wähle die 5 WICHTIGSTEN Weltnachrichten aus — priorisiere Politik, internationale " .
                "Konflikte/Diplomatie, Wirtschaft/Finanzen, Katastrophen und Ereignisse mit globaler " .
                "Tragweite. Sport-Transfers, Promi-/Boulevard-News und lokale Randmeldungen NUR " .
                "aufnehmen, wenn nichts Wichtigeres vorhanden ist. Ignoriere Einträge, die keine " .
                "echte Nachrichtenmeldung sind (z.B. allgemeine Programmbeschreibungen von " .
                "Sendern/Apps ohne konkretes Ereignis). Fasse jede gewählte Meldung in genau einem " .
                "sehr kurzen, eigenen Satz zusammen (max. 15 Wörter, Deutsch, paraphrasiert, keine " .
                "wörtlichen Zitate). Sind weniger als 5 echte, wichtige Nachrichten vorhanden, gib " .
                "nur so viele wie tatsächlich vorhanden aus — erfinde nichts. Antworte NUR mit " .
                "einer nummerierten Liste, keine Einleitung, keine Erklärung.",
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
    $text = $data['content'][0]['text'] ?? null;
    return $text ?: "Konnte die Nachrichten gerade nicht zusammenfassen.";
}

function get_world_news_summary() {
    $snippets = brave_fetch_news_multi();
    return claude_summarize_news($snippets);
}
