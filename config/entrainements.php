<?php
/**
 * Training module configuration for Stabilis.
 *
 * Keeps the original `config::getConnexion()` API used by the imported
 * controllers, but delegates to the Stabilis database connection.
 */

$mailConfig = [];
$mailConfigPath = __DIR__ . '/mail.php';
if (file_exists($mailConfigPath)) {
    $mailConfig = require $mailConfigPath;
}

require_once __DIR__ . '/database.php';

if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: ($mailConfig['gemini_api_key'] ?? ''));
}

if (!class_exists('config')) {
    class config
    {
        private static ?PDO $pdo = null;

        public static function getConnexion(): PDO
        {
            if (class_exists('Database') && method_exists('Database', 'getConnection')) {
                return Database::getConnection();
            }

            if (self::$pdo === null) {
                self::$pdo = new PDO(
                    'mysql:host=localhost;dbname=stabilis;charset=utf8mb4',
                    'root',
                    ''
                );
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }

            return self::$pdo;
        }
    }
}

