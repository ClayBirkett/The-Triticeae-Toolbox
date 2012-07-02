<?
// 18may2012  DEM Added Trial Year. Renamed some fields.
// 09/01/2011 CBirkett	changed to new template and schema
// 01/25/2011 JLee  Check 'number of entries' and 'number of replition' input values 

require 'config.php';
//define("DEBUG",2);

/*
 * Logged in page initialization
 */
include($config['root_dir'] . 'includes/bootstrap_curator.inc');
include($config['root_dir'] . 'curator_data/lineuid.php');

require_once("../lib/Excel/excel_reader2.php"); // Microsoft Excel library

connect();
loginTest();

/* ******************************* */
$row = loadUser($_SESSION['username']);

////////////////////////////////////////////////////////////////////////////////
ob_start();

authenticate_redirect(array(USER_TYPE_ADMINISTRATOR, USER_TYPE_CURATOR));
ob_end_flush();


new Annotations_Check($_GET['function']);

class Annotations_Check
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
				$this->typeAnnotationCheck(); /* intial case*/
				break;
			
		}	
	}


private function typeAnnotationCheck()
	{
		global $config;
		include($config['root_dir'] . 'theme/admin_header.php');

		echo "<h2> Add/Update Experiment Annotations: Validation</h2>"; 
		
			
		$this->type_Annotation();

		$footer_div = 1;
        include($config['root_dir'].'theme/footer.php');
	}
	
	
	private function type_Annotation()
	{
	?>
	<script type="text/javascript">
	
	    function update_database(filepath, filename, username, data_public_flag)
	{
			
			
			var url='<?php echo $_SERVER[PHP_SELF];?>?function=typeDatabase&linedata=' + filepath + '&file_name=' + filename + '&user_name=' + username + '&public=' + data_public_flag;
	
			// Opens the url in the same window
	   	window.open(url, "_self");
	}
	
	
	
	
	</script>
	
	<style type="text/css">
			th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6; white-space: nowrap }
			table {background: none; border-collapse: collapse}
			td {border: 0px solid #eee !important; white-space: nowrap}
			h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
		</style>
		
		<style type="text/css">
                   table.marker
                   {background: none; border-collapse: collapse}
                    th.marker
                    { background: #5b53a6; color: #fff; padding: 5px 0; border: 0;}
                    
                    td.marker
                    { padding: 5px 0; border: 0 !important; }
                </style>
		
		
		
		      <?php                      // dem 3dec10: Must include these files again, don't know why. 
		      require 'config.php';

  $row = loadUser($_SESSION['username']);
	
	ini_set("memory_limit","24M");
	
	$username=$row['name'];
	
		      	$tmp_dir=$config['root_dir']."curator_data/uploads/tmpdir_".$username."_".rand();
	
	umask(0);
	
	if(!file_exists($tmp_dir) || !is_dir($tmp_dir)) {
	  mkdir($tmp_dir, 0777) or die("Couldn't mkdir $tmp_dir");
	}

	$target_path=$tmp_dir."/";

	
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		$data_public_flag = $_POST['flag']; //1:yes, 0:no
		//echo" we got the value for data flag".$data_public_flag1;
	}
	
	if ($_FILES['file']['name'][0] == ""){
		error(1, "No File Uploaded");
		print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
	}
	else {
		
		$uploadfile=$_FILES['file']['name'][0];
				
		$uftype=$_FILES['file']['type'][0];
		if (strpos($uploadfile, ".xls") === FALSE) {
			error(1, "Expecting an Excel file. <br> The type of the uploaded file is ".$uftype);
			print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
		}
		else {
		
			if(move_uploaded_file($_FILES['file']['tmp_name'][0], $target_path.$uploadfile)) 
			{

    			/* start reading the excel */
		
		
		$annotfile = $target_path.$uploadfile;
		
						
		/* Read the annotation file */
	$reader = & new Spreadsheet_Excel_Reader();
	$reader->setOutputEncoding('CP1251');
	if (strpos($annotfile,'.xls')>0)
	{
		$reader->read($annotfile);
	}else {
		$reader->read($annotfile . ".xls");
	}
	
	$annots = $reader->sheets[0];
	$cols = $reader->sheets[0]['numCols'];
	$rows = $reader->sheets[0]['numRows'];
	//echo "nrows: ".$rows.", ncols: ".$cols."<br>";
	
	// find location for each row of data; find where data starts in file
	for ($i = 1; $i <= $rows; $i++) {
          if (stripos($annots['cells'][$i][1],'template version')!==FALSE){
                $VERSION = $i;
          } elseif (stripos($annots['cells'][$i][1],'crop')!==FALSE) {
                $CROP = $i;
          } elseif (stripos($annots['cells'][$i][1],'breeding program')!==FALSE) {
                $BREEDINGPROGRAM = $i;
	  } elseif (stripos($annots['cells'][$i][1],'trial name')!==FALSE) {
		$TRIALCODE = $i;
	  } elseif (stripos($annots['cells'][$i][1],'trial year')!==FALSE) {
		$TRIALYEAR = $i;
	  } elseif (stripos($annots['cells'][$i][1],'experiment name')!==FALSE){
		$EXPERIMENT_SET = $i;
	  } elseif (stripos($annots['cells'][$i][1],'location')!==FALSE){
		$LOCATION = $i;
	  } elseif (stripos($annots['cells'][$i][1],'latitude')!==FALSE){
		$LATIT = $i;
          } elseif (stripos($annots['cells'][$i][1],'longitude')!==FALSE){
                $LONGI = $i;
	  } elseif (stripos($annots['cells'][$i][1],'collaborator')!==FALSE){
		$COLLABORATOR = $i;
	  } elseif (stripos($annots['cells'][$i][1],'trial description')!==FALSE){
		$TRIAL_DESC = $i;
	  } elseif (stripos($annots['cells'][$i][1],'planting date')!==FALSE){
		$PLANTINGDATE = $i;
	  } elseif (stripos($annots['cells'][$i][1],'harvest date')!==FALSE){
		$HARVESTDATE = $i;
          } elseif (stripos($annots['cells'][$i][1],'begin weather')!==FALSE){
                $BEGINWEATHER = $i;
          } elseif (stripos($annots['cells'][$i][1],'greenhouse')!==FALSE){
                $GREENHOUSE = $i;
	  } elseif (stripos($annots['cells'][$i][1],'seeding rate')!==FALSE){
		$SEEDINGRATE = $i;
	  } elseif (stripos($annots['cells'][$i][1],'experimental design')!==FALSE){
		$EXPERIMENTALDESIGN = $i;
	  } elseif (stripos($annots['cells'][$i][1],'number of entries')!==FALSE){
		$NUMBEROFENTRIES = $i;
	  } elseif (stripos($annots['cells'][$i][1],'replications')!==FALSE){
		$NUMBEROFREPLICATIONS = $i;
	  } elseif (stripos($annots['cells'][$i][1],'plot size')!==FALSE){
		$PLOTSIZE = $i;
	  } elseif (stripos($annots['cells'][$i][1],'harvested area')!==FALSE){
		$HARVESTEDAREA = $i;
	  } elseif (stripos($annots['cells'][$i][1],'irrigation')!==FALSE){
		$IRRIGATION = $i;
	  } elseif (stripos($annots['cells'][$i][1],'other')!==FALSE){
		$OTHERREMARKS = $i;
	  } else {
          }
	}

	// Identify the Rows.
	$version_row =                  $annots['cells'][$VERSION];
        $crop_row = 			$annots['cells'][$CROP];

	$bp_row =			$annots['cells'][$BREEDINGPROGRAM];
	$trialcode_row =		$annots['cells'][$TRIALCODE];
   	$year_row = 			$annots['cells'][$TRIALYEAR];
	$location_row =			$annots['cells'][$LOCATION];
	$latitude_row =			$annots['cells'][$LATIT];
        $longitude_row = 		$annots['cells'][$LONGI];
	$collaborator_row =		$annots['cells'][$COLLABORATOR];
	$plantingdate_row =		$annots['cells'][$PLANTINGDATE];
	$harvestdate_row =		$annots['cells'][$HARVESTDATE];
	$beginweatherdate_row = 	$annots['cells'][$BEGINWEATHER];
	$greenhouse_row = 		$annots['cells'][$GREENHOUSE];
	$seedingrate_row =		$annots['cells'][$SEEDINGRATE];
	$experimentset_row =	        $annots['cells'][$EXPERIMENT_SET];
	$trialdesc_row =	        $annots['cells'][$TRIAL_DESC];
	$experimentaldesign_row =	$annots['cells'][$EXPERIMENTALDESIGN];
	$numberofentries_row =	        $annots['cells'][$NUMBEROFENTRIES];
	$numberofreplications_row =	$annots['cells'][$NUMBEROFREPLICATIONS];
	$plotsize_row =			$annots['cells'][$PLOTSIZE];
	$harvestedarea_row =		$annots['cells'][$HARVESTEDAREA];
	$irrigation_row =		$annots['cells'][$IRRIGATION];
	$otherremarks_row =		$annots['cells'][$OTHERREMARKS];
		
/*
 * Process the annotation contents.
 */
	$error_flag = 0;
	// How many Trials are annotated in this file?:
	$n_trials = 0;
	for ($i = 2; $i <= $cols; $i++) {
	  // Sometimes Excel introduces extra columns in the data files.
	  // Stop reading at first column where Trial Name is empty.
	  if (empty($trialcode_row[$i])) 
	    $cols = $i-1;
	  else 
	    $n_trials++;
	}

	// Check for current version of the Template file.
	$version = trim($version_row[2]);
	$template = $config['root_dir']."curator_data/examples/T3/TrialSubmissionForm.xls";
	if (!check_version($version, $template)) {
	  echo "<b>Error</b>: The template file has been updated since your version, <b>$version</b>.<br>
          Please use the new one.<br>";
	  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	};

	// Check for Breeding Program.
	$bp = trim($bp_row[2]);
	if (!$bp) {
	  echo "Breeding Program Code is required.<br>";
	  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	}
	else {$sql = "SELECT CAPdata_programs_uid FROM CAPdata_programs
		WHERE data_program_code= '$bp'";
	  $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	  if (1 == mysql_num_rows($res))		{
	    $row = mysql_fetch_assoc($res);
	    $capdata_uid = $row['CAPdata_programs_uid'];
	  }
	  else {
	    echo "Breeding Program <b>'$bp'</b> not found in table CAPdata_programs.<br/>";
	    exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	  }
	}

	// Create the array to hold the data, $experiments[$index]:
	$experiments = array();
	for ($i = 0; $i < $n_trials; $i++) {
	  $experiments[$i] = new experiment();
	}
	// Start reading in the trials.
        for ($i = 2; $i <= $cols; $i++) {
	  $colname = chr($i+64);
	  // Set the index for array $experiments[].
	  $index = $i - 2;

	  $trialcode = $experiments[$index]->trialcode = mysql_real_escape_string(trim($trialcode_row[$i]));
	  if (DEBUG>1) {echo "experiments trialcode [".$i."] is set to".$experiments[$index]->trialcode."<br>";}
	  // Trial code. Verify not null, then see if it is in db AND unique.
	  if (is_null($trialcode)) {
	    echo "Trial code ".$trialcode." is empty "."<br/>";
	    $error_flag = ($error_flag) | (4);
	    exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	  }
	  else   {
	    $sql = "SELECT experiment_uid FROM experiments WHERE trial_code = '{$trialcode}'";
	    $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	    if (mysql_num_rows($res)==1) { //yes, experiment found once  
	      $row = mysql_fetch_assoc($res);
	      $exp_id = $row['experiment_uid'];
	      if (DEBUG>1) {echo "exp ID ".$exp_id."\n";}
	    } 
	    elseif (mysql_num_rows($res)>1) { //yes, experiment found more than once, bad
	      if (DEBUG>1) {echo "Trial code ".$trialcode." linked to multiple experiments-must fix"."<br/>";}
	      $error_flag = ($error_flag) | (8);
	      exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	    }
	  }
		
	  $experiments[$index]->location = addslashes($location_row[$i]);
	  $experiments[$index]->latitude = mysql_real_escape_string($latitude_row[$i]);
	  $experiments[$index]->longitude = mysql_real_escape_string($longitude_row[$i]);
	  $experiments[$index]->collaborator = mysql_real_escape_string($collaborator_row[$i]);
	  $experiments[$index]->seedingrate = $seedingrate_row[$i];
	  $experiments[$index]->trialdesc = mysql_real_escape_string(trim($trialdesc_row[$i]));
	  $experiments[$index]->experimentaldesign = mysql_real_escape_string($experimentaldesign_row[$i]);
	  $experiments[$index]->beginweatherdate = mysql_real_escape_string($beginweatherdate_row[$i]);
	  $experiments[$index]->greenhouse = mysql_real_escape_string($greenhouse_row[$i]);
	  $experiments[$index]->experimentset = mysql_real_escape_string(trim($experimentset_row[$i]));

	  // Check key data fields in the experiment to ensure valid values
	  // Required fields include: year, location, collaborator (who performed experiment)

	  $year = $year_row[$i];
	  $experiments[$index]->year = $year;
	  $today = getdate();
	  // dem may12: Allow old data, and next year.
	  $curr_year = $today['year'] + 1;
	  if (($year < 1950) OR ($year > $curr_year)) {
	    echo "Trial Year value not in range [1950 - current year]: 
                  <font color=red><b>'$year'</b></font>, column $colname<br>";
	    $error_flag = ($error_flag) | (1);
	  }
		
	  $location = $experiments[$index]->location;
	  if (!$location) {
	    echo "Column <b>$colname</b>: Location (city, state/province/country) is required.<br>";
	    $error_flag = ($error_flag) | (2);
}

	  $collab = $experiments[$index]->collaborator;
	  if (!$collab) {
	    echo "Column <b>$colname</b>: Collaborator name (who performed the experiment) is required.<br>";
	    $error_flag = ($error_flag) | (8);
}

        // Check for floating point values 
        if ((strpos($numberofentries_row[$i], '.') != 0) || (strpos($numberofreplications_row[$i], '.') != 0)) {
            echo "<b>Error: Not an integer value encountered in either the 'Number of entries' or 'Number of replication' field. </b><br/><br/>";
		    exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        }
 
       //Check number of entries
	$entries = $numberofentries_row[$i];
        if ((is_numeric($numberofentries_row[$i])) || ($numberofentries_row[$i] == '' )) {
            $experiments[$index]->numberofentries = intval($numberofentries_row[$i]);
        } 
	else {
	  echo "<b>Error</b>, column $colname: 'Number of entries' must be an integer, value is '$numberofentries_row[$i]'.<br/>";
	  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        }
 
        //Check Number of replications column 
        if ((is_numeric($numberofreplications_row[$i])) || ($numberofreplications_row[$i] == '' )) {
	  $experiments[$index]->numberofreplications = intval($numberofreplications_row[$i]);
        } else {
	  echo "<b>Error: Value for 'Number of replications' must be an integer </b><br/><br/>";
	  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        }

	// Planting Date
	$teststr= addcslashes(trim($plantingdate_row[$i]),"\0..\37!@\177..\377");
	$phpdate = date_create_from_format('n/j/Y', $teststr);
	if ($phpdate === FALSE) {
	  echo "Couldn't parse Planting Date for column <font color=red><b>".chr($i+64)."</b></font>.<p>";
	  //print_h(date_get_last_errors());  // debug
	}
	else {
	  $fdate = date_format($phpdate, 'n/j/Y');
	  if ($fdate == $teststr) 
	    $experiments[$index]->plantingdate = $teststr;
	}
	if ($phpdate === FALSE OR !($fdate == $teststr)) {
	  $experiments[$index]->plantingdate = '';
	  echo "<b>Error</b>: Please use <i>m/d/yyyy</i> format for Planting date, e.g. \"5/9/2012\"<br>
                  instead of <font color=red>\"$teststr\"</font>.<br>";
	  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	}
	
	// Harvest Date
	$teststr= addcslashes(trim($harvestdate_row[$i]),"\0..\37!@\177..\377");
	$phpdate = date_create_from_format('n/j/Y', $teststr);
	if ($phpdate === FALSE) {
	  echo "Couldn't parse Harvest Date for column <font color=red><b>".chr($i+64)."</b></font>.<p>";
	  //print_h(date_get_last_errors());  // debug
	}
	else {
	  $fdate = date_format($phpdate, 'n/j/Y');
	  if ($fdate == $teststr) 
	    $experiments[$index]->harvestdate = $teststr;
	}
	if ($phpdate === FALSE OR $fdate != $teststr) {
	  $experiments[$index]->harvestdate = '';
	  echo "<b>Error</b>: Please use <i>m/d/yyyy</i> format for Harvest date, e.g. \"8/9/2012\"<br>
                  instead of <font color=red>\"$teststr\"</font>.<br>";
	  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	}
	
	// Weather Date, optional
	$teststr= addcslashes(trim($beginweatherdate_row[$i]),"\0..\37!@\177..\377");
	if ($teststr) {
	  $phpdate = date_create_from_format('n/j/Y', $teststr);
	  if ($phpdate === FALSE) {
	    echo "Couldn't parse Begin Weather Date for column <font color=red><b>".chr($i+64)."</b></font>.<p>";
	    //print_h(date_get_last_errors());  // debug
	  }
	  else {
	    $fdate = date_format($phpdate, 'n/j/Y');
	    if ($fdate == $teststr) 
	      $experiments[$index]->beginweatherdate = $teststr;
	  }
	  if ($phpdate === FALSE OR $fdate != $teststr) {
	    $experiments[$index]->beginweatherdate = '';
	    echo "<b>Error</b>: Please use <i>m/d/yyyy</i> format for Begin weather date, e.g. \"4/9/2012\"<br>
                  instead of <font color=red>\"$teststr\"</font>.<br>";
	    exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	  }
	}

	// Greenhouse trial?
	$experiments[$index]->greenhouse = mysql_real_escape_string($greenhouse_row[$i]);
	$gh = $experiments[$index]->greenhouse;
	if ($gh != "yes" AND $gh != "no") {
	  echo "<b>Error</b>: 'Greenhouse trial?' must be yes or no.<br>";
	  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	}
	
	// Plot Size
	$experiments[$index]->plotsize = mysql_real_escape_string($plotsize_row[$i]);
		
	// Harvest Area
	$experiments[$index]->harvestedarea = mysql_real_escape_string($harvestedarea_row[$i]);

	// Irrigation
	$experiments[$index]->irrigation = mysql_real_escape_string($irrigation_row[$i]);
	$ir = $experiments[$index]->irrigation;
	if ($ir != "yes" AND $ir != "no") {
	  echo "<b>Error</b>, column <font color=red><b>".chr($i+64)."</b></font>: 'Irrigation' must be yes or no.<br>";
	  exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	}

	// Other Remarks
	$experiments[$index]->otherremarks = mysql_real_escape_string(htmlentities($otherremarks_row[$i]));
		
	}
	
	if ($error_flag>0)  {
	  echo "<br><b>Fatal error:</b> problems with required fields: trial year, location, collaborator.<br/>";
	  print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
	}
	else {
	  // Show what we see.
	  ?>
		
	  <h3>The following data were found in the uploaded file.</h3>
	    <table ><thead><tr>
	    <th>Status</th>
	    <th>Trial Name </th>
	    <th>Experiment </th>
	    <th>Breeding<br>Program </th>
	    <th>Collaborator </th>
	    <th>Location </th>
	    <th>Latitude</th>
	    <th>Longitude </th>
	    <th>Planting<br>date </th>
	    <th>Harvest<br>date </th>
	    <th>Seeding<br>rate</th>
	    <th>Experimental<br>design</th>
	    <th>Entries</th>
	    <th>Replications </th>
	    <th>Plot size<br>(m2) </th>
	    <th>Harvested<br>area (m2) </th>
	    <th>Irrigation</th>
	    <th>Other remarks </th>
	    </tr></thead>
	    <tbody style="padding: 0; height: 200px; overflow: scroll;border: 1px solid #5b53a6;">	

<?php
	    for ($i = 2; $cols >= $i; $i++)  {
	      print "<tr><td><font color=red>";
	      $sql = "SELECT experiment_uid FROM experiments WHERE trial_code = '$trialcode_row[$i]'";
	      $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	      if (mysql_num_rows($res)!==0) //yes, experiment found, so update
		print "Update</td><td>";
	      else 
		print "New</td><td>";
	      print "</font>";
	      $newtext = wordwrap($trialcode_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($experimentset_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext1 = wordwrap($bp_row[2], 6, '<br>');
	      print "$newtext1</td><td>";
	      $newtext = wordwrap($collaborator_row[$i], 12, '<br>');
	      print "$newtext</td><td>";
	      $newtext2 = wordwrap($location_row[$i], 6, '<br>');
	      print "$newtext2</td><td>";
	      $newtext = wordwrap($latitude_row[$i], 6, '<br>');
	      print "$newtext</td><td>";
	      $newtext = wordwrap($longitude_row[$i], 6, '<br>');
	      print "$newtext</td><td>";
	      $newtext = wordwrap($plantingdate_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($harvestdate_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($seedingrate_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($experimentaldesign_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($numberofentries_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($numberofreplications_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($plotsize_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($harvestedarea_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($irrigation_row[$i], 6, '<br>');
	      print "$newtext<td>";
	      $newtext = wordwrap($otherremarks_row[$i], 40, "<br>" );
	      print "$newtext";
	    } /* end of loop over file rows */
?>
	  </tbody>
	      </table>
	      <p>		
	      If Status is "<font color=red>Update</font>", this Trial has been loaded previously and the values shown will replace the existing ones.
	      <p>
	      <input type="Button" value="Accept" onclick="javascript: update_database('<?echo $annotfile?>','<?echo $uploadfile?>','<?echo $username?>','<?echo $data_public_flag?>' )"/>
	      <input type="Button" value="Cancel" onclick="history.go(-1); return;"/>
	
<?php
	      }
   			}
			else {
			  error(1,"There was an error uploading the file, please try again!");
			}
		}
	}
	} // end of function type_Annotation
	
	private function type_Database() {
	  global $config;
	  include($config['root_dir'] . 'theme/admin_header.php');
		
	  $datafile = $_GET['linedata'];
	  $filename = $_GET['file_name'];
	  $username = $_GET['user_name'];
	  $data_public_flag = $_GET['public'];
	
	  $reader = & new Spreadsheet_Excel_Reader();
	  $reader->setOutputEncoding('CP1251');
	  if (strpos($datafile,'.xls')>0)
	      $reader->read($datafile);
	  else
	    $reader->read($datafile . ".xls");
	
	  $annots = $reader->sheets[0];
	  $cols = $reader->sheets[0]['numCols'];
	  $rows = $reader->sheets[0]['numRows'];
	
	  // find location for each row of data; find where data starts in file
	  for ($i = 1; $i <= $rows; $i++) {
	    if (stripos($annots['cells'][$i][1],'breeding program')!==FALSE){
	      $BREEDINGPROGRAM = $i;
	    } elseif (stripos($annots['cells'][$i][1],'trial name')!==FALSE) {
	      $TRIALCODE = $i;
	    } elseif (stripos($annots['cells'][$i][1],'trial year')!==FALSE) {
	      $TRIALYEAR = $i;
	    } elseif (stripos($annots['cells'][$i][1],'experiment name')!==FALSE){
	      $EXPERIMENT_SET = $i;
	    } elseif (stripos($annots['cells'][$i][1],'location')!==FALSE){
	      $LOCATION = $i;
	    } elseif (stripos($annots['cells'][$i][1],'latitude')!==FALSE){
	      $LATIT = $i;
	    } elseif (stripos($annots['cells'][$i][1],'longitude')!==FALSE){
	      $LONGI = $i;
	    } elseif (stripos($annots['cells'][$i][1],'collaborator')!==FALSE){
	      $COLLABORATOR = $i;
	    } elseif (stripos($annots['cells'][$i][1],'trial description')!==FALSE){
	      $TRIAL_DESC = $i;
	    } elseif (stripos($annots['cells'][$i][1],'planting date')!==FALSE){
	      $PLANTINGDATE = $i;
	    } elseif (stripos($annots['cells'][$i][1],'harvest date')!==FALSE){
	      $HARVESTDATE = $i;
	    } elseif (stripos($annots['cells'][$i][1],'begin weather')!==FALSE){
	      $BEGINWEATHER = $i;
	    } elseif (stripos($annots['cells'][$i][1],'greenhouse')!==FALSE){
	      $GREENHOUSE = $i;
	    } elseif (stripos($annots['cells'][$i][1],'seeding rate')!==FALSE){
	      $SEEDINGRATE = $i;
	    } elseif (stripos($annots['cells'][$i][1],'experimental design')!==FALSE){
	      $EXPERIMENTALDESIGN = $i;
	    } elseif (stripos($annots['cells'][$i][1],'entries')!==FALSE){
	      $NUMBEROFENTRIES = $i;
	    } elseif (stripos($annots['cells'][$i][1],'replications')!==FALSE){
	      $NUMBEROFREPLICATIONS = $i;
	    } elseif (stripos($annots['cells'][$i][1],'plot size')!==FALSE){
	      $PLOTSIZE = $i;
	    } elseif (stripos($annots['cells'][$i][1],'harvested area')!==FALSE){
	      $HARVESTEDAREA = $i;
	    } elseif (stripos($annots['cells'][$i][1],'irrigation')!==FALSE){
	      $IRRIGATION = $i;
	    } elseif (stripos($annots['cells'][$i][1],'other')!==FALSE){
	      $OTHERREMARKS = $i;
	    }
	  }

	  $bp_row =			$annots['cells'][$BREEDINGPROGRAM];
	  $trialcode_row =		$annots['cells'][$TRIALCODE];
	  $year_row =			$annots['cells'][$TRIALYEAR];
	  $location_row =		$annots['cells'][$LOCATION];
	  $latitude_row = 		$annots['cells'][$LATIT];
	  $longitude_row = 		$annots['cells'][$LONGI];
	  $collaborator_row =		$annots['cells'][$COLLABORATOR];
	  $plantingdate_row =		$annots['cells'][$PLANTINGDATE];
	  $harvestdate_row =		$annots['cells'][$HARVESTDATE];
	  $beginweatherdate_row = 	$annots['cells'][$BEGINWEATHER];
	  $greenhouse_row = 		$annots['cells'][$GREENHOUSE];
	  $seedingrate_row =		$annots['cells'][$SEEDINGRATE];
	  $experimentset_row =	        $annots['cells'][$EXPERIMENT_SET];
	  $trialdesc_row =	        $annots['cells'][$TRIAL_DESC];
	  $experimentaldesign_row =	$annots['cells'][$EXPERIMENTALDESIGN];
	  $numberofentries_row =	$annots['cells'][$NUMBEROFENTRIES];
	  $numberofreplications_row =	$annots['cells'][$NUMBEROFREPLICATIONS];
	  $plotsize_row =		$annots['cells'][$PLOTSIZE];
	  $harvestedarea_row =		$annots['cells'][$HARVESTEDAREA];
	  $irrigation_row =		$annots['cells'][$IRRIGATION];
	  $otherremarks_row =		$annots['cells'][$OTHERREMARKS];
	
	  /*
	   * Process the annotations contents.
	   */
	  // Breeding Program Code
	  $bp = trim($bp_row[2]);
	  if (DEBUG>1) {echo "experiments bp [".$i."] is set to $bp.<br>";}

	  $error_flag = 0;
	  // How many Trials are annotated in this file?:
	  $n_trials = 0;
	  for ($i = 2; $i <= $cols; $i++) {
	    // Sometimes Excel introduces extra columns in the data files.
	    // Stop reading at first column where Trial Name is empty.
	    if (empty($trialcode_row[$i])) 
	      $cols = $i-1;
	    else 
	      $n_trials++;
	  }

	  // Create the array to hold the data, $experiments[$index]:
	  $experiments = array();
	  for ($i = 0; $i < ($cols-1); $i++)
	      $experiments[$i] = new experiment();

  /* Start reading in the Trials.  */

	  for ($i = 2; $i <= $cols; $i++) {
	  $colname = chr($i+64);
	  // Set the index for array $experiments[].
	  $index = $i - 2;

	  $trialcode = $experiments[$index]->trialcode = mysql_real_escape_string(trim($trialcode_row[$i]));
	  if (DEBUG>1) {echo "experiments trialcode [".$i."] is set to".$experiments[$index]->trialcode."\n";}
	  $sql = "SELECT experiment_uid FROM experiments WHERE trial_code = '{$trialcode}'";
	  $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	  if (mysql_num_rows($res)==1) {  //yes, experiment found once
	    $row = mysql_fetch_assoc($res);
	    $exp_id = $row['experiment_uid'];
	    if (DEBUG>1) {echo "exp ID ".$exp_id."\n";}
	  } 
	  elseif (mysql_num_rows($res)>1)  { //yes, experiment found more than once, bad
	    if (DEBUG>1) {echo "Trial code ".$trialcode." linked to multiple experiments-must fix\n";}
	    $error_flag = ($error_flag) | (8);
	  }

	  $year = $year_row[$i];
	  $experiments[$index]->year = $year;
	  $today = getdate();
	  // dem may12: Allow old data, and next year.
	  $curr_year = $today['year'] + 1;
	  if (($experiments[$index]->year < 1950) OR ($year > $curr_year)) {
	    echo "Column $colname: Year <b>'$year'</b> not in range [1950 - current year].<br>";
	    $error_flag = ($error_flag) | (1);
	  }

	  $experiments[$index]->location = addslashes($location_row[$i]);
	  $experiments[$index]->latitude = mysql_real_escape_string($latitude_row[$i]);
	  $experiments[$index]->longitude = mysql_real_escape_string($longitude_row[$i]);
	  $experiments[$index]->collaborator = mysql_real_escape_string($collaborator_row[$i]);
	  $experiments[$index]->seedingrate = $seedingrate_row[$i];
	  $experiments[$index]->trialdesc = mysql_real_escape_string(trim($trialdesc_row[$i]));
	  $experiments[$index]->experimentaldesign = mysql_real_escape_string($experimentaldesign_row[$i]);
	  $experiments[$index]->beginweatherdate = mysql_real_escape_string($beginweatherdate_row[$i]);
	  $experiments[$index]->greenhouse = mysql_real_escape_string($greenhouse_row[$i]);
	  $experiments[$index]->experimentset = mysql_real_escape_string(trim($experimentset_row[$i]));

	      // Planting Date
	      $teststr= addcslashes(trim($plantingdate_row[$i]),"\0..\37!@\177..\377");
	      if (DEBUG>2) {echo $teststr."  ".$datetime."\n";}
	      if (preg_match("/\d+\/\d+\/\d+/",$teststr)) {
		$experiments[$index]->plantingdate = $teststr;
	      } else {
		$experiments[$index]->plantingdate = '';
		echo "<b>ERROR: Please use correct format for planting date (4/14/2009) </b><br>";
		exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	      }
	
	      // Check for floating point values 
	      if ((strpos($numberofentries_row[$i], '.') != 0) || (strpos($numberofreplications_row[$i], '.') != 0)) {
		echo "<b>ERROR: Not an integer value encountered in either the 'Number of entries' or 'Number of replication' field. </b><br/><br/>";
		exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");

	      }

	      //Check number of entries
	      if ((is_numeric($numberofentries_row[$i])) || ($numberofentries_row[$i] == '' )) {
		$experiments[$index]->numberofentries = intval($numberofentries_row[$i]);
	      } else {
		print "i=$i<br>\n";
		echo "<b>ERROR: Value for 'Number of entries' must be an integer </b><br/><br/>";
		exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	      }
 
	      //Check Number of replications column 
	      if ((is_numeric($numberofreplications_row[$i])) || ($numberofreplications_row[$i] == '' )) {
   	        $experiments[$index]->numberofreplications = intval($numberofreplications_row[$i]);
	      } else {
		echo "<b>ERROR: Value for 'Number of replications' must be an integer </b><br/><br/>";
		exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	      }

		
	      // Plot Size
	      $experiments[$index]->plotsize = mysql_real_escape_string($plotsize_row[$i]);
		
	      // Harvest Date
	      // convert Microsoft Excel timestamp to Unix timestamp
	      $teststr= addcslashes(trim($harvestdate_row[$i]),"\0..\37!@\177..\377");
	      if (DEBUG>2) {echo $teststr."\n";}
	
	      if (preg_match("/\d+\/\d+\/\d+/",$teststr)) {	
		if (DEBUG>2) {echo $teststr."\n";}
		$experiments[$index]->harvestdate = $teststr;
	      } else {
		$experiments[$index]->harvestdate = '';
		echo "<b>ERROR: Please use correct format for harvest date (4/14/2009) </b><br>";
		exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	      }
		
		
	
	      // Harvest Area
	      $experiments[$index]->harvestedarea = mysql_real_escape_string($harvestedarea_row[$i]);
	      // irrigation
	      if (FALSE !== stripos($irrigation_row[$i], "yes"))
		{
		  $experiments[$index]->irrigation = 'yes';
		}
	      else
		{
		  $experiments[$index]->irrigation = 'no';
		}
		
	      // Other Remarks
	      $experiments[$index]->otherremarks = mysql_real_escape_string(htmlentities($otherremarks_row[$i]));
		
	}
	
		
	  if ($error_flag>0) {
	    echo "FATAL ERROR: problems with one or more required fields: year, trialcode, experiment short name or collaborator code<p>";
	    print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
	  }
	
	  else {

	    // Insert data into experiments and phenotype_experiments  table
	    $myind=0;
	    $experiments_real = array();
	    for ($i = 0; $i < $n_trials; $i++)
		$experiments_real[$i] = $experiments[$i];
	
	    foreach ($experiments_real as $experiment)
	      {
		// Get CAPdata program id
		/* $CAPcode = $experiments[$myind]->bp; */
		$CAPcode = $bp;
		$sql = "SELECT CAPdata_programs_uid FROM CAPdata_programs
			WHERE data_program_code= '$bp'";
		$res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		if (1 == mysql_num_rows($res)) {
		    $row = mysql_fetch_assoc($res);
		    $capdata_uid = $row['CAPdata_programs_uid'];
		  }
		else die ("CAPcode not found for Breeding Program $bp.");
		
		// Get code for phenotype experiments
		$sql = "SELECT experiment_type_uid FROM experiment_types
							WHERE experiment_type_name = 'phenotype'";
		$res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		$row = mysql_fetch_assoc($res);
		$exptype_id = $row['experiment_type_uid'];

		// get experiment_set_uid
		if (preg_match("/[A-Za-z0-9]+/",$experiment->experimentset)) {
		  $sql = "SELECT experiment_set_uid FROM experiment_set WHERE experiment_set_name = '{$experiment->experimentset}'";
		  $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		  if (mysql_num_rows($res)==0) //no, experiment not found, so isert 
		    {
		      $sql = "insert into experiment_set set
                                        experiment_set_name = '{$experiment->experimentset}'";
		      //echo "SQL ".$sql."<br>\n";
		      mysql_query($sql) or die(mysql_error());
		      $sql = "select experiment_set_uid from experiment_set where experiment_set_name = '{$experiment->experimentset}'";
		      //echo "SQL ".$sql."<br>\n";
		      $res = mysql_query($sql) or die(mysql_error());
		      $row = mysql_fetch_assoc($res);
		      $experiment_set_uid = $row['experiment_set_uid'];
		      //print "experiment found $experiment_set_uid<br>\n";
		    } else {
		    $row = mysql_fetch_assoc($res);
		    $experiment_set_uid = $row['experiment_set_uid'];
		    //print "experiment found $experiment->experimentset<br>$sql<br>\n";
		  }  
		} else {
		  $experiment_set_uid = "NULL";
		}
		//print "$sql<br>\n";
	
		// Insert or update experiment table data
		// First check if this trial code is in the database, if yes, then update all fields;
		// if no then insert into table
		$sql = "SELECT experiment_uid FROM experiments WHERE trial_code = '{$experiment->trialcode}'";
		$res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		if (mysql_num_rows($res)!==0) { //yes, experiment found, so update
		    $row = mysql_fetch_assoc($res);
		    $exp_id = $row['experiment_uid'];
		
		    //update experiment information
		    $sql = "UPDATE experiments SET
			   experiment_type_uid = $exptype_id,
			   CAPdata_programs_uid = $capdata_uid,
			   experiment_set_uid = $experiment_set_uid,
			   experiment_short_name = '{$experiment->experimentset}',
			   experiment_desc_name = '{$experiment->trialdesc}',
			   trial_code = '{$experiment->trialcode}',
			   experiment_year = '{$experiment->year}',
			   data_public_flag = '$data_public_flag',
			   created_on = NOW()
			WHERE experiment_uid = $exp_id";
		    echo "Table <b>experiments</b> updated.<br>\n";
		    mysql_query($sql) or die(mysql_error() . "<br>$sql");

		    //update phenotype experiment information
		    if($experiment->beginweatherdate) 
		      /* $bwd = str_to_date('$experiment->beginweatherdate','%m/%d/%Y'); */
		      $bwd = "'".date_format(date_create_from_format('n/j/Y', $experiment->beginweatherdate), 'Y-m-d')."'";
		    else
		      $bwd = "NULL";
		    $sql = " UPDATE phenotype_experiment_info set
				collaborator = '{$experiment->collaborator}',
				planting_date = str_to_date('$experiment->plantingdate','%m/%d/%Y'),
				harvest_date = str_to_date('$experiment->harvestdate','%m/%d/%Y'),
				begin_weather_date = $bwd,
				seeding_rate = '{$experiment->seedingrate}',
				experiment_design = '{$experiment->experimentaldesign}',
				number_replications = '{$experiment->numberofreplications}',
				number_entries = '{$experiment->numberofentries}',
				plot_size = '{$experiment->plotsize}',
				harvest_area = '{$experiment->harvestedarea}',
				irrigation = '{$experiment->irrigation}',
				other_remarks = '{$experiment->otherremarks}',
				location = '{$experiment->location}',
				latitude = '{$experiment->latitude}',
				longitude = '{$experiment->longitude}',
				greenhouse_trial = '{$experiment->greenhouse}',
				updated_on = NOW()
			WHERE experiment_uid = $exp_id";
		    if (DEBUG>2) {echo "update phenotypeexp SQL ".$sql."\n";}
		    mysql_query($sql) or die("
<p><font color=red>MySQL error while updating <b>phenotype_experiment_info</b> table.</font>
<p><b>Message:</b>
<br>". mysql_error() . "
<p><b>Command:</b>
<br>$sql
<p><input type=\"Button\" value=\"Return\" onClick=\"history.go(-2); return;\">
");

		    echo "Table <b>phenotype_experiment_info</b> updated.<p>\n";
		} 
		else {
		  $sql = "
                    insert into experiments set experiment_type_uid = $exptype_id,
		    CAPdata_programs_uid = $capdata_uid, experiment_set_uid = $experiment_set_uid, 
		    experiment_short_name = '{$experiment->experimentset}', 
                    experiment_desc_name = '{$experiment->trialdesc}', 
		    trial_code = '{$experiment->trialcode}',
		    experiment_year = '{$experiment->year}', 
		    data_public_flag = '$data_public_flag', created_on = NOW()";
		  mysql_query($sql) or die(mysql_error() . "<br>Command:<pre>$sql");
		  echo "New entry added for $experiment->trialcode to <b>experiments</b> table.<br>\n";
					
		  //get experiment_uid set genotype experiments info table
		  $sql = "SELECT experiment_uid FROM experiments
									WHERE trial_code = '{$experiment->trialcode}' limit 1";
		  $res = mysql_query($sql) or die(mysql_error());
		  $row = mysql_fetch_assoc($res);
		  $exp_id = $row['experiment_uid'];
		  if (DEBUG>1) {echo "exp ID ".$exp_id."\n";}

		  if(empty($experiment->beginweatherdate)) {
		    $sql_optional = '';
		  } else {
		    $sql_optional = "begin_weather_date = str_to_date('$experiment->beginweatherdate','%m/%d/%Y'),";
		  }
		  $sql = "insert into phenotype_experiment_info set
			  experiment_uid = $exp_id,
			  collaborator = '{$experiment->collaborator}',
			  planting_date = str_to_date('$experiment->plantingdate','%m/%d/%Y'),
			  seeding_rate = '{$experiment->seedingrate}',
			  experiment_design = '{$experiment->experimentaldesign}',
			  number_replications = '{$experiment->numberofreplications}',
			  number_entries = '{$experiment->numberofentries}',
			  plot_size = '{$experiment->plotsize}',
			  harvest_area = '{$experiment->harvestedarea}',
			  harvest_date = str_to_date('$experiment->harvestdate','%m/%d/%Y'),
			  irrigation = '{$experiment->irrigation}',
			  other_remarks = '{$experiment->otherremarks}',
			  location = '{$experiment->location}',
			  latitude = '{$experiment->latitude}',
			  longitude = '{$experiment->longitude}',
			  greenhouse_trial = '{$experiment->greenhouse}',
			  $sql_optional
			  created_on = NOW()";
		  mysql_query($sql) or die("
		    <p><font color=red>MySQL error while inserting into <b>phenotype_experiment_info</b> table.</font>
		    <p><b>Message:</b>
		    <br>". mysql_error() . "
		    <p><b>Command:</b>
		    <br>$sql
		    <p><input type=\"Button\" value=\"Return\" onClick=\"history.go(-2); return;\">
		    ");
		  echo "New entry added for $experiment->trialcode to <b>phenotype_experiment_info</b> table.<br>\n";
		} 
	      }// end foreach

	    echo "Data inserted or updated successfully. ";
	    if ($experiment_set_uid)
	      echo "<p><a href=".$config['base_url']."view.php?table=experiment_set&uid=$experiment_set_uid>View</a><p>";
	    else {
	    ?>
	    <a href="<?php echo $config['base_url']; ?>curator_data/input_annotations_upload_excel.php"> Go Back To Main Page </a>
	    <?php
		}
	       // Timestamp, e.g. _28Jan12_23:01
	       $ts = date("_jMy_H:i");
	    $filename = $filename . $ts;
	    $devnull = mysql_query("INSERT INTO input_file_log (file_name,users_name) VALUES('$filename', '$username')") or die(mysql_error());

	  }

	  $footer_div = 1;
	  include($config['root_dir'].'theme/footer.php');
	
	
		
	} /* end of function type_database */


} /* end of class */


class experiment
	{
		var $year;
		var $bp;
		var $location;
		//var $latlong;
		var $collaborator;
		//var $collabcode;
		var $trialcode;
		var $plantingdate;
		var $seedingrate;
		var $experimentset;
		var $trialdesc;
		var $experimentaldesign;
		var $number_entries;
		var $numberofreplications;
		var $plotsize;
		var $harvestedarea;
		var $irrigation;
		var $harvestdate;
		var $otherremarks;
	}

?>

