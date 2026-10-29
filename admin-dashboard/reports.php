<?php
// =============================================================================
// Smart Campus Admin Dashboard - Reports & Analytics
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'technician'], true)) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getConnection();

// Summary metrics
$kpi = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END) AS closed,
        SUM(CASE WHEN status NOT IN ('RESOLVED', 'CLOSED') AND due_at < NOW() THEN 1 ELSE 0 END) AS overdue
    FROM tickets
")->fetch();

// Technician performance report
$techStmt = $pdo->query("
    SELECT 
        u.name,
        COUNT(t.id) AS assigned_count,
        SUM(CASE WHEN t.status IN ('RESOLVED', 'CLOSED') THEN 1 ELSE 0 END) AS resolved_count,
        SUM(CASE WHEN t.status IN ('ASSIGNED', 'IN_PROGRESS') THEN 1 ELSE 0 END) AS pending_count
    FROM users u
    LEFT JOIN tickets t ON u.id = t.assigned_to
    WHERE u.role = 'technician'
    GROUP BY u.id, u.name
");
$technicians = $techStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Smart Campus</title>
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="complaints.php"><i class="bi bi-card-checklist"></i> All Complaints</a></li>
                    <li class="nav-item"><a class="nav-link" href="categories.php"><i class="bi bi-tags-fill"></i> Categories</a></li>
                    <li class="nav-item"><a class="nav-link active" href="reports.php"><i class="bi bi-graph-up"></i> Reports & Analytics</a></li>
                    <li class="nav-item mt-4 border-top border-secondary pt-3"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 fw-bold mb-0">Campus Facilities Analytics & Reports</h1>
                <a href="../backend/api/admin/export_csv.php" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download Complete CSV
                </a>
            </div>

            <!-- Resolution Rate Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="kpi-card text-center">
                        <div class="kpi-label">Total Reported</div>
                        <div class="kpi-num"><?= (int)$kpi['total'] ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card text-center border-resolved">
                        <div class="kpi-label text-success">Resolved & Closed</div>
                        <div class="kpi-num text-success"><?= (int)$kpi['resolved'] + (int)$kpi['closed'] ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card text-center">
                        <div class="kpi-label">Resolution Rate</div>
                        <div class="kpi-num text-primary">
                            <?= $kpi['total'] > 0 ? round((((int)$kpi['resolved'] + (int)$kpi['closed']) / (int)$kpi['total']) * 100, 1) : 0 ?>%
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-card text-center border-overdue">
                        <div class="kpi-label text-danger">Overdue SLA</div>
                        <div class="kpi-num text-danger"><?= (int)$kpi['overdue'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Technician Performance Table -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 fw-bold">
                    <i class="bi bi-people-fill text-primary me-2"></i> Technician Resolution Performance
                </div>
                <div class="table-responsive">
                    <table class="table custom-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Technician Name</th>
                                <th>Total Assigned</th>
                                <th>Resolved / Closed</th>
                                <th>Active / Pending</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($technicians as $tech): 
                                $rate = $tech['assigned_count'] > 0 ? round(($tech['resolved_count'] / $tech['assigned_count']) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($tech['name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= (int)$tech['assigned_count'] ?></span></td>
                                    <td><span class="badge bg-success"><?= (int)$tech['resolved_count'] ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?= (int)$tech['pending_count'] ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $rate ?>%;"></div>
                                            </div>
                                            <small class="fw-semibold"><?= $rate ?>%</small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
