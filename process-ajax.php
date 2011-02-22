<?php
/** 
 * Page recieves and processes AJAX form data
 * 
 * @project DeveloperView
 * @subproject Ajax
 *
*/

/**
 *
 * Include config.php which holds all our user-defined settings.
 *
 */

$backend = true;
include('config.php');
$output = array();

//clean URLs to make them uniform
$_GET['url'] = clean_url($_GET['url']);

//lookup pageID if only URL given
if ( ! empty( $_GET['url'] ) && $_GET['url'] != '/' ) {
	$query = array('url'=>$_GET['url']);
	if (!mysql_exists('pages',$query)) $pageID = add_page($_GET['url']);
	else $pageID = get_page_ID($_GET['url']);	
	$_GET['pageID'] = $pageID;
}
	
switch($_GET['action']) {
	
	/**
	 * Add Tag
	 *	
	 * Recieves via get:
	 * "user": User's name
	 * "tags": Either a single tag, or a comma separated lists of tags (spaces optional)
	 * "pageID": ID of user's current page
	*/
	
	case "add-tag":

		//Lookup the userID, or create if necessary
		$userID = get_user_id($_POST['user']);
		
		//Breakup tags into array
		$tags = explode(',',$_POST['tags']);			
		
		foreach ($tags as $tag) {
		
			//If the tag is empty, skip the loop
			if (strlen($tag)==0) continue;			
			
			//Lookup tagID, or create if necessary
			$tagID = get_tag_by_name(trim($tag));	
			if (!$tagID) $tagID = add_tag (trim($tag));
			
			//Add the tag to the page
			if ( add_tag_to_page($tagID,$_POST['pageID'],$userID) !== FALSE) {
				
				//Insert an array into array Output with the tag (text) and ID to be sent back to the user via JSON
				$output[] = array("tag"=>trim(strtolower($tag)), "TagID"=>$tagID);

			}
			
		}
		
	break;
	/**
	 *
	 * Add a Note
	 *
	*/
	case "add-note":
	
		//If the note field is empty, set an empty array and skip to the end of the file
		if (strlen($_POST['note']) == 0) {
			$output = array();
			break;
		}
		
		//Lookup the userID, or create if necessary
		$userID = get_user_id($_POST['user']);
		
		//Store the note
		$noteID = add_note($_POST['pageID'],$userID,$_POST['note']);
		
		//Add NoteID to output array to pass back to page via JSON
		$output = array("NoteID"=>$noteID);
	break;
	/**
	 *
	 * Remove a Tag
	 *
	*/
	case "remove-tag":
		if (remove_tag_from_page($_GET['tagID'],$_GET['pageID'])) $output = array(1);
		else $output = array(0);
	break;
	
		/**
	 *
	 * Remove a Note
	 *
	*/
	case "remove-note":
		if (remove_note($_GET['noteID'])) $output = array(1);
		else $output = array(0);
	break;
	
	case "get-analytics":

	require 'includes/gapi.class.php';
	$ga = new gapi(ga_email,ga_password);
	$filter = 'pagePath == ' . str_replace('http://','',$_GET['url']);
	$ga->requestReportData(ga_profile_id,array('pagePath'),array('pageviews','timeOnPage','exits','visits'),'',$filter);
	$results = $ga->getResults();

	$output = '';
	$metrics = $results[0]->getMetrics();
	foreach ($metrics as $ID=>$metric) {

		switch($ID) {
		
			case 'pageviews':
				$output .= '<div class="label">Pageviews (past month):</div><div class="field" id="pageviews">'.number_format($metric,0).'</div>';
			break;
			case 'timeOnPage':
				$output .= '<div class="label">Average Time on Page:</div><div class="field" id="timeonpage">'.sec2hms($metric / ($metrics['pageviews'] - $metrics['exits']) ).'</div>';
			break;
			case 'exits';
				$output .= '<div class="label">% Who left FCC after viewing:</div><div class="field" id="exits">'.number_format( ( $metric / $metrics['pageviews'] ) *100 ,2 ).'%</div>';			
			break;
	
		}
	}
	break;
	case 'get-pageID':
		$query = array('url'=>$_GET['url']);
		if (!mysql_exists('pages',$query)) $pageID = add_page($_GET['url']);
		else $pageID = get_page_ID($_GET['url']);	
		$output = array($pageID);
	break;
	case 'get-tags':
		foreach(get_tags_by_page($_GET['pageID']) as $tag) {
			$output[$tag['TagID']] = array('timestamp' => $tag['TimeStamp'], 'user' => $tag['UserID'], 'Tag' => get_tag($tag['TagID']) );
		}
	break;
	case 'get-notes':
		foreach(get_notes($_GET['pageID']) as $note) { 
			$output[$note['ID']] = array('timestamp' => $note['TimeStamp'], 'user' => $note['UserID'], 'note' => $note['Note']);
		}
	break;
	case 'get-username':
		$output = get_user_name($_GET['UserID'],TRUE);
	break;
	case 'get-user':
		$output = get_user_by_name($_GET['user']);
	break;
	case 'get-tag':
		if ( !empty($_GET['TagID'] ) )
			$output = get_tag($_GET['TagID']);
		else if ( !empty($_GET['tag'] ) )
			$output = get_tag_by_name( $_GET['tag'] );
	break;
	case 'clean-url':
		$ouput = clean_url($_GET['url']);
	break;
}

//Encode $output (array) as JSON and output
echo json_encode($output);
?>
