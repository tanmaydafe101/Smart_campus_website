<?php
// =============================================================================
// API Endpoint: List Available Campus Technicians
// Route: GET /backend/api/admin/list_technicians.php
// Requires Role: admin
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

AuthMiddleware::requireRole(['admin']);
$pdo = Database::getConnection();

$stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'technician' ORDER BY name ASC");
$technicians = $stmt->fetchAll();

Response::success("Technicians list retrieved.", $technicians);
?>
