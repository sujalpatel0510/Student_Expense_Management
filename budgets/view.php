<?php
/**
 * Monthly Budgets Overview & Performance Tracking
 * Student Expense Tracker
 */
$pageTitle = 'Monthly Budgets';
$activePage = 'budgets';

require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$studentId = current_student_id();
$baseUrl = get_base_url();

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Current month's live status
$currM = (int)date('n');
$currY = (int)date('Y');
$currentMonthStatus = get_student_budget_status($pdo, $studentId, $currM, $currY);

// Fetch all recorded budgets for this student with subquery for total monthly expenses
$stmt = $pdo->prepare("
    SELECT 
        b.budget_id,
        b.month,
        b.year,
        b.budget_amount,
        COALESCE(e.total_expense, 0.00) AS total_spent,
        COALESCE(i.total_income, 0.00) AS total_income
    FROM budgets b
    LEFT JOIN (
        SELECT student_id, MONTH(expense_date) AS m, YEAR(expense_date) AS y, SUM(amount) AS total_expense
        FROM expenses
        WHERE student_id = :sid1
        GROUP BY student_id, MONTH(expense_date), YEAR(expense_date)
    ) e ON b.student_id = e.student_id AND b.month = e.m AND b.year = e.y
    LEFT JOIN (
        SELECT student_id, MONTH(income_date) AS m, YEAR(income_date) AS y, SUM(amount) AS total_income
        FROM income
        WHERE student_id = :sid2
        GROUP BY student_id, MONTH(income_date), YEAR(income_date)
    ) i ON b.student_id = i.student_id AND b.month = i.m AND b.year = i.y
    WHERE b.student_id = :sid3
    ORDER BY b.year DESC, b.month DESC
");
$stmt->execute([':sid1' => $studentId, ':sid2' => $studentId, ':sid3' => $studentId]);
$budgetList = $stmt->fetchAll();
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Monthly Budget Management</h3>
        <p class="text-muted small mb-0">Track spending caps, utilization percentages, and threshold alerts</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $baseUrl ?>/budgets/manage.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Set New Month Budget
        </a>
    </div>
</div>

<!-- Current Month Quick Status Spotlight -->
<div class="card shadow-sm border-0 mb-4 p-4 rounded-4 bg-white">
    <div class="row align-items-center g-4">
        <div class="col-lg-4 border-end">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold mb-2">Current Active Month</span>
            <h4 class="fw-bold text-dark mb-1"><?= $monthNames[$currM] ?> <?= $currY ?></h4>
            <div class="text-muted small">
                Target Budget: <strong class="text-dark"><?= format_currency($currentMonthStatus['budget']) ?></strong>
            </div>
            <div class="mt-3">
                <a href="<?= $baseUrl ?>/budgets/manage.php?month=<?= $currM ?>&year=<?= $currY ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i> Edit This Month's Budget
                </a>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row g-3 mb-3 text-center text-sm-start">
                <div class="col-sm-4">
                    <div class="text-muted small text-uppercase fw-semibold">Amount Spent</div>
                    <div class="fs-4 fw-bold text-danger"><?= format_currency($currentMonthStatus['expense']) ?></div>
                </div>
                <div class="col-sm-4">
                    <div class="text-muted small text-uppercase fw-semibold">Remaining Limit</div>
                    <div class="fs-4 fw-bold <?= ($currentMonthStatus['remaining'] < 0) ? 'text-danger' : 'text-success' ?>">
                        <?= format_currency($currentMonthStatus['remaining']) ?>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="text-muted small text-uppercase fw-semibold">Budget Used</div>
                    <div class="fs-4 fw-bold text-dark"><?= $currentMonthStatus['percentage'] ?>%</div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div>
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Utilization Progress</span>
                    <span class="fw-semibold"><?= $currentMonthStatus['percentage'] ?>% of <?= format_currency($currentMonthStatus['budget']) ?></span>
                </div>
                <div class="budget-progress-bar">
                    <?php 
                        $fillClass = 'progress-safe';
                        if ($currentMonthStatus['percentage'] > 100) $fillClass = 'progress-danger';
                        elseif ($currentMonthStatus['percentage'] >= 80) $fillClass = 'progress-warning';
                    ?>
                    <div class="budget-progress-fill <?= $fillClass ?>" style="width: <?= min(100, $currentMonthStatus['percentage']) ?>%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Historical Budgets Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-table text-primary me-2"></i> All Recorded Monthly Budgets</h6>
        <span class="badge bg-light text-dark border"><?= count($budgetList) ?> records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Month &amp; Year</th>
                    <th class="text-end">Budget Amount</th>
                    <th class="text-end">Total Spent</th>
                    <th class="text-end">Remaining</th>
                    <th>Utilization</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($budgetList)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                            <p class="mb-2">No monthly budgets found.</p>
                            <a href="<?= $baseUrl ?>/budgets/manage.php" class="btn btn-sm btn-primary">Create Your First Budget</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($budgetList as $b): 
                        $bAmt = (float)$b['budget_amount'];
                        $bSpent = (float)$b['total_spent'];
                        $bRem = $bAmt - $bSpent;
                        $bPct = $bAmt > 0 ? round(($bSpent / $bAmt) * 100, 1) : 0;
                        
                        $badgeClass = 'bg-success';
                        $statusText = 'Under Budget (Safe)';
                        if ($bSpent > $bAmt) {
                            $badgeClass = 'bg-danger';
                            $statusText = 'Exceeded Limit';
                        } elseif ($bPct >= 80.0) {
                            $badgeClass = 'bg-warning text-dark';
                            $statusText = 'Warning (≥80%)';
                        }
                    ?>
                        <tr>
                            <td class="fw-bold">
                                <?= $monthNames[(int)$b['month']] ?> <?= $b['year'] ?>
                                <?php if ((int)$b['month'] === $currM && (int)$b['year'] === $currY): ?>
                                    <span class="badge bg-primary ms-1" style="font-size: 0.7rem;">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold text-primary"><?= format_currency($bAmt) ?></td>
                            <td class="text-end fw-semibold text-danger"><?= format_currency($bSpent) ?></td>
                            <td class="text-end fw-bold <?= ($bRem < 0) ? 'text-danger' : 'text-success' ?>">
                                <?= format_currency($bRem) ?>
                            </td>
                            <td style="min-width: 140px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar <?= ($bPct > 100) ? 'bg-danger' : (($bPct >= 80) ? 'bg-warning' : 'bg-success') ?>" 
                                             style="width: <?= min(100, $bPct) ?>%;"></div>
                                    </div>
                                    <span class="small text-muted" style="min-width: 40px;"><?= $bPct ?>%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badgeClass ?> px-2 py-1 small"><?= $statusText ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= $baseUrl ?>/budgets/manage.php?month=<?= $b['month'] ?>&year=<?= $b['year'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Adjust Budget">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
