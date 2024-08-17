<?php
$filepath = realpath(dirname(__FILE__));
include_once ($filepath."/../libs/CrudOperation.php");
include_once ($filepath."/../helpers/Format.php");

/**
* Sample Class for photo uploading, insert data, update data and others.
*/
class ManageLoan
{
	private $db;
	private $fm;
	function __construct()
	{
		$this->db = new CrudOperation();
		$this->fm = new Format();
	}

	function showPath(){
		return realpath(dirname(__FILE__));
	}
	function dbcon(){
		return $this->db->link;
	}

	//loan application
	public function applyForLoan($data, $file)
	{
			// 	//validation of borrower data
		$b_id = $this->fm->validation($data['b_id']);

		$borrower_name = $this->fm->validation($data['borrower_name']);
                $gl_no = $this->fm->validation($data['gl_no']);
                
                 $item_description = $_POST['other_item_description'];
 // $item_description1 = $this->fm->validation(implode(", ", $item_description));
$item_description1=$this->fm->validation($data['other_item_description']);
                
	$loan_amount = $this->fm->validation($data['loan_amount']);

	$gross_weight = $this->fm->validation($data['gross_weight']);

		$stone_weight = $this->fm->validation($data['stone_weight']);

	$net_weight = $this->fm->validation($data['net_weight']);

	$market_value = $this->fm->validation($data['market_value']);
                 $currentDate = date("Y-m-d");
                 $status=0;
	$due_date=$this->fm->validation($data['due_date']);

		//take image information using super global variable $_FILES[];
		$permited  = array('jpg', 'jpeg', 'png', 'gif');
		$file_name = $file['image']['name'];
		$file_size = $file['image']['size'];
		$file_temp = $file['image']['tmp_name'];

		
		if (empty($b_id) or empty($borrower_name) or empty($item_description1) or empty($gross_weight) or empty($stone_weight) or empty($market_value) or empty($net_weight) or empty($loan_amount) or empty($due_date))
		{
			$msg = "<span class='error'>Fields must not be empty!</span>";
			return $msg;
		}else{
			//validate uploaded images
			$div = explode('.', $file_name);
			$file_ext = strtolower(end($div));
			$unique_image = substr(md5(time()), 0, 10).'.'.$file_ext;
			$uploaded_file = "admin/uploads/documents/".$unique_image;
			
			if ($file_size >10048567) {
				$msg = "<span class='error'>Borrower not found !</span>";
				return $msg;
			} elseif (in_array($file_ext, $permited) === false) {
				echo "<span class='error'>You can upload only:-"
				.implode(', ', $permited)."</span>";
			}else{
				move_uploaded_file($file_temp, $uploaded_file);
				
				$query = "INSERT INTO tbl_gold_loan(`gl_no`,`item_description`, `gross_weight`, `stone_weight`, `net_weight`, `market_value`, `loan_amnt`, `b_id`, `name`, `date`,`due_date`, `status`, `file`) 
				VALUES('$gl_no','$item_description1','$gross_weight','$stone_weight','$net_weight','$market_value','$loan_amount','$b_id','$borrower_name','$currentDate','$due_date','$status','$uploaded_file')";

$inserted = $this->db->insert($query);
if ($inserted) {
	$msg = "<span class='success'>Loan Application submitted successfully.</span>";
	return $msg;
} else {
	$msg = "<span class='error'>Failed to submit.</span>";
	return $msg;

}
		 	}

		}

	}

	


	

 // get opening date
public function getOpeningDate($gl_no)
{

$sql = "SELECT tbl_customer.*, tbl_gold_loan.date,tbl_gold_loan.*
			    FROM tbl_customer
				INNER JOIN tbl_gold_loan
				ON tbl_customer.id = tbl_gold_loan.b_id
				WHERE tbl_gold_loan.status = 0 ";

		$result = $this->db->select($sql);			
 

     if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc(); // Fetch the data as an associative array.
        return $row['date']; // Return the specific value you need.
    }

    // Handle the case when no rows are returned or other errors.
    return null;
}

// get gold loan details
public function getGoldLoanDetails($gl_no)
{
   // $query = "SELECT * FROM tbl_gold_loan WHERE gl_no = '$gl_no'";
$query="SELECT tbl_customer.name,tbl_customer.id, tbl_gold_loan.*
			    FROM tbl_customer
				INNER JOIN tbl_gold_loan
				ON tbl_customer.id = tbl_gold_loan.b_id
				WHERE tbl_gold_loan.gl_no ='$gl_no' AND tbl_gold_loan.status=0";
		
     $result = $this->db->select($query);

        return $result;
    
}
// get gold loan details
public function getRepledgeDetails1($gl_no,$status)
{
   // $query = "SELECT * FROM tbl_repledge WHERE tbl_repledge.finance_gl_no = '$gl_no'";
$query="SELECT tbl_repledge.*
			    FROM tbl_gold_loan
				INNER JOIN tbl_repledge
				ON tbl_gold_loan.gl_no = tbl_repledge.finance_gl_no
				WHERE tbl_repledge.finance_gl_no ='$gl_no' AND tbl_repledge.Status='$status'";
		
     $result = $this->db->select($query);

        return $result;
    
}

// get gold loan details
public function getGoldLoanDetailsBetweenDates($status,$start_date,$end_date)
{
   // $query = "SELECT * FROM tbl_gold_loan WHERE gl_no = '$gl_no'";
$query="SELECT tbl_customer.name,tbl_customer.id, tbl_gold_loan.*
			    FROM tbl_customer
				INNER JOIN tbl_gold_loan
				ON tbl_customer.id = tbl_gold_loan.b_id
				WHERE tbl_gold_loan.status='$status' AND tbl_gold_loan.date BETWEEN '$start_date' AND '$end_date' ";
		
     $result = $this->db->select($query);

        return $result;
    
}

// get repledge loan details


public function getRepledgeDetailsBetweenDates($status,$start_date,$end_date)
{
   // $query = "SELECT * FROM tbl_repledge WHERE gl_no = '$gl_no'";
$query="SELECT * from tbl_repledge
				
				WHERE tbl_repledge.status='$status' AND tbl_repledge.date BETWEEN '$start_date' AND '$end_date' ";
		
     $result = $this->db->select($query);

        return $result;
    
}






// get gold loan details using borrower id
public function getGoldLoanDetailsByBid($b_id,$status)
{
   // $query = "SELECT * FROM tbl_gold_loan WHERE b_id = '$b_id'";
$query="SELECT tbl_customer.name,tbl_customer.id, tbl_gold_loan.*
			    FROM tbl_customer
				INNER JOIN tbl_gold_loan
				ON tbl_customer.id = tbl_gold_loan.b_id
				WHERE tbl_gold_loan.b_id = '$b_id' and tbl_gold_loan.status='$status'";
		
     $result = $this->db->select($query);

        return $result;
    
}

public function getCustomerDetails($b_id)
{
   // $query = "SELECT * FROM tbl_customer WHERE id = '$b_id'";
$query="SELECT tbl_customer.*
			 FROM tbl_customer
				
				WHERE tbl_customer.id ='$b_id'";
		
     $result = $this->db->select($query);

        return $result;
    
}





public function getRepledgeDetails($bank_gl_no)
{
   // $query = "SELECT * FROM tbl_repledge WHERE bank_gl_no = '$bank_gl_no'";
$query="SELECT tbl_customer.*, tbl_repledge.*
			    FROM tbl_customer
				INNER JOIN tbl_repledge
				ON tbl_customer.id = tbl_repledge.customer_id
				WHERE tbl_repledge.bank_gl_no ='$bank_gl_no' AND tbl_repledge.Status=0";
		
     $result = $this->db->select($query);

        return $result;
    
}


// get gold loan status
public function getGoldLoanStatus()
{
   
$query="SELECT tbl_customer.name,tbl_customer.id, tbl_gold_loan.*
			    FROM tbl_customer
				INNER JOIN tbl_gold_loan
				ON tbl_customer.id = tbl_gold_loan.b_id
				WHERE tbl_gold_loan.status=0";
		
     $result = $this->db->select($query);

        return $result;
    
}

//  Function to update gold loan status to "closed"
function closeGoldLoan($gl_no) {
 $query = "UPDATE tbl_gold_loan SET closing_date = NOW(), status= 1 WHERE gl_no = '$gl_no'";
$updated = $this->db->update($query);
        return $updated;
		

}





//repledge application
	public function applyForRepledge($data,$file)
	{
			// 	//validation of borrower data
            if (!isset($data) || !is_array($data))
                {
                $msg = "<span class='error'>Data is missing or not in the expected            format.</span>";
          return $msg;
            }
          else
           {
                $bank_gl_no = $this->fm->validation($data['bank_gl_no']);
		
		$customer_name = $this->fm->validation($data['customer_name']);
                $finance_gl_no = $this->fm->validation($data['finance_gl_no']);                             
	        $amount_bank = $this->fm->validation($data['amount_bank']);

	
		$due_date = $this->fm->validation($data['due_date']);

	        $date = $this->fm->validation($data['date']);	
                 // $currentDate = date("Y-m-d");
                 $status=0;
                 }
		
if (empty($customer_name) or empty($amount_bank) or empty($due_date) or empty($date) or empty($finance_gl_no) or empty($bank_gl_no)) {
    $msg = "<span class='error'>Fields must not be empty!</span>";
    return $msg;
} else {
    $msg = "<span class='success'>ha ha</span>"; // Fixed the syntax here
}

$query = "INSERT INTO tbl_repledge(`bank_gl_no`, `date`, `due_date`, `finance_gl_no`, `Status`, `customer_name`, `amount_bank`)
          VALUES ('$bank_gl_no','$date','$due_date','$finance_gl_no','$status','$customer_name','$amount_bank')";

$inserted = $this->db->insert($query);

if ($inserted) {
    $msg = "<span class='success'>Repledge Application submitted successfully.</span>";
} else {
    $msg = "<span class='error'>Failed to submit.</span>";
}

return $msg; // Return the message outside the if-else block


		 	

	}

//  Function to update repledge status to "closed"
function closeRepledge($bank_gl_no) {
 $query = "UPDATE tbl_repledge SET due_date = NOW(), status= 1 WHERE bank_gl_no = '$bank_gl_no'";
$updated = $this->db->update($query);
        return $updated;
		

}
	// pay loan
	public function payLoan($data)
	{
		
        $gl_no = $this->fm->validation($data['gl_no']);

		$b_id = $this->fm->validation($data['b_id']);
				
		$net_weight = $this->fm->validation($data['net_weight']);

		$interest = $this->fm->validation($data['interest']);

		$pay_date = $this->fm->validation($data['pay_date']);

		$loan_amnt = $this->fm->validation($data['loan_amnt']);
		
		$total = $this->fm->validation($data['total']);
	
		//$paid_amount = $this->fm->validation($data['paid_amount']);

		//$remain_amount = $data['total_amount'] -$data['paid_amount'];
		
		//$fine = 0;
		//fine calculation needed field
		//if (isset($data['fine_amount'])) {
		//	$fine = $data['fine_amount'];
		//}

		$next_date = '0000-00-00';

		if (isset($data['next_date'])) {
			$next_date = $data['next_date'];
		}else{

			$next_date = strtotime('+30 days',strtotime($data['pay_date']));
			$next_date = date('Y-m-d', $next_date);
			var_dump($next_date);
		}

		if (empty($b_id) or empty($gl_no) or empty($loan_amnt) or empty($pay_date) or empty($total))
		{
			$msg = "<span style='color:red'>Error....!</span>";
			return $msg;
		}else{

			$query = "DELETE FROM tbl_gold_loan WHERE gl_no = '$gl_no' AND loan_id = '$loan_id'";

$deleted = $this->db->delete($query);
			
		}
		
	}

	


//end of ManageLoan class
}
?>