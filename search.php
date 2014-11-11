<?php
include "dao/db.php";
include "Page.php";
$db = DbConnect();
$page = new Page();
include "includes/header.php";

$COLLECTION_ID = dbEscape($page->params['view_collection']);

if ($COLLECTION_ID) { 
	$collection = dbLookupSingle("SELECT * from COLLECTIONS WHERE COLLECTION_ID=$COLLECTION_ID");
	$COLLECTION_DESCRIPTION = $page->cleanDescription($collection['DESCRIPTION']);
	$COLLECTION_NAME = $collection['COLLECTION_NAME'];
	$page->params['collection_id'] = $COLLECTION_ID;
	$subcollections = getCollectionsList($COLLECTION_ID);
	$subcollections_list = $subcollections ? " 
							<div class='subcollections' id='collections'>
								<h3>Subcollections</h3>
								$subcollections
							</div>" : "";
}

?>
		<!-- MAIN CONTAINER -->
				<?php echo $page->getNav(); ?>
				<div id='content'>
				<?php if ($COLLECTION_ID) { ?>
					<div class='collection'>
					<h2> <?=$COLLECTION_NAME?></h2>
					<?php if ($page->params['page'] <= 1) { 
							echo $page->getFeaturedSlideshow($COLLECTION_ID);
							?>
							<div id='collection_description'>
							<?php echo html_entity_decode($COLLECTION_DESCRIPTION); ?> 
							</div>
							<?php echo $subcollections_list; ?>
					<?php } ?>
					</div>
				<?php } else { echo "<h2>Search Results</h2>"; } ?>
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
