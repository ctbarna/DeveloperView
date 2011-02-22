<?php
$title = "Manage Tags";
include('header.php');

$msg=false;
if (isset($_REQUEST['action']) && $_SESSION['admin']) $action = $_REQUEST['action'];
else $action = '';

switch($action) {
	case 'confirm-remove-tag':
		echo "Are you sure you want to permanently remove the tag '<b>".urldecode($_GET['tag'])."</b>'? ";
		echo "<a href='?action=remove-tag&tagID=".$_GET['tagID']."'>Remove</a> | <a href='tags.php'>Cancel</a>";
	break;
	case 'remove-tag':
		if (remove_tag($_GET['tagID']))
			$msg = "Tag Removed";
	break;
	case 'add-tags':
		$tags = explode(',',$_POST['tags']);
		foreach ($tags as $tag) {
			if (!have_tag($tag)) add_tag($tag);
		}
		$msg = "Tags added";
	break;
	case 'migrate-tag':
	
		if (!isset($_GET['original-tag']) | !isset($_GET['target-tag'])) {
			$msg = "Please select the target and original tags";
			break;
		}
		
		//generate list of pages tagged with original tag
		$pages = mysql_array(mysql_select('tags-pages',array('tagID'=>$_GET['original-tag'])),false);
			
		//Breakup tags into array
		$tags = explode(',',urldecode($_GET['target-tag']));			
		
		foreach ($tags as $tag) {
		
			//If the tag is empty, skip the loop
			if (strlen($tag)==0) continue;			
			
			//Lookup new tagID, or create if necessary
			$tagID = get_tag_by_name(trim($tag));	
			if (!$tagID) $tagID = add_tag (trim($tag));
			
			foreach ($pages as $page)
				add_tag_to_page($tagID,$page['PageID'],$page['UserID'],$page['TimeStamp']);
				
			//Remove the old tag from pages
			mysql_query("DELETE FROM `tags-pages` WHERE `TagID` = '".$_GET['original-tag']."'");
			
		}		
		$msg = sizeof($tags) . " tag";
		if (sizeof($tags) != 1) $msg .= "s";
		$msg .= " migrated to " . sizeof($pages) . " page";
		if (sizeof($pages) != 1) $msg .= "s";
		
		//Delete original tag
		if (isset($_GET['delete-original-tag']))
			remove_tag($_GET['original-tag']);
	break;
}
$sort = 'Tag';
if ( !empty($_GET['sort']) ) 
	$sort = $_GET['sort'];
if ($sort == 'Tag') 
	$direction = 'ASC';
else 
	$direction = 'DESC';

$tags = get_tags($sort, $direction); 
?>
<?php if ($msg) echo "<div class='msg'>$msg</div>"; ?>
<?php if ($_SESSION['admin']) { ?>

<form method='post'>
<h3>Add Tags</h3>
<form action ='post'>
<input type='hidden' name='action' value='add-tags' />
<div class='instructions'>Add tags, separated by commas:</div>
<input type='text' name='tags' id='add-tag' size='50'/> <input type='submit' value ='Add' class='button' id='add-tag-btn'>
</form>
<form>
<h3>Migrate Tags</h3>
<input type='hidden' name='action' value='migrate-tag' />
<div class='instructions'>All pages marked with the 'Original Tag' will become associated with the 'target tag(s)' (separated by commas)</div>
<div class='form-row'>
	<div class='form-label'>
		<label for='original-tag'>Original Tag:</label> 
	</div>
	<div class='form-field'>
		<select name='original-tag' id='original-tag'> 
			<option></option>
			<?php foreach ($tags as $tag) { 
				if ($tag['Count'] == 0) continue;
			?>
				<option value='<?php echo $tag['ID']?>'><?php echo $tag['Tag']?> (<?php echo number_format($tag['Count']); ?>)</option>
			<?php } ?>
		</select>
	</div>
</div>

<div class='form-row'>
	<div class='form-label'>
		<label for='Target-tag'>Target Tag(s):</label> 
	</div>
	<div class='form-field'>
		<input type='text' name='target-tag' id='target-tag' size='66' /> 
	</div>
</div>

<div class='form-row'>
	<div class='form-label'>
		<label for='delete-original-tag'>Delete Original Tag After Migration</label>
	</div>
	<div class='form-field'>
		<input type='checkbox' name='delete-original-tag' id='delete-original-tag'>
	</div>
</div>
<div class='form-row'>
	<div class='form-label'> &nbsp; 
	</div>
	<div class='form-field'>
		<input type='submit' value='Migrate'>
	</div>
</div>
</form>
<?php } ?>
<h3>Existing Tags</h3>
<p style='font-size: 10pt;'><strong>Sort By:</strong> 
<?php if ($sort != 'Tag') { ?><a href='tags.php?sort=Tag'>Tag</a><?php } else { ?>Tag<?php } ?> 
<?php if ($sort != 'Count') { ?><a href='tags.php?sort=Count'>Count</a><?php } else { ?>Count<?php } ?>
</p>
<ul>
<?php foreach ($tags as $id=>$tag) { ?>
	<li><a href='browse.php?tag=<?php echo urlencode($tag['Tag']); ?>'><?php echo $tag['Tag']; ?></a> (<?php echo number_format($tag['Count']); ?> Page<?php if ($tag['Count'] !=1) echo "s"; ?>)
	<?php if ($_SESSION['admin']) { ?>
	[<a href='?action=confirm-remove-tag&tag=<?php echo urlencode($tag['Tag']); ?>&tagID=<?php echo $tag['ID']; ?>'>remove</a>]</li>
	<?php } ?>
<?php } ?>
</ul>
<?php include('footer.php'); ?>
