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

$db = getDB();

$page  = isset($_GET['page'])  ? max(1, (int)$_GET['page'])  : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

$total = $db->querySingle('SELECT COUNT(*) FROM games');

$stmt = $db->prepare("
    SELECT g.game_id, g.game_name, g.price, g.discount, g.details, g.image_url,
           p.platform_id, p.platform_name,
           r.region_id, r.region_name
    FROM games g
    JOIN platforms p ON g.platform_id = p.platform_id
    JOIN regions   r ON g.region_id   = r.region_id
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  $limit,  SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$result = $stmt->execute();

$games = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $games[] = $row;
}

echo json_encode([
    'total' => (int)$total,
    'page'  => $page,
    'limit' => $limit,
    'games' => $games,
]);
