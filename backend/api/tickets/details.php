<?php
// =============================================================================
// API Endpoint: Get Complaint Details, History & Comments
// Route: GET /backend/api/tickets/details.php?id=1 OR ?code=TICK-2026-0001
// Role-protected: Students only access own tickets; Admin/Tech access all
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

$user = AuthMiddleware::authenticate();
$pdo = Database::getConnection();

$ticketId = (int)($_GET['id'] ?? 0);
$ticketCode = trim($_GET['code'] ?? '');

if ($ticketId <= 0 && empty($ticketCode)) {
    Response::error("Ticket ID or Ticket Code is required.", 422);
}

// Fetch ticket
$sql = "
    SELECT 
        t.*,
        c.name AS category_name,
        l.building,
        l.room,
        l.qr_code_token,
        reporter.name AS reporter_name,
        reporter.email AS reporter_email,
        tech.name AS technician_name,
        tech.email AS technician_email
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN locations l ON t.location_id = l.id
    JOIN users reporter ON t.reporter_id = reporter.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE " . ($ticketId > 0 ? "t.id = ?" : "t.ticket_code = ?");

$stmt = $pdo->prepare($sql);
$stmt->execute([$ticketId > 0 ? $ticketId : $ticketCode]);
$ticket = $stmt->fetch();

if (!$ticket) {
    Response::error("Complaint not found.", 404);
}

// Student access restriction: Must be the reporter
if ($user['role'] === 'student' && (int)$ticket['reporter_id'] !== (int)$user['id']) {
    Response::error("Access denied. You can only view your own complaints.", 403);
}

// Resolve photo URL
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))) . '/';
$ticket['photo_url'] = !empty($ticket['photo_path']) ? $baseUrl . $ticket['photo_path'] : null;

// Fetch status history
$histStmt = $pdo->prepare("
    SELECT th.*, u.name AS changed_by_name, u.role AS changed_by_role
    FROM ticket_history th
    JOIN users u ON th.changed_by = u.id
    WHERE th.ticket_id = ?
    ORDER BY th.created_at ASC
");
$histStmt->execute([$ticket['id']]);
$history = $histStmt->fetchAll();

// Fetch comments
$commStmt = $pdo->prepare("
    SELECT tc.*, u.name AS author_name, u.role AS author_role
    FROM ticket_comments tc
    JOIN users u ON tc.user_id = u.id
    WHERE tc.ticket_id = ?
    ORDER BY tc.created_at ASC
");
$commStmt->execute([$ticket['id']]);
$comments = $commStmt->fetchAll();

Response::success("Ticket details retrieved.", [
    'ticket'   => $ticket,
    'history'  => $history,
    'comments' => $comments
]);
?>
