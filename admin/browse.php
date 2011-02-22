<?php
if ($_GET['tag']) {
	$querytag = urldecode($_GET['tag']);
	$title = "Browsing Pages Tagged &quot;$querytag&quot;";
} else {
	$querytag = false;
	$title = "Browse Tags";
}
include('header.php');
$tagID = get_tag_by_name($querytag);
$sql ="SELECT PageID, TagID, URL FROM `tags-pages` INNER JOIN `pages` ON `tags-pages`.pageID = pages.ID WHERE `tagID` = '$tagID'";
$pages = mysql_array(mysql_query($sql));
?>
<ul>
<?php
foreach ($pages as $page) { ?>
	<li><a href='../index.php?url=<?php echo $page['URL']; ?>'><?php echo $page['URL']; ?></a></li>
<?php } ?>
</ul>

<?php include('footer.php'); ?>
