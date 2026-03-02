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

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($query === '') {
    echo json_encode(['games' => [], 'query' => '']);
    exit;
}

$db = getDB();

$result = $db->query("
    SELECT g.game_id, g.game_name, g.region, g.price, g.discount, g.details,
           p.platform_id, p.platform_name
    FROM games g
    JOIN platforms p ON g.platform_id = p.platform_id
");

// Levenshtein-distance threshold: allow roughly 40 % edit distance of the
// longer string so short queries still surface relevant results.
$queryLower = strtolower($query);
$maxDistance = max(3, (int)(strlen($query) * 0.4));

$matches = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $nameLower = strtolower($row['game_name']);

    // Exact / substring match – always include
    if (str_contains($nameLower, $queryLower)) {
        $row['distance'] = 0;
        $matches[] = $row;
        continue;
    }

    // Compare against the full game name and individual words
    $distances = [levenshtein($queryLower, $nameLower)];
    foreach (explode(' ', $nameLower) as $word) {
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

// Remove helper field before sending
foreach ($matches as &$m) {
    unset($m['distance']);
}

echo json_encode(['games' => $matches, 'query' => $query]);
