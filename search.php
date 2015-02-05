<?php
include_once('config.local.php');

include "lib/dbaccess.php";
include "Page.php";
$db = DbConnect();
$page = new Page();
include "includes/header.php";

$page_header = "<h2>Search Results</h2>";
$COLLECTION_ID = dbEscape($page->params['view_collection']);

if ($COLLECTION_ID) { 
	$page->params['collection_id'] = $COLLECTION_ID;

	$collection = fetchRow("SELECT * from COLLECTIONS WHERE COLLECTION_ID=$COLLECTION_ID", true);
	$collection_description = $page->cleanDescription($collection['DESCRIPTION']);
	$collection_name = $collection['COLLECTION_NAME'];
	$subcollections_list = "";
	$featured_slideshow = "";

	if ($page->params['page'] <= 1) {
		$featured_slideshow = $page->getFeaturedSlideshow($COLLECTION_ID);
		$subcollections = getCollectionsList($COLLECTION_ID);
		if ($subcollections) {
			$subcollections_list = " 
				<div class='subcollections' id='collections'>
					<h3>Subcollections</h3>
					$subcollections
				</div>";
		}
	}
	$page_header = "
		<div class='collection'>
			<h2>$collection_name</h2>
			$featured_slideshow
			<div id='collection_description'>$collection_description</div>
			$subcollections_list
		</div>";
}

?>
		<!-- MAIN CONTAINER -->
				<?php echo $page->getNav(); ?>
				<div id='content'>
				<?=$page_header?>
					<div id='search_results'>
						<?php 
							if ($COLLECTION_ID) { 
								echo "<h3>Documents</h3>";
							}
							echo $page->getPagination(); 
							echo $page->getDocumentsList();
							if ($page->getDocCount() > 0) { 
								echo $page->getPagination(); 
							}
						?>
					</div>
				</div>

	<?php
	include "includes/footer.php";
	?>
