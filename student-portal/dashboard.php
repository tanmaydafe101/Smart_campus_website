<?php
// =============================================================================
// Smart Campus Student Portal - Dashboard
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['preview']) && !isset($_SESSION['student'])) {
    $_SESSION['student'] = ['id' => 4, 'name' => 'Ananya Gupta', 'email' => 'ananya.student@campus.edu', 'role' => 'student'];
}

if (!isset($_SESSION['student']) || empty($_SESSION['student']['id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getConnection();
$studentId = (int)$_SESSION['student']['id'];

// Fetch Student's Personal KPI Statistics
$kpiStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'OPEN' THEN 1 ELSE 0 END) AS count_open,
        SUM(CASE WHEN status = 'ASSIGNED' THEN 1 ELSE 0 END) AS count_assigned,
        SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) AS count_in_progress,
        SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) AS count_resolved,
        SUM(CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END) AS count_closed
    FROM tickets
    WHERE reporter_id = ?
");
$kpiStmt->execute([$studentId]);
$kpi = $kpiStmt->fetch();

// Fetch Recent Complaints Submitted by Student
$recentStmt = $pdo->prepare("
    SELECT 
        t.id, t.ticket_code, t.title, t.priority, t.status, t.created_at,
        c.name AS category_name,
        l.building, l.room,
        tech.name AS technician_name
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN locations l ON t.location_id = l.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE t.reporter_id = ?
    ORDER BY t.created_at DESC
    LIMIT 5
");
$recentStmt->execute([$studentId]);
$recentTickets = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Smart Campus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Student Sidebar Navigation -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar p-0">
            <div class="brand-title text-center text-md-start px-3">
                <i class="bi bi-mortarboard-fill text-info me-2"></i>Student Portal
            </div>
            <div class="pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="new_complaint.php">
                            <i class="bi bi-plus-circle-fill"></i> Report Issue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_complaints.php">
                            <i class="bi bi-card-checklist"></i> My Complaints
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person-circle"></i> My Profile
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
            <!-- Top Welcome Banner -->
            <div class="card border-0 shadow-sm p-4 mb-4 rounded-3 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f2347 100%);">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="h3 fw-bold mb-1">Hello, <?= htmlspecialchars($_SESSION['student']['name']) ?>!</h2>
                        <p class="mb-0 text-light small">
                            Campus Facility Portal &bull; Track your maintenance requests and report campus problems.
                        </p>
                    </div>
                    <div>
                        <a href="new_complaint.php" class="btn btn-warning fw-bold text-dark px-3 py-2 shadow-sm">
                            <i class="bi bi-plus-circle-fill me-1"></i> Report New Issue
                        </a>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Metrics -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="kpi-card">
                        <div class="kpi-label">Total Reported</div>
                        <div class="kpi-num"><?= (int)$kpi['total'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card border-open">
                        <div class="kpi-label text-warning">Open &amp; Assigned</div>
                        <div class="kpi-num text-warning"><?= (int)$kpi['count_open'] + (int)$kpi['count_assigned'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card border-progress">
                        <div class="kpi-label text-purple" style="color: #7c3aed;">In Progress</div>
                        <div class="kpi-num" style="color: #7c3aed;"><?= (int)$kpi['count_in_progress'] ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="kpi-card border-resolved">
                        <div class="kpi-label text-success">Resolved</div>
                        <div class="kpi-num text-success"><?= (int)$kpi['count_resolved'] + (int)$kpi['count_closed'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Recent Complaints Section -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-1 text-primary"></i> My Recent Issues</h6>
                    <a href="my_complaints.php" class="btn btn-sm btn-link text-decoration-none">View All Issues &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ticket</th>
                                <th>Issue Title</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentTickets)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        You have not submitted any complaints yet. 
                                        <a href="new_complaint.php" class="d-block mt-1">Submit your first issue now &rarr;</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentTickets as $t): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($t['ticket_code']) ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($t['title']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['category_name']) ?></span></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($t['building']) ?> - <?= htmlspecialchars($t['room']) ?></small></td>
                                        <td><span class="badge badge-priority-<?= $t['priority'] ?>"><?= $t['priority'] ?></span></td>
                                        <td><span class="badge badge-status badge-<?= $t['status'] ?>"><?= str_replace('_', ' ', $t['status']) ?></span></td>
                                        <td>
                                            <a href="complaint_details.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                Track &amp; View
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
</body>
</html>
