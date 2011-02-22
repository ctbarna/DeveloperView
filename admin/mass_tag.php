<?php
/***
 * Set up the ability to tag groups of pages.
 * @author Chris Barna
 * @package DV
 ***/
$title = "Batch Tagging";
$backend = true;
include("../config.php");
include("header.php");

// Office and Bureau prefixes
$sql = "SELECT subsite_title, subsite_url FROM `subsites`";
$result = mysql_query($sql);

$b_and_os = array();

while ($row = mysql_fetch_assoc($result)) {
	$b_and_os[$row['subsite_title']] = $row['subsite_url']; 
}

// Set the search string.
if (isset($_GET["match"])) {
	$match = array("full" => $_GET["ob"].$_GET["match"],
				   "ob" => $_GET['ob'],
				   "subdir" => $_GET["match"]);
} else if (isset($_POST['match'])){
	$match = array("full" => $_POST["ob"].$_POST["match"],
				   "ob" => $_POST['ob'],
				   "subdir" => $_POST["match"]);
}

// When POST is set, do the tagging.
if (isset($_POST["tagIt"])) {
	if($_POST['user'] == '') {
		print "Username empty.";
	} else {
		// Split the tags into an array
		$tags = explode(',',urldecode($_POST['target-tag']));
		
		// Iterate through the pages and then through the tags adding each tag in.
		foreach($tags as $tag) {

			// Check for length==0
			if (strlen($tag)==0) continue;			
				
			//Lookup new tagID, or create if necessary
			$tagID = get_tag_by_name(trim($tag));	
			if (!$tagID) $tagID = add_tag (trim($tag));

			foreach ($_POST['tagIt'] as $page) {
				// Add the tag.
				add_tag_to_page($tagID,$page,$_POST['user']);
					
			}

			// Insert a row into the `tags_batches` for auditing/deleting tag batches.
			$sql = "INSERT INTO `tags_batches` (DateTime, TagID, Pages, User) VALUES ('".date("Y-m-d H:i:s")."', ".$tagID.", '".serialize($_POST['tagIt'])."', '".$_POST['user']."')";
			echo $sql;
			$result = mysql_query($sql);
		}

		$flash = "Added ".count($tags). " tags to ".count($_POST['tagIt'])." pages.";
	}
} else if ($_GET['delete']) {
	// Delete the tag batch.
	// Select the batch by ID to match with the `tags-pages` data.
	$sql = "SELECT * FROM `tags_batches` WHERE ID = ".$_GET['batch_id'];
	$result = mysql_query($sql);
	$row = mysql_fetch_row($result);

	// Delete the tags.
	$sql = "DELETE FROM `tags-pages` WHERE TagID = ".$row[2]." AND TimeStamp='".$row[1]."'";
	$result = mysql_query($sql);
	
	// Delete the batch record.
	$sql = "DELETE FROM `tags_batches` WHERE ID = ".$_GET['batch_id'];
	$result = mysql_query($sql);
	echo "Batch deleted successfully.";
}
?>
<style type="text/css">
th {
	background:#0F2A96;
	color:white;
	padding:5px;
}
</style>

<script type="text/javascript">
$(document).ready(function() {
	$(".select-all").click(function () {
		$("input:checkbox").attr("checked", true);
		return false;
	});

	$(".select-none").click(function() {
		$("input:checkbox").attr("checked", false);
		return false;
	});
	
	$("input#user").focus(function () {
		$("#user").val('');
	
	});

	$("#results-table tr:even").css("background", "lightBlue");

	$("#scroll-top a").click(function () {
		$("html,body").animate({
			scrollTop:0 
		}, 1000);
		return false;
	})
});
</script>
<?php
if (isset($flash)) {
	print $flash;
}
?>
<form action="mass_tag.php" method="get">
<div id="search-form" class="form-row">
<label for="match">Search for URLs to Tag :</label>

<select name="ob" id="ob">
<?php foreach ($b_and_os as $label => $value) { ?>
	<option value="<?php echo $value; ?>"<?php if ( !empty($match['ob']) && $match['ob'] == $value) echo ' SELECTED'; ?>><?php echo $value ?> </option>
<?php } ?>
</select>

<input type="text" name="match" size="40" <?php if (isset($match)) echo "value='".$match["subdir"]."'"; ?> /><input type="submit" value="Search" />
</div>
<div class="form-row">
<div class="form-label">&nbsp;</div>
<div class="form-field"></div>
</div>

</form>

<?php
if (isset($_SESSION['admin'])) {
?>
<h3>Tag Batches</h3>
<ul>
<?php
$sql = "SELECT * FROM `tags_batches`";
$result = mysql_query($sql);

while ($row = mysql_fetch_assoc($result)) {
	$pages = unserialize($row['Pages']);
	echo "<li>".count($pages)." pages tagged with <strong>".get_tag($row["TagID"])."</strong> at ".date('g:i A \o\n F j, Y' , strtotime($row["DateTime"]))." by <strong>".$row["User"]."</strong> (<a href='mass_tag.php?delete=true&amp;batch_id=".$row["ID"]."'>x</a>)</li>";
}
?>
</ul>
<?php } ?>
<?php
if (isset($match)) {

$sql = "SELECT * from `pages` WHERE URL LIKE '". addslashes( $match["full"] ) ."%' LIMIT 10000";
$result = mysql_query($sql) or die("Error");

if (mysql_num_rows($result) == 0 ) {
	echo "No results found under ".$match["full"];
	continue;
}

?>
<div style="position:fixed; bottom:0; right:0;" id="scroll-top"><a href="#">Scroll to Top</a></div>
<form action="mass_tag.php" method="post">
<input type="hidden" name="match" value="<?php echo $match["subdir"]; ?>" />
<input type="hidden" name="ob" value="<?php echo $match["ob"];?>"
<h3>Tagging Information</h3>
<div id="tag-form">
<div class="form-row">
	<div class="form-label"><label for="user">User:</label></div>
	<div class='form-field'>
		<input type='text' name='user' id='user'<?php if (isset($_COOKIE['dv-user'])) echo " value='" . $_COOKIE['dv-user'] . "'";?>>@fcc.gov
	</div>
</div>
<div class="form-row"><div class="form-label"><label for="target-tag">Tags to Apply:</label><div style="font-size:9pt; color:#999;">Separated by commas: (e.g., most important, obsolete, consumer oriented)</div></div>
	<div class='form-field'>
		<input type='text' name='target-tag' id='target-tag' size='66' /> 
	</div>
</div>

<div class="form-row">
<div class='form-label'> &nbsp; 
	</div>
	<div class="form-field">
		<input type="submit" value="Apply Tags"/>
	</div>
</div>



<h3>Pages</h3>
<div class="pages-desc">Select pages to apply tags to. <a href="#" class="select-all">Select All</a>, <a href="#" class="select-none">Select None</a></div>

<div id="results">
<table id="results-table">
	<tr>
		<th>Tag?</th>
		<th>URL</th>
		<th>Current Tags</th>	
	</tr>

<?php foreach (mysql_array($result) as $row) { ?>
<tr>
<td style="text-align:center;"><input type="checkbox" id="tagIt" name="tagIt[]" value="<?php echo $row["ID"]; ?>" /></td>
	<td style="width:700px;"><div style="overflow:hidden; width:700px;"><a href="<?php echo DV_ROOT; ?>?url=<?php echo urlencode( $row['URL'] ); ?>"><?php echo $row['URL']; ?></a></div></td>
<?php $tags = get_tags_by_page($row["ID"]); $count = count($tags); $i=1;?>
<td style="width:200px"><?php foreach ($tags as $key => $value) { 
	print get_tag($key);
	if ($count>$i) print ", ";
	$i = $i+1;
} ?></td>
</tr>
<?php } ?>
</table>
</div>
<input type="submit" />
</form>
<?php } ?>
<?php include("footer.php"); ?>
