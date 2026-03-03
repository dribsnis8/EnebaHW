<?php

function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        try {
            $url = getenv('DATABASE_URL');
            if ($url !== false && $url !== '') {
                // Render supplies DATABASE_URL as postgresql://user:pass@host:port/dbname
                $parsed = parse_url($url);
                $dsn = sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
                    $parsed['host'],
                    $parsed['port'] ?? 5432,
                    ltrim($parsed['path'], '/')
                );
                $db = new PDO($dsn, $parsed['user'], $parsed['pass']);
            } else {
                $host = getenv('DB_HOST') ?: 'localhost';
                $port = getenv('DB_PORT') ?: '5432';
                $name = getenv('DB_NAME') ?: 'games';
                $user = getenv('DB_USER') ?: 'postgres';
                $pass = getenv('DB_PASS') ?: '';
                $db = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
            }
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            http_response_code(503);
            echo json_encode(['error' => 'Database connection failed']);
            exit(1);
        }
    }
    return $db;
}
