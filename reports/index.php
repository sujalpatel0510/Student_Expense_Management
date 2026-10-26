<?php
/**
 * Financial Reports & Transaction Statement
 * Student Expense Tracker - College DBMS Project
 */
$pageTitle = 'Transaction Reports & Statement';
$activePage = 'reports';

require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$studentId = current_student_id();
$student = current_student();
$baseUrl = get_base_url();

// Filter parameters
$transType = trim($_GET['type'] ?? 'all'); // 'all', 'expense', 'income'
$fromDate = trim($_GET['from_date'] ?? date('Y-m-01')); // default start of current month
$toDate = trim($_GET['to_date'] ?? date('Y-m-d'));       // default today

// 1. Fetch Consolidated Transaction Ledger using SQL UNION ALL
$ledgerSql = "";
$ledgerParams = [];

$expensePart = "
    SELECT 
        'expense' AS trans_type,
        e.expense_id AS trans_id,
        e.amount,
        e.expense_date AS trans_date,
        c.category_name,
        c.icon,
        c.color,
        e.payment_method AS method_or_source,
        e.description,
        e.created_at
    FROM expenses e
    INNER JOIN categories c ON e.category_id = c.category_id
    WHERE e.student_id = :sid_e
";

$incomePart = "
    SELECT 
        'income' AS trans_type,
        i.income_id AS trans_id,
        i.amount,
        i.income_date AS trans_date,
        c.category_name,
        c.icon,
        c.color,
        i.source AS method_or_source,
        i.description,
        i.created_at
    FROM income i
    INNER JOIN categories c ON i.category_id = c.category_id
    WHERE i.student_id = :sid_i
";

if (!empty($fromDate)) {
    $expensePart .= " AND e.expense_date >= :from_date_e";
    $incomePart .= " AND i.income_date >= :from_date_i";
}

if (!empty($toDate)) {
    $expensePart .= " AND e.expense_date <= :to_date_e";
    $incomePart .= " AND i.income_date <= :to_date_i";
}

if ($transType === 'expense') {
    $ledgerSql = $expensePart . " ORDER BY trans_date DESC, created_at DESC";
    $ledgerParams[':sid_e'] = $studentId;
    if (!empty($fromDate)) $ledgerParams[':from_date_e'] = $fromDate;
    if (!empty($toDate)) $ledgerParams[':to_date_e'] = $toDate;
} elseif ($transType === 'income') {
    $ledgerSql = $incomePart . " ORDER BY trans_date DESC, created_at DESC";
    $ledgerParams[':sid_i'] = $studentId;
    if (!empty($fromDate)) $ledgerParams[':from_date_i'] = $fromDate;
    if (!empty($toDate)) $ledgerParams[':to_date_i'] = $toDate;
} else {
    $ledgerSql = "(" . $expensePart . ") UNION ALL (" . $incomePart . ") ORDER BY trans_date DESC, created_at DESC";
    $ledgerParams[':sid_e'] = $studentId;
    $ledgerParams[':sid_i'] = $studentId;
    if (!empty($fromDate)) {
        $ledgerParams[':from_date_e'] = $fromDate;
        $ledgerParams[':from_date_i'] = $fromDate;
    }
    if (!empty($toDate)) {
        $ledgerParams[':to_date_e'] = $toDate;
        $ledgerParams[':to_date_i'] = $toDate;
    }
}

$stmt = $pdo->prepare($ledgerSql);
$stmt->execute($ledgerParams);
$transactions = $stmt->fetchAll();

// 2. Compute Filtered Aggregations (Total Inflow, Total Outflow, Net Savings)
$periodIncome = 0.00;
$periodExpense = 0.00;
foreach ($transactions as $tx) {
    if ($tx['trans_type'] === 'income') {
        $periodIncome += (float)$tx['amount'];
    } else {
        $periodExpense += (float)$tx['amount'];
    }
}
$periodSavings = $periodIncome - $periodExpense;

// 3. Category-wise Breakdown within selected range
$catSql = "
    SELECT c.category_name, c.color, SUM(e.amount) as cat_total, COUNT(e.expense_id) as cat_count
    FROM expenses e
    INNER JOIN categories c ON e.category_id = c.category_id
    WHERE e.student_id = :sid
";
$catParams = [':sid' => $studentId];
if (!empty($fromDate)) {
    $catSql .= " AND e.expense_date >= :from_d";
    $catParams[':from_d'] = $fromDate;
}
if (!empty($toDate)) {
    $catSql .= " AND e.expense_date <= :to_d";
    $catParams[':to_d'] = $toDate;
}
$catSql .= " GROUP BY c.category_id, c.category_name, c.color ORDER BY cat_total DESC";
$catStmt = $pdo->prepare($catSql);
$catStmt->execute($catParams);
$categorySummary = $catStmt->fetchAll();
?>

<!-- College Report Header (Visible only in Print mode) -->
<div class="print-header mb-4">
    <div class="text-center">
        <h3 class="fw-bold mb-1">STUDENT EXPENSE TRACKER - FINANCIAL STATEMENT</h3>
        <p class="text-muted mb-1">Database Management System (DBMS) Project Demonstration</p>
        <div class="small">
            <strong>Student:</strong> <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['course']) ?>) &bull; 
            <strong>Email:</strong> <?= htmlspecialchars($student['email']) ?>
        </div>
        <div class="small text-muted">
            Statement Period: <strong><?= format_date($fromDate) ?></strong> to <strong><?= format_date($toDate) ?></strong> | Generated on: <?= date('d M, Y h:i A') ?>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 no-print">
    <div>
        <h3 class="fw-bold mb-1">Transaction Statement &amp; Reports</h3>
        <p class="text-muted small mb-0">Generate financial summaries, export CSV data, or print college reports</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-dark shadow-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <a href="<?= $baseUrl ?>/reports/export_csv.php?type=<?= urlencode($transType) ?>&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>" class="btn btn-success shadow-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export to CSV
        </a>
    </div>
</div>

<!-- Filter Toolbar (Hidden on Print) -->
<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body p-3">
        <form method="GET" action="<?= $baseUrl ?>/reports/index.php" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Transaction Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="all" <?= ($transType === 'all') ? 'selected' : '' ?>>All (Income + Expense)</option>
                    <option value="expense" <?= ($transType === 'expense') ? 'selected' : '' ?>>Expenses Only</option>
                    <option value="income" <?= ($transType === 'income') ? 'selected' : '' ?>>Income Only</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Date From</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Date To</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate) ?>">
            </div>

            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-filter me-1"></i> Apply Filter
                </button>
                <a href="<?= $baseUrl ?>/reports/index.php" class="btn btn-sm btn-outline-secondary" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Financial Summary KPI Badges -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card p-3 border-0 bg-success bg-opacity-10 text-success rounded-3">
            <div class="small fw-semibold text-uppercase">Total Income (Inflow)</div>
            <div class="fs-4 fw-bold">+<?= format_currency($periodIncome) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 border-0 bg-danger bg-opacity-10 text-danger rounded-3">
            <div class="small fw-semibold text-uppercase">Total Expenses (Outflow)</div>
            <div class="fs-4 fw-bold">-<?= format_currency($periodExpense) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 border-0 bg-primary bg-opacity-10 text-primary rounded-3">
            <div class="small fw-semibold text-uppercase">Net Balance / Savings</div>
            <div class="fs-4 fw-bold <?= ($periodSavings >= 0) ? 'text-primary' : 'text-danger' ?>">
                <?= format_currency($periodSavings) ?>
            </div>
        </div>
    </div>
</div>

<!-- Category-wise Expense Breakdown Table -->
<?php if (!empty($categorySummary) && $transType !== 'income'): ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-primary me-2"></i> Category-wise Expense Summary for Selected Period</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-center">Transactions</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-end">% of Total Expense</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorySummary as $cs): 
                    $pct = $periodExpense > 0 ? round(((float)$cs['cat_total'] / $periodExpense) * 100, 1) : 0;
                ?>
                    <tr>
                        <td>
                            <span class="fw-semibold" style="color: <?= htmlspecialchars($cs['color'] ?: '#333') ?>;">
                                &bull; <?= htmlspecialchars($cs['category_name']) ?>
                            </span>
                        </td>
                        <td class="text-center"><?= $cs['cat_count'] ?></td>
                        <td class="text-end fw-bold text-danger"><?= format_currency($cs['cat_total']) ?></td>
                        <td class="text-end fw-semibold text-muted"><?= $pct ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Transaction Statement Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-journal-text text-primary me-2"></i> Chronological Transaction Ledger</h6>
        <span class="badge bg-light text-dark border"><?= count($transactions) ?> Total Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Method / Source</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard-x fs-1 d-block mb-2 text-secondary"></i>
                            No transactions found for the selected date range.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?= format_date($tx['trans_date']) ?></td>
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
                            <td>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="bi <?= htmlspecialchars($tx['icon'] ?: 'bi-tag') ?>" style="color: <?= htmlspecialchars($tx['color'] ?: '#4f46e5') ?>;"></i>
                                    <strong><?= htmlspecialchars($tx['category_name']) ?></strong>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
