<?php
// =============================================================================
// Smart Campus Student Portal - My Complaints
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

$statusFilter = strtoupper(trim($_GET['status'] ?? ''));
$search = trim($_GET['search'] ?? '');
$createdCode = trim($_GET['created'] ?? '');

$sql = "
    SELECT 
        t.id, t.ticket_code, t.title, t.priority, t.status, t.created_at, t.due_at,
        c.name AS category_name,
        l.building, l.room,
        tech.name AS technician_name
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN locations l ON t.location_id = l.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE t.reporter_id = ?
";

$params = [$studentId];

if (!empty($statusFilter)) {
    if ($statusFilter === 'OPEN') {
        $sql .= " AND t.status IN ('OPEN', 'ASSIGNED')";
    } elseif ($statusFilter === 'IN_PROGRESS') {
        $sql .= " AND t.status = 'IN_PROGRESS'";
    } elseif ($statusFilter === 'RESOLVED') {
        $sql .= " AND t.status IN ('RESOLVED', 'CLOSED')";
    }
}

if (!empty($search)) {
    $sql .= " AND (t.title LIKE ? OR t.ticket_code LIKE ? OR l.building LIKE ? OR l.room LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
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
    <title>My Complaints - Smart Campus</title>
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="new_complaint.php"><i class="bi bi-plus-circle-fill"></i> Report Issue</a></li>
                    <li class="nav-item"><a class="nav-link active" href="my_complaints.php"><i class="bi bi-card-checklist"></i> My Complaints</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a></li>
                    <li class="nav-item mt-4 border-top border-secondary pt-3"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3 fw-bold mb-0">My Submitted Complaints</h1>
                <a href="new_complaint.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Report New Issue
                </a>
            </div>

            <?php if (!empty($createdCode)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Your complaint has been successfully submitted! Ticket Code: <strong><?= htmlspecialchars($createdCode) ?></strong>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter Chips & Search Bar -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by ticket code, room, or title..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>

                        <div class="col-md-4 d-flex gap-1">
                            <a href="my_complaints.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                            <a href="my_complaints.php?status=OPEN" class="btn btn-sm <?= $statusFilter === 'OPEN' ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>">Open</a>
                            <a href="my_complaints.php?status=IN_PROGRESS" class="btn btn-sm <?= $statusFilter === 'IN_PROGRESS' ? 'btn-purple text-white' : 'btn-outline-secondary' ?>" style="<?= $statusFilter === 'IN_PROGRESS' ? 'background-color: #7c3aed;' : '' ?>">In Progress</a>
                            <a href="my_complaints.php?status=RESOLVED" class="btn btn-sm <?= $statusFilter === 'RESOLVED' ? 'btn-success' : 'btn-outline-secondary' ?>">Resolved</a>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Complaints Table -->
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ticket</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Technician</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tickets)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No complaints found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $t): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-primary"><?= htmlspecialchars($t['ticket_code']) ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($t['title']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['category_name']) ?></span></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($t['building']) ?> - <?= htmlspecialchars($t['room']) ?></small></td>
                                        <td><span class="badge badge-priority-<?= $t['priority'] ?>"><?= $t['priority'] ?></span></td>
                                        <td><span class="badge badge-status badge-<?= $t['status'] ?>"><?= str_replace('_', ' ', $t['status']) ?></span></td>
                                        <td><?= !empty($t['technician_name']) ? htmlspecialchars($t['technician_name']) : '<span class="text-muted small">Unassigned</span>' ?></td>
                                        <td><small class="text-muted"><?= date('M d, H:i', strtotime($t['created_at'])) ?></small></td>
                                        <td>
                                            <a href="complaint_details.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-primary">
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
