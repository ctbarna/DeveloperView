<?php

/*
================== PHP CONFIGURATION ==================
*/

//Error Reporting level (0 for live, E_ALL for Debugging)
error_reporting(E_ALL); 

//Set the Timezone to EST
date_default_timezone_set('America/New_York');

/*
================== DeveloperView CONFIGURATION ==================
*/

/**
 * MYSQL settings
 * (mysql initialized at the top of includes/header.php)
 */
define('MYSQL_SERVER','');
define('MYSQL_USER','');
define('MYSQL_PASSWORD','');
define('MYSQL_DATABASE','');

/**
 * Google Analytics Info
 */
define('ga_email','');
define('ga_password','');
define('ga_profile_id','');
	
if (MYSQL_USER == '' | MYSQL_PASSWORD == '' | MYSQL_DATABASE == '') die('Please specify the MySQL paramaters in /config.php');

/**
 * Some general DV settings.
 * DV_ADMIN is the admin password.
 * DV_ROOT is the URL of the DV install (with a trailing slash).
 */
define('DV_ADMIN', "admin");
define('DV_ROOT', "/");

/**
 * Fuzzy time settings
 *(time displayed for tags, notes, etc.)
*/
define( 'NOW',        time() );
define( 'ONE_MINUTE', 60 );
define( 'ONE_HOUR',   3600 );
define( 'ONE_DAY',    86400 );
define( 'ONE_WEEK',   ONE_DAY*7 );
define( 'ONE_MONTH',  ONE_WEEK*4 );
define( 'ONE_YEAR',   ONE_MONTH*12 );


/* 
 ================== PHProxy CONFIGURATION ================== 
*/

/**
 * Where to direct the user to on initial page load, most likely your home page
 */
$_config['default_url']			= 'http://www.google.com/';

/**
 * Domain to lock users on to prevent abuse (set to false to bypass)
 */
$_config['domain']				= 'google.com';
 				
/**
 * URL $_GET variable, can be anything
 */
$_config['url_var_name'] 		= 'url';

/**
 * Name of Option fields in header (cookies, Java, etc.), change if in conflict with target page
 */
$_config['flags_var_name'] 		= 'options';				

/**
 * Variable names, can be changed freely if in conflit with target page
 */
$_config['get_form_name'] 		= '____pgfa';			
$_config['basic_auth_var_name'] = '____pbavn';

/**
 * Max file size (-1 bypassess)
 */
$_config['max_file_size'] 		= -1;	
	
/**
 * Domain hotlinking toggles
 */			
$_config['allow_hotlinking'] 	= 1;						
$_config['upon_hotlink'] 		= 1;

/**
 * Output compression on/off, may speed up transfer but slow server depending on setup
 */	
$_config['compress_output'] 	= 0;						

/**
 * PHProxy DEFAULT SETTINGS 
 *(1 = on by deault, 0 = off by default)
*/
$_flags['include_form'] 		= 1;
$_flags['remove_scripts'] 		= 0;
$_flags['accept_cookies'] 		= 1;
$_flags['show_images'] 			= 1;
$_flags['show_referer'] 		= 1;
$_flags['rotate13'] 			= 0;
$_flags['base64_encode'] 		= 0;
$_flags['strip_meta'] 			= 0;
$_flags['strip_title'] 			= 0;
$_flags['session_cookies'] 		= 1;
$_flags['dv-fixed']				= 1;

/** 	
 * PHProxy LOCKED SETTINGS 
 * (Will not display, will force user to use default)
*/
$_frozen_flags['include_form']	= 1;
$_frozen_flags['remove_scripts']= 1;
$_frozen_flags['accept_cookies']= 1;
$_frozen_flags['show_images']	= 1;
$_frozen_flags['show_referer']	= 1;
$_frozen_flags['rotate13']		= 1;
$_frozen_flags['base64_encode']	= 1;
$_frozen_flags['strip_meta']	= 1;
$_frozen_flags['strip_title']	= 1;
$_frozen_flags['session_cookies']= 1;
$_frozen_flags['dv-fixed']		= 1;

/** 	
 * PHProxy SETTING LABELS 
 * (displayed next to checkbox, first value is label displayed, second is alt text)
*/               

$_labels['include_form']		= array('Include Form', 'Include mini URL-form on every page');
$_labels['remove_scripts']		= array('Remove Scripts', 'Remove client-side scripting (i.e JavaScript)');
$_labels['accept_cookies']		= array('Accept Cookies', 'Allow cookies to be stored');
$_labels['show_images']			= array('Show Images', 'Show images on browsed pages');
$_labels['show_referer']		= array('Show Referer', 'Show actual referring Website');
$_labels['rotate13']			= array('Rotate13', 'Use ROT13 encoding on the address');
$_labels['base64_encode']		= array('Base64', 'Use base64 encodng on the address');
$_labels['strip_meta']			= array('Strip Meta', 'Strip meta information tags from pages');
$_labels['strip_title']			= array('Strip Title', 'Strip page title');
$_labels['session_cookies']		= array('Session Cookies', 'Store cookies for this session only');
$_labels['dv-fixed']			= array('Fixed','Fixes DeveloperView Bar to top of screen');


/**
 * Load PHProxy Core
 */

if ( !$backend ) 
	require_once('includes/PHProxy/core.php');
	
/* 
================== INCLUDES ================== 
	(files included on every page load)
*/

/**
 * Grab our custom DeveloperView functions
 */
include('includes/functions.php'); 

/**
 * Grab a handful of mysql functoins used throughout developerview
 */
include('includes/functions-mysql.php'); 

/* 
================== SETUP MYSQL CONNECTION ================== 
*/

$db=mysql_connect (MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD) or die ('I cannot connect to the database because: ' . mysql_error());
mysql_select_db (MYSQL_DATABASE);

/* 
================== VERIFY AND CREATE TABLES IF NECESSARY ================== 
*/

$tables = array('meta','notes','pages','sources','tags','tags-pages','users');
$create_mysql=0;
foreach ($tables as $table) {
	if(!mysql_num_rows( mysql_query("SHOW TABLES LIKE '".$table."'"))) $create_mysql = 1;
}

if ($create_mysql) {
	$file = "create-tables.sql";
	$fh = fopen($file, 'r+');
	$contents = fread($fh, filesize($file));
	$cont = preg_split("/;/", $contents);
	foreach($cont as $query) $result = mysql_query($query);
}

/*
	(EOF)
*/

?>
