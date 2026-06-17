<?php

require __DIR__ . '/counter.php';

$slug       = $_GET['slug']        ?? '';
$mcVersion  = $_GET['mc_version']  ?? '';
$loader     = $_GET['loader']      ?? '';
$modVersion = $_GET['mod_version'] ?? '';

if ($slug === '' || $mcVersion === '' || $loader === '') {
    http_response_code(400);
    exit('Missing parameters.');
}

$slug       = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
$mcVersion  = preg_replace('/[^0-9.]/', '', $mcVersion);
$mcVersion  = preg_replace('/\.0$/', '', $mcVersion);
$modVersion = preg_replace('/[^0-9.]/', '', $modVersion);

$loaderNames = ['forge' => 'Forge', 'fabric' => 'Fabric', 'neoforge' => 'NeoForge', 'quilt' => 'Quilt'];
$loaderKey   = strtolower(preg_replace('/[^a-zA-Z]/', '', $loader));

if (!isset($loaderNames[$loaderKey])) {
    http_response_code(400);
    exit('Invalid loader.');
}
$loader = $loaderNames[$loaderKey];

if (!preg_match('/^\d+\.\d+(\.\d+)?$/', $mcVersion)) {
    http_response_code(400);
    exit('Invalid mc version.');
}
if (strlen($slug) > 64) {
    http_response_code(400);
    exit('Invalid slug.');
}

$versions = load_versions($slug);
if ($versions === null || !isset($versions[$mcVersion][$loader])) {
    http_response_code(404);
    exit('Version not found.');
}
$latest = $versions[$mcVersion][$loader];

header('Content-Type: text/plain');
echo $latest;

// store whether they're behind, not which version they're on
$status = 'unknown';
if ($modVersion !== '') {
    $status = version_compare($modVersion, $latest) < 0 ? 'outdated' : 'current';
}
record_check($slug, $mcVersion, $loader, $status);


function load_versions($slug) {
    $cacheFile = __DIR__ . '/cache/' . $slug . '.json';

    if (is_file($cacheFile) && time() - filemtime($cacheFile) < 6 * 3600) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    $json = @file_get_contents('https://workflow.serilum.com/versions/data/' . $slug . '.min.json');
    if ($json !== false) {
        $versions = json_decode($json, true);
        if ($versions !== null) {
            @mkdir(dirname($cacheFile), 0755, true);
            file_put_contents($cacheFile, $json);
            return $versions;
        }
    }

    if (is_file($cacheFile)) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    return null;
}
