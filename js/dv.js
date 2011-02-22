/*
 Turns loading image on and off
*/
function loaderImage(onoff) {
	if (onoff == "on") {
		$('#menu-bar').css('background-image', "url('img/loader.gif')");
		return true;
	} else {
		$('#menu-bar').css('background-image', "none");		
		return false;
	}
}

$(document).ready(function(){
		
		/*
			Hide Loader image on load (but put in CSS so it loads)
		*/
		loaderImage('off');
		
		$('#main-view').height( ( $(window).height() ) );
		
		/*
			Store the User's name as a cookie so don't have to keep retyping it
		*/
		$('#username').change(function() {
			$.cookie("dv-user", $('#username').val().replace('/(\[|\])/','-'), { expires: 14 });
			$('#user-div').css('border','1px solid white');
			$('.username-missing').hide();
		});
		
		
	/*
		======== TOOLS and AJAX ==========
	*/
	
		/*  
			Adding TAGS
			NOTE TO SELF: NEED TO ADD TAG TO DIV ONCE ADDED
		*/
		$('#add-tag-btn').click(function() {
			if ($('#username').val() == '') {
				$('#tags .username-missing').show();
				$('#user-div').css('border','1px solid red');
				return false;
			}
	   		loaderImage('on');
			$.ajax({
				type: "POST", 
				dataType: 'json',
				url: "process-ajax.php?action=add-tag",
				data: 'tags=' + $('#add-tag').val() +'&user=' + $('#username').val() +'&pageID=' + $('#pageID').val(),
				success: function(data){
					loaderImage('off');
					for (var i = 0;i<data.length;i++) {
						$("#tags ul").append("<li id='tag["+data[i]['TagID']+"]' style='display:none'><abbr title='Added a moment ago by you'>"+data[i]['tag']+"</abbr> [<a href='#' class='remove-tag' title='Remove Tag &quot;"+data[i]['tag']+"&quot;'>X</a>]</li>");
						$("#tags ul li:last").fadeIn('slow');
					}
				$('#add-tag').attr("value","");
				}
			});	
		});
		
		/*
			Removing Tags
		*/
		
		$('.remove-tag').live('click', function() {
	   		loaderImage('on');
			var li = $(this).parent();
			if (confirm("Are you sure you want to remove the tag '"+li.children('abbr').text()+"'?")) {
				$.ajax({
					type: "GET",
					dataType: "json",
					url: "process-ajax.php",
					data: "action=remove-tag&pageID=" + $('#pageID').val() + "&tagID=" + li.attr('ID').replace(/[^\d\.]/g, ''),
					success: function(data) {
						loaderImage('off');
						$(li.fadeOut('slow'));
					}
			});		   
		}
		});
		
		/*  
			Adding NOTES 
		*/
		$('#add-note-btn').live('click', function() {	  
			if ($('#username').val() == '') {
				$('#user-div').css('border','1px solid red');
				$('#notes .username-missing').show();
				return false;
			} 
	   		loaderImage('on');
			$.ajax({
				type: "POST",
				url: "process-ajax.php?action=add-note",
				dataType: 'json',
				data: 'note=' + $('#note').val() +'&user=' + $('#username').val() +'&pageID=' + $('#pageID').val(),
				success: function(data){
					loaderImage('off');
					if (data['NoteID']) $("#notes ul").append("<li id='note["+data['NoteID']+"]' style='display:none'>"+$('#note').val()+"<div class='note-info'> Added just a moment ago by You<div class='remove-note-div'>\r\n[<a href='#' class='remove-note' title='Delete This Note'>X</a>]\r\n</div></div></li>");
					$("#notes ul li:last").fadeIn('slow');
					$('#note').attr("value","");
				}
			});	
		});
		
		/*
			Removing Notes
		*/
		
		$('.remove-note').live('click',function() {
	   		loaderImage('on');
			var li = $(this).parent().parent().parent();
			if (confirm("Are you sure you want to delete this note?")) {
				$.ajax({
					type: "GET",
					dataType: "json",
					url: "process-ajax.php",
					data: "action=remove-note&noteID=" + li.attr('ID').replace(/[^\d\.]/g, '') + '&pageID=' + $('#pageID').val(),
					success: function(data) {
						loaderImage('off');
						$('#notes .loader').hide();
						$(li.fadeOut('slow'));
					}
			});		   
		}
		});
		
		
		/*
			Show and Hide remove note button on hover over note
		*/
				
		$("#notes ul li").live('mouseover', function(){
            $(this).children().children().show();
		});
		$("#notes ul li").live('mouseout', function(){
        	$(this).children().children().hide();
		});
				

		/*
			Initialize Tag Autocompletion
		*/
		
		$("#add-tag").autocomplete(tags, {
			multiple: true,
			autoFill: true
		});

		/*
			Get GA Data
		*/
	   	loaderImage('on');
		$.ajax({
			type: "GET",
			dataType: "json",
			url: "process-ajax.php",
			data: "action=get-analytics&url=" + $('#pageURL').val(),
			success: function(data) {
				loaderImage('off');
				$('#metadata #loading').hide();
				$('#metadata .content').append(data);
				if ( $('#metadata').height() > 24 )
					$('#metadata').height( $('#metadata').height() + 10 );
			},
			error: function() {
				loaderImage('off');
				$('#metadata #loading').hide();
			}
		});

	// Slide toggles for the tabs
	$(".drawer h3").click(function() {

		// Set variable names of heights
		contentHeight = $(this).siblings(".content").height()+15;
		headHeight = $(this).height();
		totalHeight = contentHeight + headHeight;

		// Toggle visibility of the content.
		$(this).siblings(".content").toggle();

		if($(this).siblings(".content").is(":visible")) {
			// Animate the slide up and change the arrow icon.
			$(this).parent().animate({height: [totalHeight]},null,null,function(){cookieRefresh()})
			$(this).css("background-image", "url(img/arrow-down.png)");
		} else {
			// Animate the slide down and change the arrow.
			$(this).parent().animate({height: headHeight},null,null,function(){cookieRefresh()});
			$(this).css("background-image", "url(img/arrow-up.png)");
		}
		
		//set cookie
		function cookieRefresh() {

			var panelState = [];
			$('#panels .drawer').each(function(index){
				if ($(this).height() < 30) {
					panelState[index] = "off";
				} else {
					panelState[index] = "on";
				}
			});
			$.cookie('dv-pannels', panelState);
			
		}
		
	});
	
	// Animate the drawers when they are closed.
	$(".drawer").height(24);	
	$(".drawer").hover(function() {	
		if($(this).height() <= 24) {
			$(this).animate({height: 29}, 100);
		}
	}, function() {
		if($(this).height() <= 29) {
			$(this).animate({height: 24}, 100);
		}
	});
	
	//URL box
	$('#address-go').click(function(){
		window.location = "?url=" + $('#url').val(); 
	});
		
				for (var i=0; i < $('#panels .drawer').length; i++) {
			var cookie = $.cookie("dv-pannels").split(',');
			if (cookie[i] == "on") {
				$('#panels .drawer:eq('+i+') h3').click();
			}
		}
	
	document.title = 'Developer View - ' + $('#main-view').documentElement.title;
	
});
