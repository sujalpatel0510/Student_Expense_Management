<?php
/**
 * Add New Expense
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

$categories = get_categories($pdo, 'expense');

$formData = [
    'amount' => '',
    'expense_date' => date('Y-m-d'),
    'category_id' => $categories[0]['category_id'] ?? 1,
    'payment_method' => 'UPI / Online',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $amount = (float)($_POST['amount'] ?? 0);
        $expenseDate = trim($_POST['expense_date'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $paymentMethod = trim($_POST['payment_method'] ?? 'UPI / Online');
        $description = trim($_POST['description'] ?? '');

        $formData['amount'] = $_POST['amount'] ?? '';
        $formData['expense_date'] = $expenseDate;
        $formData['category_id'] = $categoryId;
        $formData['payment_method'] = $paymentMethod;
        $formData['description'] = $description;

        // Validation
        if ($amount <= 0.00) {
            $error = 'Please enter a valid expense amount greater than zero.';
        } elseif (empty($expenseDate) || !strtotime($expenseDate)) {
            $error = 'Please select a valid expense date.';
        } elseif ($categoryId <= 0) {
            $error = 'Please select an expense category.';
        } else {
            // Verify category validity
            $chkCat = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE category_id = :id AND category_type = 'expense'");
            $chkCat->execute([':id' => $categoryId]);
            if ($chkCat->fetchColumn() == 0) {
                $error = 'Invalid category selected.';
            } else {
                // Prepared statement insertion
                $stmt = $pdo->prepare("
                    INSERT INTO expenses (student_id, category_id, amount, expense_date, payment_method, description)
                    VALUES (:sid, :cat_id, :amt, :exp_date, :method, :desc)
                ");
                $stmt->execute([
                    ':sid' => $studentId,
                    ':cat_id' => $categoryId,
                    ':amt' => $amount,
                    ':exp_date' => $expenseDate,
                    ':method' => $paymentMethod,
                    ':desc' => $description ?: null
                ]);

                set_flash('success', 'Expense of ' . format_currency($amount) . ' recorded successfully!');
                redirect($baseUrl . '/expenses/view.php');
            }
        }
    }
}

$pageTitle = 'Record Expense';
$activePage = 'expenses_add';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Add New Expense</h3>
                <p class="text-muted small mb-0">Record a new payment or purchase in your tracker</p>
            </div>
            <a href="<?= $baseUrl ?>/expenses/view.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> View All Expenses
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

                <form action="<?= $baseUrl ?>/expenses/add.php" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field(); ?>

                    <div class="row g-3 mb-3">
                        <!-- Amount -->
                        <div class="col-md-6">
                            <label for="amount" class="form-label fw-semibold small text-secondary">Amount (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-danger fw-bold">₹</span>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?= htmlspecialchars((string)$formData['amount']) ?>" placeholder="0.00" required autofocus>
                                <div class="invalid-feedback">Please enter a valid positive amount.</div>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-6">
                            <label for="expense_date" class="form-label fw-semibold small text-secondary">Expense Date *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?= htmlspecialchars($formData['expense_date']) ?>" required>
                                <div class="invalid-feedback">Please select a valid date.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Category -->
                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-semibold small text-secondary">Category *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-tag"></i></span>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['category_id'] ?>" <?= ((int)$formData['category_id'] === (int)$cat['category_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label fw-semibold small text-secondary">Payment Method *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-credit-card"></i></span>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <?php 
                                    $methods = ['UPI / Online', 'Cash', 'Debit Card', 'Credit Card', 'Net Banking', 'Other'];
                                    foreach ($methods as $m): ?>
                                        <option value="<?= $m ?>" <?= ($formData['payment_method'] === $m) ? 'selected' : '' ?>><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold small text-secondary">Description / Remarks (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="e.g. Canteen lunch coupon, semester textbook purchase..."><?= htmlspecialchars($formData['description']) ?></textarea>
                        <div class="form-text small">Add context to help you review expenses in your monthly report.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= $baseUrl ?>/expenses/view.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-danger px-4 shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Save Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
