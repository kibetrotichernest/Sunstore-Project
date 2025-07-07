<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sunstore');

// Site configuration
define('SITE_NAME', 'Sunstore Industries Limited');
define('SITE_URL', 'http://localhost/sunstore-industries');
define('CURRENCY', 'Ksh');

// Error reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

/**
 * Database connection handler using Singleton pattern
 */
class Database {
    private static $instance = null;
    private $connection;

    // Private constructor to prevent direct instantiation
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Test connection immediately
            $this->connection->query("SELECT 1");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection error");
        }
    }

    // Get the database instance
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Initialize database connection
try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}
