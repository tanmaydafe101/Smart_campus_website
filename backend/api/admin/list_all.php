<?php
// =============================================================================
// API Endpoint: List All Complaints (Admin & Technicians)
// Route: GET /backend/api/admin/list_all.php
// Supports filters: status, category_id, priority, search
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

AuthMiddleware::requireRole(['admin', 'technician']);
$pdo = Database::getConnection();

$status = strtoupper(trim($_GET['status'] ?? ''));
$categoryId = (int)($_GET['category_id'] ?? 0);
$priority = strtoupper(trim($_GET['priority'] ?? ''));
$search = trim($_GET['search'] ?? '');

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
        reporter.name AS reporter_name,
        reporter.email AS reporter_email,
        tech.name AS technician_name
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN locations l ON t.location_id = l.id
    JOIN users reporter ON t.reporter_id = reporter.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE 1=1
";

$params = [];

if (!empty($status) && in_array($status, ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'], true)) {
    $sql .= " AND t.status = ?";
    $params[] = $status;
}

if ($categoryId > 0) {
    $sql .= " AND t.category_id = ?";
    $params[] = $categoryId;
}

if (!empty($priority) && in_array($priority, ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'], true)) {
    $sql .= " AND t.priority = ?";
    $params[] = $priority;
}

if (!empty($search)) {
    $sql .= " AND (t.title LIKE ? OR t.ticket_code LIKE ? OR reporter.name LIKE ? OR l.building LIKE ? OR l.room LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Resolve photo URL
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))) . '/';
foreach ($tickets as &$ticket) {
    $ticket['photo_url'] = !empty($ticket['photo_path']) ? $baseUrl . $ticket['photo_path'] : null;
    $ticket['is_overdue'] = (!in_array($ticket['status'], ['RESOLVED', 'CLOSED'], true) && !empty($ticket['due_at']) && strtotime($ticket['due_at']) < time());
}

Response::success("Complaints list retrieved.", $tickets);
?>
