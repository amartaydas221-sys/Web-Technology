<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                $message = APP_ENV === 'development' ? $e->getMessage() : 'Database connection failed.';
                exit('<h1>Database Error</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p><p>Import <code>database.sql</code> and verify config/app.php.</p>');
            }
        }
        return self::$instance;
    }
}
