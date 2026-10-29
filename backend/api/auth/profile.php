<?php
// =============================================================================
// API Endpoint: User Profile & Personal Statistics
// Route: GET /backend/api/auth/profile.php
// Requires Bearer Token or Session
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

$currentUser = AuthMiddleware::authenticate();
$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get full user profile
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $user = $stmt->fetch();

    // Calculate personal ticket stats
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status = 'OPEN' THEN 1 ELSE 0 END) as open_tickets,
            SUM(CASE WHEN status = 'ASSIGNED' THEN 1 ELSE 0 END) as assigned_tickets,
            SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress_tickets,
            SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved_tickets,
            SUM(CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END) as closed_tickets
        FROM tickets 
        WHERE reporter_id = ?
    ");
    $statsStmt->execute([$currentUser['id']]);
    $stats = $statsStmt->fetch();

    Response::success("Profile retrieved successfully.", [
        'profile' => $user,
        'statistics' => [
            'total'       => (int)($stats['total_tickets'] ?? 0),
            'open'        => (int)($stats['open_tickets'] ?? 0),
            'assigned'    => (int)($stats['assigned_tickets'] ?? 0),
            'in_progress' => (int)($stats['in_progress_tickets'] ?? 0),
            'resolved'    => (int)($stats['resolved_tickets'] ?? 0),
            'closed'      => (int)($stats['closed_tickets'] ?? 0)
        ]
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update profile (e.g. change name or password)
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $name = trim($input['name'] ?? '');
    $newPassword = $input['new_password'] ?? '';

    if (empty($name)) {
        Response::error("Name cannot be empty.", 422);
    }

    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            Response::error("New password must be at least 6 characters.", 422);
        }
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$name, $newHash, $currentUser['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $currentUser['id']]);
    }

    Response::success("Profile updated successfully.", [
        'id' => $currentUser['id'],
        'name' => $name,
        'email' => $currentUser['email']
    ]);
} else {
    Response::error("Method not allowed.", 405);
}
?>
