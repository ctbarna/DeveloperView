------------------------------
		DeveloperView
------------------------------

	Adapted from PHProxy by Abdullah Arif (version last modified 5:27 PM 1/20/2007)
	PHProxy is distributed under GNU GPL


CHANGELOG
--------------
v1.5 -- Codename: Cupcakes 
 * NEW: Google Analytics API support.
 * NEW: Administrative statistics.
 * UPDATE: Front end redesign.
 * NEW: JSON API endpoints.
 * UPDATE: Steamlined tagging interface.
 * UPDATE: Backend loader streamlined.
 * NEW: "Subsites" allow for customized tagging statistics.

v1 -- Initial Release 	

	
FILES
---------------

	/index.php 					<- DeveloperView core file, calls all necessary files
	/config.php					<- Configuration for DeveloperView (and PHProxy core), edit freely
	/process-ajax.php			<- File which recieves and processes AJAX calls from the DeveloperView Header
	/create-tables.sql			<- SQL script to create the necessary tables
	/css						<- CSS files
		/phproxy.css 			<- CSS files used to generate PHProxy error messages (404s, blacklisted url, etc.)
		/dv.css					<- Main CSS file used by DeveloperView, injected into <head> of HTML document on load
		/autocomplete.css		<- CSS file used in autocompletion of tag results
	/js							<- JavaScript files
		/jquery.min.js			<- jQuery core
		/jquery.cookie.js		<- jQuery cookie plugin
		/jquery.autocomplete.js <- jQuery autocomplete plugin
		/dv.js					<- DeveloperView JavaScript core 
	/img						<- Image files
	/includes					<- PHP files included in other PHP files, never executed independently
		/drop-ins				<- PHP files which are rendered and injected into the HTML output the user sees
			/head.php			<- Rendered and injected immediately before </head> tag 
			/body.php			<- Rendered and injected immediately after <body> tag (generates the header you see)
		/functions.php			<- DeveloperView core PHP functions, used throughout
		/functions-mysql.php	<- Small set of mysql functions used by DeveloperView
		/PHProxy
			/core.php			<- Modified PHProxy core file
			/index.inc.php		<- File called by PHProxy to generate errors (404s, blacklisted url, etc.), relies on phproxy.css
			/LICENSE.TXT		<- GNU GPL License for PHProxy
			/README.TXT			<- PHProxy ReadMe file with explanation of settings, etc.
			
DESCRIPTION OF PROCESS
----------------------

DeveloperView relies on open-source proxy PHProxy to handle requests for web pages.  PHProxy rewrites all URLs in the target page to be routed through itself and allows the user to control several aspects of their browsing experience such as the ability to remove JavaScript or images, or to block cookies from the target page.  As PHProxy proceses the user's requested page, DeveloperView injects the output of one PHP file immediately before the conclusion of the document <head> tag to provide support for JavaScript and style sheets, and the output of another PHP file immediately after the start of the document <body> tag to include the user interface.  Both inclusions are done via a Regular Expression and a delivered to the user at the time the request page is sent.

WHAT IS A PROXY
-----------------------

A proxy is a web page that acts as an intermediary between a user and a page they request.  The user directs their web browser at the proxy, as they would any other webpage, and indicates which page they are requesting.  The proxy then retrieves that page, on the user's behalf, and returns the page to the user.  This intermediate step allows the proxy server to modify the requested page before it is served to the user.  No additional software is required to be installed on the user's computer to utilize the proxy.
	
GOALS OF PROJECT
-----------------------

DeveloperView seeks to create an open-source, light-weight, scalable means of providing website stake holders with the ability to view, organize, and most importantly collaborate in the management of website content and development. Specifically, DeveloperView supports the lawyer of three sources of information on top of a target pgae: data collected through DeveloperView itself (such as tags, notes, or a repository of site URLs), data requested from a third-party source (such as displaying the most popular user-generated Delicious tags for the current page), and data retrieved from existing, linked sources such as external databases.

ROADMAP
-------------------------
	* Finish toggle pannel UI (shaddow)
	* General Code Cleanup
	* General UI styling / clean-up
	* Creation of a means to visualize data gathered (e.g., site map generation, drill-downs, etc.)
	* Setup wizard on intitial load
	* Creation of MySQL tables if necessary
	* Detection / Storage of Page Titles
	* Retrieval and display of analytics data

LIMITATIONS & KNOWN BUGS
-------------------------
	* Some Stylesheets still break the tools drop-down
	

	
	
