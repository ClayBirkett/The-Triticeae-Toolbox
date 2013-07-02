<?php

// 21feb2013 dem: Use line_properties table instead of schema-coded properties.

require 'config.php';
include($config['root_dir'] . 'includes/bootstrap_curator.inc');
include($config['root_dir'] . 'curator_data/lineuid.php');
require_once("../lib/Excel/reader.php"); // Microsoft Excel library
connect();
loginTest();

/* The Excel file must have this string in cell B2.  Modify when a new template is needed.*/
$TemplateVersion = '1Jul13';
$cnt = 0;  // Count of errors

function die_nice($message = "") {
//Actually don't die at all yet, just show the error message.
  global $cnt;
  if ($cnt == 0) echo "<h3>Errors</h3>";
  $cnt++;
  echo "<b>$cnt:</b> $message<br>";
  return FALSE;
}

/* Show more informative messages when we get invalid data. */
function errmsg($sql, $err) {
  if (preg_match('/^Data truncated/', $err)) {
    // Undefined value for an enum type
    $pieces = preg_split("/'/", $err);
    $column = $pieces[1];
    $msg = "Unallowed value for field <b>$column</b>. ";
    // Only works for table line_records.  Could pass table name as parameter.
    $r = mysql_query("describe line_records $column");
    $columninfo = mysql_fetch_row($r);
    $msg .= "Allowed values are: ".$columninfo[1];
    $msg .= "<br>Command: ".$sql."<br>";
    die_nice($msg);
  }
  elseif (preg_match('/^Duplicate entry/', $err)) {
//   die_nice($err.". Aliases and GRIN Accessions must be unique.");
  die_nice($err."<br>".$sql);
  }
  else die_nice("MySQL error: ".$err."<br>The command was:<br>".$sql."<br>");
}


/* ******************************* */
$row = loadUser($_SESSION['username']);

////////////////////////////////////////////////////////////////////////////////
ob_start();

authenticate_redirect(array(USER_TYPE_ADMINISTRATOR, USER_TYPE_CURATOR));
ob_end_flush();


new LineNames_Check($_GET['function']);

class LineNames_Check
{
    private $delimiter = "\t";
    // Using the class's constructor to decide which action to perform
    public function __construct($function = null)
    {	
      switch($function)
	{
	case 'typeDatabase':
	  $this->type_Database(); /* update database */
	  break;
	case 'typeLineData':
	  $this->type_Line_Data(); /* Handle Line Data */
	  break;
	default:
	  $this->typeLineNameCheck(); /* intial case*/
	  break;
	}	
    }

    private function typeLineNameCheck() {
      global $config;
      include($config['root_dir'] . 'theme/admin_header.php');

      echo "<h2>Line information: Validation</h2>"; 
			
      $this->type_Line_Name();

      $footer_div = 1;
      include($config['root_dir'].'theme/footer.php');
    }
	
    private function type_Line_Name() {
      global $TemplateVersion;
      global $cnt;
      ?>
	<script type="text/javascript">
	function update_database(filepath, filename, username) {
	  var url='<?php echo $_SERVER[PHP_SELF];?>?function=typeDatabase&linedata=' + filepath + '&file_name=' + filename + '&user_name=' + username;
	  // Opens the url in the same window
	  window.open(url, "_self");
	}
	</script>
	
	<style type="text/css">
	    th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
		table {background: none; border-collapse: collapse}
		td {border: 0px solid #eee !important;}
		h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
		</style>
		
		<style type="text/css">
                   table.marker
                   {background: none; border-collapse: collapse}
                    th.marker
                    { background: #5b53a6; color: #fff; padding: 5px 0; border: 0; }
                    td.marker
                    { padding: 5px 0; border: 0 !important; }
                </style>
		
<?php

		    //DEM 19oct11: This runs out of memory.
	/* ini_set("memory_limit","24M"); */
        $row = loadUser($_SESSION['username']);
	$username=$row['name'];
	
	if ($_FILES['file']['name'] == ""){
		error(1, "No File Uploaded");
		print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
	}
	else {
	//$tmp_dir="uploads/tmpdir_".$username."_".rand();
        $tmp_dir="uploads/".str_replace(' ', '_', $username)."_".date('yMd_G:i');
	umask(0);
	if(!file_exists($tmp_dir) || !is_dir($tmp_dir)) {
		mkdir($tmp_dir, 0777);
	}
	$target_path=$tmp_dir."/";
	$uploadfile=$_FILES['file']['name'];
	$uftype=$_FILES['file']['type'];
	//	if (strpos($uploadfile, ".xls") === FALSE) {
	if (preg_match('/\.xls$/', $uploadfile) == 0) {
	  error(1, "Only xls format is accepted. <br>");
	  print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
	}
	else {
	  if(move_uploaded_file($_FILES['file']['tmp_name'], $target_path.$uploadfile)) {
	    /* start reading the excel */
	    $datafile = $target_path.$uploadfile;
	    $reader = & new Spreadsheet_Excel_Reader();
	    $reader->setOutputEncoding('CP1251');
	    $reader->read($datafile);
	    $linedata = $reader->sheets[0];
	    $cols = $reader->sheets[0]['numCols'];
	    $rows = $reader->sheets[0]['numRows'];
	    //echo "nrows ".$rows." ncols ".$cols."<br>";
	    //if (DEBUG) echo "Input File Name: ".$datafile."\n";

	    // Read the Template Version and check it.
	    if ($linedata['cells'][2][2] != $TemplateVersion )
	      die ("Incorrect Submission Form version.  Cell B2 must say \"$TemplateVersion\".");

	    // Read the Breeding Program from row 4.
	    /* if ($linedata['cells'][4][1] != "*Breeding Program")  */
	    if (stripos($linedata['cells'][4][1],"*Breeding Program") === FALSE) 
	      die("Cell A4 must be <b>*Breeding Program</b>.");
	    $bp = $linedata['cells'][4][2];
	    // Test whether this program is already in the database.
	    $sql = mysql_query("SELECT distinct data_program_code from CAPdata_programs");
	    while ($row = mysql_fetch_row($sql))
	      $bpcodes[] = $row[0];
	    if ((in_array($bp, $bpcodes) === FALSE) OR (strlen($bp) == 0) ) {
	      die("Breeding Program '$bp' is not in the database. <a href=\"".$config['base_url']."all_breed_css.php\">Show codes.</a><br><br>");
	    }

/* The following code allows the curator to put the columns in any order.
 * Any unrecognized column header will be warned as an unknown line property. */
// These are the standard columns. -1 means required, -2 means optional.
	      $columnOffsets = array(
			'line_name' => -1,
			'species' => -1,
			'generation' => -1,
			'synonyms' => -2,
			'grin' => -2,
			'pedigree' => -2,
			'comments' => -2
		);
	      // Available line properties:
	      $res = mysql_query("select name from properties") or die (mysql_error());
	      while ($r = mysql_fetch_row($res))
		$properties[] = $r[0];

	      /* Attempt to find each required column */
	      // First, locate the header line.
		$firstline = 0;
		$header = array();
		for ($irow = 5; $irow <=$rows; $irow++) {
			$teststr= addcslashes(trim($linedata['cells'][$irow][1]),"\0..\37!@\177..\377");
			if (empty($teststr))
			  break; 
			elseif (strtolower($teststr) == "*line name") {
			  $firstline = $irow;
			  // read out header line
			  for ($icol = 1; $icol <= $cols; $icol++) {
			    $value = addcslashes(trim($linedata['cells'][$irow][$icol]),"\0..\37!@\177..\377");
			    $header[] = $value;
			    //if (DEBUG2) echo "row ".$irow." col ".$icol." name ".$value."\n";
			  }
			  break 1;
			}
			else { die("The header row must begin with '*Line Name'.");}
		}
		// Now read in the data cells.
		foreach($header as $columnOffset => $columnName) { // Loop through the columns in the header row.
		  if ($columnOffset < 6) {  // Require exact match for Property names.
		    //Clean up column name so that it can be matched.
		    $columnName = strtolower(trim($columnName));
		    $order = array("\n","\t"," ");
		    $replace = array(" ",'','');
		    $columnName = str_replace($order, $replace, $columnName);
		  }
		  // Determine the column offset of "*Line Name"...
		  if (preg_match('/^\s*\*linename\s*$/is', trim($columnName)))
		    $columnOffsets['line_name'] = $columnOffset+1;
		  // Determine the column offset of "Aliases"...
		  else if (preg_match('/^\s*aliases\s*$/is', trim($columnName)))
		    $columnOffsets['synonyms'] = $columnOffset+1;
		  // Determine the column offset of "GRIN Accession"...
		  else if (preg_match('/^\s*grinaccession\s*$/is', trim($columnName)))
		    $columnOffsets['grin'] = $columnOffset+1;
		  // Determine the column offset of "Pedigree"...
		  else if (preg_match('/^\s*pedigree\s*$/is', trim($columnName)))
		    $columnOffsets['pedigree'] = $columnOffset+1;
		  // Determine the column offset of "*Filial Generation"...
		  else if (preg_match('/^\s*\*filialgeneration\s*$/is', trim($columnName)))
		    $columnOffsets['generation'] = $columnOffset+1;
		  // Determine the column offset of "*aestivum / durum / other"...
		  else if (preg_match('/^\s*\*aestivum\/durum\/other\s*$/is', trim($columnName)))
		    $columnOffsets['species'] = $columnOffset+1;
		  // Determine the column offset of "Comments"...
		  else if (preg_match('/^\s*comments\s*$/is', trim($columnName)))
		    $columnOffsets['comments'] = $columnOffset+1;
		  else {
		    // Find other Properties, and determine the column offset...
		    $pr = trim($columnName);
		    if (in_array($pr, $properties)) {
		      // Get this property's allowed values.
		      $propuid = mysql_grab("select properties_uid from properties where name = '$pr'");
		      $sql = "select value from property_values where property_uid = $propuid";
		      $res = mysql_query($sql) or die(mysql_error());
		      while ($r = mysql_fetch_row($res)) 
			$allowedvals[$pr][] = $r[0];
		      $columnOffsets[$columnName] = $columnOffset+1;
		      $ourprops[] = $pr;
		    }
		    else 
		      if (!empty($pr))
			die_nice("Line property <b>'$pr'</b> does not exist in the database.");
		  }
		} // end foreach($header as $columnOffset => $columnName)

		/* Check if any required columns weren't found. */
		$reqd = array_keys($columnOffsets, -1);
		if (!empty($reqd)) {
		  foreach ($reqd as $r)
		    echo "Column '$r' was not found. Please don't change the column labels in the header row, row 5.<br>";
		  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
		}

		$line_inserts_str = "";
		$line_uid = "";
		$line_uids = "";
		$line_uids_multiple = "";
		$lines_seen = array();
		$syns_seen = array();

	      //Ignore the next row after the header.  Or error.
 	      if ($linedata['cells'][$firstline+1][2] != "comma separated values") 
 		die("Row 5 must be the descriptions of the columns.  Please don't delete it.<br><br>"); 
	      for ($irow = $firstline+2; $irow <=$rows; $irow++)  {
		//Ignore empty rows.
		if (empty($linedata['cells'][$irow])) break;
		//Extract and validate data.
		$line = strtoupper(trim($linedata['cells'][$irow][$columnOffsets['line_name']]));
		if (empty($line)) die_nice("Row $irow: Line name is required."); 
		elseif (strpos($line, ' ')) die_nice("Row $irow: Line name contains a blank. Replace with _ or remove.") ;
		elseif (strlen($line) < 3)  echo "Warning: '$line' is a short name and may not be unique.<br>";
		$synonyms = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['synonyms']]),"\0..\37!@\177..\377");
		// Strip out any single-quotes.
		$synonyms = str_replace('\'', '', $synonyms);
		$synonyms = explode(',', str_replace(', ', ',', $synonyms));
		if (!empty ($synonyms)) {
		  $tooshort = array();
		  foreach ($synonyms as $s) {
		    if (!empty($s))
		      if ( (strlen($s) < 3) OR (strlen($s) < 4 AND is_numeric($s)) ) {
			echo "Note: Alias '$s' is too short to be unique. Removed. (Line $line)<br>";
			array_push($tooshort, $s);
		      }
		  }
		  $synonyms = array_diff($synonyms, $tooshort);
		}
		$grin = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['grin']]),"\0..\37!@\177..\377");
		if (!empty($grin)) {
		  if (preg_match("/^PI[0-9]/", $grin))
		    $grin = str_replace("PI", "PI ", $grin);
		  if (preg_match("/^CItr[0-9]/", $grin)) 
		      $grin = str_replace("CItr", "CItr ", $grin);
		  if (preg_match("/^GSTR[0-9]/", $grin)) 
		      $grin = str_replace("GSTR", "GSTR ", $grin);
		  if ( !preg_match("/^PI [0-9]*$/", $grin) 
		       AND !preg_match("/^CItr [0-9]*$/", $grin) 
		       AND !preg_match("/^GSTR [0-9]*$/", $grin) )
		    die_nice("Row $irow, Line $line: Invalid GRIN Accession $grin");
		  // Is this accession already used for a different line?
		  $sql = "select line_record_name 
                      from barley_pedigree_catalog_ref bpcr, line_records lr
		      WHERE bpcr.line_record_uid = lr.line_record_uid
		      AND barley_pedigree_catalog_uid=2
		      AND barley_ref_number = '$grin'";
		  $res = mysql_query($sql) or errmsg($sql, mysql_error());
		  if (mysql_num_rows($res) > 0) {
		    $row = mysql_fetch_row($res);
		    if ($row[0] != $line) 
		      die_nice("Row $irow, Line $line: GRIN Accession $grin is already used for Line $row[0].");
		  }
		}
		$generation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['generation']]),"\0..\37!@\177..\377");
		if ( (empty($generation)) OR ($generation != (int)$generation) OR ($generation < 1) OR ($generation > 9) )
		  die_nice("$line: Filial Generation (1-9) is required.");
		$species = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['species']]),"\0..\37!@\177..\377");
		$species = preg_replace("/^a$/", "aestivum", $species);
		$species = preg_replace("/^d$/", "durum", $species);
		if (empty($species)) die_nice("Row $irow, Line $line: Species is required.");
		$pedstring=addcslashes(trim($linedata['cells'][$irow][$columnOffsets['pedigree']]),"\0..\37!@\177..\377");
		$comments = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['comments']]),"\0..\37!@\177..\377");
		foreach ($ourprops as $pr) {
		  $propval[$pr] = addcslashes(trim($linedata['cells'][$irow][$columnOffsets[$pr]]),"\0..\37!@\177..\377");
		  if (!empty($propval[$pr])) {
		    // Test for allowed value.
		    if (!in_array($propval[$pr], $allowedvals[$pr])) {
		      $alllist = implode(",", $allowedvals[$pr]);
		      die_nice("<b>$propval[$pr]: </b>Allowed values of property $pr are only <b>$alllist</b>.");
		    }
		  }
		}

		// Line Name is required.
		if (!empty($line)) {
		  // Have we already seen this this Line Name in this file?
		  if (in_array($line, $lines_seen)) 
		    die_nice ("Line Name '$line' is used more than once in this file.");
		  else array_push($lines_seen, $line);
		  // Check if line is in database, as either a line name or synonym.
		  $line_uid = get_lineuid($line);
		  // $line_uid is an array.
		  if ($line_uid === FALSE) {
		    // Insert new line into database.
		    //convert line name to upper case and replace spaces with an underscore
		    $line = strtoupper(str_replace(" ","_",$line));
		    $line_inserts[] = $line;
		    $line_inserts_str = implode("\t",$line_inserts);
		    // $line_inserts_str == $line_inserts == $line.  All are a single line name.
		  } 
		  elseif (count($line_uid) == 1) { 
		    // If it's listed as a synonym, don't make it a line name too.
		    $sql = "select line_record_name from line_synonyms ls, line_records lr
		    where line_synonym_name = '$line' and ls.line_record_uid = lr.line_record_uid";
		    $res = mysql_query($sql) or die(mysql_error());
		    if (mysql_num_rows($res) > 0) {
		      $rn = mysql_fetch_row($res);
 		      $realname = $rn[0];
		      // It's okay for a synonym to be the same as the name except for UPPER/Mixed case.
		      if ($realname != $line)
			die_nice("Line Name $line is a synonym for $realname. Please use $realname instead.");
		    }
		    else {
		    //update the line record
		    $line_uids[] = implode(",",$line_uid);
		    // What??! $line_uids is a string, not an array.  And it contains exactly 1 uid.
		    }
		  }
		  else {
		    $line_uids_multiple .= implode(",",$line_uid);
		    //	echo " line in multiple records". $line_uids_multiple;
		    $cnt++; /* if this counter is not 0 then no accept option is displayed*/
		    error(0, "$line is found in multiple records ($line_uids_multiple), in line record table, please fix");
		  }
		  foreach ($synonyms as $syn) {
		    if (!empty($syn)) {
		      // Have we already seen this this synonym in this file?
		      if (in_array($syn, $syns_seen)) 
			die_nice ("Alias '$syn' is used more than once in this file.");
		      else array_push($syns_seen, $syn);
		      // Does the name already exist as either a synonym or a line name?
		      $linesyn_uid = get_lineuid($syn);
		      if ($linesyn_uid === FALSE) {
			// Okay, insert synonym into database.
		      } 
		      elseif (count($linesyn_uid) == 1) {
			// Is it in the synonym table?
			$sql = "select line_synonym_name, line_record_uid from line_synonyms where line_synonym_name like '$syn'";
			$r = mysql_query($sql);
			if (mysql_num_rows($r) > 0) {
			  // It's a synonym.  For the current line?
			  $found = mysql_fetch_row($r);
			  if (strtoupper($found[0]) == strtoupper($syn)) {
			    if ($found[1] != $line_uid[0]) {
			      $sql = mysql_query("SELECT line_record_name from line_records where line_record_uid = $found[1]");
			      $row = mysql_fetch_array($sql);
			      $line_name = $row['line_record_name'];
			      die_nice("$line alias '$syn' is already a synonym for a different line, $line_name.");
			    }
			  }
			}
			else 
			  die_nice("$line alias '$syn' is an existing Line Name.");
		      }
		      elseif (count($linesyn_uid) > 1) {
			die_nice("$line alias '$syn' is already an alias for multiple lines, please fix.");
		      }
		    }
		  } /* end of if (!empty($synonyms)) */
		} /* end of if (!empty($line)) */
	      } /* end of for ($irow) */
	      if (($line_uids) != "") {
		// $line_uids is a string containing 1 uid.  See line 388.
		$line_updates =implode(",",$line_uids);
		// Get line names.
		$line_sql = mysql_query("SELECT line_record_name as name
                        FROM line_records
                        WHERE line_record_uid IN ($line_updates)");
		while ($row = mysql_fetch_array($line_sql, MYSQL_ASSOC)) {
		  $line_update_names[] = $row["name"];
		}
		$line_update_data = $line_update_names;
	      }
	      else $line_update_data = array();
	      $line_insert_data = explode("\t",$line_inserts_str);
	      // $line_insert_data is a string containing a single line name.  See line 374.

	      // If any errors, show what we read and stop.
	      if ($cnt != 0) {
		?>
<h3>We were reading the following data from the uploaded file.</h3>
Breeding Program: <b><?php echo $bp ?></b><p>
  <table style="table-layout:fixed;">
    <tr>
      <th style="width: 80px;" class="marker">Line Name</th>
      <th style="width: 80px;" class="marker">Aliases</th>
      <th style="width: 50px;" class="marker">GRIN</th>
      <th style="width: 80px;" class="marker">Pedigree</th>
      <th style="width: 40px;" class="marker">Gener ation</th>
      <th style="width: 50px;" class="marker">Species</th>
		  <?php 
		  foreach ($ourprops as $pr)
		  echo "<th style='width: 50px;' class=marker>".$pr."</th>";
		?>
      <th style="width: 80px;" class="marker">Comments</th>
    </tr>
  </table>
  
  <div id="test" style="padding: 0; height: 300px; width: 935px;  overflow: scroll;border: 1px solid #5b53a6;">
    <table style="table-layout:fixed; word-wrap: break-word;">
		  <?php
		  for ($irow = $firstline+2; $irow <=$rows; $irow++)  {
		    //Ignore empty rows.
		    if (empty($linedata['cells'][$irow])) break;
		    //Extract data
		    $line = strtoupper(trim($linedata['cells'][$irow][$columnOffsets['line_name']]));
		    // Line Name is required.
		    if (!empty($line)) {
		      $synonyms = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['synonyms']]),"\0..\37!@\177..\377");
		      $grin = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['grin']]),"\0..\37!@\177..\377");
		      $pedstring=addcslashes(trim($linedata['cells'][$irow][$columnOffsets['pedigree']]),"\0..\37!@\177..\377");
		      $generation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['generation']]),"\0..\37!@\177..\377");
		      $species = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['species']]),"\0..\37!@\177..\377");
		      $species = preg_replace("/^a$/", "aestivum", $species);
		      $species = preg_replace("/^d$/", "durum", $species);
		      foreach ($ourprops as $pr) 
			$propval[$pr] = addcslashes(trim($linedata['cells'][$irow][$columnOffsets[$pr]]),"\0..\37!@\177..\377");
		      $comments = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['comments']]),"\0..\37!@\177..\377");
		      ?>
			<tr>
			   <td style="width: 100px;">
			   <?php echo $line ?></td> 
			   <td style="width: 70px;">
			   <?php echo $synonyms ?></td> 
			   <td style="width: 90px;">
			   <?php echo $grin ?></td> 
			   <td style="width: 180px;">
			   <?php echo $pedstring ?></td> 
			   <td style="width: 120px;">
			   <?php echo $generation ?></td> 
			   <td style="width: 120px;">
			   <?php echo $species ?></td> 
			   <?php 
			   foreach ($ourprops as $pr)
			   echo "<td style='width: 120px;'>".$propval[$pr]."</td>";
		           ?>
	                   <td style="width: 120px;">
			   <?php echo $comments ?></td> 
			   </tr>
			   <?php
			   } /* end of if (!empty($line)) */
		  }/* end of for loop */
		?>
		  </table>
		  </div>
		
			
		      <?php
		      echo " <p>Please fix these errors and try again.<br/><br/>";
		exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
			}
	      elseif ($cnt == 0) {
		// No errors so far.
		echo "<h3>The file is read as follows.</h3>\n";
		echo "Breeding Program: <b>$bp</b>";
		?>
  <table style="table-layout:fixed;">
    <tr>
      <th style="width: 80px;" class="marker">Line Name</th>
      <th style="width: 80px;" class="marker">Aliases</th>
      <th style="width: 50px;" class="marker">GRIN</th>
      <th style="width: 80px;" class="marker">Pedigree</th>
      <th style="width: 40px;" class="marker">Gener ation</th>
      <th style="width: 50px;" class="marker">Species</th>
		  <?php 
		  foreach ($ourprops as $pr)
		  echo "<th style='width: 50px;' class=marker>".$pr."</th>";
		?>
      <th style="width: 80px;" class="marker">Comments</th>
    </tr>
  </table>
  
  <div id="test" style="padding: 0; height: 300px; width: 935px;  overflow: scroll;border: 1px solid #5b53a6;">
    <table style="table-layout:fixed; word-wrap: break-word;">
	 <?php 
	 for ($irow = $firstline+2; $irow <=$rows; $irow++)  {
	   //Ignore empty rows.
	   if (empty($linedata['cells'][$irow])) break;
	   //Extract data.
	   $line = strtoupper(trim($linedata['cells'][$irow][$columnOffsets['line_name']]));
	   // Line Name is required.
	   if (!empty($line)) {
	     $grin = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['grin']]),"\0..\37!@\177..\377");
	     $synonyms = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['synonyms']]),"\0..\37!@\177..\377");
	     $pedstring=addcslashes(trim($linedata['cells'][$irow][$columnOffsets['pedigree']]),"\0..\37!@\177..\377");
	     $generation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['generation']]),"\0..\37!@\177..\377");
	     $species = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['species']]),"\0..\37!@\177..\377");
	     $species = preg_replace("/^a$/", "aestivum", $species);
	     $species = preg_replace("/^d$/", "durum", $species);
	     foreach ($ourprops as $pr) 
	       $propval[$pr] = addcslashes(trim($linedata['cells'][$irow][$columnOffsets[$pr]]),"\0..\37!@\177..\377");
	     $comments = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['comments']]),"\0..\37!@\177..\377");
	     ?>
	    <tr>
	      <td style="width: 100px;">
		<?php echo $line ?></td> 
	      <td style="width: 70px;">
		<?php echo $synonyms ?></td> 
	      <td style="width: 90px;">
		<?php echo $grin ?></td> 
	      <td style="width: 180px;">
		<?php echo $pedstring ?></td> 
	      <td style="width: 120px;">
		<?php echo $generation ?></td> 
	      <td style="width: 120px;">
		<?php echo $species ?></td> 
	      <?php 
		 foreach ($ourprops as $pr)
		 echo "<td style='width: 120px;'>".$propval[$pr]."</td>";
		 ?>
	      <td style="width: 120px;">
	      <?php echo $comments ?></td> 
</tr>
    <?php
    } /* end of if (!empty($line))*/
	 }  /* end of for loop */
    ?>
</table>
</div>

<h3>The following lines will be added or edited.</h3>
Please verify that<br>
    1. The lines to be added are new ones and aren&apos;t already in the 
database, e.g. under a variant spelling.<br>
2. The lines to be edited are ones you wish to change the existing data for.<br>
Please don&apos;t replace a detailed pedigree with a shallower one, unless it&apos;s wrong.
<p>
<?php
$lid = count($line_insert_data);
$lud = count($line_update_data);
?>
<table><tr><td>
      <table >
	<tr>
          <th style="width: 140px;" class="marker">Lines to Add: <?php echo "$lid" ?></th>
          <th style="width: 150px;" class="marker" >Lines to Edit: <?php echo "$lud" ?></th>
	</tr>
      </table>
      <div id="test" style="padding: 0; height: 200px; width: 290px;  overflow: scroll;border: 1px solid #5b53a6;">
	<table>
   <?php
   if($line_update_data !="") {
       for ($i = 0; $i < max(count($line_insert_data),count($line_update_data)); $i++) {
	 ?>
	     <tr>
	       <td style="width: 130px;">
		 <?php echo $line_insert_data[$i] ?>
	       </td> 
	       <td style="width: 160px;">
		 <?php echo $line_update_data[$i] ?>
	       </td>
	 <?php
       }/* end of for loop */
   }
   else {
     for ($i = 0; $i < count($line_insert_data); $i++) {
       ?>
		  <tr>
		    <td style="width: 130px;">
		      <?php echo $line_insert_data[$i] ?>
		    </td> 
		    <td style="width: 160px;">
		      <?php echo "No Updates" ?>
		    </td>
	 <?php
	 }/* end of for loop */
   }
?>
	</table>
      </div>
    </td>
    <td style="width: 250px; text-align: left; vertical-align: top;">
      <h3>Editing lines</h3>
      To add or change information about a line, edit the file 
      and reload, or load a new one.  Empty cells and unchanged 
      cells will have no effect.  Cells with content will <b>replace</b>
      the existing values. (exception: New Aliases will be <b>added</b>.)
      <P>Alternatively you can use the 
	<a href="<?php echo $config['base_url'] ?>login/edit_line.php">
	  Edit Lines</a> form.
    </td>
</tr></table>

<input type="Button" value="Accept" onclick="javascript: update_database('<?php echo $datafile?>','<?php echo $uploadfile ?>','<?php echo $username?>' )"/>
<input type="Button" value="Cancel" onclick="history.go(-1); return;"/>

	    <?php	}
	    }
	  else 
	    error(1,"There was an error uploading the file, please try again!");
	}
	}
    } /* end of function type_Line_Name */
	
    /* Validation completed, now load the database. */
    private function type_Database() {
      global $config;
      include($config['root_dir'] . 'theme/admin_header.php');
      global $cnt;
      $datafile = $_GET['linedata'];
      //dem jan2012: This isn't useful.
      //$filename_old = $_GET['file_name'];
      //$filename = $filename_old.rand();
      $filename = $_GET['file_name'];
      $username = $_GET['user_name'];
	
      $reader = & new Spreadsheet_Excel_Reader();
      $reader->setOutputEncoding('CP1251');
      $reader->read($datafile);
      $linedata = $reader->sheets[0];
      $cols = $reader->sheets[0]['numCols'];
      $rows = $reader->sheets[0]['numRows'];
	
      // Read the Breeding Program from row 4.
      $bp = $linedata['cells'][4][2];

      // Available line properties:
      $res = mysql_query("select name from properties") or die (mysql_error());
      while ($r = mysql_fetch_row($res))
	$properties[] = $r[0];

      /* Attempt to find each required column */
      // First, locate the header line.
      $firstline = 0;
      $header = array();
      for ($irow = 5; $irow <=$rows; $irow++) {
	$teststr= addcslashes(trim($linedata['cells'][$irow][1]),"\0..\37!@\177..\377");
	if (empty($teststr)){
	  break; 
	} elseif (strtolower($teststr) =="*line name") {
	  $firstline = $irow;
	  // read out header line
	  for ($icol = 1; $icol <= $cols; $icol++) {
	    $firstline = $irow;
	    $value = addcslashes(trim($linedata['cells'][$irow][$icol]),"\0..\37!@\177..\377");
	    $header[] = $value;
	  }
	  break 1;
	}
      }
      // Now read in the data cells.		
      foreach($header as $columnOffset => $columnName) { // Loop through the columns in the header row.
	// Require exact match for Property names.
	if ($columnOffset < 6) {
	  //Clean up column name so that it can be matched.
	  $columnName = strtolower(trim($columnName));
	  $order = array("\n","\t"," ");
	  $replace = array(" ",'','');
	  $columnName = str_replace($order, $replace, $columnName);
	}
	// Determine the column offset of "*Line Name"...
	if (preg_match('/^\s*\*linename\s*$/is', trim($columnName)))
	  $columnOffsets['line_name'] = $columnOffset+1;
	
	// Determine the column offset of "Aliases"...
	if (preg_match('/^\s*aliases\s*$/is', trim($columnName)))
	  $columnOffsets['synonyms'] = $columnOffset+1;

	// Determine the column offset of "GRIN Accession"...
	if (preg_match('/^\s*grinaccession\s*$/is', trim($columnName)))
	  $columnOffsets['grin'] = $columnOffset+1;
		
	// Determine the column offset of "Pedigree"...
	if (preg_match('/^\s*pedigree\s*$/is', trim($columnName)))
	  $columnOffsets['pedigree'] = $columnOffset+1;
		
	// Determine the column offset of "*Filial Generation"...
	if (preg_match('/^\s*\*filialgeneration\s*$/is', trim($columnName)))
	  $columnOffsets['generation'] = $columnOffset+1;
		
	// Determine the column offset of "*aestivum / durum / other"...
	if (preg_match('/^\s*\*aestivum\/durum\/other\s*$/is', trim($columnName)))
	  $columnOffsets['species'] = $columnOffset+1;
		
	// Determine the column offset of "Comments"...
	if (preg_match('/^\s*comments\s*$/is', trim($columnName)))
	  $columnOffsets['comments'] = $columnOffset+1;

	// Find other Properties, and determine the column offset...
	$pr = trim($columnName);
	if (in_array($pr, $properties)) {
	  // Get this property's allowed values.
	  $propuid = mysql_grab("select properties_uid from properties where name = '$pr'");
	  $sql = "select value from property_values where property_uid = $propuid";
	  $res = mysql_query($sql) or die(mysql_error());
	  while ($r = mysql_fetch_row($res)) 
	    $allowedvals[$pr][] = $r[0];
	  $columnOffsets[$columnName] = $columnOffset+1;
	  $ourprops[] = $pr;
	}
      } // end foreach($header as $columnOffset => $columnName)

      for ($irow = $firstline+2; $irow <=$rows; $irow++)  {
	//Ignore empty rows.
	if (empty($linedata['cells'][$irow])) break;
	//Extract data
	$line = strtoupper(trim($linedata['cells'][$irow][$columnOffsets['line_name']]));
	// Line Name is required.
	if (!empty($line)) {
	  $synonyms = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['synonyms']]),"\0..\37!@\177..\377");
	  // Strip out any single-quotes.
	  $synonyms = str_replace('\'', '', $synonyms);
	  $synonyms = explode(',', str_replace(', ', ',', $synonyms));
	  if (!empty ($synonyms)) {
	    $tooshort = array();
	    foreach ($synonyms as $s) 
	      if (!empty($s))
		if ( (strlen($s) < 3) OR (strlen($s) < 4 AND is_numeric($s)) ) 
		  array_push($tooshort, $s);
	    $synonyms = array_diff($synonyms, $tooshort);
	  }
	  $grin = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['grin']]),"\0..\37!@\177..\377");
	  $generation = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['generation']]),"\0..\37!@\177..\377");
	  $species = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['species']]),"\0..\37!@\177..\377");
	  $species = preg_replace("/^a$/", "aestivum", $species);
	  $species = preg_replace("/^d$/", "durum", $species);
	  $pedstring=addcslashes(trim($linedata['cells'][$irow][$columnOffsets['pedigree']]),"\0..\37!@\177..\377");
	  $comments = addcslashes(trim($linedata['cells'][$irow][$columnOffsets['comments']]),"\0..\37!@\177..\377");
	  foreach ($ourprops as $pr) {
	    $propval[$pr] = addcslashes(trim($linedata['cells'][$irow][$columnOffsets[$pr]]),"\0..\37!@\177..\377");
	  }
				
	  $line_uid=get_lineuid($line);
	  // $line_uid is an array.
	  if ($line_uid === FALSE) {
	    // Insert new line into database
	    //convert line name to upper case and replace spaces with an underscore
	    $line = strtoupper(str_replace(" ","_",$line));
	    $sql_beg = "INSERT INTO line_records (line_record_name,";
	    $sql_mid = "updated_on, created_on) VALUES('$line', ";
	    $sql_end = "NOW(),NOW())";
	    if (!empty($pedstring)) {
	      $sql_beg .= "pedigree_string,";
	      $pedstring = mysql_real_escape_string($pedstring);
	      $sql_mid .= "'$pedstring', ";
	    }
	    if (!empty($bp)) {
	      $sql_beg .= "breeding_program_code,";
	      $bp = mysql_real_escape_string($bp);
	      $sql_mid .= "'$bp', ";
	    }
	    // For numbers, 0 is empty.
	    if (isset($generation) AND $generation != "") {
	      $sql_beg .= "generation,";
	      $generation = mysql_real_escape_string($generation);
	      $sql_mid .= "'$generation', ";
	    }
	    if (!empty($species)) {
	      $sql_beg .= "species,";
	      $species = mysql_real_escape_string($species);
	      $sql_mid .= "'$species', ";
	    }
	    if (!empty($comments)) {
	      $sql_beg .= "description,";
	      $comments = mysql_real_escape_string($comments);
	      $sql_mid .= "'$comments', ";
	    }
	    $sql = $sql_beg.$sql_mid.$sql_end;
	    $linesuccess = TRUE;
	    $rlinsyn=mysql_query($sql) or $linesuccess = errmsg($sql, mysql_error());
	    $line_uid = mysql_insert_id();
	    // $line_uid is no longer an array===FALSE, cf. above, it's an int.
	    if ($linesuccess) {
	      // Insert property values in table line_properties.
	      foreach ($ourprops as $pr) {
                if (!empty($propval[$pr])) {
		  $propval[$pr] = mysql_real_escape_string($propval[$pr]);
		  $propuid = mysql_grab("select properties_uid from properties where name = '$pr'");
		  $propvaluid = mysql_grab("select property_values_uid from property_values 
                                          where property_uid = $propuid and value = '$propval[$pr]'");
		  $sql = "insert into line_properties (line_record_uid, property_value_uid) values ($line_uid, $propvaluid)";
		  $res = mysql_query($sql) or errmsg($sql, mysql_error());
		}
	      }
	      // Insert synonyms.
	      if (!empty($synonyms)) {
		foreach ($synonyms as $syn) {
		  if (!empty($syn)) {
		    $sql = "insert into line_synonyms 
		      (line_record_uid, line_synonym_name, updated_on, created_on) values 
		      ('$line_uid', '$syn', NOW(),NOW())";
		    $res = mysql_query($sql) or errmsg($sql, mysql_error());
		  }
		}	  
	      }
	      // Insert GRIN accession.
	      if (!empty($grin)) {
		// Is there already a GRIN accession for this line?  If so, replace.
		$sql = "select barley_pedigree_catalog_ref_uid from barley_pedigree_catalog_ref
                WHERE barley_pedigree_catalog_uid=2
                AND line_record_uid = '$line_uid'";
		$res = mysql_query($sql) or errmsg($sql, mysql_error());
		if (mysql_num_rows($res) == 1) {
		  $sql = "update barley_pedigree_catalog_ref set barley_ref_number = '$grin',
                updated_on=NOW() WHERE barley_pedigree_catalog_uid=2 
                AND line_record_uid = '$line_uid'";
		  $res = mysql_query($sql) or errmsg($sql, mysql_error()." at script line 990");
		}
		elseif (mysql_num_rows($res) == 0) {
		  $sql = "insert into barley_pedigree_catalog_ref 
                (barley_pedigree_catalog_uid, line_record_uid, barley_ref_number, 
                updated_on, created_on) values ('2', '$line_uid', '$grin', NOW(),NOW())";
		  $res = mysql_query($sql) or errmsg($sql, mysql_error()." at script line 996");
		}
		else echo "Unexpected: Line '$line' already has multiple GRIN accessions. Not adding.<br>";
	      }
	    }
						
	  } // end of if ($line_uid === FALSE) 

	  elseif (count($line_uid)==1) { 
	    //update the line record
	    $line_uids = implode(",",$line_uid);
	    // $line_uids is a string containing a single uid.				
	    $sql_beg = "update line_records set ";
	    $sql_mid = "";
	    $sql_end = "updated_on=NOW() where line_record_uid = '$line_uids'";
	    if (!empty($pedstring)) {
	      $pedstring = mysql_real_escape_string($pedstring);
	      $sql_mid .= "pedigree_string = '$pedstring', ";
	    }
	    if (!empty($bp)) {
              $bp = mysql_real_escape_string($bp);
	      $sql_mid .= "breeding_program_code='$bp', ";
	    }
	    // For numbers, 0 is empty.
	    if (isset($generation) AND $generation != "") {
              $generation = mysql_real_escape_string($generation);
	      $sql_mid .= "generation = '$generation', ";
	    }
	    if (!empty($species)) {
              $species = mysql_real_escape_string($species);
	      $sql_mid .= "species = '$species', ";
	    }
	    if (!empty($comments)) {
              $comments = mysql_real_escape_string($comments);
	      $sql_mid .= "description = '$comments', ";
	    }
	    $sql = $sql_beg.$sql_mid.$sql_end;
	    $linesuccess = TRUE;
	    $rlinsyn=mysql_query($sql) or $linesuccess = errmsg($sql, mysql_error());
            if ($linesuccess) {
	      // Update property values in table line_properties.
	      foreach ($ourprops as $pr) {
		if (!empty($propval[$pr])) {
		  $propval[$pr] = mysql_real_escape_string($propval[$pr]);
		  $propuid = mysql_grab("select properties_uid from properties where name = '$pr'");
		  $propvaluid = mysql_grab("select property_values_uid from property_values 
                                          where property_uid = $propuid and value = '$propval[$pr]'");
		  // Is there already a value for this line and property?  If so, replace.
		  $linepropuid = mysql_grab("select line_properties_uid
			      from line_properties lp, property_values pv
			      where line_record_uid = $line_uids and property_uid = $propuid
			      and lp.property_value_uid = pv.property_values_uid");
		  if (!empty($linepropuid)) {
		    mysql_query("update line_properties 
                        set property_value_uid = $propvaluid
                        where line_properties_uid = $linepropuid") or errmsg($sql, mysql_error());
		  }
		  else {
		    /* echo "Row $irow, Line $line: Inserting $pr value $propval[$pr].<p>"; */
		    mysql_query("insert into line_properties (line_record_uid, property_value_uid) 
                          values ($line_uids, $propvaluid)") or errmsg($sql, mysql_error());
		  }
		}
	      }
	      // Update synonyms.
	      if (!empty($synonyms)) {
		// Don't add the synonym if it's already there.
		$res = mysql_query("select line_synonym_name from line_synonyms where line_record_uid = $line_uids") 
		  or die(mysql_error());
		while ($row = mysql_fetch_row($res))
		  $oldsyns[] = $row[0];
		foreach ($synonyms as $syn) {
		  if (!empty($syn) AND !in_array($syn, $oldsyns)) {
		    $sql = "insert into line_synonyms 
	  	      (line_record_uid, line_synonym_name, updated_on, created_on) values 
		      ('$line_uids', '$syn', NOW(),NOW())";
		    $res = mysql_query($sql) or errmsg($sql, mysql_error());
		  }  
		}  // end foreach ($synonyms as $syn)
	      }  // end if (!empty($synonyms))
	      // Update GRIN accession.
	      if (!empty($grin)) {
		// Is there already a GRIN accession for this line?  If so, replace.
		// $line_uids is a string and $line_uid is an array.  Both contain one uid.
		//echo "<pre>Line 1090: line_uids = $line_uids<br>line_uid = "; print_r($line_uid); echo "</pre>"; 
		$sql = "select barley_pedigree_catalog_ref_uid from barley_pedigree_catalog_ref
                WHERE barley_pedigree_catalog_uid=2
                AND line_record_uid = '$line_uids'";
		$res = mysql_query($sql) or errmsg($sql, mysql_error());
		if (mysql_num_rows($res) == 1) {
		  $sql = "update barley_pedigree_catalog_ref set barley_ref_number = '$grin',
                updated_on=NOW() WHERE barley_pedigree_catalog_uid=2 
                AND line_record_uid = '$line_uids'";
		  $res = mysql_query($sql) or errmsg($sql, mysql_error());
		}
		elseif (mysql_num_rows($res) == 0) {
		  $sql = "insert into barley_pedigree_catalog_ref 
                (barley_pedigree_catalog_uid, line_record_uid, barley_ref_number, 
                updated_on, created_on) values ('2', '$line_uids', '$grin', NOW(),NOW())";
		  $res = mysql_query($sql) or errmsg($sql, mysql_error().' at script line 1101');
		}
		else {};  // More than one GRIN accession for this line already.
	      }
	    }
	  }
	  else {
	    $line_uids = implode(",",$line_uid);
	    error(0, "$line is found in multiple records($line_uids), in line record table, please fix");
	  }
	  if (!empty($CAPcode)) {
	    if ($CAPcode == $line) 
	      continue; /* for 2008 data*/
	    else {				
	      $linesyn_uid = get_lineuid($CAPcode);
	      if ($linesyn_uid===FALSE) {
		// Insert CAPentry code as a synonym into database
		$sql = "INSERT INTO line_synonyms (line_record_uid,line_synonym_name, updated_on, created_on)
										VALUES($line_uid, '$CAPcode', NOW(),NOW())";
	      } elseif ((count($linesyn_uid)==1)AND($linesyn_uid!=$line_uid)){
		$linesyn_uids = implode(",",$line_uid);
		$sql = mysql_query("SELECT line_record_name from line_records where line_record_uid in ($linesyn_uids)");
		$row = mysql_fetch_array($sql);
		$line_name = $row['line_record_name'];
		error(0, "$CAPcode is linked to a diffent line ($line_name), in line record table, please fix");
	      } elseif (count($linesyn_uid)>1) {
		$linesyn_uids = implode(",",$line_uid);
		error(0, "$CAPcode is linked multiple lines ($linesyn_uids), in line record table, please fix");
	      }
	    }
	  }
      } /* end of if (!empty($line)) */
    }
		 
      if ($cnt > 0) {
	// if MySQL errors
	// Cool.  Jump back _two_ pages!
	print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-2); return;\">";
      }
      else {
	echo "<h3>Loaded</h3>";
	echo "The data was loaded successfully. You can check it with <a href='".$config['base_url']."search.php'>Quick search...</a>";
	// Timestamp, e.g. _28Jan12_23:01
	$ts = date("_jMy_H:i");
	$filename = $filename . $ts;
	$devnull = mysql_query("INSERT INTO input_file_log (file_name,users_name) VALUES('$filename', '$username')") or die(mysql_error());
      }

      $footer_div = 1;
      include($config['root_dir'].'theme/footer.php');
		
    } /* end of function type_Database */

} /* end of class */

?>
