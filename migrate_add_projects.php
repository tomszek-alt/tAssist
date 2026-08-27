<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/storage.php';

if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}

function step($text, $done) { return ['id' => 's' . uniqid(), 'text' => $text, 'done' => $done]; }

$newProjects = [
    [
        'title' => 'Computec Stempeluhr (Timebutler)',
        'status' => 'arbeit',
        'next' => 'Rollout an ca. 400 Mitarbeiter',
        'notes' => 'Tkinter-Desktop-Tool (.exe) + Android-App (Kotlin/Compose) zur Timebutler-Zeiterfassung, Computec-Branding.',
        'steps' => [
            step('CLI-Tool mit Timebutler API v1 gebaut', true),
            step('Migration auf API v2 (Bearer-Token)', true),
            step('Tkinter-GUI mit Computec-Branding', true),
            step('§4-ArbZG-Pausenberechnung implementiert', true),
            step('.exe via PyInstaller gebaut', true),
            step('Android-App (Kotlin/Compose) generiert', true),
            step('Rollout an ca. 400 Mitarbeiter', false),
        ],
        'links' => [],
    ],
    [
        'title' => 'YouTube Ad-Remover (SponsorBlock)',
        'status' => 'arbeit',
        'next' => 'SponsorBlock-Timestamp-Submission ergänzen',
        'notes' => 'Python-Skript, nutzt SponsorBlock API + yt-dlp + ffmpeg zum automatischen Werbeschnitt.',
        'steps' => [
            step('Skript mit SponsorBlock API/yt-dlp/ffmpeg gebaut', true),
            step('SponsorBlock-Timestamp-Submission ergänzen', false),
        ],
        'links' => [],
    ],
    [
        'title' => 'Hotfolder-GUI (YT-Tools)',
        'status' => 'fertig',
        'next' => '',
        'notes' => 'PowerShell/WinForms-Tool zum automatischen Dateiverschieben per Hotfolder-Überwachung.',
        'steps' => [
            step('WinForms-Layout-Bug behoben (Add_Shown-Fix)', true),
        ],
        'links' => [],
    ],
    [
        'title' => 'YouTube-Kanal-Automatisierungspipeline (Module A–G)',
        'status' => 'arbeit',
        'next' => 'Modul B: Dateigrößen-Schätzung',
        'notes' => 'Für DVD-Produktion & USK-Alterseinstufung. Modul A (Video-Doku → Excel) fertig.',
        'steps' => [
            step('Modul A: Video-Dokumentation nach Excel', true),
            step('Modul B: Dateigrößen-Schätzung', false),
            step('Modul C: DVD-Kapazitätsplanung', false),
            step('Modul D: USK-Playlist-Generierung', false),
            step('Modul E: Video-Download & Ad-Removal', false),
            step('Modul F: AME-Encoding', false),
            step('Modul G: DVD-Authoring', false),
        ],
        'links' => [],
    ],
    [
        'title' => 'Trading Buddy (Marktevent-Tracker)',
        'status' => 'arbeit',
        'next' => 'Backend mit echten Finnhub/CoinMarketCal-Daten',
        'notes' => 'Web-App zeigt marktbewegende Events (Krypto, Aktien, Makro) mit Impact-Level & Push-Alerts.',
        'steps' => [
            step('React-Frontend mit Mock-Daten gebaut', true),
            step('Backend mit echten Finnhub/CoinMarketCal-Daten', false),
            step('Service Worker + Web Push für echte Benachrichtigungen', false),
        ],
        'links' => [],
    ],
    [
        'title' => 'WiFi-Sensing / CSI-Präsenzerkennung',
        'status' => 'idee',
        'next' => 'Richtung wählen: ESP32-Firmware, Simulation oder volle Architektur',
        'notes' => 'Bewegungs-/Präsenzerkennung über WLAN-Signale (CSI) statt Kamera/Bewegungsmelder.',
        'steps' => [],
        'links' => [],
    ],
    [
        'title' => 'Chrome-Erweiterung „Prompt Vault"',
        'status' => 'fertig',
        'next' => '',
        'notes' => 'Manifest-V3-Erweiterung zum Speichern/Einfügen von Text-Snippets, mit verschachteltem Ordnersystem.',
        'steps' => [
            step('Popup-UI + Content-Script gebaut', true),
            step('Mehrstufiges Ordnersystem ergänzt', true),
        ],
        'links' => [],
    ],
    [
        'title' => 'Backlink-Prüftool',
        'status' => 'fertig',
        'next' => '',
        'notes' => 'HTML-Launcher-Tool (Ahrefs/Semrush/Moz/OpenLinkProfiler), da keine echte freie Backlink-API mehr existiert.',
        'steps' => [],
        'links' => [],
    ],
];

$store = load_data();
$existingTitles = array_map(fn($p) => mb_strtolower(trim($p['title'])), $store['projects']);

$added = [];
foreach ($newProjects as $np) {
    if (in_array(mb_strtolower(trim($np['title'])), $existingTitles, true)) {
        continue; // schon vorhanden, überspringen
    }
    $np['id'] = 'p' . uniqid();
    $store['projects'][] = $np;
    $added[] = $np['title'];
}

save_data($store);

header('Content-Type: text/plain; charset=utf-8');
if (empty($added)) {
    echo "Nichts hinzugefügt — alle Projekte waren schon vorhanden.\n";
} else {
    echo count($added) . " Projekte hinzugefügt:\n";
    foreach ($added as $t) echo "- {$t}\n";
}
echo "\nBestand jetzt gesamt: " . count($store['projects']) . " Projekte.\n";
echo "\nDiese Datei (migrate_add_projects.php) kannst du jetzt löschen.\n";
