<?php
// test_accounting.php
// Place this in your project root (same level as index.php)

// Include the same files your project uses
include_once "libs/Session.php";
Session::init();

// Set a test user ID (use an actual user ID from your tbl_user table)
$_SESSION['user_id'] = 3;

// Include the accounting classes
// Adjust the path based on where you placed them
include_once "classes/accounting/AccountingCore.php";
include_once "classes/accounting/AccountingIntegration.php";

echo "<h2>Accounting Integration Test</h2>";

try {
    // Create instance
    $accounting = new AccountingIntegration();
    echo "<p style='color:green'>✓ Classes loaded successfully</p>";
    
    // Test 1: Simple Capital Injection
    echo "<h3>Test 1: Capital Injection of ₹10,000</h3>";
    
    $result = $accounting->postCapitalInjection(
        10000, 
        date('Y-m-d'), 
        'Initial test capital'
    );
    
    if (is_numeric($result)) {
        echo "<p style='color:green'>✓ Success! Transaction ID: $result</p>";
        
        // Use the same database class as AccountingCore
        $db = new CrudOperation();
        
        // Check the transaction
        $check_query = "SELECT 
                        t.transaction_date,
                        t.description,
                        t.reference_no,
                        t.status,
                        td.debit,
                        td.credit,
                        a.account_name
                       FROM tbl_transactions t
                       JOIN tbl_transaction_details td ON t.id = td.transaction_id
                       JOIN tbl_accounts a ON td.account_id = a.id
                       WHERE t.id = '$result'";
        
        $check_result = $db->select($check_query);
        
        if ($check_result) {
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
            echo "<tr style='background:#e0e0e0'>";
            echo "<th>Date</th><th>Account</th><th>Debit</th><th>Credit</th>";
            echo "</tr>";
            
            $total_debit = 0;
            $total_credit = 0;
            
            while ($row = $check_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['transaction_date']}</td>";
                echo "<td>{$row['account_name']}</td>";
                echo "<td align='right'>₹" . number_format($row['debit'], 2) . "</td>";
                echo "<td align='right'>₹" . number_format($row['credit'], 2) . "</td>";
                echo "</tr>";
                
                $total_debit += $row['debit'];
                $total_credit += $row['credit'];
            }
            
            echo "<tr style='background:#f0f0f0; font-weight:bold'>";
            echo "<td colspan='2'>Total</td>";
            echo "<td align='right'>₹" . number_format($total_debit, 2) . "</td>";
            echo "<td align='right'>₹" . number_format($total_credit, 2) . "</td>";
            echo "</tr>";
            echo "</table>";
            
            if ($total_debit == $total_credit) {
                echo "<p style='color:green'>✓ Transaction is balanced!</p>";
            } else {
                echo "<p style='color:red'>✗ Transaction is NOT balanced!</p>";
            }
        }
    } else {
        echo "<p style='color:red'>✗ Error: $result</p>";
    }
    
    // Test 2: Check if accounts exist
    echo "<h3>Test 2: Verify Chart of Accounts</h3>";
    
    $accounts_to_check = ['CASH001', 'CAPITAL001', 'LOANS001', 'INTEREST_INC001'];
    $all_good = true;
    
    foreach ($accounts_to_check as $code) {
        $acc_query = "SELECT * FROM tbl_accounts WHERE account_code = '$code'";
        $acc_result = $db->select($acc_query);
        
        if ($acc_result) {
            echo "<p style='color:green'>✓ Account $code exists</p>";
        } else {
            echo "<p style='color:red'>✗ Account $code NOT FOUND</p>";
            $all_good = false;
        }
    }
    
    if (!$all_good) {
        echo "<p style='color:orange'>⚠ Some accounts are missing. Please run the SQL to insert chart of accounts.</p>";
    }
    
    // Test 3: Check stored function
    echo "<h3>Test 3: Check get_account_id_by_code Function</h3>";
    
    $func_query = "SELECT get_account_id_by_code('CASH001') as account_id";
    $func_result = $db->select($func_query);
    
    if ($func_result) {
        $row = $func_result->fetch_assoc();
        echo "<p style='color:green'>✓ Function works! CASH001 = Account ID " . $row['account_id'] . "</p>";
    } else {
        echo "<p style='color:red'>✗ Function not working</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Cleanup</h3>";    
echo "<p>To remove test transactions, run this SQL:</p>";
echo "<pre style='background:#f0f0f0; padding:10px'>";
echo "DELETE FROM tbl_transaction_details WHERE transaction_id IN 
(SELECT id FROM tbl_transactions WHERE description LIKE '%test%');
DELETE FROM tbl_transactions WHERE description LIKE '%test%';";
echo "</pre>";

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>If all tests pass, the accounting integration is ready</li>";
echo "<li>Update ManageLoan.php to include accounting integration</li>";
echo "<li>Create the cash book interface</li>";
echo "<li>Set up daily interest calculation</li>";
echo "</ol>";
?>