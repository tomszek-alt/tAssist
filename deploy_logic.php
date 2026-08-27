<?php
require_once __DIR__ . '/../../../.configs/config.php';

// Nur diese Dateien werden vom Repo übernommen — bewusst keine config.php,
// keine data/, kein deploy_logic.php/deploy.php selbst (verhindert, dass
// sich das Skript mitten im eigenen Lauf selbst überschreibt).
function deploy_allowed_files() {
    return [
        'webhook.php', 'storage.php', 'telegram.php', 'ai_sort.php',
        'cron_reminder.php', 'world_news.php', 'news.php', 'dashboard.php',
        'api.php',
    ];
}

function run_deploy() {
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
        return "❌ Fehler: Konnte Repo-Zip nicht laden (HTTP {$httpCode}). Ist GITHUB_REPO in config.php korrekt gesetzt und das Repo public?";
    }
    file_put_contents($tmpZip, $zipData);

    $zip = new ZipArchive();
    if ($zip->open($tmpZip) !== true) {
        return "❌ Fehler: Zip konnte nicht geöffnet werden.";
    }

    $extractDir = sys_get_temp_dir() . '/deploy_extract_' . uniqid();
    mkdir($extractDir, 0755, true);
    $zip->extractTo($extractDir);
    $zip->close();
    unlink($tmpZip);

    $subdirs = glob($extractDir . '/*', GLOB_ONLYDIR);
    $sourceDir = $subdirs[0] ?? $extractDir;

    $updated = [];
    $skipped = [];
    foreach (deploy_allowed_files() as $file) {
        $src = $sourceDir . '/' . $file;
        $dst = __DIR__ . '/' . $file;
        if (file_exists($src)) {
            copy($src, $dst);
            $updated[] = $file;
        } else {
            $skipped[] = $file . " (nicht im Repo gefunden)";
        }
    }

    function deploy_rrmdir($dir) {
        foreach (glob($dir . '/*') as $f) { is_dir($f) ? deploy_rrmdir($f) : unlink($f); }
        rmdir($dir);
    }
    deploy_rrmdir($extractDir);

    $out = "✅ " . count($updated) . " Datei(en) aktualisiert:\n";
    foreach ($updated as $f) $out .= "- {$f}\n";
    if ($skipped) {
        $out .= "\nÜbersprungen:\n";
        foreach ($skipped as $f) $out .= "- {$f}\n";
    }
    $out .= "\nconfig.php und data/ wurden nicht angerührt.";
    return $out;
}
