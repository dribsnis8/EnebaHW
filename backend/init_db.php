<?php
require_once __DIR__ . '/database.php';

$db = getDB();

$db->exec('
    CREATE TABLE IF NOT EXISTS platforms (
        platform_id   SERIAL PRIMARY KEY,
        platform_name VARCHAR(255) NOT NULL
    );
');

$db->exec('
    CREATE TABLE IF NOT EXISTS regions (
        region_id   SERIAL PRIMARY KEY,
        region_name VARCHAR(255) NOT NULL
    );
');

$db->exec('
    CREATE TABLE IF NOT EXISTS games (
        game_id     SERIAL PRIMARY KEY,
        game_name   VARCHAR(255) NOT NULL,
        price       FLOAT        NOT NULL,
        discount    INTEGER,
        details     VARCHAR      NOT NULL,
        image_url   VARCHAR(512),
        platform_id INTEGER      NOT NULL,
        region_id   INTEGER      NOT NULL,
        FOREIGN KEY (platform_id) REFERENCES platforms(platform_id),
        FOREIGN KEY (region_id)   REFERENCES regions(region_id)
    );
');

// Only seed if tables are empty
try {
    $count = (int)$db->query('SELECT COUNT(*) FROM platforms')->fetchColumn();
} catch (PDOException $e) {
    // Table may not exist yet on very first run; treat as empty
    $count = 0;
}
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
        $stmt->execute([':name' => $name]);
    }

    // Seed regions
    // region_id: Global=1, Europe=2, North America=3, Asia=4
    $regions = ['Global', 'Europe', 'North America', 'Asia'];
    $stmt = $db->prepare('INSERT INTO regions (region_name) VALUES (:name)');
    foreach ($regions as $name) {
        $stmt->execute([':name' => $name]);
    }

    // platform_id: PC=1, PS5=2, PS4=3, XboxSX=4, XboxOne=5, Switch=6
    // region_id:   Global=1, Europe=2, North America=3, Asia=4
    // image_url:   Steam CDN header images per game title
    $fifa_img = 'https://cdn.akamai.steamstatic.com/steam/apps/1811260/header.jpg';
    $rdr2_img = 'https://cdn.akamai.steamstatic.com/steam/apps/1174180/header.jpg';
    $sf_img   = 'https://cdn.akamai.steamstatic.com/steam/apps/2001120/header.jpg';
    $games = [
        // EA SPORTS FIFA 23
        ['EA SPORTS FIFA 23', 59.99, null, 'The most realistic football game to date.',      $fifa_img, 1, 1],
        ['EA SPORTS FIFA 23', 59.99, 10,   'European edition with Bundesliga license.',      $fifa_img, 1, 2],
        ['EA SPORTS FIFA 23', 59.99, null, 'Includes all MLS teams.',                       $fifa_img, 2, 3],
        ['EA SPORTS FIFA 23', 49.99, 5,    'Asian regional edition.',                       $fifa_img, 3, 4],
        ['EA SPORTS FIFA 23', 59.99, null, 'Ultimate Team edition.',                        $fifa_img, 4, 1],
        ['EA SPORTS FIFA 23', 69.99, 15,   'Champions Edition with bonus content.',         $fifa_img, 5, 2],
        ['EA SPORTS FIFA 23', 59.99, null, 'Standard edition for Nintendo Switch.',         $fifa_img, 6, 1],
        ['EA SPORTS FIFA 23', 59.99, 20,   'Discounted North America edition.',             $fifa_img, 2, 3],

        // Red Dead Redemption 2
        ['Red Dead Redemption 2', 49.99, null, 'Epic open-world western adventure.',            $rdr2_img, 1, 1],
        ['Red Dead Redemption 2', 49.99, 10,   'European edition with extra story missions.',   $rdr2_img, 4, 2],
        ['Red Dead Redemption 2', 44.99, null, 'North American standard edition.',              $rdr2_img, 5, 3],
        ['Red Dead Redemption 2', 44.99, 5,    'Asian release with subtitles support.',         $rdr2_img, 3, 4],
        ['Red Dead Redemption 2', 49.99, null, 'Special edition includes exclusive content.',   $rdr2_img, 1, 1],
        ['Red Dead Redemption 2', 54.99, 25,   'Ultimate edition with all story DLC.',          $rdr2_img, 2, 2],
        ['Red Dead Redemption 2', 49.99, null, 'Online multiplayer bundle.',                    $rdr2_img, 4, 1],
        ['Red Dead Redemption 2', 39.99, 30,   'Heavily discounted bundle deal.',               $rdr2_img, 5, 3],

        // Split Fiction
        ['Split Fiction', 39.99, null, 'Co-op adventure across different fiction worlds.', $sf_img, 1, 1],
        ['Split Fiction', 39.99, null, 'European release of the co-op adventure.',         $sf_img, 2, 2],
        ['Split Fiction', 34.99, 10,   'North America edition with bonus chapter.',        $sf_img, 3, 3],
        ['Split Fiction', 34.99, null, 'Asian edition with localized content.',             $sf_img, 4, 4],
        ['Split Fiction', 39.99, 5,    'Deluxe edition includes digital artbook.',         $sf_img, 5, 1],
        ['Split Fiction', 44.99, null, 'Ultimate edition with OST included.',              $sf_img, 6, 2],
        ['Split Fiction', 39.99, null, 'Switch portable co-op edition.',                   $sf_img, 6, 1],
        ['Split Fiction', 29.99, 25,   'Budget-friendly co-op bundle.',                    $sf_img, 1, 3],
    ];

    $stmt = $db->prepare('
        INSERT INTO games (game_name, price, discount, details, image_url, platform_id, region_id)
        VALUES (:game_name, :price, :discount, :details, :image_url, :platform_id, :region_id)
    ');
    foreach ($games as $g) {
        $stmt->execute([
            ':game_name'   => $g[0],
            ':price'       => $g[1],
            ':discount'    => $g[2],
            ':details'     => $g[3],
            ':image_url'   => $g[4],
            ':platform_id' => $g[5],
            ':region_id'   => $g[6],
        ]);
    }

    echo "Database initialized and seeded successfully.\n";
} else {
    echo "Database already seeded (platforms count: $count).\n";
}
