<?php
declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

// Demo talking point: single shared PDO connection (singleton via the
// static $instance), configured once here. Every controller calls
// Database::connect() and gets the SAME connection back instead of opening
// a new one per query — and because it's created here with these specific
// attributes, every ->prepare()/->execute() call site automatically inherits
// them rather than each controller having to set its own.
class Database
{
    private static ?PDO $instance = null;

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            // Credentials come from .env (never hardcoded/committed) via
            // vlucas/phpdotenv, loaded once at app boot in index.php.
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'],
                $_ENV['DB_NAME']
            );
            try {
                self::$instance = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                    // Throw exceptions on DB errors instead of failing silently.
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    // Rows come back as ['column' => value] associative arrays,
                    // which is the shape every controller in this app expects.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['error' => 'Database connection failed']));
            }
        }
        return self::$instance;
    }
}
