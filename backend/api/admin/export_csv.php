<?php
// =============================================================================
// API Endpoint: Export Complaints Report as CSV
// Route: GET /backend/api/admin/export_csv.php
// Requires Role: admin
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

// Verify admin role via Session or Bearer Token
AuthMiddleware::requireRole(['admin']);
$pdo = Database::getConnection();

$status = strtoupper(trim($_GET['status'] ?? ''));
$categoryId = (int)($_GET['category_id'] ?? 0);
$priority = strtoupper(trim($_GET['priority'] ?? ''));

$sql = "
    SELECT 
        t.ticket_code,
        t.title,
        c.name AS category,
        CONCAT(l.building, ' - ', l.room) AS campus_location,
        t.priority,
        t.status,
        reporter.name AS reported_by,
        reporter.email AS reporter_email,
        COALESCE(tech.name, 'Unassigned') AS assigned_technician,
        t.created_at,
        t.due_at,
        COALESCE(t.resolved_at, 'N/A') AS resolved_at
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN locations l ON t.location_id = l.id
    JOIN users reporter ON t.reporter_id = reporter.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE 1=1
";

$params = [];
if (!empty($status)) {
    $sql .= " AND t.status = ?";
    $params[] = $status;
}
if ($categoryId > 0) {
    $sql .= " AND t.category_id = ?";
    $params[] = $categoryId;
}
if (!empty($priority)) {
    $sql .= " AND t.priority = ?";
    $params[] = $priority;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = "campus_complaints_report_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Microsoft Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header row
fputcsv($output, [
    'Ticket Code',
    'Title',
    'Category',
    'Location',
    'Priority',
    'Status',
    'Reported By',
    'Reporter Email',
    'Assigned Technician',
    'Reported Date',
    'SLA Due Date',
    'Resolution Date'
]);

foreach ($rows as $row) {
    fputcsv($output, [
        $row['ticket_code'],
        $row['title'],
        $row['category'],
        $row['campus_location'],
        $row['priority'],
        $row['status'],
        $row['reported_by'],
        $row['reporter_email'],
        $row['assigned_technician'],
        $row['created_at'],
        $row['due_at'],
        $row['resolved_at']
    ]);
}

fclose($output);
exit();
?>
