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
ALTER TABLE tbl_transactions ADD COLUMN created_by INT;
ALTER TABLE tbl_transactions ADD COLUMN approved_by INT;
ALTER TABLE tbl_transactions ADD COLUMN status ENUM('Draft', 'Posted', 'Cancelled') DEFAULT 'Draft';

-- Add this to tbl_transaction_details for better tracking:
ALTER TABLE tbl_transaction_details ADD COLUMN description VARCHAR(255);

