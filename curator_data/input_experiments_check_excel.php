<?php 
/**
 * Phenotype Experiment Results
 * 
 * @category PHP
 * @package  T3
 * 
 */
// 02/01/2011 JLee  Fix indentations and fatal error not presenting data
// 02/01/2011 JLee  Fix problem with line with the value of 0
// 12/14/2010 JLee  Change to use curator bootstrap

require 'config.php';
include($config['root_dir'] . 'includes/bootstrap_curator.inc');
include($config['root_dir'] . 'curator_data/lineuid.php');
require_once("../lib/Excel/reader.php"); // Microsoft Excel library

connect();
loginTest();

$row = loadUser($_SESSION['username']);

ob_start();
authenticate_redirect(array(USER_TYPE_ADMINISTRATOR, USER_TYPE_CURATOR));
ob_end_flush();

/**
 * Returns $arg1 if it is set, else fatal error
 * @param unknown_type $arg1
 * @param unknown_type $msg
 * @return unknown
 */
function ForceValue(& $arg1, $msg) {
  if (isset($arg1))   
    return $arg1;
  die($msg);
}

$col_lookup = array('trialmean' => 'mean_value', 'std.error' => 'standard_error', 
		    'std.errordiff.' => 'std_err_diff', 'prob>f' => 'prob_gt_F', 
		    'coef.var.' => 'cv', 'replications' => 'number_replicates');

new LineNames_Check($_GET['function']);

/**
 * 
 * Phenotype Experiment Results
 *
 */
class LineNames_Check
{
  /**
   * Using the class's constructor to decide which action to perform
   * @param unknown_type $function
   */
  public function __construct($function = null) {	
    switch($function)
      {
      case 'typeDatabase':
	$this->type_Database(); /* update database */
	break;
      default:
	$this->typeExperimentCheck(); /* intial case*/
	break;
      }	
  }

/**
 * check experiment data before loading into database
 */
private function typeExperimentCheck()
	{
		global $config;
		include($config['root_dir'] . 'theme/admin_header.php');
		echo "<h2>Phenotype Data Validation</h2>"; 
		$this->type_Experiment_Name();
		$footer_div = 1;
        include($config['root_dir'].'theme/footer.php');
	}
	
/**
 * check experiment data before loading into database
 */	
 private function type_Experiment_Name() {
?>
   <script type="text/javascript">
     function update_database(filepath, filename, username, rawdatafile) {
     var url='<?php echo $_SERVER[PHP_SELF];?>?function=typeDatabase&expdata=' + filepath + '&file_name=' + filename + '&user_name=' + username + '&raw_data_file=' + rawdatafile;
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
<?php
         global $config;
	 $row = loadUser($_SESSION['username']);
	 //ini_set("memory_limit","24M");
	 $username=$row['name'];
	 $tmp_dir="uploads/tmpdir_".$username."_".rand();
	 $raw_path= "../raw/phenotype/".$_FILES['file']['name'][1];
	 if (!empty($_FILES['file']['name'][1]))
	   if (move_uploaded_file($_FILES['file']['tmp_name'][1], $raw_path) !== TRUE)
	     echo "<font color=red><b>Oops!</b></font> Your raw data file <b>"
	       .$_FILES['file']['name'][1]."</b> was not saved in directory ".$config['root_dir']."raw/ and
               will be lost.  Please <a href='".$config['base_url']."feedback.php'>contact the 
               programmers</a>.<p>";
	 umask(0);
	
	 if(!file_exists($tmp_dir) || !is_dir($tmp_dir)) {
	   mkdir($tmp_dir, 0777);
	 }
	 $target_path=$tmp_dir."/";
	 if ($_FILES['file']['name'][0] == ""){
	   error(1, "No File Uploaded");
	   print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
	 }
	 else {
	   $uploadfile=$_FILES['file']['name'][0];
	   $rawdatafile = $_FILES['file']['name'][1];
	   //	echo "uploaded file" .$uploadfile. "<br/>". "raw file" .$rawdatafile;
	   
	   $uftype=$_FILES['file']['type'][0];
	   if (strpos($uploadfile, ".xls") === FALSE) {
	     error(1, "Expecting an Excel file. <br> The type of the uploaded file is ".$uftype);
	     print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
	   }
	   else {
	     if (move_uploaded_file($_FILES['file']['tmp_name'][0], $target_path.$uploadfile)) {
	       $meansfile = $target_path.$uploadfile;
	       //echo $meansfile."\n";

	       /* Read the Means file */
	       $reader = & new Spreadsheet_Excel_Reader();
	       $reader->setOutputEncoding('CP1251');
	       if (strpos($meansfile,'.xls')>0)
		 $reader->read($meansfile);
	       else 
		 $reader->read($meansfile . ".xls");
	       $means = $reader->sheets[0];
	       $cols = $reader->sheets[0]['numCols'];
	       $rows = $reader->sheets[0]['numRows'];
	       //echo "nrows ".$rows." ncols ".$cols."\n";

	       // First get the one-time values in the header.
	       $crop = $means['cells'][2][2];
	       if ($means['cells'][3][1] == "*Breeding Program Code")
		 $breeding_program_name = $means['cells'][3][2];
	       else {
		 $actual = $means['cells'][3][1];
		 echo "<b>Error</b>: Row 3 of the spreadsheet must be \"*Breeding Program Code\"<br>";
		 echo "instead of \"$actual\".<p>";
		 exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	       }
	       if ($means['cells'][4][1] == "*Trial Code")
		 $trial_code = $means['cells'][4][2];
	       else {
		 echo "<b>Error</b>: Row 4 of the spreadsheet must be \"*Trial Code\".<p>";
		 exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	       }
	       /*
		* Figure out which experiment to use
		*/
	       $sql = "select experiment_uid as id from experiments where trial_code = '$trial_code'";
	       $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	       if (1 == mysql_num_rows($res)) {
		 $experiment = mysql_fetch_assoc($res);
		 $experiment_uid = $experiment['id'];
	       } elseif (0 == mysql_num_rows($res)) {
		 echo "<b>Error</b>: experiment ".$trial_code. " does not exist <br/><br/>";
		 exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	       } else {
		 echo "<b>Error</b>: experiment ".$trial_code." matches multiple experiments-must be unique <br/><br/>" ;
		 exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	       } // end if

    /**
    * Columns in the means file header row, row 5.
    *
    * Tells the script which column is which. (Starting at 1)
    * This implies that the standard form MUST be used for data entry
    **/
	       $COL_LINENAME = $COL_CHECK = $COL_GENERATION = $COL_SEEDYEAR = $COL_SEEDEXPT = $COL_SEED_ID = 0;
	
	       for ($i = 1; $i <= $cols; $i++) {
		 $teststr = str_replace(' ','',$means['cells'][5][$i]);
		 if (stripos($teststr,'Linename')!==FALSE)
		   $COL_LINENAME = $i;
		 elseif (stripos($teststr,'Check')!==FALSE)
		   $COL_CHECK = $i;
	       }
	       // Check if a required col is missing
	       if (($COL_LINENAME*$COL_CHECK)==0) {
		 echo "Missing column: Line Name and Check are required.<p>";
		 exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\"><br>");
	       }
	       $offset = $COL_LINENAME + 6;//column where phenotype data starts
	       $phenonames = array();
	       $phenoids = array();
   
	       for ($i = $offset; $i <= $cols; $i++) {
		 $teststr= addcslashes(trim($means['cells'][5][$i]),"\0..\37!@\177..\377");
		 if (empty($teststr)) break; 
		 else {
		   $teststr= str_replace('\\n',' ',$teststr);
		   $pheno_cur =trim($teststr);
		   $sql = "SELECT phenotype_uid as id,phenotypes_name as name, 
                               max_pheno_value as maxphen, min_pheno_value as minphen, datatype
		             FROM phenotypes
			     WHERE phenotypes_name = '$pheno_cur'";
		   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		   if ($row = mysql_fetch_assoc($res)) {
		     $datatypes[] = $row['datatype'];
		     $phenonames[] =  $row['name'];
		     $phenoids[] = $row['id'];//$phenotype_uid;
		     $pheno_max[] = $row['maxphen'];
		     $pheno_min[] = $row['minphen'];
		   } else {
		     $eflgs[] = $pheno_cur;
                   }
		 }
	       }
	       if (count($eflgs) > 0) {
		 foreach ($eflgs as $bad)
		   echo "Trait <font color = red>$bad</font> does not exist in the database.<br> ";
		 exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\"><br>");
	       }
	       $pheno_num = count($phenoids);
   
	       /*
		* Process the means file
		*/
	       $current = NULL;	// the current row
	       $num_exp = 0;
	       $experiment_uids[$num_exp] = -1;
	       $BeginLinesInput = FALSE;
	       for($i = 6; $i <= $rows; $i++) {
		 $current = $means['cells'][$i];
		 //check if line is empty, if yes then skip to the next line
		 if (!empty($current)) {
		   /* Deal with statistics */
		   // identify which statistic it is based on column 1
		   $statname = str_replace(array(" ", "*"),"",strtolower(trim($current[1])));
                   if (preg_match('/^trialinformationgoesabove/', $statname)) {
                     $BeginLinesInput = TRUE;
		     $i++;
		     $current = $means['cells'][$i];
		   } 
		   global $col_lookup;
		   $fieldname = $col_lookup[$statname];
		   if ($BeginLinesInput === FALSE) {
		     // Not yet down to the data for individual lines.
		     for ($j=0;$j<$pheno_num;$j++) {
		       $pheno_uid =$phenoids[$j];
		       $phenotype_data = trim($current[$offset+$j]);
		       // insert NULL value if empty
		       if (strlen($phenotype_data) == 0) {
			 $phenotype_data = "NULL";
		       } elseif ( (!is_numeric($phenotype_data)) AND ($fieldname != 'prob_gt_F') ) {
			 echo "<font color=red><b>Error:</b></font> Value is not numeric. <b>".$current[1]."</b> for 
                         <b>". $phenonames[$j]."</b> = '".$phenotype_data."'<br>";
			 $phenotype_data = "NULL";
		       }

		       /* /\* ?? Why are we doing this before we've clicked Accept?? *\/ */
		       /* if (!is_null($phenotype_data)) { */
		       /* 	 if ($phenotype_data != "NULL") { $phenotype_data = "'".$phenotype_data."'"; } */
		       /* 	 // check if there are existing statistics data for this experiment if yes then update */
		       /* 	 $sql = "SELECT phenotype_mean_data_uid FROM phenotype_mean_data */
                       /*          WHERE phenotype_uid = '$phenoids[$j]' */
                       /*          AND experiment_uid = '$experiment_uid'"; */
		       /* 	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		       /* 	 if ( mysql_num_rows($res)>0) { */
		       /* 	   $sql = "UPDATE phenotype_mean_data SET $fieldname = $phenotype_data, updated_on=NOW() */
                       /*              WHERE experiment_uid = '$experiment_uid' AND phenotype_uid = '$phenoids[$j]'"; */
		       /* 	 } else { */
		       /* 	   $sql = "INSERT INTO phenotype_mean_data SET $fieldname = '$phenotype_data', */
                       /*              experiment_uid = '$experiment_uid', phenotype_uid = '$phenoids[$j]', */
                       /*              updated_on=NOW(), created_on = NOW()"; */
		       /* 	 } */
		       /* 	 //$res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		       /* } */

		     } // end of for($j)
		   } // end of if ($BeginLinesInput === FALSE), finished collecting trial statistics
		   else {
		     // Get required columns
		     $line_name = ForceValue($current[$COL_LINENAME], "<b>Error</b>: missing Line Name at row " . $i);
		     $check = ForceValue($current[$COL_CHECK], "<b>Error</b>: missing Check value at row " . $i);
		     $sql = "select line_record_uid as id from line_records where line_record_name  = '$line_name'";
		     $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		     if (mysql_numrows($res) > 0) {
		       $line = mysql_fetch_assoc($res);
		       $line_uid = $line['id'];
		     } else {
		       /* Translate synonyms */
		       $sql = "select line_record_uid as id from line_synonyms where line_synonym_name  = '$line_name'";
		       $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		       if (mysql_numrows($res) > 0) {
			 $realname = mysql_fetch_assoc($res);
			 $line_uid = $realname['id'];
		       } else {
			 echo "Line name/synonym not found for line '". $line_name."' at row " . $i ."<br/><br/>";
			 exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
		       }
		     }
		     //Store experiment_uids for this file
		     if (!in_array($experiment_uid,$experiment_uids)) {
		       $experiment_uids[$num_exp]=$experiment_uid;
		       $num_exp++;

		    /*    // Don't do this before we've clicked Accept. : */
		    /*    // remove checkline data for the phenotypes in this experiment from phenotype_data */
		    /*    // table, this will help deal with multiple copies of a check_line. */
		    /*    // get tht-base_uids for checklines */
		    /*    // Only do this the first time through for an experiment */
		    /*    $pheno_uids = implode(",",$phenoids); */
			
		    /*    $sql = "SELECT tht_base_uid */
                    /* FROM tht_base */
                    /* WHERE check_line='yes' AND experiment_uid='$experiment_uid'"; */
                           
		    /*    $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		    /*    if (mysql_num_rows($res)>0) { */
		    /* 	 while ($row = mysql_fetch_array($res)){ */
		    /* 	   $tht_base_uids[]=$row['tht_base_uid']; */
		    /* 	 } */
		    /* 	 $tht_base_uids = implode(',',$tht_base_uids); */
				
		    /* 	 $sql = "DELETE FROM phenotype_data */
		    /* 				WHERE tht_base_uid in ($tht_base_uids)AND phenotype_uid IN ($pheno_uids)"; */

		    /* 	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		    /* 	 unset($tht_base_uids); */
		    /*    } */

		     }
		     /*
		      * Figure out which line to use
		      */
		     if ($check !=2) {
		       $line_record_uid =	get_lineuid($line_name);
		       if (count($line_record_uid)>1) {
			 exit("more than one line record id for {$line_name}");
		       } elseif ($line_record_uid===FALSE){
			 exit("line {$line_name} not found in table, stop");
		       }
		       $line_record_uid=$line_record_uid[0];
		       if (DEBUG>1) {
			 echo "exp uid ".$experiment_uid." line uid ".$line_record_uid."\n";
		       }
		     }

		     /*
		      * Figure out which dataset to use if this is not a checkline
		      */
		     if ($check == 0) {
		       $sql = "SELECT CAPdata_programs_uid as id
                    FROM CAPdata_programs
                    WHERE data_program_code  = '$breeding_program_name'";
		       $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		       if (1 == mysql_num_rows($res)) {
			 $row = mysql_fetch_assoc($res);
			 $BPcode_uid = $row['id'];
		       } else {
			 echo "<b>Error</b>: CAPbreeding program  does not exist at row " . $i . "<br/><br/>";
			 exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
		       }
		       $sql = "SELECT de.datasets_experiments_uid as id
                     FROM datasets_experiments AS de, datasets AS ds, CAPdata_programs AS cd
                     WHERE
                        de.datasets_uid = ds.datasets_uid
                        AND ds.CAPdata_programs_uid ='$BPcode_uid'
                        AND experiment_uid = '$experiment_uid' limit 1";
		       $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		       if (1 == mysql_num_rows($res)) {
			 $row = mysql_fetch_assoc($res);
			 $de_uid = $row['id'];
		       } 

		       /* Don't do this till we've clicked Accept. */
		       /* else { */
		       /* 	 // set new dataset experiment code */
		       /* 	 // Dataset name is data program name plus year.  Get year from  */
		       /* 	 // previously loaded experiment annotation. */
		       /* 	 $year = mysql_grab("select experiment_year from experiments where trial_code = '$trial_code'"); */
		       /* 	 $ds_name = $breeding_program_name . substr($year, -2); */
		       /* 	 // Get datasets_uid. */
		       /* 	 $sql = "SELECT datasets_uid as id */
                       /*  FROM  datasets */
                       /*  WHERE dataset_name ='$ds_name'"; */
		       /* 	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		       /* 	 if (mysql_num_rows($res)<1) {  */
		       /* 	   // add in dataset */
		       /* 	   $row = mysql_fetch_assoc($res); */
		       /* 	   $ds_name = $breeding_program_name.substr($CAPyear,-2); */
		       /* 	   echo "year = $year, dsname = $ds_name<br>"; */
		       /* 	   $sql = "INSERT INTO datasets SET CAPdata_programs_uid='$BPcode_uid', */
                       /*     breeding_year = '$year', dataset_name = '$ds_name', updated_on=NOW(), */
                       /*     created_on = NOW()"; */
		       /* 	   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		       /* 	   $ds_uid = mysql_insert_id(); */
		       /* 	   $sql = "INSERT INTO datasets_experiments SET experiment_uid='$experiment_uid', */
                       /*     datasets_uid = '$ds_uid', updated_on=NOW(), */
                       /*     created_on = NOW()"; */
		       /* 	   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		       /* 	   $de_uid = mysql_insert_id(); */
		       /* 	 } elseif (1 == mysql_num_rows($res)) { */
		       /* 	   $row = mysql_fetch_assoc($res); */
		       /* 	   $ds_uid = $row['id']; */
		       /* 	   $sql = "INSERT INTO datasets_experiments SET experiment_uid='$experiment_uid', */
                       /*     datasets_uid = '$ds_uid', updated_on=NOW(), */
                       /*     created_on = NOW()"; */
		       /* 	   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		       /* 	   $de_uid = mysql_insert_id(); */
		       /* 	 } else { */
		       /* 	   $nr  = mysql_num_rows($res); */
		       /* 	   echo "numrows = $nr<p>"; */
		       /* 	   die ("<b>Error</b>: problem with dataset \"".$ds_name."\""); */
		       /* 	 } */
		       /* } */

		       if (DEBUG>1) {
			 echo "ds uid ".$ds_uid." de uid ".$de_uid."\n";
		       }
		     } // end if for datalines

		     /*
		      * Insert line into tht-base if check is 0 or 1
		      */
		     if ($check < 2) {
		       $check_val ='no';
		       if ($check == 1) {
		     	 $check_val ='yes';
		       }
		       // check if tht_base_uid already exists for this line, check condition, and experiment
		       $sql = "SELECT tht_base_uid FROM tht_base
                        WHERE line_record_uid='$line_record_uid' AND experiment_uid='$experiment_uid'
		     				AND check_line ='$check_val' limit 1";
		       $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");

		       /* Don't do this till we've clicked Accept. */
		       /* if (mysql_num_rows($res)==1) { */
		       /* 	 $row = mysql_fetch_assoc($res); */
		       /* 	 $tht_base_uid = $row['tht_base_uid']; */
		       /* 	 $sql = "UPDATE tht_base */
                       /*  SET line_record_uid = '$line_record_uid', */
                       /*  experiment_uid = '$experiment_uid',"; */
		       /* 	 if ($check ==1){ */
		       /* 	   $sql .= "check_line='yes', datasets_experiments_uid=NULL, */
                       /*  trial_code_number = NULL,"; */
		       /* 	 } else { */
		       /* 	   $sql .= "datasets_experiments_uid='$de_uid', */
                       /*  trial_code_number = '$trial_entry_no',"; */
		       /* 	 } */
		       /* 	 $sql .= "updated_on=NOW() */
                       /*  WHERE tht_base_uid = '$tht_base_uid'"; */
		       /* 	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		       /* } else { */
		       /* 	 $sql = "INSERT INTO tht_base */
                       /*  SET line_record_uid = '$line_record_uid', */
                       /*  experiment_uid = '$experiment_uid',"; */
		       /* 	 if ($check ==1) { */
		       /* 	   $sql .= "check_line='yes', datasets_experiments_uid=NULL, */
                       /*  trial_code_number = NULL,"; */
		       /* 	 } else { */
		       /* 	   $sql .= "datasets_experiments_uid='$de_uid', */
                       /*      trial_code_number = '$trial_entry_no',"; */
		       /* 	 } */
		       /* 	 $sql .= " updated_on=NOW(),created_on = NOW()"; */
		       /* 	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
		       /* 	 $tht_base_uid = mysql_insert_id(); */
		       /* 	 if (DEBUG>2) { */
		       /* 	   echo "thtbase uid ".$tht_base_uid." line uid ".$line_record_uid."\n"; */
		       /* 	 } */
		       /* } */

		       /*
			* Enter phenotype values into the database for this particular line in this
			* particular experiment. First check if data just needs to be updated, if no, then insert in
			* new data.
			*/
		       // Get phenotypedata columns
		       if (($check ==0) || ($check ==1)) {
			 for ($j=0;$j<$pheno_num;$j++) {
			   $pheno_uid =$phenoids[$j];
			   // Remove some invisible characters, esp. " ", chr(32).
			   $phenotype_data = trim($current[$offset+$j]);
			   //put in check for SAS value for NULL
			   // Apparently PHP's trim(chr(32)) == chr(0), not NULL.  Damn.
			   /* if ((!is_null($phenotype_data))&&($phenotype_data!=".")) { */
			   /* if ((!is_null($phenotype_data))&&($phenotype_data!=".")&&(ord($phenotype_data)!=0)) { */
			   if ((!is_null($phenotype_data)) && ($phenotype_data!=".") && ($phenotype_data!="")) {
			     // Check that the value is numeric if the schema says it must be.
			     $dt = $datatypes[$j];
			     if ( (!is_numeric($phenotype_data)) AND ($dt != "string") AND ($dt != "text") ) {
			       echo "<font color=red><b>Error:</b></font> Data not numeric. 
                                     <b>".$line_name."</b>: ".$phenonames[$j]." = ".$phenotype_data."<br>";
			     } 
			     //CHeck if phenotype data is within the specified range given in the database.
			     // fix occasional excel problem with zeros coming up as very small negative numbers (E-12-E-15)
			     if (abs($phenotype_data) < .00001){
			       $phenotype_data = 0;
			     }
			     if (($pheno_min[$j] !=$pheno_max[$j]) && (($phenotype_data<$pheno_min[$j]) ||($phenotype_data>$pheno_max[$j]))) {
								
			       echo "<font color=red><b>Error:</b></font> Out of bounds line,trait,value: ".$line_name.",".$phenonames[$j].",".$phenotype_data."<br>";

			     } 

			     /* Don't do this until we've clicked Accept. */
			     /* elseif ($check == 0){ */
			     /*   // check if there is existing data for this experiment if yes then update */
			     /*   $sql = "SELECT phenotype_data_uid FROM phenotype_data */
			     /* 							WHERE phenotype_uid = '$phenoids[$j]' */
			     /* 							AND tht_base_uid = '$tht_base_uid'"; */
			     /*   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
			     /*   if ( mysql_num_rows($res) > 0) { */
			     /* 	 $sql = "UPDATE phenotype_data SET value = '$phenotype_data', updated_on=NOW() */
			     /* 							WHERE tht_base_uid = '$tht_base_uid' AND phenotype_uid = '$phenoids[$j]'"; */
			     /*   } else { */
			     /* 	 $sql = "INSERT INTO phenotype_data SET phenotype_uid = '$phenoids[$j]', */
			     /* 							 tht_base_uid = '$tht_base_uid', value = '$phenotype_data', */
			     /* 							 updated_on=NOW(), created_on = NOW()"; */
			     /*   } */
			     /*   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
								
			     /* } elseif ($check == 1) { */
			     /*   //Insert only as all checklines were deleted at the beginning. The problem */
			     /*   //occurs when an experiment has multiple values for the same checklines (e.g., MN data) */
			     /*   if (DEBUG>2) {echo "checkline data ".$phenotype_data."\n";} */
			     /*   if (!is_null($phenotype_data)) { */
			     /* 	 $sql = "insert into phenotype_data set phenotype_uid = '$phenoids[$j]', */
			     /* 						   tht_base_uid = '$tht_base_uid', value = '$phenotype_data', */
			     /* 						   updated_on=NOW(), created_on = NOW()"; */
			     /* 	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql"); */
			     /*   } */
			     /* } */

			   }
			 }
		       }
		     }
		   } 
		 } // end skipping a line
	       } // end for loop through file
   
	       ?>
   
	       <style type="text/css">
		  th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
	       table {background: none; border-collapse: collapse}
	       td {border: 1px solid #eee !important;}
	       h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
	       </style>

		   <h3>We are reading the following data from the uploaded data file.</h3>
		
		   <table>
		   <thead>
		   <tr> 
		   <?php
		   for ($i = 1; $i <= $cols; $i++) {
		     /* $teststr = str_replace(' ','',$means['cells'][5][$i]); */
		     /* $newtext = wordwrap($teststr, 7, "\n", true); */
		     /* echo "<th>$newtext</th>"; */
		     echo "<th>".$means['cells'][5][$i]."</th>";
		   }
	       ?>
	       </tr>
		   </thead>
		   <tbody style="padding: 0; width: 700px;  overflow: scroll;border: 1px solid #5b53a6;">
		   <?php
		   /* printing the values onto the page for user*/
		   for ($i = 6; $i <= $rows; $i++) {
		     echo "<tr>";
		     $current_row = $means['cells'][$i];
		     for ($j=1; $j<=$cols; $j++) {
		       echo "<td>";
		       /* $newtext = wordwrap($current_row[$j], 7, "\n", true); */
		       /* echo  $newtext; */
		       echo $current_row[$j];
		       echo "</td>";
		     }
		     echo "</tr>";
		   } 
	       ?>
	       </tbody>
		   </table>
			
		   <input type="Button" value="Accept" onclick="javascript: update_database('<?php echo $meansfile?>','<?php echo $uploadfile?>','<?php echo $username?>','<?php echo $rawdatafile ?>' )"/>
		   <input type="Button" value="Cancel" onclick="history.go(-1);" />

		   <?php
		   }
	     else {
	       error(1,"There was an error uploading the file.");
	       print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
	     }
	   }
	 }
 } /* end of type_Experiment_Name function*/

 /**
  * after accepting data load into database
  */
 private function type_Database() {
	
   global $config;
   include($config['root_dir'] . 'theme/admin_header.php');
	
   //connect_dev();	/* connecting to development database */
	
   $meansfile = $_GET['expdata'];
   $filename = $_GET['file_name'];
   $username = $_GET['user_name'];
   $rawdatafile = $_GET['raw_data_file'];
	
   $reader = & new Spreadsheet_Excel_Reader();
   $reader->setOutputEncoding('CP1251');
   if (strpos($meansfile,'.xls')>0) 	{
     $reader->read($meansfile);
   }else {
     $reader->read($meansfile . ".xls");
   }
   $means = $reader->sheets[0];
   $cols = $reader->sheets[0]['numCols'];
   $rows = $reader->sheets[0]['numRows'];

   /*	
    * Read the header info.	       
    */
   $crop = $means['cells'][2][2];
   $breeding_program_name = $means['cells'][3][2];
   $trial_code = $means['cells'][4][2];
   $experiment_uid = mysql_grab("select experiment_uid as id from experiments where trial_code = '$trial_code'");
	
   /**
    * Columns
    *
    * Tells the script which column is which. (Starting at 1)
    * This implies that the standard form MUST be used for data entry
    * 
    */
   $COL_LINENAME = $COL_CHECK = $COL_FILGEN = $COL_SSYEAR = $COL_SSEXPT = $COL_SSID = 0;
   for ($i = 1; $i <= $cols; $i++) {
     $teststr = str_replace(array(' ','*'), '', strtolower($means['cells'][5][$i]));
     if (stripos($teststr,'linename') !== FALSE) $COL_LINENAME = $i;
     elseif (stripos($teststr,'check') !== FALSE) $COL_CHECK = $i;
     // To be filled in ...:
     elseif (stripos($teststr,'xxx') !== FALSE) $COL_ = $i;
   }
   // Check if a required col is missing
   if (($COL_LINENAME*$COL_CHECK) == 0) {
     echo "Missing column: Line Name and Check are required.<p>";
     exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
   }
   $offset = $COL_LINENAME + 6;//column where phenotype data starts
   $phenonames = array();
   $phenoids = array();
   for ($i = $offset; $i <= $cols; $i++) {
     $teststr= addcslashes(trim($means['cells'][5][$i]),"\0..\37!@\177..\377");
     if (strlen($teststr) == 0) break;
     else {
       $teststr= str_replace('\\n',' ',$teststr);
       
	 $pheno_cur =trim($teststr);
	 $sql = "SELECT phenotype_uid as id,phenotypes_name as name, max_pheno_value as maxphen, min_pheno_value as minphen, datatype
					FROM phenotypes
					WHERE phenotypes_name = '$pheno_cur'";
	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	 if ($row = mysql_fetch_assoc($res)) {
	   $datatypes[] = $row['datatype'];
	   $phenonames[] =  $row['name'];
	   $phenoids[] = $row['id'];//$phenotype_uid;
	   $pheno_max[] = $row['maxphen'];
	   $pheno_min[] = $row['minphen'];
	   $eflg = 1;
	 } else {
           echo "Trait \"".$pheno_cur."\" does not exist in the database.<p> ";
           exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
         }
     }
   }
   $pheno_num = count($phenoids);
   
   /*
    * Process the means file
    */
   $current = NULL;	// the current row
   $num_exp = 0;
   $experiment_uids[$num_exp] = -1;
   $BeginLinesInput = FALSE;   
   for($i = 6; $i <= $rows; $i++)    {
     $current = $means['cells'][$i];
     //check if line is empty, if yes then skip to the next line
     if (!empty($current)) {
       /* Deal with statistics */
       // identify which statistic it is based on column 1
       $statname = str_replace(array(" ", "*"),"",strtolower(trim($current[1])));
       if (preg_match('/^trialinformationgoesabove/', $statname)) {
	 $BeginLinesInput = TRUE;
	 $i++;
	 $current = $means['cells'][$i];
       } 
       global $col_lookup;
       $fieldname = $col_lookup[$statname];
       if ($BeginLinesInput === FALSE) {
	 // Not yet down to the data for individual lines.
	 for ($j=0;$j<$pheno_num;$j++) {
	   $pheno_uid =$phenoids[$j];
	   $phenotype_data = trim($current[$offset+$j]);
	   // insert NULL value if empty
	   if (strlen($phenotype_data) == 0) {
	     $phenotype_data = "NULL";
	   } elseif ( (!is_numeric($phenotype_data)) AND ($fieldname != 'prob_gt_F') ) {
	     echo "<font color=red><b>Error:</b></font> Value is not numeric. <b>".$current[1]."</b> for 
                         <b>". $phenonames[$j]."</b> = '".$phenotype_data."'<br>";
	     $phenotype_data = "NULL";
	   }
	   if (!is_null($phenotype_data)) {
	     if ($phenotype_data != "NULL") {
	       $phenotype_data = "'".$phenotype_data."'";
	     }
	     // check if there are existing statistics data for this experiment if yes then update
	     $sql = "SELECT phenotype_mean_data_uid FROM phenotype_mean_data
                                WHERE phenotype_uid = '$phenoids[$j]'
                                AND experiment_uid = '$experiment_uid'";
	     $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	     if ( mysql_num_rows($res)>0) {
	       $sql = "UPDATE phenotype_mean_data SET $fieldname = $phenotype_data, updated_on=NOW()
                                    WHERE experiment_uid = '$experiment_uid' AND phenotype_uid = '$phenoids[$j]'";
	     } else {
	       $sql = "INSERT INTO phenotype_mean_data SET $fieldname = $phenotype_data,
                                    experiment_uid = '$experiment_uid', phenotype_uid = '$phenoids[$j]',
                                    updated_on=NOW(), created_on = NOW()";
	     }
	     $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	   }
	 } 
       } // end of if ($BeginLinesInput === FALSE), finished collecting trial statistics
       else {
       // Get required columns
       $line_name = ForceValue($current[$COL_LINENAME], "<b>Error</b>: Missing line name at row " . $i);
       $check =	ForceValue($current[$COL_CHECK], "<b>Error</b>: Missing checkvalue at row " . $i);

       //Store experiment_uids for this file
       if (!in_array($experiment_uid,$experiment_uids)) {
	 $experiment_uids[$num_exp]=$experiment_uid;
	 $num_exp++;
	 // remove  checkline data for the phenotypes in this experiment from phenotype_data table, this will help deal with multiple
	 // copies of a check_line
	 // get tht-base_uids for checklines
	 // Only do this the first time through for an experiment
	 $pheno_uids = implode(",",$phenoids);
			
	 $sql = "SELECT tht_base_uid
                    FROM tht_base
                    WHERE check_line='yes' AND experiment_uid='$experiment_uid'";
                           
	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	 if (mysql_num_rows($res)>0) {
	   while ($row = mysql_fetch_array($res)){
	     $tht_base_uids[]=$row['tht_base_uid'];
	   }
	   $tht_base_uids = implode(',',$tht_base_uids);
	   $sql = "DELETE FROM phenotype_data
						WHERE tht_base_uid in ($tht_base_uids)AND phenotype_uid IN ($pheno_uids)";
	   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	   unset($tht_base_uids);
	 }
       }
       /*
	* Figure out which line to use
	*/
       if ($check !=2) {
	 $line_record_uid = get_lineuid($line_name);
	 if (count($line_record_uid)>1) {
	   exit("more than one line record id for {$line_name}");
	 } elseif ($line_record_uid===FALSE){
	   exit("line {$line_name} not found in table, stop");
	 }
	 $line_record_uid=$line_record_uid[0];
	 if (DEBUG>1) {
	   echo "exp uid ".$experiment_uid." line uid ".$line_record_uid."\n";
	 }
       }
       /*
	* Figure out which dataset to use if this is not a checkline
	*/
       if ($check == 0) {
	 $sql = "SELECT CAPdata_programs_uid as id
                     FROM CAPdata_programs
                     WHERE data_program_code  = '$breeding_program_name'";
	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	 if (1 == mysql_num_rows($res)) {
	   $row = mysql_fetch_assoc($res);
	   $BPcode_uid = $row['id'];
	 } else {
	   echo "Fatal error: Breeding program \"".$breeding_program_name."\" does not exist in the database.<p>";
	   exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	 }
	 $sql = "SELECT de.datasets_experiments_uid as id
                     FROM datasets_experiments AS de, datasets AS ds, CAPdata_programs AS cd
                     WHERE
                        de.datasets_uid = ds.datasets_uid
                    AND ds.CAPdata_programs_uid ='$BPcode_uid'
                    AND experiment_uid = '$experiment_uid' limit 1";
	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	 if (1 == mysql_num_rows($res)) {
	   $row = mysql_fetch_assoc($res);
	   $de_uid = $row['id'];
	 } else {
	   // set new dataset experiment code
	   // Dataset name is data program name plus year.  Get year from 
	   // previously loaded experiment annotation.
	   $year = mysql_grab("select experiment_year from experiments where trial_code = '$trial_code'");
	   $ds_name = $breeding_program_name . substr($year, -2);
	   // Get datasets_uid.
	   $sql = "SELECT datasets_uid as id
                        FROM  datasets
                        WHERE dataset_name ='$ds_name'";
	   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	   if (mysql_num_rows($res) < 1) { 
	     // Add in dataset.
	     $row = mysql_fetch_assoc($res);
	     $sql = "INSERT INTO datasets SET CAPdata_programs_uid='$BPcode_uid',
                           breeding_year = '$year', dataset_name = '$ds_name', updated_on=NOW(),
                           created_on = NOW()";
	     $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	     $ds_uid = mysql_insert_id();
	     $sql = "INSERT INTO datasets_experiments SET experiment_uid='$experiment_uid',
                           datasets_uid = '$ds_uid', updated_on=NOW(),
                           created_on = NOW()";
	     $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	     $de_uid = mysql_insert_id();
	   } elseif (1 == mysql_num_rows($res)) {
	     $row = mysql_fetch_assoc($res);
	     $ds_uid = $row['id'];
	     $sql = "INSERT INTO datasets_experiments SET experiment_uid='$experiment_uid',
                           datasets_uid = '$ds_uid', updated_on=NOW(),
                           created_on = NOW()";
	     $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	     $de_uid = mysql_insert_id();
	   } else {
	     $nr  = mysql_num_rows($res);
	     echo "numrows = $nr<p>";
	     die ("<b>Error</b>: problem with dataset \"".$ds_name."\"");
	     /* die ("<b>Error</b>: dataset ".$ds_uid." does not exist at row " . $i); */
	   }
	 }
	 if (DEBUG>1) {
	   echo "ds uid ".$ds_uid." de uid ".$de_uid."\n";
	 }
       } // end if for datalines
       /*
	* Insert line into tht-base if check is 0 or 1
	*/
       if ($check < 2)  {
	 $check_val ='no';
	 if ($check == 1) {
	   $check_val ='yes';
	 }
	 // check if tht_base_uid already exists for this line, check condition, and experiment
	 $sql = "SELECT tht_base_uid FROM tht_base
                        WHERE line_record_uid='$line_record_uid' AND experiment_uid='$experiment_uid'
						AND check_line ='$check_val' limit 1";
                           
	 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	 if (mysql_num_rows($res)==1) {
	   $row = mysql_fetch_assoc($res);
	   $tht_base_uid = $row['tht_base_uid'];
	   $sql = "UPDATE tht_base
                        SET line_record_uid = '$line_record_uid',
                        experiment_uid = '$experiment_uid',";
	   if ($check ==1) {
	     $sql .= "check_line='yes', datasets_experiments_uid=NULL,
                            trial_code_number = NULL,";
	   } else {
	     $sql .= "datasets_experiments_uid='$de_uid',
                            trial_code_number = '$trial_entry_no',";
	   }
	   $sql .= "updated_on=NOW()
                        WHERE tht_base_uid = '$tht_base_uid'";
	   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	 } else {
	   $sql = "INSERT INTO tht_base
                        SET line_record_uid = '$line_record_uid',
                        experiment_uid = '$experiment_uid',";
	   if ($check ==1) {
	     $sql .= "check_line='yes', datasets_experiments_uid=NULL,
                            trial_code_number = NULL,";
	   } else {
	     $sql .= "datasets_experiments_uid='$de_uid',
                            trial_code_number = '$trial_entry_no',";
	   }
	   $sql .= " updated_on=NOW(),created_on = NOW()";
	   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	   $tht_base_uid = mysql_insert_id();
	   if (DEBUG>2) {
	     echo "thtbase uid ".$tht_base_uid." line uid ".$line_record_uid."\n";
	   }
	 }   
	 /*
	  * Enter phenotype values into the database for this particular line in this
	  * particular experiment. First check if data just needs to be updated, if no, then insert in
	  * new data.
	  */
	 // Get phenotypedata columns
	 if (($check ==0)||($check ==1)) {
	   for ($j=0;$j<$pheno_num;$j++) {
	     $pheno_uid =$phenoids[$j];
	     $phenotype_data = $current[$offset+$j];
	     if (DEBUG>2) {echo $phenotype_data."\n";}
	     //Put in check for SAS value for NULL, ".".
	     if ((!is_null($phenotype_data))&&($phenotype_data!=".")) {
	       $dt = $datatypes[$j];
	       if ( ($dt != "string") AND ($dt != "text") ) {
		 // Fix occasional excel problem with zeros coming up as very small negative numbers (E-12-E-15).
		 if (abs($phenotype_data) < .00001)
		   $phenotype_data = '0';
		 //Check if phenotype data is within the specified range given in the database.
		 if (($pheno_min[$j]!=$pheno_max[$j])&&(($phenotype_data<$pheno_min[$j])||($phenotype_data>$pheno_max[$j]))){
		   echo "<font color=red><b>Error:</b></font> Out of bounds 
                     line,trait,value: ".$line_name.",".$phenonames[$j].",".$phenotype_data."<p>";
		   exit("<input type=\"Button\" value=\"Return\" onClick=\"history.go(-2); return;\"><p>");
		 } 
	       }
	       if ($check == 0) {
		 // check if there is existing data for this experiment if yes then update
		 $sql = "SELECT phenotype_data_uid FROM phenotype_data
			WHERE phenotype_uid = '$phenoids[$j]'
			AND tht_base_uid = '$tht_base_uid'";
		 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		 if ( mysql_num_rows($res)>0) {
		   if ($phenotype_data != "NULL") {
		     $phenotype_data = "'".$phenotype_data."'";
		   }
		   $sql = "UPDATE phenotype_data SET value = $phenotype_data, updated_on=NOW()
		       WHERE tht_base_uid = '$tht_base_uid' AND phenotype_uid = '$phenoids[$j]'";
		 } else {
		   $sql = "INSERT INTO phenotype_data SET phenotype_uid = '$phenoids[$j]',
                                       tht_base_uid = '$tht_base_uid', value = '$phenotype_data',
                                       updated_on=NOW(), created_on = NOW()";
		 }
		 $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
	       } elseif ($check == 1) {
		 //Insert only as all checklines were deleted at the beginning. The problem
		 //occurs when an experiment has multiple values for the same checklines (e.g., MN data)
		 if (DEBUG>2) {echo "checkline data ".$phenotype_data."\n";}
		 if (!is_null($phenotype_data)) {
		   $sql = "insert into phenotype_data set phenotype_uid = '$phenoids[$j]',
                             tht_base_uid = '$tht_base_uid', value = '$phenotype_data',
                             updated_on=NOW(), created_on = NOW()";
		   $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		 }
	       }
	     }
	   }
	 }
       }
       }

     } // end skipping a line
   } // end for loop through file
   // Update trait statistics
   $trait_stats = calcPhenoStats_mysql ($phenoids);
   if ($trait_stats === FALSE) die ("Calculating stats on non-numeric data.");
   if (count($trait_stats) == 0) die ("Calculating stats on non-numeric data.");
   
   for ($i = 0;$i<count($phenoids);$i++){
     //check if record there
     $max_val= $trait_stats[$i][max_val];
     $min_val= $trait_stats[$i][min_val];
     $mean_val= $trait_stats[$i][mean_val];
     $std_val= $trait_stats[$i][std_val];
     $sample_size= $trait_stats[$i][sample_size];
     $pheno_uid = $trait_stats[$i][phenotype_uid];
		
     $sql = "SELECT * FROM phenotype_descstat WHERE phenotype_uid = $pheno_uid";
     $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
     if (mysql_num_rows($res)>0) {
       $sql = "UPDATE phenotype_descstat SET mean_val = $mean_val,
						max_val = '$max_val', min_val = '$min_val',
						std_val = $std_val, sample_size = $sample_size,updated_on=NOW()
                        WHERE phenotype_uid = '$pheno_uid'";
     } else {
       $sql = "INSERT INTO phenotype_descstat SET mean_val = $mean_val,
						max_val = $max_val, min_val = $min_val,
						std_val = $std_val, sample_size = $sample_size,
						phenotype_uid = $pheno_uid, updated_on=NOW(), created_on = NOW()
						";
     }
     //echo $sql."\n";
     $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
   }
    
   // Go through experiments in this file to update string of measured traits in
   // the experiment and file name
   for ($i=0;$i<$num_exp;$i++){
     unset($phenotypes);
     $sql = "SELECT p.phenotype_uid AS id, p.phenotypes_name AS name
					FROM phenotypes AS p, tht_base AS t, phenotype_data AS pd
					WHERE pd.tht_base_uid = t.tht_base_uid
					AND p.phenotype_uid = pd.phenotype_uid
					AND t.experiment_uid=$experiment_uids[$i]
					GROUP BY p.phenotype_uid";
		
            $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
            while ($row = mysql_fetch_array($res)){
				$phenotypes[]=$row['name'];
            }
            $countfound = count($phenotypes);
            if ($countfound > 0) {
              $phenotypes = implode(',',$phenotypes);
              $sql = "UPDATE experiments SET traits =('$phenotypes') WHERE experiment_uid=$experiment_uids[$i]";
              $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
            } else {
              echo "error - experiments not found<br>$sql<br>\n";
            }
		
            // Add meansfile name to the field for meansfile name, append to existing list if different
            $sql = "SELECT input_data_file_name
                    FROM experiments 
					WHERE experiment_uid = '$experiment_uids[$i]'";
            $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
            $row = mysql_fetch_assoc($res);
            $meansfile = basename($meansfile);
            if ($row["input_data_file_name"]===NULL) {
                $infile = $meansfile;
            } else {
                $infile = $row["input_data_file_name"];
            }
            if (stripos($infile,$meansfile)===FALSE) {
				$infile .= ", ".$meansfile;
            }
			$sql = "UPDATE experiments SET input_data_file_name = '$infile', updated_on=NOW()
                  WHERE experiment_uid = '$experiment_uids[$i]'";
			$res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
		
			// Add rawdata name to the field for raw_data_file_name , append to existing list if different
	
            /* this part id not necessary as we want to replace the raw data file name and append to the existing raw data file name */		
            /* $sql = "SELECT raw_data_file_name
					FROM experiments 
					WHERE experiment_uid = '$experiment_uids[$i]'";
            $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
            $row = mysql_fetch_assoc($res);
            //$meansfile = basename($meansfile);
			$infile_raw = $rawdatafile;
	
            if ($row["raw_data_file_name"]===NULL) {
                $infile_raw = $rawdatafile;
            } else {
                $infile_raw = $row["raw_data_file_name"];
            }
            if (stripos($infile_raw,$rawdatafile)===FALSE) {
                $infile_raw .= ", ".$rawdatafile;
            }   
		
            /* this part id not necessary as we want to replace the raw data file name and append to the existing raw data file name */	
            /*  if ($rawdatafile) {
                $sql = "UPDATE experiments SET raw_data_file_name = '$infile_raw', updated_on=NOW()
                    WHERE experiment_uid = '$experiment_uids[$i]'";
                $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
			}
            */
        }
    //calling this function to calculate the statistics for phenotype data.
    // echo"statistics function call";
        calcPhenoStats_mysql($empty);
	
    // testing recent data
        $sql = "select input_data_file_name  from experiments where experiment_uid = '10'";
        $res = mysql_query($sql) or die(mysql_error() . "<br>$sql");
        //$row = mysql_fetch_array($res);
        while ($row = mysql_fetch_array($res)){
			$data[]=$row['input_data_file_name'];
		}
        //$data = explode(",",$row);
        //echo"input data files";
        //print_r($data);
// end of testing recent data 
	?>

        <b>The data was inserted/updated successfully. </b><br>
	<a href="<?php echo $config['base_url']; ?>display_phenotype.php?trial_code=<?php echo $trial_code ?>"> View </a><br>
	<a href="<?php echo $config['base_url']; ?>curator_data/input_experiments_upload_excel.php"> Go Back To Main Page </a>
	<?php
   	$footer_div = 1;
    include($config['root_dir'].'theme/footer.php');
	}/* end of type_database function */
} /* end of class */
