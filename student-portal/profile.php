<?php
// =============================================================================
// Smart Campus Student Portal - Profile & Account Settings
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

$alert = "";
$alertType = "info";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($name)) {
        $alert = "Name cannot be empty.";
        $alertType = "warning";
    } else {
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                $alert = "New password must be at least 6 characters long.";
                $alertType = "warning";
            } else {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$name, $hash, $studentId]);
                $_SESSION['student']['name'] = $name;
                $alert = "Profile and password updated successfully!";
                $alertType = "success";
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $studentId]);
            $_SESSION['student']['name'] = $name;
            $alert = "Profile name updated successfully!";
            $alertType = "success";
        }
    }
}

// Fetch user info
$stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
$stmt->execute([$studentId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart Campus</title>
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
                    <li class="nav-item"><a class="nav-link" href="my_complaints.php"><i class="bi bi-card-checklist"></i> My Complaints</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a></li>
                    <li class="nav-item mt-4 border-top border-secondary pt-3"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h1 class="h3 fw-bold mb-4">Student Profile &amp; Settings</h1>

            <?php if (!empty($alert)): ?>
                <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($alert) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-6 col-lg-5">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-person-lines-fill text-primary me-2"></i> Edit Account
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Campus Email (Read-Only)</label>
                                    <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Full Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-semibold">Change Password (Leave blank to keep current)</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="New password (min 6 chars)">
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                                    <i class="bi bi-save me-1"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header bg-white py-3 fw-bold">
                            <i class="bi bi-shield-check text-primary me-2"></i> Account Summary
                        </div>
                        <div class="card-body small">
                            <div class="mb-2"><strong>Role:</strong> <span class="badge bg-primary">STUDENT</span></div>
                            <div class="mb-2"><strong>Student ID:</strong> #<?= (int)$user['id'] ?></div>
                            <div class="mb-2"><strong>Registered On:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                            <hr>
                            <a href="logout.php" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-box-arrow-left me-1"></i> Sign Out of Student Portal
                            </a>
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
