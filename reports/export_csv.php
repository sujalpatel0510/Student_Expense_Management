<?php
/**
 * Export Transactions to CSV
 * Student Expense Tracker
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = getDBConnection();
$studentId = current_student_id();
$student = current_student();

$transType = trim($_GET['type'] ?? 'all');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$expensePart = "
    SELECT 
        'Expense' AS trans_type,
        e.expense_id AS trans_id,
        e.expense_date AS trans_date,
        c.category_name,
        e.payment_method AS method_or_source,
        e.amount,
        e.description
    FROM expenses e
    INNER JOIN categories c ON e.category_id = c.category_id
    WHERE e.student_id = :sid_e
";

$incomePart = "
    SELECT 
        'Income' AS trans_type,
        i.income_id AS trans_id,
        i.income_date AS trans_date,
        c.category_name,
        i.source AS method_or_source,
        i.amount,
        i.description
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

$ledgerParams = [];
if ($transType === 'expense') {
    $sql = $expensePart . " ORDER BY trans_date DESC";
    $ledgerParams[':sid_e'] = $studentId;
    if (!empty($fromDate)) $ledgerParams[':from_date_e'] = $fromDate;
    if (!empty($toDate)) $ledgerParams[':to_date_e'] = $toDate;
} elseif ($transType === 'income') {
    $sql = $incomePart . " ORDER BY trans_date DESC";
    $ledgerParams[':sid_i'] = $studentId;
    if (!empty($fromDate)) $ledgerParams[':from_date_i'] = $fromDate;
    if (!empty($toDate)) $ledgerParams[':to_date_i'] = $toDate;
} else {
    $sql = "(" . $expensePart . ") UNION ALL (" . $incomePart . ") ORDER BY trans_date DESC";
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

$stmt = $pdo->prepare($sql);
$stmt->execute($ledgerParams);
$rows = $stmt->fetchAll();

// Clean filename
$filename = "student_expense_statement_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header metadata
fputcsv($output, ['Student Expense Tracker - Account Statement']);
fputcsv($output, ['Student Name', $student['name']]);
fputcsv($output, ['Course', $student['course']]);
fputcsv($output, ['Email', $student['email']]);
fputcsv($output, ['Export Date', date('Y-m-d H:i:s')]);
fputcsv($output, []); // blank line

// Column Headers
fputcsv($output, ['Type', 'Transaction ID', 'Date', 'Category', 'Payment Method / Source', 'Amount (INR)', 'Description']);

// Rows
foreach ($rows as $r) {
    fputcsv($output, [
        $r['trans_type'],
        $r['trans_id'],
        $r['trans_date'],
        $r['category_name'],
        $r['method_or_source'],
        number_format((float)$r['amount'], 2, '.', ''),
        $r['description'] ?? ''
    ]);
}

fclose($output);
exit;
