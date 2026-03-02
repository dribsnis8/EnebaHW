<?php

function getDB(): SQLite3 {
    static $db = null;
    if ($db === null) {
        $dbPath = __DIR__ . '/database/games.db';
        $db = new SQLite3($dbPath);
        $db->enableExceptions(true);
    }
    return $db;
}
