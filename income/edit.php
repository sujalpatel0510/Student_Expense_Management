<?php
/**
 * Edit Income Record
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

$incomeId = (int)($_GET['id'] ?? 0);
if ($incomeId <= 0) {
    set_flash('danger', 'Invalid income record identifier.');
    redirect($baseUrl . '/income/view.php');
}

$stmt = $pdo->prepare("SELECT * FROM income WHERE income_id = :id AND student_id = :sid LIMIT 1");
$stmt->execute([':id' => $incomeId, ':sid' => $studentId]);
$income = $stmt->fetch();

if (!$income) {
    set_flash('danger', 'Income record not found or access denied.');
    redirect($baseUrl . '/income/view.php');
}

$categories = get_categories($pdo, 'income');

$formData = [
    'amount' => $income['amount'],
    'income_date' => $income['income_date'],
    'category_id' => $income['category_id'],
    'source' => $income['source'],
    'description' => $income['description'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $amount = (float)($_POST['amount'] ?? 0);
        $incomeDate = trim($_POST['income_date'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $source = trim($_POST['source'] ?? 'Pocket Money');
        $description = trim($_POST['description'] ?? '');

        $formData['amount'] = $_POST['amount'] ?? '';
        $formData['income_date'] = $incomeDate;
        $formData['category_id'] = $categoryId;
        $formData['source'] = $source;
        $formData['description'] = $description;

        if ($amount <= 0.00) {
            $error = 'Please enter a valid positive income amount.';
        } elseif (empty($incomeDate) || !strtotime($incomeDate)) {
            $error = 'Please select a valid income receipt date.';
        } elseif ($categoryId <= 0) {
            $error = 'Please select an income category.';
        } else {
            $updateStmt = $pdo->prepare("
                UPDATE income 
                SET category_id = :cat_id, amount = :amt, income_date = :inc_date, 
                    source = :source, description = :desc
                WHERE income_id = :id AND student_id = :sid
            ");
            $updateStmt->execute([
                ':cat_id' => $categoryId,
                ':amt' => $amount,
                ':inc_date' => $incomeDate,
                ':source' => $source,
                ':desc' => $description ?: null,
                ':id' => $incomeId,
                ':sid' => $studentId
            ]);

            set_flash('success', 'Income record updated successfully!');
            redirect($baseUrl . '/income/view.php');
        }
    }
}

$pageTitle = 'Edit Income';
$activePage = 'income';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1">Edit Income Record</h3>
                <p class="text-muted small mb-0">Modify income receipt details for Income #<?= $incomeId ?></p>
            </div>
            <a href="<?= $baseUrl ?>/income/view.php" class="btn btn-outline-secondary btn-sm">
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

                <form action="<?= $baseUrl ?>/income/edit.php?id=<?= $incomeId ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field(); ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label fw-semibold small text-secondary">Amount (₹) *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-success fw-bold">₹</span>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?= htmlspecialchars((string)$formData['amount']) ?>" required autofocus>
                                <div class="invalid-feedback">Please enter a valid positive amount.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="income_date" class="form-label fw-semibold small text-secondary">Date Received *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                                <input type="date" class="form-control" id="income_date" name="income_date" value="<?= htmlspecialchars($formData['income_date']) ?>" required>
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
                            <label for="source" class="form-label fw-semibold small text-secondary">Source Stream *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-briefcase"></i></span>
                                <select class="form-select" id="source" name="source" required>
                                    <?php 
                                    $sources = ['Pocket Money', 'Scholarship', 'Part-time Job', 'Freelancing', 'Gifts', 'Other Income'];
                                    foreach ($sources as $s): ?>
                                        <option value="<?= $s ?>" <?= ($formData['source'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
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
                        <a href="<?= $baseUrl ?>/income/view.php" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-success px-4 shadow-sm">
                            <i class="bi bi-save me-1"></i> Update Income
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
