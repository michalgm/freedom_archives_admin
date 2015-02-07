<?php
set_error_handler('handleError');

$debug=0;
$db = null;
include_once('../config.local.php');

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
	'fetchData'=>'all',
	'fetchDocuments'=>'all',
	'fetchCollections'=>'all',
	'deleteCollection'=>'all',
	'deleteDocument'=>'all',
	'fetchDocument'=>'all',
	'saveDocument'=>'all',
	'fetchCollection'=>'all',
	'saveCollection'=>'all',
	'exportCollection'=>'all',
	'exportRecordsSearch'=>'all',
	'fetchLists'=>'Administrator',
	'editListItem'=>'Administrator',
	'csvImport'=>'Administrator',
	'filemakerImport'=>'Administrator',
	'getThumbnailDocs'=>'all', 
	'updateThumbnail'=>'all',
	'updateLookups'=>'all',
	'uploadFile'=>'all',
	'backupDatabase'=>'all',
	'getDocIds'=>'all',
	'findDuplicates'=>'all',
	'fetchAuditLog'=>'Administrator',
	'pushChanges'=>'Administrator',
	'fetchBackups'=>'Administrator',
	'restoreBackup'=>'Administrator',
	'fetchList'=>'all',
	'fetchUsers'=>'Administrator',
	'saveUser'=>'Administrator',
	'deleteUser'=>'Administrator',
);

checkLogin();

include('../lib/dbaccess.php');
header('Content-type: application/json');

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
			$user = false;
			if ($username && $password) { 
				$login_query = "select USER_ID, USERNAME, USER_TYPE, PASSWORD, concat(firstname, ' ', lastname) as NAME from USERS where USERNAME = '$username' limit 1";
				$userinfo = fetchRow($login_query);
				if ($userinfo[3] == crypt($password, $userinfo[3])) {
					$user = $userinfo;
					unset($user[3]);
				}
			}
			if ($user) { 
				$_SESSION['user_id'] = $user[0];
				$_SESSION['username'] = $user[1];
				$_SESSION['user_type'] = $user[2];
				$_SESSION['name'] = $user[4];
				$data = $_SESSION;
			} else { 
				setResponse(401, 'Bad Login');
			}
			break;

		case 'logout':
			setResponse(1, 'Logged Out');
			break;

		case 'fetchData':
			$query = "select C.COLLECTION_ID as id, C.COLLECTION_NAME as label, C.IS_HIDDEN as hidden, count(D.DOCID) as count from COLLECTIONS C left join DOCUMENTS D using (COLLECTION_ID) group by COLLECTION_ID order by COLLECTION_NAME";
			$data['collections'] = dbLookupArray($query);
			$data['action_access'] = $action_access;
			$data['users'] = dbLookupArray("select username, concat(lastname, ', ', firstname) as name from USERS order by username");
			break;

		case 'fetchDocuments':
			$data = fetchItems('document', $request);
			break;

		case 'fetchCollections':
			$data = fetchItems('collection', $request);
			break;

		case 'deleteCollection':
			dbwrite("delete from COLLECTIONS where COLLECTION_ID = '".dbEscape($request['id'])."'");
			$data = 1;
			break;

		case 'deleteDocument':
			dbwrite("delete from DOCUMENTS where DOCID = '".dbEscape($request['id'])."'");
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
			$docs = fetchRows("Select d.docid as 'Document Id', d.Call_Number, c.collection_name as Folder, Title, Authors, 
				publisher as 'Organization of Publisher', vol_number as 'Vol #-Issue/Date', Year, no_copies as 'No. of Copies', Format, d.Description, 
				url as 'File Name', d.Subjects, d.keywords as Keywords, location as 'Place of Publication'
				from COLLECTIONS c left join DOCUMENTS d using(collection_id) where c.collection_id != 20 $where group by docid");

			if (isset($request['collection_id']) && isset($docs[0])) { 
				$filename = $docs[0]['Folder'];
			}
			$csv = arrayToCSV($docs);
			$data = array("filename"=>$filename, "file"=>$csv);
			break;

		case 'exportRecordsSearch':
			$searchdocs = fetchItems('document', $request);
			$ids = arrayToInString(array_map(function($doc) { return $doc['id']; }, $searchdocs['docs']));

			$docs = fetchRows("Select d.docid as 'Document Id', d.Call_Number, c.collection_name as Folder, Title, Authors, 
				publisher as 'Organization of Publisher', vol_number as 'Vol #-Issue/Date', Year, no_copies as 'No. of Copies', Format, d.Description, 
				url as 'File Name', d.Subjects, d.keywords as Keywords, location as 'Place of Publication'
				from COLLECTIONS c left join DOCUMENTS d using(collection_id) where c.collection_id != 20 and d.docid in ($ids) group by docid");

			$filename = "Search Results";
			$csv = arrayToCSV($docs);
			$data = array("filename"=>$filename, "file"=>$csv);

			break;

		case 'fetchList':
			$field = dbEscape($request['field']);
			$value = dbEscape($request['value']);
			$value = $value == " " ? "" : $value;
			$limit = "";
			$offset = 0;
			if (isset($request['offset'])) { 
				$offset = dbEscape($request['offset']);
			}
			if (isset($request['limit'])) {
				$limit = " limit $offset, ".dbEscape($request['limit']);
			}
			$query = "select a.item, sum(if(is_doc, 1, 0)) as record_count, sum(if(is_doc, 1, 0)) as collection_count from LIST_ITEMS a left join LIST_ITEMS_LOOKUP b using(item) where a.type = '$field' and a.item like('%$value%') collate utf8_unicode_ci  group by item order by if(a.item like('$value%') collate utf8_unicode_ci, 0, 1), ucase(a.item) $limit";

			$data = array(
				'items'=> fetchRows("$query"),
				'count' => fetchValue("select count(*) from LIST_ITEMS where type = '$field' and item like('%$value%') collate utf8_unicode_ci")
			);
			break;

		case 'editListItem':
			$ids = array();
			$listAction = $request['listAction'];
			$field = dbEscape($request['field']);
			$item = dbEscape($request['item']);
			$new_item = dbEscape($request['new_item']);
			$query = "";

			if (in_array($field, array('author', 'subject', 'producer', 'keyword'))) {
				if ($listAction != 'add') {
					$ids = dbLookupArray("select id, is_doc from LIST_ITEMS_LOOKUP where item = '$item' and type='$field'");
				}
			} else {
				if ($listAction != 'add') {
					$ids = dbLookupArray("select docid as id from DOCUMENTS where $field = '$item'");
				}
			}

			if ($listAction == 'add') {
				$query = "insert ignore into LIST_ITEMS set item = '$new_item', type='$field'";
			} elseif ($listAction == 'edit') {
				dbwrite("delete from LIST_ITEMS where item='$item' and type='$field'");
				dbwrite("delete from LIST_ITEMS where item='$new_item' and type='$field'");
				$query = "insert ignore into LIST_ITEMS set item = '$new_item', type='$field'";
			} elseif ($listAction == 'delete') {
				$query = "delete from LIST_ITEMS where item='$item' and type='$field'";
			}

			dbwrite($query);

			if (! in_array($field, array('author', 'subject', 'producer', 'keyword'))) {
				foreach($ids as $doc) {
					dbwrite("update DOCUMENTS set $field = '$new_item' where docid=$doc[id]");
				}
			} else {
				foreach($ids as $doc) {
					$dbfield = $field."s";
					if ($listAction == 'edit') {
						dbwrite("update LIST_ITEMS_LOOKUP set item='$new_item' where type='$field' and id=$doc[id] and item='$item'");
					} else {
						dbwrite("delete from LIST_ITEMS_LOOKUP where type='$field' and id=$doc[id] and item='$item'");
					}
					$list = fetchCol("select item from LIST_ITEMS_LOOKUP where type='$field' and id=$doc[id] and is_doc=$doc[is_doc] order by `order`");
					if ($doc['is_doc']) {
						dbwrite("update DOCUMENTS set $dbfield = '".dbEscape(implode(", ", $list))."' where docid = $doc[id]");
					} else {
						dbwrite("update COLLECTIONS set $dbfield = '".dbEscape(implode(", ", $list))."' where collection_id = '$doc[id]'");
					}
				}
			}

			$data = 'success';
			break;

		case 'csvImport':
			$data = csvImport($request['data']);
			break;
		
		case 'filemakerImport':
			$data = filemakerImport($request['data']);
			break;

		case 'getThumbnailDocs': 
			$where = $request['force'] ? "" : " and (thumbnail = '' or thumbnail is null) ";
			if ($request['collection']) { $where .= " and collection_id = '".$request['collection']."'"; }
			$query = "select docid, title from DOCUMENTS where url is not null and url != '' $where";# and docid=5675");
			$data = array_values(dbLookupArray($query));
			break;

		case 'updateThumbnail':
			$data = updateThumbnail($request['id']);
			break;

		case 'updateLookups':
			foreach($request['data']['items'] as $item) {
				$itemData = fetchItem($item['type'], $item['id']);
				$itemData = parseLookups($item['type'], $itemData);
				saveItem($item['type'], $item['id'], $itemData, true);
			}
			$data = count($request['data']['items']);
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
			require_once('lib/mysqldump.php');
			$sql_dump = new MySQLDump($dbname,$dblogin,$dbpass,$dbhost);
			$sql_dump->droptableifexists = true;

			$sql_dump->start();
			$data = $sql_dump->output;
			break;
		
		case 'getDocIds':
			$data = fetchCol("select DOCID from DOCUMENTS where authors != '' or keywords != '' or subjects != '' or producers != ''");
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
				foreach( array('TITLE', 'DESCRIPTION', 'VOL_NUMBER', 'CREATOR', 'CONTRIBUTOR', 'DATE_AVAILABLE', 'DATE_MODIFIED', 'SOURCE', 'IDENTIFIER', 
					'LANGUAGE', 'RELATION', 'COVERAGE', 'RIGHTS', 'AUDIENCE', 'DIGITIZATION_SPECIFICATION', 'PBCORE_CREATOR', 'PBCORE_COVERAGE', 
					'PBCORE_RIGHTS_SUMMARY', 'PBCORE_EXTENSION', 'URL_TEXT', 'LENGTH') as $field) { 
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

		case 'fetchAuditLog':
			$lastUpdate = fetchValue("select unix_timestamp(max(timestamp)) from audit_log where action='push'");
			$date = '';
			if ($request['time_limit'] == 'last_update') {
				$date = "'$lastUpdate'";
			} else {
				$date = "unix_timestamp(DATE_SUB(NOW(), INTERVAL ".dbEscape($request['time_amount'])." ". dbEscape($request['time_period'])."))";
			}
			$needs_review = $request['only_reviewed'] =='true' ? " and NEEDS_REVIEW = 1 " : "";
			// $log = fetchRows("select *, unix_timestamp(timestamp) as time from audit_log where unix_timestamp(timestamp) > '$date' and action != 'push'"); // limit ".(($page-1)*$limit).",$limit");
			$query = "
				select * from (
				select DOCID as id, TITLE as description, 'document' as type, CONTRIBUTOR as user, IF(DATE_MODIFIED = DATE_CREATED, 'create', 'update') as action, 
					NEEDS_REVIEW, collection_id, unix_timestamp(DATE_MODIFIED) as time from DOCUMENTS where unix_timestamp(DATE_MODIFIED) > $date $needs_review
				union 
					select COLLECTION_ID as id, COLLECTION_NAME as description, 'collection' as type, CONTRIBUTOR as user, IF(DATE_MODIFIED = DATE_CREATED, 'create', 'update') as action, 
						NEEDS_REVIEW, parent_id as collection_id, unix_timestamp(DATE_MODIFIED) as time from COLLECTIONS where unix_timestamp(DATE_MODIFIED) > $date $needs_review
				) a order by time desc
				"; // limit ".(($page-1)*$limit).",$limit");
			$log = fetchRows($query);
			$data = array('lastUpdate'=>$lastUpdate,'log'=>$log, 'date'=>$date);
			break;

		case 'pushChanges':
			$tables = fetchCol("show tables");
			foreach (array('DOCUMENTS', 'COLLECTIONS', 'LIST_ITEMS_LOOKUP', 'FEATURED_DOCS') as $table) {
				$where = ($table == 'DOCUMENTS' || $table == 'COLLECTIONS') ? " where IS_HIDDEN = 0 and NEEDS_REVIEW = 0 " : "";
				dbwrite("drop table IF EXISTS $table"."_BACKUP_3");
				if (in_array($table."_BACKUP_2", $tables)) {
					dbwrite("rename table $table"."_BACKUP_2 to $table"."_BACKUP_3");
				}
				if (in_array($table."_BACKUP_1", $tables)) {
					dbwrite("rename table $table"."_BACKUP_1 to $table"."_BACKUP_2");
				}
				if (in_array($table."_LIVE", $tables)) {
					dbwrite("rename table $table"."_LIVE to $table"."_BACKUP_1");
				}
				dbwrite("create table $table"."_LIVE like $table");
				dbwrite("insert into $table"."_LIVE select * from $table $where");
			}
			dbwrite("update backups a join backups b on a.id = 'backup_3' and b.id = 'backup_2' set a.date = b.date");
			dbwrite("update backups a join backups b on a.id = 'backup_2' and b.id = 'backup_1' set a.date = b.date");
			dbwrite("update backups a join backups b on a.id = 'backup_1' and b.id = 'live' set a.date = b.date");
			dbwrite("update backups set date = NOW() where id='LIVE'");

			$data = 1;
			updateLog('', array(), 'push');
			break;

		case 'fetchBackups':
			$data = dbLookupArray("select * from backups order by date desc");
			break;

		case 'restoreBackup':
			$id = dbEscape($request['id']);
			foreach (array('DOCUMENTS', 'COLLECTIONS', 'LIST_ITEMS_LOOKUP', 'FEATURED_DOCS') as $table) {
				dbwrite("drop table $table"."_LIVE");
				dbwrite("create table $table"."_LIVE like $table"."_$id");
				dbwrite("insert into $table"."_LIVE select * from $table"."_$id");
			}
			dbwrite("update backups a join backups b on a.id = 'live' and b.id = '$id' set a.date = b.date");

			$data = 1;
			break;

		case 'fetchUsers':
			$query = "select user_id, username, firstname, lastname, user_type, email from USERS order by username";
			$data = dbLookupArray($query);
			break;

		case 'saveUser':
			$user = $request['data'];
			if (! isset($user['username']) || ! $user['username']) {
				trigger_error("Username cannot be blank");
			}
			$id = dbEscape($user['user_id']);
			if ($id == 'new') {unset($user['user_id']); }

			if (isset($user['password'])) {
				if (!$user['password']) {
					trigger_error("Password can not be blank");
				} elseif (strlen($user['password']) < 8) {
					trigger_error("Password must be at least 8 characters long");
				}
				// $salt = 'foo';s
				$salt = substr(base64_encode(openssl_random_pseudo_bytes(17)),0,22);
				$salt = str_replace("+",".",$salt);
				$salt = '$'.implode('$',
					array(
	          "2y", //select the most secure version of blowfish (>=PHP 5.3.7)
	          str_pad(10,2,"0",STR_PAD_LEFT), //add the cost in two digits
	          $salt //add the salt
	        )
	      );
				$user['password'] = crypt($user['password'], $salt);
			} else {
				if ($id == 'new') {
					trigger_error("Password can not be blank");
				}
				unset($user['password']);
			}
			if (fetchValue("select username from USERS where username = '".dbEscape($user['username'])."' and (user_id != '$id' or '$id' = 'new')")) {
				trigger_error("User '$user[username]' already exists");
			}
			if ($id == 'new') {
				$query = "insert into USERS set ".arrayToUpdateString($user);
				$id = dbInsert($query);	
			} else {
				$query = "update USERS set ".arrayToUpdateString($user). " where user_id = $id";
				dbwrite($query);
			}

			$data = fetchRow("select user_id, username, firstname, lastname, user_type, email from USERS where user_id = $id", true);
			break;

		case 'deleteUser':
			$id = dbEscape($request['data']['id']);
			$query = "delete from USERS where user_id = $id";
			dbwrite($query);
			$data = "1";
			break;

		default:
			trigger_error('"'.$request['action'].'" is an invalid method.', E_USER_ERROR);
			break;
	}
	setResponse(1, 'Success', $data, $query);
} else {
	trigger_error('No action specified', E_USER_ERROR);
}

function fetchItems($type, $request) {
	global $query;
	$where = array();
	$order = array();
	$idfield = 'DOCID';
	$isDoc = 1;

	if ($type == 'document') {		
		if(! $request['nonDigitized']) { 
			$where[] = " URL is not null and URL != '' ";
		}
	} else {
		$idfield = 'COLLECTION_ID';
		$isDoc = 0;
	}

	if (isset($request['collection']) && $request['collection'] != '') { 
		$cid = dbEscape($request['collection']);
		$where[] = "I.".($isDoc? "COLLECTION_ID" : "PARENT_ID")." in (select COLLECTION_ID from COLLECTIONS where COLLECTION_ID = '$cid' or PARENT_ID = '$cid') ";
	}

	if(isset($request['IS_HIDDEN']) && $request['IS_HIDDEN']) { 
		$where[] = " I.IS_HIDDEN = 1 ";
	}
	if(isset($request['NEEDS_REVIEW']) && $request['NEEDS_REVIEW']) { 
		$where[] = " I.NEEDS_REVIEW = 1 ";
	}
	if (isset($request['filter']) && $request['filter'] != '') { 
		$filter = dbEscape($request['filter']);
		$filter = str_replace(" ", '%', $filter);
		$like = "like _utf8 '%$filter%'";
		if (isset($request['titleOnly']) && $request['titleOnly'])  {
			$where[] = "(I.TITLE $like or I.CALL_NUMBER $like or I.DOCID = '$filter')";
			$order[] = "I.TITLE like _utf8 '$filter%'";
			$order[] = "I.CALL_NUMBER like _utf8 '$filter%'";
			$order[] = "I.DOCID like '$filter%'";
		} else {
			if ($isDoc) {
				$where[] = "(I.TITLE $like or I.KEYWORDS $like collate utf8_unicode_ci or I.PRODUCERS $like collate utf8_unicode_ci or I.CALL_NUMBER $like or I.DESCRIPTION $like collate utf8_unicode_ci or I.DOCID = '$filter')";
				$order[] = "I.TITLE like _utf8 '$filter%'";
				$order[] = "I.DOCID like '$filter%'";
			} else {
				$where[] = "(I.COLLECTION_NAME $like or I.CALL_NUMBER $like or I.DESCRIPTION $like collate utf8_unicode_ci or I.COLLECTION_ID = '$filter')";
				$order[] = "I.COLLECTION_NAME like _utf8 '$filter%'";
				$order[] = "I.COLLECTION_ID like '$filter%'";
			}
			$order[] = "I.DESCRIPTION like _utf8 '$filter%'";
			$order[] = "I.CALL_NUMBER like _utf8 '$filter%'";
		}
	}

	$filters = "";
	$filter_count = "filter_a";
	if (isset($request['filter_types']) && isset($request['filter_values'])) {
		foreach ($request['filter_types'] as $filter_type) {
			$filter_value = dbEscape(array_shift($request['filter_values']));
			$filter_type = dbEscape($filter_type);
			if ($filter_type != '' && $filter_value != '') {
				if (in_array($filter_type, array('keyword', 'author', 'subject', 'producer'))) {
					$filters.= " JOIN LIST_ITEMS_LOOKUP $filter_count on $filter_count.id = I.$idfield and IS_DOC = $isDoc and $filter_count.type = '$filter_type' and $filter_count.item = '$filter_value' ";
				} else if (in_array($filter_type, array('location', 'organization', 'publisher', 'description', 'title', 'collection_name', 'date_range', 'vol_number'))) {
					$filter_value = str_replace(" ", '%', $filter_value);
					$where[] = "I.$filter_type like _utf8 '%$filter_value%'";
					$order[] = "I.$filter_type like _utf8 '$filter_value%'";
				} else {
					$where[] = "I.$filter_type = '$filter_value'";
				}
			}
			$filter_count++;
		}
	}

	$wherestring = count($where) ? " WHERE ".implode(' AND ', $where)." " : "";
	$orderstring = count($order) ? " IF(".implode(', 0, 1), IF(', $order).", 0, 1), " : "";

	$query = "";
	$data = array();
	$select = "";
	$limit = "";
	if ($isDoc) {
		$query = "from DOCUMENTS I $filters left JOIN COLLECTIONS C using(COLLECTION_ID) $wherestring order by $orderstring I.TITLE";
		$data = fetchRow("Select count(*) as count, sum(if(URL is not null and URL != '', 1, 0)) as digitized $query", true);
		$select = " I.DOCID as id, I.TITLE as label, I.DESCRIPTION, I.THUMBNAIL, C.COLLECTION_NAME, I.AUTHORS, I.CALL_NUMBER ";
	} else {
		$query = " from COLLECTIONS I $filters $wherestring order by $orderstring COLLECTION_NAME";
		$data = fetchRow("Select count(*) as count $query", true);
		$select = " COLLECTION_ID as id, I.* ";
	}

	if (isset($request['limit']) && isset($request['page'])) {
		$request['limit'] = dbEscape($request['limit']);
		$request['page'] = dbEscape($request['page']);
		$limit = " limit ".(($request['page']-1)*$request['limit']).",$request[limit]";
	}

	$query = "select $select $query $limit";
	$results = fetchRows($query);
	if ($isDoc) {
		$data['docs'] = $results;
	} else { 
		$data['collections'] = $results;
	}
	return $data;
}

function fetchItem($type, $id) { 
	$id = dbEscape($id);
	$type = dbEscape($type);
	if ($id == 'new') {return array(); }
	$is_doc = $type == 'document' ? 1 : 0;
	$query = "";
	if (! $is_doc) { 
		$query = "select C.*, count(D.DOCID) as count from COLLECTIONS C left join DOCUMENTS D using (COLLECTION_ID) where COLLECTION_ID = '$id'";
	} else { 
		$query = "select D.* from DOCUMENTS D where DOCID = $id";
	}
	$data = fetchRow($query, 1);
	$data['_keywords'] = fetchCol("select item from LIST_ITEMS_LOOKUP where id = $id and type = 'keyword' and is_doc=$is_doc order by `order`");
	$data['_subjects'] = fetchCol("select item from LIST_ITEMS_LOOKUP where id = $id and type = 'subject' and is_doc=$is_doc order by `order`");

	if ($type == 'collection') { 
		$data['_featured_docs'] = array_values(dbLookupArray("select F.DOCID, F.DOC_ORDER, F.DESCRIPTION, D.TITLE, D.THUMBNAIL from FEATURED_DOCS F join DOCUMENTS D using(DOCID) where F.COLLECTION_ID = '$id' order by F.DOC_ORDER"));
		$data['_subcollections'] = array_values(dbLookupArray("select COLLECTION_ID, PARENT_ID, COLLECTION_NAME, IS_HIDDEN from COLLECTIONS where PARENT_ID = '$id' and COLLECTION_ID != '$id' order by DISPLAY_ORDER, COLLECTION_NAME"));
		$data['_removeDocs'] = array();
		$data['_addDocs'] = array();
	}
	if ($type == 'document') { 
		$data['_authors'] = fetchCol("select item from LIST_ITEMS_LOOKUP where ID = $id and type='author' order by `order`");
		$data['_producers'] = fetchCol("select item from LIST_ITEMS_LOOKUP where ID = $id and type='producer' order by `order`");
		$data['_related'] = array_values(dbLookupArray("select ID,
			IF(DOCID_1 = $id, DOCID_1, DOCID_2) as DOCID,
			IF(DOCID_1 = $id, DOCID_2, DOCID_1) as DOCID_OTHER,
			IF(DOCID_1 = $id, TITLE_1, TITLE_2) as TITLE,
			IF(DOCID_1 = $id, DESCRIPTION_1, DESCRIPTION_2) as DESCRIPTION,
			IF(DOCID_1 = $id, TRACK_NUMBER_1, TRACK_NUMBER_2) as TRACK_NUMBER,
			IF(DOCID_1 = $id, TRACK_NUMBER_2, TRACK_NUMBER_1) as TRACK_NUMBER_OTHER,
			CALL_NUMBER, FORMAT
			from RELATED_RECORDS R JOIN DOCUMENTS D ON DOCID = IF(DOCID_1 = $id, DOCID_2, DOCID_1) where DOCID_1 = $id or DOCID_2 = $id order by TRACK_NUMBER"
		));
	}
	return $data;
}

function saveItem($type, $id, $data, $noLog=false) { 
	global $query;
	$table = strtoupper($type)."S";
	$idfield = $type == 'document' ? 'DOCID' : strtoupper($type)."_ID";
	$data[$idfield] = $id;
	$oldItem = fetchItem($type, $id);
	$tags = array(
		'_keywords'=> isset($data['_keywords']) ? $data['_keywords'] : null,
		'_subjects'=> isset($data['_subjects']) ? $data['_subjects'] : null,
	);
	$relatedDocs = array();
	$removeDocs = array();
	$addDocs = array();
	unset($data['_keywords']);
	unset($data['_subjects']);

	if ($type == 'collection') { 
		$tags['_featured_docs'] = isset($data['_featured_docs']) ? $data['_featured_docs'] : null;
		$tags['_subcollections'] = isset($data['_subcollections']) ? $data['_subcollections'] : null;
		$removeDocs = isset($data['_removeDocs']) ? $data['_removeDocs'] : array();
		$addDocs = isset($data['_addDocs']) ? $data['_addDocs'] : array();
		if (array_key_exists('PARENT_ID', $data) && ($data['PARENT_ID'] === null || $data['PARENT_ID'] == '')) {$data['PARENT_ID'] = 1000; }
		unset($data['_featured_docs']);
		unset($data['_subcollections']);
		unset($data['count']);
		unset($data['_removeDocs']);
		unset($data['_addDocs']);
	} elseif ($type == 'document') { 
		$tags['_authors'] = isset($data['_authors']) ? $data['_authors'] : null;
		$tags['_producers']= isset($data['_producers']) ? $data['_producers'] : null;
		$relatedDocs = isset($data['_related']) ? $data['_related'] : array();
		if (array_key_exists('COLLECTION_ID', $data) && ($data['COLLECTION_ID'] === null || $data['COLLECTION_ID'] == '')) {$data['COLLECTION_ID'] = 1000; }
		unset($data['_authors']);
		unset($data['_producers']);
		unset($data['_related']);
		if (isset($data['URL'])) {
			$mediaTypes = array(
				'mp3'=>'Audio',
				'mp4'=>'Audio',
				'wav'=>'Audio',
				'jpg'=>'Image',
				'png'=>'Image',
				'jpeg'=>'Image',
				'tiff'=>'Image',
				'bmp'=>'Image',
				'pdf'=>'PDF',
			);
			$media_type = '';
			$url = strtolower($data['URL']);
			if ($url != '') {
				if (stristr($url, 'vimeo')) { 
					$ext = 'Video'; 
				} else {
					$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
					if (isset($mediaTypes[$ext])) {
						$media_type = $mediaTypes[$ext];
					} else {
						$media_type = 'Webpage';
					}
				}
			}
			$data['MEDIA'] = $media_type;
		}
	}
	foreach(array('MONTH', 'DAY', 'YEAR') as $time) {
		if(isset($data[$time]) && $data[$time] === '') {$data[$time] = '?'; }
	}

	if (isset($data['CALL_NUMBER'])) {
		if (preg_match("/^([A-z\/]+)(.*)$/", $data['CALL_NUMBER'], $matches)) {
			if ($matches[1] && $matches[2]) {
				$data['CALL_NUMBER'] = trim($matches[1])." ".trim($matches[2]);
			}
		}
	}

	$action = $id === 'new' ? 'create' : 'update';
	$date = date('Y-m-d H:i:s');

	if (! $noLog) {
		$data['DATE_MODIFIED'] = $date;
		$data['CONTRIBUTOR'] = $_SESSION['username'];
		if ($id === 'new') {
			$data['DATE_CREATED'] = $date;
			$data['CREATOR'] = $_SESSION['username'];
		}
	}
	if ($_SESSION['user_type'] != 'Administrator') {
		$data['NEEDS_REVIEW'] =1;
 	} else if (! isset($data['NEEDS_REVIEW'])) {
		$data['NEEDS_REVIEW'] =0;
 	}

 	if ($id === 'new') {
 		unset($data[$idfield]);
		$query = "insert into $table set ".arrayToUpdateString($data);
		$id = dbInsert($query);
 	} else {
		$query = "update $table set ".arrayToUpdateString($data) ." where $idfield = '$id'";
		dbInsert($query);
 	}
	
	if ($type == 'collection') {
		if ($tags['_featured_docs'] !== null) {
			updateFeatured($id, $tags['_featured_docs']);
		}
		if ($tags['_subcollections'] !== null) {
			updateSubcollections($id, $tags['_subcollections']);
		}
		if (isset($removeDocs[0])) {
			dbwrite("update DOCUMENTS set COLLECTION_ID = 1000 where DOCID in (".arrayToInString($removeDocs).")");
		}

		if (isset($addDocs[0])) {
			dbwrite("update DOCUMENTS set COLLECTION_ID = '$id' where DOCID in (".arrayToInString($addDocs).")");
		}
	}
	if ($type == 'document') {
		if (isset($data['URL']) && $data['URL'] && isset($oldItem['URL']) && $data['URL'] != $oldItem['URL']) {
			updateThumbnail($id);
		} 
		foreach (array('format', 'generation', 'program', 'quality') as $field) {
			if (isset($data[strtoupper($field)]) && $data[strtoupper($field)] != "") {
				dbwrite("insert ignore into LIST_ITEMS set item='".dbEscape($data[strtoupper($field)])."', type='$field'");
			}
		}
		saveRelated($relatedDocs);
	}

	updateTags($id, $type, $tags);
	$item = fetchItem($type, $id);
	if (! $noLog) {
		//updateLog($type, $item, $action);
	}
	return $item;
}

function saveRelated($relatedDocs) {
	foreach($relatedDocs as $related) {
		$relatedDoc = array();

		if (isset($related['delete']) && $related['delete']) {
			if(isset($related['ID'])) {
				dbwrite("delete from RELATED_RECORDS where ID = ".dbEscape($related['ID']));
			}
			continue;
		} else {
			$current = $related['DOCID'] <= $related['DOCID_OTHER'] ? 1 : 2;
			$other = $current == 1 ? 2 : 1;

			if (isset($related["ID"])) {
				$relatedDoc['ID'] = dbEscape($related['ID']);
			}
			foreach(array('DOCID', 'TITLE', 'DESCRIPTION', 'TRACK_NUMBER') as $field) {
				$relatedDoc[$field."_$current"] = $related[$field];
				if (isset($related[$field."_OTHER"])) {
					$relatedDoc[$field."_$other"] = $related[$field."_OTHER"];
				} else if ($related['DOCID'] == $related['DOCID_OTHER'] && $field != 'DOCID') {
					$relatedDoc[$field."_$other"] = $relatedDoc[$field."_$current"];
				}
			}
			
			if (! isset($relatedDoc['ID'])) {
				$exists_query = "select id, TRACK_NUMBER_$other, TITLE_$other, DESCRIPTION_$other from RELATED_RECORDS where DOCID_$current = '".dbEscape($relatedDoc['DOCID_'.$current])."' and TRACK_NUMBER_"."$current = '".dbEscape($relatedDoc['TRACK_NUMBER_'.$current])."'";
				$exists = fetchRow($exists_query, true);
				if ($exists) { 
					$relatedDoc['ID'] = $exists['id']; 
					if ($exists["TRACK_NUMBER_$other"] != 0) {
						$relatedDoc["TRACK_NUMBER_$other"] = $exists["TRACK_NUMBER_$other"]; 
					}
				}
			}

			if (! isset($relatedDoc["TRACK_NUMBER_$other"])) {
				$relatedDoc["TRACK_NUMBER_$other"] = fetchValue("select max(IF(DOCID_1 >= DOCID_2, TRACK_NUMBER_1, TRACK_NUMBER_2)) + 1 from RELATED_RECORDS where DOCID_1 = '".dbEscape($relatedDoc['DOCID_'.$other])."' or DOCID_2 = '".dbEscape($relatedDoc['DOCID_'.$other])."'"); // $exists["TITLE_$other"];
				if (! $relatedDoc["TRACK_NUMBER_$other"]) { $relatedDoc["TRACK_NUMBER_$other"] = 1; }
			}
			if (isset($relatedDoc['ID'])) {
				$query = "update RELATED_RECORDS set ".arrayToUpdateString($relatedDoc) ." where id = ".$relatedDoc['ID'];
			} else {
				$query = "insert into RELATED_RECORDS set ".arrayToUpdateString($relatedDoc);
			}
			dbInsert($query);	
		}
	}
}

function parseLookups($type, $data) {
	if ($type == 'document') {
		if (isset($data['PRODUCERS'])) {
			$data['_producers'] = preg_split("/ ?(,| and |\&|\/) ?/i", $data['PRODUCERS']);
		}
		if (isset($data['AUTHORS'])) {
			$data['_authors'] = preg_split("/[,;] ?/", $data['AUTHORS']);
		}
	}
	if (isset($data['KEYWORDS'])) {
		$data['_keywords'] = preg_split("/[,;] ?/", $data['KEYWORDS']);
	}
	if (isset($data['SUBJECTS'])) {
		$data['_subjects'] = preg_split("/[,;] ?/", $data['SUBJECTS']);
	}
	return $data;
}

function updateTags($id, $type, $data) {
	$fields = array(
		'author'=>'authors',
		'keyword'=>'keywords',
		'subject'=>'subjects',
		'producer'=>'producers',
	);
	$is_doc = $type == 'document' ? 1 : 0;

	foreach(array_keys($fields) as $field) { 
		if ($is_doc == 0 && ($field == 'author' || $field == 'producer')) { 
			continue;
		}
		$table = "LIST_ITEMS_LOOKUP";
		$db_field = $fields[$field];
		if (isset($data["_$field"."s"]) && $data["_$field"."s"] !== null) { 
			$query = "delete from LIST_ITEMS_LOOKUP where id = $id and is_doc = $is_doc and type='$field'";
			dbwrite($query);
			$trimmed_list = array();
			$x = 0;
			foreach($data["_$field"."s"] as $item) { 
				$trimmed = trim($item);
				if (preg_match("/^ *$/", $trimmed)) { continue; }
				dbwrite("insert into LIST_ITEMS_LOOKUP (id, item, type, `order`, is_doc) values($id, '".dbEscape($trimmed)."', '$field', $x, $is_doc) on duplicate key update `order` = $x");
				dbwrite("insert ignore into LIST_ITEMS set item='".dbEscape($trimmed)."', type='$field'");
				$trimmed_list[] = $trimmed;
				$x++;
			}
			if ($is_doc) {
				dbwrite("update DOCUMENTS set $db_field = '".dbEscape(implode(", ", $trimmed_list))."' where docid = $id");
			} else {
				dbwrite("update COLLECTIONS set $db_field = '".dbEscape(implode(", ", $trimmed_list))."' where collection_id = '$id'");
			}
		}	
	}
}

function updateFeatured($id, $data) { 
	dbwrite("delete from FEATURED_DOCS where COLLECTION_ID = '$id'");
	$x=0;
	foreach($data as $doc) { 
		unset($doc['TITLE']);
		unset($doc['THUMBNAIL']);
		$doc['DOC_ORDER'] = $x;
		dbInsert("insert into FEATURED_DOCS set ".arrayToUpdateString($doc).", COLLECTION_ID = '$id'");
		$x++;
	}
}

function updateSubcollections($id, $data) { 
	dbwrite("update COLLECTIONS set PARENT_ID = 1000, DISPLAY_ORDER = 1000 where PARENT_ID = '$id'");

	$x = 0;
	foreach($data as $col) { 
		dbwrite("update COLLECTIONS set PARENT_ID = '$id', DISPLAY_ORDER = $x where COLLECTION_ID = '$col[COLLECTION_ID]'");
		$x++;
	}
}

function csvImport($data) { 
	$fields = array('docid', 'title', 'creator', 'subjects', 'description', 'publisher', 'contributor', 
		'identifier', 'source', 'language', 'relation', 'coverage', 'rights', 'audience', 'format', 
		'keywords', 'authors', 'vol_number', 'no_copies', 'file_name', 'doc_text', 'file_extension', 
		'collection_id', 'url', 'url_text', 'producers', 'program', 'generation', 'quality', 'year', 
		'location', 'needs_review', 'is_hidden', 'call_number', 'notes', 'thumbnail', 'length', 'month', 'day',
		'collection');

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
		'author'=>'authors',
	);

	//parse a CSV file into a two-dimensional array
	//this seems as simple as splitting a string by lines and commas, but this only works if tricks are performed
	//to ensure that you do NOT split on lines and commas that are inside of double quotes.
	function parse_csv($str) {
    //match all the non-quoted text and one series of quoted text (or the end of the string)
    //each group of matches will be parsed with the callback, with $matches[1] containing all the non-quoted text,
    //and $matches[3] containing everything inside the quotes
    $str = preg_replace_callback('/([^"]*)("((""|[^"])*)"|$)/s', 'parse_csv_quotes', $str);

    //remove the very last newline to prevent a 0-field array for the last line
    $str = preg_replace('/\n$/', '', $str);

    //split on LF and parse each line with a callback
    return array_map('parse_csv_line', explode("\n", $str));
	}

	//replace all the csv-special characters inside double quotes with markers using an escape sequence
	function parse_csv_quotes($matches) {
    $str = "";
    if (isset($matches[3])) {
	    //anything inside the quotes that might be used to split the string into lines and fields later,
	    //needs to be quoted. The only character we can guarantee as safe to use, because it will never appear in the unquoted text, is a CR
	    //So we're going to use CR as a marker to make escape sequences for CR, LF, Quotes, and Commas.
	    $str = str_replace("\r", "\rR", $matches[3]);
	    $str = str_replace("\n", "\rN", $str);
	    $str = str_replace('""', "\rQ", $str);
	    $str = str_replace(',', "\rC", $str);
  	}
    //The unquoted text is where commas and newlines are allowed, and where the splits will happen
    //We're going to remove all CRs from the unquoted text, by normalizing all line endings to just LF
    //This ensures us that the only place CR is used, is as the escape sequences for quoted text
    return preg_replace('/\r\n?/', "\n", $matches[1]) . $str;
	}

	//split on comma and parse each field with a callback
	function parse_csv_line($line) {
    return array_map('parse_csv_field', explode(',', $line));
	}

	//restore any csv-special characters that are part of the data
	function parse_csv_field($field) {
    $field = str_replace("\rC", ',', $field);
    $field = str_replace("\rQ", '"', $field);
    $field = str_replace("\rN", "\n", $field);
    $field = str_replace("\rR", "\r", $field);
    return $field;
	}

	$collections = dbLookupArray("select lower(collection_name), collection_id from COLLECTIONS order by collection_name");

	$columns = array();
	$csv = array();

	$csv_one_line = base64_decode($data);
	if ($csv_one_line === false) { trigger_error("Invalid data encoding", E_USER_ERROR); }
	$bom = pack('H*','EFBBBF');
	$csv_one_line = preg_replace("/^$bom/", '', $csv_one_line); 
	$csv = parse_csv($csv_one_line);

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

		$data = array();
		foreach ($columns as $column) {
			$value = dbEscape(trim(array_shift($row)));
			if ($column == 'url' && $value && ! preg_match("/^http:\/\//i", $value)) {
				trigger_error("Bad URL in row $row_num ('$value'). URL field must contain full URL path (i.e. 'http://freedomarchives.org/doc.html')");
			}
			$data[strtoupper($column)] = $value; //.= " `$column`='$value', ";
		}	
		//$values = substr($values, 0, -2);
		if (isset($col_id)) { 
			$data['COLLECTION_ID'] = $col_id;
		}
		$id = isset($data['DOCID']) ? $data['DOCID'] : 'new';
			// $values .= ", `collection_id` = $col_id"; }
		$data = parseLookups('document', $data);
		$data = saveItem('document', $id, $data);
		// $query = "insert into DOCUMENTS set $values on duplicate key update $values";
		// $id = dbInsert($query);
		// updateTags($id);
		// updateThumbnail($id, 1);
		$row_num++;
	}
	return array("status"=>"success", "count"=>$row_num-1);
}

function filemakerImport($data_encoded) { 
	require_once("lib/FMXMLReader.php");	
	$collections_lookup = dbLookupArray('select call_number, collection_id from FILEMAKER_COLLECTIONS');
	$data = base64_decode($data_encoded);
	if ($data === false) { trigger_error("Invalid data encoding", E_USER_ERROR); } 
	$reader = FMXMLReader::read($data);
	$count = 0;

	print "#STATUS#";
	while($row=$reader->nextRow()) {
		$file = array();
		// if (! in_array($row['id'][0], array(1780, 5516))) { continue; }
		// if (! in_array($row['id'][0], array(5304, 5744, 5745, 5746, 5747, 5748, 5749, 5750))) { continue; }
		// if ($count >= 100) { continue; }
		$file['DOCID'] = $row['id'][0];
		$file['CALL_NUMBER'] = $row['Call_Number'][0];
		$file['TITLE'] = $row['Title'][0];
		$file['DESCRIPTION'] = $row['Description'][0];
		$file['PROGRAM'] = $row['Program'][0];
		$file['KEYWORDS'] = $row['Key_Words'][0];
		$file['PRODUCERS'] = $row['Producers'][0];
		$file['SUBJECTS'] = $row['Subject_List'][0];
		$file['FORMAT'] = $row['Format'][0];
		$file['GENERATION'] = $row['Generation'][0];
		$file['QUALITY'] = $row['Quality'][0];
		$file['URL'] = $row['url_to_document'][0];
		$file['CREATOR'] = $row['created_name'][0];
		$file['DATE_CREATED'] = dateToSQL($row['Date Entered'][0], $row['created_time'][0]);
		$file['CONTRIBUTOR'] = $row['modified_by'][0];
		$file['DATE_MODIFIED'] = dateToSQL($row['Last Modified'][0], $row['modified_time'][0]);
		$file['LOCATION'] = $row['Location'][0];
		$volume = $row['Date_Made'][0];// ? $row['Date_Made'][0] : $row['new date field'][0];
		$parsed = date_parse($volume);
		if (preg_match("/^(\d\d?)[\/\-](\d\d?)[\/\-](\d\d\d?\d?)$/", $volume, $matches)) {
			$file['MONTH'] = $matches[1];
			$file['DAY'] = $matches[2];
			$year = $matches[3];
			if ($year < 100) {$year +=1900; }
			if ($year > 2015) {$year -= 100; }
			$file['YEAR'] = $year;
		} else if ($parsed['warning_count'] == 0 && $parsed['error_count'] == 0) {
			if ($parsed['month']) { $file['MONTH'] = $parsed['month']; }
			if ($parsed['day']) { $file['DAY'] = $parsed['day']; }
			if ($parsed['year']) { $file['YEAR'] = $parsed['year']; }
		} else {
			$file['VOL_NUMBER'] = $volume;
		}
		$file['_related'] = array();

		$cn_parts =explode(" ", $file['CALL_NUMBER']);; 
		$cn_prefix = strtoupper(array_shift($cn_parts));
		if(isset($row['Sub_coll_number']) && $row['Sub_coll_number'][0]) { 
			$file['COLLECTION_ID']  = dbEscape($row['Sub_coll_number'][0]);
		} else if (isset($collections_lookup[$cn_prefix])) { 
			$file['COLLECTION_ID'] = $collections_lookup[$cn_prefix]['collection_id'];
		}

		if (strlen($file['URL'])>3){
			$file['FILE_EXTENSION'] = substr($file['URL'],strlen($file['URL'])-3,strlen($file['URL']));
		} else {
			$file['FILE_EXTENSION'] = "NONE";
		}
		$file = parseLookups('document', $file);
		$id = isset($file['DOCID']) ? $file['DOCID'] : 'new';
		$file = saveItem('document', $id, $file, true);
		$count++;
		print $count;
		flush();
	}
	//Now we loop through the whole thing again to save relatec records
	$reader = FMXMLReader::read($data);
	$count = 0;
	while($row=$reader->nextRow()) {
		$file = array();
		$id = $row['id'][0];
		// if ($count >= 100) { continue; }
		// if (! in_array($row['id'][0], array(1780, 5516))) { continue; }
		$file['DOCID'] = $id;
		$file['TITLE'] = $row['Title'][0];
		$file['DESCRIPTION'] = $row['Description'][0];
		if (isset($row['Insert Tracks::Original Source'])) {
			$index =0;
		  foreach($row['Insert Tracks::Original Source'] as $to_call_number) {
		  	$related_doc = array();
		  	$other_id = "";
		  	if ($to_call_number) {
			  	$other_id = fetchValue("select DOCID from DOCUMENTS where CALL_NUMBER = '$to_call_number' and CALL_NUMBER != ''");
			  }
			  $other_id = $other_id ? $other_id : $id;
		    $related_doc['DOCID'] = $id;
		    $related_doc['DOCID_OTHER'] = $other_id;
		    $related_doc['TITLE'] = $row['Insert Tracks::Track Title'][$index];
		    $related_doc['DESCRIPTION'] = $row['Insert Tracks::Track Description'][$index];
		    $related_doc['TRACK_NUMBER'] = $index+1;

		    $file['_related'][] = $related_doc;
		    $index++;
		  }
		}
		// file_put_contents("fm.txt", print_r($file, true),   FILE_APPEND | LOCK_EX);
		// file_put_contents("fm.txt", print_r($row, true),   FILE_APPEND | LOCK_EX);

		$file = saveItem('document', $id, $file, true);

		$count++;
		print $count;
		flush();
	}
	dbwrite("update RELATED_RECORDS a join DOCUMENTS b on DOCID_1 = docid set a.title_1 = b.title  and a.description_1 = b.description where a.title_1 = ''");
	dbwrite("update RELATED_RECORDS a join DOCUMENTS b on DOCID_2 = docid set a.title_2 = b.title  and a.description_2 = b.description where a.title_2 = ''");

	// dbwrite("update RELATED_RECORDS a join DOCUMENTS b on to_id = docid set a.title = b.title where a.title = ''");
	// dbwrite("update RELATED_RECORDS a join DOCUMENTS b on to_id = docid set a.description = b.description where a.description = ''");
	print "#ENDSTATUS#";

	// session_start();
	return array("status"=>"success", "count"=>$count);
}

function dateToSQL($date, $time) {
	if (!$date) { return ""; }
  $times = array(0,0,0,0);
  preg_match('/^(\d+)\/(\d+)\/(\d+)$/i', trim($date), $dates);  
  if ($time) {
    preg_match('/^(\d+):(\d+):?(\d*)( [AP]M)$/i', trim($time), $times);
    if (isset($times[5]) && $times[5] == ' PM') { 
      $times[1] += 12;
    }
  }
  $dates[1] = sprintf("%02d", $dates[1]);
  $dates[2] = sprintf("%02d", $dates[2]);
  
  foreach($times as &$padtime) {
    $padtime = sprintf("%02d", $padtime);
  }
  
  $datetime = "$dates[3]-$dates[1]-$dates[2] $times[1]:$times[2]:$times[3]";
  return $datetime;
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
	file_put_contents("docs/admin_errors.log", date('Y-m-d H:i:s') ." - $errstr\n", FILE_APPEND | LOCK_EX);

	setResponse($errno, "$errstr", $data);
	return true;
}

function setResponse($statusCode, $statusString, $data="", $query="") {
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

function arrayToCSV($array) {
	$first = reset($array); 
	$headers = array_keys($first);

	$csv = "\xEF\xBB\xBF"; // UTF-8 BOM
	$csv .= str_putcsv($headers);
	foreach($array as $row) { 
		//Not sure why this was happening, but it was broken
		// if(!isset($doc['Collection']) || ! $doc['Collection']) { 
		// 	$doc['Collection'] = $doc['Folder'];
		// 	$doc['Folder'] = "";
		// }
		$csv .= str_putcsv($row);
	}
	return $csv;
}

function checkLogin() {
	global $action;
	session_start();

	// $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
	// $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
	// $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
	// $name = isset($_SESSION['name']) ? $_SESSION['name'] : null;
	if ($action == 'login' || $action == 'logout') { 
		$_SESSION['user_id'] = '';
		$_SESSION['username'] = '';
		$_SESSION['user_type'] = '';
		$_SESSION['name'] = '';
	} else if (! isset($_SESSION['username'])) { 
		setResponse(401, 'Not Authorized');
	} else if ($action == 'check_login') { 
		$userinfo = array(
			'user_id'=>null,
			'username'=>null,
			'user_type'=>null,
			'name'=>null,
		);
		foreach(array_keys($userinfo) as $key) {
			if (isset($_SESSION[$key])) {
				$userinfo[$key] = $_SESSION[$key];
			}
		}
		setResponse(1, 'Success', $userinfo);
	}
}

function updateLog($type, $item, $action, $description="") {
	$description = "";
	$id = "";
	$data = array(
		'action' => $action,
		'type' => $type,
		'user'=> $_SESSION['username'],
		'needs_review'=>isset($item['NEEDS_REVIEW']) ? $item['NEEDS_REVIEW'] : ""
	);
	if ($type == 'collection') {
		$data['id'] = $item['COLLECTION_ID'];
		$data['description'] = $item['COLLECTION_NAME'];
		if ($data['id'] == 0 ) {
			$data['description'] = 'Top-level collection';
		}
	} elseif ($type == 'document') {
		$data['id'] = $item['DOCID'];
		$data['description'] = $item['TITLE'];
	} else {
		$data['description'] = 'push to live';
	}
	dbwrite("insert into audit_log set ".arrayToUpdateString($data));
}

function updateThumbnail($doc_id, $check=0) {
	global $production;
	$doc_id = dbEscape($doc_id);
	$doc = fetchRow("select * from DOCUMENTS where docid = $doc_id", true);
	if ($doc['THUMBNAIL']!='' && $check) {
		return;
	}
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
