<?php
// =============================================================================
// Smart Campus - Admin Seeding Script
// Access via browser: http://localhost/SmartCampus/backend/config/seed_admin.php
// Or run via CLI: php seed_admin.php
// =============================================================================

require_once __DIR__ . '/database.php';

header('Content-Type: text/html; charset=UTF-8');

$pdo = Database::getConnection();

$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? 'System Administrator');
    $email = trim($_POST['email'] ?? 'admin@campus.edu');
    $password = $_POST['password'] ?? '';
    $secret_key = $_POST['secret_key'] ?? '';

    // Simple security key to prevent unauthorized seeding
    if ($secret_key !== 'CampusSetup2026') {
        $message = "Invalid setup secret key. Enter 'CampusSetup2026' to authorize seeding.";
        $status = "danger";
    } elseif (empty($name) || empty($email) || strlen($password) < 6) {
        $message = "All fields required. Password must be at least 6 characters long.";
        $status = "warning";
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Check if admin email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, password_hash = ?, role = 'admin' WHERE id = ?");
            $stmt->execute([$name, $hash, $existing['id']]);
            $message = "Admin account for '{$email}' was successfully updated!";
            $status = "success";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$name, $email, $hash]);
            $message = "Admin account for '{$email}' was successfully created!";
            $status = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Campus - Admin Account Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h5 class="mb-0 fw-bold">Smart Campus Setup</h5>
                    <small>Create or Reset Administrator Account</small>
                </div>
                <div class="card-body p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $status ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="System Administrator" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Email</label>
                            <input type="email" name="email" class="form-control" value="admin@campus.edu" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password (min 6 chars)" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Setup Security Key</label>
                            <input type="password" name="secret_key" class="form-control" placeholder="Default: CampusSetup2026" required>
                            <div class="form-text">Default authorization key: <code>CampusSetup2026</code></div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2">Create / Update Admin</button>
                    </form>
                </div>
                <div class="card-footer text-center bg-white py-3">
                    <a href="../../admin-dashboard/index.php" class="text-decoration-none">Go to Admin Dashboard Login &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
