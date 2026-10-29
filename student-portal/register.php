<?php
// =============================================================================
// Smart Campus Student Portal - Registration Page
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['student']) && !empty($_SESSION['student']['id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/utils/auth_middleware.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid campus email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "This email is already registered. Please log in.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'student')");
            $insert->execute([$name, $email, $hash]);
            $newId = (int)$pdo->lastInsertId();

            $_SESSION['student'] = [
                'id'    => $newId,
                'name'  => $name,
                'email' => $email,
                'role'  => 'student'
            ];
            $_SESSION['student_token'] = AuthMiddleware::generateToken($_SESSION['student']);
            header("Location: dashboard.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Smart Campus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <a href="../index.php" class="text-decoration-none">
                    <div class="display-6 fw-bold text-primary mb-1">
                        <i class="bi bi-mortarboard-fill text-primary"></i> SmartCampus
                    </div>
                </a>
                <div class="text-muted small">Student Self-Registration</div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 text-center">Create Student Account</h5>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Vikramaditya Rao" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Campus Email</label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. vikram.student@campus.edu" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-person-check-fill me-1"></i> Complete Registration
                        </button>
                    </form>

                    <div class="mt-3 text-center">
                        <span class="text-muted small">Already have an account?</span>
                        <a href="index.php" class="text-decoration-none fw-semibold small ms-1">Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
