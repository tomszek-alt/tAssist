<?php
// Dieses Skript per Cronjob täglich aufrufen lassen (z.B. 8:00 Uhr):
// php /pfad/zu/cron_reminder.php

require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/world_news.php';

$store = load_data();

$news = get_world_news_summary();
$lines = ["<b>🌍 Top 5 Weltnachrichten:</b>", tg_esc($news), ""];

$active = array_filter($store['projects'], fn($p) => ($p['status'] ?? '') === 'arbeit');

if (empty($active)) {
    $lines[] = "Aktuell ist kein Projekt als „In Arbeit“ markiert.";
    telegram_send(implode("\n", $lines));
    exit;
}

$lines[] = "<b>Stand deiner aktiven Projekte:</b>";
foreach ($active as $p) {
    $openSteps = array_filter($p['steps'] ?? [], fn($s) => empty($s['done']));
    $lines[] = "\n<b>" . tg_esc($p['title']) . "</b>";
    if (!empty($p['next'])) {
        $lines[] = "→ Nächster Schritt: " . tg_esc($p['next']);
    } elseif (!empty($openSteps)) {
        $first = array_values($openSteps)[0];
        $lines[] = "→ Offen: " . tg_esc($first['text']);
    } else {
        $lines[] = "→ Keine offenen Schritte hinterlegt.";
    }
}

if (!empty($store['inbox'])) {
    $lines[] = "\n📥 " . count($store['inbox']) . " unsortierte Einträge in der Inbox.";
}

telegram_send(implode("\n", $lines));
