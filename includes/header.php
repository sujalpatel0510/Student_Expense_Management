<?php
/**
 * Global Header Component
 * Student Expense Tracker
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Enforce login for dashboard views by default unless explicitly public
if (!isset($isPublicPage) || !$isPublicPage) {
    require_login();
}

$currentUser = current_student();
$baseUrl = get_base_url();
$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Student Expense Tracker</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Chart.js for Visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="app-wrapper">
    <?php if (is_logged_in() && (!isset($hideSidebar) || !$hideSidebar)): ?>
        <?php include __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>

    <div class="main-content">
        <?php if (is_logged_in() && (!isset($hideNavbar) || !$hideNavbar)): ?>
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-light border d-lg-none" id="sidebarToggle" aria-label="Toggle Sidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-sm-block">
                    <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($pageTitle) ?></h5>
                    <span class="text-muted small">Academic Year 2026-2027</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="d-none d-md-flex align-items-center gap-2 px-3 py-1 bg-light rounded-pill border">
                    <i class="bi bi-mortarboard text-primary"></i>
                    <span class="small fw-semibold text-secondary"><?= htmlspecialchars($currentUser['course'] ?? 'Student') ?></span>
                </div>
                
                <div class="dropdown">
                    <button class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2 rounded-pill" type="button" data-bs-dismiss="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar" style="width: 28px; height: 28px; font-size: 0.75rem;">
                            <?= strtoupper(substr($currentUser['name'] ?? 'S', 0, 1)) ?>
                        </div>
                        <span class="small fw-bold d-none d-sm-inline"><?= htmlspecialchars($currentUser['name'] ?? 'Student') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                        <li class="dropdown-header">
                            <div class="fw-bold"><?= htmlspecialchars($currentUser['name'] ?? '') ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($currentUser['email'] ?? '') ?></div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/budgets/view.php"><i class="bi bi-piggy-bank me-2"></i> Budget Status</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= $baseUrl ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>
        <?php endif; ?>

        <main class="content-body">
            <div class="container-fluid p-0">
                <?php display_flash(); ?>
