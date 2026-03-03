<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../database.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    // --- Search mode ---
    $db = getDB();

    $stmt = $db->query("
        SELECT g.game_id, g.game_name, g.price, g.discount, g.details, g.image_url,
               p.platform_id, p.platform_name,
               r.region_id, r.region_name
        FROM games g
        JOIN platforms p ON g.platform_id = p.platform_id
        JOIN regions   r ON g.region_id   = r.region_id
    ");

    // Levenshtein-distance threshold: allow roughly 40 % edit distance of the
    // longer string so short queries still surface relevant results.
    $queryLower  = strtolower($search);
    $maxDistance = max(3, (int)(strlen($search) * 0.4));

    $matches = [];
    while ($row = $stmt->fetch()) {
        $nameLower = strtolower($row['game_name']);

        // Exact / substring match – always include
        if (str_contains($nameLower, $queryLower)) {
            $row['distance'] = 0;
            $matches[] = $row;
            continue;
        }

        // Compare against the full game name and individual words
        $distances = [levenshtein($queryLower, $nameLower)];
        foreach (array_filter(explode(' ', $nameLower)) as $word) {
            $distances[] = levenshtein($queryLower, $word);
        }
        $minDist = min($distances);

        if ($minDist <= $maxDistance) {
            $row['distance'] = $minDist;
            $matches[] = $row;
        }
    }

    // Sort by distance ascending so best matches come first
    usort($matches, fn($a, $b) => $a['distance'] <=> $b['distance']);

    // Remove helper field and cast types before sending
    foreach ($matches as &$m) {
        unset($m['distance']);
        $m['price']    = (float)$m['price'];
        $m['discount'] = $m['discount'] !== null ? (int)$m['discount'] : null;
    }

    echo json_encode(['games' => $matches, 'query' => $search]);
} else {
    // --- List mode (paginated) ---
    $db = getDB();

    $page   = isset($_GET['page'])  ? max(1, (int)$_GET['page'])  : 1;
    $limit  = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;

    $total = (int)$db->query('SELECT COUNT(*) FROM games')->fetchColumn();

    $stmt = $db->prepare("
        SELECT g.game_id, g.game_name, g.price, g.discount, g.details, g.image_url,
               p.platform_id, p.platform_name,
               r.region_id, r.region_name
        FROM games g
        JOIN platforms p ON g.platform_id = p.platform_id
        JOIN regions   r ON g.region_id   = r.region_id
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $games = $stmt->fetchAll();

    foreach ($games as &$g) {
        $g['price']    = (float)$g['price'];
        $g['discount'] = $g['discount'] !== null ? (int)$g['discount'] : null;
    }
    unset($g);

    echo json_encode([
        'total' => $total,
        'page'  => $page,
        'limit' => $limit,
        'games' => $games,
    ]);
}
