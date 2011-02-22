<?php
 
/**
 * DeveloperView.
 *
 * DeveloperView relies on open-source proxy PHProxy to handle requests for web pages.  PHProxy rewrites all URLs in the target page to be routed through itself and
 * allows the user to control several aspects of their browsing experience such as the ability to remove JavaScript or images, or to block cookies from the target page. 
 * As PHProxy proceses the user"s requested page, DeveloperView injects the output of one PHP file immediately before the conclusion of the document <head> tag to provide 
 * support for JavaScript and style sheets, and the output of another PHP file immediately after the start of the document <body> tag to include the user interface.  Both \
 * inclusions are done via a Regular Expression and a delivered to the user at the time the request page is sent.
 *
 * DeveloperView seeks to create an open-source, light-weight, scalable means of providing website stake holders with the ability to view, organize, and most importantly  
 * collaborate in the management of website content and development. Specifically, DeveloperView supports three sources of information: data collected through 
 * DeveloperView itself (such as tags, notes, or a repository of site URLs), data requested from a third-party source (such as displaying the most popular user-generated 
 * Delicious tags for the current page), and to pull additional page informatoin from existing, linked databases.
 * 
 * @version 1.0a
 * @package DeveloperView
 *
 */

/**
 *
 * Include config.php which holds all our user-defined settings.
 *
 */
include('config.php'); 

//If the no frame flag is set, redirect them directly to our proxy
if ( isset($_GET['nf']) ) {
	include('proxy.php');
	exit();
}

/* ON PAGE LOAD, CHECK TO SEE IF THE URL IS ALREADY IN THE DB, IF NOT ADD IT */
$query = array('url'=>$_url);
$data = array('url'=>$_url);
if (!mysql_exists('pages',$query)) $pageID = add_page($_url);
else $pageID = get_page_ID($_url);	

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
	<head>
		<title>DeveloperView</title>
		<link rel="stylesheet" href="<?php echo $_script_base; ?>css/dv.css" type="text/css">
		<link rel="stylesheet" href="<?php echo $_script_base; ?>css/jquery.autocomplete.css" type="text/css">
		<script type="text/javascript" src="<?php echo $_script_base; ?>js/jquery.min.js"></script>
		<script type="text/javascript" src="<?php echo $_script_base; ?>js/jquery.cookie.js"></script>
		<script type="text/javascript" src="<?php echo $_script_base; ?>js/jquery.autocomplete.min.js"></script>
		<script type="text/javascript" src="js/jquery.bgiframe.js"></script>
		<script type="text/javascript" src="js/jquery.dimensions.js"></script>
		<script>
		//Prevent recursive iframes
		if (top.location != self.location) top.location = self.location;
		</script>
		<script src="<?php echo $_script_base; ?>js/dv.min.js" language="javascript" type="text/javascript"></script>
		<script><?php foreach (get_tags() as $tag) $tags[] = $tag['Tag'];  echo 'var tags = ["' . implode('","',$tags) . '"]'; ?></script>
	</head>
	<body>
		<div id="container" height="100%" width="100%">
			<iframe src="proxy.php?url=<?php echo $_GET['url']; ?>" id="main-view" frameBorder="0" marginheight="0" marginwidth="0"></iframe>
		</div>
		<!-- NOTE TO SELF MAKE HELP BUBBLE !!!! --> 
	
	
	<div id="menu">
		<div id="panels">
			<form>
			<input type="hidden" name="pageID" id="pageID" value="<?php echo $pageID; ?>" />
			<input type="hidden" name="pageURL" id="pageURL" value="<?php echo $_url; ?>" />		
			<div id="tags" class="drawer">
				<h3>Tags</h3>
				<div class="content">
					<ul>
					<?php foreach(get_tags_by_page($pageID) as $tag) { ?>
							<li id="tag[<?php echo $tag['TagID'];?>]">
								<abbr title="Added <?php echo fuzzy_time($tag['TimeStamp']); ?> by <?php echo get_user_name($tag['UserID'],TRUE); ?>"><?php echo get_tag($tag['TagID']); ?></abbr> 
								[<a href="#" class="remove-tag" title="Remove Tag &quot;<?php echo get_tag($tag['TagID']); ?>&quot;">X</a>]
							</li>
					<?php } ?>
					</ul>
					<div class="instructions">Add tags, separated by commas: <span class='username-missing'>Please enter a username</span></div>
					<input type="text" name="tag" id="add-tag"/> <input type="button" value ="Add" class="button" id="add-tag-btn">
				</div>
			</div>	
			<div id="notes" class="drawer">
				<h3>Notes</h3>
				<div class="content">
					<ul>
					<?php foreach(get_notes($pageID) as $note) { ?> 
						<li id="note[<?php echo $note['ID'];?>]"><?php echo $note['Note']; ?> 
							<div class="note-info"> Added <?php echo fuzzy_time($note['TimeStamp']);?> 
								by <?php echo get_user_name($note['UserID'],TRUE); ?> 
								<div class="remove-note-div">
									[<a href="#" class="remove-note" title="Delete This Note">X</a>]
								</div>
							</div>
						</li>
					<?php } ?>
					</ul>
					<div class="instructions">Add a note: <span class='username-missing'>Please enter a username</span></div>
					<textarea name="note" id="note" rows="2" cols="50"/></textarea> 
					<input type="button" class="button" value ="Add" id="add-note-btn">
				</div>
			</div>

			<div id="metadata" class="drawer">
				<h3>Page Statistics</h3>
				<div class="content">
				<?php 
				$meta = get_meta($pageID);
				if ($meta) { ?>
						<?php foreach ($meta as $row) {
							echo '<div class="label">' . $row['Key'] . ':</div><div class="field">';
							if ($row['Value'] == intval($row['Value'])) echo number_format($row['Value']);
							else echo $row['Value'];
							echo '</div>';
						}
						$ob = explode('/',$_url); 
						?>
					<div id="loading">Retrieving the most recent Google Analytics data for this page...</div>
				<?php } ?>
				</div>
			</div>	
		</div>
		<div id="menu-bar">
			<div id='address-div'>
				<label for="url">Address:</label><input type="text" name="url" id="url" value="<?php echo $_url; ?>"/> <input type="button" id="address-go" class="button" value="Go" />
			</div>
			<div id='user-div'>
				<label for="username">Username:</label><input type="text" name="username" id="username" value="<?php if (! empty($_COOKIE['dv-user'])) echo $_COOKIE['dv-user']; ?>"/>@FCC.gov
			</div>
			<div id='branding'>
				DeveloperView
			</div>
		</div>
	</div>
	</body>
</html>
