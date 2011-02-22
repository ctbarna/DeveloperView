<?php
session_start();

//grab the current directory and store as a var
$cwd = getcwd();

//pages which do not require the user to be logged in
$public_pages = array(
	"$cwd/index.php",
	"$cwd/tags.php",
	"$cwd/usage.php",
	"$cwd/browse.php",
	"$cwd/progress.php",
	"$cwd/mass_tag.php"
);

if ((!isset($_SESSION['admin']) | !$_SESSION['admin']) && !in_array($_SERVER['SCRIPT_FILENAME'],$public_pages)) {
	header('Location: login.php');
	die();
}

//set a backend global so we don't load PHProxy
$backend = true;
require_once('../config.php');
if (!isset($title)) $title = "Admin";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="../css/admin.css" type="text/css" media="all" />
<script type='text/javascript' src='<?php echo DV_ROOT; ?>js/jquery.min.js'></script>
<script type='text/javascript' src='<?php echo DV_ROOT; ?>js/jquery.autocomplete.min.js'></script>
<link rel='stylesheet' href='../css/jquery.autocomplete.css' type='text/css'>
<script><?php foreach (get_tags() as $tag) $tags[] = $tag['Tag'];  echo 'var tags = ["' . implode('","',$tags) . '"]'; ?></script>
<script>
	$(document).ready(function(){
		$("#target-tag").autocomplete(tags, {
			multiple: true,
			autoFill: true
		});
	});
</script>
	<title><?php echo $title; ?> | DeveloperView Administrative Dashboard</title>
</head>
<body>
<div id='container'>
	<div id='header'>
		<h1><a href='./'>DeveloperView Administrative Dashboard</a></h1>
		<?php if ($_SESSION['admin']) { ?>
		<div id='logout'><a href='logout.php'>Log Out</a></div>
		<?php } else { ?>
		<div id='logout'><a href='login.php'>Log in</a></div>
		<?php } ?>
	</div>
	<div id='content'>
		<h2><?php echo $title; ?></h2>
		
