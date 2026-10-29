<?php
// =============================================================================
// API Endpoint: List Logged-In Student's Complaints
// Route: GET /backend/api/tickets/list_user.php?status=OPEN
// Requires Authentication (Student only sees their own complaints)
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

$user = AuthMiddleware::authenticate();
$pdo = Database::getConnection();

$statusFilter = strtoupper(trim($_GET['status'] ?? ''));

$sql = "
    SELECT 
        t.id,
        t.ticket_code,
        t.title,
        t.description,
        t.photo_path,
        t.priority,
        t.status,
        t.created_at,
        t.due_at,
        t.resolved_at,
        c.name AS category_name,
        l.building,
        l.room,
        u.name AS technician_name
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN locations l ON t.location_id = l.id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.reporter_id = ?
";

$params = [$user['id']];

if (!empty($statusFilter) && in_array($statusFilter, ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'], true)) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Add relative full photo URL for Android app consumption
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))) . '/';

foreach ($tickets as &$ticket) {
    if (!empty($ticket['photo_path'])) {
        $ticket['photo_url'] = $baseUrl . $ticket['photo_path'];
    } else {
        $ticket['photo_url'] = null;
    }
}

Response::success("Complaints retrieved successfully.", $tickets);
?>
