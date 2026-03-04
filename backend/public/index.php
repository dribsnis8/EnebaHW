<?php

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Set base path so Slim strips /api from request URIs
$app->setBasePath('/api');

// CORS middleware
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
});

// Slim error handling (renders JSON errors)
$app->addErrorMiddleware(false, false, false);

// Handle OPTIONS preflight requests
$app->options('/{routes:.+}', function (Request $request, Response $response): Response {
    return $response->withStatus(204);
});

/**
 * Perform a Levenshtein-distance fuzzy search over a set of games.
 *
 * @param PDOStatement $stmt  Executed statement whose rows will be scanned.
 * @param string       $query Raw search term provided by the user.
 * @return array Matching game rows, sorted by distance ascending.
 */
function fuzzySearch(PDOStatement $stmt, string $query): array
{
    $queryLower  = strtolower($query);
    $maxDistance = max(3, (int)(strlen($query) * 0.4));

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

    // Remove helper field and cast types before returning
    foreach ($matches as &$m) {
        unset($m['distance']);
        $m['price']    = (float)$m['price'];
        $m['discount'] = $m['discount'] !== null ? (int)$m['discount'] : null;
    }
    unset($m);

    return $matches;
}

/** SQL fragment used by all three endpoints to select game data. */
const GAMES_SELECT_SQL = "
    SELECT g.game_id, g.game_name, g.price, g.discount, g.details, g.image_url,
           p.platform_id, p.platform_name,
           r.region_id, r.region_name
    FROM games g
    JOIN platforms p ON g.platform_id = p.platform_id
    JOIN regions   r ON g.region_id   = r.region_id
";

/**
 * GET /api/games
 * Returns a paginated list of games.
 * Query params: page (default 1), limit (default 20)
 */
$app->get('/games', function (Request $request, Response $response): Response {
    try {
        $db = Database::getConnection();
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Database connection failed']));
        return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
    }

    $params = $request->getQueryParams();
    $page   = isset($params['page'])  ? max(1, (int)$params['page'])  : 1;
    $limit  = isset($params['limit']) ? max(1, (int)$params['limit']) : 20;
    $offset = ($page - 1) * $limit;

    $total = (int)$db->query('SELECT COUNT(*) FROM games')->fetchColumn();

    $stmt = $db->prepare(GAMES_SELECT_SQL . 'LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $games = $stmt->fetchAll();
    foreach ($games as &$g) {
        $g['price']    = (float)$g['price'];
        $g['discount'] = $g['discount'] !== null ? (int)$g['discount'] : null;
    }
    unset($g);

    $response->getBody()->write(json_encode([
        'total' => $total,
        'page'  => $page,
        'limit' => $limit,
        'games' => $games,
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * GET /api/list
 * Returns a paginated game list, or fuzzy-search results when ?search= is provided.
 * Query params:
 *   - search: optional search term (Levenshtein-distance fuzzy matching)
 *   - page:   page number (default 1, ignored when searching)
 *   - limit:  page size  (default 20, ignored when searching)
 */
$app->get('/list', function (Request $request, Response $response): Response {
    try {
        $db = Database::getConnection();
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Database connection failed']));
        return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
    }

    $params = $request->getQueryParams();
    $search = isset($params['search']) ? trim($params['search']) : '';

    if ($search !== '') {
        $stmt = $db->query(GAMES_SELECT_SQL);
        $matches = fuzzySearch($stmt, $search);

        $response->getBody()->write(json_encode(['games' => $matches, 'query' => $search]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Paginated list mode
    $page   = isset($params['page'])  ? max(1, (int)$params['page'])  : 1;
    $limit  = isset($params['limit']) ? max(1, (int)$params['limit']) : 20;
    $offset = ($page - 1) * $limit;

    $total = (int)$db->query('SELECT COUNT(*) FROM games')->fetchColumn();

    $stmt = $db->prepare(GAMES_SELECT_SQL . 'LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $games = $stmt->fetchAll();
    foreach ($games as &$g) {
        $g['price']    = (float)$g['price'];
        $g['discount'] = $g['discount'] !== null ? (int)$g['discount'] : null;
    }
    unset($g);

    $response->getBody()->write(json_encode([
        'total' => $total,
        'page'  => $page,
        'limit' => $limit,
        'games' => $games,
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

/**
 * GET /api/search
 * Fuzzy-search games by name using Levenshtein distance.
 * Query params: q (search term)
 */
$app->get('/search', function (Request $request, Response $response): Response {
    $params = $request->getQueryParams();
    $query  = isset($params['q']) ? trim($params['q']) : '';

    if ($query === '') {
        $response->getBody()->write(json_encode(['games' => [], 'query' => '']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    try {
        $db = Database::getConnection();
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(['error' => 'Database connection failed']));
        return $response->withStatus(503)->withHeader('Content-Type', 'application/json');
    }

    $stmt = $db->query(GAMES_SELECT_SQL);
    $matches = fuzzySearch($stmt, $query);

    $response->getBody()->write(json_encode(['games' => $matches, 'query' => $query]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
