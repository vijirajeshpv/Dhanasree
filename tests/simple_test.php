<?php
// test_simple.php
// A simple test to verify accounting integration

// First, let's check where your classes are located
$possible_paths = [
    "classes/accounting/AccountingIntegration.php",
];

$found_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $found_path = $path;
        break;
    }
}

if (!$found_path) {
    die("Error: Cannot find AccountingIntegration.php. Please check the file path.<br>
         Create the directory structure: classes/accounting/<br>
         And place the accounting classes there.");
}

// Include necessary files
include_once "libs/Session.php";
Session::init();
$_SESSION['user_id'] = 3; // Set a user ID for testing

include_once $found_path;

echo "<h2>Simple Accounting Integration Test</h2>";

try {
    $accounting = new AccountingIntegration();
    echo "<p style='color:green'>✓ AccountingIntegration class loaded successfully</p>";
    
    // Test a simple transaction
    echo "<h3>Testing Capital Injection...</h3>";
    $result = $accounting->postCapitalInjection(
        10000, 
        date('Y-m-d'), 
        'Test capital'
    );
    
    if (is_numeric($result)) {
        echo "<p style='color:green'>✓ Transaction created successfully! ID: $result</p>";
        
        // Show the transaction
        include_once "libs/CrudOperation.php";
        $db = new CrudOperation();
        $query = "SELECT t.*, td.*, a.account_name 
                  FROM tbl_transactions t 
                  JOIN tbl_transaction_details td ON t.id = td.transaction_id 
                  JOIN tbl_accounts a ON td.account_id = a.id 
                  WHERE t.id = $result";
        
        $trans_result = $db->select($query);
        if ($trans_result) {
            echo "<table border='1' style='margin-top:10px'>";
            echo "<tr style='background:#f0f0f0'><th>Date</th><th>Description</th><th>Account</th><th>Debit</th><th>Credit</th></tr>";
            while ($row = $trans_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['transaction_date']}</td>";
                echo "<td>{$row['description']}</td>";
                echo "<td>{$row['account_name']}</td>";
                echo "<td>₹" . number_format($row['debit'], 2) . "</td>";
                echo "<td>₹" . number_format($row['credit'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color:red'>✗ Error: $result</p>";
        echo "<p>Possible issues:</p>";
        echo "<ul>";
        echo "<li>Check if Session is initialized properly</li>";
        echo "<li>Check if database connection is working</li>";
        echo "<li>Check if the accounts exist in tbl_accounts</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Directory Structure Required:</h3>";
echo "<pre>
your_project/
├── classes/
│   ├── accounting/
│   │   ├── AccountingCore.php
│   │   ├── AccountingIntegration.php
│   │   ├── AccountingReports.php
│   │   ├── AccountManager.php
│   │   └── AccountingHelper.php
│   └── ManageLoan.php
├── libs/
│   ├── Database.php
│   ├── Session.php
│   └── CrudOperation.php
└── test_simple.php (this file)
</pre>";
?>