<?php
  include_once "inc/header.php";
  include_once "inc/sidebar.php";
?>


<div class="card">
  <div class="card-header bg-secondary text-white">
    Borrower Information
  </div>
  <div class="card-body p-3">
    	<h5 class="card-title border-bottom pb-3">Borrower personal details</h5>
<?php
$name = "";
$b_id = "";
$mobile="";
$image="";		
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $searchKey = $_POST['key'];
    //list($searchName, $searchMobile) = explode(",", $searchKey);

    // Convert $searchMobile to integer
    $searchMobile = intval($searchKey);

    $br = $emp->findBorrowerByMobile($searchMobile);

    if ($br === false) {
        // Handle the case when there's an error in the query
        echo "Database error: " . $emp->db->error;
    } elseif ($br->num_rows > 0) {
        // Fetch the first row
        $row = $br->fetch_assoc();

        if ($row) {
            // Check if the expected keys exist in the row
            if (isset($row['name']) && isset($row['mobile']) && isset($row['id'])) {
                $name = $row['name'];
                $mobile = $row['mobile'];
                $b_id = $row['id'];

                // Process the data
                // echo "Name: $name, Mobile: $mobile, ID: $b_id<br>";
            } else {
                // Handle the case when the expected keys are not found
                echo "Invalid data structure in the result set";
            }
        } else {
            // Handle the case when no row is fetched
            echo "No data found for the specified criteria";
        }
    } else {
        // Handle the case when no borrower is found
        echo "User not found or not applicable for a loan";
    }
}	            

      //echo $mobile;
    // Perform the search query and display results
    $searchResults = $emp->findBorrowerByMobile($mobile);

    // Display search results to the user
    foreach ($searchResults as $result) {
       // echo "ID: {$result['id']}, Name: {$result['name']}, Mobile: {$result['mobile']}";
        // Add buttons for delete or update operations for each result
        echo '<a href="deleteborrower.php?id=' . $result['id'] . '">Delete</a>';
        echo '<a href="update.php?id=' . $result['id'] . '">Update</a>';
    }

?>
<form class="pt-4" action="<?php echo $_SERVER['PHP_SELF'];?>" method="POST" autocomplete="off">
                <div class="form-group row">
              <label for="inputBorrowerFirstName" class="text-right col-3 font-weight-bold col-form-label">Search borrower: </label>                      
              <div class="col-sm-6">
                  <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter Name of borrower" required>
              </div>
              <div class="col-2 pr-0">
                <input type="submit" class="btn btn-info w-100 " name="search" value="Search">
              </div>  
            </div>

          </form> 


<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $searchKey = $_POST['mobile'];
    $mobile = intval($searchKey);
    
    $br = $emp->deleteBorrower($mobile);

    if ($br === false) {
        // Handle the case when there's an error in the query
        echo "Database error: " . $emp->db->error;
    } else {
        // Check if any rows were affected (indicating a successful deletion)
        if ($br) {
            echo "The row deleted successfully!";
        } else {
            echo "The borrower does not exist or an error occurred during deletion.";
        }
    }
}
?>

<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data" name="myform" id="myform" autocomplete="off">
            </div>
<!--Added Borrower Name-->
            <div class="form-group row">
              <label for="borrower_name" class="text-right col-2 font-weight-bold col-form-label">Borrower Name</label>                      
              <div class="col-sm-9">
                  <input type="text"  name="borrower_name" class="form-control" value="<?php echo $name; ?>" >
              </div>
            </div>

            <!--Added Borrower ID-->
            <div class="form-group row">
              <label for="borrower_id" class="text-right col-2 font-weight-bold col-form-label">Borrower ID</label>                      
              <div class="col-sm-9">
                  <input type="text"  name="b_id" class="form-control" value="<?php echo $b_id; ?>" >
              </div>
            </div>     		
	<!--Added Mobile-->
           <div class="form-group row">
              <label for="mobile" class="text-right col-2 font-weight-bold col-form-label">Mobile Number</label>                      
              <div class="col-sm-9">
                  <input type="text"  name="mobile" class="form-control" value="<?php echo $mobile; ?>" >
              </div>
            </div>  			
	       <!--Added Image-->
            <div class="form-group row">
              <label for="image" class="text-right col-2 font-weight-bold col-form-label">Image</label>                      
              <div class="col-sm-9">
                <input type="text"  name="image" class="form-control" value="<?php echo $image; ?>" > 

              </div>
            </div> 
<div class="d-flex col-11 justify-content-end pr-0">
  <div class="col-3"><input type="submit" name="delete" class="btn btn-danger w-100 " value="Delete">    	<hr>		</div>
      <div class="col-3 pr-0"><input type="submit" name="update" class="btn btn-info w-100" value="update"> </div>
</div>
   
	</div>
	</div>
<?php
include_once "inc/footer.php";
?>