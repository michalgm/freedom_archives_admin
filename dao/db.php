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
$db_username='root';
$db_password='';
$db_defaultSchema = 'freedom_archives';

/*
FUNCTION: DbConnect
PARAMETERS: None
PURPOSE: Attempt to connect to the database and select the default schema.
       : Any failure will result in script termination
RETURNS: A handle to the database
 */
function DbConnect()
{
  global $db_server, $db_username, $db_password, $db_defaultSchema;
	$db = mysqli_init();
  $db->connect($db_server, $db_username, $db_password);
  if ($db->connect_error)
  {
	die('Failed to connect to the database.<br>Connect Error (' . $db->connect_errno . ') ' . $db->connect_error);
  }
 
  if (!$db->select_db($db_defaultSchema))
  {
    die("Failed to select default database schema<br>".$db->error);
  }
	$db->set_charset('utf8');
  return $db;
}

/*
FUNCTION: DbClose
PARAMETERS: $db - A connection handle obtained from DbConnect
PURPOSE: Closes the supplied database connection
RETURNS: None
 */
function DbClose($db)
{
	if ($db) { $db->close(); }
}

/*
FUNCTION: DbGetRecord
PARAMETERS: $db - a valid db handle obtained from DbConnect
          : $sql - The SQL select text to execute
PURPOSE: Execute the SQL text to obtain a record from the database.
       : The script will exit with an error should the SQL fail
RETURNS: The record as an array of values
*/
function DbGetRecord($db, $sql)
{
  $rs = $db->query($sql);
  if (!$rs)
  {
    echo "The SQL query failed!<br>";
    echo $db->error;
    exit;
  }

  // obtain record then free database resources
  $rec = $rs->fetch_row();
  $rs->free_result();
  return $rec;
}

/*
FUNCTION: DbGetRecords
PARAMETERS: $db - a valid db handle obtained from DbConnect
          : $sql - The SQL select text to execute
PURPOSE: Execute the SQL text to obtain a set of records from the database.
       : The script will exit with an error should the SQL fail
RETURNS: An array of records. Each record is an array of values
*/function DbGetRecords($db, $sql)
{
  $rs = $db->query($sql);
  if (!$rs)
  {
    echo "The SQL query failed!<br>";
    echo $db->error();
    exit;
  }

  // obtain each record and add to results array
  $results = array();
  while (($rec = $rs->fetch_row()) != null)
    array_push($results, $rec);

  // free database resources
  $rs->free_result();
  return $results;
}

/*
FUNCTION: DbUpdateRecord
PARAMETERS: $db - a valid db handle obtained from DbConnect
          : $sql - The SQL update text to execute
PURPOSE: Execute the SQL text to update database record(s).
       : The script will exit with an error should the SQL fail
RETURNS: None
*/function DbUpdateRecord($db, $sql)
{
  $rs = $db->query($sql);
  if (!$rs)
  {
    echo "The SQL query failed!<br>";
    echo $db->error;
    exit;
  }
}

/*
FUNCTION: DbInsertRecord
PARAMETERS: $db - a valid db handle obtained from DbConnect
          : $sql - The SQL insert text to execute
PURPOSE: Execute the SQL text to insert a database record.
       : The script will exit with an error should the SQL fail
RETURNS: The inserted id where appropriate
*/function DbInsertRecord($db, $sql)
{
  $rs = $db->query($sql);
  // return the inserted id
  //return $db->insert_id;
  if (!$rs)
  {
    echo "The SQL query failed!<br>";
    echo $db->error;
    exit;
  }
  // return the inserted id
  return mysql_insert_id();

}

/*
FUNCTION: DbDeleteRecord
PARAMETERS: $db - a valid db handle obtained from DbConnect
          : $sql - The SQL delete text to execute
PURPOSE: Execute the SQL text to delete database record(s).
       : The script will exit with an error should the SQL fail
RETURNS: None
*/function DbDeleteRecord($db, $sql)
{
  $rs = $db->query($sql);
  if (!$rs)
  {
    echo "The SQL query failed!<br>";
    echo $db->error;
    exit;
  }
}

/*
FUNCTION: DbIteratorOpen
PARAMETERS: $db - a valid db handle obtained from DbConnect
          : $sql - The SQL update text to execute
PURPOSE: Execute the SQL text to update database record(s).
       : The script will exit with an error should the SQL fail
RETURNS: None
*/function DbIteratorOpen($db, $sql)
{
  $rs = $db->query($sql);
  if (!$rs)
  {
    echo "The SQL query failed!<br>";
    echo $db->error;
    exit;
  }
  return $rs;
}

/*
FUNCTION: DbIteratorNext
PARAMETERS: $rs - a valid handle obtained from DbIteratorOpen
PURPOSE: Obtain the next record from the iterator.
RETURNS: The record as an array of values or null if there are no records left
*/function DbIteratorNext($rs)
{
  return $rs->fetch_row();
}

/*
FUNCTION: DbIteratorClose
PARAMETERS: $rs - a valid handle obtained from DbIteratorOpen
PURPOSE: Close the iterator and free resources
RETURNS: None
*/function DbIteratorClose($rs)
{
  $rs->free_result();
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

function dbLookupArray($query, $unsetkey=0) {
	$res = dbQuery($query);
	$array = Array();
	while($row = $res->fetch_assoc()) {
		$key = current($row);
		if($unsetkey == 1) { array_shift($row); }
		$array[$key] = $row;
	}
	return $array;
}

function dbLookupSingle($query) {
	$res = dbQuery($query);
	$array = Array();
	if ($res) { 
		$array = $res->fetch_assoc();
	}
	return $array;
}

function dbQuery($query) {
	$res = query($query);
	return $res;	
}

function query($query) {
	global $db;
	if (! $db) { $db = dbconnect(); }
	$res = $db->query($query) or trigger_error(('Query failed: '.$db->error."\n\t<br/>$query\n"));
	return $res;
}

if(! function_exists("session_register") ) { 
	function session_register($key, $value) { 
		if (!isset($_SESSION[$key])) {
			$_SESSION[$key] = $value;
		}
	}
}

?>
