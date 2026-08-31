<?php
require_once __DIR__ . '/../../../.configs/config.php';
require_once __DIR__ . '/shared_lists_storage.php';

if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$oldFile = __DIR__ . '/../../../.configs/data/packliste.json';
if (!file_exists($oldFile)) {
    echo "Keine alte Packliste gefunden (packliste.json existiert nicht) — nichts zu migrieren.\n";
    exit;
}

$old = json_decode(file_get_contents($oldFile), true);
if (!$old) {
    echo "Konnte packliste.json nicht lesen.\n";
    exit;
}

$lists = shared_lists_load();
$newSecret = shared_lists_new_secret();
$lists[] = [
    'id' => 'sl' . uniqid(),
    'secret' => $newSecret,
    'title' => $old['title'] ?? 'Packliste',
    'subtitle' => $old['subtitle'] ?? '',
    'categories' => $old['categories'] ?? [],
];
shared_lists_save($lists);

echo "✅ Migriert! Neuer Link (kein config.php-Eintrag mehr nötig):\n\n";
echo "https://hugahuga.com/kira/shared.php?secret={$newSecret}\n\n";
echo "Diese Datei (migrate_packliste_to_shared.php) kannst du danach löschen.\n";
