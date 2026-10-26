<?php
/**
 * Edit Expense Record
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

$expenseId = (int)($_GET['id'] ?? 0);
if ($expenseId <= 0) {
    set_flash('danger', 'Invalid expense record identifier.');
    redirect($baseUrl . '/expenses/view.php');
}

// Ensure record exists and belongs to the authenticated student
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE expense_id = :id AND student_id = :sid LIMIT 1");
$stmt->execute([':id' => $expenseId, ':sid' => $studentId]);
$expense = $stmt->fetch();

if (!$expense) {
    set_flash('danger', 'Expense record not found or unauthorized access attempt.');
    redirect($baseUrl . '/expenses/view.php');
}

$categories = get_categories($pdo, 'expense');

$formData = [
    'amount' => $expense['amount'],
    'expense_date' => $expense['expense_date'],
    'category_id' => $expense['category_id'],
    'payment_method' => $expense['payment_method'],
    'description' => $expense['description'] ?? ''
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

        if ($amount <= 0.00) {
            $error = 'Please enter a valid expense amount greater than zero.';
        } elseif (empty($expenseDate) || !strtotime($expenseDate)) {
            $error = 'Please select a valid expense date.';
        } elseif ($categoryId <= 0) {
            $error = 'Please select an expense category.';
        } else {
            $updateStmt = $pdo->prepare("
                UPDATE expenses 
                SET category_id = :cat_id, amount = :amt, expense_date = :exp_date, 
                    payment_method = :method, description = :desc
                WHERE expense_id = :id AND student_id = :sid
            ");
            $updateStmt->execute([
                ':cat_id' => $categoryId,
                ':amt' => $amount,
                ':exp_date' => $expenseDate,
                ':method' => $paymentMethod,
                ':desc' => $description ?: null,
                ':id' => $expenseId,
                ':sid' => $studentId
            ]);

            set_flash('success', 'Expense record updated successfully!');
            redirect($baseUrl . '/expenses/view.php');
        }
    }
}

$pageTitle = 'Edit Expense';
$activePage = 'expenses';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Edit Expense</h3>
                <p class="text-muted small mb-0">Modify transaction details for Expense #<?= $expenseId ?></p>
            </div>
            <a href="<?= $baseUrl ?>/expenses/view.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Cancel &amp; Back
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

                <form action="<?= $baseUrl ?>/expenses/edit.php?id=<?= $expenseId ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field(); ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label fw-semibold small text-secondary">Amount (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-danger fw-bold">₹</span>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?= htmlspecialchars((string)$formData['amount']) ?>" required autofocus>
                                <div class="invalid-feedback">Please enter a valid positive amount.</div>
                            </div>
                        </div>

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

                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold small text-secondary">Description / Remarks</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($formData['description']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= $baseUrl ?>/expenses/view.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-save me-1"></i> Update Expense
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
