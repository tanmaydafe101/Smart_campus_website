<?php
// =============================================================================
// Smart Campus Issue & Facility Management System
// Database Configuration using PDO
// =============================================================================

class Database {
    // Database credentials for local XAMPP environment
    private static $host = "localhost";
    private static $db_name = "smart_campus";
    private static $username = "root";
    private static $password = ""; // Default XAMPP MySQL password is empty
    private static $charset = "utf8mb4";
    private static $conn = null;

    /**
     * Get singleton PDO database connection
     * @return PDO|null
     */
    public static function getConnection() {
        if (self::$conn === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    "success" => false,
                    "message" => "Database connection failure: " . $e->getMessage(),
                    "data" => null
                ]);
                exit;
            }
        }
        return self::$conn;
    }
}
?>
