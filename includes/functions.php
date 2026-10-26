<?php
/**
 * Core Helper Functions
 * Student Expense Tracker
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (ob_get_level() === 0) {
    ob_start();
}

/**
 * Sanitize and clean string input
 */
function sanitize($data): string {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency with symbol
 */
function format_currency($amount): string {
    $num = (float)$amount;
    return '₹' . number_format($num, 2);
}

/**
 * Format date for user display
 */
function format_date(?string $date, string $format = 'd M, Y'): string {
    if (!$date) return '-';
    $time = strtotime($date);
    return $time ? date($format, $time) : $date;
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output hidden CSRF form input
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Set flash message
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message HTML if present
 */
function display_flash(): void {
    $flash = get_flash();
    if ($flash) {
        $icon = match($flash['type']) {
            'success' => 'bi-check-circle-fill',
            'danger' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default => 'bi-info-circle-fill'
        };
        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="bi ' . $icon . ' me-2 fs-5"></i>
            <div>' . htmlspecialchars($flash['message']) . '</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}

/**
 * Safe HTTP Redirect
 */
function redirect(string $url): void {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    }
    // Fallback if HTML output was already started before redirection
    echo '<script type="text/javascript">window.location.replace(' . json_encode($url) . ');</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '"></noscript>';
    exit;
}

/**
 * Detect Application Base URL dynamically
 */
function get_base_url(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Detect folder relative to doc root
    $base = dirname($script);
    // Normalize backward slashes on Windows
    $base = str_replace('\\', '/', $base);
    
    // Check if script is inside a subfolder like expenses/, income/, budgets/, reports/
    $subfolders = ['/expenses', '/income', '/budgets', '/reports'];
    foreach ($subfolders as $sub) {
        if (str_ends_with($base, $sub)) {
            $base = substr($base, 0, -strlen($sub));
            break;
        }
    }
    
    return rtrim($base, '/') ?: '';
}

/**
 * Fetch Categories from Database
 */
function get_categories(PDO $pdo, string $type = 'expense'): array {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_type = :type ORDER BY category_name ASC");
    $stmt->execute([':type' => $type]);
    return $stmt->fetchAll();
}

/**
 * Fetch Budget calculation and status for a student in a specific month
 */
function get_student_budget_status(PDO $pdo, int $student_id, int $month, int $year): array {
    // 1. Get allocated budget
    $stmt = $pdo->prepare("SELECT budget_amount FROM budgets WHERE student_id = :sid AND month = :m AND year = :y");
    $stmt->execute([':sid' => $student_id, ':m' => $month, ':y' => $year]);
    $budgetRow = $stmt->fetch();
    $budget = $budgetRow ? (float)$budgetRow['budget_amount'] : 0.00;

    // 2. Get total expenses for that month
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0.00) as total_expense
        FROM expenses
        WHERE student_id = :sid AND MONTH(expense_date) = :m AND YEAR(expense_date) = :y
    ");
    $stmt->execute([':sid' => $student_id, ':m' => $month, ':y' => $year]);
    $expense = (float)$stmt->fetchColumn();

    // 3. Get total income for that month
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0.00) as total_income
        FROM income
        WHERE student_id = :sid AND MONTH(income_date) = :m AND YEAR(income_date) = :y
    ");
    $stmt->execute([':sid' => $student_id, ':m' => $month, ':y' => $year]);
    $income = (float)$stmt->fetchColumn();

    $remaining = $budget > 0 ? ($budget - $expense) : 0.00;
    $percentage = $budget > 0 ? round(($expense / $budget) * 100, 1) : 0.00;
    $savings = $income - $expense;

    $status = 'NO_BUDGET';
    if ($budget > 0) {
        if ($expense > $budget) {
            $status = 'EXCEEDED';
        } elseif ($percentage >= 80.0) {
            $status = 'WARNING';
        } else {
            $status = 'SAFE';
        }
    }

    return [
        'budget' => $budget,
        'expense' => $expense,
        'income' => $income,
        'remaining' => $remaining,
        'percentage' => $percentage,
        'savings' => $savings,
        'status' => $status
    ];
}
