<?php
/**
 * Generates statistics of tags and pages by bureau
 * @author Chris Barna
 * @package DV
 *
 */

 /** 
 * Grab Config and connect to DB
 */
$backend = true;
require_once("../config.php");

//Tags to look for
$tags = array( 'consumer', 'industry', 'archive', 'rewrite' );

//Init results array
$results = array();

/**
 * A function to turn the numbers into round percents.
 *
 * to_percent($count, $out_of, $reverse) can do two things:
 * 1. If you want the results to do completion, $count is the number of untagged
 *    pages that is subtracted from $out_of. This is when $reverse=true.
 * 2. The other way is to do a straight percentage. When $reverse=false. 
 */
function to_percent($count, $out_of, $reverse=true) {
	if ((($out_of-$count) != 0) && ($reverse)) {
		return number_format((($out_of-$count)/$out_of)*100,2);
	} else if (($count != 0) && (!$reverse)) {
		return number_format(($count/$out_of)*100,2);
	} else {
		return number_format(0, 2);
	}
}

// Set the matching string or else die.
if ( !empty( $_GET['ob'] ) ) {
	$domain_match = strip_tags( $_GET['ob'] );
} else { 
	die('No office or bureau selected'); 
}

// Get the list of excluded URLs.
$sql1 = "SELECT URL FROM `exclusions`";
$excluded_result = mysql_query($sql1);

// This query will select the URLS from all pages that DO NOT have tags.
$sql = "SELECT `pages`.URL FROM `pages` LEFT JOIN `tags-pages` ON `tags-pages`.PageID = `pages`.ID WHERE `tags-pages`.PageID IS NULL AND (`pages`.URL LIKE 'http://www.fcc.gov/".$domain_match."/%' or `pages`.URL LIKE 'http://".$domain_match.".fcc.gov/%') AND (";

// This builds the exclusion list based onthe result of the exluded URLS
$i=0;
while ($exclusion = mysql_fetch_assoc($excluded_result)) {
	if ($i != 0) $exclusion_list .= " AND ";
	$exclusion_list .= "`pages`.URL NOT LIKE 'http://www.fcc.gov".$exclusion['URL']."%' ";
	$i = $i +1 ;
}

// Finish the SQL and then query the DB.
$sql .= $exclusion_list.") GROUP BY `pages`.ID";
$untagged_result = mysql_query($sql);
$results['pages']['untagged'] = mysql_num_rows($untagged_result);

// This query will select all of the rows in a given subdomain. Used only for making the percentage.
$sql = "SELECT COUNT(*) FROM `pages` WHERE (`pages`.URL LIKE 'http://www.fcc.gov/".$domain_match."/%' or `pages`.URL LIKE 'http://".$domain_match.".fcc.gov/%') AND (".$exclusion_list.")";

$pages_result = mysql_query($sql);
$pages_num = mysql_fetch_row($pages_result);
$results['pages']['total'] = $pages_num[0];
$results['pages']['tagged'] = $results['pages']['total'] - $results['pages']['untagged'];

// Count tags within a subdirectory/subdomain.
$sql = "SELECT `tags-pages`.TagID, COUNT(*) FROM `pages` JOIN `tags-pages` ON `tags-pages`.PageID = `pages`.ID WHERE (";

// Query to get a tag cloud.
$sql = "SELECT  `tags-pages`.TagID, COUNT(*) FROM `pages` LEFT JOIN `tags-pages` ON `tags-pages`.PageID = `pages`.ID WHERE `tags-pages`.TagID IS NOT NULL AND (`pages`.URL LIKE 'http://www.fcc.gov/".$domain_match."/%' or `pages`.URL LIKE 'http://".$domain_match.".fcc.gov/%') GROUP BY `tags-pages`.TagID";

$tags_result = mysql_query($sql);

// Put the results from the tag query into an array.
while ($tag = mysql_fetch_assoc($tags_result)) {
	if ($tag["TagID"] != 22 ) {
		$tag_name = get_tag($tag["TagID"]);
		$results['tags'][$tag_name] = $tag["COUNT(*)"];
	}	
}

// The only results we absolutely care about are the four tags listed at the top. This ensures they have valus. 
foreach ($tags as $ID => $tag) {
	// If there are no pages tagged, change the number to zero.
	if (!$results['tags'][$tag]) {
		$results['tags'][$tag] = 0;
	}
}

// Sort the tag array.
arsort($results['tags']);

//if they asked for json, give them json
if ( $_GET['json'] ) {
	echo json_encode( $results  );
	exit();
}
?>
<?php 
	//calculate percent complete
	$percent_complete = to_percent($results['pages']['untagged'], $results['pages']['total']);
	if ($percent_complete > 90) $color = 'green';
	else if ($percent_complete > 45) $color = 'orange';
	else $color = 'red';
?>
	
<div class='complete' id="complete-stats">
	<span class='number <?php echo $color; ?>'><?php echo $percent_complete; ?></span>
	<span class='percent'>%</span><br />
	Complete
</div>

<div class='progress-bar'>
	<div class='percent-complete' style="width: <?php echo number_format($percent_complete,0); ?>%; background-color:<?php echo $color; ?>"> &nbsp;
	</div>
</div>

<div class="clearfix"> &nbsp; </div>
<div style='height:20px; width: 100%'>&nbsp;</div>
 
<h3 id="general-statistics">General Statistics</h3> 
<div class='result-row'>
	<span class='value'><?php echo number_format($results['pages']['total'],0); ?></span> pages in <?php echo strtoupper($domain_match);  ?>
</div>
<div class='result-row'>
	<span class='value'><?php echo number_format($results['pages']['tagged'],0); ?></span> pages tagged
</div>
<div class='result-row'>
	<span class='value'><?php echo number_format($results['pages']['untagged'],0); ?></span> pages untagged
</div>
<div class='clearfix'>&nbsp;</div>

<h3 id="tagging-stats">Tagging Statistics</h3>
<?php foreach ($tags as $tag) { ?>
<div class='result-row'>
	<span class='value'><?php echo $results['tags'][$tag]; ?></span> 
	Pages Tagged <?php echo ucfirst( $tag ); ?> 
	(<?php if(isset($results['tags'][$tag])) { echo number_format(to_percent($results['tags'][$tag], $results['pages']['tagged'], false),2); } else { echo "0"; } ?>% of tagged pages)

</div>
<?php } ?>
<p><a href="#general-statistics" class="tags-link">View full tagging statistics</a></p>

<div id="full-tag-stats" style="display:none;">
<h4>All Tags</h4>
<?php foreach ($results['tags'] as $tag_name => $tag_count) { ?>

<div class='result-row'>
	<span class='value'><?php echo $tag_count; ?></span> 
	Pages Tagged "<span style="color:black"><?php echo $tag_name; ?></span>" 
	(<?php echo number_format(to_percent($tag_count, $results['pages']['tagged'], false),2); ?>% of tagged pages)

</div>

<?php } ?>
</div>
<div class='clearfix'>&nbsp;</div>

<h3>Pages Remaining</h3>
<ul>
<?php while ($url = mysql_fetch_assoc($untagged_result)) { ?>
	<li>
		<a href="http://intranet.fcc.gov/fcconline/labs/dv/?url=<?php echo urlencode( $url['URL'] ); ?>"><?php echo $url['URL']; ?></a>
	</li>
<?php } ?>
</ul>
