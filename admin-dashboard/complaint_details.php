<?php
// =============================================================================
// Smart Campus Admin Dashboard - Complaint Details & Management
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

$ticketId = (int)($_GET['id'] ?? 0);
if ($ticketId <= 0) {
    header("Location: complaints.php");
    exit();
}

$alert = null;
$alertType = "info";

// Handle Form Submissions (Status Change, Assign Technician, Add Comment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Status Update
    if ($action === 'update_status') {
        $newStatus = strtoupper(trim($_POST['status'] ?? ''));
        $comment = trim($_POST['status_comment'] ?? '');

        if (in_array($newStatus, ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'], true)) {
            $curr = $pdo->prepare("SELECT status FROM tickets WHERE id = ?");
            $curr->execute([$ticketId]);
            $currentStatus = $curr->fetchColumn();

            $resolvedSql = ($newStatus === 'RESOLVED') ? ", resolved_at = NOW()" : "";
            $stmt = $pdo->prepare("UPDATE tickets SET status = ? {$resolvedSql} WHERE id = ?");
            $stmt->execute([$newStatus, $ticketId]);

            // Add history
            $hist = $pdo->prepare("INSERT INTO ticket_history (ticket_id, changed_by, old_status, new_status, comment) VALUES (?, ?, ?, ?, ?)");
            $hist->execute([$ticketId, $_SESSION['user']['id'], $currentStatus, $newStatus, !empty($comment) ? $comment : "Status updated to {$newStatus}"]);

            $alert = "Status updated to {$newStatus} successfully!";
            $alertType = "success";
        }
    }

    // 2. Assign Technician
    if ($action === 'assign_technician') {
        $techId = (int)($_POST['technician_id'] ?? 0);
        $assignNote = trim($_POST['assign_note'] ?? '');

        if ($techId > 0) {
            $tStmt = $pdo->prepare("SELECT name FROM users WHERE id = ? AND role = 'technician'");
            $tStmt->execute([$techId]);
            $techName = $tStmt->fetchColumn();

            if ($techName) {
                $curr = $pdo->prepare("SELECT status FROM tickets WHERE id = ?");
                $curr->execute([$ticketId]);
                $currStatus = $curr->fetchColumn();
                $newStatus = ($currStatus === 'OPEN') ? 'ASSIGNED' : $currStatus;

                $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = ?, status = ? WHERE id = ?");
                $stmt->execute([$techId, $newStatus, $ticketId]);

                $hist = $pdo->prepare("INSERT INTO ticket_history (ticket_id, changed_by, old_status, new_status, comment) VALUES (?, ?, ?, ?, ?)");
                $histComment = "Assigned to {$techName}" . (!empty($assignNote) ? ". Note: {$assignNote}" : "");
                $hist->execute([$ticketId, $_SESSION['user']['id'], $currStatus, $newStatus, $histComment]);

                $alert = "Technician {$techName} assigned successfully!";
                $alertType = "success";
            }
        }
    }

    // 3. Add Discussion Comment
    if ($action === 'add_comment') {
        $msg = trim($_POST['comment_message'] ?? '');
        if (!empty($msg)) {
            $cStmt = $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $cStmt->execute([$ticketId, $_SESSION['user']['id'], $msg]);
            $alert = "Comment posted successfully!";
            $alertType = "success";
        }
    }
}

// Fetch Ticket Details
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        c.name AS category_name,
        l.building, l.room, l.qr_code_token,
        reporter.name AS reporter_name, reporter.email AS reporter_email,
        tech.name AS technician_name, tech.email AS technician_email
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN locations l ON t.location_id = l.id
    JOIN users reporter ON t.reporter_id = reporter.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: complaints.php");
    exit();
}

// Fetch Technicians list for dropdown
$technicians = $pdo->query("SELECT id, name, email FROM users WHERE role = 'technician' ORDER BY name ASC")->fetchAll();

// Fetch History
$histStmt = $pdo->prepare("
    SELECT th.*, u.name AS user_name, u.role AS user_role
    FROM ticket_history th
    JOIN users u ON th.changed_by = u.id
    WHERE th.ticket_id = ?
    ORDER BY th.created_at DESC
");
$histStmt->execute([$ticketId]);
$history = $histStmt->fetchAll();

// Fetch Comments
$commStmt = $pdo->prepare("
    SELECT tc.*, u.name AS user_name, u.role AS user_role
    FROM ticket_comments tc
    JOIN users u ON tc.user_id = u.id
    WHERE tc.ticket_id = ?
    ORDER BY tc.created_at ASC
");
$commStmt->execute([$ticketId]);
$comments = $commStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ticket['ticket_code']) ?> - Complaint Details</title>
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
                    <li class="nav-item"><a class="nav-link active" href="complaints.php"><i class="bi bi-card-checklist"></i> All Complaints</a></li>
                    <li class="nav-item"><a class="nav-link" href="categories.php"><i class="bi bi-tags-fill"></i> Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reports & Analytics</a></li>
                    <li class="nav-item mt-4 border-top border-secondary pt-3"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <a href="complaints.php" class="text-decoration-none text-muted small">&larr; Back to Complaints</a>
                    <h2 class="h4 fw-bold mt-1 mb-0">Complaint <?= htmlspecialchars($ticket['ticket_code']) ?></h2>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge badge-priority-<?= $ticket['priority'] ?> fs-6 p-2"><?= $ticket['priority'] ?> PRIORITY</span>
                    <span class="badge badge-status badge-<?= $ticket['status'] ?> fs-6 p-2"><?= str_replace('_', ' ', $ticket['status']) ?></span>
                </div>
            </div>

            <?php if ($alert): ?>
                <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($alert) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Left Column: Details, Image, Discussion -->
                <div class="col-lg-8">
                    <!-- Issue Information Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-info-circle-fill text-primary me-2"></i> Issue Description
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold text-navy mb-2"><?= htmlspecialchars($ticket['title']) ?></h5>
                            <p class="text-secondary" style="white-space: pre-wrap;"><?= htmlspecialchars($ticket['description']) ?></p>

                            <?php if (!empty($ticket['photo_path'])): ?>
                                <hr>
                                <div class="fw-semibold mb-2">Attached Issue Photograph:</div>
                                <a href="../backend/<?= htmlspecialchars($ticket['photo_path']) ?>" target="_blank">
                                    <img src="../backend/<?= htmlspecialchars($ticket['photo_path']) ?>" alt="Complaint Photo" class="img-fluid rounded border shadow-sm" style="max-height: 320px;">
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Discussion / Comments Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-chat-dots-fill text-primary me-2"></i> Activity Discussion
                        </div>
                        <div class="card-body">
                            <?php if (empty($comments)): ?>
                                <div class="text-center py-3 text-muted small">No comments or notes posted yet.</div>
                            <?php else: ?>
                                <?php foreach ($comments as $c): ?>
                                    <div class="comment-bubble <?= in_array($c['user_role'], ['admin', 'technician']) ? 'comment-admin' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-semibold small">
                                                <?= htmlspecialchars($c['user_name']) ?>
                                                <span class="badge bg-secondary ms-1"><?= ucfirst($c['user_role']) ?></span>
                                            </span>
                                            <span class="text-muted small"><?= date('M d, H:i', strtotime($c['created_at'])) ?></span>
                                        </div>
                                        <div class="small text-secondary"><?= nl2br(htmlspecialchars($c['message'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <hr>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="add_comment">
                                <div class="mb-2">
                                    <textarea name="comment_message" class="form-control form-control-sm" rows="3" placeholder="Post a comment or update for the student/staff..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send-fill me-1"></i> Post Comment</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Status Actions, Assignment, History -->
                <div class="col-lg-4">
                    <!-- Status Progression Workflow Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-arrow-repeat text-primary me-2"></i> Manage Status Workflow
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_status">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Update Status</label>
                                    <select name="status" class="form-select form-select-sm" required>
                                        <option value="OPEN" <?= $ticket['status'] === 'OPEN' ? 'selected' : '' ?>>OPEN</option>
                                        <option value="ASSIGNED" <?= $ticket['status'] === 'ASSIGNED' ? 'selected' : '' ?>>ASSIGNED</option>
                                        <option value="IN_PROGRESS" <?= $ticket['status'] === 'IN_PROGRESS' ? 'selected' : '' ?>>IN_PROGRESS</option>
                                        <option value="RESOLVED" <?= $ticket['status'] === 'RESOLVED' ? 'selected' : '' ?>>RESOLVED</option>
                                        <option value="CLOSED" <?= $ticket['status'] === 'CLOSED' ? 'selected' : '' ?>>CLOSED</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Status Change Note</label>
                                    <input type="text" name="status_comment" class="form-control form-control-sm" placeholder="Reason or resolution summary">
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm w-100 fw-semibold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Update Status
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Assign Technician Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-person-gear text-primary me-2"></i> Assign Technician
                        </div>
                        <div class="card-body">
                            <div class="small mb-2">
                                Current Technician: 
                                <strong><?= !empty($ticket['technician_name']) ? htmlspecialchars($ticket['technician_name']) : '<span class="text-danger">None Assigned</span>' ?></strong>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="assign_technician">
                                <div class="mb-2">
                                    <select name="technician_id" class="form-select form-select-sm" required>
                                        <option value="">-- Choose Technician --</option>
                                        <?php foreach ($technicians as $tech): ?>
                                            <option value="<?= $tech['id'] ?>" <?= (int)$ticket['assigned_to'] === (int)$tech['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tech['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="assign_note" class="form-control form-control-sm" placeholder="Instructions for technician">
                                </div>
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-person-check-fill me-1"></i> Assign
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Metadata Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-geo-alt-fill text-primary me-2"></i> Campus Metadata
                        </div>
                        <div class="card-body small">
                            <div class="mb-2"><strong>Category:</strong> <?= htmlspecialchars($ticket['category_name']) ?></div>
                            <div class="mb-2"><strong>Location:</strong> <?= htmlspecialchars($ticket['building']) ?> - <?= htmlspecialchars($ticket['room']) ?></div>
                            <div class="mb-2"><strong>QR Token:</strong> <code><?= htmlspecialchars($ticket['qr_code_token']) ?></code></div>
                            <hr>
                            <div class="mb-2"><strong>Reported By:</strong> <?= htmlspecialchars($ticket['reporter_name']) ?></div>
                            <div class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($ticket['reporter_email']) ?></div>
                            <div class="mb-2"><strong>Reported At:</strong> <?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?></div>
                            <div class="mb-2"><strong>SLA Deadline:</strong> <?= !empty($ticket['due_at']) ? date('M d, Y H:i', strtotime($ticket['due_at'])) : 'N/A' ?></div>
                        </div>
                    </div>

                    <!-- Status History Timeline -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-clock-history text-primary me-2"></i> Transition History
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach ($history as $h): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="fw-bold small"><?= $h['new_status'] ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            By <?= htmlspecialchars($h['user_name']) ?> &bull; <?= date('M d, H:i', strtotime($h['created_at'])) ?>
                                        </div>
                                        <?php if (!empty($h['comment'])): ?>
                                            <div class="text-secondary small mt-1"><?= htmlspecialchars($h['comment']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
