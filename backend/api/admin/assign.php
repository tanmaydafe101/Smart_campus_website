<?php
// =============================================================================
// API Endpoint: Assign Technician to Complaint
// Route: POST /backend/api/admin/assign.php
// Requires Role: admin
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

$admin = AuthMiddleware::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$ticketId = (int)($input['ticket_id'] ?? 0);
$technicianId = (int)($input['technician_id'] ?? 0);
$note = trim($input['note'] ?? '');

if ($ticketId <= 0 || $technicianId <= 0) {
    Response::error("Ticket ID and Technician ID are required.", 422);
}

$pdo = Database::getConnection();

// Validate technician
$tStmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'technician'");
$tStmt->execute([$technicianId]);
$technician = $tStmt->fetch();

if (!$technician) {
    Response::error("Specified user is not a valid campus technician.", 422);
}

// Fetch ticket
$stmt = $pdo->prepare("SELECT id, status, ticket_code, assigned_to FROM tickets WHERE id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    Response::error("Complaint not found.", 404);
}

$pdo->beginTransaction();
try {
    $oldStatus = $ticket['status'];
    $newStatus = ($oldStatus === 'OPEN') ? 'ASSIGNED' : $oldStatus;

    $update = $pdo->prepare("UPDATE tickets SET assigned_to = ?, status = ? WHERE id = ?");
    $update->execute([$technicianId, $newStatus, $ticketId]);

    // Log to history
    $comment = "Assigned to {$technician['name']}" . (!empty($note) ? ". Note: {$note}" : ".");
    $hist = $pdo->prepare("
        INSERT INTO ticket_history (ticket_id, changed_by, old_status, new_status, comment) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $hist->execute([$ticketId, $admin['id'], $oldStatus, $newStatus, $comment]);

    $pdo->commit();

    Response::success("Technician '{$technician['name']}' successfully assigned.", [
        'ticket_id'       => $ticketId,
        'technician_id'   => $technicianId,
        'technician_name' => $technician['name'],
        'status'          => $newStatus
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    Response::error("Failed to assign technician: " . $e->getMessage(), 500);
}
?>
