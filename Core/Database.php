<?php
/**
 * Database - Singleton PDO connection manager
 * 
 * Provides centralized database access with proper error handling
 * and connection pooling.
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $config = [
        'host'    => 'localhost',
        'dbname'  => 'gestion_fitness',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ];

    /**
     * Get PDO instance (singleton pattern)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private static function connect(): void
    {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            self::$config['host'],
            self::$config['dbname'],
            self::$config['charset']
        );

        try {
            self::$instance = new PDO(
                $dsn,
                self::$config['user'],
                self::$config['pass'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Database connection failed: " . $e->getMessage()
            );
        }
    }

    /**
     * Configure database connection
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
        self::$instance = null; // Force reconnection
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }
}
