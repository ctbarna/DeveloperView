/**
 * DeveloperView Bookmarlet
 *
 * Allows user to view current page in DeveloperView
 *
 * @version 1.0a
 * @package DeveloperView
 */

if (typeof jQuery == 'undefined') {
	var jQ = document.createElement('script');
	jQ.type = 'text/javascript';
	jQ.onload=runthis;
	jQ.src = 'http://intranet.fcc.gov/fcconline/labs/dv/js/jquery.min.js';
	document.body.appendChild(jQ);
} else {
	runthis();
}


function runthis() {

	//Verify that they are on an FCC.gov page, otherwise err out
	if (!window.location.href.match(/fcc\.gov/)) {
	
		alert('Please navigate to a FCC.Gov page before trying to access DeveloperView');
		
	} else {
	
		//Load the URLEnocoding jQuery Plugin
		$.extend({URLEncode:function(c){var o='';var x=0;c=c.toString();var r=/(^[a-zA-Z0-9_.]*)/;
		  while(x<c.length){var m=r.exec(c.substr(x));
			if(m!=null && m.length>1 && m[1]!=''){o+=m[1];x+=m[1].length;
			}else{if(c[x]==' ')o+='+';else{var d=c.charCodeAt(x);var h=d.toString(16);
			o+='%'+(h.length<2?'0':'')+h.toUpperCase();}x++;}}return o;},
		URLDecode:function(s){var o=s;var binVal,t;var r=/(%[^%]{2})/;
		  while((m=r.exec(o))!=null && m.length>1 && m[1]!=''){b=parseInt(m[1].substr(1),16);
		  t=String.fromCharCode(b);o=o.replace(m[1],t);}return o;}
		});
		
		//Append Current URL to DV URL and redirect user
		$(window.location).attr('href','http://intranet.fcc.gov/fcconline/labs/dv/index.php?url='+$.URLEncode(window.location));

	}
}