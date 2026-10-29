<?php
// =============================================================================
// API Endpoint: User Login (Students, Technicians, Admin)
// Route: POST /backend/api/auth/login.php
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    Response::error("Please provide both email and password.", 422);
}

$pdo = Database::getConnection();

$stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    Response::error("Invalid email or password. Please try again.", 401);
}

// Generate token
$userData = [
    'id' => (int)$user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role']
];

$token = AuthMiddleware::generateToken($userData);

// Store in PHP Session as well for Web Dashboard seamless support
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user'] = $userData;
$_SESSION['token'] = $token;

Response::success("Login successful. Welcome back, " . $user['name'] . "!", [
    'user' => $userData,
    'token' => $token
]);
?>
