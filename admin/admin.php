<?php
set_error_handler('handleError');

$debug=1;
$db = null;
include_once('config.local.php');

$limit = 20;
$data = $query = "";

if (isset($_SERVER['CONTENT_TYPE']) && strstr($_SERVER['CONTENT_TYPE'],'application/json')) {
    $request = json_decode(file_get_contents('php://input'), true);
} else {
	$request = $_REQUEST;
}
$action = isset($request['action']) && $request['action'] ? $request['action'] : null;

$action_access = array(
	'login'=>'all',
	'logout'=>'all',
	'fetch_data'=>'all',
	'fetchDocuments'=>'all',
	'deleteDocument'=>'administrator',
	'fetchDocument'=>'all',
	'saveDocument'=>'administrator',
	'fetchCollection'=>'all',
	'saveCollection'=>'administrator',
	'exportCollection'=>'all',
	'csvImport'=>'admin',
	'filemakerImport'=>'administrator',
	'getThumbnailDocs'=>'all', 
	'updateThumbnail'=>'administrator',
	'updateLookups'=>'administrator',
	'uploadFile'=>'administrator',
	'backupDatabase'=>'all',
	'getDocIds'=>'all',
	'findDuplicates'=>'all',
);


checkLogin();

include('dbaccess.php');

if ($action) { 
	$data = array();
	$query = null;
	if (isset($action_access[$action])) { 
		if($action_access[$action] != 'all' && $action_access[$action] != $_SESSION['user_type']) { 
			trigger_error('You do not have permissions to access method "'.$request['action'].'".', E_USER_ERROR);
		}
	} else { 
		trigger_error('"'.$request['action'].'" is an invalid method.', E_USER_ERROR);
	};

	switch ($action) {
		case 'login':
			$data = $request['data'];
			$username = isset($data['user']) ? dbEscape($data['user']) : '';
			$password = isset($data['password']) ? dbEscape($data['password']) : '';
			$user = '';
			if ($username && $password) { 
				$login_query = "select USER_ID, USERNAME, USER_TYPE from USERS where USERNAME = '$username' and PASSWORD = '$password' limit 1";
				$user = fetchRow($login_query);
			}
			if ($user) { 
				$_SESSION['user_id'] = $user[0];
				$_SESSION['username'] = $user[1];
				$_SESSION['user_type'] = $user[2];
				$data = $_SESSION;
			} else { 
				setResponse(401, 'Bad Login');
			}
			break;

		case 'logout':
			setResponse(1, 'Logged Out');
			break;

		case 'fetch_data':
			$query = "select C.COLLECTION_ID as id, C.COLLECTION_NAME as label, C.IS_DELETED as hidden, count(D.DOCID) as count from COLLECTIONS C left join DOCUMENTS D using (COLLECTION_ID) group by COLLECTION_ID order by COLLECTION_NAME";
			$data['collections'] = array_values(dbLookupArray($query));
			$query = "select USER_ID as id, USERNAME as label, USER_TYPE from USERS";
			$data['users'] = dbLookupArray($query);
			$data['authors'] = fetchCol("select distinct author from AUTHOR_LOOKUP");
			$data['subjects'] = fetchCol("select distinct subject from SUBJECT_LOOKUP");
			$data['keywords'] = fetchCol("select distinct keyword from KEYWORD_LOOKUP");
			$data['action_access'] = $action_access;
			break;

		case 'fetchDocuments':
			$where = array();
			if (isset($request['collection']) && $request['collection']) { 
				$cid = dbEscape($request['collection']);
				$where[] = "D.COLLECTION_ID in (select COLLECTION_ID from COLLECTIONS where COLLECTION_ID = $cid or PARENT_ID = $cid) ";
			}
			if(! $request['nonDigitized']) { 
				$where[] = " URL is not null and URL != '' ";
			}
			if (isset($request['filter']) && $request['filter']) { 
				$filter = dbEscape($request['filter']);
				$like = "like '%$filter%'";
				$where[] = "(D.TITLE $like or D.KEYWORDS $like or D.CALL_NUMBER $like or D.DESCRIPTION $like or D.DOCID = '$filter')";
			}	

			$wherestring = count($where) ? " WHERE ".implode(' AND ', $where)." " : "";

			$query = "from DOCUMENTS D left JOIN COLLECTIONS C using(COLLECTION_ID) $wherestring order by D.TITLE";
			$data['count'] = fetchValue("Select count(*) $query");

			$query = "select D.DOCID as id, D.TITLE as label, D.DESCRIPTION, D.THUMBNAIL, C.COLLECTION_NAME, D.AUTHOR $query limit ".(($request['page']-1)*$request['limit']).",$request[limit]";
			$data['docs'] = array_values(dbLookupArray($query));
			break;

		case 'deleteDocument':
			dbwrite("delete from DOCUMENTS where DOCID = '".$request['id']."'");
			$data = 1;
			break;
		
		case 'fetchDocument':
			$data = fetchItem('document', $request['id']);
			break;
		
		case 'saveDocument':
			$data = saveItem('document', $request['id'], $request['data']);
			break;
		
		case 'fetchCollection':
			$data = fetchItem('collection', $request['id']);
			break;
		
		case 'saveCollection':
			$data = saveItem('collection', $request['id'], $request['data']);
			break;
		
		case 'exportCollection':
			$filename = 'All Collections';
			$where = isset($request['collection_id']) ? " and c.collection_id = ".dbEscape($request['collection_id']). " " : "";
			$docs = dbLookupArray("Select d.docid as 'Document Id', c.collection_name as Folder, Title, Author, publisher as 'Organization of Publisher', vol_number as 'Vol #-Issue/Date', Year, no_copies as 'No. of Copies', Format, d.Description, url as 'File Name', subject_list as 'Subjects', location as 'Place of Publication' from COLLECTIONS c left join DOCUMENTS d using(collection_id) where c.collection_id != 20 $where group by docid");

			$first = reset($docs); 
			if (isset($request['collection_id'])) { 
				$filename = $first['Folder'];
			}

			$headers = array_keys($first);

			//header('Content-Encoding: UTF-8');
			//header('Content-type: text/csv; charset=UTF-8');
			//header("Content-Disposition: attachment; filename=\"$filename.csv\"");
			$csv = "\xEF\xBB\xBF"; // UTF-8 BOM
			//fwrite($output, $data);
			//fwrite($output, "Select d.docid as 'Document Id', c.collection_name as Folder, Title, Author, publisher as 'Organization of Publisher', vol_number as 'Vol #-Issue/Date', Year, no_copies as 'No. of Copies', Format, d.Description, url as 'File Name', subject_list as 'Subjects', location as 'Place of Publication' from COLLECTIONS c join DOCUMENTS d using(collection_id) where c.collection_id != 20 $where group by docid");
			$csv .= str_putcsv($headers);
			foreach($docs as $doc) { 
				if(!isset($doc['Collection']) || ! $doc['Collection']) { 
					$doc['Collection'] = $doc['Folder'];
					$doc['Folder'] = "";
				}
				$csv .= str_putcsv($doc);
			}
			//$data = array("filename"=>$filename, "file"=>"data:text/csv;base64,".base64_encode($csv));
			$data = array("filename"=>$filename, "file"=>$csv);
			break;
		
		case 'csvImport':
			$data = csvImport($request['data']);
			break;
		
		case 'filemakerImport':
			$data = filemakerImport($request['data']);
			break;

		case 'getThumbnailDocs': 
			$where = $request['force'] ? "" : " and (thumbnail = '' or thumbnail is null) ";
			if ($request['collection']) { $where .= " and collection_id = ".$request['collection']; }
			$query = "select docid, title from DOCUMENTS where url is not null and url != '' $where";# and docid=5675");
			$data = array_values(dbLookupArray($query));
			break;

		case 'updateThumbnail':
			$data = updateThumbnail($request['id']);
			break;

		case 'updateLookups':
			$doc_id = $request['id'];
			$lookups = fetchRow("select author, keywords, subject_list from DOCUMENTS where docid = $doc_id", 1);
			$data = array(
				'_authors'=>preg_split("/, ?/", $lookups['author']), 
				'_keywords'=>preg_split("/, ?/", $lookups['keywords']), 
				'_subjects'=>preg_split("/, ?/", $lookups['subject_list']), 
			);
			updateTags($doc_id, $data);

			$data = 'success';
			break;
		
		case 'uploadFile':
			$data = $request['data'];
			$file_data = $data['data'];
			$type = $data['type'];
			$id = $data['id'];
			$tmpfile = "tmp/$id.$data[ext]";
			file_put_contents($tmpfile, base64_decode($file_data));
			$filename = ($type == 'collection' ?  'collections/' : '' ) . $id;
			$thumbnail = createThumbnail($tmpfile, '', $filename);
			$thumbnail = preg_replace("/^\.\.\//", "", $thumbnail);
			unlink($tmpfile);
			$result = saveItem($type, $id, array('THUMBNAIL'=>$thumbnail));
			$data = $result['THUMBNAIL'];
			break;
		
		case 'backupDatabase':
			global $db;
			require_once('mysqldump.php');
			$sql_dump = new MySQLDump($dbname,$dblogin,$dbpass,$dbhost);
			$sql_dump->droptableifexists = true;

			$sql_dump->start();
			$data = $sql_dump->output;
			break;
		
		case 'getDocIds':
			$data = fetchCol("select DOCID from DOCUMENTS where author != '' or keywords != '' or subject_list != ''");
			break;

		case 'findDuplicates':
			$data = array();
			$duplicates = fetchRows("
				SELECT DOCID, a.*
				FROM `DOCUMENTS` a
				JOIN (
					SELECT title, description, vol_number
					FROM DOCUMENTS
					WHERE title != ''
					GROUP BY title, description, vol_number
					HAVING count( * ) >1
				)b
				USING ( title, description, vol_number )
				ORDER BY title, description, vol_number");
			foreach($duplicates as $doc) { 
				$last = end($data);
				$title = trim($doc['TITLE']);
				$desc = trim($doc['DESCRIPTION']);
				$vol = trim($doc['VOL_NUMBER']);
				foreach( array('TITLE', 'DESCRIPTION', 'VOL_NUMBER', 'CREATOR', 'CONTRIBUTOR', 'DATE_AVAILABLE', 'DATE_MODIFIED', 'SOURCE', 'IDENTIFIER', 'LANGUAGE', 'RELATION', 'COVERAGE', 'RIGHTS', 'AUDIENCE', 'DIGITIZATION_SPECIFICATION', 'PBCORE_CREATOR', 'PBCORE_COVERAGE', 'PBCORE_RIGHTS_SUMMARY', 'PBCORE_EXTENSION', 'URL_TEXT', 'LENGTH') as $field) { 
					unset($doc[$field]);
				}

				if (isset($last['docs']) && $last['TITLE'] == $title && $last['DESCRIPTION'] == $desc && $last['VOL_NUMBER'] == $vol) { 
					$last['docs'][] = $doc;
					$last['count']++;
					$data[key($data)] = $last;
				} else { 
					$data[] = array('TITLE'=>$title, 'count'=>1, 'DESCRIPTION'=>$desc, 'VOL_NUMBER' => $vol, 'docs'=>array($doc));
				}
			}
			break;
		default:
			trigger_error('"'.$request['action'].'" is an invalid method.', E_USER_ERROR);
			break;
	}
	setResponse(1, 'Success', $data, $query);
} else {
	trigger_error('No action specified', E_USER_ERROR);
}

function fetchItem($type, $id) { 
	$id = dbEscape($id);
	if ($type == 'collection') { 
		$query = "select C.*, count(D.DOCID) as count from COLLECTIONS C left join DOCUMENTS D using (COLLECTION_ID) where COLLECTION_ID = $id";
	} else if ($type == 'document') { 
		$query = "select D.* from DOCUMENTS D where DOCID = $id";
	}
	$data = fetchRow($query, 1);
	if ($type == 'collection') { 
		$data['_featured_docs'] = array_values(dbLookupArray("select F.DOCID, F.DOC_ORDER, F.DESCRIPTION, D.TITLE, D.THUMBNAIL from FEATURED_DOCS F join DOCUMENTS D using(DOCID) where F.COLLECTION_ID = $id order by F.DOC_ORDER"));
		$data['_subcollections'] = array_values(dbLookupArray("select COLLECTION_ID, PARENT_ID, COLLECTION_NAME, IS_DELETED from COLLECTIONS where PARENT_ID = $id order by DISPLAY_ORDER, COLLECTION_NAME"));
	}
	if ($type == 'document') { 
		$data['_authors'] = fetchCol("select author from AUTHOR_LOOKUP where DOCID = $id order by `order`");
		$data['_keywords'] = fetchCol("select keyword from KEYWORD_LOOKUP where DOCID = $id order by `order`");
		$data['_subjects'] = fetchCol("select subject from SUBJECT_LOOKUP where DOCID = $id order by `order`");
	}
	return $data;
}

function saveItem($type, $id, $data) { 
	$table = strtoupper($type)."S";
	$idfield = $type == 'document' ? 'DOCID' : strtoupper($type)."_ID";
	$oldItem = fetchItem($type, $id);
	$tags = array();
	if ($type == 'collection' && isset($data['_featured_docs']) && isset($data['_subcollections']) ) { 
		$featuredDocs = $data['_featured_docs'];
		$subcollections = $data['_subcollections'];
		unset($data['_featured_docs']);
		unset($data['_subcollections']);
	} elseif ($type == 'document') { 
		
		$tags = array(
			'_authors'=> isset($data['_authors']) ? $data['_authors'] : [],
			'_keywords'=> isset($data['_keywords']) ? $data['_keywords'] : [],
			'_subjects'=> isset($data['_subjects']) ? $data['_subjects'] : []
		);
		unset($data['_authors']);
		unset($data['_keywords']);
		unset($data['_subjects']);
	}

	if ($id === 'new') { 
		$query = "insert into $table set ".arrayToUpdateString($data);
		$id = dbInsert($query);
	} else { 
		$query = "update $table set ".arrayToUpdateString($data)." where $idfield = ".dbEscape($id);
		dbwrite($query);
	}
	if ($type == 'collection' && isset($data['_featured_docs']) && isset($data['_subcollections'])) { 
		updateFeatured($id, $featuredDocs);
		updateSubcollections($id, $subcollections);
	}
	if ($type == 'document') {
		if (isset($data['URL']) && $data['URL'] && $data['URL'] != $oldItem['URL']) {
			updateThumbnail($id);
		} 
		updateTags($id, $tags);
	}
	return fetchItem($type, $id);
}

function updateTags($id, $data) { 
	foreach(array('keyword', 'author', 'subject') as $type) { 
		$table = strtoupper($type)."_LOOKUP";
		$field = $type == 'author' ? 'author' : ($type == 'keyword' ? 'keywords' : 'subject_list');

		if (isset($data["_$type"."s"])) { 
			dbwrite("delete from $table where DOCID = $id");
			$trimmed_list = array();
			$x = 0;
			foreach($data["_$type"."s"] as $item) { 
				$trimmed = trim($item);
				if (preg_match("/^ *$/", $trimmed)) { continue; }
				dbwrite("insert into $table (DOCID, $type, `order`) values($id, '".dbEscape($trimmed)."', $x) on duplicate key update `order` = $x");
				$trimmed_list[] = $trimmed;
				$x++;
			}
			dbwrite("update DOCUMENTS set $field = '".dbEscape(implode(", ", $trimmed_list))."' where docid = $id");
		}	
	}
}

function updateFeatured($id, $data) { 
	dbwrite("delete from FEATURED_DOCS where COLLECTION_ID = $id");
	$x=0;
	foreach($data as $doc) { 
		unset($doc['TITLE']);
		unset($doc['THUMBNAIL']);
		$doc['DOC_ORDER'] = $x;
		dbInsert("insert into FEATURED_DOCS set ".arrayToUpdateString($doc).", COLLECTION_ID = $id");
		$x++;
	}
}

function updateSubcollections($id, $data) { 
	$x = 0;

	dbwrite("update COLLECTIONS set PARENT_ID = 0, DISPLAY_ORDER = 1000 where PARENT_ID = $id");
	foreach($data as $col) { 
		dbwrite("update COLLECTIONS set PARENT_ID = $id, DISPLAY_ORDER = $x where COLLECTION_ID = $col[COLLECTION_ID]");
		$x++;
	}
}

function csvImport($data) { 
	$fields = array('docid', 'title', 'creator', 'subject_list', 'description', 'publisher', 'contributor', 'identifier', 'source', 'language', 'relation', 'coverage', 'rights', 'audience', 'format', 'keywords', 'author', 'vol_number', 'no_copies', 'file_name', 'doc_text', 'file_extension', 'collection_id', 'url', 'url_text', 'producers', 'program', 'generation', 'quality', 'year', 'location', 'is_reviewed', 'is_published', 'call_number', 'notes', 'thumbnail', 'length', 'collection');

	$aliases = array(
		'no. copies'=>'no_copies',
		'no. of copies'=>'no_copies',
		'organization of publisher'=>'publisher',
		'place of publication'=>'location',
		'issue date/no'=>'vol_number',
		'vol #-issue/ date'=>'vol_number',
		'vol #-issue/date'=>'vol_number',
		'file name'=>'url',
		'folder'=>'collection',
		'subcollection'=>'collection',
		'document id'=>'docid',
		'subjects'=>'subject_list',
	);


	$collections = dbLookupArray("select lower(collection_name), collection_id from COLLECTIONS order by collection_name");

	$columns = array();
	//ini_set('auto_detect_line_endings',TRUE);
	$csv = array();

	$csv_one_line = base64_decode($data);
	if ($csv_one_line === false) { trigger_error("Invalid data encoding", E_USER_ERROR); } 
	foreach (preg_split('/$\R?^/m', $csv_one_line) as $line) { 
		$csv[] = str_getcsv($line);
	}
	//$csv_data = str_getcsv($csv, null, ','. '"');
	$headers = array_shift($csv);
	$collection = null;
	$col_num = 0;
	foreach($headers as $header) { 
		if (! $header) { 
			trigger_error("Blank header column found (column ".($col_num+1).")");
		}
		$header = strtolower(trim($header));
		if (isset($aliases[$header])) { $header = $aliases[$header]; }
		if (! in_array(strtolower($header), $fields)) { trigger_error("Unrecognized Column Name: $header. <br/>Accepted columns are '".implode(array_merge($fields, array_keys($aliases)), "', '")."'"); }
		if ($header == 'collection') { 
			if (isset($collection)) { 
				trigger_error("Two collection fields specified!");
			} else {
				$collection = $col_num; 
			}
		} else {
			array_push($columns, $header);
		}
		$col_num++;
	}
	$row_num = 1;
	while ($row = array_shift($csv)) {
		$col_id = null;
		if (isset($collection)) { 
			$col_name = array_splice($row, $collection, 1);
			$col_name = trim(strtolower($col_name[0]));
			if ($col_name)  {
				if (isset($collections[$col_name])) { 
					$col_id = $collections[$col_name]['collection_id'];
				} else { 
					trigger_error("Unknown collection: '$col_name' in row $row_num - Do you need too create it?");
				}
			}
		}

		$values = "";
		foreach ($columns as $column) {
			$value = dbEscape(trim(array_shift($row)));
			if ($column == 'url' && $value && ! preg_match("/^http:\/\//i", $value)) {
				trigger_error("Bad URL in row $row_num ('$value'). URL field must contain full URL path (i.e. 'http://freedomarchives.org/doc.html')");
			}
			$values .= " `$column`='$value', ";
		}	
		$values = substr($values, 0, -2);
		if (isset($col_id)) { $values .= ", `collection_id` = $col_id"; }
		$query = "insert into DOCUMENTS set $values on duplicate key update $values";
		dbwrite($query);
		$row_num++;
	}
	return array("status"=>"success", "count"=>$row_num-1);
}

function filemakerImport($data_encoded) { 
	require_once("FMXMLReader.php");
	$collections_lookup = dbLookupArray('select call_number, collection_id from FILEMAKER_COLLECTIONS');
	$data = base64_decode($data_encoded);
	if ($data === false) { trigger_error("Invalid data encoding", E_USER_ERROR); } 
	$reader = FMXMLReader::read($data);
	$file = array();
	$count = 0;
	while($row=$reader->nextRow()) {
		$file['DOCID'] = dbEscape($row['id'][0]); 
		$file['CALL_NUMBER'] = dbEscape($row['Call_Number'][0]);
		$file['TITLE'] = dbEscape($row['Title'][0]);
		$file['DESCRIPTION'] = dbEscape($row['Description'][0]);
		$file['PROGRAM'] = dbEscape($row['Program'][0]);
		$file['KEYWORDS'] = dbEscape($row['Key_Words'][0]);
		$file['PRODUCERS'] = dbEscape($row['Producers'][0]);
		$file['SUBJECT_LIST'] = dbEscape($row['Subject_List'][0]);
		$file['DATE_CREATED'] = dbEscape($row['Date_Made_To_MySQL'][0]);
		$file['FORMAT'] = dbEscape($row['Format'][0]);
		$file['GENERATION'] = dbEscape($row['Generation'][0]);
		$file['QUALITY'] = dbEscape($row['Quality'][0]);
		$file['URL'] = dbEscape($row['url_to_document'][0]);
		$file['URL_TEXT'] = dbEscape($row['url_to_document_display_text' ][0]);
		
		$cn_parts =explode(" ", $file['CALL_NUMBER']);; 
		$cn_prefix = strtoupper(array_shift($cn_parts));
		if($row['Sub_coll_number'][0]) { 
			$file['COLLECTION_ID']  = dbEscape($row['Sub_coll_number'][0]);
		} else if (isset($collections_lookup[$cn_prefix])) { 
			$file['COLLECTION_ID'] = $collections_lookup[$cn_prefix]['collection_id'];
		}

		if (strlen($file['URL'])>3){
			$file['FILE_EXTENSION'] = substr($file['URL'],strlen($file['URL'])-3,strlen($file['URL']));
		} else {
			$file['FILE_EXTENSION'] = "NONE";
		}
		$query = "insert into DOCUMENTS set ".arrayToUpdateString($file) ." on duplicate key update ".arrayToUpdateString($file);
		dbwrite($query);
		$count++;
	}
	return array("status"=>"success", "count"=>$count);
}

function output($data, $query, $status='ok') {
	if (! $data) { 
		returnError('No Data found');
	}
	$output = array(
		'status'=> $status,
		'query'=>$query,
		'data'=> $data
	);
	if ($status == 'ok') { 
		$db->commit();
	}
	print json_encode($output, true);
	exit;
}

function returnError($message, $code=0) {
	output($message, null, 'error');
}

function handleError($errno, $errstr, $errfile, $errline, $errcontext) {
	global $debug;
	global $db;
	if ($db) { $db->rollback(); }
	$data = array();
	if ($debug) { $errstr = "'$errstr' in file $errfile line $errline "; } //(".print_r($errcontext, 1).")"; }
	setResponse($errno, "$errstr", $data);
	return true;
}

function setResponse($statusCode, $statusString, $data="", $query="") {
	header('Content-type: application/json');
	$response = array('statusCode'=>$statusCode, 'statusString'=>$statusString, 'data'=>$data, 'query'=>$query);
	//if php version < 5.3.0 we need to emulate the object string
	if (PHP_MAJOR_VERSION <= 5 & PHP_MINOR_VERSION < 3){
		print __json_encode($response);
	} else {
		print json_encode($response);
	}
	exit;
}
/**
*  outputCSV creates a line of CSV and outputs it to browser   
*/
function outputCSV($array) {
	$fp = fopen('php://output', 'w'); // this file actual writes to php output
	fputcsv($fp, $array);
	fclose($fp);
}

/**
*  getCSV creates a line of CSV and returns it.
*/
function str_putcsv($array) {
	ob_start(); // buffer the output ...
	outputCSV($array);
	return ob_get_clean(); // ... then return it as a string!
}

function checkLogin() {
	global $action;
	session_start();
	$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
	$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
	$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
	if ($action == 'login' || $action == 'logout') { 
		$_SESSION['user_id'] = '';
		$_SESSION['username'] = '';
		$_SESSION['user_type'] = '';
	} else if ($action == 'check_login') { 
		setResponse(1, 'Success', array('user_id'=>$user_id, 'username'=>$username, 'user_type'=>$user_type ));
	} else if (! $username) { 
		setResponse(401, 'Not Authorized');
	}
}

function updateThumbnail($doc_id) {
	global $production;
	$doc_id = dbEscape($doc_id);
	$doc = fetchRow("select * from DOCUMENTS where docid = $doc_id", true);
	$tmpfile = "tmp/$doc_id";
	$status = 'Failed';
	$image_file = "";
	if ($doc['URL']) {
		$url = $doc['URL'];
		if (stristr($url, 'vimeo')) { 
			$ext = 'video'; 
		} else {
			$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
		}
		$icon = "";
		$filename = "";
		if ($production) { 
			$url = preg_replace("|^http:\/\/[^\.]*\.?freedomarchives.org\/|",  '/home/claude/public_html/', $url);
		}
		if($ext == 'pdf' || $ext == 'video' || $ext == 'jpg' || $ext == 'jpeg') { 
			if ($ext == 'pdf') { 
				$icon = "../images/fileicons/pdf.png";
			} elseif($ext == 'video') {
				$vimeo_id = preg_replace("/^.*?\/(\d+)$/", "$1", $url);
				$json_url = "http://vimeo.com/api/v2/video/$vimeo_id.json";
				if (url_exists($json_url)) { 
					$icon = "../images/fileicons/video.png";
					$json = json_decode(file_get_contents($json_url), 1);
					$url = $json[0]['thumbnail_large'];
				}
			}
			if (file_exists($url)) { 
				$filename = $url;
			} else if (url_exists($url)) { 
				copy($url, $tmpfile);
				$filename = $tmpfile;
			}
			if (file_exists($filename)) { 
				$image_file = createThumbnail($filename, $icon, $doc_id);
				if ($image_file == 'timeout') { 
					$status = 'Thumbnail creation timed out. Bad Document?';	
				}
				if (file_exists("$image_file")) { 
					$status = 'Success';
				}
				if(file_exists($tmpfile)) { unlink($tmpfile); }
			} else { $status = "bad url for doc #$doc_id: $url"; }
		} else if ($ext == 'htm' || $ext == 'html') { 
			$image_file = "images/thumbnails/HTM.jpg";
			$status = 'Success';
		} else if ($ext == 'mp3') { 
			$image_file = "images/thumbnails/MP3.jpg";
			$status = 'Success';
		} else { 
			$status = "Unknown file format '$ext' for doc id $doc_id";
		}
		$image_file = preg_replace("|^../|", "", $image_file);
		dbwrite("update DOCUMENTS set thumbnail= '$image_file' where docid = $doc_id");
	} else { 
		$status = 'Missing URL';
	}
	return array('status'=>$status, 'image'=>$image_file);
}

function createThumbnail($image, $icon, $output_name) { 
	global $production;
	$thumbnail_path="images/thumbnails/";

	$large_size = 250;
	$small_size = 75;
	$border = "";
	$timeout = 10;
	$convert_path = $production ? "/usr/local/bin/convert" : "convert";

	if ($image && file_exists($image)) { 
		#$orig_image = $image;
		#$image = str_replace("[0]", "", $image);
		#if(! preg_match("/\....$/", $image)) {
		#	$image.= ".jpg";
		#}
		if(stripos($image, 'tmp')) {
			$border = " -bordercolor '#333' -border 1 ";
			$large_size -= 2;
			$small_size -= 2;
		}

		$large_file = "$thumbnail_path/$output_name"."_large.jpg";
		$small_file = "$thumbnail_path/$output_name.jpg";
		if (file_exists("../$large_file")) { unlink("../$large_file"); }
		if (file_exists("../$small_file")) { unlink("../$small_file"); }

		$icon_image = $icon ? "-background transparent $icon -gravity SouthEast -geometry 70x+15+15 -composite " : "";
		$small_icon_image = $icon ? "-background transparent $icon -gravity SouthEast -geometry 23x+5+5 -composite " : "";
		$large_cmd = "$convert_path $image"."[0] -trim +repage -background \"#fff\" -flatten -thumbnail '$large_size"."x$large_size>' -background \"#333\" -gravity center -extent $large_size"."x$large_size $icon_image $border ../$large_file 2>&1";
		$small_cmd = "$convert_path $image"."[0] -trim +repage -background \"#fff\" -flatten -thumbnail '$small_size"."x' -background \"#333\" -gravity center -extent $small_size"."x $small_icon_image $border ../$small_file 2>&1";
		/*
		exec($large_cmd, $output1);
		print_r($output1);
		exec($small_cmd, $output2);
		print_r($output2);
		if ($output1 || $output2) { 
			exit;
		}*/

		$one = ExecWaitTimeout($large_cmd);
		$two = ExecWaitTimeout($small_cmd);
		if (! $one || ! $two) {
			return "timeout";
		}

		if(! file_exists("../$large_file") || ! file_exists("../$small_file")) { 
			return; 
		}

		return "../$small_file";
	}
}

function url_exists($url) {
	$headers = @get_headers($url);
	if($headers) { 
		$content_type = array_pop($headers);
		if(strpos($headers[0],'200')===false || strpos($content_type, 'html')) {
			return false;
		} else { return true; }
	} else { return false; }	
}

function ExecWaitTimeout($cmd, $timeout=5) {
	exec($cmd." > /dev/null 2>&1 & echo $! ", $op);
	$pid = (int)$op[0];
	$time = 0;
	
	while($time < $timeout) {
		$output = "";
		sleep(1);
		$time++;
		exec("ps -p $pid", $foo, $val);
		if ($val) {
			return true;
		}
	}
	exec("kill -9 $pid");
	throw new Exception("command timeout on: $cmd", "444444");
	return false;
	//throw new Exception("command timeout on: " . $cmd);
}

?>
