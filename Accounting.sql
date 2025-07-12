-- ===================================================
-- CLEAN DATABASE SCRIPT (Run this FIRST to clean existing data)
-- ===================================================

-- Drop accounting-related tables if they exist
DROP TABLE IF EXISTS tbl_transaction_details;
DROP TABLE IF EXISTS tbl_transactions;
DROP TABLE IF EXISTS tbl_accounts;
DROP TABLE IF EXISTS tbl_financial_periods;
DROP TABLE IF EXISTS tbl_reference_sequences;
DROP TABLE IF EXISTS tbl_accounting_settings;

-- Remove accounting columns from existing tables
ALTER TABLE tbl_gold_loan DROP COLUMN IF EXISTS accounting_transaction_id;
ALTER TABLE tbl_gold_loan DROP COLUMN IF EXISTS closing_accounting_id;
ALTER TABLE tbl_repledge DROP COLUMN IF EXISTS accounting_transaction_id;
ALTER TABLE tbl_expenses DROP COLUMN IF EXISTS accounting_transaction_id;

-- ===================================================
-- PRODUCTION ACCOUNTING MIGRATION (Run this SECOND)
-- ===================================================

-- 1. CORE ACCOUNTING TABLES
-- ===================================================

-- Main Transactions Table
CREATE TABLE tbl_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference_no VARCHAR(50),
    transaction_type ENUM('Receipt', 'Payment', 'Journal') NOT NULL,
    created_by INT NULL,
    approved_by INT NULL,
    status ENUM('Draft', 'Posted', 'Cancelled') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transaction Details (Double-Entry)
CREATE TABLE tbl_transaction_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    account_id INT NOT NULL,
    debit DECIMAL(12,2) DEFAULT 0.00,
    credit DECIMAL(12,2) DEFAULT 0.00,
    description VARCHAR(255),
    FOREIGN KEY (transaction_id) REFERENCES tbl_transactions(id),
    FOREIGN KEY (account_id) REFERENCES tbl_accounts(id)
);

-- Chart of Accounts
CREATE TABLE tbl_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Income', 'Expense') NOT NULL,
    parent_account_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_account_id) REFERENCES tbl_accounts(id)
);

-- Financial Periods
CREATE TABLE tbl_financial_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_closed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL DEFAULT NULL
);

-- Reference Sequences for Auto-numbering
CREATE TABLE tbl_reference_sequences (
    sequence_type VARCHAR(20) PRIMARY KEY,
    last_number INT DEFAULT 0,
    prefix VARCHAR(10),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Accounting Settings
CREATE TABLE tbl_accounting_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255),
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. INDEXES FOR PERFORMANCE
-- ===================================================

-- Transaction indexes
CREATE INDEX idx_transaction_date ON tbl_transactions(transaction_date);
CREATE INDEX idx_reference_no ON tbl_transactions(reference_no);
CREATE INDEX idx_transaction_type ON tbl_transactions(transaction_type);
CREATE INDEX idx_status ON tbl_transactions(status);
CREATE INDEX idx_trans_date_status ON tbl_transactions(transaction_date, status);

-- Transaction details indexes
CREATE INDEX idx_transaction_id ON tbl_transaction_details(transaction_id);
CREATE INDEX idx_account_id ON tbl_transaction_details(account_id);
CREATE INDEX idx_td_composite ON tbl_transaction_details(transaction_id, account_id);

-- Account indexes
CREATE INDEX idx_account_code ON tbl_accounts(account_code);
CREATE INDEX idx_account_type ON tbl_accounts(account_type);
CREATE INDEX idx_parent ON tbl_accounts(parent_account_id);

-- Financial periods indexes
CREATE INDEX idx_dates ON tbl_financial_periods(start_date, end_date);

-- 3. FOREIGN KEY CONSTRAINTS
-- ===================================================

-- Add user foreign keys if tbl_user exists
ALTER TABLE tbl_transactions 
ADD CONSTRAINT fk_created_by FOREIGN KEY (created_by) 
REFERENCES tbl_user(id) ON DELETE SET NULL;

ALTER TABLE tbl_transactions 
ADD CONSTRAINT fk_approved_by FOREIGN KEY (approved_by) 
REFERENCES tbl_user(id) ON DELETE SET NULL;

-- 4. DATA INTEGRITY CONSTRAINTS
-- ===================================================

-- Ensure debit/credit are not negative
ALTER TABLE tbl_transaction_details 
ADD CONSTRAINT chk_debit_positive CHECK (debit >= 0),
ADD CONSTRAINT chk_credit_positive CHECK (credit >= 0);

-- Ensure at least one of debit/credit is non-zero
ALTER TABLE tbl_transaction_details 
ADD CONSTRAINT chk_debit_or_credit CHECK (debit > 0 OR credit > 0);

-- Ensure period dates are valid
ALTER TABLE tbl_financial_periods 
ADD CONSTRAINT chk_period_dates CHECK (end_date > start_date);

-- 5. ESSENTIAL CHART OF ACCOUNTS
-- ===================================================

INSERT INTO tbl_accounts (id, account_code, account_name, account_type, parent_account_id, is_active) VALUES
-- Assets
(1001, 'CASH001', 'Cash in Hand', 'Asset', NULL, TRUE),
(1002, 'BANK001', 'Bank Account - Main', 'Asset', NULL, TRUE),
(1003, 'LOANS001', 'Gold Loan', 'Asset', NULL, TRUE),
(1004, 'INTEREST001', 'Interest Receivable', 'Asset', NULL, TRUE),
(1005, 'GOLD001', 'Gold Stock', 'Asset', NULL, TRUE),
(1006, 'OFFICEEQUIP001', 'Office Equipment', 'Asset', NULL, TRUE),
(1007, 'FURNITURE001', 'Furniture & Fixtures', 'Asset', NULL, TRUE),

-- Liabilities
(2001, 'REPLEDGE001', 'Bank Repledge Payable', 'Liability', NULL, TRUE),
(2002, 'CUSDEPOSITS001', 'Customer Deposits', 'Liability', NULL, TRUE),
(2003, 'PAYABLE001', 'Accounts Payable', 'Liability', NULL, TRUE),
(2004, 'SALARYPAY001', 'Salary Payable', 'Liability', NULL, TRUE),
(2005, 'OTHERLIAB001', 'Other Current Liabilities', 'Liability', NULL, TRUE),

-- Equity
(3001, 'CAPITAL001', 'Owner Capital', 'Equity', NULL, TRUE),
(3002, 'RETAINED001', 'Retained Earnings', 'Equity', NULL, TRUE),

-- Income
(4001, 'INTEREST_INC001', 'Interest Income', 'Income', NULL, TRUE),
(4002, 'OTHER_INC001', 'Other Income', 'Income', NULL, TRUE),
(4003, 'LATEPAYMENT001', 'Late Payment Charges', 'Income', NULL, TRUE),
(4004, 'PROCESSING001', 'Processing Fees', 'Income', NULL, TRUE),

-- Expenses
(5001, 'INTEREST_EXP001', 'Interest Expense', 'Expense', NULL, TRUE),
(5002, 'OFFICE_EXP001', 'Office Expenses', 'Expense', NULL, TRUE),
(5003, 'SALARY_EXP001', 'Salary Expense', 'Expense', NULL, TRUE),
(5004, 'RENT_EXP001', 'Rent Expense', 'Expense', NULL, TRUE),
(5005, 'UTILITIES_EXP001', 'Utilities Expense', 'Expense', NULL, TRUE),
(5006, 'DEPRECIATION_EXP001', 'Depreciation Expense', 'Expense', NULL, TRUE),
(5007, 'BADDEBT_EXP001', 'Bad Debt Expense', 'Expense', NULL, TRUE),
(5008, 'TRANSPORTATION_EXP001', 'Transportation Expense', 'Expense', NULL, TRUE);

-- 6. REFERENCE SEQUENCES
-- ===================================================

INSERT INTO tbl_reference_sequences (sequence_type, prefix, last_number) VALUES
('JOURNAL', 'JV-', 0),
('RECEIPT', 'RCT-', 0),
('PAYMENT', 'PMT-', 0),
('LOAN', 'GL-', 0),
('REPLEDGE', 'RPL-', 0);

-- 7. ACCOUNTING SETTINGS
-- ===================================================

INSERT INTO tbl_accounting_settings (setting_key, setting_value, description) VALUES
('default_interest_rate', '21', 'Default annual interest rate (%)'),
('compound_interest_base', '1.21', 'Base for compound interest calculation'),
('financial_year_start', '04-01', 'Financial year start month-day'),
('currency_symbol', '₹', 'Currency symbol'),
('decimal_places', '2', 'Decimal places for amounts');

-- 8. CURRENT FINANCIAL PERIOD
-- ===================================================

INSERT INTO tbl_financial_periods (period_name, start_date, end_date) VALUES
('FY 2024-2025', '2024-04-01', '2025-03-31');

-- 9. ADD ACCOUNTING INTEGRATION COLUMNS
-- ===================================================

-- For gold loans
ALTER TABLE tbl_gold_loan 
ADD COLUMN accounting_transaction_id INT DEFAULT NULL,
ADD COLUMN closing_accounting_id INT DEFAULT NULL,
ADD KEY idx_accounting_trans (accounting_transaction_id);

-- For repledge
ALTER TABLE tbl_repledge 
ADD COLUMN accounting_transaction_id INT DEFAULT NULL,
ADD KEY idx_accounting_trans (accounting_transaction_id);

-- For expenses
ALTER TABLE tbl_expenses 
ADD COLUMN accounting_transaction_id INT DEFAULT NULL,
ADD KEY idx_accounting_transaction_id (accounting_transaction_id);

-- 10. USEFUL VIEWS FOR REPORTING
-- ===================================================

-- Cash Book View
CREATE OR REPLACE VIEW vw_cash_book AS
SELECT 
    t.id,
    t.transaction_date,
    t.description,
    t.reference_no,
    t.transaction_type,
    td.debit,
    td.credit,
    a.account_name,
    a.account_code
FROM tbl_transaction_details td
JOIN tbl_transactions t ON td.transaction_id = t.id
JOIN tbl_accounts a ON td.account_id = a.id
WHERE a.account_code IN ('CASH001', 'BANK001')
AND t.status = 'Posted'
ORDER BY t.transaction_date, t.id;

-- Trial Balance View
CREATE OR REPLACE VIEW vw_trial_balance AS
SELECT 
    a.account_code,
    a.account_name,
    a.account_type,
    SUM(td.debit) as total_debit,
    SUM(td.credit) as total_credit,
    CASE 
        WHEN a.account_type IN ('Asset', 'Expense') 
        THEN SUM(td.debit) - SUM(td.credit)
        ELSE SUM(td.credit) - SUM(td.debit)
    END as balance
FROM tbl_accounts a
LEFT JOIN tbl_transaction_details td ON a.id = td.account_id
LEFT JOIN tbl_transactions t ON td.transaction_id = t.id AND t.status = 'Posted'
WHERE a.is_active = TRUE
GROUP BY a.id, a.account_code, a.account_name, a.account_type
HAVING total_debit > 0 OR total_credit > 0
ORDER BY a.account_type, a.account_code;

-- Outstanding Loans View
CREATE OR REPLACE VIEW vw_outstanding_loans AS
SELECT 
    gl.gl_no,
    gl.b_id,
    c.name as customer_name,
    gl.loan_amnt,
    gl.date as loan_date,
    DATEDIFF(CURDATE(), gl.date) as days_outstanding,
    CEIL(gl.loan_amnt * POW(1.21, (DATEDIFF(CURDATE(), gl.date) / 365))) as current_value
FROM tbl_gold_loan gl
JOIN tbl_customer c ON gl.b_id = c.id
WHERE gl.status = 0;

-- 11. STORED PROCEDURES
-- ===================================================

DELIMITER $$

-- Get account balance as of date
CREATE PROCEDURE sp_get_account_balance(
    IN p_account_id INT,
    IN p_as_of_date DATE,
    OUT p_balance DECIMAL(12,2)
)
BEGIN
    SELECT COALESCE(SUM(td.debit) - SUM(td.credit), 0) INTO p_balance
    FROM tbl_transaction_details td
    JOIN tbl_transactions t ON td.transaction_id = t.id
    WHERE td.account_id = p_account_id
    AND t.transaction_date <= p_as_of_date
    AND t.status = 'Posted';
END$$

-- Check if transaction is balanced
CREATE PROCEDURE sp_check_transaction_balance(
    IN p_transaction_id INT,
    OUT p_is_balanced BOOLEAN
)
BEGIN
    DECLARE v_debit_total DECIMAL(12,2);
    DECLARE v_credit_total DECIMAL(12,2);
    
    SELECT SUM(debit), SUM(credit) INTO v_debit_total, v_credit_total
    FROM tbl_transaction_details
    WHERE transaction_id = p_transaction_id;
    
    SET p_is_balanced = (ABS(v_debit_total - v_credit_total) < 0.01);
END$$

DELIMITER ;

-- 12. HELPER FUNCTION
-- ===================================================

DELIMITER $$
CREATE FUNCTION get_account_id_by_code(p_account_code VARCHAR(20))
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN 
    DECLARE v_account_id INT;
    SELECT id INTO v_account_id 
    FROM tbl_accounts 
    WHERE account_code = p_account_code 
    LIMIT 1;
    RETURN v_account_id;
END$$
DELIMITER ;

-- ===================================================
-- MIGRATION COMPLETE
-- ===================================================

-- ===================================================
-- FIXED ASSETS MODULE TABLES
-- ===================================================

-- 1. Fixed Assets Table
CREATE TABLE tbl_fixed_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(20) NOT NULL UNIQUE,
    asset_name VARCHAR(100) NOT NULL,
    category ENUM('Furniture', 'Fixtures', 'Equipment', 'Electronics', 'Others') NOT NULL,
    purchase_date DATE NOT NULL,
    purchase_amount DECIMAL(12,2) NOT NULL,
    depreciation_rate DECIMAL(5,2) DEFAULT 10.00,
    accumulated_depreciation DECIMAL(12,2) DEFAULT 0.00,
    book_value DECIMAL(12,2) NOT NULL,
    status ENUM('Active', 'Disposed', 'Scrapped') DEFAULT 'Active',
    vendor_name VARCHAR(100),
    invoice_number VARCHAR(50),
    warranty_period INT DEFAULT 0, -- in years
    location VARCHAR(100),
    remarks TEXT,
    accounting_transaction_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset_code (asset_code),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_purchase_date (purchase_date),
    INDEX idx_accounting_trans (accounting_transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Depreciation Schedule Table
CREATE TABLE tbl_depreciation_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    depreciation_date DATE NOT NULL,
    depreciation_amount DECIMAL(12,2) NOT NULL,
    accumulated_depreciation DECIMAL(12,2) NOT NULL,
    book_value_after DECIMAL(12,2) NOT NULL,
    accounting_transaction_id INT,
    status ENUM('Pending', 'Posted') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES tbl_fixed_assets(id) ON DELETE CASCADE,
    INDEX idx_asset_id (asset_id),
    INDEX idx_depreciation_date (depreciation_date),
    INDEX idx_status (status),
    INDEX idx_accounting_trans (accounting_transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Add Fixed Asset Account Codes to Chart of Accounts
INSERT INTO tbl_accounts (account_code, account_name, account_type, is_active) VALUES
-- Asset Accounts (if not already exists)
('FA001', 'Furniture & Fittings', 'Asset', 1),
('FA002', 'Office Equipment', 'Asset', 1), 
('FA003', 'Electronics & IT Equipment', 'Asset', 1),
('FA004', 'Other Fixed Assets', 'Asset', 1),

-- Accumulated Depreciation Accounts
('DEP001', 'Accumulated Depreciation - Furniture', 'Asset', 1),
('DEP002', 'Accumulated Depreciation - Equipment', 'Asset', 1),
('DEP003', 'Accumulated Depreciation - Electronics', 'Asset', 1),
('DEP004', 'Accumulated Depreciation - Others', 'Asset', 1)

ON DUPLICATE KEY UPDATE 
account_name = VALUES(account_name),
is_active = VALUES(is_active);

-- 5. Create View for Asset Register
CREATE OR REPLACE VIEW vw_asset_register AS
SELECT 
    fa.id,
    fa.asset_code,
    fa.asset_name,
    fa.category,
    fa.purchase_date,
    fa.purchase_amount,
    fa.depreciation_rate,
    fa.accumulated_depreciation,
    fa.book_value,
    fa.status,
    fa.vendor_name,
    fa.location,
    CASE 
        WHEN fa.accounting_transaction_id IS NOT NULL THEN 'Posted'
        ELSE 'Not Posted'
    END as accounting_status,
    DATEDIFF(CURDATE(), fa.purchase_date) as age_in_days,
    CASE 
        WHEN fa.warranty_period > 0 AND DATEDIFF(CURDATE(), fa.purchase_date) <= (fa.warranty_period * 365)
        THEN 'Under Warranty'
        ELSE 'Out of Warranty'
    END as warranty_status
FROM tbl_fixed_assets fa
WHERE fa.status = 'Active'
ORDER BY fa.purchase_date DESC;

-- 6. Create View for Depreciation Summary
CREATE OR REPLACE VIEW vw_depreciation_summary AS
SELECT 
    fa.category,
    COUNT(fa.id) as asset_count,
    SUM(fa.purchase_amount) as total_cost,
    SUM(fa.accumulated_depreciation) as total_depreciation,
    SUM(fa.book_value) as total_book_value,
    ROUND(AVG(fa.depreciation_rate), 2) as avg_depreciation_rate,
    SUM(CASE WHEN fa.accounting_transaction_id IS NOT NULL THEN 1 ELSE 0 END) as posted_assets
FROM tbl_fixed_assets fa
WHERE fa.status = 'Active'
GROUP BY fa.category
ORDER BY total_cost DESC;


-- Add reference sequences for asset management
INSERT INTO tbl_reference_sequences (sequence_type, prefix, last_number) VALUES
('ASSET', 'AST-', 0),
('DEP', 'DEP-', 0),
('DISP', 'DISP-', 0)
ON DUPLICATE KEY UPDATE 
prefix = VALUES(prefix);



-- Database Updates for Financial Period Integration

-- 1. Add financial_period_id to transactions table
ALTER TABLE tbl_transactions 
ADD COLUMN financial_period_id INT DEFAULT NULL,
ADD CONSTRAINT fk_financial_period 
FOREIGN KEY (financial_period_id) REFERENCES tbl_financial_periods(id);

-- 2. Add financial_period_id to capital_transactions table
ALTER TABLE tbl_capital_transactions 
ADD COLUMN financial_period_id INT DEFAULT NULL,
ADD CONSTRAINT fk_capital_financial_period 
FOREIGN KEY (financial_period_id) REFERENCES tbl_financial_periods(id);

-- 3. Add indexes for performance
CREATE INDEX idx_transaction_period ON tbl_transactions(financial_period_id);
CREATE INDEX idx_capital_period ON tbl_capital_transactions(financial_period_id);
CREATE INDEX idx_period_date_status ON tbl_financial_periods(start_date, end_date, is_closed);

-- 4. Update existing data to assign to current period (if needed)
UPDATE tbl_transactions 
SET financial_period_id = (
    SELECT id FROM tbl_financial_periods 
    WHERE tbl_transactions.transaction_date BETWEEN start_date AND end_date 
    LIMIT 1
)
WHERE financial_period_id IS NULL;

UPDATE tbl_capital_transactions 
SET financial_period_id = (
    SELECT id FROM tbl_financial_periods 
    WHERE tbl_capital_transactions.transaction_date BETWEEN start_date AND end_date 
    LIMIT 1
)
WHERE financial_period_id IS NULL;

ALTER TABLE tbl_reference_sequences 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add index for better performance
CREATE INDEX IF NOT EXISTS idx_sequence_type ON tbl_reference_sequences(sequence_type);

-- 5. Create view for period-wise transactions
CREATE VIEW vw_period_transactions AS
SELECT 
    t.id,
    t.transaction_date,
    t.description,
    t.reference_no,
    t.transaction_type,
    t.status,
    fp.period_name,
    fp.is_closed as period_closed,
    SUM(td.debit) as total_debit,
    SUM(td.credit) as total_credit
FROM tbl_transactions t
LEFT JOIN tbl_financial_periods fp ON t.financial_period_id = fp.id
LEFT JOIN tbl_transaction_details td ON t.id = td.transaction_id
GROUP BY t.id, fp.id;

-- 6. Create view for period-wise P&L summary
CREATE VIEW vw_period_pl_summary AS
SELECT 
    fp.id as period_id,
    fp.period_name,
    fp.start_date,
    fp.end_date,
    fp.is_closed,
    COALESCE(SUM(CASE WHEN a.account_type = 'Income' 
                     THEN td.credit - td.debit ELSE 0 END), 0) as total_income,
    COALESCE(SUM(CASE WHEN a.account_type = 'Expense' 
                     THEN td.debit - td.credit ELSE 0 END), 0) as total_expenses,
    COALESCE(SUM(CASE WHEN a.account_type = 'Income' 
                     THEN td.credit - td.debit ELSE 0 END), 0) - 
    COALESCE(SUM(CASE WHEN a.account_type = 'Expense' 
                     THEN td.debit - td.credit ELSE 0 END), 0) as net_profit
FROM tbl_financial_periods fp
LEFT JOIN tbl_transactions t ON fp.id = t.financial_period_id AND t.status = 'Posted'
LEFT JOIN tbl_transaction_details td ON t.id = td.transaction_id
LEFT JOIN tbl_accounts a ON td.account_id = a.id
GROUP BY fp.id;

-- -- 7. Insert additional periods if needed (example)
-- INSERT INTO tbl_financial_periods (period_name, start_date, end_date, is_closed) VALUES
-- ('FY 2023-2024', '2023-04-01', '2024-03-31', TRUE),
-- ('FY 2025-2026', '2025-04-01', '2026-03-31', FALSE);

-- 8. Create stored procedure for period validation
DELIMITER //
CREATE PROCEDURE ValidateTransactionPeriod(
    IN p_transaction_date DATE,
    OUT p_valid BOOLEAN,
    OUT p_period_id INT,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE period_count INT DEFAULT 0;
    DECLARE period_closed BOOLEAN DEFAULT FALSE;
    
    -- Check if date falls in any period
    SELECT COUNT(*), id, is_closed
    INTO period_count, p_period_id, period_closed
    FROM tbl_financial_periods
    WHERE p_transaction_date BETWEEN start_date AND end_date
    LIMIT 1;
    
    IF period_count = 0 THEN
        SET p_valid = FALSE;
        SET p_message = 'Transaction date does not fall within any financial period';
    ELSEIF period_closed = TRUE THEN
        SET p_valid = FALSE;
        SET p_message = 'Cannot create transaction in closed financial period';
    ELSE
        SET p_valid = TRUE;
        SET p_message = 'Valid period found';
    END IF;
END //
DELIMITER ;

-- 9. Create function to get current active period
DELIMITER //
CREATE FUNCTION GetCurrentPeriodId() 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE period_id INT DEFAULT NULL;
    
    SELECT id INTO period_id
    FROM tbl_financial_periods
    WHERE CURDATE() BETWEEN start_date AND end_date
    AND is_closed = FALSE
    LIMIT 1;
    
    RETURN period_id;
END //
DELIMITER ;

-- 10. Create trigger to auto-assign period to transactions
DELIMITER //
CREATE TRIGGER tr_auto_assign_period
    BEFORE INSERT ON tbl_transactions
    FOR EACH ROW
BEGIN
    IF NEW.financial_period_id IS NULL THEN
        SET NEW.financial_period_id = GetCurrentPeriodId();
    END IF;
END //
DELIMITER ;