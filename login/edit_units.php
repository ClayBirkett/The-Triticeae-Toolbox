<?php
// 30jan2012 DEM Taken from edit_traits.php.
// 12/14/2010 JLee  Change to use curator bootstrap

require 'config.php';
include $config['root_dir'] . 'includes/bootstrap_curator.inc';
$mysqli = connecti();
loginTest();

ob_start();
include $config['root_dir'] . 'theme/admin_header.php';
authenticate_redirect(array(USER_TYPE_CURATOR,USER_TYPE_ADMINISTRATOR));
ob_end_flush();

/*
 * Has an update been submitted?
 */
if (($id = array_search("Update", $_POST)) != null) {
    foreach ($_POST as $k => $v) {
        $_POST[$k] = addslashes($v);
    }
    updateTable($_POST, "units", array("unit_uid"=>$id));
} elseif (!empty($_POST['Delete'])) {
// Deleting a unit?
    $id = ($_POST['Delete']);
    $name = mysql_grab("select unit_name from units where unit_uid = $id");
    echo "Attempting to delete Unit id = $id, <b>$name</b>...<p>";
    // Is there data for this trait?
    $sql = "select * from phenotypes where unit_uid = $id";
    $res = mysqli_query($mysqli, $sql);
    $datacount = mysqli_num_rows($res);
    if ($datacount > 0)
        echo "<font color=red><b>Can't delete.</b></font> There are <b>$datacount</b> phenotypes using this unit.";
    else {
        // Delete the unit.
        $sql = "delete from units where unit_uid = $id";
    $res = mysqli_query($mysqli, $sql);
    $err = mysqli_error($mysqli);
    if (!empty($err)) {
      if (strpos($err, "a foreign key constraint fails"))
        echo "<font color=red><b>Can't delete.</b></font> Other data is linked to this unit. The error message is:<br>$err";
      else
        echo "<font color=red><b>Can't delete.</b></font> The error message is:<br>$err";
    }
    else
      echo "Success.  Unit <b>$name</b> deleted.<p>";
  }
}

$searchstring = '';
if(isset($_REQUEST['search']) && $_REQUEST['search'] != "") {
  $tablesToSearch = array("units");
  $found = array();
  $searchstring = $_REQUEST['search'];
  $words = explode(" ", $_REQUEST['search']);
  foreach($words as $q) {
    $found = array_merge($found, desperateTermSearch($tablesToSearch, $q));
  }
  $drds = array();
  if(count($found) > 0) {		//if we found results..
    for($i=0; $i<count($found); $i++) {
      $parts = explode("@@", $found[$i]);
      array_push($drds, $parts[2]);
    }
  }
}

$start = 0;
if(isset($_GET['start'])) 
  $start = $_GET['start'];

?>

<div id="primaryContentContainer">
  <div id="primaryContent">
    <div class="box">
      <h2>Edit Trials</h2>
      <div class="boxContent">
	<form action="<?php echo $config['base_url']; ?>login/edit_units.php" method="post">
	  <p>Show only items containing these words:<br>
	    <input type="text" name="search" value="<?php echo $searchstring ?>" size="30" /> 
	    <input type="submit" value="Search" /></p>
	</form>
      </div>
    </div>

<?php
// attaching the query string to the callback URL.
$self = $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'];

if(isset($drds) && count($drds) > 0) {
  $self .= isset($_GET['search']) ? "" : "search=". $_REQUEST['search'];
  editSelectTrials($drds, $self, $start);
}
else if(!isset($drds))
  editAllTrials($self, $start);
else 
  echo "<p>Search returned no results</p>";

echo "</div></div></div>";
include $config['root_dir'] . 'theme/footer.php';



////////// The editing functions:

/*
 * This function will actually display the row. 
 *
 * @param $where - sets the conditions of which to select the row(s). This makes it possible to select any number of rows.
 * @param $page - editing allows for updating and has a button that goes to a certain page to update. This variable sets that page
 * 
 * @return nothing - this function outputs to the screen.
 */
function editTrialRow($where, $page, $start="0") {
  $ignore = array("unit_uid");
  editGeneral("units", $where, $page, $ignore, "20", $start);
}

/*
 * This is an example of using the above function. This should display every line (minus the gramene data) in the same format 
 * as the spreadsheet. The problem is the 0 value in the units table. It's killing us unless we put something for 0 in there.
 */
function editAllTrials($page, $start) {
  editTrialRow("1", $page, $start);
}

/*
 * This will select a range of traits to edit from a given id to a given id. 
 *
 * $minID - the lower boundary of id to get.
 * $maxID - the upper boundary of id to get.
 * $page - this is the page that the update button will travel to. 
 *
 * Note: These values are exclusive, meaning if $minID = 1 and $maxID = 5 then the results returned will be IDs: 2, 3, and 4.
 *
 * @return nothing
 * @see editTrialRow()
 */
function editRangeTrials($minID, $maxID, $page) {
	$where = "unit_uid < '$maxID' AND unit_uid > '$minID'";
	editTrialRow($where, $page);
}

/*
 * This will select a list of traits to edit from a given array of IDs
 * 
 * If we have a bunch of IDs that we want to edit and there isn't a range
 * of them then we can use this function to display them. 
 *
 * @param $IDRange - an array of IDs to edit. This MUST be an array.
 * @param $page - the page that the update button will travel to
 *
 * @return nothing
 * @see editTrialRow()
 */ 
function editSelectTrials($IDRange, $page, $start) {
  if(is_array($IDRange)) {
    $where = "";
    for($i=0; $i<count($IDRange); $i++) {
      if($i != 0)
	$where .= " OR ";
      $where .= "unit_uid = '$IDRange[$i]'";
    }
    editTrialRow($where, $page, $start);
  }
}

/*
 * This function will edit only a single row. 
 * 
 * WARNING: Do not use this function in a for loop if you have multiple IDs to edit
 *	    use the editSelectTrials() function for that.
 *
 * @param $ID - the id of the row to edit
 * @param $page - the page that the update button will travel to
 * 
 * @return nothing
 * @see editTrialRow()
 */
function editSingleTrial($ID, $page) {
  $where = "unit_uid = '$ID'";
  editTrialRow($where, $page);
}

//////////

?>
