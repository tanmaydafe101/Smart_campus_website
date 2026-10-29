<?php
// =============================================================================
// API Endpoint: Create Category (Admin Only)
// Route: POST /backend/api/categories/create.php
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

AuthMiddleware::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$default_priority = strtoupper(trim($input['default_priority'] ?? 'MEDIUM'));

if (empty($name)) {
    Response::error("Category name is required.", 422);
}

$validPriorities = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
if (!in_array($default_priority, $validPriorities, true)) {
    $default_priority = 'MEDIUM';
}

$pdo = Database::getConnection();

// Check unique name
$check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
$check->execute([$name]);
if ($check->fetch()) {
    Response::error("A category with this name already exists.", 409);
}

$stmt = $pdo->prepare("INSERT INTO categories (name, description, default_priority) VALUES (?, ?, ?)");
$stmt->execute([$name, $description, $default_priority]);
$newId = (int)$pdo->lastInsertId();

Response::success("Category created successfully.", [
    'id' => $newId,
    'name' => $name,
    'description' => $description,
    'default_priority' => $default_priority
], 201);
?>
