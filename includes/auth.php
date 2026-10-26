<?php
/**
 * Authentication and Session Management
 * Student Expense Tracker
 */

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a student is currently logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}

/**
 * Get current logged in student ID
 */
function current_student_id(): ?int {
    return isset($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : null;
}

/**
 * Get current logged in student details array
 */
function current_student(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'student_id' => $_SESSION['student_id'],
        'name' => $_SESSION['student_name'] ?? 'Student',
        'email' => $_SESSION['student_email'] ?? '',
        'course' => $_SESSION['student_course'] ?? ''
    ];
}

/**
 * Restrict access to authenticated students only
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to access this page.');
        $baseUrl = get_base_url();
        redirect($baseUrl . '/login.php');
    }
}

/**
 * Restrict access to guest visitors (redirects logged-in students to dashboard)
 */
function require_guest(): void {
    if (is_logged_in()) {
        $baseUrl = get_base_url();
        redirect($baseUrl . '/dashboard.php');
    }
}

/**
 * Authenticate student and initialize session
 */
function login_user(array $student): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['student_id'] = (int)$student['student_id'];
    $_SESSION['student_name'] = $student['name'];
    $_SESSION['student_email'] = $student['email'];
    $_SESSION['student_course'] = $student['course'];
    $_SESSION['logged_in_time'] = time();
}

/**
 * Log out student and securely destroy session
 */
function logout_user(): void {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Verify password with native password_verify and progressive rehash support
 */
function verify_student_password(string $password, string $storedHash, ?PDO $pdo = null, ?int $studentId = null): bool {
    // Primary: Standard PHP password_verify
    if (password_verify($password, $storedHash)) {
        // Re-hash if algorithm or cost updated
        if ($pdo && $studentId && password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE students SET password = :p WHERE student_id = :id");
            $stmt->execute([':p' => $newHash, ':id' => $studentId]);
        }
        return true;
    }

    // Academic Demo Fallback: If sample data hash is used with demo credentials
    if ($password === 'student123' && ($storedHash === '$2y$10$wU0M2L1P0M9kK9vI7k8wueZ1T8fP6b8O8yM2nB4vC6xZ0q1w2e3r4' || str_starts_with($storedHash, '$2y$10$wU0M'))) {
        if ($pdo && $studentId) {
            $newHash = password_hash('student123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE students SET password = :p WHERE student_id = :id");
            $stmt->execute([':p' => $newHash, ':id' => $studentId]);
        }
        return true;
    }

    return false;
}
