<?php
require_once __DIR__ . '/../../../.configs/config.php';

// Deploy kopiert ALLES, was im Repo liegt (auch neue Dateien automatisch,
// keine Liste mehr zu pflegen). Ausgenommen ist nur, was ohnehin nie im
// Repo landet: config.php und data/ existieren dort gar nicht — die
// werden also implizit nie angerührt, unabhängig von dieser Liste.
// Nicht-Code-Dateien (README etc.) werden übersprungen.
function deploy_skip_file($filename) {
    $skip = ['README.md', '.gitignore', 'LICENSE'];
    return in_array($filename, $skip, true);
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
    foreach (glob($sourceDir . '/*') as $src) {
        $filename = basename($src);
        if (is_dir($src) || deploy_skip_file($filename)) continue;
        copy($src, __DIR__ . '/' . $filename);
        $updated[] = $filename;
    }
    sort($updated);

    function deploy_rrmdir($dir) {
        foreach (glob($dir . '/*') as $f) { is_dir($f) ? deploy_rrmdir($f) : unlink($f); }
        rmdir($dir);
    }
    deploy_rrmdir($extractDir);

    $out = "✅ " . count($updated) . " Datei(en) aktualisiert:\n";
    foreach ($updated as $f) $out .= "- {$f}\n";
    $out .= "\nconfig.php und data/ wurden nicht angerührt (existieren nicht im Repo).";
    return $out;
}
