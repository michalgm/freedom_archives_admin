<?php 
/**
Abstract DB layer
Gazi Mahmud
 **/

/**
$db_server = "ischool.berkeley.edu";
$db_username = "gmahmud";
$db_password = "affy;egal";
$db_defaultSchema = "gmahmud";
*/

$db_server = 'localhost';
$db_username='freedom_archives';
$db_password='freedomarc';
$db_defaultSchema = 'freedom_archives';

//dbconnect: open db connection, return connection pointer
function dbconnect() {
	global $db, $dblogin, $dbpass, $dbname, $dbport, $dbsocket, $dbhost;
	if (! $db) {
		$db = mysqli_init();
		$db->options(MYSQLI_OPT_LOCAL_INFILE, 1);
		$db->real_connect($dbhost,$dblogin,$dbpass, $dbname, $dbport, $dbsocket);
		if ($db->connect_error) {
			die('Connect Error (' . $db->connect_errno . ') ' . $db->connect_error);
		}
		$db->set_charset('utf8');
		//$db->query("SET NAMES 'utf8'");
	}
	return $db;
}

//dbQuery: perform db query, return query result
function dbQuery($query) {
	$res = query($query);
	return $res;	
}

//fetchRow: performs query and returns single row result
function fetchRow($query, $assoc=0) {
	$res = query($query);
	if ($res) { 
		if ($assoc) { 
			return $res->fetch_assoc(); 
		} else {
			return $res->fetch_row(); 
		}
	}
}

function fetchValue($query) {
	$res = fetchRow($query);
	if ($res && $res[0]) {
		return $res[0];
	}
}

function query($query) {
	global $db;
	if (! $db) { $db = dbconnect(); }
	$res = $db->query($query) or trigger_error(('Query failed: '.$db->error."\n\t<br/>$query\n"));
	return $res;
}


function fetchCol($query) {
	$res = query($query);
	$array = Array();
	if ($res) { 
		while ($row = $res->fetch_row()) {
			$array[] = $row[0];
		}
	}
	return $array;
}


//dbLookupArray: performs query and returns associative array
function dbLookupArray($query, $unsetkey=0) {
	$res = dbwrite($query);
	$array = Array();
	while($row = $res->fetch_assoc()) {
		$key = current($row);
		if($unsetkey == 1) { array_shift($row); }
		$array[$key] = $row;
	}
	return $array;
}


function dbEscape($string) {
	global $db;
	if (! $db) { $db = dbconnect(); }
	return $db->real_escape_string($string);	
}


function cleanInput($keys) { 
	foreach ($keys as $key) {
		if (isset($_GET[$key])) { 
			$_GET[$key] = strip_tags($_GET[$key]);
		} else {
			$_GET[$key] = '';
		}
	}
}

function dbEscapeInput($keys) { 
	foreach ($keys as $key) {
		if (isset($_GET[$key])) { 
			$_GET[$key] = dbEscape($_GET[$key]);
		} else {
			$_GET[$key] = '';
		}
	}
}

function dbLookupSingle($query) {
	$res = dbQuery($query);
	$array = Array();
	if ($res) { 
		$array = $res->fetch_assoc();
	}
	return $array;
}

function dbInsert($query);
	global $db;
	$res = query($query);
	if ($res) { 
		return $db->insert_id;
	}
}

if(! function_exists("session_register") ) { 
	function session_register($key, $value) { 
		if (!isset($_SESSION[$key])) {
			$_SESSION[$key] = $value;
		}
	}
}

?>
