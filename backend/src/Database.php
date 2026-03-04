<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
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
                self::$connection = new PDO($dsn, $parsed['user'], $parsed['pass']);
            } else {
                $host = getenv('DB_HOST') ?: 'localhost';
                $port = getenv('DB_PORT') ?: '5432';
                $name = getenv('DB_NAME') ?: 'games';
                $user = getenv('DB_USER') ?: 'postgres';
                $pass = getenv('DB_PASS') ?: '';
                self::$connection = new PDO("pgsql:host=$host;port=$port;dbname=$name", $user, $pass);
            }
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return self::$connection;
    }
}
