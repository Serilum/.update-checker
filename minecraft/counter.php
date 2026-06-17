<?php

// The counter. One row per (day, mod, mc version, loader, status); see the README.
// DB lives outside the web root so it can't be downloaded directly.

function counter_db() {
    $path = getenv('UPDATE_STATS_DB') ?: '/var/lib/update-serilum-com/counts.db';

    if (!is_dir(dirname($path))) {
        @mkdir(dirname($path), 0755, true);
    }

    $db = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('CREATE TABLE IF NOT EXISTS version_checks (
        day     TEXT    NOT NULL,
        slug    TEXT    NOT NULL,
        mc      TEXT    NOT NULL,
        loader  TEXT    NOT NULL,
        status  TEXT    NOT NULL,
        n       INTEGER NOT NULL,
        PRIMARY KEY (day, slug, mc, loader, status)
    )');
    return $db;
}

function record_check($slug, $mc, $loader, $status) {
    try {
        $db = counter_db();
        $insert = $db->prepare('INSERT INTO version_checks (day, slug, mc, loader, status, n)
            VALUES (?, ?, ?, ?, ?, 1)
            ON CONFLICT (day, slug, mc, loader, status) DO UPDATE SET n = n + 1');
        $insert->execute([gmdate('Y-m-d'), $slug, $mc, $loader, $status]);
    } catch (Throwable $e) {
        // best-effort; a count must never break a version check
    }
}
