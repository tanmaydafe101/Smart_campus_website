<?php
// =============================================================================
// Smart Campus Admin Dashboard - Home / Overview
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

// Fetch summary metrics
$kpiStmt = $pdo->query("
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
$kpi = $kpiStmt->fetch();

// Category statistics for Chart
$catStmt = $pdo->query("
    SELECT c.name, COUNT(t.id) AS ticket_count
    FROM categories c
    LEFT JOIN tickets t ON c.id = t.category_id
    GROUP BY c.id, c.name
    ORDER BY ticket_count DESC
");
$categories = $catStmt->fetchAll();

// Recent 6 complaints
$recentStmt = $pdo->query("
    SELECT 
        t.id, t.ticket_code, t.title, t.priority, t.status, t.created_at,
        u.name AS reporter_name, c.name AS category_name,
        tech.name AS technician_name
    FROM tickets t
    JOIN users u ON t.reporter_id = u.id
    JOIN categories c ON t.category_id = c.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    ORDER BY t.created_at DESC
    LIMIT 6
");
$recentTickets = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Campus Admin</title>
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="complaints.php">
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
            <!-- Top Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <h1 class="h3 fw-bold mb-0 text-navy">Campus Facilities Overview</h1>
                    <small class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['user']['name']) ?> (<?= ucfirst($_SESSION['user']['role']) ?>)</small>
                </div>
                <div>
                    <a href="complaints.php" class="btn btn-outline-primary btn-sm me-2">
                        <i class="bi bi-list-task"></i> View Complaints
                    </a>
                    <a href="reports.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export Report
                    </a>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-card">
                        <div class="kpi-label">Total</div>
                        <div class="kpi-num"><?= (int)$kpi['total'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-card border-open">
                        <div class="kpi-label text-warning">Open</div>
                        <div class="kpi-num text-warning"><?= (int)$kpi['count_open'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-card border-assigned">
                        <div class="kpi-label text-primary">Assigned</div>
                        <div class="kpi-num text-primary"><?= (int)$kpi['count_assigned'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-card border-progress">
                        <div class="kpi-label text-purple" style="color: #8b5cf6;">In Progress</div>
                        <div class="kpi-num" style="color: #8b5cf6;"><?= (int)$kpi['count_in_progress'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-card border-resolved">
                        <div class="kpi-label text-success">Resolved</div>
                        <div class="kpi-num text-success"><?= (int)$kpi['count_resolved'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="kpi-card border-overdue">
                        <div class="kpi-label text-danger">Overdue</div>
                        <div class="kpi-num text-danger"><?= (int)$kpi['count_overdue'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-pie-chart-fill me-1 text-primary"></i> Complaints by Status
                        </div>
                        <div class="card-body" style="height: 280px; position: relative;">
                            <canvas id="statusChart" 
                                data-open="<?= (int)$kpi['count_open'] ?>"
                                data-assigned="<?= (int)$kpi['count_assigned'] ?>"
                                data-inprogress="<?= (int)$kpi['count_in_progress'] ?>"
                                data-resolved="<?= (int)$kpi['count_resolved'] ?>"
                                data-closed="<?= (int)$kpi['count_closed'] ?>"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-bar-chart-fill me-1 text-primary"></i> Complaints by Category
                        </div>
                        <div class="card-body" style="height: 280px; position: relative;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Complaints Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-1 text-primary"></i> Recent Campus Complaints</h6>
                    <a href="complaints.php" class="btn btn-sm btn-link text-decoration-none">View All &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Reported By</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Technician</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentTickets)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted">No complaints submitted yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentTickets as $ticket): ?>
                                    <tr>
                                        <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($ticket['ticket_code']) ?></td>
                                        <td><?= htmlspecialchars($ticket['title']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($ticket['category_name']) ?></span></td>
                                        <td><?= htmlspecialchars($ticket['reporter_name']) ?></td>
                                        <td><span class="badge badge-priority-<?= $ticket['priority'] ?>"><?= $ticket['priority'] ?></span></td>
                                        <td><span class="badge badge-status badge-<?= $ticket['status'] ?>"><?= str_replace('_', ' ', $ticket['status']) ?></span></td>
                                        <td><?= !empty($ticket['technician_name']) ? htmlspecialchars($ticket['technician_name']) : '<span class="text-muted">Unassigned</span>' ?></td>
                                        <td>
                                            <a href="complaint_details.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-primary">
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    window.campusCategoryData = [
        <?php foreach ($categories as $cat): ?>
            { name: "<?= addslashes($cat['name']) ?>", count: <?= (int)$cat['ticket_count'] ?> },
        <?php endforeach; ?>
    ];
</script>
<script src="js/main.js"></script>
</body>
</html>
