<?php

// Token-gated read of the counts, for the box that graphs them. Token is in
// stats-secret.php (not committed).

require __DIR__ . '/counter.php';

$expected = @include __DIR__ . '/stats-secret.php';
$given    = $_GET['key'] ?? '';

if (!is_string($expected) || $expected === '' || !hash_equals($expected, (string) $given)) {
    http_response_code(403);
    exit('Forbidden.');
}

$db = counter_db();

$since = gmdate('Y-m-d', time() - 3 * 86400);
$read = $db->prepare('SELECT day, slug, mc, loader, status, n
    FROM version_checks WHERE day >= ? ORDER BY day, slug, mc, loader, status');
$read->execute([$since]);
$rows = $read->fetchAll(PDO::FETCH_NUM);

$db->exec("DELETE FROM version_checks WHERE day < date('now', '-8 days')");

header('Content-Type: application/json');
echo json_encode([
    'columns' => ['day', 'slug', 'mc', 'loader', 'status', 'n'],
    'rows'    => $rows,
]);
