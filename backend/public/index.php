<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../database.php';

$app = AppFactory::create();

// Add routing middleware
$app->addRoutingMiddleware();

// Add error middleware
$app->addErrorMiddleware(false, true, true);

// CORS middleware
$app->add(function (Request $request, $handler): Response {
    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
            ->withStatus(204);
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
});

// GET /api/games – paginated game list
$app->get('/api/games', function (Request $request, Response $response): Response {
    $db     = getDB();
    $params = $request->getQueryParams();

    $page   = isset($params['page'])  ? max(1, (int)$params['page'])  : 1;
    $limit  = isset($params['limit']) ? max(1, (int)$params['limit']) : 20;
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

    $payload = json_encode([
        'total' => (int)$total,
        'page'  => $page,
        'limit' => $limit,
        'games' => $games,
    ]);

    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

// GET /api/search – Levenshtein-distance fuzzy search
$app->get('/api/search', function (Request $request, Response $response): Response {
    $params = $request->getQueryParams();
    $query  = isset($params['q']) ? trim($params['q']) : '';

    if ($query === '') {
        $response->getBody()->write(json_encode(['games' => [], 'query' => '']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $db     = getDB();
    $result = $db->query("
        SELECT g.game_id, g.game_name, g.price, g.discount, g.details, g.image_url,
               p.platform_id, p.platform_name,
               r.region_id, r.region_name
        FROM games g
        JOIN platforms p ON g.platform_id = p.platform_id
        JOIN regions   r ON g.region_id   = r.region_id
    ");

    // Levenshtein-distance threshold: allow roughly 40% edit distance of the
    // longer string so short queries still surface relevant results.
    $queryLower  = strtolower($query);
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

    $response->getBody()->write(json_encode(['games' => $matches, 'query' => $query]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
