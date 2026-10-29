<?php
// =============================================================================
// Smart Campus Issue & Facility Management System
// Main Dual-Portal Landing Page
// =============================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Campus - Issue & Facility Management Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-navy: #0f2347;
            --secondary-blue: #2563eb;
            --bg-light: #f8fafc;
        }
        body {
            background-color: var(--bg-light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .hero-banner {
            background: linear-gradient(135deg, #0f2347 0%, #1e3a8a 100%);
            color: white;
            padding: 4rem 1rem 3rem 1rem;
            text-align: center;
        }
        .portal-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            height: 100%;
        }
        .portal-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
        }
        .portal-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.25rem;
        }
        .icon-student {
            background-color: #dbeafe;
            color: #2563eb;
        }
        .icon-admin {
            background-color: #fef3c7;
            color: #d97706;
        }
        .badge-live {
            background-color: #10b981;
            color: white;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
        }
    </style>
</head>
<body>

    <!-- Hero Header -->
    <header class="hero-banner">
        <div class="container">
            <span class="badge-live mb-3 d-inline-block"><i class="bi bi-broadcast me-1"></i> System Online &amp; Active</span>
            <h1 class="display-5 fw-bold mb-2">Smart Campus Issue Management</h1>
            <p class="lead text-light mb-0 mx-auto" style="max-width: 650px;">
                A centralized digital portal for reporting, tracking, and resolving campus facility, Wi-Fi, classroom, and electrical problems.
            </p>
        </div>
    </header>

    <!-- Dual Portal Selection Section -->
    <main class="container py-5 flex-grow-1">
        <div class="row justify-content-center g-4">
            
            <!-- 1. Student Portal Card -->
            <div class="col-md-5 col-lg-4">
                <div class="card portal-card p-4 text-center">
                    <div class="card-body d-flex flex-column">
                        <div class="portal-icon icon-student mx-auto">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <h3 class="fw-bold text-navy mb-2">Student Portal</h3>
                        <p class="text-secondary small mb-4 flex-grow-1">
                            Report issues with campus Wi-Fi, classroom fans, projectors, water dispensers, and track resolution in real-time.
                        </p>

                        <a href="student-portal/index.php" class="btn btn-primary btn-lg w-100 mb-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Student Sign In
                        </a>
                        <a href="student-portal/register.php" class="btn btn-outline-secondary w-100 btn-sm">
                            New Student? Register Account
                        </a>

                        <div class="mt-4 pt-3 border-top text-start small">
                            <div class="text-muted fw-semibold mb-1">Demo Student Account:</div>
                            <div><code>ananya.student@campus.edu</code></div>
                            <div>Password: <code>Student@123</code></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Admin & Staff Portal Card -->
            <div class="col-md-5 col-lg-4">
                <div class="card portal-card p-4 text-center">
                    <div class="card-body d-flex flex-column">
                        <div class="portal-icon icon-admin mx-auto">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h3 class="fw-bold text-navy mb-2">Admin Dashboard</h3>
                        <p class="text-secondary small mb-4 flex-grow-1">
                            Review campus complaints, assign technicians, update workflow status, analyze charts, and export CSV reports.
                        </p>

                        <a href="admin-dashboard/index.php" class="btn btn-warning btn-lg w-100 mb-2 fw-semibold text-dark">
                            <i class="bi bi-gear-fill me-1"></i> Administrator Sign In
                        </a>
                        <a href="backend/config/seed_admin.php" class="btn btn-outline-secondary w-100 btn-sm">
                            Admin Account Setup / Reset
                        </a>

                        <div class="mt-4 pt-3 border-top text-start small">
                            <div class="text-muted fw-semibold mb-1">Demo Admin Account:</div>
                            <div><code>admin@campus.edu</code></div>
                            <div>Password: <code>Admin@123</code></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Android App Section -->
        <div class="row justify-content-center mt-5">
            <div class="col-md-10 col-lg-8">
                <div class="card border-0 shadow-sm bg-white p-4 rounded-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="p-3 bg-light rounded-circle text-primary fs-1">
                                <i class="bi bi-phone"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h5 class="fw-bold mb-1">Android Application Available</h5>
                            <p class="text-secondary small mb-0">
                                Native Android app project is ready in <code>SmartCampus/android-app/</code>. Open in Android Studio to test on physical mobile or emulator!
                            </p>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-secondary">Java + XML</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-top py-3 text-center text-muted small">
        <div class="container">
            Smart Campus Issue &amp; Facility Management System &bull; B.Tech IT College Project
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
