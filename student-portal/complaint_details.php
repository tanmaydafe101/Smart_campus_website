<?php
// =============================================================================
// Smart Campus Student Portal - Complaint Details & Tracking
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['student']) || empty($_SESSION['student']['id'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../backend/config/database.php';
$pdo = Database::getConnection();
$studentId = (int)$_SESSION['student']['id'];
$ticketId = (int)($_GET['id'] ?? 0);

if ($ticketId <= 0) {
    header("Location: my_complaints.php");
    exit();
}

// Handle Add Comment Form
$commentAlert = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $msg = trim($_POST['message'] ?? '');
    if (!empty($msg)) {
        // Verify ticket belongs to this student
        $check = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND reporter_id = ?");
        $check->execute([$ticketId, $studentId]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$ticketId, $studentId, $msg]);
            $commentAlert = "Comment posted successfully!";
        }
    }
}

// Fetch Ticket (Enforce Student Ownership)
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        c.name AS category_name,
        l.building, l.room, l.qr_code_token,
        tech.name AS technician_name, tech.email AS technician_email
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN locations l ON t.location_id = l.id
    LEFT JOIN users tech ON t.assigned_to = tech.id
    WHERE t.id = ? AND t.reporter_id = ?
");
$stmt->execute([$ticketId, $studentId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header("Location: my_complaints.php");
    exit();
}

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
                <div>
                    <a href="my_complaints.php" class="text-decoration-none text-muted small">&larr; Back to My Complaints</a>
                    <h2 class="h4 fw-bold mt-1 mb-0">Complaint <?= htmlspecialchars($ticket['ticket_code']) ?></h2>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge badge-priority-<?= $ticket['priority'] ?> fs-6 p-2"><?= $ticket['priority'] ?> PRIORITY</span>
                    <span class="badge badge-status badge-<?= $ticket['status'] ?> fs-6 p-2"><?= str_replace('_', ' ', $ticket['status']) ?></span>
                </div>
            </div>

            <?php if (!empty($commentAlert)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($commentAlert) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Left Column: Details & Discussion -->
                <div class="col-lg-8">
                    <!-- Issue Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-card-heading text-primary me-2"></i> Issue Details
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold text-navy mb-2"><?= htmlspecialchars($ticket['title']) ?></h5>
                            <p class="text-secondary" style="white-space: pre-wrap;"><?= htmlspecialchars($ticket['description']) ?></p>

                            <?php if (!empty($ticket['photo_path'])): ?>
                                <hr>
                                <div class="fw-semibold mb-2">Attached Issue Photograph:</div>
                                <a href="../backend/<?= htmlspecialchars($ticket['photo_path']) ?>" target="_blank">
                                    <img src="../backend/<?= htmlspecialchars($ticket['photo_path']) ?>" alt="Complaint Photo" class="img-fluid rounded border shadow-sm" style="max-height: 300px;">
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Discussion Thread -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-chat-dots-fill text-primary me-2"></i> Activity &amp; Discussion
                        </div>
                        <div class="card-body">
                            <?php if (empty($comments)): ?>
                                <div class="text-center py-3 text-muted small">No comments or notes posted yet.</div>
                            <?php else: ?>
                                <?php foreach ($comments as $c): ?>
                                    <div class="comment-bubble <?= in_array($c['user_role'], ['admin', 'technician']) ? 'comment-staff' : '' ?>">
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
                                    <textarea name="message" class="form-control form-control-sm" rows="3" placeholder="Have a question or update for maintenance staff? Type here..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send-fill me-1"></i> Post Message</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Campus Details & History -->
                <div class="col-lg-4">
                    <!-- Status & Assigned Staff Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-person-badge text-primary me-2"></i> Assignment &amp; SLA
                        </div>
                        <div class="card-body small">
                            <div class="mb-2">
                                <strong>Assigned Technician:</strong><br>
                                <span class="fs-6 fw-semibold text-primary">
                                    <?= !empty($ticket['technician_name']) ? htmlspecialchars($ticket['technician_name']) : '<span class="text-muted">Awaiting Assignment</span>' ?>
                                </span>
                            </div>
                            <hr>
                            <div class="mb-2"><strong>Category:</strong> <?= htmlspecialchars($ticket['category_name']) ?></div>
                            <div class="mb-2"><strong>Location:</strong> <?= htmlspecialchars($ticket['building']) ?> - <?= htmlspecialchars($ticket['room']) ?></div>
                            <div class="mb-2"><strong>Reported On:</strong> <?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?></div>
                            <div class="mb-2"><strong>Target SLA Due:</strong> <?= !empty($ticket['due_at']) ? date('M d, Y H:i', strtotime($ticket['due_at'])) : 'N/A' ?></div>
                            <?php if (!empty($ticket['resolved_at'])): ?>
                                <div class="mb-2 text-success"><strong>Resolved At:</strong> <?= date('M d, Y H:i', strtotime($ticket['resolved_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Status History Timeline -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-clock-history text-primary me-2"></i> Status Timeline
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach ($history as $h): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="fw-bold small"><?= $h['new_status'] ?></div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            By <?= htmlspecialchars($h['user_name']) ?> (<?= ucfirst($h['user_role']) ?>) &bull; <?= date('M d, H:i', strtotime($h['created_at'])) ?>
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
