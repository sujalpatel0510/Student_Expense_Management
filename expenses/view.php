<?php
/**
 * View Expenses - Filtering, Search & CRUD Management
 * Student Expense Tracker
 */
$pageTitle = 'Manage Expenses';
$activePage = 'expenses_view';

require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$studentId = current_student_id();
$baseUrl = get_base_url();

// Expense categories for dropdown filter
$categories = get_categories($pdo, 'expense');

// Filter parameters
$catFilter = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : '';
$methodFilter = trim($_GET['payment_method'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');
$minAmount = isset($_GET['min_amount']) && $_GET['min_amount'] !== '' ? (float)$_GET['min_amount'] : '';
$maxAmount = isset($_GET['max_amount']) && $_GET['max_amount'] !== '' ? (float)$_GET['max_amount'] : '';

// Build dynamic SQL query with prepared statements
$sql = "
    SELECT e.expense_id, e.amount, e.expense_date, e.payment_method, e.description, e.created_at,
           c.category_name, c.icon, c.color
    FROM expenses e
    INNER JOIN categories c ON e.category_id = c.category_id
    WHERE e.student_id = :sid
";
$params = [':sid' => $studentId];

if ($catFilter !== '') {
    $sql .= " AND e.category_id = :cat_id";
    $params[':cat_id'] = $catFilter;
}

if (!empty($methodFilter)) {
    $sql .= " AND e.payment_method = :method";
    $params[':method'] = $methodFilter;
}

if (!empty($fromDate)) {
    $sql .= " AND e.expense_date >= :from_date";
    $params[':from_date'] = $fromDate;
}

if (!empty($toDate)) {
    $sql .= " AND e.expense_date <= :to_date";
    $params[':to_date'] = $toDate;
}

if (!empty($keyword)) {
    $sql .= " AND (e.description LIKE :kw OR c.category_name LIKE :kw2)";
    $params[':kw'] = '%' . $keyword . '%';
    $params[':kw2'] = '%' . $keyword . '%';
}

if ($minAmount !== '') {
    $sql .= " AND e.amount >= :min_amt";
    $params[':min_amt'] = $minAmount;
}

if ($maxAmount !== '') {
    $sql .= " AND e.amount <= :max_amt";
    $params[':max_amt'] = $maxAmount;
}

$sql .= " ORDER BY e.expense_date DESC, e.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Calculate aggregate statistics for filtered set
$totalFilteredExpense = 0.00;
$countFiltered = count($expenses);
foreach ($expenses as $item) {
    $totalFilteredExpense += (float)$item['amount'];
}
$avgFilteredExpense = $countFiltered > 0 ? ($totalFilteredExpense / $countFiltered) : 0.00;
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Expense Management</h3>
        <p class="text-muted small mb-0">Record, search, edit, and organize all your college expenditures</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $baseUrl ?>/expenses/add.php" class="btn btn-danger shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Expense
        </a>
    </div>
</div>

<!-- Filter & Search Accordion / Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= $baseUrl ?>/expenses/view.php" class="row g-2 align-items-end">
            <!-- Keyword search -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Search Description</label>
                <input type="text" name="keyword" class="form-control form-control-sm" placeholder="e.g. books, snacks..." value="<?= htmlspecialchars($keyword) ?>">
            </div>

            <!-- Category filter -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= ($catFilter === (int)$cat['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Payment method filter -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Payment Method</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">All Methods</option>
                    <?php 
                    $methods = ['UPI / Online', 'Cash', 'Debit Card', 'Credit Card', 'Net Banking', 'Other'];
                    foreach ($methods as $m): ?>
                        <option value="<?= $m ?>" <?= ($methodFilter === $m) ? 'selected' : '' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- From Date -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate) ?>">
            </div>

            <!-- To Date -->
            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate) ?>">
            </div>

            <!-- Actions -->
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Apply Filter">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="<?= $baseUrl ?>/expenses/view.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Row for Filtered Expenses -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card p-3 border-0 bg-danger bg-opacity-10 text-danger rounded-3">
            <div class="small fw-semibold text-uppercase">Total Filtered Spend</div>
            <div class="fs-4 fw-bold"><?= format_currency($totalFilteredExpense) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 border-0 bg-light rounded-3">
            <div class="small fw-semibold text-uppercase text-muted">Transactions Found</div>
            <div class="fs-4 fw-bold text-dark"><?= $countFiltered ?> Record(s)</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 border-0 bg-light rounded-3">
            <div class="small fw-semibold text-uppercase text-muted">Average Spend / Record</div>
            <div class="fs-4 fw-bold text-dark"><?= format_currency($avgFilteredExpense) ?></div>
        </div>
    </div>
</div>

<!-- Expenses Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul text-danger me-2"></i> Expense Records</h6>
        <span class="badge bg-light text-dark border"><?= $countFiltered ?> entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Payment Method</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center" style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt-cutoff fs-1 d-block mb-2 text-secondary"></i>
                            <p class="mb-2">No expense records match your search criteria.</p>
                            <a href="<?= $baseUrl ?>/expenses/add.php" class="btn btn-sm btn-outline-danger">Add Expense</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= format_date($row['expense_date']) ?></div>
                                <div class="text-muted small"><?= date('h:i A', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="bi <?= htmlspecialchars($row['icon'] ?: 'bi-tag') ?>" style="color: <?= htmlspecialchars($row['color'] ?: '#ef4444') ?>;"></i>
                                    <strong><?= htmlspecialchars($row['category_name']) ?></strong>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['description'] ?: '-') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-credit-card me-1"></i><?= htmlspecialchars($row['payment_method']) ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold text-danger fs-6">
                                -<?= format_currency($row['amount']) ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= $baseUrl ?>/expenses/edit.php?id=<?= $row['expense_id'] ?>" class="btn btn-outline-primary" title="Edit Expense">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="<?= $baseUrl ?>/expenses/delete.php?id=<?= $row['expense_id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-item-name="expense record of <?= format_currency($row['amount']) ?>"
                                       title="Delete Expense">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
