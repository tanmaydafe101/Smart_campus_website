<?php
// =============================================================================
// Smart Campus Authentication Middleware
// Token generation, verification, and role-based access control
// Uses HMAC-SHA256 signatures for stateless, dependency-free tokens
// =============================================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';

class AuthMiddleware {
    private static $secret_key = "SmartCampusSecuritySecret2026_Key";

    /**
     * Generate secure auth token for authenticated user
     * 
     * @param array $user
     * @return string
     */
    public static function generateToken($user) {
        $payload = [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'created_at' => time()
        ];
        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, self::$secret_key);
        return base64_encode($payloadJson . '||' . $signature);
    }

    /**
     * Authenticate request and return verified user object
     * Checks Authorization header (Bearer token) or POST/GET token or PHP Session
     * 
     * @return array Verified user data
     */
    public static function authenticate() {
        // 1. Check PHP Session first (useful for Web Admin Dashboard)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
            return $_SESSION['user'];
        }

        // 2. Check Authorization Header (for Android REST calls)
        $token = null;
        $headers = null;

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        if ($headers) {
            // Case-insensitive header check
            foreach ($headers as $key => $val) {
                if (strtolower($key) === 'authorization') {
                    if (preg_match('/Bearer\s(\S+)/', $val, $matches)) {
                        $token = $matches[1];
                    }
                    break;
                }
            }
        }

        // Fallback to HTTP_AUTHORIZATION server variable
        if (!$token && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $token = $matches[1];
            }
        }

        // Fallback to token parameter
        if (!$token && isset($_REQUEST['token'])) {
            $token = $_REQUEST['token'];
        }

        if (!$token) {
            Response::error("Authentication required. Please provide a valid Bearer token.", 401);
        }

        // Verify token
        $decoded = base64_decode($token, true);
        if (!$decoded || strpos($decoded, '||') === false) {
            Response::error("Invalid token format.", 401);
        }

        list($payloadJson, $signature) = explode('||', $decoded, 2);
        $expectedSignature = hash_hmac('sha256', $payloadJson, self::$secret_key);

        if (!hash_equals($expectedSignature, $signature)) {
            Response::error("Token verification failed (signature mismatch).", 401);
        }

        $payload = json_decode($payloadJson, true);
        if (!$payload || !isset($payload['id'])) {
            Response::error("Invalid token payload.", 401);
        }

        // Verify user still exists in database
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
        $stmt->execute([$payload['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error("User account not found or deactivated.", 401);
        }

        return $user;
    }

    /**
     * Require one of the specified roles
     * 
     * @param array $allowedRoles E.g. ['admin', 'technician']
     * @return array Verified user data
     */
    public static function requireRole($allowedRoles = ['admin']) {
        $user = self::authenticate();
        if (!in_array($user['role'], $allowedRoles, true)) {
            Response::error("Access denied. Required role: " . implode(" or ", $allowedRoles), 403);
        }
        return $user;
    }
}
?>
