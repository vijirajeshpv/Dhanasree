<?php
  include_once "inc/header.php";
  include_once "inc/sidebar.php";
?>

<div class="card">
  <div class="card-header">
    Borrower Information
  </div>
  <div class="card-body">
    	<h5 class="card-title">Borrower personal details</h5>
		<table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
	        <thead>
	            <tr>
	                <th>Name</th>
	               
	               
	                <th>mobile</th>
	                
	                <th>address</th>
	               
	                <th>Image</th>
	            </tr>
	        </thead>
	        <tfoot>
	            <tr>
	                <th>Name</th>
	               
	                
	                <th>mobile</th>
	                
	                <th>address</th>
	                
	                <th>Image</th>
	            </tr>
	        </tfoot>
	        <tbody>
	        	<?php 
	        		$all = $emp->viewBorrower();
	        		if ($all) {
	        			$i = 1;
	        			while ($row = $all->fetch_assoc()) {
	        				$i++;

	        	 ?>
	            <tr>
	                <td><?php echo $row['name']; ?></td>
	                
	               
	                <td><?php echo $row['mobile']; ?></td>
	                
	                <td><?php echo $row['address']; ?></td>
	                
	                <td><img style="width:80px;height:70px;" src="<?php echo $row['image']; ?>" alt="" ></td>

	            </tr>
				<?php 
	        			}
	        		}
				 ?>
	        </tbody>
	    </table>
	  </div>
	</div>

<?php
include_once "inc/footer.php";
?>