<?php
// =============================================================================
// Smart Campus Student Portal - Report New Issue / Complaint
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

$error = "";
$success = "";

// Fetch Categories and Locations
$categories = $pdo->query("SELECT id, name, default_priority FROM categories ORDER BY name ASC")->fetchAll();
$locations = $pdo->query("SELECT id, building, room FROM locations ORDER BY building ASC, room ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $location_id = (int)($_POST['location_id'] ?? 0);
    $priority = strtoupper(trim($_POST['priority'] ?? 'MEDIUM'));

    if (empty($title) || empty($description) || $category_id <= 0 || $location_id <= 0) {
        $error = "Please fill in all required fields.";
    } else {
        // Handle photo upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileSize = $_FILES['photo']['size'];
            $fileName = $_FILES['photo']['name'];

            if ($fileSize > 5 * 1024 * 1024) {
                $error = "Attached image cannot exceed 5MB.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($fileTmpPath);
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];

                if (!in_array($mimeType, $allowed, true)) {
                    $error = "Invalid image format. Allowed formats: JPG, PNG, WebP.";
                } else {
                    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                    if (empty($ext)) $ext = 'jpg';
                    $uploadDir = __DIR__ . '/../backend/uploads/tickets/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $newFileName = 'ticket_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                    $dest = $uploadDir . $newFileName;
                    if (move_uploaded_file($fileTmpPath, $dest)) {
                        $photoPath = 'uploads/tickets/' . $newFileName;
                    }
                }
            }
        }

        if (empty($error)) {
            $year = date('Y');
            $rand = strtoupper(bin2hex(random_bytes(3)));
            $ticketCode = "TICK-{$year}-{$rand}";

            $slaHours = 48;
            if ($priority === 'CRITICAL') $slaHours = 12;
            elseif ($priority === 'HIGH') $slaHours = 24;
            elseif ($priority === 'LOW') $slaHours = 72;
            $dueAt = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (ticket_code, reporter_id, category_id, location_id, title, description, photo_path, priority, status, created_at, due_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'OPEN', NOW(), ?)
                ");
                $stmt->execute([$ticketCode, $studentId, $category_id, $location_id, $title, $description, $photoPath, $priority, $dueAt]);
                $newTicketId = (int)$pdo->lastInsertId();

                $hist = $pdo->prepare("INSERT INTO ticket_history (ticket_id, changed_by, old_status, new_status, comment) VALUES (?, ?, NULL, 'OPEN', 'Ticket registered by student via web portal.')");
                $hist->execute([$newTicketId, $studentId]);

                $pdo->commit();
                header("Location: my_complaints.php?created=" . urlencode($ticketCode));
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to submit ticket: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Issue - Smart Campus</title>
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
                    <li class="nav-item"><a class="nav-link active" href="new_complaint.php"><i class="bi bi-plus-circle-fill"></i> Report Issue</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_complaints.php"><i class="bi bi-card-checklist"></i> My Complaints</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a></li>
                    <li class="nav-item mt-4 border-top border-secondary pt-3"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h3 fw-bold mb-0">Report Campus Issue</h1>
                    <small class="text-muted">Submit infrastructure, Wi-Fi, electrical or laboratory maintenance issues</small>
                </div>
                <a href="my_complaints.php" class="btn btn-outline-secondary btn-sm">View My Issues</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Complaint Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Wi-Fi router red light blinking in CS Lab 2" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Issue Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Campus Location <span class="text-danger">*</span></label>
                                <select name="location_id" class="form-select" required>
                                    <option value="">-- Select Building &amp; Room --</option>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['building']) ?> - <?= htmlspecialchars($loc['room']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Severity / Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="LOW">Low (Cosmetic / General)</option>
                                    <option value="MEDIUM" selected>Medium (Standard Maintenance)</option>
                                    <option value="HIGH">High (Impacts Lectures/Labs)</option>
                                    <option value="CRITICAL">Critical (Immediate Hazard/No Water/Power)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description of Problem <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Describe what is broken, how long it has been malfunctioning, and exact spot in room..." required></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Attach Photograph (Optional)</label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Accepted formats: JPG, PNG, WebP (Max file size: 5MB)</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                                <i class="bi bi-send-fill me-1"></i> Submit Complaint
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary px-3 py-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
