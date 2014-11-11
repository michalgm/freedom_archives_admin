<?php 
$pagetype = '';
if (strstr(basename($_SERVER['PHP_SELF']), 'index')) {
	$pagetype = 'home';
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Freedom Archives Search Engine</title>

		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="author" content="Gazi Mahmud" />
		<meta name="description" content="The Freedom Archives contains over 10,000 hours of audio and video tapes. These recordings date from the late-60s to the mid-90s and chronicle the progressive history of the Bay Area, the United States, and international solidarity movements. The collection includes weekly news/ poetry/ music programs broadcast on several educational radio stations; in-depth interviews and reports on social and cultural issues; diverse activist voices; original and recorded music, poetry, original sound collages; and an extensive La Raza collection." />

		<link rel="shortcut icon" type="image/gif" href="images/favicon.gif" />
		<link rel="stylesheet" href="font/stylesheet.css" type="text/css" media="all" />
		<script type="text/javascript" src="bower_components/jquery/dist/jquery.min.js"></script>
		<script type="text/javascript" src="bower_components/jquery-ui/jquery-ui.min.js"></script>
		<script type="text/javascript" src="bower_components/jplayer/jquery.jplayer/jquery.jplayer.js"></script>
		<script type="text/javascript" src="bower_components/jquery-modal/jquery.modal.min.js"></script>
		<script type="text/javascript" src="bower_components/jquery-expander/jquery.expander.min.js"></script>
		<script type="text/javascript" src="bower_components/flexslider/jquery.flexslider-min.js"></script>
		<script type="text/javascript" src="freedomarc.js"></script>
		<link type="text/css" rel="stylesheet" href="bower_components/jquery-ui/themes/smoothness/jquery-ui.min.css" media="all" />
		<link type="text/css" rel="stylesheet" href="bower_components/jplayer/skin/midnight.black/jplayer.midnight.black.css"/>
		<link type="text/css" rel="stylesheet" href="bower_components/jquery-modal/jquery.modal.css"/>
		<link type="text/css" rel="stylesheet" href="bower_components/flexslider/flexslider.css"/>
		<link type="text/css" rel="stylesheet" href="includes/tag-cloud/css/tagcloud.css"/>
		<link media="all" rel="stylesheet" type="text/css" href="css/style.css" />
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

		</script>
	</head>

	<body class='<?php echo $pagetype ?>'>
		<!-- HEADER  -->
		<div id="header">
			<div id='header_links'> <a href='/'>Search&raquo;</a> <a href='http://freedomarchives.org'> Home&raquo;</a></div>
		</div>
		<!-- HEADER ENDS-->

		<!-- MAIN CONTAINER -->
		<div id="body">
			<div id='help_info'>
				<h2>Search Help</h2>
				<div id='help_contents'>
					<dl>
						<dt>How does this work?</dt>
						<dd>There are many ways to search the collections of the Freedom Archives. Below is a brief guide that will help you conduct effective searches. Note, anytime you search for anything in the Freedom Archives, the first results that appear will be our digitized items. Information for items that have yet to be scanned or yet to be digitized can still be viewed, but only by clicking on the <a href='#'>show</a> link that will display the hidden (non-digitized) items. If you are interested in accessing these non-digitized materials, please email <a href='mailto:info@freedomarchives.org'>info@freedomarchives.org</a>.</dd>
						<dt>Exploring the Collections without the Search Bar</dt>
						<dd>Under the heading Browse By Collection, you’ll notice most of the Freedom Archives’ major collections. These collections have an image as well as a short description of what you’ll find in that collection. Click on that image to instantly explore that specific collection.</dd>
						<dt>Basic Searching</dt>
						<dd>You can always type what you’re looking for into the search bar. Certain searches may generate hundreds of results, so sometimes it will help to use quotation marks to help narrow down your results. For instance, searching for the phrase Black Liberation will generate all of our holdings that contain the words Black and Liberation, while searching for “Black Liberation” (in quotation marks) will only generate our records that have those two words next to each other.</dd>
						<dt>Advanced Searching</dt>
						<dd>The Freedom Archives search site also understands <a target='_new' href='http://msass.case.edu/harrislibrary/LibStudents/tutorials/tutboolean.html'>Boolean search logic</a>. Click on <a target='_new' href='http://msass.case.edu/harrislibrary/LibStudents/tutorials/tutboolean.html'>this link</a> for a brief tutorial on how to use Boolean search logic. Our search function also understands “fuzzy searches.” Fuzzy searches utilize the (*) and will find matches even when users misspell words or enter in only partial words for the search. For example, searching for liber* will produce results for liberation/liberate/liberates/etc.</dd>
						<dt>Keyword Searches</dt>
						<dd>You’ll notice that under the heading KEYWORDS, there are a number of words, phrases or names that describe content. Sometimes these are also called “tags.” Clicking on these words is essentially the same as conducting a basic search.</dd>
					</dl>
				</div>
				<button onclick='$.modal.close();'>Close</button>
			</div>
			<div id='header_content'>
				<div id='welcometext'>
					<b>Welcome to the Freedom Archives' Digital Search Engine.</b>
					The Freedom Archives contains over 10,000 hours of audio and video tapes which date from the late-1960s to the mid-90s and chronicle the progressive history of the Bay Area, the United States, and international movements. We are also in the process of scanning and uploading thousands of historical documents which enrich our media holdings. Our collection includes weekly news, poetry, music programs; in-depth interviews and reports on social and cultural issues; numerous voices from behind prison walls; diverse activists; and pamphlets, journals and other materials from many radical organizations and movements.
				</div>
				<div id='header_search'>
					<h2>Search Archives</h2><form action='search.php'><input placeholder='Enter Search Keywords' id='search_box' name='s' value='<?php
					if(isset($_REQUEST['s'])) {
						echo html_encode($page->params['s']);
					}
					?>'/><img onclick='$(this).parent().submit()' src='images/search_button.png'/><span class='help_button' onclick='showHelp();' title='Click for search help'>?</span></form>
				</div>
				<ul id='collections_menu'>
					<li><h2><a href="#">Browse Collections <img src='images/down_arrow.png' alt=''/></a></h2>
					<?php echo getCollectionsMenu(); ?></li>
				</ul>
			</div>
