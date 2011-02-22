<?php
die();

$title = "Tag Cleanup";
include('header.php');

$sql = "SELECT *, COUNT(*) as Count from `tags-pages` GROUP BY TagID, PageID ORDER BY Count DESC";
$dups = mysql_array(mysql_query($sql),false);

foreach ($dups as $dup) {
	
	if ($dup['Count'] == 1) break;
	
	//$sql = "SELECT * FROM `tags-pages` WHERE `TagID` = '" . $dup['TagID'] . "' AND `PageID` = '" . $dup['PageID'] . "' ORDER BY TimeStamp ASC LIMIT 1";
	//$original = mysql_row_array(mysql_query($sql));
	
	//$sql = "DELETE FROM `tags-pages` WHERE `TagID` = '" . $dup['TagID'] . "' AND `PageID` = '" . $dup['PageID'] . "' AND `TimeStamp` != '" . $original['TimeStamp'] . "'";
	//$sql = "DELETE FROM `tags-pages` WHERE `TagID` = '" . $dup['TagID'] . "' AND `PageID` = '" . $dup['PageID'] . "' LIMIT " . ($dup['Count'] - 1);
	
	//echo $sql . "<BR />";
	//mysql_query($sql);
	
}

include('footer.php');
?>