<?php
require_once __DIR__ . '/../../../.configs/config.php';

if (($_GET['secret'] ?? '') !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

// Nur diese Dateien werden vom Repo übernommen — bewusst keine config.php,
// keine data/, kein deploy.php selbst (verhindert, dass sich das Skript
// mitten im eigenen Lauf selbst überschreibt).
$allowedFiles = [
    'webhook.php', 'storage.php', 'telegram.php', 'ai_sort.php',
    'cron_reminder.php', 'world_news.php', 'news.php', 'dashboard.php',
];

$zipUrl = "https://codeload.github.com/" . GITHUB_REPO . "/zip/refs/heads/main";
$tmpZip = sys_get_temp_dir() . '/deploy_' . uniqid() . '.zip';

$ch = curl_init($zipUrl);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$zipData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$zipData) {
    echo "Fehler: Konnte Repo-Zip nicht laden (HTTP {$httpCode}). Ist GITHUB_REPO in config.php korrekt gesetzt und das Repo public?\n";
    exit;
}
file_put_contents($tmpZip, $zipData);

$zip = new ZipArchive();
if ($zip->open($tmpZip) !== true) {
    echo "Fehler: Zip konnte nicht geöffnet werden.\n";
    exit;
}

$extractDir = sys_get_temp_dir() . '/deploy_extract_' . uniqid();
mkdir($extractDir, 0755, true);
$zip->extractTo($extractDir);
$zip->close();
unlink($tmpZip);

// GitHub packt alles in einen Unterordner "<repo>-main/"
$subdirs = glob($extractDir . '/*', GLOB_ONLYDIR);
$sourceDir = $subdirs[0] ?? $extractDir;

$updated = [];
$skipped = [];
foreach ($allowedFiles as $file) {
    $src = $sourceDir . '/' . $file;
    $dst = __DIR__ . '/' . $file;
    if (file_exists($src)) {
        copy($src, $dst);
        $updated[] = $file;
    } else {
        $skipped[] = $file . " (nicht im Repo gefunden)";
    }
}

// Aufräumen
function rrmdir($dir) {
    foreach (glob($dir . '/*') as $f) { is_dir($f) ? rrmdir($f) : unlink($f); }
    rmdir($dir);
}
rrmdir($extractDir);

echo count($updated) . " Datei(en) aktualisiert:\n";
foreach ($updated as $f) echo "- {$f}\n";
if ($skipped) {
    echo "\nÜbersprungen:\n";
    foreach ($skipped as $f) echo "- {$f}\n";
}
echo "\nconfig.php und data/ wurden nicht angerührt.\n";
