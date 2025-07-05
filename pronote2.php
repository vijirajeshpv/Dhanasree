<?php 
ob_start(); // Start output buffering
include_once "inc/header.php";
include_once "inc/sidebar.php";

// Initialize variables
$name = $b_id = $mob = $addr = $documents = $outputDate = $pl_no = $loan_amount = $other_documents = "";
$interest = '';
$part_payment = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Part Payment Submission
    if (isset($_POST['submit_part_payment'])) {
        // Safely retrieve and validate form data
        $pl_no = htmlspecialchars(trim($_POST['pl_no'] ?? ''));
        $part_payment = htmlspecialchars(trim($_POST['interest'] ?? ''));
echo $pl_no;
        if (!empty($pl_no) && !empty($part_payment)) {
            // Update part payment
            $updated = $ml->pronotePartpayment($pl_no, $part_payment);

            if ($updated) {
                echo "<div class='alert alert-success'>Part payment updated successfully.</div>";
            } else {
                echo "<div class='alert alert-danger'>Failed to update part payment. Please try again.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>PL Number and Part Payment are required.</div>";
        }
    }

    // Handle Borrower Search
    if (isset($_POST['search'])) {
        $pl_no = htmlspecialchars(trim($_POST['key'] ?? ''));
echo $pl_no;
        if (!empty($pl_no)) {
            try {
                $br = $emp->findBorrowerPl($pl_no);
                if ($br && $row = $br->fetch_assoc()) {
                    $name = $row['name'] ?? '';
                    $b_id = $row['b_id'] ?? '';
                    $addr = $row['address'] ?? '';
                    $mob = $row['mobile'] ?? '';
                } else {
                    echo "<div class='alert alert-danger'>No borrower found with the provided PL Number.</div>";
                }
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Please enter a PL Number to search.</div>";
        }
    }
}
?>


<!-- Search Borrower Form -->
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" autocomplete="off">
    <div class="form-group row">
        <label for="key" class="text-right col-2 font-weight-bold col-form-label">Search Borrower: </label>
        <div class="col-sm-6">
            <input type="text" name="key" class="form-control" id="key" placeholder="Enter PL No" required>
        </div>
        <div class="col-sm-3">
            <input type="submit" class="btn btn-info" name="search" value="Search">
        </div>
    </div>
</form>

<!-- Loan Application Form -->
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" name="myform" id="myform" autocomplete="off">
    <div class="form-group row">
        <label for="borrower_name" class="text-right col-2 font-weight-bold col-form-label">Borrower Name</label>
        <div class="col-sm-9">
            <input type="text" name="borrower_name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" readonly>
        </div>
    </div>

   <input type="hidden" name="pl_no" id="pl_no" value="<?php echo htmlspecialchars($pl_no); ?>" required>


    <div class="form-group row">
        <label for="interest" class="text-right col-2 font-weight-bold col-form-label">Interest </label>
        <div class="col-sm-9">
            <input type="text" name="interest" class="form-control" id="interest" placeholder="Enter Part Payment" value="<?php echo htmlspecialchars($interest); ?>" required>
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-6">
            <input type="submit" name="submit_part_payment" class="btn btn-info pull-right" value="Submit">
        </div>
    </div>
</form>

<script>
// Handle selection of documents
function getItem() {
    var selectedItems = Array.from(document.getElementById("documents").options)
        .filter(option => option.selected)
        .map(option => option.value);
    document.getElementById("other_documents").value = selectedItems.join(", ");
}
</script>

<?php
include_once "inc/footer.php";
?>
