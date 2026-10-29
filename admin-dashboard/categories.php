<?php
// =============================================================================
// Smart Campus Admin Dashboard - Manage Categories
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

$message = "";
$status = "";

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = strtoupper(trim($_POST['default_priority'] ?? 'MEDIUM'));

    if (empty($name)) {
        $message = "Category name is required.";
        $status = "warning";
    } else {
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            $message = "Category '{$name}' already exists.";
            $status = "danger";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description, default_priority) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $priority]);
            $message = "Category '{$name}' added successfully!";
            $status = "success";
        }
    }
}

// Fetch categories with complaint counts
$catStmt = $pdo->query("
    SELECT c.*, COUNT(t.id) AS total_complaints,
           SUM(CASE WHEN t.status IN ('OPEN', 'ASSIGNED', 'IN_PROGRESS') THEN 1 ELSE 0 END) AS active_complaints
    FROM categories c
    LEFT JOIN tickets t ON c.id = t.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
");
$categories = $catStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Smart Campus</title>
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
                    <li class="nav-item"><a class="nav-link active" href="categories.php"><i class="bi bi-tags-fill"></i> Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up"></i> Reports & Analytics</a></li>
                    <li class="nav-item mt-4 border-top border-secondary pt-3"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 fw-bold mb-0">Complaint Categories</h1>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="bi bi-plus-circle me-1"></i> Add New Category
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $status ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Categories Table -->
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table custom-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Default Priority</th>
                                <th>Total Complaints</th>
                                <th>Active Issues</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $index => $cat): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-bold text-navy"><?= htmlspecialchars($cat['name']) ?></td>
                                    <td class="text-secondary small"><?= htmlspecialchars($cat['description']) ?></td>
                                    <td><span class="badge badge-priority-<?= $cat['default_priority'] ?>"><?= $cat['default_priority'] ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?= (int)$cat['total_complaints'] ?></span></td>
                                    <td>
                                        <?php if ((int)$cat['active_complaints'] > 0): ?>
                                            <span class="badge bg-warning text-dark"><?= (int)$cat['active_complaints'] ?> Open</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">0 Open</span>
                                        <?php endif; ?>
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

<!-- Modal: Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="" class="modal-content">
            <input type="hidden" name="action" value="add_category">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Campus Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Elevator Maintenance" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Describe the scope of this category..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Default Priority</label>
                    <select name="default_priority" class="form-select">
                        <option value="LOW">LOW</option>
                        <option value="MEDIUM" selected>MEDIUM</option>
                        <option value="HIGH">HIGH</option>
                        <option value="CRITICAL">CRITICAL</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
