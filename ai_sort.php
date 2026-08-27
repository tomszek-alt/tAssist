<?php
require_once __DIR__ . '/../../../.configs/config.php';

// Fragt Claude, zu welchem bestehenden Projekt ein Text am besten passt,
// oder ob es eher ein neues Vorhaben ist. Gibt ein Array zurück:
// ['project_id' => string|null, 'new_title' => string|null, 'reason' => string]
function claude_classify_inbox($text, $projects) {
    $projectList = array_map(function ($p) {
        return $p['id'] . ": " . $p['title'] . " (" . $p['status'] . ")";
    }, $projects);
    $projectListText = implode("\n", $projectList);

    $prompt = "Ordne die folgende Notiz/Link einem bestehenden Projekt zu, falls sie eindeutig passt. " .
        "Falls sie zu keinem Projekt passt, schlage einen kurzen Titel für ein neues Projekt vor.\n\n" .
        "Bestehende Projekte:\n{$projectListText}\n\n" .
        "Notiz: \"{$text}\"\n\n" .
        "Antworte NUR mit einem JSON-Objekt, keine Erklärung, kein Markdown:\n" .
        '{"project_id": "<id oder null>", "new_title": "<Titel oder null>", "reason": "<kurzer Grund, max 10 Wörter>"}';

    $body = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => 300,
        'messages' => [['role' => 'user', 'content' => $prompt]],
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $result = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($result, true);
    $text_out = $data['content'][0]['text'] ?? null;
    if (!$text_out) {
        return ['project_id' => null, 'new_title' => null, 'reason' => 'KI nicht erreichbar'];
    }
    $clean = trim(preg_replace('/```json|```/', '', $text_out));
    $parsed = json_decode($clean, true);
    if (!is_array($parsed)) {
        return ['project_id' => null, 'new_title' => null, 'reason' => 'Antwort nicht auswertbar'];
    }
    return [
        'project_id' => $parsed['project_id'] ?? null,
        'new_title' => $parsed['new_title'] ?? null,
        'reason' => $parsed['reason'] ?? '',
    ];
}
