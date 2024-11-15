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
    // Validation of borrower data
    $b_id = $this->fm->validation($data['b_id']);
    $borrower_name = $this->fm->validation($data['borrower_name']);
    $gl_no = $this->fm->validation($data['gl_no']);
    
    // Handle item description - check if it's an array before using implode
    $item_description1 = isset($data['other_item_description']) && is_array($data['other_item_description']) 
        ? implode(", ", $data['other_item_description']) 
        : $this->fm->validation($data['other_item_description']); // if it's a string, assign it directly

    $loan_amount = $this->fm->validation($data['loan_amount']);
    $gross_weight = $this->fm->validation($data['gross_weight']);
    $stone_weight = $this->fm->validation($data['stone_weight']);
    $net_weight = $this->fm->validation($data['net_weight']);
    $market_value = $this->fm->validation($data['market_value']);
    $currentDate = date("Y-m-d");
    $status = 0;
    
    // Handle due date conversion
    $due_date1 = $this->fm->validation($data['due_date']);
    $dateObject = DateTime::createFromFormat('d-m-Y', $due_date1);

    if ($dateObject) {
        $due_date = $dateObject->format('Y-m-d');
    } else {
        throw new Exception("Invalid date format in due_date.");
    }

    // Handle file upload
    $permited = array('jpg', 'jpeg', 'png', 'gif');
    $file_name = $file['image']['name'];
    $file_size = $file['image']['size'];
    $file_temp = $file['image']['tmp_name'];

    if (empty($b_id) || empty($borrower_name) || empty($item_description1) || empty($gross_weight) || empty($stone_weight) || empty($market_value) || empty($net_weight) || empty($loan_amount) || empty($due_date)) {
        $msg = "<span class='error'>Fields must not be empty!</span>";
        return $msg;
    } else {
        if (!empty($file_name)) {
            $div = explode('.', $file_name);
            $file_ext = strtolower(end($div));
            $unique_image = substr(md5(time()), 0, 10) . '.' . $file_ext;
            $uploaded_file = "admin/uploads/documents/" . $unique_image;

            if ($file_size > 10048567) {
                $msg = "<span class='error'>File size should be less than 10MB!</span>";
                return $msg;
            } elseif (!in_array($file_ext, $permited)) {
                $msg = "<span class='error'>You can upload only: " . implode(', ', $permited) . "</span>";
                return $msg;
            } else {
                move_uploaded_file($file_temp, $uploaded_file);
            }
        } else {
            $uploaded_file = '';  // Handle case if no file is uploaded
        }

        // Insert into tbl_gold_loan
        $query = "INSERT INTO tbl_gold_loan(`gl_no`, `item_description`, `gross_weight`, `stone_weight`, `net_weight`, `market_value`, `loan_amnt`, `b_id`, `name`, `date`, `due_date`, `status`, `file`) 
                  VALUES('$gl_no', '$item_description1', '$gross_weight', '$stone_weight', '$net_weight', '$market_value', '$loan_amount', '$b_id', '$borrower_name', '$currentDate', '$due_date', '$status', '$uploaded_file')";
        
        $inserted = $this->db->insert($query);
        
        if ($inserted) {
            // Insert into tbl_gold_stock (Stock Register)
            $stock_query = "INSERT INTO tbl_stock(`gl_no`,`date`, `gross_weight`, `stone_weight`, `net_weight`, `b_id`) 
                            VALUES('$gl_no','$currentDate', '$gross_weight', '$stone_weight', '$net_weight',  '$b_id')";
            $stock_inserted = $this->db->insert($stock_query);

            if ($stock_inserted) {
                $msg = "<span class='success'>Loan Application and Stock Register updated successfully.</span>";
                return $msg;
            } else {
                $msg = "<span class='error'>Loan submitted, but failed to update stock register.</span>";
                return $msg;
            }
        } else {
            $msg = "<span class='error'>Failed to submit loan application.</span>";
            return $msg;
        }
    }
}

	
	public function payLoan($data)
	{

		$b_id = $this->fm->validation($data['b_id']);
		
		$loan_id = $this->fm->validation($data['loan_id']);

		$payment = $this->fm->validation($data['payment']);

		$pay_date = $this->fm->validation($data['pay_date']);

		$current_inst = $this->fm->validation($data['current_inst']);
		
		$remain_inst = $this->fm->validation($data['remain_inst']);
	
		$paid_amount = $this->fm->validation($data['paid_amount']);

		$remain_amount = $data['total_amount'] -$data['paid_amount'];
		
		$fine = 0;
		//fine calculation needed field
		if (isset($data['fine_amount'])) {
			$fine = $data['fine_amount'];
		}

		$next_date = '0000-00-00';

		if (isset($data['next_date'])) {
			$next_date = $data['next_date'];
		}else{

			$next_date = strtotime('+30 days',strtotime($data['pay_date']));
			$next_date = date('Y-m-d', $next_date);
			var_dump($next_date);
		}

		if (empty($b_id) or empty($loan_id) or empty($payment) or empty($pay_date) or empty($current_inst) or empty($paid_amount) )
		{
			$msg = "<span style='color:red'>Error....!</span>";
			return $msg;
		}else{

			$query = "INSERT INTO tbl_payment(b_id,loan_id,pay_amount,pay_date,current_inst,remain_inst,fine) 
				VALUES('$b_id','$loan_id','$payment','$pay_date','$current_inst','$remain_inst','$fine')";

			$inserted = $this->db->insert($query);
			if ($inserted) {

				$updateSql = "UPDATE tbl_loan_application SET amount_paid = '$paid_amount', amount_remain ='$remain_amount', current_inst='$current_inst', remain_inst='$remain_inst', next_date='$next_date' WHERE id = '$loan_id' ";

				$up = $this->db->update($updateSql);

				$msg = "<span class='success'>Loan payment submitted successfully.</span>";
				return $msg;
			}else{
				$msg = "<span class='error'>Failed to submit.</span>";
				return $msg;
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

public function applyForPersonalLoan($data, $file)
{
    // Validation of borrower data
    $b_id = $this->fm->validation($data['b_id']);
    $borrower_name = $this->fm->validation($data['borrower_name']);
    $loan_amount = $this->fm->validation($data['loan_amount']);
    $loan_percent = $this->fm->validation($data['loan_percent']);
    $installments = $data['installments'];
    $total_amount = $this->fm->validation($data['total_amount']);
    $borrower_emi = $this->fm->validation($data['borrower_emi']);
    
    $date = date("Y-m-d");
    $status = 0;

    // Convert due_date to 'yyyy-mm-dd' format for database insertion
    $due_date1 = $this->fm->validation($data['due_date']);
    $dateObject = DateTime::createFromFormat('d-m-Y', $due_date1);

    if ($dateObject) {
        $due_date = $dateObject->format('Y-m-d');
    } else {
        throw new Exception("Invalid date format in due_date.");
    }

    // Take image information using super global variable $_FILES
    $permited  = array('doc', 'docx', 'pdf');
    $file_name = isset($file['borrower_files']['name']) ? $file['borrower_files']['name'] : null;
    $file_size = isset($file['borrower_files']['size']) ? $file['borrower_files']['size'] : null;
    $file_temp = isset($file['borrower_files']['tmp_name']) ? $file['borrower_files']['tmp_name'] : null;

    // Check for empty fields
    if (empty($b_id) || empty($borrower_name) || empty($loan_amount) || empty($loan_percent) || empty($installments) || empty($total_amount) || empty($borrower_emi) || empty($next_date) || empty($due_date) || empty($file_name)) {
        $msg = "<span class='error'>Fields must not be empty!</span>";
        return $msg;
    } else {
        // Validate uploaded files
        $div = explode('.', $file_name);
        $file_ext = strtolower(end($div));
        $unique_image = substr(md5(time()), 0, 10) . '.' . $file_ext;
        $uploaded_file = "admin/uploads/documents/" . $unique_image;

        if ($file_size > 10048567) {
            $msg = "<span class='error'>File size must be less than 10MB.</span>";
            return $msg;
        } elseif (in_array($file_ext, $permited) === false) {
            $msg = "<span class='error'>You can upload only: " . implode(', ', $permited) . "</span>";
            return $msg;
        } else {
            move_uploaded_file($file_temp, $uploaded_file);

            $query = "INSERT INTO tbl_loan_application(b_id, name, expected_loan, loan_percentage, installments, total_loan, emi_loan, amount_remain, remain_inst, due_date, files) 
                      VALUES('$b_id', '$borrower_name', '$loan_amount', '$loan_percent', '$installments', '$total_amount', '$borrower_emi', '$total_amount', '$installments', '$due_date', '$uploaded_file')";

            $inserted = $this->db->insert($query);
            if ($inserted) {
                $msg = "<span class='success'>Loan Application submitted successfully.</span>";
                return $msg;
            } else {
                $msg = "<span class='error'>Failed to submit the application.</span>";
                return $msg;
            }
        }
    }
}


public function getApprovedLoanNotPaid($b_id)
	{
		//get all borrower data
		$sql = "SELECT tbl_customer.*, tbl_loan_application.*
			    FROM tbl_customer
				INNER JOIN tbl_loan_application
				ON tbl_customer.id = tbl_loan_application.b_id
				WHERE tbl_loan_application.status = 3 AND tbl_loan_application.b_id = '$b_id' AND tbl_loan_application.total_loan > tbl_loan_application.amount_paid
		 		ORDER BY tbl_loan_application.id DESC";
		$result = $this->db->select($sql);
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


public function getDueDetails($current_date_str)
{
    $query = "SELECT tbl_gold_loan.gl_no,tbl_customer.name, tbl_customer.mobile, tbl_gold_loan.date, tbl_gold_loan.item_description, tbl_gold_loan.net_weight, tbl_gold_loan.loan_amnt, tbl_gold_loan.due_date 
              FROM tbl_customer
              INNER JOIN tbl_gold_loan
              ON tbl_customer.id = tbl_gold_loan.b_id 
              WHERE tbl_gold_loan.due_date < '$current_date_str' 
              AND tbl_gold_loan.status = 0";
    
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

public function getStockRegisterStatus()
{
   
$query="SELECT tbl_gold_loan.item_description,tbl_stock.* from tbl_gold_loan INNER JOIN tbl_stock
			    
				
				ON tbl_stock.gl_no = tbl_gold_loan.gl_no";
		
     $result = $this->db->select($query);

        return $result;
    
}
public function netStockRegister()
{
   
$query="SELECT tbl_gold_loan.item_description,tbl_stock.* from tbl_gold_loan INNER JOIN tbl_stock
			    
				
				ON tbl_stock.gl_no = tbl_gold_loan.gl_no where tbl_gold_loan.status=0";
		
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
	

	


//end of ManageLoan class
}
?>