<?php

/**
 * Database connection helper using PDO.
 * Usage:
 *   $db = Database::getInstance()->pdo();
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/config.php';
        $db = $config['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['database'],
            $db['charset']
        );

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new \PDO($dsn, $db['username'], $db['password'], $options);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }
}
