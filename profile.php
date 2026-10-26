<?php
/**
 * Student Profile & Security Settings
 * Student Expense Tracker
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pdo = getDBConnection();
$studentId = current_student_id();
$baseUrl = get_base_url();

$error = '';
$pwdError = '';

// Fetch fresh student record
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = :sid LIMIT 1");
$stmt->execute([':sid' => $studentId]);
$student = $stmt->fetch();

// 1. Handle Profile Info Update (Name & Course)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_profile'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $course = trim($_POST['course'] ?? '');

        if (empty($name) || empty($course)) {
            $error = 'Name and Course cannot be left empty.';
        } else {
            $upStmt = $pdo->prepare("UPDATE students SET name = :name, course = :course WHERE student_id = :sid");
            $upStmt->execute([':name' => $name, ':course' => $course, ':sid' => $studentId]);

            $_SESSION['student_name'] = $name;
            $_SESSION['student_course'] = $course;

            set_flash('success', 'Profile information updated successfully!');
            redirect($baseUrl . '/profile.php');
        }
    }
}

// 2. Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $pwdError = 'Security session expired. Please refresh and try again.';
    } else {
        $currPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currPassword) || empty($newPassword) || empty($confirmPassword)) {
            $pwdError = 'All password fields are required.';
        } elseif (!verify_student_password($currPassword, $student['password'], $pdo, $studentId)) {
            $pwdError = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 6) {
            $pwdError = 'New password must be at least 6 characters in length.';
        } elseif ($newPassword !== $confirmPassword) {
            $pwdError = 'New password and confirmation password do not match.';
        } else {
            $newHashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $pwdStmt = $pdo->prepare("UPDATE students SET password = :p WHERE student_id = :sid");
            $pwdStmt->execute([':p' => $newHashed, ':sid' => $studentId]);

            set_flash('success', 'Password updated successfully! Please use your new password next time.');
            redirect($baseUrl . '/profile.php');
        }
    }
}

$pageTitle = 'My Profile';
$activePage = 'profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="mb-4">
            <h3 class="fw-bold mb-1">Student Profile &amp; Settings</h3>
            <p class="text-muted small mb-0">Manage your student credentials, degree information, and password</p>
        </div>

        <div class="row g-4">
            <!-- Left: Profile Info -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge text-primary me-2"></i> Student Details</h6>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger small alert-dismissible fade show">
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="<?= $baseUrl ?>/profile.php" method="POST">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action_update_profile" value="1">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($student['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">College Email Address</label>
                                <input type="email" class="form-control bg-light text-muted" value="<?= htmlspecialchars($student['email']) ?>" readonly>
                                <div class="form-text small">Student email is your unique identifier and cannot be modified.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Degree / Course</label>
                                <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($student['course']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary">Account Created</label>
                                <input type="text" class="form-control bg-light text-muted" value="<?= format_date($student['created_at'], 'd M, Y h:i A') ?>" readonly>
                            </div>

                            <button type="submit" class="btn btn-primary px-4 shadow-sm mt-2">
                                <i class="bi bi-check-circle me-1"></i> Update Details
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right: Change Password -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-shield-lock text-primary me-2"></i> Change Password</h6>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($pwdError)): ?>
                            <div class="alert alert-danger small alert-dismissible fade show">
                                <?= htmlspecialchars($pwdError) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="<?= $baseUrl ?>/profile.php" method="POST" class="needs-validation" novalidate>
                            <?= csrf_field(); ?>
                            <input type="hidden" name="action_change_password" value="1">

                            <div class="mb-3">
                                <label for="current_password" class="form-label small fw-semibold text-secondary">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter current password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label small fw-semibold text-secondary">New Password (Min 6 chars)</label>
                                <input type="password" id="new_password" name="new_password" minlength="6" class="form-control" placeholder="Enter new password" required>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" minlength="6" class="form-control" placeholder="Repeat new password" required>
                            </div>

                            <button type="submit" class="btn btn-outline-danger px-4 shadow-sm mt-2">
                                <i class="bi bi-key me-1"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
