-- =======================================================================
-- Student Expense Tracker - Useful & Advanced DBMS Queries
-- Subject: Database Management System (DBMS) Project Showcase
-- Demonstrates: DDL, DML, Joins, Aggregations, Subqueries, GROUP BY, HAVING,
-- Views, Indexes, Referential Integrity, and Analytical Queries.
-- =======================================================================

USE `student_expense_tracker`;

-- -----------------------------------------------------------------------
-- 1. BASIC DML OPERATIONS (INSERT, SELECT, UPDATE, DELETE)
-- -----------------------------------------------------------------------

-- 1.1 Insert a new expense record with foreign keys
INSERT INTO `expenses` (`student_id`, `category_id`, `amount`, `expense_date`, `payment_method`, `description`)
VALUES (1, 1, 250.00, CURDATE(), 'UPI / Online', 'Afternoon cafeteria snacks with project group');

-- 1.2 Update an existing expense description and amount
UPDATE `expenses`
SET `amount` = 280.00, `description` = 'Special campus cafeteria lunch'
WHERE `expense_id` = 1 AND `student_id` = 1;

-- 1.3 Safe Delete an expense belonging to a specific student
DELETE FROM `expenses`
WHERE `expense_id` = 20 AND `student_id` = 1;


-- -----------------------------------------------------------------------
-- 2. INNER JOIN & LEFT JOIN DEMONSTRATIONS
-- -----------------------------------------------------------------------

-- 2.1 INNER JOIN: Retrieve detailed expense records with student name & category name
-- Explanation: Matches rows where foreign keys exist in all referenced tables.
SELECT 
    e.expense_id,
    s.name AS student_name,
    s.course,
    c.category_name,
    e.amount,
    e.expense_date,
    e.payment_method,
    e.description
FROM expenses e
INNER JOIN students s ON e.student_id = s.student_id
INNER JOIN categories c ON e.category_id = c.category_id
WHERE e.student_id = 1
ORDER BY e.expense_date DESC;

-- 2.2 LEFT JOIN: List all expense categories and total student spend
-- (Includes categories even if zero expenses have been recorded for them)
SELECT 
    c.category_id,
    c.category_name,
    c.color,
    COUNT(e.expense_id) AS transaction_count,
    COALESCE(SUM(e.amount), 0.00) AS total_spent
FROM categories c
LEFT JOIN expenses e ON c.category_id = e.category_id AND e.student_id = 1
WHERE c.category_type = 'expense'
GROUP BY c.category_id, c.category_name, c.color
ORDER BY total_spent DESC;


-- -----------------------------------------------------------------------
-- 3. AGGREGATE FUNCTIONS, GROUP BY & HAVING CLAUSE
-- -----------------------------------------------------------------------

-- 3.1 Calculate Monthly Spending Summary (SUM, AVG, COUNT, MIN, MAX)
SELECT 
    YEAR(expense_date) AS expense_year,
    MONTH(expense_date) AS expense_month,
    MONTHNAME(expense_date) AS month_name,
    COUNT(expense_id) AS total_transactions,
    SUM(amount) AS total_monthly_spend,
    ROUND(AVG(amount), 2) AS average_transaction_cost,
    MIN(amount) AS lowest_expense,
    MAX(amount) AS highest_expense
FROM expenses
WHERE student_id = 1
GROUP BY YEAR(expense_date), MONTH(expense_date), MONTHNAME(expense_date)
ORDER BY expense_year DESC, expense_month DESC;

-- 3.2 GROUP BY with HAVING: High-expenditure categories
-- Filters category groups where the cumulative spending exceeds ₹1,000
SELECT 
    c.category_name,
    COUNT(e.expense_id) AS total_records,
    SUM(e.amount) AS total_spent
FROM expenses e
INNER JOIN categories c ON e.category_id = c.category_id
WHERE e.student_id = 1
GROUP BY c.category_name
HAVING SUM(e.amount) > 1000.00
ORDER BY total_spent DESC;


-- -----------------------------------------------------------------------
-- 4. SUBQUERIES (CORRELATED & SCALAR)
-- -----------------------------------------------------------------------

-- 4.1 Subquery in WHERE: Find all expenses that are above the student's average expense
SELECT 
    e.expense_id,
    c.category_name,
    e.amount,
    e.expense_date,
    e.description
FROM expenses e
INNER JOIN categories c ON e.category_id = c.category_id
WHERE e.student_id = 1 
  AND e.amount > (
      SELECT AVG(amount) 
      FROM expenses 
      WHERE student_id = 1
  )
ORDER BY e.amount DESC;

-- 4.2 Correlated Subquery: Spending percentage compared to total monthly spending
SELECT 
    e.expense_id,
    c.category_name,
    e.amount,
    e.expense_date,
    ROUND(
        (e.amount / (
            SELECT SUM(amount) 
            FROM expenses sub 
            WHERE sub.student_id = e.student_id 
              AND MONTH(sub.expense_date) = MONTH(e.expense_date) 
              AND YEAR(sub.expense_date) = YEAR(e.expense_date)
        )) * 100, 2
    ) AS percentage_of_monthly_spend
FROM expenses e
INNER JOIN categories c ON e.category_id = c.category_id
WHERE e.student_id = 1 AND MONTH(e.expense_date) = 10 AND YEAR(e.expense_date) = 2026
ORDER BY e.amount DESC;


-- -----------------------------------------------------------------------
-- 5. UNION ALL: COMBINED TRANSACTION LEDGER (INCOME + EXPENSES)
-- -----------------------------------------------------------------------

-- Chronological transaction log for student #1 combining both tables
SELECT 
    'Expense' AS transaction_type,
    e.expense_id AS trans_id,
    e.expense_date AS trans_date,
    c.category_name,
    e.payment_method AS method_or_source,
    -1 * e.amount AS amount,
    e.description
FROM expenses e
INNER JOIN categories c ON e.category_id = c.category_id
WHERE e.student_id = 1

UNION ALL

SELECT 
    'Income' AS transaction_type,
    i.income_id AS trans_id,
    i.income_date AS trans_date,
    c.category_name,
    i.source AS method_or_source,
    i.amount AS amount,
    i.description
FROM income i
INNER JOIN categories c ON i.category_id = c.category_id
WHERE i.student_id = 1

ORDER BY trans_date DESC, trans_id DESC;


-- -----------------------------------------------------------------------
-- 6. BUDGET VS ACTUAL SPENDING WITH STATUS & WARNINGS
-- -----------------------------------------------------------------------

-- Calculates monthly budget, total expense, balance remaining, % consumed,
-- and dynamically assigns warning alerts (Safe, Warning 80%+, Exceeded).
SELECT 
    b.month,
    b.year,
    b.budget_amount,
    COALESCE(SUM(e.amount), 0.00) AS total_spent,
    (b.budget_amount - COALESCE(SUM(e.amount), 0.00)) AS remaining_balance,
    ROUND((COALESCE(SUM(e.amount), 0.00) / b.budget_amount) * 100, 1) AS percentage_used,
    CASE 
        WHEN COALESCE(SUM(e.amount), 0.00) > b.budget_amount THEN 'EXCEEDED_ALERT'
        WHEN (COALESCE(SUM(e.amount), 0.00) / b.budget_amount) >= 0.80 THEN 'WARNING_80_PERCENT'
        ELSE 'SAFE'
    END AS budget_status
FROM budgets b
LEFT JOIN expenses e ON b.student_id = e.student_id 
    AND b.month = MONTH(e.expense_date) 
    AND b.year = YEAR(e.expense_date)
WHERE b.student_id = 1 AND b.month = 10 AND b.year = 2026
GROUP BY b.budget_id, b.month, b.year, b.budget_amount;


-- -----------------------------------------------------------------------
-- 7. NET SAVINGS & CASH FLOW REPORT
-- -----------------------------------------------------------------------

SELECT 
    1 AS student_id,
    10 AS report_month,
    2026 AS report_year,
    COALESCE((
        SELECT SUM(amount) FROM income 
        WHERE student_id = 1 AND MONTH(income_date) = 10 AND YEAR(income_date) = 2026
    ), 0.00) AS total_income,
    COALESCE((
        SELECT SUM(amount) FROM expenses 
        WHERE student_id = 1 AND MONTH(expense_date) = 10 AND YEAR(expense_date) = 2026
    ), 0.00) AS total_expenses,
    (
        COALESCE((
            SELECT SUM(amount) FROM income 
            WHERE student_id = 1 AND MONTH(income_date) = 10 AND YEAR(income_date) = 2026
        ), 0.00) 
        - 
        COALESCE((
            SELECT SUM(amount) FROM expenses 
            WHERE student_id = 1 AND MONTH(expense_date) = 10 AND YEAR(expense_date) = 2026
        ), 0.00)
    ) AS net_savings;
