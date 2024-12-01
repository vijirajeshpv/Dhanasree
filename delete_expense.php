<?php
require_once 'classes/ExpenseTracker.php';
$tracker = new ExpenseTracker();

// Check if an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$expense_id = $_GET['id'];

// Attempt to delete the expense
try {
    if ($tracker->deleteExpense($expense_id)) {
        // Set a session message for successful deletion
        session_start();
        $_SESSION['message'] = "Expense deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        // Set a session message for deletion failure
        session_start();
        $_SESSION['message'] = "Failed to delete expense.";
        $_SESSION['message_type'] = "error";
    }
} catch (Exception $e) {
    // Set a session message for any unexpected errors
    session_start();
    $_SESSION['message'] = "An error occurred: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
}

// Redirect back to the dashboard
header("Location: expenses_dashboard.php");
exit();
?>