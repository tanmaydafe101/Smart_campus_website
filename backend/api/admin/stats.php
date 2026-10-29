<?php
// =============================================================================
// API Endpoint: Admin Dashboard KPI Statistics & Chart Analytics
// Route: GET /backend/api/admin/stats.php
// Requires Role: admin or technician
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

AuthMiddleware::requireRole(['admin', 'technician']);
$pdo = Database::getConnection();

// Overall counters
$countersStmt = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'OPEN' THEN 1 ELSE 0 END) AS count_open,
        SUM(CASE WHEN status = 'ASSIGNED' THEN 1 ELSE 0 END) AS count_assigned,
        SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) AS count_in_progress,
        SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) AS count_resolved,
        SUM(CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END) AS count_closed,
        SUM(CASE WHEN status NOT IN ('RESOLVED', 'CLOSED') AND due_at < NOW() THEN 1 ELSE 0 END) AS count_overdue
    FROM tickets
");
$counters = $countersStmt->fetch();

// Category distribution
$catStmt = $pdo->query("
    SELECT c.name, COUNT(t.id) AS ticket_count
    FROM categories c
    LEFT JOIN tickets t ON c.id = t.category_id
    GROUP BY c.id, c.name
    ORDER BY ticket_count DESC
");
$categoryStats = $catStmt->fetchAll();

// Priority distribution
$priStmt = $pdo->query("
    SELECT priority, COUNT(*) AS count
    FROM tickets
    GROUP BY priority
");
$priorityStats = $priStmt->fetchAll();

// Recent 5 complaints
$recentStmt = $pdo->query("
    SELECT 
        t.id, t.ticket_code, t.title, t.priority, t.status, t.created_at,
        u.name AS reporter_name, c.name AS category_name
    FROM tickets t
    JOIN users u ON t.reporter_id = u.id
    JOIN categories c ON t.category_id = c.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
$recentTickets = $recentStmt->fetchAll();

Response::success("Dashboard stats retrieved successfully.", [
    'counters' => [
        'total'       => (int)($counters['total'] ?? 0),
        'open'        => (int)($counters['count_open'] ?? 0),
        'assigned'    => (int)($counters['count_assigned'] ?? 0),
        'in_progress' => (int)($counters['count_in_progress'] ?? 0),
        'resolved'    => (int)($counters['count_resolved'] ?? 0),
        'closed'      => (int)($counters['count_closed'] ?? 0),
        'overdue'     => (int)($counters['count_overdue'] ?? 0),
    ],
    'by_category' => $categoryStats,
    'by_priority' => $priorityStats,
    'recent'      => $recentTickets
]);
?>
