<?php
// =============================================================================
// API Endpoint: Add Comment to Complaint
// Route: POST /backend/api/tickets/add_comment.php
// Allowed: Student (owner), Technician (assignee), or Admin
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

$user = AuthMiddleware::authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$ticketId = (int)($input['ticket_id'] ?? 0);
$message = trim($input['message'] ?? '');

if ($ticketId <= 0 || empty($message)) {
    Response::error("Ticket ID and message are required.", 422);
}

$pdo = Database::getConnection();

// Check ticket access
$stmt = $pdo->prepare("SELECT id, reporter_id, status FROM tickets WHERE id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    Response::error("Complaint not found.", 404);
}

if ($user['role'] === 'student' && (int)$ticket['reporter_id'] !== (int)$user['id']) {
    Response::error("Access denied. You can only comment on your own complaints.", 403);
}

$insert = $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, message) VALUES (?, ?, ?)");
$insert->execute([$ticketId, $user['id'], $message]);
$commentId = (int)$pdo->lastInsertId();

Response::success("Comment added successfully.", [
    'id'          => $commentId,
    'ticket_id'   => $ticketId,
    'user_id'     => $user['id'],
    'author_name' => $user['name'],
    'author_role' => $user['role'],
    'message'     => $message,
    'created_at'  => date('Y-m-d H:i:s')
], 201);
?>
