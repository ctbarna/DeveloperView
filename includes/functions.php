<?php
/**
 * Include file with functions used throughout DeveloperView.
 *
 * Functions rely heavily on {@package mysql_functoins} to do a lot of the heavy lifting.  
 * Many are just shorthand functions to execute the necessary MySQL queries
 * and keep the code clean.
 *
 * @package DeveloperView 
 *
 */

/*
	FILE PARSING FUNCTIONS
*/

/**
 * Function which utilizes output buffering to parse a PHP file into a string (so that it can be REGEX'd into the HTML document.
 *
 * @package DeveloperView
 * @subpackage file parsing
 * @param file $file php file to parse into string
 * @return string Output of file
 */
function parse_file($file) {

	//Start output buffering
	ob_start();
	
	//grab the file
	include($file); 
	
	//dump the buffer into a string and clear the buffer
	$output = ob_get_clean(); 	
	
	//return the string
	return $output; 
}

/**
 * Function to parse and return /includes/drop-ins/head.php.
 *
 * Output gets injected immediately before </head> tag of page
 *
 * @package DeveloperView
 * @subpackage file parsing
 * @return string output of file
 */
function get_dv_header() {
	return parse_file('drop-ins/head.php');
}


/**
 * Function to parse and return /includes/drop-ins/body.php.
 *
 * Output gets injected immediately after <body> tag of page
 *
 * @package DeveloperView
 * @subpackage file parsing
 * @return string output of file
 */
function get_dv_body() {

	return parse_file('drop-ins/body.php');
}

/*
	PAGE FUNCTIONS
*/

/**
 * Add a page to the pages table
 * 
 * URL is NOT case sensitive
 * 
 * @package DeveloperView
 * @subpackage pages
 * @param string $url URL of page to add
 * @return int|bool ID of page or false if error
 *
*/
function add_page($url) {
	$data = array("url"=>strtolower(clean_url($url)));
	return mysql_insert('pages',$data); 
}

/**
 * Edits the values of a page already in the database.
 * 
 * @parm int $id PageID of page to edit
 * @param array $data Associative array of fields and values of updated data
 * @returns bool true or false on success
 * @package DeveloperView
 * @subpackage pages
 *
 */
function edit_page($id,$data) {
	$query = array("ID"=>$id);
	return mysql_update('pages',$query,$data);
}

/**
 * Return associative array of pages.
 *
 * Default is all pages, optionally accepts array $query
 *
 * @param array $query Associative Array of field names and values to query
 * @return array Associative array of pages keyed to page ID
 * @package DeveloperView
 * @subpackage pages
 *
 */
function get_pages($query = array()) {
	return mysql_array(mysql_select('pages',$query));
}

/**
 * Returns aray of specific page given PageID.
 *
 * @param string $id ID of Page to retrieve
 * @retun array of selected row
 * @package DeveloperView
 * @subpackage pages
 *
 */
function get_page($id) {
	$query = array("ID"=>$id);
	return mysql_row_array(mysql_select('pages',$query));
}

/**
 * Returns ID of page given URL.
 *
 * Note: case sensitive
 * 
 * @param string $url URL of page to lookup
 * @return int ID of slected page
 * @package DeveloperView
 * @subpackage pages
 *
 */
function get_page_id($url) {
	//NOTE TO SELF: this will generate an error if the URL is not in the DB, should probably check before returning
	$query = array("URL"=>clean_url($url));
	$page = mysql_row_array(mysql_select('pages',$query));
	if (!$page) return false;
	else return $page['ID'];
}


/**
 * Uniformly cleans a url to avoid duplicates
 *
 * 1. Removes anchor tags (foo.html#bar to foo.html)
 * 2. Adds trailing slash if directory (foo.com/bar to foo.com/bar/)
 * 3. Adds www if there is not a subdomain (foo.com to www.foo.com but not bar.foo.com)
 *
 * @params string $url url to clean
 * @parmas string $dir directory of parent (linking) page
 * @return strin cleaned url
 */
function clean_url($url) {
	if (stripos($url,'#') != FALSE) $url = substr($url,0,stripos($url,'#')); //remove anchors
	if (!preg_match('#(^http://(.*)/$)|http://(.*)/(.*)\.([A-Za-z0-9]+)|http://(.*)/([^\?\#]*)(\?|\#)([^/]*)#i',$url))  $url .= '/';
	$url = preg_replace('#http://([^.]+).([a-zA-z]{3})/#i','http://www.$1.$2/',$url);
	return $url;
}

/*
	TAG FUNCTIONS
*/

/**
 * Shorthand function to check if a tag exists in the tags table.
 *
 * *NOT* case sensitive
 *
 * @param string $tag Tag to look up 
 * @returns bool true if exists, otherwise false
 * @package DeveloperView
 * @subpackage tags
 */
function have_tag($tag) {
	return mysql_exists('tags',array('tag'=>strtolower($tag)));
}

/**
 * Adds a tag to the tags table.
 *
 * Converts tag to all lowercase before executing
 *
 * @param string $tag Tag to add
 * @return int|bool ID of tag on sucess, false on fail
 * @package DeveloperView
 * @subpackage tags
 *
 */
function add_tag($tag) {
	if (strlen($tag) == 0 || $tag == " ") return false;
	$data = array("Tag"=>strtolower($tag));
	return mysql_insert('tags',$data);
}

/**
 * Associates a tag with a given page.
 *
 * Automatically timestamps on execution; checks for collisions
 *
 * @param int $tagID ID # of tag to be added
 * @param int $page ID # of target page
 * @param int $user ID # of user adding the tag
 * @return int|bool ID of relationship on sucess, false on fail
 * @package DeveloperView
 * @subpackage tags
 *
 */
function add_tag_to_page($tagID,$page,$user, $timestamp = '') {
	if ($timestamp == '') $timestamp = date("Y-m-d H:i:s");
	
	if (mysql_exists('tags-pages',array("TagID" => $tagID, "PageID" =>$page)))
		return false;

	$data = array("TagID" => $tagID, "PageID" =>$page, "UserID"=>$user, "TimeStamp"=>$timestamp);
	return mysql_insert('tags-pages',$data);
}

/**
 * Can be used to edit a tag (e.g., to correct a typo on entry).
 * 
 * @param int $tagID Tag to operate on
 * @parm string $tag Updated tag
 * @return bool true on sucess, false on fail
 * @package DeveloperView
 * @subpackage tags
 *
 */
function edit_tag($tagID,$tag) {
	$query = array("ID"=>$tagID);
	$data = array("Tag"=>$tag);
	return mysql_update('tags',$data,$query);
}

/**
 * Operates on tags-pages, removes relationsip between tag and page.
 *
 * @param int $tagID ID # of Tag to remove
 * @param int $pageID ID # of Page to operate on
 * @return bool true on sucess, false on fail
 * @package DeveloperView
 * @subpackage tags
 *
 */
function remove_tag_from_page($tagID,$pageID) {
	$query = array('TagID'=>$tagID,'PageID'=>$pageID);
	return mysql_remove('tags-pages',$query);
}

/**
 * Shorthand function to remove a tag from the tags table.
 *
 * @param int $tagID ID # of tag to be remove
 * @return bool true on sucess, false on fail
 * @package DeveloperView
 * @subpackage tags
 *
 */
function remove_tag($tagID) {
	mysql_query("DELETE FROM `tags-pages` WHERE `TagID` = '$tagID'");
	$query = array('ID'=>$tagID);
	return mysql_remove('tags',$query);
}

/**
 * Returns all tags in table tags.
 *
 * Used for auto completion and for backend visualizations.  Returns a multi-dimensional associative array of all tags key'd by ID #
 *
 * @return array multi-dimensional associative array of all tags key'd by ID #
 * @package DeveloperView
 * @subpackage tags
 * @param string $orderby column to sort on
 * @param string $direction direction to stort (ASC or DESC)
 *
 */
function get_tags($orderby = "Tag", $direction = "ASC") {
	return mysql_array(mysql_query("SELECT `tags`.ID, `tags`.Tag, COUNT(`pageID`) AS Count FROM `tags` LEFT JOIN `tags-pages` ON (`tags`.ID = `tags-pages`.TagID)  GROUP BY `tags`.ID ORDER BY $orderby $direction"));
} 

/**
 * Returns tag by ID.
 *
 * @param int $tagID ID # of tag to retrieve
 * @return string|bool tag on sucess, false on fail
 * @package DeveloperView
 * @subpackage tags
 *
 */

function get_tag($tagID) {
	$query = array('ID'=>$tagID);
	$tag = mysql_row_array(mysql_select('tags',$query));
	if ($tag) return $tag['Tag'];
	else return false;
}

/**
 * Given a tag it returns the tags ID #.
 *
 * @param string $tag the tag
 * @return int|bool The ID # of the tag if sucess, false if fail
 * @package DeveloperView
 * @subpackage tags
 *
 */
function get_tag_by_name($tag) {
	$query = array('tag'=>strtolower($tag));
	$tag = mysql_row_array(mysql_select('tags',$query));
	if ($tag) return $tag['ID'];
	else return false;
}

/**
 * Returns all tags associated with a given page.
 *
 * @param int $pageID ID # of target page
 * @return array multi-dimensional associative array of all tags keyed to their ID #
 * @package DeveloperView
 * @subpackage tags
 *
 */
function get_tags_by_page($pageID) {
	$query = array('PageID'=>$pageID);
	return mysql_array(mysql_select('tags-pages',$query));
}

/*
	NOTE FUNCTIONS
*/


/**
 * Adds a note to the notes table.
 *
 * Automatically adds time stamp on execute 
 *
 * @param int $page The ID of the page to associate the note with
 * @param int $user The ID of the user adding the note
 * @param string $note The Text of the note
 * @return int|bool The ID # of the note added if sucessful, false on fail
 * @package DeveloperView
 * @subpackage notes
 *
 */
function add_note($page,$user,$note) {
	$data = array( 
		'PageID' => $page, 
		'UserID'=>$user, 
		'Note' =>$note,
		'TimeStamp'=>date('Y-m-d H:i:s')
	);
	return mysql_insert('notes',$data);
}

/**
 * Makes changes to a record in the notes table.
 * 
 * @param int $noteID ID # of note to updaet
 * @param array $data array of one or more fields to update and their values
 * @return bool true on sucess, false on fail
 * @package DeveloperView
 * @subpackage notes
 *
 */
function edit_note($noteID,$data) {
	$query = array('NoteID'=>$noteID);
	return mysql_update('notes',$data,$query);
}

/**
 * Removes a note from the notes table.
 *
 * @param int $noteID The ID # of the note to remove
 * @return bool true on sucess, false on fail
 * @package DeveloperView
 * @subpackage notes
 *
 */
function remove_note($noteID) {
	$query = array('ID'=>$noteID);
	return mysql_remove('notes',$query);
}

/**
 *
 * Returns an associative array of all notes associated with a given page.
 *
 * @param int $pageID ID # of page to retrieve notes for
 * @return array multi-dimensional associative array of notes keyed by the note's ID #
 * @package DeveloperView
 * @subpackage notes
 *
 */
function get_notes($pageID) {
	$query = array('PageID'=>$pageID);
	return mysql_array(mysql_select('notes',$query));
}

/*
	USER FUNCTIONS
*/

/**
 * Adds a user to the user table.
 * 
 * Automatically converts the username to all lowercase
 *
 * @param string $name username
 * @return int ID # of added user
 * @package DeveloperView
 * @subpackage users
 *
 */
function add_user($name) {
	return mysql_insert('users',array('Name'=>strtolower($name)));
}

/*
 * Retrives user's record given ID.
 *
 * @param int $id ID # of user
 * @return array associate array of user's record
 * @package DeveloperView
 * 
*/
function get_user($id) {
	$query = array("ID"=>$id);
	return mysql_row_array(mysql_select('users',$query));
}

/*
 * Lookup a user by name and retrieve their record.
 *
 * Automatically converts the username to all lowercase
 * 
 * @param string $name username
 * @return array associate array of user's record
 * @package DeveloperView
 * @subpackage users
 *
 */
function get_user_by_name($name) {
	$query = array("Name"=>strtolower($name));
	return mysql_row_array(mysql_select('users',$query));
}

/** 
 * Returns a user ID given a username, adds the user if they do not already exist.
 * 
 * @param string $name uesrname to lookup 
 * @return int ID # of user
 * @package DeveloperView
 * @subpackage users
 *
 */
function get_user_id($name) {
	$user = get_user_by_name($name);
	if (sizeof($user)>0) return $user['ID'];
	else return add_user($name);
}

/**
 * Returns a username given an ID.
 *
 * Optionally takes a 2nd parameter to make the name user friendly (converts period to space, capitalizes each word)
 *
 * @param int $id ID # of user
 * @param bool $clean Whether or not to clean the username before returning
 * @return string|bool return name if found, returns false if not found
 * @package DeveloperView
 * @subpackage users
 *
 */
function get_user_name($id, $clean = FALSE) {
	$user = get_user($id);
	if ($user) {
		if ($clean) return clean_name($user['Name']);
		else return $user['Name'];
	}
	else return false;
}

/*
 * Returns a list of all users.
 *
 * Eventually to be used in backend
 *
 * @return array multi-dimensional associtive array keyed by user ID
 * @package DeveloperView
 * @subpackage users
 *
 */
function get_users() {
	return mysql_array(mysql_select('users'));
}

/*
 * Cleans a user name to make it look nice (converts period to space, capitalizes each word).
 *
 * @param string $name username
 * @return string username (but cleaner)
 * @package DeveloperView
 * @subpackage users
 *
 */
function clean_name($name) {
	return ucwords(str_replace('.',' ',$name));
}

/*
	DELICIOUS FUNCTIONS
*/

/** 
 * Function to grab the top delicious tags for current url via curl.
 *
 * Can either accept a URL as an argument, or deault to PHProxy's current URL
 * Returns in the form array( [tag (string)] => [count (var)] )
 *
 * @param string $url URL of page to lookup
 * @param int $count # of Tags to retrieve, default is 10
 * @return array Aarray of top tags in the form of array(tag=>count)
 * @package DeveloperView
 * @subpackage delicious
 *
 */
function get_delicious_tags($url = '', $count = 10) {

	// grab the URL from PHProxy
	global $_url;
	if ($url == '') $url = $_url;
	
	//set the target
	$target = "http://feeds.delicious.com/v2/json/urlinfo/" . md5($url) . "?count=" . $count;
	
	//Curl the JSON info and decode it
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $target);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = json_decode(curl_exec($ch));
	curl_close($ch);
	
	//if there are not any tags, return an empty array to avoid errors, otherwise return the results
	if (!isset($data[0]->top_tags)) return array();
	else return $data[0]->top_tags;
}

/*
	GENERAL FUNCTIONS
	http://blog.thetonk.com/archives/fuzzy-time
*/

/**
 * Function to generate funny time given a timestamp.
 *
 * Fuzzy time would be, e.g., an hour ago, two weeks ago, 20 minutes ago, etc. rather than 10:40 am.
 * Relies on globals set in config.php
 * Bassed on code from http://blog.thetonk.com/archives/fuzzy-time
 *
 * @global NOW
 * @global ONE_MINUTE
 * @global ONE_HOUR
 * @global ONE_DAY
 * @global ONE_MONTH
 * @global ONE_WEEK
 * @global ONE_YEAR
 * @param string time takes time in almost anyformat (uses strtotime())
 * @return string the time in fuzzy time
 * @package DeveloperView
 * @subpackage fuzzy time
 *
 */
 
function fuzzy_time( $time ) {

  if ( ( $time = strtotime( $time ) ) == false ) {
    return 'an unknown time';
  }
 
  // sod = start of day :)
  $sod = mktime( 0, 0, 0, date( 'm', $time ), date( 'd', $time ), date( 'Y', $time ) );
  $sod_now = mktime( 0, 0, 0, date( 'm', NOW ), date( 'd', NOW ), date( 'Y', NOW ) );
 
  // used to convert numbers to strings
  $convert = array( 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven' );
 
  // today
  if ( $sod_now == $sod ) {
    if ( $time > NOW-(ONE_MINUTE*3) ) {
      return 'just a moment ago';
    } else if ( $time > NOW-(ONE_MINUTE*7) ) {
      return 'a few minutes ago';
    } else if ( $time > NOW-(ONE_HOUR) ) {
      return 'less than an hour ago';
    }
    return 'today at ' . date( 'g:ia', $time );
  }
 
  // yesterday
  if ( ($sod_now-$sod) <= ONE_DAY ) {
    if ( date( 'i', $time ) > (ONE_MINUTE+30) ) {
      $time += ONE_HOUR/2;
    }
    return 'yesterday around ' . date( 'ga', $time );
  }
 
  // within the last 5 days
  if ( ($sod_now-$sod) <= (ONE_DAY*5) ) {
    $str = date( 'l', $time );
    $hour = date( 'G', $time );
    if ( $hour < 12 ) {
      $str .= ' morning';
    } else if ( $hour < 17 ) {
      $str .= ' afternoon';
    } else if ( $hour < 20 ) {
      $str .= ' evening';
    } else {
      $str .= ' night';
    }
    return $str;
  }
 
  // number of weeks (between 1 and 3)...
  if ( ($sod_now-$sod) < (ONE_WEEK*3.5) ) {
    if ( ($sod_now-$sod) < (ONE_WEEK*1.5) ) {
      return 'about a week ago';
    } else if ( ($sod_now-$sod) < (ONE_DAY*2.5) ) {
      return 'about two weeks ago';
    } else {
      return 'about three weeks ago';
    }
  }
 
  // number of months (between 1 and 11)...
  if ( ($sod_now-$sod) < (ONE_MONTH*11.5) ) {
    for ( $i = (ONE_WEEK*3.5), $m=0; $i < ONE_YEAR; $i += ONE_MONTH, $m++ ) {
      if ( ($sod_now-$sod) <= $i ) {
        return 'about ' . $convert[$m] . ' month' . (($m>1)?'s':'') . ' ago';
      }
    }
  }
 
  // number of years...
  for ( $i = (ONE_MONTH*11.5), $y=0; $i < (ONE_YEAR*10); $i += ONE_YEAR, $y++ ) {
    if ( ($sod_now-$sod) <= $i ) {
      return 'about ' . $convert[$y] . ' year' . (($y>1)?'s':'') . ' ago';
    }
  }
 
  // more than ten years...
  return 'more than ten years ago';
}

function sec2hms ($sec, $padHours = false)  {

    // start with a blank string
    $hms = "";
    
    // do the hours first: there are 3600 seconds in an hour, so if we divide
    // the total number of seconds by 3600 and throw away the remainder, we're
    // left with the number of hours in those seconds
    $hours = intval(intval($sec) / 3600); 

    // add hours to $hms (with a leading 0 if asked for)
    $hms .= ($padHours) 
          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ":"
          : $hours. ":";
    
    // dividing the total seconds by 60 will give us the number of minutes
    // in total, but we're interested in *minutes past the hour* and to get
    // this, we have to divide by 60 again and then use the remainder
    $minutes = intval(($sec / 60) % 60); 

    // add minutes to $hms (with a leading 0 if needed)
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ":";

    // seconds past the minute are found by dividing the total number of seconds
    // by 60 and using the remainder
    $seconds = intval($sec % 60); 

    // add seconds to $hms (with a leading 0 if needed)
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

    // done!
    return $hms;
    
  }


/*
	META FUNCTIONS
*/

function add_meta($pageID,$sourceID,$key,$value) {
	$data = array('PageID'=>$pageID,'SourceID'=>$sourceID,'Key'=>$key,"Value"=>$value);
	return mysql_insert('meta',$data);
}

function get_sources( $query=array()) {
	return mysql_array(mysql_select('sources',$query));
}
function add_source($label) {
	return mysql_insert('sources',array('Name'=>$label,'Date'=>date('Y-m-d H:i:s')));
}

function remove_source($sourceID) {
	mysql_remove('meta',array('SourceID'=>$sourceID));
	return mysql_remove('sources',array('ID'=>$sourceID));
}

function get_meta($pageID) {
	return mysql_array(mysql_select('meta',array('PageID'=>$pageID)));
}

/*
	DEBUGGING FUNCTIONS
*/

/**
 * Simple function to spit out a var, used for debugging during development, not used when live
 *
 * Takes anything, spits it out with <PRE> tags, returns nothing
 *
 * @package DeveloperView
 * @subpackage debugging 
 *
 */
function debug_var($var) {
	echo "<!-- DEBUG OUTPUT START -->\r\n";
	echo "<PRE>\r\n";
	print_r($var);
	echo "</PRE>\r\n";
	echo "<!-- DEBUG OUTPUT END -->\r\n";
}


?>