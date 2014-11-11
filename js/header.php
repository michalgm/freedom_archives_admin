<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>Freedom Archives Search Engine</title>

		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="author" content="Gazi Mahmud">
		<meta name="description" content="The Freedom Archives contains over 10,000 hours of audio and video tapes. These recordings date from the late-60s to the mid-90s and chronicle the progressive history of the Bay Area, the United States, and international solidarity movements. The collection includes weekly news/ poetry/ music programs broadcast on several educational radio stations; in-depth interviews and reports on social and cultural issues; diverse activist voices; original and recorded music, poetry, original sound collages; and an extensive La Raza collection." />

		<link media="all" rel="stylesheet" type="text/css" href="css/style.css">
		<link media="all" rel="stylesheet" type="text/css" href="css/style_002.css">
		<link media="all" rel="stylesheet" type="text/css" href="css/menu.css">
		<script type="text/javascript" src="js/modernizer.js"></script>
		<link rel="shortcut icon" type="image/gif" href="images/favicon.gif">

		<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', 'UA-32592340-1']);
			_gaq.push(['_trackPageview']);

			(function() {
				var ga = document.createElement('script');
				ga.type = 'text/javascript';
				ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0];
				s.parentNode.insertBefore(ga, s);
			})();

			var ajax_load = "<img src='images/ajax-loader.gif' alt='loading...' />";

			function fetchMore(fieldName, limitvalue) {
				var newvalue = ((limitvalue * 1) + (10 * 1));
				$("#" + fieldName).html(ajax_load).load("lib/facets/loadfacetvalues.php?FACET_FIELD=" + fieldName + "&fetchAll=true&newlimitvalue=" + newvalue);
			}

			function fetchLess(fieldName, limitvalue) {
				var newvalue = ((limitvalue * 1) - (10 * 1));
				$("#" + fieldName).html(ajax_load).load("lib/facets/loadfacetvalues.php?FACET_FIELD=" + fieldName + "&fetchAll=true&newlimitvalue=" + newvalue);
			}
			
			var singledocwindow;
			function viewSingleDoc(url)
			{
				singledocwindow=window.open(url,'name','height=550,width=600,location=0,toolbar=0,resizable=1');
				if (window.focus) {singledocwindow.focus()}
			}
		</script>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
	</head>

	<body bgcolor="#E9E9B8">
