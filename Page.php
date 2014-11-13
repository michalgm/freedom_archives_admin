<?php

class Page {
	public $params;
	public $query;
	public $targetpage;
	public $docCount;
	public $docNoDigitalCount;
	public $pagination;
	public $docLimit = 10;
	public $collections_limit = 0;
	public $filterparams = array(
			'collection_id'=>array('field'=>'collection_id', 'display'=>'Collection'),
			'media'=>array('field'=>'if(DOCUMENTS_LIVE.url like "%vimeo%", 
				"Video", 
				if(lower(substring_index(DOCUMENTS_LIVE.URL, ".", -1)) in ("pdf", "mp3"),
					lower(substring_index(DOCUMENTS_LIVE.URL, ".", -1)), 
					if(lower(substring_index(DOCUMENTS_LIVE.URL, ".", -1)) in ("jpg", "jpeg"),
						"Image", 
						if(DOCUMENTS_LIVE.URL != "", 
							"Webpage", 
							""
						)
					)
				)
			)', 'display'=>'Media Type'),
			'format'=>array('field'=>'FORMAT', 'display'=>'Source Format'),
			'year'=>array('field'=>'YEAR', 'display'=>'Year'),
			'title'=>array('field'=>'TITLE', 'display'=>'Title'),
			'subject'=>array('field'=>'SUBJECT_LIST', 'display'=>'Subject'),
			'author'=>array('field'=>'author', 'display'=>'Author'),
		);

	function __construct() {
		$this->params = $this->setupParams();	
	}
	function setupParams() { 
		$params = array();
		foreach(array_merge(array('view_collection', 's', 'page', 'no_digital'), array_keys($this->filterparams)) as $param) {
			$params[$param] = isset($_REQUEST[$param]) ? $_REQUEST[$param] : "";
		}
		return $params;
	}

	function getQuery() {
		if (! $this->query) { 
			$DB_SEARCH_TERMS = dbEscape($this->params['s']);
			$query = array(
				'select' => "select DOCUMENTS_LIVE.*, date_format(DOCUMENTS_LIVE.DATE_CREATED, '%c/%e/%Y') as DATE, collection_name, ".$this->filterparams['media']['field']." as media_type ",
				'from' => "from DOCUMENTS_LIVE join COLLECTIONS_LIVE using (collection_id)",
				'where' => " where 1=1 ",
				'order' => "",
				'limit' => "",
				'querystring' => "",
			);
			if ($DB_SEARCH_TERMS) { 
				include "includes/search/wordstemmer.php";
				include "includes/search/search.php";
				$search_terms_string = getSearchQueryString($DB_SEARCH_TERMS);
				$natural_language_terms = preg_replace("/\+|-\w*|\band\b|\bnot\b( \w+)?|\bor\b/i", "", $DB_SEARCH_TERMS);
				$query['select'] .=	", MATCH(`TITLE`, DOCUMENTS_LIVE.DESCRIPTION, `SUBJECT_LIST`, `AUTHOR`, `DOC_TEXT`, DOCUMENTS_LIVE.keywords, `FILE_EXTENSION`) AGAINST('$natural_language_terms' IN NATURAL LANGUAGE MODE) as relevance ";
				$query['where'] .= "and MATCH(TITLE, DOCUMENTS_LIVE.KEYWORDS, DOCUMENTS_LIVE.DESCRIPTION, AUTHOR) AGAINST('$search_terms_string' IN BOOLEAN MODE) ";
				$query['order'] .= " order by relevance desc";
			}

			foreach(array_keys($this->filterparams) as $field) { 
				if ($this->params[$field]) { 
					$dbfield  = $this->filterparams[$field]['field'];
					$dbvalue = dbEscape($this->params[$field]);
					$value = $this->params[$field] == 'None' ? "($dbfield is null or $dbfield = '')" : "$dbfield = \"$dbvalue\"";
					if ($field == 'collection_id' && $dbvalue) { 
						$query['where'] .= " AND ($value or parent_id = $dbvalue) ";
					} else { 
						$query['where'] .= " AND $value ";
					}
				}
			}
			if (! $this->params['no_digital']) { $query['where'] .= " and URL is not NULL and URL != '' "; }

			$query['limit'] = "limit ".($this->params['page'] ? (($this->params['page'] -1) * $this->docLimit).", $this->docLimit" : $this->docLimit);
			$query['querystring'] = "$query[select] $query[from] $query[where] $query[order] $query[limit]";
			$this->query = $query;
		}
		return $this->query;
	}

	function getDocCount() {
		if(! $this->docCount) { 
			$query = $this->getQuery();	
			$count_query = "select count(*) as count $query[from] ". $query['where'];
			$count_res = dbLookupSingle($count_query); 
			$this->docCount = $count_res['count'];	
		}
		return $this->docCount;
	}

	function getNoDigitalDocCount() {
		if(! $this->docNoDigitalCount) { 
			$query = $this->getQuery();	
			$no_digi_where = str_replace(" and URL is not NULL and URL != '' ", " and (URL is NULL or URL = '') ", $query['where']);
			$no_digi_count = "select count(*) as count $query[from] $no_digi_where";
			$no_digi_res = dbLookupSingle($no_digi_count); 
			$this->docNoDigitalCount = $no_digi_res['count'];	
		}
		return $this->docNoDigitalCount;
	}

	function getDocumentsList() { 
		$query = $this->getQuery();
		$docs = dbLookupArray($query['querystring']);
		#print $this->getQuery()['querystring'];
		$documentsList = "";
		$search_terms = array();

		$search_string = $this->params['s'];
		if ($search_string) { 
			#$search_string = strtolower(preg_replace("/\+|\-/", "", $search_string));
			$search_string = strtolower($search_string);
			$search_terms = preg_split('#\s*((?<!\\\\)"[^"]*")\s*|\s+#', $search_string, -1 , PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			#$search_terms = explode(" ", $search_string);	
		}
		foreach ($docs as $doc) { 
			$title = htmlspecialchars($doc['TITLE'], ENT_QUOTES);
			foreach(array('keywords', 'TITLE', 'DESCRIPTION', 'AUTHOR') as $key) {
				if (! $doc[$key]) { continue; }
				foreach($search_terms as $term) { 
					$term = preg_replace("/\"|'/", "", $term);
					if (in_array($term, array('not', 'and', '', ' ', 'or'))) { continue; }
					$doc[$key] = preg_replace("|(".preg_quote($term).")|Ui" , "[[!]]$1[[#]]" , $doc[$key] );
				}
				$doc[$key] = html_encode($doc[$key]);
				$doc[$key] = str_replace('[[!]]', "<span class='highlight_search'>", $doc[$key]);
				$doc[$key] = str_replace('[[#]]', "</span>", $doc[$key]);
				
			}
			foreach(array('publisher', 'year', 'call_number', 'date', 'producers', 'program') as $key) { 
				$ukey = strtoupper($key);
				$nicekey = ucwords(str_replace("_", " ", $key));
				$doc[$ukey] = $doc[$ukey] ? "<span class='$key'>$nicekey: ".html_encode($doc[$ukey])."</span>" : "";
			}
			$doc['AUTHOR'] = $doc['AUTHOR'] ? "<span class='author'>Author".(strstr($doc['AUTHOR'], ',') || stristr($doc['AUTHOR'], ' and ')  ? 's' : '').": ".$doc['AUTHOR']."</span>" : "";
			$thumbnail = $doc['THUMBNAIL'] ? $doc['THUMBNAIL'] : "images/thumbnails/not_digitized.jpg";
			$link_title = preg_replace("/<\/?span[^>]*?>/si", "", $title);
			$doc['TITLE'] = trim($doc['TITLE']);
			$description = $this->cleanDescription($doc['DESCRIPTION']);
			$image = "<img src='$thumbnail' class='doc_thumbnail' alt='$link_title'/>";
			$collection = "<span class='collection_name'>Collection: <a href='search.php?view_collection=$doc[COLLECTION_ID]'>".$doc['collection_name']."</a></span>";
			$title = "<span class='doc_title'>$doc[TITLE]</span>";
			if ($doc['URL']) { 
				$action = "onclick='showDoc(\"$link_title\", \"$doc[media_type]\", \"".trim($doc['URL'])."\"); return false;'";
				$image = "<a href='#' $action>$image</a>";
				$title = "<a href='#' $action class='doc_title'>$doc[TITLE]</a>";
			}
			$documentsList .= "
				<div id='doc_$doc[DOCID]' class='document'>
					$image
					$title
					<div class='details'>$doc[AUTHOR]$doc[PUBLISHER]$doc[YEAR]$doc[CALL_NUMBER]$doc[DATE]$doc[PRODUCERS]$doc[PROGRAM]$collection</div>
					<div class='doc_description'>$description</div>
				</div>
			";
		}
		return $documentsList;
	}

	function getTargetPage() { 
		if(! $this->targetpage) { 
			$pageName = basename($_SERVER['PHP_SELF']);
			$query_string = preg_replace("/\&page=\d+/", "", $_SERVER['QUERY_STRING']);
			$this->targetpage = "$pageName?$query_string";
		}
		return $this->targetpage;
	}

	function getNoDigitalLink() {
		$targetpage = $this->getTargetPage();
		return html_encode(preg_replace("/&no_digital=./", "", $targetpage)."&no_digital=".($this->params['no_digital'] ? "0" : "1"));
	}

	function getPagination() {
		if(! $this->pagination) { 
			$endlinks = 1;
			
			$total_pages = $this->getDocCount();
			$no_digi_count = "";
			if(! $this->params['no_digital']) { 
				$no_digi_count = "<br/><span class='no_digital_doc_count'>Hiding ".$this->getNoDigitalDocCount()." non-digitized documents (<a class='no_digital_link' href='".$this->getNoDigitalLink()."'>show</a>)</span>";
			} 
			$targetpage = html_encode($this->getTargetPage());
			preg_replace("/&amp;page=\d+/", "", $targetpage);
			$page = $this->params['page'];

			if ($page) {
				$start = ($page - 1) * $this->docLimit;
				//first item to display on this page
			} else {
				$start = 0;
				//if no page var is given, set start to 0
			}

			/* Setup page vars for display. */
			$page = $page ? $page : 1;
			
			//if no page var is given, default to 1.
			$prev = $page - 1;
			//previous page is page - 1
			$next = $page + 1;
			//next page is page + 1
			$lastpage = ceil($total_pages / $this->docLimit);
			//lastpage is = total pages / items per page, rounded up.

			/*
			 Now we apply our rules and draw the pagination object.
			 We're actually saving the code to a variable in case we want to draw it more than once.
			 */
			$pagination = "";
			//echo "lastpage". $lastpage." totalpage:".$CARDINALITY;
			$pagination .= "<div class=\"pagination\"><span class='doc_count'>$total_pages Documents Found</span>";
			if ($lastpage > 1) {
				$pagination .= " | ";
				//previous button
				if ($page > 1) {
					$pagination .= "<a href=\"$targetpage&amp;page=$prev\">&laquo;Previous</a>";
				} else {
					$pagination .= "<span class=\"disabled\">&laquo;Previous</span>";
				}

				for ($counter = 1; $counter <= $lastpage; $counter++) {
					if ($counter <= $endlinks || $counter > ($lastpage - $endlinks) || ($counter < $page +2 && $counter > $page-2)) {
						if ($counter == $page) {
							$pagination .= "<span class=\"current\">$counter</span>";
						} else {
							$pagination .= "<a href=\"$targetpage&amp;page=$counter\">$counter</a>";
						}
					} else if ($counter == $endlinks+1 || $counter == $lastpage -$endlinks) {
						$pagination .= "...";
					}
				}

				//next button
				if ($page < $counter - 1) {
					$pagination .= "<a href=\"$targetpage&amp;page=$next\">Next&raquo;</a>";
				} else {
					$pagination .= "<span class=\"disabled\">Next&raquo;</span>";
				}	
			}
			$pagination .= "$no_digi_count</div>\n";
			$this->pagination = $pagination;
		}
		return $this->pagination;
	}

	function getNav() {
		$filter = $this->getFilter();
		$value = $this->params['s'] ? $this->params['s'] : 'Search';
		$keywords = $this->getWordCloud();
		$nav = "<div id='nav'>
			$filter
			$keywords
		</div>";
		return $nav;
	}
	
	function cleanDescription($description) { 
		$description = preg_replace("/font-[^;\"]*/", "", $description);
		return html_entity_decode($description, ENT_COMPAT|ENT_HTML5, 'UTF-8');
	}

	function getFilter() {
		$targetpage = $this->getTargetPage();
		$query = $this->getQuery();
		#foreach (list('format', 'collection') as $field) { }
		$digital = "<span onclick='window.location=\"".$this->getNoDigitalLink()."\"'><input id='no_digital' name='no_digital' type='checkbox' ".($this->params['no_digital'] ? "checked='checked'" : '')."/><label for='no_digital'>Include non-digitized documents</label></span><br/>";
		$aliases = array(
			'media'=>array('pdf'=>'PDF', 'mp3'=>"Audio", 'jpg'=>'Image', 'jpeg'=>'Image'),
		);
		$collections = dbLookupArray("select collection_id, collection_name from COLLECTIONS_LIVE");
		$filter_components = "";
		foreach (array_values($collections) as $c) {
			$aliases['collection_id'][$c['collection_id']] = $c['collection_name'];
		}
		foreach (array_keys($this->filterparams) as $param) { 
			if ($param == 'collection_id' && $this->params['view_collection']) { continue; }
			$field = $this->filterparams[$param]['field'];
			$display = $this->filterparams[$param]['display'];
			$data = dbLookupArray("select $field as value, count(*) as count $query[from] $query[where] group by if($field is null, '', $field) order by count(*) desc, value");
			$filter_components .= "<h5>$display</h5>
				<ul class='filter_cat $param'>";
			$x = 0;
			foreach ($data as $item) { 
				if ($item['value'] == "") { $item['value'] =  'None'; }
				$value_display =  isset($aliases[$param][$item['value']]) ?  $aliases[$param][$item['value']] : $item['value'];
				$x++;
				$style = $x > 5 ? "style='display: none;' class='hidden' " : "";
				if(isset($this->params[$param]) && $this->params[$param] == $item['value']) { 
					$filter_components .= "\n<li><a href='".html_encode(preg_replace("/&?$param=[^&]+/", "", $targetpage))."'>&laquo; All {$display}s</a></li><li $style>$value_display</li>";
				} else { 
					$filter_components .= "\n<li $style><a href='".html_encode("$targetpage&$param=").urlencode($item['value'])."'>$value_display ($item[count])</a></li>";
				}
			}
			if ($x > 5) { $filter_components .= "<li class='more_filters'><a href='#' onclick='return showMoreFilters(\"$param\");'>Show More...</a></li>"; }
			$filter_components .= "</ul>";
		}
		$filtertext = "
			<div id='filter'>
				<h3>Filter Results</h3>
				$digital
				$filter_components
			</div>";
		return $filtertext;
	}

	function getWordCloud() {
		include_once "includes/tag-cloud/classes/tagcloud.php";
		$kw_limit = 30;
		$query = $this->getQuery();
		$keywords = dbLookupArray("SELECT KEYWORD, count(*) as count $query[from] join KEYWORD_LOOKUP_LIVE using(DOCID) ".$query['where']." group by lower(KEYWORD) order by counT(*) desc limit $kw_limit");
		if (! $keywords) { return ""; }
		$link= preg_replace("/s=[^&]*&?/", "", $this->getTargetPage());
		$cloud = new tagcloud();
		foreach ($keywords as $kw) { 
			$tag = $kw['KEYWORD'];
			$url = $link."&amp;s=".urlencode($this->params['s']." AND \"$tag\"");
			$cloud->addTag(array('tag'=>$tag, 'size'=>$kw['count'], 'url'=>$url));
		}
		$cloud->setOrder('tag','ASC');
		return "
				<div style='position: relative;' id='wordcloud'>
				<h3>Keywords</h3>
				".$cloud->render()."
				</div>
		";
	}

	function getFeaturedSlideshow($collection_id) {
		$slideshow = "";
		$featured_docs = dbLookupArray("select DOCID, a.DESCRIPTION, THUMBNAIL, URL,  TITLE, ".$this->filterparams['media']['field']."	as media_type  from FEATURED_DOCS_LIVE a join DOCUMENTS_LIVE using (DOCID) where a.collection_id = $collection_id order by doc_order"); 
		if (sizeof($featured_docs)) { 
			$slideshow .= "
				<div id='featured_media'>
					<h3>Featured Content</h3>
					<div class='flexslider'>
						<ul class='slides'>
			";
			foreach($featured_docs as $doc) { 
				$thumbnail = $doc['THUMBNAIL'] ? $doc['THUMBNAIL'] : "images/thumbnails/not_digitized.jpg";
				$large_thumb = str_replace(".jpg", "_large.jpg", $thumbnail);
				if (! file_exists($large_thumb)) { 
					$large_thumb = $thumbnail;
				}
				$slideshow .=  "
							<li data-thumb='$thumbnail' class='media_item'>
							<img onclick='showDoc(\"$doc[TITLE]\", \"$doc[media_type]\", \"$doc[URL]\");' src='$large_thumb' alt='$doc[TITLE]' />
								$doc[DESCRIPTION]
							</li>";
			}
			$slideshow .= "
						</ul>
					</div>
				</div>
			";
		}
		return $slideshow;
	}
}

function getCollections($parent_id=null) {
	$where = $parent_id ? " and c.parent_id = $parent_id " : "";
	$cols = dbLookupArray("SELECT c.collection_id,c.thumbnail,c.collection_name,c.description, c.summary , c.parent_id FROM COLLECTIONS_LIVE c left join COLLECTIONS_LIVE c2 on c.collection_id = c2.parent_id join DOCUMENTS_LIVE d on c.collection_id = d.collection_id or c2.collection_id =  d.collection_id where c.is_deleted = 0 $where group by c.collection_id order by c.display_order, c.collection_name" );
	return $cols;
}

function getCollectionsList($parent_id=0) {
	global $page;
	$cols = getCollections($parent_id);
	if (!$cols) { return; }
	$cols_list ="<ul class='collections_list'>";
	$x= 0;
	foreach ($cols as $col) {
		if($col['parent_id'] != $parent_id) { continue; }
		if ($page->collections_limit && $x >= $page->collections_limit) { break; }
		$x++;
		if ($col['summary']) { $col['summary'] = "<br/>".$col['summary']; }
		$thumbnail = $col['thumbnail'] ? "<img class='collection_thumbnail' src='$col[thumbnail]' alt='$col[collection_name]'/>" : "";
		$url = "search.php?view_collection=$col[collection_id]";
		$cols_list .= "
				<li class='collection_list_entry' onclick='window.location=\"search.php?view_collection=$col[collection_id]\";'>
					<div class='thumbnail_container'>
						<a href='search.php?view_collection=$col[collection_id]'>
							$thumbnail
						</a>
					</div>
					<a href='search.php?view_collection=$col[collection_id]'>$col[collection_name]</a>
					$col[summary]
				</li>\n";	
	}
	$cols_list .= "			</ul>\n";
	return $cols_list;
}

function getCollectionsMenu() {
	$cols = getCollections();
	$cols_list ="<ul id='collections_nav_menu' class='collections_nav submenu' style='display: none;'>";
	$subcols = array();
	foreach ($cols as &$col) {
		$pid = $col['parent_id'];
		if ($pid != 0) {
			if(! isset($subcols[$pid])) { $subcols[$pid] = array(); }
			array_push($subcols[$pid], $col['collection_id']);
		}
	}

	foreach ($cols as $col) {
		if($col['parent_id'] != 0) { continue; }
		$id = $col['collection_id'];
		$submenu = "";
		$prefix = "";
		if (isset($subcols[$id])) {
			$prefix = "<span class='left_arrow'>&laquo;</span>";
			$submenu .="
					<ul class='collections_nav submenu' style='display: none;'>";
			foreach($subcols[$id] as $cid) { 
				$subcol = $cols[$cid];
				$submenu .= "
						<li class='collection_list_entry'>
							<a href='search.php?view_collection=$subcol[collection_id]'>$subcol[collection_name]</a>
						</li>
				";
			}
			$submenu .= "
					</ul>";
		} 
		$cols_list .= "
				<li class='collection_list_entry'>
					<a href='search.php?view_collection=$col[collection_id]'>$prefix$col[collection_name]</a>
					$submenu
				</li>\n";	
	}
	$cols_list .= "			</ul>\n";
	return $cols_list;
}

function html_encode($string) { 
	return htmlentities($string, ENT_QUOTES, 'UTF-8') ;
}
