<?php
$filepath = realpath(dirname(__FILE__));
include_once ($filepath."/../libs/CrudOperation.php");
include_once ($filepath."/../libs/Session.php");
include_once ($filepath."/../helpers/Format.php");
/**
* Users class for registration and login, user profiles and others.
*/
class Employee
{
	private $db;
	private $fm;

	function __construct()
	{
		$this->db = new CrudOperation();
		$this->fm = new Format();
	}

public function findBorrowerByMobile($mobile) {
        // Implement the logic to search for the borrower by name
        // Perform the necessary database query and return the result
        // You can use SQL queries or an ORM depending on your application

        // Example implementation using mysqli:
        $query = "SELECT * FROM tbl_customer WHERE mobile= '$mobile'";
        $result = $this->db->query($query);

        return $result;
    }
	public function employeeReg($data){
		$name = $this->fm->validation($data['name']);
		$name = mysqli_real_escape_string($this->db->link, $name);

		$email = $data['email'];
		$email = mysqli_real_escape_string($this->db->link, $email);

	    $pass = $this->fm->validation(md5($data['pass']));
	    $pass = mysqli_real_escape_string($this->db->link, $pass);

		$role = $this->fm->validation($data['role']);
	    $role = mysqli_real_escape_string($this->db->link, $role);

	    //check empty value
	    if (empty($name) or empty($email) or empty($pass) or empty($role))
		{
			$msg = "<span class='text-danger'>Fields must not be empty !</span>";
			return $msg;
		}
		$ckemail = "SELECT * FROM tbl_user WHERE email='$email'";
		$result = $this->db->select($ckemail);
		if ($result != false) {
			$msg = "<span class='text-danger'>This email already registered !</span>";
			return $msg;
		}else{

			if ($role == 1) {
		    	$designation = "Varifier";
		    }elseif($role == 2){
		    	$designation = "Branch Officer";
		    }else{
		    	$designation = "Head Officer";
		    }

			 $sql = "INSERT INTO tbl_user(name,email,pass,designation,role) VALUES('$name','$email','$pass','$designation','$role')";
		    $inserted = $this->db->insert($sql);
		    if ($inserted) {
		    	$msg = "<span class='text-success'>Registered successfully !</span>";
			    return $msg;
		    }else{
		    	$msg = "<span class='text-danger'>Registration failed !</span>";
				return $msg;
		    }
		}
		
	}
	// //users login
	public function employeeLogin($data){
		$email = $data['email'];
		$email = mysqli_real_escape_string($this->db->link, $email);
		$pass = $this->fm->validation(md5($data['pass']));
	    $pass = mysqli_real_escape_string($this->db->link, $pass);
	    if (empty($email) or empty($pass))
		{
			$msg = "<span class='text-danger'>Fields must not be empty !</span>";
			return $msg;
		}else{
			$sql = "SELECT * FROM tbl_user WHERE email='$email' AND pass='$pass'";
			$result = $this->db->select($sql);
			if ($result != false) {
				$value = $result->fetch_assoc();
				Session::set("userlogin",true);
				Session::set("user_id",$value['id']);
				Session::set("name",$value['name']);
				Session::set("designation",$value['designation']);
				Session::set("role",$value['role']);
				header("Location: index.php");
			}else{
				$msg = "<span class='text-danger'>Email or password not matched !</span>";
				return $msg;
			}
		}
	}

	public function addBorrower($data, $file)
	{
			// 	//validation of borrower data
		$borrower_name = $this->fm->validation($data['borrower_name']);
		$borrower_name = mysqli_real_escape_string($this->db->link, $borrower_name);		

		$borrower_mobile = $data['borrower_mobile'];
		$borrower_mobile = mysqli_real_escape_string($this->db->link, $borrower_mobile);

		

	    $borrower_address = $this->fm->validation($data['borrower_address']);
	    $borrower_address = mysqli_real_escape_string($this->db->link, $borrower_address);

	    

		//take image information using super global variable $_FILES[];
		$permited  = array('jpg', 'jpeg', 'png', 'gif');
		$file_name = $file['image']['name'];
		$file_size = $file['image']['size'];
		$file_temp = $file['image']['tmp_name'];

		
		if (empty($borrower_name)  or empty($borrower_mobile)  or empty($borrower_address)  or empty($file_name))
		{
			$msg = "<span class='error'>Fields must not be empty !.</span>";
			return $msg;
		}else{

			$mobileSql = "SELECT * FROM tbl_customer WHERE mobile = '$borrower_mobile' ";
			$mobileresult = $this->db->select($mobileSql);
			if ($mobileresult) {
				$msg = "<span class='error'>Mobile number already exists !.</span>";
				return $msg;
			}else{
			//validate uploaded images
				$div = explode('.', $file_name);
				$file_ext = strtolower(end($div));
				$unique_image = substr(md5(time()), 0, 10).'.'.$file_ext;
				$uploaded_image = "admin/uploads/".$unique_image;
				
				if ($file_size >1048567) {
					$msg = "<span class='error'>Borrower not found !.</span>";
					return $msg;
				} elseif (in_array($file_ext, $permited) === false) {
					echo "<span class='error'>You can upload only:-"
					.implode(', ', $permited)."</span>";
				}else{
					move_uploaded_file($file_temp, $uploaded_image);
					
					$query = "INSERT INTO tbl_customer(name,mobile,address,image) 
					VALUES('$borrower_name','$borrower_mobile','$borrower_address','$uploaded_image')";

					$inserted = $this->db->insert($query);
					if ($inserted) {
						$msg = "<span class='success'>Borrower added successfully.</span>";
						return $msg;
					}else{
						$msg = "<span class='error'>Failed to insert.</span>";
						return $msg;
					}
			 	}
			 }
		}

	}

	public function viewBorrower()
	{
		//get all borrower data
		$sql = "SELECT * FROM tbl_customer  ORDER BY id DESC";
		$result = $this->db->select($sql);
		return $result;

	}
public function getCustomerDetails($b_id)
	{
		//get all borrower data
		$sql = "SELECT * FROM tbl_customer  where id=$b_id";
		$result = $this->db->select($sql);
		return $result;

	}

	public function findBorrower($nid)
	{
		//get all borrower data by nid
		$sql = "SELECT * FROM tbl_borrower WHERE nid='$nid'";
		$result = $this->db->select($sql);
		return $result;
	}

	public function findBorrowerById($id)
	{
		//get all borrower data by nid
		$sql = "SELECT * FROM tbl_borrower WHERE id='$id' ";
		$result = $this->db->select($sql);
		return $result;
	}
public function getLastInsertId(){
    return $this->db->getLastInsertId();
}



// update borrower details
public function updateBorrower($name,$mobile,$address,$image,$borrower_id)
{
$image=$_FILES['image'];
$mobile=$_FILES['mobile'];

if ($imageFile && $imageFile['size'] > 0) {

            $sql = "UPDATE tbl_customer SET name='$name', mobile='$mobile', address='$address', image='$image' WHERE id=$borrower_id";
        } else {
            $sql = "UPDATE tbl_customer SET name='$name', mobile='$mobile', address='$address' WHERE id=$borrower_id";
        }


 // $sql = "UPDATE tbl_customer  SET name='$name', mobile_number='$mobileNumber', address='$address' WHERE id=$id";

$result = $this->db->update($sql);
		return $result;
}
// delete borrower
public function deleteBorrower($mobile)
{
$sql = "DELETE FROM tbl_customer   WHERE mobile=$mobile";

$result = $this->db->delete($sql);
		return $result;
}
//end of Employee class
}
?>

