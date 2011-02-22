<?php
 
/**
 * DeveloperView.
 *
 * DeveloperView relies on open-source proxy PHProxy to handle requests for web pages.  PHProxy rewrites all URLs in the target page to be routed through itself and
 * allows the user to control several aspects of their browsing experience such as the ability to remove JavaScript or images, or to block cookies from the target page. 
 * As PHProxy proceses the user's requested page, DeveloperView injects the output of one PHP file immediately before the conclusion of the document <head> tag to provide 
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

if ( isset($_GET['nf']) ) {
	header('Location: proxy.php?url=' . urlencode( $_GET['url'] ) );
	exit();
}

if ( !isset($_GET['url']) ) {
	header('Location: ?url=' . urlencode( $_config['default_url'] ) );
	exit();
}


?>
<html>
	<head>
		<title>iFrame Test</title>
		<style>
			#popup {float:left; z-index: 100; background: #fff; left:5px; top: -150px; position: relative; width:100px;}
			iframe {z-index: 0;}
			body {margin: 0;}
* html body{ overflow:hidden; }
# html body{ overflow:hidden; }
		</style>
		<script>
		if (top.location != self.location) top.location = self.location;
		</script>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
		<script>
			$(document).ready(function(){
				$('#popup').hide();
				$('#button').click(function(){
					$('#popup').toggle('slow');
					return false;
				});
			});
		</script>
	</head>
	<body>
		<iframe src="proxy.php?url=<?php echo $_GET['url']; ?>" width="99.5%" height="95%"></iframe>
		<div id='menu'>
			<a href='#' id='button'>Button</a>
		</div>
		<div id='popup'>
			<ul>
				<li>blah</li>
				<li>blah</li>
				<li>blah</li>
				<li>blah</li>
				<li>blah</li>
			</ul>
		</div>

	</body>
</html>