<?php
// =============================================================================
// API Endpoint: List Campus Locations
// Route: GET /backend/api/locations/list.php
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

Response::initHeaders();

$pdo = Database::getConnection();

$stmt = $pdo->query("SELECT id, building, room, qr_code_token, created_at FROM locations ORDER BY building ASC, room ASC");
$locations = $stmt->fetchAll();

Response::success("Locations retrieved successfully.", $locations);
?>
