<?php 
/************************************************************
Name:	dbaccess.php
Version:
Date:
Common database-related functions
************************************************************/

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
		$db->autocommit('false');
		//$db->query("SET NAMES 'utf8'");
	}
	return $db;
}

//dbwrite: perform db query, return query result
//FIXME: rename this to dbquery
function dbwrite($query) {
	$res = query($query);
	return $res;	
}

function dbInsert($query) {
	global $db;
	$res = query($query);
	if ($res) { 
		return $db->insert_id;
	}
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

function fetchRows($query) { 
	$res = dbwrite($query);
	$array = Array();
	while($row = $res->fetch_assoc()) {
		$array[] = $row;
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

function arrayValuesToInString($array) {
	return "'".join("','", array_values($array))."'";
}

function arrayToInString($array, $assoc=0) {
	$array2 = Array();
	if($assoc) { 
		$array = array_keys($array);
	}
	foreach ($array as $key) {
		$key = dbEscape($key);
		$array2[] = $key;
	}
	return "'".join("','", $array2)."'";
}

function arrayToUpdateString($array, $keys='', $ignore_empty=0) {
	if (! $keys) { $keys = array_keys($array); }
	$values = Array();
	foreach ($keys as $key) { 
		if ($ignore_empty && (! isset($array[$key]) || $array[$key] == '')) { continue; }
		$values[] = "$key='".dbEscape($array[$key])."'";
	}
	return implode(",", $values);	
}



/* This doesn't work  - need to support OR/LIKE/IN and clusterin
function arrayToWhereString($array, $ignore_empty=0) {
	$values = Array();
	foreach($key as array_keys($array)) {
		if ($ignore_empty || $array[$key]) { 
			$values[$key] = dbEscape($array[$key]);
		}
	}
	return implode(" and ", $values);
}*/

?>
