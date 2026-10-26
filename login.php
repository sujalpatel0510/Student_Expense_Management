<?php
/**
 * Student Login
 * Student Expense Tracker
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_guest();

$pdo = getDBConnection();
$baseUrl = get_base_url();
$error = '';
$prefillEmail = isset($_GET['demo']) ? 'rahul@student.edu' : '';
$prefillPass = isset($_GET['demo']) ? 'student123' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your college email and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address format.';
        } else {
            // Prepared statement to prevent SQL injection
            $stmt = $pdo->prepare("SELECT * FROM students WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $student = $stmt->fetch();

            if ($student && verify_student_password($password, $student['password'], $pdo, (int)$student['student_id'])) {
                login_user($student);
                set_flash('success', 'Welcome back, ' . $student['name'] . '! Successfully logged in.');
                redirect($baseUrl . '/dashboard.php');
            } else {
                $error = 'Invalid email or password. Please verify your credentials.';
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
    <title>Student Login - Expense Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="text-center mb-4">
                <a href="<?= $baseUrl ?>/index.php" class="text-decoration-none">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-3 mb-2 shadow-sm">
                        <i class="bi bi-wallet2 fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Student Expense Tracker</h3>
                </a>
                <p class="text-muted small">Sign in with your student credentials</p>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <?php display_flash(); ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= $baseUrl ?>/login.php" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field(); ?>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-secondary">College Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" value="<?= htmlspecialchars($prefillEmail) ?>" placeholder="student@college.edu" required autofocus>
                                <div class="invalid-feedback">Please provide a valid email.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label fw-semibold small text-secondary mb-0">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" value="<?= htmlspecialchars($prefillPass) ?>" placeholder="Enter password" required>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mt-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                        </button>
                    </form>

                    <!-- Academic Demonstration Fast Login Widget -->
                    <div class="mt-4 pt-3 border-top text-center">
                        <div class="p-3 bg-light rounded-3 text-start small border">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-dark"><i class="bi bi-info-circle text-primary me-1"></i> Demo Evaluation Account:</span>
                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 small" onclick="fillDemoCredentials()">Auto-fill</button>
                            </div>
                            <div class="text-muted"><strong>Email:</strong> rahul@student.edu</div>
                            <div class="text-muted"><strong>Password:</strong> student123</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-3 border-0 small">
                    Don't have an account yet? <a href="<?= $baseUrl ?>/register.php" class="fw-bold text-primary text-decoration-none">Register here</a>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?= $baseUrl ?>/index.php" class="text-muted text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i> Back to Homepage
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/script.js"></script>
<script>
function fillDemoCredentials() {
    document.getElementById('email').value = 'rahul@student.edu';
    document.getElementById('password').value = 'student123';
}
</script>
</body>
</html>
