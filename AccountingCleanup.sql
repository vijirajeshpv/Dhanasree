-- ===================================================
-- COMPLETE DATABASE CLEANUP SCRIPT
-- WARNING: This will delete ALL data from specified tables
-- ===================================================

-- DISABLE FOREIGN KEY CHECKS (temporary)
SET FOREIGN_KEY_CHECKS = 0;

-- 1. CLEAN EXISTING BUSINESS DATA (if you want fresh start)
-- ===================================================

-- Clean transaction-related data (DELETE instead of TRUNCATE due to foreign keys)
DELETE FROM tbl_transaction_details;
DELETE FROM tbl_transactions;

-- Clean gold loan data (DELETE instead of TRUNCATE to be safe)
DELETE FROM tbl_stock;
DELETE FROM tbl_gold_loan;

-- Clean repledge data  
DELETE FROM tbl_repledge;

-- Clean expense data
DELETE FROM tbl_expenses;

-- Keep customer and user data (comment out if you want to clean these too)
-- TRUNCATE TABLE tbl_customer;
-- TRUNCATE TABLE tbl_user;

-- 2. RESET AUTO INCREMENT COUNTERS
-- ===================================================

-- Reset transaction counters
ALTER TABLE tbl_transactions AUTO_INCREMENT = 1;
ALTER TABLE tbl_transaction_details AUTO_INCREMENT = 1;

-- Reset business counters
ALTER TABLE tbl_gold_loan AUTO_INCREMENT = 1;
ALTER TABLE tbl_stock AUTO_INCREMENT = 1;
ALTER TABLE tbl_repledge AUTO_INCREMENT = 1;
ALTER TABLE tbl_expenses AUTO_INCREMENT = 1;

-- Reset customer/user counters (uncomment if you cleaned these tables)
-- ALTER TABLE tbl_customer AUTO_INCREMENT = 1;
-- ALTER TABLE tbl_user AUTO_INCREMENT = 1;

-- 3. RESET REFERENCE SEQUENCES
-- ===================================================

UPDATE tbl_reference_sequences SET last_number = 0;

-- 4. CLEAN ACCOUNTING DATA ONLY (keep business structure)
-- ===================================================

-- If you only want to clean accounting entries, use this instead:
-- DELETE FROM tbl_transaction_details;
-- DELETE FROM tbl_transactions;
-- UPDATE tbl_gold_loan SET accounting_transaction_id = NULL, closing_accounting_id = NULL;
-- UPDATE tbl_repledge SET accounting_transaction_id = NULL;
-- UPDATE tbl_expenses SET accounting_transaction_id = NULL;

-- RE-ENABLE FOREIGN KEY CHECKS
SET FOREIGN_KEY_CHECKS = 1;

-- ===================================================
-- VERIFICATION QUERIES (Run after cleanup)
-- ===================================================

-- Check if tables are empty
SELECT 'tbl_transactions' as table_name, COUNT(*) as record_count FROM tbl_transactions
UNION ALL
SELECT 'tbl_transaction_details', COUNT(*) FROM tbl_transaction_details
UNION ALL  
SELECT 'tbl_gold_loan', COUNT(*) FROM tbl_gold_loan
UNION ALL
SELECT 'tbl_repledge', COUNT(*) FROM tbl_repledge
UNION ALL
SELECT 'tbl_expenses', COUNT(*) FROM tbl_expenses
UNION ALL
SELECT 'tbl_customer', COUNT(*) FROM tbl_customer
UNION ALL
SELECT 'tbl_user', COUNT(*) FROM tbl_user;

-- ===================================================
-- CLEANUP COMPLETE
-- ===================================================