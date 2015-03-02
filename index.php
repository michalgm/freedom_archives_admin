<?php
include_once('config.local.php');
include "lib/dbaccess.php";
$db = DbConnect();
include "Page.php";
$page = new Page();

//Edit this number to limit the # of collections that show on the page
$page->collections_limit = fetchValue("select value from CONFIG_LIVE where setting = 'frontPageCollectionNum'");
$introText = fetchValue("select value from CONFIG_LIVE where setting = 'introText'");

include "includes/header.php";

?>
		<div id='index_content'>
			<div id='search'>
				<h2>Search Archives</h2>
				<form action='search.php'>
					<input id='search_field' placeholder='Enter Search Keywords' name='s'/><img onclick='$(this).parent().submit()' src='images/search_button.png'/><span class='help_button' onclick='showHelp();' title='Click for search help'>?</span>
				</form>
				<?php echo str_replace("index.php", "search.php", $page->getWordCloud()); ?>
			</div>	
			<div id='collections'>
				<h2>Browse by Collection</h2>
				<?php echo getCollectionsList(); ?>
			</div>
			<?php echo $page->getFeaturedSlideshow('0'); ?>
			<br style='clear: both;'/>
		</div>
<?php include "includes/footer.php"; ?>

