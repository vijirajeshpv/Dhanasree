ALTER TABLE tbl_user ADD PRIMARY KEY (id);

-- Accounting transactions
CREATE TABLE tbl_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference_no VARCHAR(50),
    transaction_type ENUM('Receipt', 'Payment', 'Journal') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transaction details (double-entry accounting)
CREATE TABLE tbl_transaction_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    account_id INT NOT NULL,
    debit DECIMAL(12,2) DEFAULT 0,
    credit DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (transaction_id) REFERENCES tbl_transactions(id)
);

-- Chart of accounts
CREATE TABLE tbl_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Income', 'Expense') NOT NULL,
    parent_account_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (parent_account_id) REFERENCES tbl_accounts(id)
);

-- Financial periods
CREATE TABLE tbl_financial_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_closed BOOLEAN DEFAULT FALSE
);

-- Add these fields to your tbl_transactions for better audit trail:
ALTER TABLE tbl_transactions ADD COLUMN created_by INT NULL;
ALTER TABLE tbl_transactions ADD COLUMN approved_by INT NULL;
ALTER TABLE tbl_transactions ADD COLUMN status ENUM('Draft', 'Posted', 'Cancelled') DEFAULT 'Draft';

-- Add this to tbl_transaction_details for better tracking:
ALTER TABLE tbl_transaction_details ADD COLUMN description VARCHAR(255);

-- For tbl_transactions
CREATE INDEX idx_transaction_date ON tbl_transactions(transaction_date);
CREATE INDEX idx_reference_no ON tbl_transactions(reference_no);
CREATE INDEX idx_transaction_type ON tbl_transactions(transaction_type);
CREATE INDEX idx_status ON tbl_transactions(status);
CREATE INDEX idx_trans_date_status ON tbl_transactions(transaction_date, status);

-- For tbl_transaction_details
CREATE INDEX idx_transaction_id ON tbl_transaction_details(transaction_id);
CREATE INDEX idx_account_id ON tbl_transaction_details(account_id);
CREATE INDEX idx_td_composite ON tbl_transaction_details(transaction_id, account_id);

-- For tbl_accounts
CREATE INDEX idx_account_code ON tbl_accounts(account_code);
CREATE INDEX idx_account_type ON tbl_accounts(account_type);
CREATE INDEX idx_parent ON tbl_accounts(parent_account_id);

-- For tbl_financial_periods
CREATE INDEX idx_dates ON tbl_financial_periods(start_date, end_date);

-- 2. MISSING FOREIGN KEY CONSTRAINTS
-- =============================================
-- Add proper foreign key constraints for data integrity

-- For tbl_transaction_details (add account foreign key)
ALTER TABLE tbl_transaction_details 
ADD CONSTRAINT fk_account FOREIGN KEY (account_id) 
REFERENCES tbl_accounts(id) ON DELETE RESTRICT;

-- For tbl_transactions (if you have user table)
ALTER TABLE tbl_transactions 
ADD CONSTRAINT fk_created_by FOREIGN KEY (created_by) 
REFERENCES tbl_user(id) ON DELETE SET NULL;

ALTER TABLE tbl_transactions 
ADD CONSTRAINT fk_approved_by FOREIGN KEY (approved_by) 
REFERENCES tbl_user(id) ON DELETE SET NULL;

-- 3. MISSING TIMESTAMP FIELDS
-- =============================================
-- Add timestamps for audit trail

ALTER TABLE tbl_transactions 
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE tbl_accounts 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE tbl_financial_periods 
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN closed_at TIMESTAMP NULL DEFAULT NULL;


-- Pre-populate essential accounts for gold loan business
INSERT INTO tbl_accounts (id, account_code, account_name, account_type, parent_account_id, is_active) VALUES
(1001, 'CASH001', 'Cash in Hand', 'Asset', NULL, TRUE),
(1002, 'BANK001', 'Bank Account - Main', 'Asset', NULL, TRUE),
(1003, 'LOANS001', 'Gold Loans Receivable', 'Asset', NULL, TRUE),
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

-- 4. MISSING ACCOUNTING REFERENCE COLUMNS
-- =============================================
-- Link business transactions to accounting entries

-- For gold loans
ALTER TABLE tbl_gold_loan 
ADD COLUMN accounting_transaction_id INT DEFAULT NULL,
ADD COLUMN closing_accounting_id INT DEFAULT NULL,
ADD KEY idx_accounting_trans (accounting_transaction_id);

-- For repledge
ALTER TABLE tbl_repledge 
ADD COLUMN accounting_transaction_id INT DEFAULT NULL,
ADD KEY idx_accounting_trans (accounting_transaction_id);

-- If you have tbl_expenses
ALTER TABLE tbl_expenses 
ADD COLUMN accounting_transaction_id INT DEFAULT NULL;


-- 6. USEFUL VIEWS FOR REPORTING
-- =============================================

-- View for Cash Book
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

-- View for Outstanding Loans
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

-- 7. STORED PROCEDURES (Optional but Useful)
-- =============================================

DELIMITER $$

-- Get account balance with date
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

-- 8. DEFAULT FINANCIAL PERIOD
-- =============================================
-- Insert current financial year if not exists
INSERT INTO tbl_financial_periods (period_name, start_date, end_date) 
SELECT 'FY 2024-2025', '2024-04-01', '2025-03-31'
WHERE NOT EXISTS (
    SELECT 1 FROM tbl_financial_periods 
    WHERE start_date = '2024-04-01' AND end_date = '2025-03-31'
);

-- 9. DATA INTEGRITY CONSTRAINTS
-- =============================================

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


-- Add a view for Trial Balance (very useful)
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

/*
INSERT INTO tbl_transactions (transaction_date, description, reference_no, transaction_type, status, created_by) 
VALUES ('2024-04-01', 'Opening Balances', 'OB-20240401', 'Journal', 'Posted', 1);

SET @trans_id = LAST_INSERT_ID();

-- Sample opening balance entries (adjust amounts as needed)
INSERT INTO tbl_transaction_details (transaction_id, account_id, debit, credit, description) VALUES
(@trans_id, 1001, 50000.00, 0, 'Opening Cash Balance'),        -- Cash in Hand (Debit)
(@trans_id, 1002, 100000.00, 0, 'Opening Bank Balance'),       -- Bank Account (Debit)
(@trans_id, 3001, 0, 150000.00, 'Opening Capital Balance');    -- Owner Capital (Credit)
*/


-- A. Add a sequence/counter table for reference numbers
CREATE TABLE IF NOT EXISTS tbl_reference_sequences (
    sequence_type VARCHAR(20) PRIMARY KEY,
    last_number INT DEFAULT 0,
    prefix VARCHAR(10),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Initialize sequences
INSERT INTO tbl_reference_sequences (sequence_type, prefix, last_number) VALUES
('JOURNAL', 'JV-', 0),
('RECEIPT', 'RCT-', 0),
('PAYMENT', 'PMT-', 0),
('LOAN', 'GL-', 0),
('REPLEDGE', 'RPL-', 0);

-- B. Add a settings table for accounting configuration
CREATE TABLE IF NOT EXISTS tbl_accounting_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255),
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default settings
INSERT INTO tbl_accounting_settings (setting_key, setting_value, description) VALUES
('default_interest_rate', '21', 'Default annual interest rate (%)'),
('compound_interest_base', '1.21', 'Base for compound interest calculation'),
('financial_year_start', '04-01', 'Financial year start month-day'),
('currency_symbol', '₹', 'Currency symbol'),
('decimal_places', '2', 'Decimal places for amounts');

-- 5. UPDATE YOUR ACCOUNTING CLASS CONSTANTS
-- =============================================
-- Since you're using string account codes instead of numeric IDs,
-- update the AccountingIntegration.php constants:

/*
In AccountingIntegration.php, change:
const CASH_ACCOUNT = 1001;
To:
const CASH_ACCOUNT = 'CASH001';

And similarly for all other account constants.
Or better yet, create a method to get account ID by code:
*/

-- Helper function to get account ID by code
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

CREATE INDEX idx_accounting_transaction_id ON tbl_expenses(accounting_transaction_id);
