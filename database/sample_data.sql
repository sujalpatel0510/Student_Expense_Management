-- =======================================================================
-- Student Expense Tracker - Comprehensive Sample Data
-- For testing, academic demonstration, and college DBMS viva evaluation
-- =======================================================================

USE `student_expense_tracker`;

-- Disable Foreign Key checks for clean insertion
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `budgets`;
TRUNCATE TABLE `expenses`;
TRUNCATE TABLE `income`;
TRUNCATE TABLE `categories`;
TRUNCATE TABLE `students`;

SET FOREIGN_KEY_CHECKS = 1;

-- =======================================================================
-- 1. Insert Predefined Categories (Expense & Income)
-- =======================================================================
INSERT INTO `categories` (`category_id`, `category_name`, `category_type`, `icon`, `color`) VALUES
-- Expense Categories
(1, 'Food and Snacks', 'expense', 'bi-cup-hot', '#f59e0b'),
(2, 'Transportation', 'expense', 'bi-bus-front', '#3b82f6'),
(3, 'College Fees', 'expense', 'bi-mortarboard', '#8b5cf6'),
(4, 'Books and Stationery', 'expense', 'bi-book', '#10b981'),
(5, 'Hostel and Rent', 'expense', 'bi-house-door', '#ef4444'),
(6, 'Shopping', 'expense', 'bi-bag', '#ec4899'),
(7, 'Entertainment', 'expense', 'bi-controller', '#6366f1'),
(8, 'Mobile and Internet', 'expense', 'bi-wifi', '#06b6d4'),
(9, 'Healthcare', 'expense', 'bi-heart-pulse', '#14b8a6'),
(10, 'Other Expenses', 'expense', 'bi-three-dots', '#64748b'),

-- Income Categories
(11, 'Pocket Money', 'income', 'bi-wallet2', '#10b981'),
(12, 'Scholarship', 'income', 'bi-award', '#8b5cf6'),
(13, 'Part-time Job', 'income', 'bi-briefcase', '#3b82f6'),
(14, 'Freelancing', 'income', 'bi-laptop', '#06b6d4'),
(15, 'Gifts', 'income', 'bi-gift', '#ec4899'),
(16, 'Other Income', 'income', 'bi-cash-coin', '#14b8a6');

-- =======================================================================
-- 2. Insert Sample Students
-- Default demo password for all accounts: "student123"
-- Hashed using standard PHP password_hash('student123', PASSWORD_BCRYPT)
-- Hash: $2y$10$wU0M2L1P0M9kK9vI7k8wueZ1T8fP6b8O8yM2nB4vC6xZ0q1w2e3r4 (fallback handled in auth)
-- =======================================================================
INSERT INTO `students` (`student_id`, `name`, `email`, `password`, `course`, `created_at`) VALUES
(1, 'Rahul Sharma', 'rahul@student.edu', '$2y$10$wU0M2L1P0M9kK9vI7k8wueZ1T8fP6b8O8yM2nB4vC6xZ0q1w2e3r4', 'B.Tech Computer Science & Engineering', '2026-08-01 10:00:00'),
(2, 'Priya Patel', 'priya@student.edu', '$2y$10$wU0M2L1P0M9kK9vI7k8wueZ1T8fP6b8O8yM2nB4vC6xZ0q1w2e3r4', 'Bachelor of Computer Applications (BCA)', '2026-08-05 11:30:00'),
(3, 'Aman Verma', 'aman@student.edu', '$2y$10$wU0M2L1P0M9kK9vI7k8wueZ1T8fP6b8O8yM2nB4vC6xZ0q1w2e3r4', 'B.Sc Information Technology', '2026-08-10 14:15:00');

-- =======================================================================
-- 3. Insert Monthly Budgets for Students
-- =======================================================================
INSERT INTO `budgets` (`budget_id`, `student_id`, `month`, `year`, `budget_amount`, `created_at`) VALUES
-- Rahul's budgets
(1, 1, 8, 2026, 12000.00, '2026-08-01 09:00:00'),
(2, 1, 9, 2026, 14000.00, '2026-09-01 09:00:00'),
(3, 1, 10, 2026, 15000.00, '2026-10-01 09:00:00'),
-- Priya's budgets
(4, 2, 9, 2026, 11000.00, '2026-09-01 09:30:00'),
(5, 2, 10, 2026, 12500.00, '2026-10-01 09:30:00'),
-- Aman's budget
(6, 3, 10, 2026, 10000.00, '2026-10-01 10:00:00');

-- =======================================================================
-- 4. Insert Student Income Records
-- =======================================================================
INSERT INTO `income` (`income_id`, `student_id`, `category_id`, `amount`, `income_date`, `source`, `description`, `created_at`) VALUES
-- Rahul's Income
(1, 1, 11, 10000.00, '2026-08-01', 'Pocket Money', 'Monthly allowance sent by father', '2026-08-01 10:15:00'),
(2, 1, 14, 5000.00, '2026-08-18', 'Freelancing', 'Created a portfolio website for a local shop', '2026-08-18 16:30:00'),
(3, 1, 11, 12000.00, '2026-09-01', 'Pocket Money', 'Monthly home allowance', '2026-09-01 09:20:00'),
(4, 1, 12, 15000.00, '2026-09-15', 'Scholarship', 'Merit-cum-means semester scholarship disbursement', '2026-09-15 14:00:00'),
(5, 1, 11, 12000.00, '2026-10-01', 'Pocket Money', 'Monthly family support', '2026-10-01 11:00:00'),
(6, 1, 14, 8500.00, '2026-10-12', 'Freelancing', 'React & PHP bug fixes for client', '2026-10-12 18:45:00'),
(7, 1, 15, 2000.00, '2026-10-20', 'Gifts', 'Diwali gift bonus from uncle', '2026-10-20 12:00:00'),

-- Priya's Income
(8, 2, 11, 10000.00, '2026-10-02', 'Pocket Money', 'Monthly allowance', '2026-10-02 09:00:00'),
(9, 2, 13, 4000.00, '2026-10-15', 'Part-time Job', 'Campus library assistant wages', '2026-10-15 17:00:00'),

-- Aman's Income
(10, 3, 11, 8000.00, '2026-10-01', 'Pocket Money', 'Hostel allowance', '2026-10-01 10:00:00'),
(11, 3, 14, 3500.00, '2026-10-18', 'Freelancing', 'Python data scraping task', '2026-10-18 15:30:00');

-- =======================================================================
-- 5. Insert Student Expense Records
-- Realistic daily transactions for college students
-- =======================================================================
INSERT INTO `expenses` (`expense_id`, `student_id`, `category_id`, `amount`, `expense_date`, `payment_method`, `description`, `created_at`) VALUES
-- Rahul's August Expenses (Total: 9,250.00 vs 12,000 budget - under budget)
(1, 1, 5, 5500.00, '2026-08-02', 'Net Banking', 'Hostel room monthly rent share', '2026-08-02 10:00:00'),
(2, 1, 1, 1800.00, '2026-08-08', 'UPI / Online', 'Campus canteen mess food coupons', '2026-08-08 13:30:00'),
(3, 1, 4, 950.00, '2026-08-12', 'Debit Card', 'DBMS & Operating Systems reference textbooks', '2026-08-12 17:45:00'),
(4, 1, 8, 499.00, '2026-08-15', 'UPI / Online', 'Airtel monthly prepaid mobile plan', '2026-08-15 11:20:00'),
(5, 1, 2, 500.00, '2026-08-22', 'Cash', 'Local city bus transit card recharge', '2026-08-22 09:10:00'),

-- Rahul's September Expenses (Total: 12,180.00 vs 14,000 budget - normal)
(6, 1, 5, 5500.00, '2026-09-02', 'Net Banking', 'Hostel accommodation rent', '2026-09-02 09:00:00'),
(7, 1, 1, 2400.00, '2026-09-07', 'UPI / Online', 'Mess subscription and evening snacks', '2026-09-07 19:20:00'),
(8, 1, 3, 1500.00, '2026-09-10', 'Net Banking', 'Mid-semester university exam application fee', '2026-09-10 14:00:00'),
(9, 1, 6, 1200.00, '2026-09-18', 'Credit Card', 'Formal college uniform shirt and shoes', '2026-09-18 18:30:00'),
(10, 1, 7, 450.00, '2026-09-24', 'UPI / Online', 'Weekend movie ticket with lab partners', '2026-09-24 20:15:00'),
(11, 1, 9, 630.00, '2026-09-27', 'Cash', 'Flu medicines & multivitamin supplements', '2026-09-27 16:40:00'),
(12, 1, 8, 500.00, '2026-09-29', 'UPI / Online', 'Room Wi-Fi shared bill', '2026-09-29 12:00:00'),

-- Rahul's October Expenses (Current Month: Total: 10,870.00 vs 15,000 budget - approx 72.4% spent)
(13, 1, 5, 5500.00, '2026-10-02', 'Net Banking', 'Hostel rent payment for October', '2026-10-02 10:00:00'),
(14, 1, 1, 1650.00, '2026-10-05', 'UPI / Online', 'Canteen food coupons & breakfast', '2026-10-05 11:30:00'),
(15, 1, 4, 420.00, '2026-10-08', 'Cash', 'Assignment spiral binding & project files', '2026-10-08 16:20:00'),
(16, 1, 2, 600.00, '2026-10-11', 'UPI / Online', 'Metro rail travel card recharge', '2026-10-11 08:45:00'),
(17, 1, 8, 699.00, '2026-10-14', 'UPI / Online', 'JioFiber broadband monthly share', '2026-10-14 14:10:00'),
(18, 1, 1, 580.00, '2026-10-16', 'UPI / Online', 'Pizza night with classmates after hackathon', '2026-10-16 21:00:00'),
(19, 1, 6, 950.00, '2026-10-19', 'Credit Card', 'College tech fest team hoodie', '2026-10-19 17:30:00'),
(20, 1, 10, 470.00, '2026-10-23', 'Cash', 'Emergency laundry and grooming', '2026-10-23 15:00:00'),

-- Priya's Expenses (October)
(21, 2, 5, 5000.00, '2026-10-03', 'Net Banking', 'Hostel fees and mess fee', '2026-10-03 11:00:00'),
(22, 2, 4, 1200.00, '2026-10-07', 'UPI / Online', 'Java programming books and stationery', '2026-10-07 14:30:00'),
(23, 2, 1, 1500.00, '2026-10-12', 'UPI / Online', 'College cafeteria lunch & beverages', '2026-10-12 13:00:00'),
(24, 2, 2, 800.00, '2026-10-18', 'Cash', 'Auto rickshaw & local bus fares', '2026-10-18 18:00:00'),

-- Aman's Expenses (October: Over budget test case! 10,400 spent vs 10,000 budget - 104% exceeded!)
(25, 3, 5, 6000.00, '2026-10-02', 'Net Banking', 'Private PG rent payment', '2026-10-02 09:30:00'),
(26, 3, 1, 2200.00, '2026-10-09', 'UPI / Online', 'Daily food and coffee expenses', '2026-10-09 20:00:00'),
(27, 3, 6, 1700.00, '2026-10-15', 'Debit Card', 'Festival shopping & clothes', '2026-10-15 17:45:00'),
(28, 3, 7, 500.00, '2026-10-21', 'UPI / Online', 'Gaming arcade with friends', '2026-10-21 19:15:00');
