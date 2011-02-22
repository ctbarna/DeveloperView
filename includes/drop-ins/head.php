<?php
/*
Contents of this PHP file parsed and dropped into final HTML page immediately before </head> tag
(used to inject JS, CSS, etc.)

USAGE NOTE: 	Because this page is called after the requested page's original CSS file, 
				any CSS file called here should (in theory) override the original CSS
*/
global $_script_base; //grab the base URL from PHProxy for absolute URLs
?>

<!-- DeveloperView header START -->

<link rel='stylesheet' href='<?php echo $_script_base; ?>css/dv.css' type='text/css'>
<link rel='stylesheet' href='<?php echo $_script_base; ?>css/jquery.autocomplete.css' type='text/css'>
<script type='text/javascript' src='<?php echo $_script_base; ?>js/jquery.min.js'></script>
<script type='text/javascript' src='<?php echo $_script_base; ?>js/jquery.cookie.js'></script>
<script type='text/javascript' src='<?php echo $_script_base; ?>js/jquery.autocomplete.min.js'></script>
<script type="text/javascript" src="js/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="js/jquery.dimensions.js"></script>

<!--[if IE]>
<style>
#dv-border {background: none; background-image: url("img/tools-bg.png"); background-position:center top; background-repeat:repeat-y;}
#dv-tools {background: none; background-image: url("img/tools-btm.png"); background-position:center bottom; background-repeat:no-repeat;}
#dv-header, #dv-shadow, #dv-toggles, #dv-toggle-shadow, #dv-tools, #dv-branding, #help-bubble{position: absolute;}
</style>
<![endif]-->
<script src='<?php echo $_script_base; ?>js/dv.min.js' language='javascript' type='text/javascript'></script>
<script><?php foreach (get_tags() as $tag) $tags[] = $tag['Tag'];  echo 'var tags = ["' . implode('","',$tags) . '"]'; ?></script>
<!-- DeveloperView header END -->

