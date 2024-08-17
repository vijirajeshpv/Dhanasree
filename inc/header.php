<?php
ob_start();
//code for cache-control
  header("Cache-Control: no-cache, must-revalidate");
  header("Pragma: no-cache"); 
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 
  header("Cache-Control: max-age=2592000");

	include_once "libs/Session.php";
  Session::init();
  Session::checkSession();
	include_once "helpers/Format.php";
	spl_autoload_register(function($class){
	    include_once "classes/".$class.".php";
	  });


	$ml = new ManageLoan();
	$emp = new Employee();

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Gold Loan Management</title>
	<link rel="stylesheet" type="text/css" href="assets/font-awesome/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="assets/css/perfect-scrollbar.min.css">
	<link rel="stylesheet" type="text/css" href="assets/flag-icon-css/css/flag-icon.min.css">
  <!-- DataTables CSS -->
  <link href="assets/css/dataTables.bootstrap4.min.css" rel="stylesheet">
  <link href="assets/css/dataTables.responsive.css" rel="stylesheet">
	<!-- main  + bootstrap css -->
  <link rel="stylesheet" type="text/css" href="assets/css/style.css">
  <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.css">
	<!-- custom css -->
	<link rel="stylesheet" type="text/css" href="assets/css/main.css">

	<link rel="shortcut icon" href="images/favicon.png" />

</head>

<body>
	<!-- start scroller container -->
	<div class=" container-scroller">
		<!-- partial:partials/_navbar.html -->
    <nav class="navbar navbar-default col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="bg-white text-center navbar-brand-wrapper">
        <a class="navbar-brand brand-logo" href="index.php"><img class="img-fluid" src="images/dhanasreeLogo.png" /></a>
        <a class="navbar-brand brand-logo-mini img-fluid" href="index.php"><img src="images/dhanasreeLogoSmall.png" alt="" style=""></a>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center">
        <button class="navbar-toggler navbar-toggler d-none d-lg-block navbar-dark align-self-center mr-3" type="button" data-toggle="minimize">
          <span class="navbar-toggler-icon"></span>
        </button>

        <ul class="navbar-nav ml-lg-auto d-flex align-items-center flex-row">
          <li class="nav-item">
            <span class="text-white logged-in-as">Logged in as </span><a class="nav-link d-inline " href="#"><?php echo Session::get("name");?></a>
            <!-- <i class="fa fa-th"> </i> -->
          </li>
          <?php
            if(isset($_GET['action']) && $_GET['action']=="logout"){
                Session::destroy();
                header("Location: signin.php");
            }
          ?>
          <li class="nav-item">
            <a class="nav-link " href="?action=logout"><i class="fa fa-sign-out"></i>Logout</a>
          </li>
          
        </ul>
        <button class="navbar-toggler navbar-dark navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>
    </nav>