<?php

/**
 * PHProxy.                                                   
 *
 * Modified version of PHProxy core files.  Used to retrieve and parse target pages before serving to the user.
 *
 * Last Modified   5:27 PM 1/20/2007
 * 
 * This program is free software; you can redistribute it and/or               
 * Modify it under the terms of the GNU General Public License                 
 * as published by the Free Software Foundation; either version 2              
 * of the License, or (at your option) any later version.                      
 *                                                                                 
 * This program is distributed in the hope that it will be useful,             
 * but WITHOUT ANY WARRANTY; without even the implied warranty of              
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               
 * GNU General Public License for more details.                                                                                                               
 *   
 * @author Abdullah Arif                                              
 * @package PHProxy
 *
 */

/**
 *
 * There should be no need to edit anything in this file. PHProxy configuration variables moved to config.php and combined with DeveloperView settings
 *
 */

$_hotlink_domains   = array(); //moved from configuration, depreciated functionality, set to empty array to prevent errors
$_insert            = array(); //moved from configuration, depreciated functionality, set to empty array to prevent errors

$_iflags            = '';
$_system            = array
                    (
                        'ssl'          => extension_loaded('openssl') && version_compare(PHP_VERSION, '4.3.0', '>='),
                        'uploads'      => ini_get('file_uploads'),
                        'gzip'         => extension_loaded('zlib') && !ini_get('zlib.output_compression'),
                        'stripslashes' => get_magic_quotes_gpc()
                    );
$_proxify           = array('text/html' => 1, 'application/xml+xhtml' => 1, 'application/xhtml+xml' => 1, 'text/css' => 1);
$_http_host         = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
$_script_url        = 'http' . ((isset($_ENV['HTTPS']) && $_ENV['HTTPS'] == 'on') || $_SERVER['SERVER_PORT'] == 443 ? 's' : '') . '://' . $_http_host . ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ? null : '') . substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/')+1);
$_script_base       = substr($_script_url, 0, strrpos($_script_url, '/')+1);
$_version           = '0.5b2';
$_url               = '';
$_url_parts         = array();
$_base              = array();
$_socket            = null;
$_request_method    = $_SERVER['REQUEST_METHOD'];
$_request_headers   = '';
$_cookie            = '';
$_post_body         = '';
$_response_headers  = array();
$_response_keys     = array();  
$_http_version      = '';
$_response_code     = 0;
$_content_type      = 'text/html';
$_content_length    = false;
$_content_disp      = '';
$_set_cookie        = array();
$_retry             = false;
$_quit              = false;
$_basic_auth_header = '';
$_basic_auth_realm  = '';
$_auth_creds        = array();
$_response_body     = '';

//
// FUNCTION DECLARATIONS
//
function show_report($data)
{    
    include $data['which'] . '.inc.php';
    exit(0);
}

function add_cookie($name, $value, $expires = 0)
{
    return rawurlencode(rawurlencode($name)) . '=' . rawurlencode(rawurlencode($value)) . (empty($expires) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s \G\M\T', $expires)) . '; path=/; domain=.' . $GLOBALS['_http_host'];
}

function set_post_vars($array, $parent_key = null)
{
    $temp = array();

    foreach ($array as $key => $value)
    {
        $key = isset($parent_key) ? sprintf('%s[%s]', $parent_key, urlencode($key)) : urlencode($key);
        if (is_array($value))
        {
            $temp = array_merge($temp, set_post_vars($value, $key));
        }
        else
        {
            $temp[$key] = urlencode($value);
        }
    }
    
    return $temp;
}

function set_post_files($array, $parent_key = null)
{
    $temp = array();

    foreach ($array as $key => $value)
    {
        $key = isset($parent_key) ? sprintf('%s[%s]', $parent_key, urlencode($key)) : urlencode($key);
        if (is_array($value))
        {
            $temp = array_merge_recursive($temp, set_post_files($value, $key));
        }
        else if (preg_match('#^([^\[\]]+)\[(name|type|tmp_name)\]#', $key, $m))
        {
            $temp[str_replace($m[0], $m[1], $key)][$m[2]] = $value;
        }
    }

    return $temp;
}

function url_parse($url, & $container)
{
    $temp = @parse_url($url);

    if (!empty($temp))
    {
        $temp['port_ext'] = '';
        $temp['base']     = $temp['scheme'] . '://' . $temp['host'];

        if (isset($temp['port']))
        {
            $temp['base'] .= $temp['port_ext'] = ':' . $temp['port'];
        }
        else
        {
            $temp['port'] = $temp['scheme'] === 'https' ? 443 : 80;
        }
        
        $temp['path'] = isset($temp['path']) ? $temp['path'] : '/';
        $path         = array();
        $temp['path'] = explode('/', $temp['path']);
    
        foreach ($temp['path'] as $dir)
        {
            if ($dir === '..')
            {
                array_pop($path);
            }
            else if ($dir !== '.')
            {
                for ($dir = rawurldecode($dir), $new_dir = '', $i = 0, $count_i = strlen($dir); $i < $count_i; $new_dir .= strspn($dir{$i}, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$-_.+!*\'(),?:@&;=') ? $dir{$i} : rawurlencode($dir{$i}), ++$i);
                $path[] = $new_dir;
            }
        }

        $temp['path']     = str_replace('/%7E', '/~', '/' . ltrim(implode('/', $path), '/'));
        $temp['file']     = substr($temp['path'], strrpos($temp['path'], '/')+1);
        $temp['dir']      = substr($temp['path'], 0, strrpos($temp['path'], '/'));
        $temp['base']    .= $temp['dir'];
        $temp['prev_dir'] = substr_count($temp['path'], '/') > 1 ? substr($temp['base'], 0, strrpos($temp['base'], '/')+1) : $temp['base'] . '/';
        $container = $temp;

        return true;
    }
    
    return false;
}

function complete_url($url, $proxify = true)
{
    $url = trim($url);
    
    if ($url === '')
    {
        return '';
    }
    
    $hash_pos = strrpos($url, '#');
    $fragment = $hash_pos !== false ? '#' . substr($url, $hash_pos) : '';
    $sep_pos  = strpos($url, '://');
    
    if ($sep_pos === false || $sep_pos > 5)
    {
        switch ($url{0})
        {
            case '/':
                $url = substr($url, 0, 2) === '//' ? $GLOBALS['_base']['scheme'] . ':' . $url : $GLOBALS['_base']['scheme'] . '://' . $GLOBALS['_base']['host'] . $GLOBALS['_base']['port_ext'] . $url;
                break;
            case '?':
                $url = $GLOBALS['_base']['base'] . '/' . $GLOBALS['_base']['file'] . $url;
                break;
            case '#':
                $proxify = false;
                break;
            case 'm':
                if (substr($url, 0, 7) == 'mailto:')
                {
                    $proxify = false;
                    break;
                }
            default:
                $url = $GLOBALS['_base']['base'] . '/' . $url;
        }
    }

    return $proxify ? "{$GLOBALS['_script_url']}?{$GLOBALS['_config']['url_var_name']}=" . encode_url($url) . $fragment : $url;
}

function proxify_inline_css($css)
{
    preg_match_all('#url\s*\(\s*(([^)]*(\\\))*[^)]*)(\)|$)?#i', $css, $matches, PREG_SET_ORDER);

    for ($i = 0, $count = count($matches); $i < $count; ++$i)
    {
        $css = str_replace($matches[$i][0], 'url(' . proxify_css_url($matches[$i][1]) . ')', $css);
    }

    return $css;
}

function proxify_css($css)
{
    $css = proxify_inline_css($css);

    preg_match_all("#@import\s*(?:\"([^\">]*)\"?|'([^'>]*)'?)([^;]*)(;|$)#i", $css, $matches, PREG_SET_ORDER);

    for ($i = 0, $count = count($matches); $i < $count; ++$i)
    {
        $delim = '"';
        $url   = $matches[$i][2];

        if (isset($matches[$i][3]))
        {
            $delim = "'";
            $url = $matches[$i][3];
        }

        $css = str_replace($matches[$i][0], '@import ' . $delim . proxify_css_url($matches[$i][1]) . $delim . (isset($matches[$i][4]) ? $matches[$i][4] : ''), $css);
    }

    return $css;
}

function proxify_css_url($url)
{
    $url   = trim($url);
    $delim = strpos($url, '"') === 0 ? '"' : (strpos($url, "'") === 0 ? "'" : '');

    return $delim . preg_replace('#([\(\),\s\'"\\\])#', '\\$1', complete_url(trim(preg_replace('#\\\(.)#', '$1', trim($url, $delim))),false)) . $delim;
}

//
// SET FLAGS
//

if (isset($_POST[$_config['url_var_name']]) && !isset($_GET[$_config['url_var_name']]) && isset($_POST[$_config['flags_var_name']]))
{    
    foreach ($_flags as $flag_name => $flag_value)
    {
        $_iflags .= isset($_POST[$_config['flags_var_name']][$flag_name]) ? (string)(int)(bool)$_POST[$_config['flags_var_name']][$flag_name] : ($_frozen_flags[$flag_name] ? $flag_value : '0');
    }
    
    $_iflags = base_convert(($_iflags != '' ? $_iflags : '0'), 2, 16);
}
else if (isset($_GET[$_config['flags_var_name']]) && !isset($_GET[$_config['get_form_name']]) && ctype_alnum($_GET[$_config['flags_var_name']]))
{
    $_iflags = $_GET[$_config['flags_var_name']];
}
else if (isset($_COOKIE['flags']) && ctype_alnum($_COOKIE['flags']))
{
    $_iflags = $_COOKIE['flags'];
}

if ($_iflags !== '')
{
    $_set_cookie[] = add_cookie('flags', $_iflags, time()+2419200);
    $_iflags = str_pad(base_convert($_iflags, 16, 2), count($_flags), '0', STR_PAD_LEFT);
    $i = 0;

    foreach ($_flags as $flag_name => $flag_value)
    {
        $_flags[$flag_name] = $_frozen_flags[$flag_name] ? $flag_value : (int)(bool)$_iflags{$i};
        $i++;
    }
}

//
// DETERMINE URL-ENCODING BASED ON FLAGS
//

if ($_flags['rotate13'])
{
    function encode_url($url)
    {
        return rawurlencode(str_rot13($url));
    }
    function decode_url($url)
    {
        return str_replace(array('&amp;', '&#38;'), '&', str_rot13(rawurldecode($url)));
    }
}
else if ($_flags['base64_encode'])
{
    function encode_url($url)
    {
        return rawurlencode(base64_encode($url));
    }
    function decode_url($url)
    {
        return str_replace(array('&amp;', '&#38;'), '&', base64_decode(rawurldecode($url)));
    }
}
else
{
    function encode_url($url)
    {
        return rawurlencode($url);
    }
    function decode_url($url)
    {
        return str_replace(array('&amp;', '&#38;'), '&', rawurldecode($url));
    }
}

//
// COMPRESS OUTPUT IF INSTRUCTED
//

if ($_config['compress_output'] && $_system['gzip'])
{
    ob_start('ob_gzhandler');
}

//
// STRIP SLASHES FROM GPC IF NECESSARY
//

if ($_system['stripslashes'])
{
    function _stripslashes($value)
    {
        return is_array($value) ? array_map('_stripslashes', $value) : (is_string($value) ? stripslashes($value) : $value);
    }
    
    $_GET    = _stripslashes($_GET);
    $_POST   = _stripslashes($_POST);
    $_COOKIE = _stripslashes($_COOKIE);
}

//
// FIGURE OUT WHAT TO DO (POST URL-form submit, GET form request, regular request, basic auth, cookie manager, show URL-form)
//

if (isset($_POST[$_config['url_var_name']]) && !isset($_GET[$_config['url_var_name']]))
{   
    header('Location: ' . $_script_url . '?' . $_config['url_var_name'] . '=' . encode_url($_POST[$_config['url_var_name']]) . '&' . $_config['flags_var_name'] . '=' . base_convert($_iflags, 2, 16));
    exit(0);
}

if (isset($_GET[$_config['get_form_name']]))
{
    $_url  = decode_url($_GET[$_config['get_form_name']]);
    $qstr = strpos($_url, '?') !== false ? (strpos($_url, '?') === strlen($_url)-1 ? '' : '&') : '?';
    $arr  = explode('&', $_SERVER['QUERY_STRING']);
    
    if (preg_match('#^\Q' . $_config['get_form_name'] . '\E#', $arr[0]))
    {
        array_shift($arr);
    }
    
    $_url .= $qstr . implode('&', $arr);
}
else if (isset($_GET[$_config['url_var_name']]))
{
    $_url = decode_url($_GET[$_config['url_var_name']]);
}
else if (isset($_GET['action']) && $_GET['action'] == 'cookies')
{
    show_report(array('which' => 'cookies'));
}
else if (!isset ($_REQUEST['pageID'])) {

    //BEGIN DeveloperView MODIFICATION TO DEFAULT TO DEFAULT URL
	//if no URL is known (e.g. if viewing root of directory) default to default URL home rather than prompt
	
	header('Location: ' . $_script_url . '?' . $_config['url_var_name'] . '=' . encode_url($_config['default_url']));
    exit(0);
	
}

if (isset($_GET[$_config['url_var_name']], $_POST[$_config['basic_auth_var_name']], $_POST['username'], $_POST['password']))
{
    $_request_method    = 'GET';
    $_basic_auth_realm  = base64_decode($_POST[$_config['basic_auth_var_name']]);
    $_basic_auth_header = base64_encode($_POST['username'] . ':' . $_POST['password']);
}

//
// SET URL
//

if (strpos($_url, '://') === false)
{
    $_url = 'http://' . $_url;
}

if ( url_parse($_url, $_url_parts))
{
    $_base = $_url_parts;
    
	//MODIFIED for DeveloperView to lock users into a specific domain (whitelist) rather than original functionality (blacklist)
	//ORIGINAL from PHProxy if (preg_match($host, $_url_parts['host']))

	if ($_config['domain'] && !preg_match("#".$_config['domain']."#i", $_url_parts['host']))
	{
		show_report(array('which' => 'index', 'category' => 'error', 'group' => 'url', 'type' => 'external', 'error' => 1));
	}
}
else if (!isset ($_REQUEST['pageID']) )
{
    show_report(array('which' => 'index', 'category' => 'error', 'group' => 'url', 'type' => 'external', 'error' => 2));
}

//
// HOTLINKING PREVENTION
//

if (!$_config['allow_hotlinking'] && isset($_SERVER['HTTP_REFERER']))
{
    $_hotlink_domains[] = $_http_host;
    $is_hotlinking      = true;
    
    foreach ($_hotlink_domains as $host)
    {
        if (preg_match('#^https?\:\/\/(www)?\Q' . $host  . '\E(\/|\:|$)#i', trim($_SERVER['HTTP_REFERER'])))
        {
            $is_hotlinking = false;
            break;
        }
    }
    
    if ($is_hotlinking)
    {
        switch ($_config['upon_hotlink'])
        {
            case 1:
                show_report(array('which' => 'index', 'category' => 'error', 'group' => 'resource', 'type' => 'hotlinking'));
                break;
            case 2:
                header('HTTP/1.0 404 Not Found');
                exit(0);
            default:
                header('Location: ' . $_config['upon_hotlink']);
                exit(0);
        }
    }
}
 

?>