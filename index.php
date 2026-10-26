<?php
/**
 * Public Landing Page
 * Student Expense Tracker - College DBMS Project
 */
$isPublicPage = true;
$hideSidebar = true;
$hideNavbar = true;
$hideFooter = true;
$pageTitle = 'Welcome';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// If student is already logged in, redirect directly to their dashboard
if (is_logged_in()) {
    redirect(get_base_url() . '/dashboard.php');
}

$baseUrl = get_base_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Expense Tracker - College DBMS Project</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/style.css" rel="stylesheet">
    <style>
        .hero-section {
            background: radial-gradient(circle at top right, #3730a3, #0f172a);
            color: #ffffff;
            padding: 5rem 0;
            position: relative;
        }
        .feature-card {
            border-radius: 1rem;
            padding: 2rem;
            height: 100%;
            background: #ffffff;
            border: 1px solid var(--border-color);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        }
        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= $baseUrl ?>/index.php">
                <i class="bi bi-wallet2 text-primary fs-4"></i>
                <span>Student Expense Tracker</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= $baseUrl ?>/login.php" class="btn btn-outline-light px-3">Login</a>
                <a href="<?= $baseUrl ?>/register.php" class="btn btn-primary px-3">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center text-lg-start">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="badge bg-primary bg-opacity-25 text-primary-light px-3 py-2 rounded-pill fw-semibold mb-3 border border-primary">
                        <i class="bi bi-database-check me-1"></i> DBMS Academic Project
                    </span>
                    <h1 class="display-4 fw-extrabold text-white mb-3">
                        Smart Personal Finance &amp; Budgeting for College Students
                    </h1>
                    <p class="lead text-slate-300 mb-4" style="color: #cbd5e1;">
                        Record expenses, manage monthly budgets, track multiple income streams, and analyze your financial habits with dynamic charts and relational database intelligence.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        <a href="<?= $baseUrl ?>/login.php" class="btn btn-primary btn-lg px-4 shadow">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Launch Tracker
                        </a>
                        <a href="<?= $baseUrl ?>/register.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-person-plus me-2"></i> Create Account
                        </a>
                    </div>
                </div>

                <!-- Demo Credentials Quick Card -->
                <div class="col-lg-5">
                    <div class="card bg-white text-dark shadow-lg border-0 p-4 rounded-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h5 class="fw-bold mb-0 text-primary">
                                <i class="bi bi-mortarboard-fill me-2"></i> Viva &amp; Demo Access
                            </h5>
                            <span class="badge bg-success">Pre-populated</span>
                        </div>
                        <p class="text-muted small mb-3">
                            Evaluate the system using the pre-loaded student account with full semester expenses, budgets, and income records:
                        </p>
                        <div class="bg-light p-3 rounded-3 border mb-3 small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Student Email:</span>
                                <span class="fw-bold text-dark">rahul@student.edu</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Default Password:</span>
                                <code class="fw-bold text-primary">student123</code>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Registered Course:</span>
                                <span class="badge bg-secondary">B.Tech CSE</span>
                            </div>
                        </div>
                        <a href="<?= $baseUrl ?>/login.php?demo=1" class="btn btn-dark w-100 py-2 fw-semibold">
                            Login with Demo Account <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Modules Section -->
    <section class="py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h6 class="text-primary fw-bold text-uppercase tracking-wide">Relational System Architecture</h6>
                <h2 class="fw-bold text-dark">Engineered for Academic Rigor &amp; Real Utility</h2>
                <p class="text-muted mx-auto" style="max-width: 650px;">
                    Built with third-normal-form (3NF) relational database design, foreign-key integrity, and dynamic analytics.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-credit-card-2-front"></i>
                        </div>
                        <h5 class="fw-bold">Expense Tracking (CRUD)</h5>
                        <p class="text-muted small">
                            Complete Create, Read, Update, and Delete operations for daily student expenses categorized across food, travel, books, fees, and rent.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <h5 class="fw-bold">Income Management</h5>
                        <p class="text-muted small">
                            Track pocket money, scholarships, freelancing gigs, and part-time jobs. Calculates dynamic cumulative inflow per semester.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-pie-chart"></i>
                        </div>
                        <h5 class="fw-bold">Budget &amp; 80% Warning Alerts</h5>
                        <p class="text-muted small">
                            Set monthly budget caps with automated warning alerts when spending hits 80%, plus exceeded-budget alerts and remaining balance calculation.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                        </div>
                        <h5 class="fw-bold">Ledger &amp; CSV Export</h5>
                        <p class="text-muted small">
                            Chronological transaction log combining income and expenditure using SQL `UNION ALL`, filterable by dates and exportable to CSV.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h5 class="fw-bold">Robust Security</h5>
                        <p class="text-muted small">
                            Session-based authentication, CSRF tokens, output escaping (XSS prevention), ownership validation, and Bcrypt password hashing.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h5 class="fw-bold">Normalized Database (3NF)</h5>
                        <p class="text-muted small">
                            Designed with 5 normalized tables in MySQL InnoDB engine with cascading deletes, composite constraints, and indexing.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 border-top">
        <div class="container text-center small text-muted">
            <p class="mb-1 text-light"><strong>Student Expense Tracker</strong> &bull; College DBMS Mini Project</p>
            <p class="mb-0">Designed for XAMPP (Apache, MySQL, PHP) &bull; Academic Year 2026-2027</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
