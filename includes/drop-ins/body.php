<?php
/*
Contents of this PHP file parsed and dropped into final HTML page immediately after <body> tag
(used to inject header, address bar, etc.)
*/

/* GRAB VARIABLES WE WILL NEED */
global $_script_base; //grab the base URL from PHProxy for absolute URLs
global $_script_url; //grab the base URL from PHProxy for absolute URLs
global $_flags; //grab the settings from config.php 
global $_frozen_flags; //grab the settings from config.php 
global $_config; //grab the settings from config.php 
global $_labels; //grab the settings from config.php 
global $_url; //grab the current URL

/* ON PAGE LOAD, CHECK TO SEE IF THE URL IS ALREADY IN THE DB, IF NOT ADD IT */
$query = array("url"=>$_url);
$data = array("url"=>$_url);
if (!mysql_exists("pages",$query)) $pageID = add_page($_url);
else $pageID = get_page_ID($_url);	

/* Workout for IE */
if (isset($_SERVER['HTTP_USER_AGENT']) && 
    (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
		$_flags['dv-fixed']				= 0;
?>

<!-- DeveloperView Body START -->
<div class='dv-header' id='dv-header'>
	<?php if ($_flags['include_form'] && !isset($_GET['nf'])) { ?>
			<form method="post" action="<?php echo $_script_url; ?>">
				<div id='address-container'>
					<label for="____<?php echo $_config['url_var_name']; ?>">
						<a href="<?php echo $_url; ?>">Address</a>:
					</label> 
					<input id="____<?php echo $_config['url_var_name']; ?>" type="text" size="40" name="<?php echo $_config['url_var_name']; ?>" value="<?php echo $_url; ?>" />
					<input type="submit" name="go" value="Go" />
				</div> <!-- #address-container -->
				<div id='options-container'>
					<div id='dv-options'>
					<?php foreach ($_flags as $flag_name => $flag_value) {
						if (!$_frozen_flags[$flag_name]) { ?>
						<input type="checkbox" name="<?php echo $_config['flags_var_name'] . '[' . $flag_name . ']"' . ($flag_value ? ' checked="checked"' : ''); ?> id="<?php echo $_config['flags_var_name'] . '[' . $flag_name . ']'; ?>"/>
						<label for='<?php echo $_config['flags_var_name'] . '[' . $flag_name . ']'; ?>'>
							<?php echo $_labels[$flag_name][0]; ?>
						</label> 
						<?php } ?>
					<?php } ?>
					 </div> <!-- #options -->
				</div> <!-- #options-container -->
			</form> 

	<?php } ?>	
	<div id='dv-branding'>
		<span id='big'>D</span>eveloper<span id='big'>V</span>iew
		<span id='beta'>prototype</span>		
	</div>
</div> <!-- #dv-header -->
<div id='dv-shadow'> </div>
<div class='dv-toggles' id='dv-toggles'>
	<div id='dv-tool-toggle'>
		<a href='#'>Page Info</a>
	</div> <!-- #dv-tool-toggle -->
	<div id='dv-divider'>
	|
	</div>
	<div id='dv-options-toggle'>
		<a href='#'>Settings</a>
	</div><!-- #options-toggle -->
</div>
<div id='dv-toggle-shadow'> </div>
<div id='dv-tools'>
	<div id='dv-border'>
		<div id='dv-tools-container'>
				<?php /* <div class='delicious' id='delicious'>
					<?php 
					$tags = get_delicious_tags();
					if (sizeof($tags) > 0) { ?>
						<ul class='tags' id='delicious-tags'>
							<li class='delicious-label'><b>Top Delicious Tags:</b></li>
							<?php foreach ($tags as $tag=>$count) { ?>
								<li><a href='http://www.delicious.com/tag/<?php echo $tag; ?>/'><?php echo $tag; ?> (<?php echo $count; ?>)</a></li>
							<?php } ?>
						</ul>
				<?php } ?>
				</div> <!-- #delicious -->
				*/ ?>
			<form id='dv-form'>
			<div id='loaderdiv'>
				<img src='<?php echo $_script_base; ?>img/loader.gif' id='loader'>
			</div>
				<input type='hidden' name='pageID' id='pageID' value='<?php echo $pageID; ?>'>
				<input type='hidden' name='pageUrl' id='pageUrl' value='<?php echo $_url; ?>'>
				<div class='form-row'>
				    <div class='form-label'>
				    	<label for='user'>User: </label>
				    </div>
				    <div class='form-field'>
				    	<input type='text' name='user' id='user'<?php if (isset($_COOKIE['dv-user'])) echo " value='" . $_COOKIE['dv-user'] . "'"; ?>>@fcc.gov
						<div id='username-missing'>
							Please enter a username
						</div>	
					</div>
		
				</div>
				<div class='form-row'>
				    <div class='form-label'>
				    	<label for='tags'>Tags:</label> 
				    </div>
				    <div class='form-field'>
				    <ul class='tags' id='tags'>
				    <?php foreach(get_tags_by_page($pageID) as $tag) { ?>
				    		<li id='tag[<?php echo $tag['TagID'];?>]'><abbr title='Added <?php echo fuzzy_time($tag['TimeStamp']); ?> by <?php echo get_user_name($tag['UserID'],TRUE); ?>'><?php echo get_tag($tag['TagID']); ?></abbr> [<a href='#' class='remove-tag' title='Remove Tag &quot;<?php echo get_tag($tag['TagID']); ?>&quot;'>X</a>]</li>
				    <?php } ?>
				    	</ul>
				    	<div class='instructions'>Add tags, separated by commas:<br/> (e.g., <a href='#' class='add-tag'>most important</a>, <a href='#' class='add-tag'>obsolete</a>, <a href='#' class='add-tag'>consumer oriented</a>)</div>
				    	<input type='text' name='tag' id='add-tag' size='30'/> <input type='button' value ='Add' class='button' id='add-tag-btn'>
				    </div>
				</div>
				<div class='form-row'>
					<div class='form-label'>
						Wiki:
					</div>
					<div class='form-field'>
						<a href='http://intranet.fcc.gov/wiki/index.php/<?php echo $_url; ?>' target='_BLANK'>Join the Discussion</a>
					</div>
				</div>
				<div class='form-row'>
					<div class='form-label'>
						B/O:
					</div>
					<div class='form-field'>
						<?php $ob = explode('/',$_url); ?>
						<a href='admin/progress.php?ob=<?php echo $ob[3]; ?>' target='_BLANK'>View Bureau/Office Progress</a>
					</div>
				</div>	
				<div class='form-row'>
				    <div class='form-label'>
				    	<label for='notes'>Notes:</label> 
				    </div>
				    <div class='form-field'>
				    	<ul class='notes' id='notes'>
				    	<?php foreach(get_notes($pageID) as $note) { ?> 
				    		<li id='note[<?php echo $note['ID'];?>]'><?php echo $note['Note']; ?> 
								<div class='remove-note-div'>
									[<a href='#' class='remove-note' title='Delete This Note'>X</a>]
								</div>
				    			<div class='note-info'> Added <?php echo fuzzy_time($note['TimeStamp']);?> 
				    				by <?php echo get_user_name($note['UserID'],TRUE); ?> 
				    			</div>
				    		</li>
				    	<?php } ?>
				    	</ul>
				    	<textarea name='note' id='note' rows='2' cols='50'/></textarea> <input type='button' class='button' value ='Add' id='add-note-btn'>
				    </div>
				</div>
				</form>
				<div class='clearfix'></div>
				
				<?php 
					$meta = get_meta($pageID);
					if ($meta) { ?>
				<div id='metadata'>
					<div id='meta-header'>Page Metadata</div>
					<?php foreach ($meta as $row) {
						echo "<div class='label'>" . $row['Key'] . ":</div><div class='field'>";
						if ($row['Value'] == intval($row['Value'])) echo number_format($row['Value']);
						else echo $row['Value'];
						echo "</div>";
					}
					?>
						<div id='loading' class='clearfix'>Retrieving the most recent Google Analytics data for this page...</div>
				<?php } ?>
				</div>
				<div class='clearfix'></div>

			</div> <!-- #dv-container -->
		</div> <!-- #dv-border -->
	</div> <!-- #dv-tools -->

<?php if (!isset($_COOKIE['no-help'])) { ?>
	<div id="help-bubble" class='dv-help-bubble'>
	  To begin, click "Page Info"
	  <div id="help-bubble-arrow-border" class='dv-help-bubble' ></div>
	  <div id="help-bubble-arrow" class='dv-help-bubble' ></div>
	</div>
<?php } ?>

<!-- DeveloperView Body END -->

