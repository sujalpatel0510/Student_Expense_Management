<?php
/**
 * Main Student Dashboard
 * Student Expense Tracker - College DBMS Project
 */
$pageTitle = 'Dashboard Overview';
$activePage = 'dashboard';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$studentId = current_student_id();

// Selected month and year filter (defaulting to current month/year)
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Clamp month and year
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = (int)date('n');
if ($selectedYear < 2020 || $selectedYear > 2035) $selectedYear = (int)date('Y');

// 1. Fetch Monthly Summary & Budget Status
$budgetStats = get_student_budget_status($pdo, $studentId, $selectedMonth, $selectedYear);

// 2. Fetch Category Breakdown for Pie Chart
$catStmt = $pdo->prepare("
    SELECT c.category_name, c.color, SUM(e.amount) as total_cat
    FROM expenses e
    INNER JOIN categories c ON e.category_id = c.category_id
    WHERE e.student_id = :sid AND MONTH(e.expense_date) = :m AND YEAR(e.expense_date) = :y
    GROUP BY c.category_id, c.category_name, c.color
    ORDER BY total_cat DESC
");
$catStmt->execute([':sid' => $studentId, ':m' => $selectedMonth, ':y' => $selectedYear]);
$categoryBreakdown = $catStmt->fetchAll();

$chartCatLabels = [];
$chartCatValues = [];
$chartCatColors = [];
foreach ($categoryBreakdown as $cat) {
    $chartCatLabels[] = $cat['category_name'];
    $chartCatValues[] = (float)$cat['total_cat'];
    $chartCatColors[] = $cat['color'] ?: '#4f46e5';
}

// 3. Fetch 6-Month Income vs Expense Trend for Bar Chart
$trendLabels = [];
$trendIncome = [];
$trendExpense = [];

for ($i = 5; $i >= 0; $i--) {
    $targetTimestamp = strtotime("-$i months", strtotime("$selectedYear-$selectedMonth-01"));
    $tMonth = (int)date('n', $targetTimestamp);
    $tYear = (int)date('Y', $targetTimestamp);
    $tLabel = date('M Y', $targetTimestamp);

    // Sum expenses for this month
    $tExpStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0.00) 
        FROM expenses 
        WHERE student_id = :sid AND MONTH(expense_date) = :m AND YEAR(expense_date) = :y
    ");
    $tExpStmt->execute([':sid' => $studentId, ':m' => $tMonth, ':y' => $tYear]);
    $mExp = (float)$tExpStmt->fetchColumn();

    // Sum income for this month
    $tIncStmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0.00) 
        FROM income 
        WHERE student_id = :sid AND MONTH(income_date) = :m AND YEAR(income_date) = :y
    ");
    $tIncStmt->execute([':sid' => $studentId, ':m' => $tMonth, ':y' => $tYear]);
    $mInc = (float)$tIncStmt->fetchColumn();

    $trendLabels[] = $tLabel;
    $trendExpense[] = $mExp;
    $trendIncome[] = $mInc;
}

// 4. Fetch Recent Transactions (Combined Expense & Income)
$recentStmt = $pdo->prepare("
    (SELECT 'expense' AS trans_type, e.expense_id AS id, e.amount, e.expense_date AS trans_date, 
            c.category_name, c.icon, c.color, e.payment_method AS method_or_source, e.description, e.created_at
     FROM expenses e
     INNER JOIN categories c ON e.category_id = c.category_id
     WHERE e.student_id = :sid1)
    UNION ALL
    (SELECT 'income' AS trans_type, i.income_id AS id, i.amount, i.income_date AS trans_date, 
            c.category_name, c.icon, c.color, i.source AS method_or_source, i.description, i.created_at
     FROM income i
     INNER JOIN categories c ON i.category_id = c.category_id
     WHERE i.student_id = :sid2)
    ORDER BY trans_date DESC, created_at DESC
    LIMIT 7
");
$recentStmt->execute([':sid1' => $studentId, ':sid2' => $studentId]);
$recentTransactions = $recentStmt->fetchAll();

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<!-- Month & Year Filter Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Financial Overview</h3>
        <p class="text-muted small mb-0">Showing records for <strong><?= $monthNames[$selectedMonth] ?> <?= $selectedYear ?></strong></p>
    </div>

    <!-- Quick Month Switcher Form -->
    <form action="<?= $baseUrl ?>/dashboard.php" method="GET" class="d-flex align-items-center gap-2">
        <select name="month" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
            <?php foreach ($monthNames as $num => $name): ?>
                <option value="<?= $num ?>" <?= ($num === $selectedMonth) ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <select name="year" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
            <?php for ($y = date('Y') + 1; $y >= 2024; $y--): ?>
                <option value="<?= $y ?>" <?= ($y === $selectedYear) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <noscript><button type="submit" class="btn btn-sm btn-primary">Filter</button></noscript>
    </form>
</div>

<!-- Dynamic Budget Warning / Alert Banner -->
<?php if ($budgetStats['status'] === 'EXCEEDED'): ?>
    <div class="alert alert-danger shadow-sm border-danger d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-octagon-fill fs-3 text-danger"></i>
            <div>
                <strong class="d-block">Budget Exceeded Alert!</strong>
                <span>You have exceeded your monthly budget of <strong><?= format_currency($budgetStats['budget']) ?></strong> by <strong><?= format_currency(abs($budgetStats['remaining'])) ?></strong> (<?= $budgetStats['percentage'] ?>% spent).</span>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/budgets/manage.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>" class="btn btn-sm btn-danger">Adjust Budget</a>
    </div>
<?php elseif ($budgetStats['status'] === 'WARNING'): ?>
    <div class="alert alert-warning shadow-sm border-warning d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
            <div>
                <strong class="d-block">80% Budget Threshold Warning</strong>
                <span>Caution: You have utilized <strong><?= $budgetStats['percentage'] ?>%</strong> of your allocated monthly budget. Only <strong><?= format_currency($budgetStats['remaining']) ?></strong> remaining.</span>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/expenses/view.php" class="btn btn-sm btn-warning">Review Expenses</a>
    </div>
<?php elseif ($budgetStats['status'] === 'NO_BUDGET'): ?>
    <div class="alert alert-info shadow-sm border-info d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill fs-3 text-info"></i>
            <div>
                <strong class="d-block">No Budget Set for <?= $monthNames[$selectedMonth] ?></strong>
                <span>Setting a monthly budget will help you control expenditure and avoid overspending.</span>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/budgets/manage.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>" class="btn btn-sm btn-info text-white">Set Budget Now</a>
    </div>
<?php endif; ?>

<!-- 4 Summary KPI Cards -->
<div class="row g-3 mb-4">
    <!-- Total Income -->
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card kpi-income h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-title">Total Income</div>
                    <div class="kpi-value text-success"><?= format_currency($budgetStats['income']) ?></div>
                    <div class="small text-muted mt-1"><i class="bi bi-calendar-event me-1"></i> <?= $monthNames[$selectedMonth] ?></div>
                </div>
                <div class="kpi-icon-wrapper">
                    <i class="bi bi-arrow-down-left"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Expenses -->
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card kpi-expense h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-title">Total Expenses</div>
                    <div class="kpi-value text-danger"><?= format_currency($budgetStats['expense']) ?></div>
                    <div class="small text-muted mt-1"><i class="bi bi-receipt me-1"></i> Recorded Outflow</div>
                </div>
                <div class="kpi-icon-wrapper">
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Remaining Budget -->
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card kpi-budget h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-title">Remaining Budget</div>
                    <div class="kpi-value <?= ($budgetStats['remaining'] < 0) ? 'text-danger' : 'text-primary' ?>">
                        <?= ($budgetStats['budget'] > 0) ? format_currency($budgetStats['remaining']) : 'Not Set' ?>
                    </div>
                    <div class="small text-muted mt-1">
                        <?= ($budgetStats['budget'] > 0) ? ($budgetStats['percentage'] . '% spent of ' . format_currency($budgetStats['budget'])) : 'No limit set' ?>
                    </div>
                </div>
                <div class="kpi-icon-wrapper">
                    <i class="bi bi-pie-chart"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Savings -->
    <div class="col-sm-6 col-xl-3">
        <div class="card kpi-card kpi-savings h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="kpi-title">Net Savings</div>
                    <div class="kpi-value <?= ($budgetStats['savings'] >= 0) ? 'text-info' : 'text-danger' ?>">
                        <?= format_currency($budgetStats['savings']) ?>
                    </div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-piggy-bank me-1"></i> <?= ($budgetStats['savings'] >= 0) ? 'Positive Savings' : 'Deficit' ?>
                    </div>
                </div>
                <div class="kpi-icon-wrapper">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Budget Progress Bar -->
<?php if ($budgetStats['budget'] > 0): ?>
<div class="card p-3 mb-4 shadow-sm border-0">
    <div class="d-flex justify-content-between align-items-center mb-2 small">
        <span class="fw-bold text-secondary">
            <i class="bi bi-speedometer2 me-1"></i> Monthly Budget Utilization (<?= $budgetStats['percentage'] ?>%)
        </span>
        <span class="text-muted">
            Spent <strong><?= format_currency($budgetStats['expense']) ?></strong> of <strong><?= format_currency($budgetStats['budget']) ?></strong>
        </span>
    </div>
    <div class="budget-progress-bar">
        <?php 
            $fillClass = 'progress-safe';
            if ($budgetStats['percentage'] > 100) $fillClass = 'progress-danger';
            elseif ($budgetStats['percentage'] >= 80) $fillClass = 'progress-warning';
            $barWidth = min(100, $budgetStats['percentage']);
        ?>
        <div class="budget-progress-fill <?= $fillClass ?>" style="width: <?= $barWidth ?>%;"></div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions Toolbar -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="<?= $baseUrl ?>/expenses/add.php" class="btn btn-danger shadow-sm">
        <i class="bi bi-plus-circle me-1"></i> Add Expense
    </a>
    <a href="<?= $baseUrl ?>/income/add.php" class="btn btn-success shadow-sm">
        <i class="bi bi-plus-circle me-1"></i> Add Income
    </a>
    <a href="<?= $baseUrl ?>/budgets/manage.php?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>" class="btn btn-primary shadow-sm">
        <i class="bi bi-sliders me-1"></i> Set / Update Budget
    </a>
    <a href="<?= $baseUrl ?>/reports/index.php" class="btn btn-outline-secondary ms-auto shadow-sm">
        <i class="bi bi-file-earmark-bar-graph me-1"></i> Full Statement
    </a>
</div>

<!-- Visual Charts Section (Chart.js) -->
<div class="row g-4 mb-4">
    <!-- Expense Category Breakdown -->
    <div class="col-lg-5">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart-fill text-primary me-2"></i> Category Breakdown</h6>
                <span class="badge bg-light text-secondary border"><?= $monthNames[$selectedMonth] ?></span>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <?php if (empty($categoryBreakdown)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                        <p class="mb-2">No expenses recorded for this month.</p>
                        <a href="<?= $baseUrl ?>/expenses/add.php" class="btn btn-sm btn-outline-danger">Record First Expense</a>
                    </div>
                <?php else: ?>
                    <div style="height: 260px; position: relative;">
                        <canvas id="categoryPieChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 6-Month Income vs Expense Trend -->
    <div class="col-lg-7">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart-fill text-primary me-2"></i> Income vs Expense Trend (Last 6 Months)</h6>
                <span class="badge bg-light text-secondary border">Dynamic Analytics</span>
            </div>
            <div class="card-body">
                <div style="height: 260px; position: relative;">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i> Recent Transactions</h6>
        <div class="d-flex gap-2">
            <a href="<?= $baseUrl ?>/expenses/view.php" class="btn btn-xs btn-outline-danger py-1 px-2 small">View Expenses</a>
            <a href="<?= $baseUrl ?>/income/view.php" class="btn btn-xs btn-outline-success py-1 px-2 small">View Income</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Method / Source</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-clipboard-x fs-2 d-block mb-1"></i>
                            No recent transactions found. Start by adding an expense or income!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentTransactions as $tx): ?>
                        <tr>
                            <td>
                                <?php if ($tx['trans_type'] === 'expense'): ?>
                                    <span class="badge badge-category badge-expense">
                                        <i class="bi bi-arrow-up-right"></i> Expense
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-category badge-income">
                                        <i class="bi bi-arrow-down-left"></i> Income
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= format_date($tx['trans_date']) ?></td>
                            <td>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="bi <?= htmlspecialchars($tx['icon'] ?: 'bi-tag') ?>" style="color: <?= htmlspecialchars($tx['color'] ?: '#4f46e5') ?>;"></i>
                                    <?= htmlspecialchars($tx['category_name']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($tx['description'] ?: '-') ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($tx['method_or_source']) ?></span></td>
                            <td class="text-end fw-bold <?= ($tx['trans_type'] === 'expense') ? 'text-danger' : 'text-success' ?>">
                                <?= ($tx['trans_type'] === 'expense') ? '-' : '+' ?><?= format_currency($tx['amount']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoryData = {
        labels: <?= json_encode($chartCatLabels) ?>,
        values: <?= json_encode($chartCatValues) ?>,
        colors: <?= json_encode($chartCatColors) ?>
    };

    const monthlyTrendData = {
        labels: <?= json_encode($trendLabels) ?>,
        income: <?= json_encode($trendIncome) ?>,
        expense: <?= json_encode($trendExpense) ?>
    };

    initDashboardCharts(categoryData, monthlyTrendData);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
