<?php
include_once "classes/FixedAssetManager.php";

$assetManager = new FixedAssetManager();
$message = "";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $asset_id = intval($_POST['asset_id']);
    $disposal_date = $_POST['disposal_date'];
    $disposal_amount = floatval($_POST['disposal_amount']);
    $disposal_type = $_POST['disposal_type'];
    $disposal_remarks = $_POST['disposal_remarks'] ?? '';
    
    // Debug: Log the received data
    error_log("Dispose Asset - ID: $asset_id, Date: $disposal_date, Amount: $disposal_amount, Type: $disposal_type");
    
    if (!$asset_id || !$disposal_date || !$disposal_type) {
        $message = "error|Missing required information for asset disposal.";
        error_log("Dispose Asset Error - Missing data: ID=$asset_id, Date=$disposal_date, Type=$disposal_type");
    } else {
        // Get asset details before disposal
        $asset = $assetManager->getFixedAssetById($asset_id);
        
        if (!$asset) {
            $message = "error|Asset not found.";
            error_log("Dispose Asset Error - Asset ID $asset_id not found");
        } elseif ($asset['status'] != 'Active') {
            $message = "error|Only active assets can be disposed. Current status: " . $asset['status'];
            error_log("Dispose Asset Error - Asset ID $asset_id status is " . $asset['status']);
        } else {
            // Perform disposal
            $result = $assetManager->disposeAsset(
                $asset_id, 
                $disposal_date, 
                $disposal_amount, 
                $disposal_type, 
                $disposal_remarks
            );
            
            if ($result) {
                $message = "success|Asset '{$asset['asset_name']}' has been successfully disposed.";
                error_log("Dispose Asset Success - Asset ID $asset_id disposed successfully");
            } else {
                $message = "error|Failed to dispose asset. Please check error logs.";
                error_log("Dispose Asset Error - disposeAsset() returned false for ID $asset_id");
            }
        }
    }
} else {
    $message = "error|Invalid request method.";
    error_log("Dispose Asset Error - Invalid request method: " . $_SERVER['REQUEST_METHOD']);
}

// Redirect back with message
$redirect_url = isset($_POST['redirect']) ? $_POST['redirect'] : 'asset_list.php';

// Add message to session or URL parameter
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['asset_message'] = $message;

error_log("Dispose Asset - Redirecting to: $redirect_url with message: $message");

header("Location: $redirect_url");
exit;
?>