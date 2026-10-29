<?php
// =============================================================================
// Smart Campus Admin Dashboard - All Complaints (Search & Filter)
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['preview']) && !isset($_SESSION['user'])) {
    $_SESSION['user'] = ['id' => 1, 'name' => 'System Administrator', 'email' => 'admin@campus.edu', 'role' => 'admin'];
}

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'technician'], true)) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getConnection();

// Filters from GET
$statusFilter = strtoupper(trim($_GET['status'] ?? ''));
$categoryFilter = (int)($_GET['category_id'] ?? 0);
$priorityFilter = strtoupper(trim($_GET['priority'] ?? ''));
$searchQuery = trim($_GET['search'] ?? '');

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Build query
$sql = "
    SELECT 
        t.id, t.ticket_code, t.title, t.priority, t.status, t.created_at, t.due_at,
        c.name AS category_name,
        l.building, l.room,
        u.name AS reporter_name,
        tech.name AS technician_name
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN locations l ON t.location_id = l.id
    JOIN users u ON t.reporter_id = u.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE 1=1
";

$params = [];

if (!empty($statusFilter) && in_array($statusFilter, ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'], true)) {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter > 0) {
    $sql .= " AND t.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($priorityFilter) && in_array($priorityFilter, ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'], true)) {
    $sql .= " AND t.priority = ?";
    $params[] = $priorityFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (t.title LIKE ? OR t.ticket_code LIKE ? OR u.name LIKE ? OR l.building LIKE ? OR l.room LIKE ?)";
    $term = "%{$searchQuery}%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Complaints - Smart Campus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar p-0">
            <div class="brand-title text-center text-md-start px-3">
                <i class="bi bi-buildings-fill text-info me-2"></i>Smart Campus
            </div>
            <div class="pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="complaints.php">
                            <i class="bi bi-card-checklist"></i> All Complaints
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">
                            <i class="bi bi-tags-fill"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Reports & Analytics
                        </a>
                    </li>
                    <li class="nav-item mt-4 border-top border-secondary pt-3">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-left"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3 fw-bold mb-0">Campus Complaints Directory</h1>
                <a href="../backend/api/admin/export_csv.php?status=<?= urlencode($statusFilter) ?>&category_id=<?= $categoryFilter ?>&priority=<?= urlencode($priorityFilter) ?>" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-download"></i> Export Filtered CSV
                </a>
            </div>

            <!-- Filters Bar -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search ticket, room, reporter..." value="<?= htmlspecialchars($searchQuery) ?>" id="tableSearchInput">
                        </div>

                        <div class="col-md-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="OPEN" <?= $statusFilter === 'OPEN' ? 'selected' : '' ?>>Open</option>
                                <option value="ASSIGNED" <?= $statusFilter === 'ASSIGNED' ? 'selected' : '' ?>>Assigned</option>
                                <option value="IN_PROGRESS" <?= $statusFilter === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                                <option value="RESOLVED" <?= $statusFilter === 'RESOLVED' ? 'selected' : '' ?>>Resolved</option>
                                <option value="CLOSED" <?= $statusFilter === 'CLOSED' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select name="priority" class="form-select form-select-sm">
                                <option value="">All Priorities</option>
                                <option value="CRITICAL" <?= $priorityFilter === 'CRITICAL' ? 'selected' : '' ?>>Critical</option>
                                <option value="HIGH" <?= $priorityFilter === 'HIGH' ? 'selected' : '' ?>>High</option>
                                <option value="MEDIUM" <?= $priorityFilter === 'MEDIUM' ? 'selected' : '' ?>>Medium</option>
                                <option value="LOW" <?= $priorityFilter === 'LOW' ? 'selected' : '' ?>>Low</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel-fill"></i> Filter</button>
                            <a href="complaints.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Complaints Table -->
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table custom-table table-hover align-middle mb-0" id="complaintsTable">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th>Reported By</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Technician</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">No complaints match your selected filters.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $t): 
                                    $isOverdue = (!in_array($t['status'], ['RESOLVED', 'CLOSED'], true) && !empty($t['due_at']) && strtotime($t['due_at']) < time());
                                ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-primary">
                                            <?= htmlspecialchars($t['ticket_code']) ?>
                                            <?php if ($isOverdue): ?>
                                                <span class="badge bg-danger ms-1" title="Overdue SLA">!</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-semibold"><?= htmlspecialchars($t['title']) ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($t['building']) ?> - <?= htmlspecialchars($t['room']) ?></small></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['category_name']) ?></span></td>
                                        <td><?= htmlspecialchars($t['reporter_name']) ?></td>
                                        <td><span class="badge badge-priority-<?= $t['priority'] ?>"><?= $t['priority'] ?></span></td>
                                        <td><span class="badge badge-status badge-<?= $t['status'] ?>"><?= str_replace('_', ' ', $t['status']) ?></span></td>
                                        <td><?= !empty($t['technician_name']) ? htmlspecialchars($t['technician_name']) : '<span class="text-muted small">Unassigned</span>' ?></td>
                                        <td><small class="text-muted"><?= date('M d, H:i', strtotime($t['created_at'])) ?></small></td>
                                        <td>
                                            <a href="complaint_details.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-primary">
                                                Manage
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
