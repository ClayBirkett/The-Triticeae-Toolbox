<?php session_start();
require 'config.php';
include($config['root_dir'] . 'includes/bootstrap.inc');
connect();
include($config['root_dir'] . 'theme/admin_header.php');
?>


<?php
if($_SERVER['REQUEST_METHOD'] == "POST")
{
       
        $name = $_POST['LineSearchInput'];
        $hullType = $_POST['Hull'];
        $rowType = $_POST['RowType'];
        $severity = $_POST['severity'];
        $description = $_POST['description'];
       
			 
        $typeSelected[$rowType] = 'checked="checked"';
        $hullSelected[$hullType] = 'checked="checked"';
       /* $severitySelected[$severity] = 'selected="selected"';*/


        if(is_array($_POST['breedingprogramcode']))
        {
                foreach ($_POST['breedingprogramcode'] as $key => $value)
                {
                       // echo "Problem Type $key: $value<br/>";
                        $breeding[$value] = 'selected="selected"';
                }
        }  
        
        if(is_array($_POST['primaryenduse']))
        {
                foreach ($_POST['primaryenduse'] as $key => $value)
                {
                       // echo "Problem Type $key: $value<br/>";
                        $primary[$value] = 'selected="selected"';
                }
        }  
        
        if(is_array($_POST['growthhabit']))
        {
                foreach ($_POST['growthhabit'] as $key => $value)
                {
                       // echo "Problem Type $key: $value<br/>";
                        $growth[$value] = 'selected="selected"';
                }
        } 
        
        
        

}



?>









<script type="text/javascript">
//var test = new Array("<?/*php echo $selLines*/?>");
//test1 =  test.length;
// Select All
            function exclude_all() {
            
            alert ("hi");
            	count = document.lines.elements.length;
    for (i=0; i < count; i++) 
	{
    if(document.lines.elements[i].checked == 0)
    	{document.lines.elements[i].checked = 1; }
   // else {document.lines.elements[i].checked = 1;}
	}

          document.lines.btn1.checked = "checked";                     
            }
            
            function exclude_none()
            {
            count = document.lines.elements.length;
    for (i=0; i < count; i++) 
	{
    if(document.lines.elements[i].checked == 1)
    	{document.lines.elements[i].checked = 0; }
    //else {document.lines.elements[i].checked = 1;}
	}
            }
            
            
            
</script>

<style type="text/css">
			th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
			h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
		</style>


<div id="primaryContentContainer">
  <div id="primaryContent">
  <h2> Search Lines</h2>
  <br/>
  <div class="boxContent">
  <h3> Search for line records: </h3>
  <table width="850px">
  <form id="searchLines" action="<?php echo $_SERVER['SCRIPT_NAME']?>" method="POST">
  
	<tr> <td>
  <b>Name</b> <br/><br/><input type="text" name="LineSearchInput" value="<?php echo $name?>"/> <br/><br/> Eg: Excel,Morex,.. </td>
  <td> 
	<b> Breeding Program </b> <br/><br/>
		
	
	<select name="breedingprogramcode[]" multiple="multiple" size="4" style="width: 12em height: 12em;">
				<?php
		
		$sql = "SELECT DISTINCT(l.breeding_program_code), c.data_program_name FROM line_records l, CAPdata_programs c WHERE l.breeding_program_code = c.data_program_code ";
		$res = mysql_query($sql) or die(mysql_error());
		while ($resp = mysql_fetch_assoc($res))
		{
			?>
				<option value="<?php echo $resp['breeding_program_code'] ?>" <?php echo $breeding[$resp['breeding_program_code']]?>><?php echo $resp['breeding_program_code'] ?><?php echo "--".$resp['data_program_name'] ?></option>
			<?php
		}
		?>
						</select><br/><br/><br/>
	</td>
	<td> <b> Primary End Use </b> <br/><br/>
	
	<select name="primaryenduse[]" multiple="multiple" size="4" style="width: 12em height: 12em;">
				<?php
		
		
		$sql = "SELECT DISTINCT(primary_end_use) FROM line_records WHERE primary_end_use NOT LIKE 'NULL'";
		$res = mysql_query($sql) or die(mysql_error());
		while ($resp = mysql_fetch_assoc($res))
		{
			
			?>
				<option value="<?php echo $resp['primary_end_use'] ?>" <?php echo $primary[$resp['primary_end_use']]?>><?php echo $resp['primary_end_use'] ?></option>
			<?php
		}
		?>
						</select><br/><br/><br/>
	
	
	</td>
  </tr>
  <tr>
  <td>
  <b>Growth Habit </b> <br/> <br/>
	
	<select name="growthhabit[]" multiple="multiple" size="4" style="width: 10em;height: 4em;">
				<?php
		
		$sql = "SELECT DISTINCT(growth_habit) FROM line_records WHERE growth_habit NOT LIKE 'NULL'";
		$res = mysql_query($sql) or die(mysql_error());
		//$count = 1;
		while ($resp = mysql_fetch_assoc($res))
		{
			
			?>
				<option value="<?php echo $resp['growth_habit'] ?>" <?php echo $growth[$resp['growth_habit']]?>><?php echo $resp['growth_habit'] ?></option>
			<?php
			//	$count++;
		}
		?>
						</select>
	
	
	</td>
  <td>
  <b>Row Type </b> <br/><br/>
	<input type="radio" name="RowType" value="2" <?php echo $typeSelected['2'] ?>/> 2 &nbsp;&nbsp; <input type="radio" name="RowType" value="6" <?php echo $typeSelected['6'] ?>/> 6 
	</td>
	<td>
	<b> Hull </b> <br/><br/>
	<input type="radio" name="Hull" value="hulled" <?php echo $hullSelected['hulled']?>/> Hulled &nbsp;&nbsp; <input type="radio" name="Hull" value="hulless" <?php echo $hullSelected['hulless']?>/> Hulless
	</td>
	</tr>
	<tr align="center">
	<td></td>
	<td >
  <p ><input type="submit" style="height:2em; width:8em;" value="Search"/></p>
  </td>
  </tr>
  </form>
  </table>
  
	<?php
		
  if (isset($_POST['LineSearchInput'])) {
    $linename = $_POST['LineSearchInput'];
    $breedingProgram = $_POST['breedingprogramcode'];
    $growthHabit = $_POST['growthhabit'];
    $rowType = $_POST['RowType'];
    $hull = $_POST['Hull'];
    $primaryEndUse = $_POST['primaryenduse'];
    
    $breedingCode = implode("','", $breedingProgram);
    $growthStr = implode("','", $growthHabit);
    $primaryUse = implode("','", $primaryEndUse);
    $count = 0;
    
    
    if (count($breedingProgram) != 0)
    {
    	if ($count == 0)
    	{
			$where .= "breeding_program_code IN ('".$breedingCode."')";
			}
			else
			{
			$where .= " AND breeding_program_code IN ('".$breedingCode."')";
			}
			$count++;
		}
		
		if (count($growthHabit) != 0)
    {
    if ($count == 0)
    	{
			$where .= "growth_habit IN ('".$growthStr."')";
			}
		else
			{
				$where .= " AND growth_habit IN ('".$growthStr."')";
			}
			$count++;
		}
		
		if (count($primaryEndUse) != 0)
    {
    if ($count == 0)
    	{
    	$where .= "primary_end_use IN ('".$primaryUse."')";
    	}
    else
    	{
			$where .= " AND primary_end_use IN ('".$primaryUse."')";
			}
			
			$count++;
		}
		
		if (strlen($linename) > 0)
		{
		if ($count == 0)
    	{
    	$where .= "line_record_name regexp ('".$linename."')";
    	}
    else
    	{
			$where .= " AND line_record_name regexp ('".$linename."')";
			}
			$count++;
		}
		
		if (strlen($rowType) > 0)
		{
		if ($count == 0)
    	{
    	$where .= "row_type IN ('".$rowType."')";
    	}
    else
    	{
			$where .= " AND row_type IN ('".$rowType."')";
			}
			$count++;
		}
		
		if (strlen($hull) > 0)
		{
		if ($count == 0)
    	{
    	$where .= "hull IN ('".$hull."')";
    	}
    else
    	{
			$where .= " AND hull IN ('".$hull."')";
			}
			$count++;
		}
		
    
    // $test = "'CC','SM'";
    
   // echo "WHere VAlue =".$where ;
    
    //$escaped = mysql_real_escape_string($where);
    
   // echo "excaped VAlue =".$escaped ;
    
   /* echo "<br/>";
    var_dump($escaped);
    var_dump($lineStr);
    var_dump($test);*/
   // var_dump($breedingCode);
    
   // var_dump ($breedingProgram);
    //var_dump ($breedingCode);
    
   /* var_dump($breedingProgram);
    var_dump($growthHabit);
    var_dump($primaryEndUse); 
    
    if (strlen($linename) < 1)
      $linename = ".";
   
    if (count($breedingProgram) < 1)
      $breedingProgram = ".";
      
    if (count($growthHabit) < 1)
      $growthHabit = ".";
      
    if (strlen($rowType) < 1)
      $rowType = ".";  
		
		if (strlen($hull) < 1)
      $hull = ".";     
		
		if (count($primaryEndUse) < 1)
      $primaryEndUse = ".";   */
      
    if ( (strlen($linename) < 1) AND (strlen($hull) < 1) AND (strlen($rowType) < 1) AND (count($breedingProgram) == 0) AND  (count($growthHabit) == 0) AND (count($primaryEndUse) == 0)  )
    {
			$result=mysql_query("select line_record_uid, line_record_name from line_records ");
		}
  	else
  	{
    // echo "select line_record_uid, line_record_name from line_records where line_record_name regexp \"$linename\"";
    
   // $result=mysql_query("select line_record_uid, line_record_name from line_records where line_record_name regexp '".$linename."' ");
    
    //$result=mysql_query("select line_record_uid, line_record_name from line_records where ('".$where."') ");
		$result=mysql_query("select line_record_uid, line_record_name from line_records where $where ");
  //  echo "<div style="padding: 0; width: 810px; height: 300px; overflow: scroll; border: 1px">";
  	
  		//var_dump($result);
  	}
	?>
  <h2> Select Lines</h2>
	<div style="width: 420px; height: 280px; overflow: scroll;border: 1px solid #5b53a6;">
	<?php
		
   //  echo "<input type='submit' value='Select Lines'><br/>";
  	
    echo "<table width='400px' id='linesTab' class='tableclass1'>";
    ?><tr><th>Check <br/>
		 <input type="radio" name="btn1" value="ALL" onclick="javascript:exclude_all();"/>All
		 <input type="radio" name="btn1" value="NONE" onclick="javascript:exclude_none();"/>None</th><th><b>Line name</b></th></tr>
		<?php
		
		 echo "<form name='lines' id='selectLines' action='pedigree/line_selection.php' method='post'>";
    
    if (mysql_num_rows($result) > 0) {
      while($row = mysql_fetch_assoc($result)) {
	$line_record_name = $row['line_record_name'];
	$line_record_uid = $row['line_record_uid'];
	?>
	<tr>
	

	<td><input type='checkbox' value="<?php echo $line_record_uid?>" name='selLines[]'id="exbx_<?php echo $line_record_uid ?>"/>
	</td>
	
	<td>
	 <?php echo $line_record_name ?> 
	</td>
	</tr>
	<?php
      }
    }
    ?>
  </table>    
  </div>
    <?php
   
   // echo "</div>";
    echo "<input type='submit' value='Select Lines'>";
    echo "</form>";
  }
  ?>

</div>	
</div>
</div>

<?php
$verify_selected_lines = $_POST['selLines'];
$verify_session = $_SESSION['selected_lines'];
if (count($verify_selected_lines)!=0 OR count($verify_session)!=0)
{

?>
<div>

  <?php
  if (isset($_POST['selLines'])) {  
  
	    $selLines = $_POST['selLines'];
    $selected_lines = $_SESSION['selected_lines'];
    if (!isset($selected_lines))
      $selected_lines = array();
    foreach($selLines as $line_uid) {
      if (!in_array($line_uid, $selected_lines)) {
	array_push($selected_lines, $line_uid);
      }
    }
    $_SESSION['selected_lines'] = $selected_lines;
  }
if (isset($_POST['deselLines'])) {
  $selected_lines = $_SESSION['selected_lines'];
  foreach ($_POST['deselLines'] as $line_uid) {
    if (($lineidx = array_search($line_uid, $selected_lines)) !== false) {
      array_splice($selected_lines, $lineidx,1);
    }
  }
  $_SESSION['selected_lines']=$selected_lines;
 }
$username=$_SESSION['username'];
if ($username && !isset($_SESSION['selected_lines'])) {
  $stored = retrieve_session_variables('selected_lines', $username);
  if (-1 != $stored)
    $_SESSION['selected_lines'] = $stored;
 }
$display = $_SESSION['selected_lines'] ? "":" style='display: none;'";

print "<form id=\"deselLinesForm\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" $display>";
echo "<h3>Currently selected lines</h3>";
print "<select name=\"deselLines[]\" multiple=\"multiple\" style=\"height: 12em;width: 16em\">";
foreach ($_SESSION['selected_lines'] as $lineuid) {
  $result=mysql_query("select line_record_name from line_records where line_record_uid=$lineuid") or die("invalid line uid\n");
  while ($row=mysql_fetch_assoc($result)) {
    $selval=$row['line_record_name'];
    print "<option value=\"$lineuid\">$selval</option>\n";
  }
}
print "</select>";
print "<p><input type=\"submit\" value=\"Deselect Lines\" /></p>";
print "</form>";
	
$display1 = $_SESSION['selected_lines'] ? "":" style='display: none;'";	
print "<form id=\"showPedigreeInfo\" action=\"pedigree/pedigree_info.php\" method=\"post\" $display1>";
print "<p><input type=\"submit\" value=\"Show Line Information\" /></p></form>";

// store the selected markers into the database
if ($username)
  store_session_variables('selected_lines', $username);
?>
</div>
<?php
}
?>














<?php
require $config['root_dir'] . 'theme/footer.php';
?>
