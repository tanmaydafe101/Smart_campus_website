<?php
// =============================================================================
// Standardized JSON Response Helper
// Ensures uniform response schema across all Android and Web APIs:
// {
//     "success": true|false,
//     "message": "Human readable description",
//     "data": {}|[]|null
// }
// =============================================================================

class Response {
    /**
     * Set common API headers (JSON, CORS)
     */
    public static function initHeaders() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-User-Id, X-User-Role");

        // Handle browser pre-flight CORS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Send standardized JSON response and exit
     * 
     * @param bool $success
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     */
    public static function json($success, $message = "", $data = null, $statusCode = 200) {
        self::initHeaders();
        http_response_code($statusCode);
        echo json_encode([
            "success" => (bool)$success,
            "message" => (string)$message,
            "data"    => $data
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit();
    }

    public static function success($message = "Success", $data = null, $statusCode = 200) {
        self::json(true, $message, $data, $statusCode);
    }

    public static function error($message = "An error occurred", $statusCode = 400, $data = null) {
        self::json(false, $message, $data, $statusCode);
    }
}
?>
