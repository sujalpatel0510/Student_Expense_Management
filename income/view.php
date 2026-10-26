<?php
/**
 * View Income - History, Search & Management
 * Student Expense Tracker
 */
$pageTitle = 'Manage Income';
$activePage = 'income_view';

require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();
$studentId = current_student_id();
$baseUrl = get_base_url();

$categories = get_categories($pdo, 'income');

// Filters
$catFilter = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : '';
$sourceFilter = trim($_GET['source'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');

$sql = "
    SELECT i.income_id, i.amount, i.income_date, i.source, i.description, i.created_at,
           c.category_name, c.icon, c.color
    FROM income i
    INNER JOIN categories c ON i.category_id = c.category_id
    WHERE i.student_id = :sid
";
$params = [':sid' => $studentId];

if ($catFilter !== '') {
    $sql .= " AND i.category_id = :cat_id";
    $params[':cat_id'] = $catFilter;
}

if (!empty($sourceFilter)) {
    $sql .= " AND i.source = :source";
    $params[':source'] = $sourceFilter;
}

if (!empty($fromDate)) {
    $sql .= " AND i.income_date >= :from_date";
    $params[':from_date'] = $fromDate;
}

if (!empty($toDate)) {
    $sql .= " AND i.income_date <= :to_date";
    $params[':to_date'] = $toDate;
}

if (!empty($keyword)) {
    $sql .= " AND (i.description LIKE :kw OR i.source LIKE :kw2 OR c.category_name LIKE :kw3)";
    $params[':kw'] = '%' . $keyword . '%';
    $params[':kw2'] = '%' . $keyword . '%';
    $params[':kw3'] = '%' . $keyword . '%';
}

$sql .= " ORDER BY i.income_date DESC, i.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$incomes = $stmt->fetchAll();

$totalFilteredIncome = 0.00;
$countFiltered = count($incomes);
foreach ($incomes as $item) {
    $totalFilteredIncome += (float)$item['amount'];
}
$avgFilteredIncome = $countFiltered > 0 ? ($totalFilteredIncome / $countFiltered) : 0.00;
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">Income Management</h3>
        <p class="text-muted small mb-0">Record and track allowances, scholarships, jobs, and financial inflows</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $baseUrl ?>/income/add.php" class="btn btn-success shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Income
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= $baseUrl ?>/income/view.php" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Search Keyword</label>
                <input type="text" name="keyword" class="form-control form-control-sm" placeholder="e.g. scholarship, father..." value="<?= htmlspecialchars($keyword) ?>">
            </div>

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

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Source Stream</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">All Sources</option>
                    <?php 
                    $sources = ['Pocket Money', 'Scholarship', 'Part-time Job', 'Freelancing', 'Gifts', 'Other Income'];
                    foreach ($sources as $s): ?>
                        <option value="<?= $s ?>" <?= ($sourceFilter === $s) ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate) ?>">
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Apply Filter">
                    <i class="bi bi-funnel"></i>
                </button>
                <a href="<?= $baseUrl ?>/income/view.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card p-3 border-0 bg-success bg-opacity-10 text-success rounded-3">
            <div class="small fw-semibold text-uppercase">Total Filtered Inflow</div>
            <div class="fs-4 fw-bold"><?= format_currency($totalFilteredIncome) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 border-0 bg-light rounded-3">
            <div class="small fw-semibold text-uppercase text-muted">Income Deposits</div>
            <div class="fs-4 fw-bold text-dark"><?= $countFiltered ?> Record(s)</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 border-0 bg-light rounded-3">
            <div class="small fw-semibold text-uppercase text-muted">Average Inflow</div>
            <div class="fs-4 fw-bold text-dark"><?= format_currency($avgFilteredIncome) ?></div>
        </div>
    </div>
</div>

<!-- Income Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold"><i class="bi bi-wallet2 text-success me-2"></i> Income Inflow History</h6>
        <span class="badge bg-light text-dark border"><?= $countFiltered ?> entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Source Stream</th>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center" style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($incomes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-cash-stack fs-1 d-block mb-2 text-secondary"></i>
                            <p class="mb-2">No income records found for this period.</p>
                            <a href="<?= $baseUrl ?>/income/add.php" class="btn btn-sm btn-outline-success">Record Income</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($incomes as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= format_date($row['income_date']) ?></div>
                                <div class="text-muted small"><?= date('h:i A', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td>
                                <span class="d-inline-flex align-items-center gap-1">
                                    <i class="bi <?= htmlspecialchars($row['icon'] ?: 'bi-tag') ?>" style="color: <?= htmlspecialchars($row['color'] ?: '#10b981') ?>;"></i>
                                    <strong><?= htmlspecialchars($row['category_name']) ?></strong>
                                </span>
                            </td>
                            <td><span class="badge bg-success bg-opacity-10 text-success border border-success"><?= htmlspecialchars($row['source']) ?></span></td>
                            <td><?= htmlspecialchars($row['description'] ?: '-') ?></td>
                            <td class="text-end fw-bold text-success fs-6">
                                +<?= format_currency($row['amount']) ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= $baseUrl ?>/income/edit.php?id=<?= $row['income_id'] ?>" class="btn btn-outline-primary" title="Edit Income">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="<?= $baseUrl ?>/income/delete.php?id=<?= $row['income_id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                       class="btn btn-outline-danger btn-confirm-delete" 
                                       data-item-name="income record of <?= format_currency($row['amount']) ?>"
                                       title="Delete Income">
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
