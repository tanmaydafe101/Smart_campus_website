<?php
// =============================================================================
// API Endpoint: Student Registration
// Route: POST /backend/api/auth/register.php
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", 405);
}

// Support both JSON payload and standard form-data / x-www-form-urlencoded
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// Validation
if (empty($name) || empty($email) || empty($password)) {
    Response::error("All fields are required: name, email, password.", 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error("Please provide a valid email address.", 422);
}

if (strlen($password) < 6) {
    Response::error("Password must be at least 6 characters long.", 422);
}

$pdo = Database::getConnection();

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    Response::error("This email is already registered. Please log in.", 409);
}

// Hash password securely with PHP BCrypt
$passwordHash = password_hash($password, PASSWORD_BCRYPT);
$role = 'student'; // Public registration defaults to student

$stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
try {
    $stmt->execute([$name, $email, $passwordHash, $role]);
    $newUserId = (int)$pdo->lastInsertId();

    $userData = [
        'id' => $newUserId,
        'name' => $name,
        'email' => $email,
        'role' => $role
    ];

    $token = AuthMiddleware::generateToken($userData);

    Response::success("Registration successful! Welcome to Smart Campus.", [
        'user' => $userData,
        'token' => $token
    ], 201);
} catch (PDOException $e) {
    Response::error("Failed to register account: " . $e->getMessage(), 500);
}
?>
