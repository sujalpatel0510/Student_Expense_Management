-- =======================================================================
-- Student Expense Tracker - Database Schema
-- Subject: Database Management System (DBMS) Project
-- Target DBMS: MySQL (Compatible with MariaDB / XAMPP)
-- Engine: InnoDB (Ensures full ACID compliance and Foreign Key support)
-- =======================================================================

-- 1. Create and select Database
CREATE DATABASE IF NOT EXISTS `student_expense_tracker`
    DEFAULT CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `student_expense_tracker`;

-- Disable foreign key checks during schema creation/reset
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `income`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `students`;

SET FOREIGN_KEY_CHECKS = 1;

-- =======================================================================
-- Table 1: students
-- Entity representing registered college students
-- Meets 3NF: No repeating groups, primary key defined, no transitive dependencies
-- =======================================================================
CREATE TABLE `students` (
    `student_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password using PHP password_hash()',
    `course` VARCHAR(100) NOT NULL COMMENT 'e.g., B.Tech CSE, BCA, B.Sc IT',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `chk_student_email_format` CHECK (`email` LIKE '%@%.%')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================================
-- Table 2: categories
-- Entity representing standardized expense & income categories
-- Normalized into a separate table to eliminate data redundancy (2NF & 3NF)
-- =======================================================================
CREATE TABLE `categories` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(60) NOT NULL UNIQUE,
    `category_type` ENUM('expense', 'income') NOT NULL DEFAULT 'expense',
    `icon` VARCHAR(50) DEFAULT 'bi-tag' COMMENT 'Bootstrap icon identifier',
    `color` VARCHAR(25) DEFAULT '#4f46e5' COMMENT 'Hex color code for charts and badges',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================================
-- Table 3: expenses
-- Entity storing all expenditure transactions made by students
-- Linked to students (M:1) and categories (M:1) via Foreign Keys
-- =======================================================================
CREATE TABLE `expenses` (
    `expense_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `payment_method` ENUM('Cash', 'UPI / Online', 'Debit Card', 'Credit Card', 'Net Banking', 'Other') NOT NULL DEFAULT 'UPI / Online',
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT `chk_expense_positive_amount` CHECK (`amount` > 0.00),
    
    -- Referential Integrity
    CONSTRAINT `fk_expenses_student` 
        FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`student_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
        
    CONSTRAINT `fk_expenses_category` 
        FOREIGN KEY (`category_id`) 
        REFERENCES `categories` (`category_id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance Indexes for frequent query filtering
CREATE INDEX `idx_expenses_student_date` ON `expenses` (`student_id`, `expense_date`);
CREATE INDEX `idx_expenses_category` ON `expenses` (`category_id`);

-- =======================================================================
-- Table 4: income
-- Entity storing student income inflows (allowance, scholarship, freelance, etc.)
-- Linked to students (M:1) and categories (M:1)
-- =======================================================================
CREATE TABLE `income` (
    `income_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `income_date` DATE NOT NULL,
    `source` VARCHAR(100) NOT NULL COMMENT 'Pocket Money, Scholarship, Part-time Job, etc.',
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT `chk_income_positive_amount` CHECK (`amount` > 0.00),
    
    -- Referential Integrity
    CONSTRAINT `fk_income_student` 
        FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`student_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
        
    CONSTRAINT `fk_income_category` 
        FOREIGN KEY (`category_id`) 
        REFERENCES `categories` (`category_id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance Indexes for search and reporting
CREATE INDEX `idx_income_student_date` ON `income` (`student_id`, `income_date`);
CREATE INDEX `idx_income_category` ON `income` (`category_id`);

-- =======================================================================
-- Table 5: budgets
-- Monthly planned spending limits per student
-- Composite Unique constraint ensures a student can set only one budget per month/year
-- =======================================================================
CREATE TABLE `budgets` (
    `budget_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `month` TINYINT NOT NULL COMMENT '1 to 12 representing January to December',
    `year` SMALLINT NOT NULL COMMENT 'e.g., 2026',
    `budget_amount` DECIMAL(10, 2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT `chk_budget_month` CHECK (`month` BETWEEN 1 AND 12),
    CONSTRAINT `chk_budget_year` CHECK (`year` >= 2020),
    CONSTRAINT `chk_budget_positive_amount` CHECK (`budget_amount` > 0.00),
    CONSTRAINT `uq_student_month_year` UNIQUE (`student_id`, `month`, `year`),
    
    -- Referential Integrity
    CONSTRAINT `fk_budgets_student` 
        FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`student_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_budgets_student` ON `budgets` (`student_id`);

-- =======================================================================
-- Database View: v_student_financial_overview
-- Advanced DBMS feature demonstration: Pre-aggregated View for fast queries
-- =======================================================================
CREATE OR REPLACE VIEW `v_student_monthly_overview` AS
SELECT 
    s.student_id,
    s.name AS student_name,
    b.month,
    b.year,
    COALESCE(b.budget_amount, 0.00) AS allocated_budget,
    COALESCE(e.total_expense, 0.00) AS total_expense,
    COALESCE(i.total_income, 0.00) AS total_income,
    (COALESCE(b.budget_amount, 0.00) - COALESCE(e.total_expense, 0.00)) AS remaining_budget,
    (COALESCE(i.total_income, 0.00) - COALESCE(e.total_expense, 0.00)) AS net_savings
FROM students s
LEFT JOIN budgets b ON s.student_id = b.student_id
LEFT JOIN (
    SELECT student_id, MONTH(expense_date) AS m, YEAR(expense_date) AS y, SUM(amount) AS total_expense
    FROM expenses
    GROUP BY student_id, MONTH(expense_date), YEAR(expense_date)
) e ON s.student_id = e.student_id AND b.month = e.m AND b.year = e.y
LEFT JOIN (
    SELECT student_id, MONTH(income_date) AS m, YEAR(income_date) AS y, SUM(amount) AS total_income
    FROM income
    GROUP BY student_id, MONTH(income_date), YEAR(income_date)
) i ON s.student_id = i.student_id AND b.month = i.m AND b.year = i.y;
