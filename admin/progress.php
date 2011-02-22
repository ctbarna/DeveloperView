<?php
$title = "Tagging Progress";
include('header.php');
if ( !empty( $_GET['ob'] ) )
	$_GET['ob'] = strtoupper($_GET['ob']);

/* $b_and_os = array(
	'Consumer and Governmental Affairs Bureau' => 'CGB',
	'Enforcement Bureau' => 'EB',  
	'International Bureau' => 'IB', 
	'Media Bureau' => 'MB', 
	'Wireless Telecommunications Bureau' => 'wireless', 
	'Public Safety and Homeland Security Bureau' => 'PSHS',
	'Wireline Competition Bureau' => 'WCB',
	'Office Of Administrative Law Judges' => 'OALJ',
	'Office Of Communications Business Opportunities' => 'OCBO',
	'Office Of Engineering And Technology' => 'OET',
	'Office Of General Counsel' => 'OGC',
	'Office Of Inspector General' => 'OIG',
	'Office Of Legislative Affairs' => 'OLA', 
	'Office Of Managing Director' => 'OMD', 
	'Office Of Media Relations' => 'OMR',
	'Office Of Strategic Planning And Policy Analysis' => 'OSP',
	'Office Of Work Place Diversity' => 'OWD',
	'Office Of The Secretary' => 'OSEC'
); */
// Office and Bureau prefixes
$sql = "SELECT subsite_title, subsite_short_title FROM `subsites`";
$result = mysql_query($sql);

$b_and_os = array();

while ($row = mysql_fetch_assoc($result)) {
	$b_and_os[$row['subsite_title']] = $row['subsite_short_title']; 
}
?>

<script type="text/javascript">
		(function($)
		{
			$.fn.blink = function(options)
			{
				var defaults = { delay:500 };
				var options = $.extend(defaults, options);
				
				return this.each(function()
				{
					var obj = $(this);
					setInterval(function()
					{
						if($(obj).css("visibility") == "visible")
						{
							$(obj).css('visibility','hidden');
						}
						else
						{
							$(obj).css('visibility','visible');
						}
					}, options.delay);
				});
			}
		}(jQuery))

		var incrementPercent = function(i,progress) {
			$('.complete .number').html(i.toFixed(2));
			$('.percent-complete').css("width",i+"%");
			i = i + (progress / 100);
			if (i <= progress) setTimeout("incrementPercent("+i+","+progress+")",10);
			if (i > progress) {
				$('.complete .number').html(progress);
			}
		};

		function changeOffice (officeShortName) {
			location.hash = officeShortName;
			$('#completion-results').hide();
			$('#progress-loader').show();
			$('#completion-results').load('get_progress.php?ob=' + officeShortName, function(response, status, xhr) {
				$('#progress-loader').hide();
				$('.percent-complete').hide();
				$('#completion-results').show();	
				$('.percent-complete').show();
				incrementPercent(0,parseFloat( $('.complete .number').html()) );
				
			});
		}

$(document).ready(function() {	
		if ( $('.complete .number') ) 
			incrementPercent(0,parseFloat( $('.complete .number').html()) );

		if (location.hash) {
			$("#ob").val(location.hash.substr(1));
			changeOffice(location.hash.substr(1));	
		}

$(".tags-link").live('click', function () {
	event.preventDefault();
	$("#full-tag-stats").slideToggle();

	
	if ($(".tags-link").html() == "View full tagging statistics") {
		$("html,body").animate({
			scrollTop: $("#tagging-stats").offset().top
		}, 2000);

		$(".tags-link").html("Hide full tagging statistics");
	} else {
		$("html,body").animate({
			scrollTop:0 
		}, 2000);

		$(".tags-link").html("View full tagging statistics");
	}
});

		$('#progress-loader').hide();
		$('#ob').change(function() {
			changeOffice($("#ob").val());
		});
	});
</script>
<form method='get'>
<label for="ob">Office/Bureau:</label>
<select name="ob" id="ob">
	<option value=""></option>
<?php foreach ($b_and_os as $label => $value) { ?>
	<option value="<?php echo $value; ?>"<?php if ( !empty($_GET['ob']) && $_GET['ob'] == $value) echo ' SELECTED'; ?>><?php echo $label; ?> </option>
<?php } ?>
</select>
<noscript>
	<input type='submit' value='Go'>
</noscript>
</form>
<div id='progress-loader'>
	<img src='../img/loader.gif' id='loader' alt="Loading..." /> Retrieving results... This may take up to a minute.
</div>
<div id='completion-results'><?php
	if ( !empty( $_GET['ob'] ) )
		include( 'get_progress.php' );
	else
		echo '&nbsp;';
?></div>
<?php include('footer.php'); ?> 
