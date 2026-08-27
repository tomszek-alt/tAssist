<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/storage.php';

if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}

function step2($text, $done) { return ['id' => 's' . uniqid(), 'text' => $text, 'done' => $done]; }

$newProjects = [
    [
        'title' => 'Kanzleiumzug IT',
        'status' => 'arbeit',
        'next' => 'DSGVO-konformen IT-Umzugsplan fertigstellen',
        'notes' => 'IT-Infrastruktur & DSGVO-konformer Standortumzug für eine Anwaltskanzlei; beA-Kartenverwaltung.',
        'steps' => [
            step2('beA-Karte aktiviert/Sicherheits-Token freigeschaltet', true),
            step2('Strukturierter IT-Umzugsplan (Word, DSGVO-Fokus) erstellen', false),
        ],
        'links' => [],
    ],
    [
        'title' => 'DayTrading',
        'status' => 'arbeit',
        'next' => 'Scalping-System live testen/verfeinern',
        'notes' => 'Systematisches, regelbasiertes Scalping-System für Forex (EUR/USD, GBP/USD) und DAX. 1–2 Trades/Tag angestrebt.',
        'steps' => [
            step2('EMA-Rebound-Setup entwickelt (Multi-Timeframe)', true),
            step2('DAX Opening Breakout Setup entwickelt', true),
            step2('London Session Spike Setup entwickelt', true),
            step2('8-Kriterien-Checkliste + Positionsgrößen-Rechner gebaut', true),
        ],
        'links' => [],
    ],
    [
        'title' => "Let's Make Money (Website-Portfolio)",
        'status' => 'arbeit',
        'next' => 'Nächstes Domain-Cluster priorisieren und umsetzen',
        'notes' => 'Portfolio wartungsarmer Passiv-Einkommen-Websites (Kleinunternehmer §19 UStG). 33 Domains im Bestand, mehrere Cluster identifiziert.',
        'steps' => [
            step2('nightlife24.de fertig gebaut (Splash-Page)', true),
            step2('biergarten-nuernberg.de weit fortgeschritten — AdSense-Einbindung offen', false),
            step2('3d-machen.de: 6 Homepage-Mockups fertig, Umsetzung offen', false),
            step2('vegan7.de: 3 Mockups fertig, keine Umsetzung bisher', false),
            step2('regionalundbio.de (Saisonkalender + Bio-Label-Guide) — Status prüfen', false),
        ],
        'links' => [],
    ],
    [
        'title' => 'LODARO (Kartenspiel)',
        'status' => 'arbeit',
        'next' => 'Entscheidung: Verlag/Lizenzierung vs. Selbstverlag/Crowdfunding',
        'notes' => 'Physisches Push-your-luck-Kartenspiel (2–6 Spieler, "cozy danger"-Optik). Ziel-Messen: SPIEL Essen (Okt 2026), Spielwarenmesse Nürnberg (Feb 2027).',
        'steps' => [
            step2('Regelwerk (Spielanleitung) fertig', true),
            step2('Print-and-Play-Vorlage (76 Karten) fertig', true),
            step2('Visuelles Design-System fertig', true),
            step2('Publisher-Sell-Sheet fertig', true),
            step2('Brand-Identity-Paket (Logos) fertig', true),
        ],
        'links' => [],
    ],
    [
        'title' => 'WirMachensBillig.de',
        'status' => 'arbeit',
        'next' => 'SEO & Monetarisierung weiter optimieren',
        'notes' => 'Live Preisvergleichs-/Affiliate-Portal (Strom, Gas, Handy, DSL, Girokonto, Reisen, Mietwagen). CHECK24/AdSense/Finanzcheck24-Monetarisierung.',
        'steps' => [
            step2('Seite live und funktionsfähig', true),
            step2('Dynamisches Artikelsystem (ratgeber.json) eingerichtet', true),
            step2('AdSense ads.txt korrekt konfiguriert', true),
        ],
        'links' => [],
    ],
    [
        'title' => 'One Ring (Multi-Agent-KI-Agentur)',
        'status' => 'arbeit',
        'next' => 'SSL/curl-Fehler beim Ollama-Setup beheben',
        'notes' => 'Kosteneffiziente Multi-Agent-KI-Agentur. Architektur: n8n (Hetzner VPS) + Claude API + Supabase/pgvector + Chatwoot + Ollama-Hybrid-Routing.',
        'steps' => [
            step2('Architektur entschieden (n8n/Claude/Supabase/Chatwoot/Ollama)', true),
        ],
        'links' => [],
    ],
    [
        'title' => 'SchatzWasKocheIchHeute.de',
        'status' => 'fertig',
        'next' => 'ADSENSE_SLOT_ID in consent.js ergänzen (Ad-Refresh aktivieren)',
        'notes' => 'Minimalistischer Rezept-Zufallsgenerator (ein Button, "Nochmal!"). Live auf Alfahosting, 100 Rezepte mit Bildern.',
        'steps' => [
            step2('Seite live, alle 100 Rezepte + Bilder hochgeladen', true),
        ],
        'links' => [],
    ],
];

$store = load_data();
$existingTitles = array_map(fn($p) => mb_strtolower(trim($p['title'])), $store['projects']);

$added = [];
foreach ($newProjects as $np) {
    if (in_array(mb_strtolower(trim($np['title'])), $existingTitles, true)) continue;
    $np['id'] = 'p' . uniqid();
    $store['projects'][] = $np;
    $added[] = $np['title'];
}

// Bonus: bestehendes "YouTube Altbestand Revitalisierung" um konkrete Tool-Namen ergänzen
foreach ($store['projects'] as &$proj) {
    if (mb_strtolower(trim($proj['title'])) === 'youtube altbestand revitalisierung') {
        $existingStepTexts = array_map(fn($s) => $s['text'], $proj['steps']);
        $extraSteps = [
            'YouTube Archive Optimizer (v9.23) — ROI-Scoring, A–G-Klassifizierung, Gemini-Prompts',
            'YT Kanal-Backup Tool (v1.9) — versionierte CSV/JSON-Exports, Diff-Analyse',
            'YouTube Traffic Chain Analyzer (v1.03) — Bubble-Tree-UI für Traffic-Flows',
            'Fresh-Upload CTR Monitor (v1.5) — CTR-Tracking im 72h-Fenster',
        ];
        foreach ($extraSteps as $t) {
            if (!in_array($t, $existingStepTexts, true)) {
                $proj['steps'][] = step2($t, true);
            }
        }
    }
}
unset($proj);

save_data($store);

header('Content-Type: text/plain; charset=utf-8');
echo count($added) . " Projekte hinzugefügt:\n";
foreach ($added as $t) echo "- {$t}\n";
echo "\n'YouTube Altbestand Revitalisierung' um konkrete Tool-Namen ergänzt (falls noch nicht vorhanden).\n";
echo "\nBestand jetzt gesamt: " . count($store['projects']) . " Projekte.\n";
echo "\nDiese Datei kannst du danach löschen.\n";
