<?
require_once("FMXMLReader.php");
include "../dao/db.php";
$db = DbConnect();
$collections_lookup = dbLookupArray('select call_number, collection_id from FILEMAKER_COLLECTIONS');
if (! isset($_FILES['file'])) { 
?>
	<html>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<body>

		Upload Filemaker XML export.
		<form action="fp.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
		<label for="file">Filename:</label>
		<input type="file" name="file" id="file"><br>
		<input type="submit" name="submit" value="Submit">
		</form>

		</body>
		</html>
<?php
} else { 
	#while (@ob_end_flush());
	print "<h2>Importing documents</h2>";
	$x = 0;
	$COLLECTION_ID=20;
	#$xmlSource = "../XML/DB.xml";
	$xmlSource = $_FILES['file']['tmp_name'];
	//echo "XML Source File: ". $xmlSource ."<br />";

	$reader = FMXMLReader::open($xmlSource);
	$i=0;
	$row=$reader->nextRow();
	//print_r($row);
	//echo "<p >reader: ". $row['id'][0];
	while($row=$reader->nextRow()) {
		
		$DOCID = dbEscape($row['id'][0]); 
		$CALL_NUMBER = dbEscape($row['Call_Number'][0]);
		$TITLE = dbEscape($row['Title'][0]);
		$DESCRIPTION = dbEscape($row['Description'][0]);
		$PROGRAM = dbEscape($row['Program'][0]);
		$KEY_WORDS = dbEscape($row['Key_Words'][0]);
		$PRODUCERS = dbEscape($row['Producers'][0]);
		$SUBJECT_LIST = dbEscape($row['Subject_List'][0]);
		$CREATED_DTM = dbEscape($row['Date_Made_To_MySQL'][0]);
		$FORMAT = dbEscape($row['Format'][0]);
		$GENERATION = dbEscape($row['Generation'][0]);
		$QUALITY = dbEscape($row['Quality'][0]);
		$URL  = dbEscape($row['url_to_document'][0]);
		$URL_TEXT = dbEscape($row['url_to_document_display_text' ][0]);
		
		$cn_prefix = strtoupper(array_shift(explode(" ", $CALL_NUMBER)));
		if($row['Sub_coll_number'][0]) { 
			$COLLECTION_ID  = dbEscape($row['Sub_coll_number'][0]);
		} else if (isset($collections_lookup[$cn_prefix])) { 
			$COLLECTION_ID = $collections_lookup[$cn_prefix]['collection_id'];
		}

		if (strlen($URL)>3){
			$FILE_EXTENSION = substr($URL,strlen($URL)-3,strlen($URL));
			
		}
		else {
			$FILE_EXTENSION = "NONE";
		}

	//	echo "<p>&nbsp;</p>";
		$strsql = "INSERT INTO DOCUMENTS (DOCID,CALL_NUMBER,TITLE,DESCRIPTION,PROGRAM,KEYWORDS,PRODUCERS,SUBJECT_LIST,DATE_CREATED,FORMAT,GENERATION,QUALITY,URL,URL_TEXT,FILE_EXTENSION,COLLECTION_ID) VALUES (". $DOCID .",'".$CALL_NUMBER."','".$TITLE."','".$DESCRIPTION."','".$PROGRAM."','".$KEY_WORDS."', '".$PRODUCERS."','".$SUBJECT_LIST."','".$CREATED_DTM."','".$FORMAT."','".$GENERATION."','".$QUALITY."','".$URL."','".$URL_TEXT."','".$FILE_EXTENSION."',$COLLECTION_ID) on duplicate key update CALL_NUMBER='$CALL_NUMBER',TITLE='$TITLE',DESCRIPTION='$DESCRIPTION',PROGRAM='$PROGRAM',KEYWORDS='$KEY_WORDS',PRODUCERS='$PRODUCERS',SUBJECT_LIST='$SUBJECT_LIST',DATE_CREATED='$CREATED_DTM',FORMAT='$FORMAT',GENERATION='$GENERATION',QUALITY='$QUALITY',URL='$URL',URL_TEXT='$URL_TEXT',FILE_EXTENSION='$FILE_EXTENSION',COLLECTION_ID='$COLLECTION_ID'";
		//echo $strsql.";<br/>";	
		
		try {
			$id = DbQuery($strsql); //manually enable execution if need be - GAZI
			$x++;
		}
		catch (Exception $e){
			echo $e->getMessage().'<pre>'.$e->getTraceAsString().'</pre>';
			DbClose($db); 
		}
		 
	/*
		echo "<font color=red><b>".$strsql."</b></font><br/>";

		echo  "<b>ID:</b> ". $DOCID;
		echo  "<br /><b>Call Number:</b> ". $CALL_NUMBER;
		echo  "<br /><b>Title: </b> ". $TITLE;
		echo  "<br /><b>Description: </b> ". $DESCRIPTION;
		echo  "<br /><b>Program: </b> ". $PROGRAM;
		echo  "<br /><b>Keywords: </b> ". $KEY_WORDS;
		echo  "<br /><b>Producers: </b> ". $PRODUCERS;
		echo  "<br /><b>Subject List: </b> ". $SUBJECT_LIST;
		echo  "<br /><b>Created DTM: </b> ". $CREATED_DTM;
		echo  "<br /><b>Format: </b> ". $FORMAT;
		echo  "<br /><b>Generation: </b> ". $GENERATION;
		echo  "<br /><b>Quality: </b> ". $QUALITY;
		echo  "<br /><b>URL: </b> ". $URL;
		echo  "<br /><b>URL Display: </b> ". $URL_TEXT;
		echo "<p>&nbsp;</p>";
	*/		
		

		#$i=$i+1;
		#echo "\r$i";
	}
	print "<br/>Imported $x documents!";
	DbClose($db); 
}
?>
