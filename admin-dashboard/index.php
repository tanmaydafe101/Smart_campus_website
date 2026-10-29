<?php
// =============================================================================
// Smart Campus Admin Dashboard - Login Page
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in as admin/technician, redirect to dashboard
if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['admin', 'technician'], true)) {
    header("Location: dashboard.php");
    exit();
}

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/utils/auth_middleware.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!in_array($user['role'], ['admin', 'technician'], true)) {
                $error = "Access denied. Only administrators and technicians can access this portal.";
            } else {
                $_SESSION['user'] = [
                    'id'    => (int)$user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'role'  => $user['role']
                ];
                $_SESSION['token'] = AuthMiddleware::generateToken($_SESSION['user']);
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid credentials. Please verify your email and password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Smart Campus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <div class="display-6 fw-bold text-primary mb-1">
                    <i class="bi bi-buildings-fill text-primary"></i> SmartCampus
                </div>
                <div class="text-muted small">Campus Issue & Facility Management Portal</div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 text-center">Administrator Sign In</h5>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger py-2 small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="admin@campus.edu" value="admin@campus.edu" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" value="Admin@123" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <div class="small text-muted mb-1">Default Demo Admin: <code>admin@campus.edu</code> / <code>Admin@123</code></div>
                    <div class="small"><a href="../backend/config/seed_admin.php" class="text-decoration-none">Need to reset admin account?</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
