<?php
/**
 * Delete Income Record
 * Student Expense Tracker
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$studentId = current_student_id();
$baseUrl = get_base_url();

$incomeId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if ($incomeId <= 0 || !verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Invalid deletion request or security token expired.');
    redirect($baseUrl . '/income/view.php');
}

$checkStmt = $pdo->prepare("SELECT amount FROM income WHERE income_id = :id AND student_id = :sid");
$checkStmt->execute([':id' => $incomeId, ':sid' => $studentId]);
$amount = $checkStmt->fetchColumn();

if ($amount === false) {
    set_flash('danger', 'Income record not found or access denied.');
    redirect($baseUrl . '/income/view.php');
}

$delStmt = $pdo->prepare("DELETE FROM income WHERE income_id = :id AND student_id = :sid");
$delStmt->execute([':id' => $incomeId, ':sid' => $studentId]);

set_flash('success', 'Income record of ' . format_currency($amount) . ' successfully deleted.');
redirect($baseUrl . '/income/view.php');
