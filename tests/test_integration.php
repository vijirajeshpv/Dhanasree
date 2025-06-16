// test_integration.php
<?php
include_once "classes/accounting/AccountingIntegration.php";

$accounting = new AccountingIntegration();

// Test the account lookup
$cash_id = $accounting->getAccountId('CASH001');
echo "Cash Account ID: $cash_id\n";

// Test a simple transaction
$result = $accounting->postCapitalInjection(
    100000, 
    date('Y-m-d'), 
    'Initial capital'
);

if (is_numeric($result)) {
    echo "Success! Transaction ID: $result\n";
} else {
    echo "Error: $result\n";
}
?>