<?php
session_start();
$backend = true;
require_once("../config.php");
$msg = 'You must be logged in to access the DeveloperView Administrative Dashboard.';
if (isset($_GET['logout'])) $msg = 'You have sucessfully logged out.';

if ((isset($_SESSION['admin'])) && ($_SESSION['admin'] == true)) header('Location: index.php');
if (md5($_POST['pass']) == md5(DV_ADMIN)) {
	$_SESSION['admin'] = true;
	header('Location: index.php');
} else if ($_POST['pass']) {
	$msg = "Please enter a valid password.";
	
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="../css/admin.css" type="text/css" media="all" />
	<title>DeveloperView Login</title>
</head>
<body>
<div id='container'>
	<div id='content'>
		<h2 style='text-align:center'>DeveloperView Login</h2>
		<p style='color: red; font-weight: bold; text-align:center'><?php echo $msg; ?></p>
		<form method='post' style='text-align: center'>
			<label for='pass'>Password:</label> <input type='password' name='pass' id='pass'/> <input type='submit' value ='Login'/>
		</form>
	</div>
</div>
