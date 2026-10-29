<?php
// =============================================================================
// API Endpoint: List Complaint Categories
// Route: GET /backend/api/categories/list.php
// Public / Authenticated
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

Response::initHeaders();

$pdo = Database::getConnection();

$stmt = $pdo->query("SELECT id, name, description, default_priority, created_at FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

Response::success("Categories retrieved successfully.", $categories);
?>
