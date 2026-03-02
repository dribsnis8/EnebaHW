<?php
require_once __DIR__ . '/database.php';

$db = getDB();

$db->exec('PRAGMA foreign_keys = ON;');

$db->exec('
    CREATE TABLE IF NOT EXISTS platforms (
        platform_id   INTEGER PRIMARY KEY AUTOINCREMENT,
        platform_name VARCHAR(255) NOT NULL
    );
');

$db->exec('
    CREATE TABLE IF NOT EXISTS regions (
        region_id   INTEGER PRIMARY KEY AUTOINCREMENT,
        region_name VARCHAR(255) NOT NULL
    );
');

$db->exec('
    CREATE TABLE IF NOT EXISTS games (
        game_id     INTEGER PRIMARY KEY AUTOINCREMENT,
        game_name   VARCHAR(255) NOT NULL,
        price       FLOAT        NOT NULL,
        discount    INTEGER,
        details     VARCHAR      NOT NULL,
        platform_id INTEGER      NOT NULL,
        region_id   INTEGER      NOT NULL,
        FOREIGN KEY (platform_id) REFERENCES platforms(platform_id),
        FOREIGN KEY (region_id)   REFERENCES regions(region_id)
    );
');

// Only seed if tables are empty
$count = $db->querySingle('SELECT COUNT(*) FROM platforms');
if ($count === 0) {
    $platforms = [
        'PC',
        'PlayStation 5',
        'PlayStation 4',
        'Xbox Series X/S',
        'Xbox One',
        'Nintendo Switch',
    ];
    $stmt = $db->prepare('INSERT INTO platforms (platform_name) VALUES (:name)');
    foreach ($platforms as $name) {
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->execute();
    }

    // Seed regions
    // region_id: Global=1, Europe=2, North America=3, Asia=4
    $regions = ['Global', 'Europe', 'North America', 'Asia'];
    $stmt = $db->prepare('INSERT INTO regions (region_name) VALUES (:name)');
    foreach ($regions as $name) {
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->execute();
    }

    // platform_id: PC=1, PS5=2, PS4=3, XboxSX=4, XboxOne=5, Switch=6
    // region_id:   Global=1, Europe=2, North America=3, Asia=4
    $games = [
        // EA SPORTS FIFA 23
        ['EA SPORTS FIFA 23', 59.99, null, 'The most realistic football game to date.',      1, 1],
        ['EA SPORTS FIFA 23', 59.99, 10,   'European edition with Bundesliga license.',      1, 2],
        ['EA SPORTS FIFA 23', 59.99, null, 'Includes all MLS teams.',                       2, 3],
        ['EA SPORTS FIFA 23', 49.99, 5,    'Asian regional edition.',                       3, 4],
        ['EA SPORTS FIFA 23', 59.99, null, 'Ultimate Team edition.',                        4, 1],
        ['EA SPORTS FIFA 23', 69.99, 15,   'Champions Edition with bonus content.',         5, 2],
        ['EA SPORTS FIFA 23', 59.99, null, 'Standard edition for Nintendo Switch.',         6, 1],
        ['EA SPORTS FIFA 23', 59.99, 20,   'Discounted North America edition.',             2, 3],

        // Red Dead Redemption 2
        ['Red Dead Redemption 2', 49.99, null, 'Epic open-world western adventure.',            1, 1],
        ['Red Dead Redemption 2', 49.99, 10,   'European edition with extra story missions.',   4, 2],
        ['Red Dead Redemption 2', 44.99, null, 'North American standard edition.',              5, 3],
        ['Red Dead Redemption 2', 44.99, 5,    'Asian release with subtitles support.',         3, 4],
        ['Red Dead Redemption 2', 49.99, null, 'Special edition includes exclusive content.',   1, 1],
        ['Red Dead Redemption 2', 54.99, 25,   'Ultimate edition with all story DLC.',          2, 2],
        ['Red Dead Redemption 2', 49.99, null, 'Online multiplayer bundle.',                    4, 1],
        ['Red Dead Redemption 2', 39.99, 30,   'Heavily discounted bundle deal.',               5, 3],

        // Split Fiction
        ['Split Fiction', 39.99, null, 'Co-op adventure across different fiction worlds.', 1, 1],
        ['Split Fiction', 39.99, null, 'European release of the co-op adventure.',         2, 2],
        ['Split Fiction', 34.99, 10,   'North America edition with bonus chapter.',        3, 3],
        ['Split Fiction', 34.99, null, 'Asian edition with localized content.',             4, 4],
        ['Split Fiction', 39.99, 5,    'Deluxe edition includes digital artbook.',         5, 1],
        ['Split Fiction', 44.99, null, 'Ultimate edition with OST included.',              6, 2],
        ['Split Fiction', 39.99, null, 'Switch portable co-op edition.',                   6, 1],
        ['Split Fiction', 29.99, 25,   'Budget-friendly co-op bundle.',                    1, 3],
    ];

    $stmt = $db->prepare('
        INSERT INTO games (game_name, price, discount, details, platform_id, region_id)
        VALUES (:game_name, :price, :discount, :details, :platform_id, :region_id)
    ');
    foreach ($games as $g) {
        $stmt->bindValue(':game_name',   $g[0], SQLITE3_TEXT);
        $stmt->bindValue(':price',       $g[1], SQLITE3_FLOAT);
        $stmt->bindValue(':discount',    $g[2], $g[2] === null ? SQLITE3_NULL : SQLITE3_INTEGER);
        $stmt->bindValue(':details',     $g[3], SQLITE3_TEXT);
        $stmt->bindValue(':platform_id', $g[4], SQLITE3_INTEGER);
        $stmt->bindValue(':region_id',   $g[5], SQLITE3_INTEGER);
        $stmt->execute();
    }

    echo "Database initialized and seeded successfully.\n";
} else {
    echo "Database already seeded (platforms count: $count).\n";
}
