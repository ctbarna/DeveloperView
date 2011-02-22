<?php
/**
 * Lists URLs excluded from tagging statistics.
 * @author Chris Barna
 * @package DV
 *
 **/
$title = "Excluded Directories";
include("../config.php");
include("header.php");

$sql = "SELECT * FROM `exclusions`";
$result = mysql_query($sql);

?>
<ul>
<?php while ($excluded = mysql_fetch_assoc($result)) { ?>
<li><?php echo $excluded['URL']; ?> <!-- [<a href="excluded.php?action=delete-confirm&amp;id=<?php echo $excluded["ID"]; ?>">x</a>]--></li>
<?php } ?>
</ul>
<?php include("footer.php"); ?>
