<?php
/**
 * Set & Update Monthly Budget
 * Student Expense Tracker
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$studentId = current_student_id();
$baseUrl = get_base_url();
$error = '';

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($month < 1 || $month > 12) $month = (int)date('n');
if ($year < 2020 || $year > 2035) $year = (int)date('Y');

// Fetch existing budget if already set
$stmt = $pdo->prepare("SELECT * FROM budgets WHERE student_id = :sid AND month = :m AND year = :y");
$stmt->execute([':sid' => $studentId, ':m' => $month, ':y' => $year]);
$existingBudget = $stmt->fetch();

$currentAmount = $existingBudget ? $existingBudget['budget_amount'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $month = (int)($_POST['month'] ?? 0);
        $year = (int)($_POST['year'] ?? 0);
        $budgetAmount = (float)($_POST['budget_amount'] ?? 0);

        if ($month < 1 || $month > 12) {
            $error = 'Please select a valid calendar month.';
        } elseif ($year < 2020 || $year > 2035) {
            $error = 'Please enter a valid four-digit year.';
        } elseif ($budgetAmount <= 0.00) {
            $error = 'Monthly budget amount must be greater than zero.';
        } else {
            // Uses MySQL ON DUPLICATE KEY UPDATE to uphold relational integrity without duplicates
            $upsertStmt = $pdo->prepare("
                INSERT INTO budgets (student_id, month, year, budget_amount)
                VALUES (:sid, :m, :y, :amt)
                ON DUPLICATE KEY UPDATE budget_amount = VALUES(budget_amount)
            ");
            $upsertStmt->execute([
                ':sid' => $studentId,
                ':m' => $month,
                ':y' => $year,
                ':amt' => $budgetAmount
            ]);

            set_flash('success', 'Monthly budget for ' . $monthNames[$month] . ' ' . $year . ' set to ' . format_currency($budgetAmount) . '!');
            redirect($baseUrl . '/budgets/view.php');
        }
    }
}

$pageTitle = 'Manage Budget';
$activePage = 'budgets_manage';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Set / Update Monthly Budget</h3>
                <p class="text-muted small mb-0">Define spending thresholds to prevent end-of-month debt</p>
            </div>
            <a href="<?= $baseUrl ?>/budgets/view.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Budget Overview
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= $baseUrl ?>/budgets/manage.php" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field(); ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="month" class="form-label fw-semibold small text-secondary">Target Month *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar-month"></i></span>
                                <select class="form-select" id="month" name="month" required>
                                    <?php foreach ($monthNames as $num => $name): ?>
                                        <option value="<?= $num ?>" <?= ($num === $month) ? 'selected' : '' ?>><?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="year" class="form-label fw-semibold small text-secondary">Target Year *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar-event"></i></span>
                                <input type="number" class="form-control" id="year" name="year" min="2020" max="2035" value="<?= htmlspecialchars((string)$year) ?>" required>
                                <div class="invalid-feedback">Enter a valid year.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="budget_amount" class="form-label fw-semibold small text-secondary">Planned Budget Amount (₹) *</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light text-primary fw-bold">₹</span>
                            <input type="number" step="0.01" min="1.00" class="form-control" id="budget_amount" name="budget_amount" value="<?= htmlspecialchars((string)$currentAmount) ?>" placeholder="e.g. 15000" required autofocus>
                            <div class="invalid-feedback">Please enter a valid positive budget amount.</div>
                        </div>
                        <div class="form-text small mt-2">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            An automated warning alert is triggered whenever your cumulative expenses reach <strong>80%</strong> of this limit.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= $baseUrl ?>/budgets/view.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Save Budget
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
