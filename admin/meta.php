<?php
$title = "Remove Datasources";
include('header.php');

$msg=false;
if (isset($_GET['action'])) $action = $_GET['action'];
else $action = '';

switch($action) {
	case 'confirm-remove-source':
		echo "Are you sure you want to permanently remove the source '<b>".urldecode($_GET['source'])."</b>'? ";
		echo "<a href='?action=remove-source&sourceID=".$_GET['sourceID']."'>Remove</a> | <a href='tags.php'>Cancel</a>";
	break;
	case 'remove-source':
		if (remove_source($_GET['sourceID']))
			$msg = "Datasource Removed";
	break;
}
?>
<ul>
<?php
 $sources = get_sources();
 foreach ($sources as $source) { ?>
	<li><?php echo $source['Name']; ?> created <?php echo date('h:ia m-d-Y',strtotime($source['Date'])); ?> [<a href='?action=confirm-remove-source&source=<?php echo $source['Name']; ?>&sourceID=<?php echo $source['ID']; ?>'>Remove</a>]</li>
 <?php } ?>
<?php if (!$sources) { ?>
	<li>No datasets found. <a href='import.php'>Add one</a>?</li>
<?php } ?>
</ul>

<?php include('footer.php'); ?>
