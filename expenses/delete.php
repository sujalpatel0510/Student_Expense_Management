<?php
/**
 * Delete Expense Record
 * Student Expense Tracker
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$studentId = current_student_id();
$baseUrl = get_base_url();

$expenseId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if ($expenseId <= 0 || !verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Invalid deletion request or security token expired.');
    redirect($baseUrl . '/expenses/view.php');
}

// Ownership check before deletion
$checkStmt = $pdo->prepare("SELECT amount FROM expenses WHERE expense_id = :id AND student_id = :sid");
$checkStmt->execute([':id' => $expenseId, ':sid' => $studentId]);
$amount = $checkStmt->fetchColumn();

if ($amount === false) {
    set_flash('danger', 'Expense record not found or access denied.');
    redirect($baseUrl . '/expenses/view.php');
}

// Perform safe delete
$delStmt = $pdo->prepare("DELETE FROM expenses WHERE expense_id = :id AND student_id = :sid");
$delStmt->execute([':id' => $expenseId, ':sid' => $studentId]);

set_flash('success', 'Expense record of ' . format_currency($amount) . ' successfully deleted.');
redirect($baseUrl . '/expenses/view.php');
