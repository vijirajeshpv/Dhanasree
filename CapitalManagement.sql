-- =====================================================
-- SIMPLIFIED CAPITAL MANAGEMENT DATABASE SCHEMA
-- For JAYALAKSHMI ENTERPRISES
-- =====================================================

-- 1. Capital Transactions Log Table (Simple tracking)
CREATE TABLE IF NOT EXISTS `tbl_capital_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_type` enum('Capital Investment','Capital Withdrawal','Profit Transfer','Loss Transfer') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text,
  `transaction_date` date NOT NULL,
  `accounting_transaction_id` int(11),
  `created_by` int(11),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `fk_capital_accounting_transaction` (`accounting_transaction_id`),
  CONSTRAINT `fk_capital_transaction` FOREIGN KEY (`accounting_transaction_id`) REFERENCES `tbl_transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Ensure Essential Equity Accounts Exist
INSERT INTO `tbl_accounts` (`account_code`, `account_name`, `account_type`, `is_active`) VALUES
('3001', 'Owner Capital', 'Equity', 1),
('3002', 'Retained Earnings', 'Equity', 1),
('PL001', 'P&L Summary', 'Equity', 1)
ON DUPLICATE KEY UPDATE 
  `account_name` = VALUES(`account_name`),
  `is_active` = VALUES(`is_active`);

-- 3. Add Reference Sequences for Capital Transactions
INSERT INTO `tbl_reference_sequences` (`sequence_type`, `prefix`, `last_number`) VALUES
('CAP', 'CAP-', 0),
('DRAW', 'DRAW-', 0),
('PROFIT', 'PROFIT-', 0)
ON DUPLICATE KEY UPDATE 
  `prefix` = VALUES(`prefix`);

-- 4. Create Views for Capital Reports

-- Capital Summary View
CREATE OR REPLACE VIEW `vw_capital_summary` AS
SELECT 
    'Owner Capital' as account_name,
    '3001' as account_code,
    COALESCE(SUM(CASE WHEN td.account_id = 3001 THEN td.credit - td.debit ELSE 0 END), 0) as balance,
    'Capital' as category
FROM `tbl_transaction_details` td
JOIN `tbl_transactions` t ON td.transaction_id = t.id
WHERE t.status = 'Posted'
  AND td.account_id IN (3001, 3002)

UNION ALL

SELECT 
    'Retained Earnings' as account_name,
    '3002' as account_code,
    COALESCE(SUM(CASE WHEN td.account_id = 3002 THEN td.credit - td.debit ELSE 0 END), 0) as balance,
    'Retained Earnings' as category
FROM `tbl_transaction_details` td
JOIN `tbl_transactions` t ON td.transaction_id = t.id
WHERE t.status = 'Posted'
  AND td.account_id IN (3001, 3002);

-- Monthly Capital Activity View
CREATE OR REPLACE VIEW `vw_monthly_capital_activity` AS
SELECT 
    DATE_FORMAT(ct.transaction_date, '%Y-%m') as month_year,
    SUM(CASE WHEN ct.transaction_type = 'Capital Investment' THEN ct.amount ELSE 0 END) as investments,
    SUM(CASE WHEN ct.transaction_type = 'Capital Withdrawal' THEN ct.amount ELSE 0 END) as withdrawals,
    SUM(CASE WHEN ct.transaction_type = 'Profit Transfer' AND ct.amount > 0 THEN ct.amount ELSE 0 END) as profits_retained,
    SUM(CASE WHEN ct.transaction_type = 'Loss Transfer' OR (ct.transaction_type = 'Profit Transfer' AND ct.amount < 0) THEN ABS(ct.amount) ELSE 0 END) as losses_absorbed,
    COUNT(ct.id) as total_transactions
FROM `tbl_capital_transactions` ct
GROUP BY DATE_FORMAT(ct.transaction_date, '%Y-%m')
ORDER BY month_year DESC;

-- Capital Movement Analysis View (without running balance - will be calculated in application)
CREATE OR REPLACE VIEW `vw_capital_movement_analysis` AS
SELECT 
    ct.id,
    ct.transaction_date,
    ct.transaction_type,
    ct.amount,
    ct.description,
    t.reference_no,
    CASE 
        WHEN ct.transaction_type IN ('Capital Investment', 'Profit Transfer') AND ct.amount > 0 THEN ct.amount
        WHEN ct.transaction_type IN ('Capital Withdrawal', 'Loss Transfer') OR ct.amount < 0 THEN -ABS(ct.amount)
        ELSE 0
    END as net_amount,
    ct.created_at
FROM `tbl_capital_transactions` ct
LEFT JOIN `tbl_transactions` t ON ct.accounting_transaction_id = t.id
ORDER BY ct.transaction_date, ct.created_at;

-- 5. Create Indexes for Performance
-- CREATE INDEX `idx_capital_date_type` ON `tbl_capital_transactions` (`transaction_date`, `transaction_type`);
-- CREATE INDEX `idx_capital_amount` ON `tbl_capital_transactions` (`amount`);

-- 6. Add Constraints and Validations
ALTER TABLE `tbl_capital_transactions` 
ADD CONSTRAINT `chk_capital_amount_not_zero` CHECK (`amount` != 0);

-- 7. Create Stored Procedures for Capital Operations

DELIMITER $$

-- Procedure to get capital movement with running balances
CREATE PROCEDURE `sp_get_capital_movement_with_balances`(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_date DATE;
    DECLARE v_type VARCHAR(50);
    DECLARE v_amount DECIMAL(15,2);
    DECLARE v_description TEXT;
    DECLARE v_reference VARCHAR(50);
    DECLARE v_net_amount DECIMAL(15,2);
    DECLARE v_running_balance DECIMAL(15,2) DEFAULT 0;
    
    DECLARE cur CURSOR FOR 
        SELECT 
            ct.transaction_date,
            ct.transaction_type,
            ct.amount,
            ct.description,
            COALESCE(t.reference_no, '') as reference_number,
            CASE 
                WHEN ct.transaction_type IN ('Capital Investment', 'Profit Transfer') AND ct.amount > 0 THEN ct.amount
                WHEN ct.transaction_type IN ('Capital Withdrawal', 'Loss Transfer') OR ct.amount < 0 THEN -ABS(ct.amount)
                ELSE 0
            END as net_amount
        FROM `tbl_capital_transactions` ct
        LEFT JOIN `tbl_transactions` t ON ct.accounting_transaction_id = t.id
        WHERE ct.transaction_date BETWEEN p_start_date AND p_end_date
        ORDER BY ct.transaction_date, ct.created_at;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Get opening balance (transactions before start date)
    SELECT COALESCE(SUM(
        CASE 
            WHEN ct.transaction_type IN ('Capital Investment', 'Profit Transfer') AND ct.amount > 0 THEN ct.amount
            WHEN ct.transaction_type IN ('Capital Withdrawal', 'Loss Transfer') OR ct.amount < 0 THEN -ABS(ct.amount)
            ELSE 0
        END
    ), 0) INTO v_running_balance
    FROM `tbl_capital_transactions` ct
    WHERE ct.transaction_date < p_start_date;
    
    -- Create temporary table for results
    DROP TEMPORARY TABLE IF EXISTS temp_capital_movement;
    CREATE TEMPORARY TABLE temp_capital_movement (
        transaction_date DATE,
        transaction_type VARCHAR(50),
        amount DECIMAL(15,2),
        description TEXT,
        reference_number VARCHAR(50),
        net_amount DECIMAL(15,2),
        running_balance DECIMAL(15,2)
    );
    
    -- Insert opening balance row
    INSERT INTO temp_capital_movement VALUES (
        p_start_date, 'Opening Balance', 0, 'Opening Balance', '', 0, v_running_balance
    );
    
    -- Process transactions
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_date, v_type, v_amount, v_description, v_reference, v_net_amount;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        SET v_running_balance = v_running_balance + v_net_amount;
        
        INSERT INTO temp_capital_movement VALUES (
            v_date, v_type, v_amount, v_description, v_reference, v_net_amount, v_running_balance
        );
    END LOOP;
    CLOSE cur;
    
    -- Return results
    SELECT * FROM temp_capital_movement ORDER BY transaction_date;
    
    -- Clean up
    DROP TEMPORARY TABLE temp_capital_movement;
END$$
CREATE PROCEDURE `sp_get_capital_statement`(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    DECLARE opening_capital DECIMAL(15,2) DEFAULT 0;
    DECLARE opening_retained DECIMAL(15,2) DEFAULT 0;
    
    -- Calculate opening balances (as of day before start date)
    SELECT 
        COALESCE(SUM(CASE WHEN td.account_id = 3001 THEN td.credit - td.debit ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN td.account_id = 3002 THEN td.credit - td.debit ELSE 0 END), 0)
    INTO opening_capital, opening_retained
    FROM `tbl_transaction_details` td
    JOIN `tbl_transactions` t ON td.transaction_id = t.id
    WHERE t.status = 'Posted'
      AND t.transaction_date < p_start_date
      AND td.account_id IN (3001, 3002);
    
    -- Return opening balances
    SELECT 
        'Opening Balance' as description,
        p_start_date as transaction_date,
        'Opening' as transaction_type,
        opening_capital as capital_amount,
        opening_retained as retained_amount,
        (opening_capital + opening_retained) as total_equity;
    
    -- Return transactions for the period
    SELECT 
        ct.description,
        ct.transaction_date,
        ct.transaction_type,
        CASE WHEN ct.transaction_type IN ('Capital Investment', 'Capital Withdrawal') 
             THEN ct.amount ELSE 0 END as capital_amount,
        CASE WHEN ct.transaction_type IN ('Profit Transfer', 'Loss Transfer') 
             THEN ct.amount ELSE 0 END as retained_amount,
        ct.amount as transaction_amount,
        t.reference_no
    FROM `tbl_capital_transactions` ct
    LEFT JOIN `tbl_transactions` t ON ct.accounting_transaction_id = t.id
    WHERE ct.transaction_date BETWEEN p_start_date AND p_end_date
    ORDER BY ct.transaction_date, ct.created_at;
    
    -- Return closing balances
    SELECT 
        'Closing Balance' as description,
        p_end_date as transaction_date,
        'Closing' as transaction_type,
        COALESCE(SUM(CASE WHEN td.account_id = 3001 THEN td.credit - td.debit ELSE 0 END), 0) as capital_amount,
        COALESCE(SUM(CASE WHEN td.account_id = 3002 THEN td.credit - td.debit ELSE 0 END), 0) as retained_amount,
        COALESCE(SUM(CASE WHEN td.account_id IN (3001, 3002) THEN td.credit - td.debit ELSE 0 END), 0) as total_equity
    FROM `tbl_transaction_details` td
    JOIN `tbl_transactions` t ON td.transaction_id = t.id
    WHERE t.status = 'Posted'
      AND t.transaction_date <= p_end_date
      AND td.account_id IN (3001, 3002);
END$$

-- Procedure to get capital balances as of a specific date
CREATE PROCEDURE `sp_get_capital_balances_as_of`(
    IN p_as_of_date DATE
)
BEGIN
    SELECT 
        a.account_code,
        a.account_name,
        COALESCE(SUM(td.credit - td.debit), 0) as balance
    FROM `tbl_accounts` a
    LEFT JOIN `tbl_transaction_details` td ON a.id = td.account_id
    LEFT JOIN `tbl_transactions` t ON td.transaction_id = t.id
    WHERE a.account_type = 'Equity'
      AND a.is_active = 1
      AND (t.transaction_date <= p_as_of_date OR t.transaction_date IS NULL)
      AND (t.status = 'Posted' OR t.status IS NULL)
    GROUP BY a.id, a.account_code, a.account_name
    HAVING balance != 0
    ORDER BY a.account_code;
END$$

-- Function to calculate current capital balance from transactions
CREATE FUNCTION `fn_get_current_capital_balance`() 
RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE capital_balance DECIMAL(15,2) DEFAULT 0;
    
    SELECT COALESCE(SUM(
        CASE 
            WHEN ct.transaction_type IN ('Capital Investment', 'Profit Transfer') AND ct.amount > 0 THEN ct.amount
            WHEN ct.transaction_type IN ('Capital Withdrawal', 'Loss Transfer') OR ct.amount < 0 THEN -ABS(ct.amount)
            ELSE 0
        END
    ), 0) INTO capital_balance
    FROM `tbl_capital_transactions` ct;
    
    RETURN capital_balance;
END$$

-- Procedure to get capital statement for a period
CREATE FUNCTION `fn_get_total_equity`() 
RETURNS DECIMAL(15,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE total_equity DECIMAL(15,2) DEFAULT 0;
    
    SELECT COALESCE(SUM(td.credit - td.debit), 0)
    INTO total_equity
    FROM `tbl_transaction_details` td
    JOIN `tbl_transactions` t ON td.transaction_id = t.id
    JOIN `tbl_accounts` a ON td.account_id = a.id
    WHERE a.account_type = 'Equity'
      AND t.status = 'Posted';
    
    RETURN total_equity;
END$$

DELIMITER ;

-- 8. Insert Sample Data for Testing (Optional)
-- Uncomment below to add sample transactions

/*
-- Sample Capital Investment
INSERT INTO `tbl_capital_transactions` 
(`transaction_type`, `amount`, `description`, `transaction_date`) 
VALUES ('Capital Investment', 500000.00, 'Initial Business Capital', '2024-01-01');

-- Sample Profit Transfer
INSERT INTO `tbl_capital_transactions` 
(`transaction_type`, `amount`, `description`, `transaction_date`) 
VALUES ('Profit Transfer', 75000.00, 'FY 2023-24 Profit Retention', '2024-03-31');

-- Sample Capital Withdrawal
INSERT INTO `tbl_capital_transactions` 
(`transaction_type`, `amount`, `description`, `transaction_date`) 
VALUES ('Capital Withdrawal', 25000.00, 'Owner Drawing', '2024-06-15');
*/

-- 9. Create Triggers for Automatic Balance Updates

DELIMITER $

-- Trigger to ensure capital transactions are properly logged
CREATE TRIGGER `tr_capital_transaction_validation` 
BEFORE INSERT ON `tbl_capital_transactions`
FOR EACH ROW
BEGIN
    -- Ensure amount is not zero
    IF NEW.amount = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Capital transaction amount cannot be zero';
    END IF;
    
    -- Ensure future dates are not allowed beyond reasonable limit
    IF NEW.transaction_date > DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Transaction date cannot be more than 7 days in future';
    END IF;
END$$

-- Trigger to update transaction amounts to positive for withdrawals
CREATE TRIGGER `tr_normalize_withdrawal_amounts` 
BEFORE INSERT ON `tbl_capital_transactions`
FOR EACH ROW
BEGIN
    -- Ensure withdrawal amounts are stored as positive values
    IF NEW.transaction_type IN ('Capital Withdrawal', 'Loss Transfer') AND NEW.amount < 0 THEN
        SET NEW.amount = ABS(NEW.amount);
    END IF;
END$$

DELIMITER ;

-- 10. Grant Permissions (adjust user as needed)
-- GRANT SELECT, INSERT, UPDATE ON jayalakshmi_db.tbl_capital_transactions TO 'accounting_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE jayalakshmi_db.sp_get_capital_statement TO 'accounting_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE jayalakshmi_db.sp_get_capital_balances_as_of TO 'accounting_user'@'localhost';
-- GRANT EXECUTE ON FUNCTION jayalakshmi_db.fn_get_total_equity TO 'accounting_user'@'localhost';

-- 11. Create Summary Report Query Templates

-- Monthly Capital Summary Report
/*
SELECT 
    mca.month_year,
    mca.investments,
    mca.withdrawals,
    mca.profits_retained,
    mca.losses_absorbed,
    (mca.investments - mca.withdrawals + mca.profits_retained - mca.losses_absorbed) as net_change,
    mca.total_transactions
FROM vw_monthly_capital_activity mca
WHERE mca.month_year >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 12 MONTH), '%Y-%m')
ORDER BY mca.month_year DESC;
*/

-- Current Financial Position
/*
SELECT 
    cs.account_name,
    cs.balance,
    ROUND((cs.balance / total.total_equity) * 100, 2) as percentage_of_total
FROM vw_capital_summary cs
CROSS JOIN (
    SELECT fn_get_total_equity() as total_equity
) total
WHERE cs.balance != 0
ORDER BY cs.balance DESC;
*/

-- Capital Activity for Current Financial Year
/*
SELECT 
    ct.transaction_date,
    ct.transaction_type,
    ct.amount,
    ct.description,
    t.reference_no
FROM tbl_capital_transactions ct
LEFT JOIN tbl_transactions t ON ct.accounting_transaction_id = t.id
WHERE ct.transaction_date >= '2024-04-01'  -- Adjust for your financial year
ORDER BY ct.transaction_date DESC;
*/

-- 12. Maintenance Queries

-- Find orphaned capital transactions (without accounting entries)
/*
SELECT ct.id, ct.transaction_date, ct.transaction_type, ct.amount
FROM tbl_capital_transactions ct
LEFT JOIN tbl_transactions t ON ct.accounting_transaction_id = t.id
WHERE ct.accounting_transaction_id IS NOT NULL 
  AND t.id IS NULL;
*/

-- Reconcile capital account balances
/*
SELECT 
    'Owner Capital' as account,
    (SELECT balance FROM vw_capital_summary WHERE account_code = '3001') as view_balance,
    (
        SELECT COALESCE(SUM(td.credit - td.debit), 0)
        FROM tbl_transaction_details td
        JOIN tbl_transactions t ON td.transaction_id = t.id
        WHERE td.account_id = 3001 AND t.status = 'Posted'
    ) as calculated_balance
UNION ALL
SELECT 
    'Retained Earnings' as account,
    (SELECT balance FROM vw_capital_summary WHERE account_code = '3002') as view_balance,
    (
        SELECT COALESCE(SUM(td.credit - td.debit), 0)
        FROM tbl_transaction_details td
        JOIN tbl_transactions t ON td.transaction_id = t.id
        WHERE td.account_id = 3002 AND t.status = 'Posted'
    ) as calculated_balance;
*/

-- Clean up old temporary data (run periodically)
/*
DELETE FROM tbl_capital_transactions 
WHERE transaction_type = 'Profit Transfer' 
  AND description LIKE '%temporary%' 
  AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
*/

-- 13. Backup and Restore Templates

-- Backup capital data
/*
SELECT * FROM tbl_capital_transactions 
INTO OUTFILE '/backup/capital_transactions_backup.csv'
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"' 
LINES TERMINATED BY '\n';
*/

-- Create archive table for old data
/*
CREATE TABLE tbl_capital_transactions_archive LIKE tbl_capital_transactions;

INSERT INTO tbl_capital_transactions_archive 
SELECT * FROM tbl_capital_transactions 
WHERE transaction_date < '2023-04-01';  -- Archive data older than current FY
*/