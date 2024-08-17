<?php
  include_once "inc/header.php";
  include_once "inc/sidebar.php";
include_once "inc/title.php";
ob_end_clean(); 

require('fpdf186/fpdf.php'); 
  
// Instantiate and use the FPDF class  
$pdf = new FPDF(); 
  
//Add a new page 
$pdf->AddPage(); 
$customer_name=$_GET['borrower_name'];
$b_id=$_GET['b_id'];
echo $customer_name;
// $gl_no=$_GET['gl_no'];
 $date=date("Y-m-d");
// $item_description=$_GET['item_description'];
// $net_weight=$_GET['net_weight'];
// $loan_amount=$_GET['loan_amount'];
  



 $customer_name=$_POST['borrower_name'];
 $b_id=$_POST['b_id'];
// $gl_no=$_POST['gl_no'];
 $date=date("Y-m-d");
$item_description=$_POST['item_description'];
$net_weight=$_POST['net_weight'];
$loan_amount=$_POST['loan_amount'];

 $br = $emp->findBorrowerByName($customer_name);

$br = $emp->findBorrowerByName($customer_name);
$gl_no = $emp->getLastInsertId();

if ($br) {
    $row = $br->fetch_assoc();
    $name = $row['name'];
    $b_id = $row['id'];
$cmr = $emp->getCustomerDetails($b_id);
if($cmr)
{
 $row = $cmr->fetch_assoc();
$addr=$row['address'];
$mob=$row['mobile'];
$image=$row['image'];

    // Prepare report data
    $report_data = [
        'Name' => $name,
        'Borrower ID' => $b_id,
       'Address' => $addr,
        'Mobile' => $mob,
        'GL Number' => $gl_no,
        'Date' => $date,
        'Item Description' => implode(", ", $item_description),
        'Net Weight' => $net_weight,
        'Loan Amount' => $loan_amount,
       'Customer'=> $image
    ];



$customer_name=$_POST['borrower_name'];
$br = $emp->findBorrowerByName($customer_name);
$row = $br->fetch_assoc();
 $b_id = $row['id'];
$cmr = $emp->getCustomerDetails($b_id);
if($cmr)
{
 $row = $cmr->fetch_assoc();
$addr=$row['address'];
$mob=$row['mobile'];
$image=$row['image'];
}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Application Report</title>
    <style>
        table {
            border-collapse: collapse;
            width: 50%;
            margin: 20px;
        }

        table, th, td {
            border: 0px solid black;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
<h1 align="middle">Jayalakshmi Enterprises<br>Kml                       No:<br>Manapaady,Thanisserry P.O.</h1>
    <h3>Loan Application Report</h3>
    <table border="0">
<tr>
<td></td>
<td><img src="'admin/uploads/<?php  echo $image ?>'" width='100'></td>
</tr>
        <?php foreach ($report_data as $label => $value): ?>

            <tr>
                <td><?php echo $label; ?></td>
                <td><?php echo $value; ?></td>
            </tr>
        <?php endforeach; ?>
	<tr>
		<td><?php echo $customer_name; ?></td>
		<td></td>
</tr>

    </table>
</body>
</html>

<?php

} else {
    echo "<span class='text-center' style='color:red'>Borrower not found or not applicable for a loan</span>";
}
?>
  


     
