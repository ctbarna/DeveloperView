<?php

//
// OPEN SOCKET TO SERVER
//

do
{
    $_retry  = false;
    $_socket = @fsockopen(($_url_parts['scheme'] === 'https' && $_system['ssl'] ? 'ssl://' : 'tcp://') . $_url_parts['host'], $_url_parts['port'], $err_no, $err_str, 30);

    if ($_socket === false)
    {
        show_report(array('which' => 'index', 'category' => 'error', 'group' => 'url', 'type' => 'internal', 'error' => $err_no));
    }

    //
    // SET REQUEST HEADERS
    //

    $_request_headers  = $_request_method . ' ' . $_url_parts['path'];

    if (isset($_url_parts['query']))
    {
        $_request_headers .= '?';
        $query = preg_split('#([&;])#', $_url_parts['query'], -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 0, $count = count($query); $i < $count; $_request_headers .= implode('=', array_map('urlencode', array_map('urldecode', explode('=', $query[$i])))) . (isset($query[++$i]) ? $query[$i] : ''), $i++);
    }

    $_request_headers .= " HTTP/1.0\r\n";
    $_request_headers .= 'Host: ' . $_url_parts['host'] . $_url_parts['port_ext'] . "\r\n";

    if (isset($_SERVER['HTTP_USER_AGENT']))
    {
        $_request_headers .= 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
    }
    if (isset($_SERVER['HTTP_ACCEPT']))
    {
        $_request_headers .= 'Accept: ' . $_SERVER['HTTP_ACCEPT'] . "\r\n";
    }
    else
    {
        $_request_headers .= "Accept: */*;q=0.1\r\n";
    }
    if ($_flags['show_referer'] && isset($_SERVER['HTTP_REFERER']) && preg_match('#^\Q' . $_script_url . '?' . $_config['url_var_name'] . '=\E([^&]+)#', $_SERVER['HTTP_REFERER'], $matches))
    {
        $_request_headers .= 'Referer: ' . decode_url($matches[1]) . "\r\n";
    }
    if (!empty($_COOKIE))
    {
        $_cookie  = '';
        $_auth_creds    = array();
    
        foreach ($_COOKIE as $cookie_id => $cookie_content)
        {
            $cookie_id      = explode(';', rawurldecode($cookie_id));
            $cookie_content = explode(';', rawurldecode($cookie_content));
    
            if ($cookie_id[0] === 'COOKIE')
            {
                $cookie_id[3] = str_replace('_', '.', $cookie_id[3]); //stupid PHP can't have dots in var names

                if (count($cookie_id) < 4 || ($cookie_content[1] == 'secure' && $_url_parts['scheme'] != 'https'))
                {
                    continue;
                }
    
                if ((preg_match('#\Q' . $cookie_id[3] . '\E$#i', $_url_parts['host']) || strtolower($cookie_id[3]) == strtolower('.' . $_url_parts['host'])) && preg_match('#^\Q' . $cookie_id[2] . '\E#', $_url_parts['path']))
                {
                    $_cookie .= ($_cookie != '' ? '; ' : '') . (empty($cookie_id[1]) ? '' : $cookie_id[1] . '=') . $cookie_content[0];
                }
            }
            else if ($cookie_id[0] === 'AUTH' && count($cookie_id) === 3)
            {
                $cookie_id[2] = str_replace('_', '.', $cookie_id[2]);

                if ($_url_parts['host'] . ':' . $_url_parts['port'] === $cookie_id[2])
                {
                    $_auth_creds[$cookie_id[1]] = $cookie_content[0];
                }
            }
        }
        
        if ($_cookie != '')
        {
            $_request_headers .= "Cookie: $_cookie\r\n";
        }
    }
    if (isset($_url_parts['user'], $_url_parts['pass']))
    {
        $_basic_auth_header = base64_encode($_url_parts['user'] . ':' . $_url_parts['pass']);
    }
    if (!empty($_basic_auth_header))
    {
        $_set_cookie[] = add_cookie("AUTH;{$_basic_auth_realm};{$_url_parts['host']}:{$_url_parts['port']}", $_basic_auth_header);
        $_request_headers .= "Authorization: Basic {$_basic_auth_header}\r\n";
    }
    else if (!empty($_basic_auth_realm) && isset($_auth_creds[$_basic_auth_realm]))
    {
        $_request_headers  .= "Authorization: Basic {$_auth_creds[$_basic_auth_realm]}\r\n";
    }
    else if (list($_basic_auth_realm, $_basic_auth_header) = each($_auth_creds))
    {
        $_request_headers .= "Authorization: Basic {$_basic_auth_header}\r\n";
    }
    if ($_request_method == 'POST')
    {   
        if (!empty($_FILES) && $_system['uploads'])
        {
            $_data_boundary = '----' . md5(uniqid(rand(), true));
            $array = set_post_vars($_POST);
    
            foreach ($array as $key => $value)
            {
                $_post_body .= "--{$_data_boundary}\r\n";
                $_post_body .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
                $_post_body .= urldecode($value) . "\r\n";
            }
            
            $array = set_post_files($_FILES);
    
            foreach ($array as $key => $file_info)
            {
                $_post_body .= "--{$_data_boundary}\r\n";
                $_post_body .= "Content-Disposition: form-data; name=\"$key\"; filename=\"{$file_info['name']}\"\r\n";
                $_post_body .= 'Content-Type: ' . (empty($file_info['type']) ? 'application/octet-stream' : $file_info['type']) . "\r\n\r\n";
    
                if (is_readable($file_info['tmp_name']))
                {
                    $handle = fopen($file_info['tmp_name'], 'rb');
                    $_post_body .= fread($handle, filesize($file_info['tmp_name']));
                    fclose($handle);
                }
                
                $_post_body .= "\r\n";
            }
            
            $_post_body       .= "--{$_data_boundary}--\r\n";
            $_request_headers .= "Content-Type: multipart/form-data; boundary={$_data_boundary}\r\n";
            $_request_headers .= "Content-Length: " . strlen($_post_body) . "\r\n\r\n";
            $_request_headers .= $_post_body;
        }
        else
        {
            $array = set_post_vars($_POST);
            
            foreach ($array as $key => $value)
            {
                $_post_body .= !empty($_post_body) ? '&' : '';
                $_post_body .= $key . '=' . $value;
            }
            $_request_headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $_request_headers .= "Content-Length: " . strlen($_post_body) . "\r\n\r\n";
            $_request_headers .= $_post_body;
            $_request_headers .= "\r\n";
        }
        
        $_post_body = '';
    }
    else
    {
        $_request_headers .= "\r\n";
    }

    fwrite($_socket, $_request_headers);
    
    //
    // PROCESS RESPONSE HEADERS
    //
    
    $_response_headers = $_response_keys = array();
    
    $line = fgets($_socket, 8192);
    
    while (strspn($line, "\r\n") !== strlen($line))
    {
        @list($name, $value) = explode(':', $line, 2);
        $name = trim($name);
        $_response_headers[strtolower($name)][] = trim($value);
        $_response_keys[strtolower($name)] = $name;
        $line = fgets($_socket, 8192);
    }
    sscanf(current($_response_keys), '%s %s', $_http_version, $_response_code);
    
    if (isset($_response_headers['content-type']))
    {
        list($_content_type, ) = explode(';', str_replace(' ', '', strtolower($_response_headers['content-type'][0])), 2);
    }
    if (isset($_response_headers['content-length']))
    {
        $_content_length = $_response_headers['content-length'][0];
        unset($_response_headers['content-length'], $_response_keys['content-length']);
    }
    if (isset($_response_headers['content-disposition']))
    {
        $_content_disp = $_response_headers['content-disposition'][0];
        unset($_response_headers['content-disposition'], $_response_keys['content-disposition']);
    }
    if (isset($_response_headers['set-cookie']) && $_flags['accept_cookies'])
    {
        foreach ($_response_headers['set-cookie'] as $cookie)
        {
            $name = $value = $expires = $path = $domain = $secure = $expires_time = '';

            preg_match('#^\s*([^=;,\s]*)\s*=?\s*([^;]*)#',  $cookie, $match) && list(, $name, $value) = $match;
            preg_match('#;\s*expires\s*=\s*([^;]*)#i',      $cookie, $match) && list(, $expires)      = $match;
            preg_match('#;\s*path\s*=\s*([^;,\s]*)#i',      $cookie, $match) && list(, $path)         = $match;
            preg_match('#;\s*domain\s*=\s*([^;,\s]*)#i',    $cookie, $match) && list(, $domain)       = $match;
            preg_match('#;\s*(secure\b)#i',                 $cookie, $match) && list(, $secure)       = $match;
    
            $expires_time = empty($expires) ? 0 : intval(@strtotime($expires));
            $expires = ($_flags['session_cookies'] && !empty($expires) && time()-$expires_time < 0) ? '' : $expires;
            $path    = empty($path)   ? '/' : $path;
                
            if (empty($domain))
            {
                $domain = $_url_parts['host'];
            }
            else
            {
                $domain = '.' . strtolower(str_replace('..', '.', trim($domain, '.')));
    
                if ((!preg_match('#\Q' . $domain . '\E$#i', $_url_parts['host']) && $domain != '.' . $_url_parts['host']) || (substr_count($domain, '.') < 2 && $domain{0} == '.'))
                {
                    continue;
                }
            }
            if (count($_COOKIE) >= 15 && time()-$expires_time <= 0)
            {
                $_set_cookie[] = add_cookie(current($_COOKIE), '', 1);
            }
            
            $_set_cookie[] = add_cookie("COOKIE;$name;$path;$domain", "$value;$secure", $expires_time);
        }
    }
    if (isset($_response_headers['set-cookie']))
    {
        unset($_response_headers['set-cookie'], $_response_keys['set-cookie']);
    }
    if (!empty($_set_cookie))
    {
        $_response_keys['set-cookie'] = 'Set-Cookie';
        $_response_headers['set-cookie'] = $_set_cookie;
    }
    if (isset($_response_headers['p3p']) && preg_match('#policyref\s*=\s*[\'"]?([^\'"\s]*)[\'"]?#i', $_response_headers['p3p'][0], $matches))
    {
        $_response_headers['p3p'][0] = str_replace($matches[0], 'policyref="' . complete_url($matches[1]) . '"', $_response_headers['p3p'][0]);
    }
    if (isset($_response_headers['refresh']) && preg_match('#([0-9\s]*;\s*URL\s*=)\s*(\S*)#i', $_response_headers['refresh'][0], $matches))
    {
        $_response_headers['refresh'][0] = $matches[1] . complete_url($matches[2]);
    }
    if (isset($_response_headers['location']))
    {   
        $_response_headers['location'][0] = complete_url($_response_headers['location'][0]);
    }
    if (isset($_response_headers['uri']))
    {   
        $_response_headers['uri'][0] = complete_url($_response_headers['uri'][0]);
    }
    if (isset($_response_headers['content-location']))
    {   
        $_response_headers['content-location'][0] = complete_url($_response_headers['content-location'][0]);
    }
    if (isset($_response_headers['connection']))
    {
        unset($_response_headers['connection'], $_response_keys['connection']);
    }
    if (isset($_response_headers['keep-alive']))
    {
        unset($_response_headers['keep-alive'], $_response_keys['keep-alive']);
    }
    if ($_response_code == 401 && isset($_response_headers['www-authenticate']) && preg_match('#basic\s+(?:realm="(.*?)")?#i', $_response_headers['www-authenticate'][0], $matches))
    {
        if (isset($_auth_creds[$matches[1]]) && !$_quit)
        {
            $_basic_auth_realm  = $matches[1];
            $_basic_auth_header = '';
            $_retry = $_quit = true;
        }
        else
        {
            show_report(array('which' => 'index', 'category' => 'auth', 'realm' => $matches[1]));
        }
    }
}
while ($_retry);

//
// OUTPUT RESPONSE IF NO PROXIFICATION IS NEEDED
//  

if (!isset($_proxify[$_content_type]))
{
    @set_time_limit(0);
   
    $_response_keys['content-disposition'] = 'Content-Disposition';
    $_response_headers['content-disposition'][0] = empty($_content_disp) ? ($_content_type == 'application/octet_stream' ? 'attachment' : 'inline') . '; filename="' . $_url_parts['file'] . '"' : $_content_disp;
    
    if ($_content_length !== false)
    {
        if ($_config['max_file_size'] != -1 && $_content_length > $_config['max_file_size'])
        {
            show_report(array('which' => 'index', 'category' => 'error', 'group' => 'resource', 'type' => 'file_size'));
        }
        
        $_response_keys['content-length'] = 'Content-Length';
        $_response_headers['content-length'][0] = $_content_length;
    }
    
    $_response_headers   = array_filter($_response_headers);
    $_response_keys      = array_filter($_response_keys);
    
    header(array_shift($_response_keys));
    array_shift($_response_headers);
    
    foreach ($_response_headers as $name => $array)
    {
        foreach ($array as $value)
        {
            header($_response_keys[$name] . ': ' . $value, false);
        }
    }
        
    do
    {
        $data = fread($_socket, 8192);
        echo $data;
    }
    while (isset($data{0}));
        
    fclose($_socket);
    exit(0);
}

do
{
    $data = @fread($_socket, 8192); // silenced to avoid the "normal" warning by a faulty SSL connection
    $_response_body .= $data;
}   
while (isset($data{0}));
   
unset($data);
fclose($_socket);

//
// MODIFY AND DUMP RESOURCE
//

if ($_content_type == 'text/css')
{
    $_response_body = proxify_css($_response_body);
}
else
{
if ($_flags['strip_title'])
    {
        $_response_body = preg_replace('#(<\s*title[^>]*>)(.*?)(<\s*/title[^>]*>)#is', '$1$3', $_response_body);
    }
    if ($_flags['remove_scripts'])
    {
        $_response_body = preg_replace('#<\s*script[^>]*?>.*?<\s*/\s*script\s*>#si', '', $_response_body);
        $_response_body = preg_replace("#(\bon[a-z]+)\s*=\s*(?:\"([^\"]*)\"?|'([^']*)'?|([^'\"\s>]*))?#i", '', $_response_body);
        $_response_body = preg_replace('#<noscript>(.*?)</noscript>#si', "$1", $_response_body);
    }
    if (!$_flags['show_images'])
    {
        $_response_body = preg_replace('#<(img|image)[^>]*?>#si', '', $_response_body);
    }
    
    //
    // PROXIFY HTML RESOURCE
    //
    
    $tags = array
    (
        'a'          => array('href'),
        'img'        => array('src', 'longdesc'),
        'image'      => array('src', 'longdesc'),
        'body'       => array('background'),
        'base'       => array('href'),
        'frame'      => array('src', 'longdesc'),
        'iframe'     => array('src', 'longdesc'),
        'head'       => array('profile'),
        'layer'      => array('src'),
        'input'      => array('src', 'usemap'),
        'form'       => array('action'),
        'area'       => array('href'),
        'link'       => array('href', 'src', 'urn'),
        'meta'       => array('content'),
        'param'      => array('value'),
        'applet'     => array('codebase', 'code', 'object', 'archive'),
        'object'     => array('usermap', 'codebase', 'classid', 'archive', 'data'),
        'script'     => array('src'),
        'select'     => array('src'),
        'hr'         => array('src'),
        'table'      => array('background'),
        'tr'         => array('background'),
        'th'         => array('background'),
        'td'         => array('background'),
        'bgsound'    => array('src'),
        'blockquote' => array('cite'),
        'del'        => array('cite'),
        'embed'      => array('src'),
        'fig'        => array('src', 'imagemap'),
        'ilayer'     => array('src'),
        'ins'        => array('cite'),
        'note'       => array('src'),
        'overlay'    => array('src', 'imagemap'),
        'q'          => array('cite'),
        'ul'         => array('src')
    );
	
    preg_match_all('#(<\s*style[^>]*>)(.*?)(<\s*/\s*style[^>]*>)#is', $_response_body, $matches, PREG_SET_ORDER);

    for ($i = 0, $count_i = count($matches); $i < $count_i; ++$i)
    {
        $_response_body = str_replace($matches[$i][0], $matches[$i][1]. proxify_css($matches[$i][2]) .$matches[$i][3], $_response_body);
    }

preg_match_all("#<\s*([a-zA-Z\?-]+)([^>]+)>#S", $_response_body, $matches);

for ($i = 0, $count_i = count($matches[0]); $i < $count_i; ++$i)
{
	if (!preg_match_all("#([a-zA-Z\-\/]+)\s*(?:=\s*(?:\"([^\">]*)\"?|'([^'>]*)'?|([^'\"\s]*)))?#S", $matches[2][$i], $m, PREG_SET_ORDER))
	{
		continue;
	}
	
	$rebuild    = false;
	$extra_html = $temp = '';
	$attrs      = array();

	for ($j = 0, $count_j = count($m); $j < $count_j; $attrs[strtolower($m[$j][1])] = (isset($m[$j][4]) ? $m[$j][4] : (isset($m[$j][3]) ? $m[$j][3] : (isset($m[$j][2]) ? $m[$j][2] : false))), ++$j);
	
        if (isset($attrs['style']))
        {
            $rebuild = true;
            $attrs['style'] = proxify_inline_css($attrs['style']);
        }
	$tag = strtolower($matches[1][$i]);

	if (isset($tags[$tag]))
	{ 
		switch ($tag)
		{
			case 'a':
				if (isset($attrs['href']))
				{
					$rebuild = true;
					$attrs['href'] = complete_url($attrs['href']);
					$attrs['target'] = '_parent';
				}
             break;
			 case 'img':
				if (isset($attrs['src']))
				{
					$rebuild = true;
					$attrs['src'] = complete_url($attrs['src'],false);
				}
				if (isset($attrs['longdesc']))
				{
					$rebuild = true;
					$attrs['longdesc'] = complete_url($attrs['longdesc'],false);

				}
			break;
			case 'form':
				if (isset($attrs['action']))
				{
					$rebuild = true;
					
					if (trim($attrs['action']) === '')
					{
						$attrs['action'] = $_url_parts['path'];
					}
					if (!isset($attrs['method']) || strtolower(trim($attrs['method'])) === 'get')
					{
						$extra_html = '<input type="hidden" name="' . $_config['get_form_name'] . '" value="' . encode_url(complete_url($attrs['action'], false)) . '" />';
						$attrs['action'] = '';
						break;
					}
					
					$attrs['action'] = complete_url($attrs['action']);
				}
				break;
			case 'base':
				if (isset($attrs['href']))
				{
					$rebuild = true;  
					url_parse($attrs['href'], $_base);
					$attrs['href'] = complete_url($attrs['href']);
				}
				break;
                case 'meta':
                    if ($_flags['strip_meta'] && isset($attrs['name']))
                    {
                        $_response_body = str_replace($matches[0][$i], '', $_response_body);
                    }
                    if (isset($attrs['http-equiv'], $attrs['content']) && preg_match('#\s*refresh\s*#i', $attrs['http-equiv']))
                    {
                        if (preg_match('#^(\s*[0-9]*\s*;\s*url=)(.*)#i', $attrs['content'], $content))
                        {                 
                            $rebuild = true;
                            $attrs['content'] =  $content[1] . complete_url(trim($content[2], '"\''), false);
                        }
                    }
                    break;
                case 'head':
                    if (isset($attrs['profile']))
                    {
                        $rebuild = true;
                        $attrs['profile'] = implode(' ', array_map('complete_url', explode(' ', $attrs['profile'])));
                    }
                    break;
                case 'applet':
                    if (isset($attrs['codebase']))
                    {
                        $rebuild = true;
                        $temp = $_base;
                        url_parse(complete_url(rtrim($attrs['codebase'], '/') . '/', false), $_base);
                        unset($attrs['codebase']);
                    }
                    if (isset($attrs['code']) && strpos($attrs['code'], '/') !== false)
                    {
                        $rebuild = true;
                        $attrs['code'] = complete_url($attrs['code'], false);
                    }
                    if (isset($attrs['object']))
                    {
                        $rebuild = true;
                        $attrs['object'] = complete_url($attrs['object'], false);
                    }
                    if (isset($attrs['archive']))
                    {
                        $rebuild = true;
                        $attrs['archive'] = implode(',', array_map('complete_url', preg_split('#\s*,\s*#', $attrs['archive'])));
                    }
                    if (!empty($temp))
                    {
                        $_base = $temp;
                    }
                    break;
                case 'object':
                    if (isset($attrs['usemap']))
                    {
                        $rebuild = true;
                        $attrs['usemap'] = complete_url($attrs['usemap'], false);
                    }
                    if (isset($attrs['codebase']))
                    {
                        $rebuild = true;
                        $temp = $_base;
                        url_parse(complete_url(rtrim($attrs['codebase'], '/') . '/', false), $_base);
                        unset($attrs['codebase']);
                    }
                    if (isset($attrs['data']))
                    {
                        $rebuild = true;
                        $attrs['data'] = complete_url($attrs['data'], false);
                    }
                    if (isset($attrs['classid']) && !preg_match('#^clsid:#i', $attrs['classid']))
                    {
                        $rebuild = true;
                        $attrs['classid'] = complete_url($attrs['classid'], false);
                    }
                    if (isset($attrs['archive']))
                    {
                        $rebuild = true;
                        $attrs['archive'] = implode(' ', array_map('complete_url', explode(' ', $attrs['archive'])));
                    }
                    if (!empty($temp))
                    {
                        $_base = $temp;
                    }
                    break;
                case 'param':
                    if (isset($attrs['valuetype'], $attrs['value']) && strtolower($attrs['valuetype']) == 'ref' && preg_match('#^[\w.+-]+://#', $attrs['value']))
                    {
                        $rebuild = true;
                        $attrs['value'] = complete_url($attrs['value'], false);
                    }
                    break;
			case 'frame':
			case 'iframe':
				if (isset($attrs['src']))
				{
					$rebuild = true;
					$attrs['src'] = complete_url($attrs['src']) . '&nf=1';
				}
				if (isset($attrs['longdesc']))
				{
					$rebuild = true;
					$attrs['longdesc'] = complete_url($attrs['longdesc']);
				}
				break;
			case 'script':
				if (isset($attrs['src']))
				{
					$rebuild = true;
					$attrs['src'] = complete_url($attrs['src']) . '&nf=1';
				}
				break;
			case 'link':
				if (isset($attrs['href']))
				{
					$attrs['href'] = complete_url($attrs['href']) . '&nf=1';
					$rebuild = true;
				}
				break;
			default:
				foreach ($tags[$tag] as $attr)
				{
					if (isset($attrs[$attr]))
					{
						$rebuild = true;
						$attrs[$attr] = complete_url($attrs[$attr], false);
					}
				}
				break;
		}
	}

	if ($rebuild)
	{
		$new_tag = "<$tag";
		foreach ($attrs as $name => $value)
		{
			$delim = strpos($value, '"') && !strpos($value, "'") ? "'" : '"';
			$new_tag .= ' ' . $name . ($value !== false ? '=' . $delim . $value . $delim : '');
		}
		
		$_response_body = str_replace($matches[0][$i], $new_tag . '>' . $extra_html, $_response_body);
	}
}    	
}

$_response_keys['content-disposition'] = 'Content-Disposition';
$_response_headers['content-disposition'][0] = empty($_content_disp) ? ($_content_type == 'application/octet_stream' ? 'attachment' : 'inline') . '; filename="' . $_url_parts['file'] . '"' : $_content_disp;
$_response_keys['content-length'] = 'Content-Length';
$_response_headers['content-length'][0] = strlen($_response_body);    
$_response_headers   = array_filter($_response_headers);
$_response_keys      = array_filter($_response_keys);

header(array_shift($_response_keys));
array_shift($_response_headers);

foreach ($_response_headers as $name => $array)
{
    foreach ($array as $value)
    {
        header($_response_keys[$name] . ': ' . $value, false);
    }
}

//Remove Frame Breaking Code
$_response_body = str_replace('if (top.location != self.location) top.location = self.location;','//[ Frame Breaking Code Removed by DeveloperView]', $_response_body );

echo $_response_body;

?>