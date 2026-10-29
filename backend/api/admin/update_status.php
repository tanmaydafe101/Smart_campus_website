<?php
// =============================================================================
// API Endpoint: Update Complaint Status (Workflow Execution)
// Route: POST /backend/api/admin/update_status.php
// Workflow: OPEN -> ASSIGNED -> IN_PROGRESS -> RESOLVED -> CLOSED
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

$user = AuthMiddleware::requireRole(['admin', 'technician']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$ticketId = (int)($input['ticket_id'] ?? 0);
$newStatus = strtoupper(trim($input['status'] ?? ''));
$comment = trim($input['comment'] ?? '');

$validStatuses = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];
if ($ticketId <= 0 || !in_array($newStatus, $validStatuses, true)) {
    Response::error("Valid ticket_id and status ('OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED') required.", 422);
}

$pdo = Database::getConnection();

// Fetch existing ticket
$stmt = $pdo->prepare("SELECT id, status, ticket_code FROM tickets WHERE id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    Response::error("Complaint not found.", 404);
}

$oldStatus = $ticket['status'];

if ($oldStatus === $newStatus) {
    Response::error("The complaint is already in '{$newStatus}' status.", 422);
}

$pdo->beginTransaction();
try {
    $resolvedAtClause = "";
    if ($newStatus === 'RESOLVED') {
        $resolvedAtClause = ", resolved_at = NOW()";
    }

    $update = $pdo->prepare("UPDATE tickets SET status = ? {$resolvedAtClause} WHERE id = ?");
    $update->execute([$newStatus, $ticketId]);

    // Record in history log
    $histComment = !empty($comment) ? $comment : "Status updated from {$oldStatus} to {$newStatus}";
    $hist = $pdo->prepare("
        INSERT INTO ticket_history (ticket_id, changed_by, old_status, new_status, comment) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $hist->execute([$ticketId, $user['id'], $oldStatus, $newStatus, $histComment]);

    $pdo->commit();

    Response::success("Status updated to '{$newStatus}' successfully.", [
        'ticket_id'  => $ticketId,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'comment'    => $histComment,
        'updated_by' => $user['name']
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    Response::error("Failed to update status: " . $e->getMessage(), 500);
}
?>
