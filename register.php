<?php
/**
 * Student Registration
 * Student Expense Tracker
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_guest();

$pdo = getDBConnection();
$baseUrl = get_base_url();
$error = '';
$formData = [
    'name' => '',
    'email' => '',
    'course' => 'B.Tech Computer Science & Engineering'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please refresh the page and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $formData['name'] = $name;
        $formData['email'] = $email;
        $formData['course'] = $course;

        // Validation
        if (empty($name) || empty($email) || empty($course) || empty($password) || empty($confirmPassword)) {
            $error = 'All fields are mandatory. Please fill in all required inputs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters in length.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match. Please re-enter carefully.';
        } else {
            // Check for existing student email
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE email = :email");
            $checkStmt->execute([':email' => $email]);
            if ($checkStmt->fetchColumn() > 0) {
                $error = 'A student account is already registered with this email address.';
            } else {
                // Securely hash the password using Bcrypt
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                try {
                    $pdo->beginTransaction();

                    $insertStmt = $pdo->prepare("
                        INSERT INTO students (name, email, password, course) 
                        VALUES (:name, :email, :password, :course)
                    ");
                    $insertStmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':password' => $hashedPassword,
                        ':course' => $course
                    ]);
                    $newStudentId = (int)$pdo->lastInsertId();

                    // Optional: Initialize default monthly budget for the current month
                    $currMonth = (int)date('n');
                    $currYear = (int)date('Y');
                    $budgetStmt = $pdo->prepare("
                        INSERT INTO budgets (student_id, month, year, budget_amount)
                        VALUES (:sid, :m, :y, 10000.00)
                    ");
                    $budgetStmt->execute([
                        ':sid' => $newStudentId,
                        ':m' => $currMonth,
                        ':y' => $currYear
                    ]);

                    $pdo->commit();

                    set_flash('success', 'Registration successful! You can now log in with your credentials.');
                    redirect($baseUrl . '/login.php');
                } catch (PDOException $ex) {
                    $pdo->rollBack();
                    $error = 'Database Error during registration: ' . $ex->getMessage();
                }
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
    <title>Student Registration - Expense Tracker</title>
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
        <div class="col-md-8 col-lg-6">
            <div class="text-center mb-4">
                <a href="<?= $baseUrl ?>/index.php" class="text-decoration-none">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-3 mb-2 shadow-sm">
                        <i class="bi bi-wallet2 fs-2"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Student Expense Tracker</h3>
                </a>
                <p class="text-muted small">Create your student financial tracking profile</p>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center small" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?= $baseUrl ?>/register.php" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field(); ?>

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold small text-secondary">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" placeholder="e.g. Rahul Sharma" required autofocus>
                                <div class="invalid-feedback">Please enter your full name.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-secondary">College / Personal Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" placeholder="student@college.edu" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="course" class="form-label fw-semibold small text-secondary">Degree / Course of Study</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-mortarboard"></i></span>
                                <select class="form-select border-start-0 ps-0" id="course" name="course" required>
                                    <option value="B.Tech Computer Science & Engineering" <?= $formData['course'] === 'B.Tech Computer Science & Engineering' ? 'selected' : '' ?>>B.Tech Computer Science & Engineering</option>
                                    <option value="B.Tech Information Technology" <?= $formData['course'] === 'B.Tech Information Technology' ? 'selected' : '' ?>>B.Tech Information Technology</option>
                                    <option value="Bachelor of Computer Applications (BCA)" <?= $formData['course'] === 'Bachelor of Computer Applications (BCA)' ? 'selected' : '' ?>>Bachelor of Computer Applications (BCA)</option>
                                    <option value="B.Sc Computer Science / IT" <?= $formData['course'] === 'B.Sc Computer Science / IT' ? 'selected' : '' ?>>B.Sc Computer Science / IT</option>
                                    <option value="Master of Computer Applications (MCA)" <?= $formData['course'] === 'Master of Computer Applications (MCA)' ? 'selected' : '' ?>>Master of Computer Applications (MCA)</option>
                                    <option value="M.Tech Computer Engineering" <?= $formData['course'] === 'M.Tech Computer Engineering' ? 'selected' : '' ?>>M.Tech Computer Engineering</option>
                                    <option value="Other Degree Program" <?= $formData['course'] === 'Other Degree Program' ? 'selected' : '' ?>>Other Degree Program</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label for="password" class="form-label fw-semibold small text-secondary">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" minlength="6" placeholder="Min. 6 chars" required>
                                    <div class="invalid-feedback">Minimum 6 characters.</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="confirm_password" class="form-label fw-semibold small text-secondary">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control border-start-0 ps-0" id="confirm_password" name="confirm_password" minlength="6" placeholder="Repeat password" required>
                                    <div class="invalid-feedback">Passwords must match.</div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mt-2">
                            <i class="bi bi-person-check me-1"></i> Register Student Account
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-light text-center py-3 border-0 small">
                    Already registered? <a href="<?= $baseUrl ?>/login.php" class="fw-bold text-primary text-decoration-none">Sign in here</a>
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
</body>
</html>
