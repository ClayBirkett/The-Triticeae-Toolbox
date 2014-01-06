<?php
/**
 * Download Gateway New
 * 
 * PHP version 5.3
 * Prototype version 1.5.0
 * 
 * @category PHP
 * @package  T3
 * @author   Clay Birkett <clb343@cornell.edu>
 * @license  http://triticeaetoolbox.org/wheat/docs/LICENSE Berkeley-based
 * @version  GIT: 2
 * @link     http://triticeaetoolbox.org/wheat/curator_data/fieldbook_export.php
 * 
 */
require_once 'config.php';
require $config['root_dir'] . 'includes/bootstrap_curator.inc';
connect();
$mysqli = connecti();

new Tablet($_GET['function']);

/** Using a PHP class to implement the "Tablet" feature
 * 
 * @category PHP
 * @package  T3
 * @author   Clay Birkett <claybirkett@gmail.com>
 * @license  http://triticeaetoolbox.org/wheat/docs/LICENSE Berkeley-based
 * @link     http://triticeaetoolbox.org/wheat/curator_data/fieldbook_export.php
 **/
class Tablet
{
    /**
     * Using the class's constructor to decide which action to perform
     *
     * @param string $function action to perform
     */
    public function __construct($function = null)
    {
        switch($function)
        {
            case 'save':
                    $this->save();
                    break;
            case 'step2phenotype':
                    $this->step2_phenotype();
                    break;
            case 'step3phenotype':
                    $this->step3_phenotype();
                    break;
            default:
                    $this->display();
                    break;
        }
    }
	
    function display()
    {
    	global $config;
    	global $mysqli;
		include($config['root_dir'] . 'theme/admin_header.php');
                ?>
		<h2>Tablet Tools</h2>
                These tools provide a interface to the Android Field Book program created by the
                <a href="http://www.wheatgenetics.org/bioinformatics/22-android-field-book.html">Poland Lab</a>.
                Before using these tools it is necessary to <a href="curator_data/input_experiments_upload_excel.php">import a field layout</a> into the database.
                In the Field Book program the range is the first level division and refers to the row of the field. 
                The second level division is the plot. All other coluns are considered Extra Information. The program moves through the field row by row for measurements.
                <h4>Create Field Layout and Trait File for import into the tablet</h4>
		1. Select a fieldbook containing your experiment. Download and save the field layout file.<br>
                2. Select a category and one or more traits. Download and save the trait file.<br>
                3. Connect your tablet to this computer.<br>
                4. Move the field layout file to the field_import folder of the SD card of the tablet.<br>
                5. Move the trait file to the trait folder of the SD card of the tablet.<br>
                6. Import these files into the Field Book App.<br>
                7. Record measurements using the tablet.<br><br>

		<div style="float: left">
                <table class="tableclass1">	
		<tr><th>Fieldbook:
                <?php
		$sql = "select fieldbook_info_uid, experiment_uid, fieldbook_file_name from fieldbook_info";
		$sql = "select distinct(fieldbook.experiment_uid), trial_code from fieldbook, experiments where fieldbook.experiment_uid = experiments.experiment_uid";
		$res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
		echo "<tr><td><select id=\"experiment\" name=\"experiment\" onchange=\"javascript: update_expr()\">";
		echo "<option>select a fieldbook</option>\n";
		while ($row = mysqli_fetch_row($res)) {
			$uid = $row[0];
			$name = $row[1];
			echo "<option value=$uid>$name</option>\n";
		}
		?>
		</select>
                </table>
		</div>
                <div id="export" style="float: left; text-align: center; width: 200px"></div>

                <div id="step1" style="clear: both; float: left; margin-bottom: 1.5em;">
                <div id="step11">
                <?php
                $this->step1_phenotype();
                ?>
                </div></div>
                <div id="step2" style="float: left; margin-bottom: 1.5em;"></div>
                <div id="step3" style="float: left; text-align: center; width: 200px"></div>
                <div id="step4" style="clear: both; float: left;">
                </div><br><br>
		<div style="clear:both; float: left;">
		<h4>Create Trait Plot File (Exported from tablet)</h4>
		1. After completing data collection, export the data in table format.<br>
                2. Export as "Table Format" and select the first 4 columns for export.<br>
                3. Connect your tablet to this computer and move the file from the field_export folder.<br>
                4. Browse to this file and select Upload.<br>
		<p><form action="curator_data/input_tablet_plot_check.php" method="post" enctype="multipart/form-data">Plot file:
		<input type="hidden" id="plot" name="plot" value="-1" />
		<input id="file[]" type="file" name="file[]" size="50%" />
		<input type="submit" value="Upload" /><br>
		<a href="<?php echo $config['base_url']; ?>curator_data/examples/T3/PlotTabletTemplate.csv">Example Plot file</a><br>
		</form></p>
		
		</div>
                <div style="clear:both;">
                Note: Other tablet devices can be used by opening the file from the fieldbook download in a spreedsheet application and adding on columns for each trait as in the Example Plot file.
                </div></div>
		<?php
		include($config['root_dir'].'theme/footer.php');
		?>
		<script type="text/javascript" src="curator_data/tablet.js"></script>
		<?php 
    }
    
    function step1_phenotype()
    {
        global $mysqli;
        ?>
        <table id="phenotypeSelTab" class="tableclass1">
                <tr>
                        <th>Category</th>
                </tr>
                <tr><td>
                        <select name="phenotype_categories" multiple="multiple" style="height: 10.5em;" onchange="javascript: update_phenotype_categories(this.options)">
                <?php
                $sql = "SELECT phenotype_category_uid AS id, phenotype_category_name AS name from phenotype_category";
                $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
                while ($row = mysqli_fetch_assoc($res))
                {
                 ?>
                                <option value="<?php echo $row['id'] ?>">
                                        <?php echo $row['name'] ?>
                                </option>
                                <?php
                }
                ?>
                </select>
                </td>
                </table>
        <?php 
    }

    private function step2_phenotype()
    {
        global $mysqli;
        $phen_cat = $_GET['pc'];
        ?>
        <table id="phenotypeSelTab" class="tableclass1">
        <tr>
                <th>Traits</th>
                </tr>
                <tr><td>
                <select id="traitsbx" name="phenotype_items" multiple="multiple" style="height: 10.5em;" onchange="javascript: update_phenotype_items(this.options)">
                <?php

                  $sql = "SELECT DISTINCT phenotypes.phenotype_uid AS id, phenotypes_name AS name from phenotypes, phenotype_category, phenotype_data, line_records, tht_base
                  where phenotypes.phenotype_uid = phenotype_data.phenotype_uid
                  AND phenotypes.phenotype_category_uid = phenotype_category.phenotype_category_uid
                  AND phenotype_data.tht_base_uid = tht_base.tht_base_uid 
                  AND line_records.line_record_uid = tht_base.line_record_uid 
                  AND phenotype_category.phenotype_category_uid in ($phen_cat)";
        $res = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
                while ($row = mysqli_fetch_assoc($res))
                {
                 ?>
                    <option value="<?php echo $row['id'] ?>">
                     <?php echo $row['name'] ?>
                    </option>
                    <?php
                }
                ?>
                </select>
                </table>
        <?php
    }

    private function step3_phenotype()
    {
        global $mysqli;
        $phen_item = $_GET['pi'];
        $phen_list = explode(",", $phen_item);

        $sql = "select phenotype_uid, phenotypes_name, datatype from phenotypes";
        $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_row($res)) {
            $uid= $row[0];
            $trait[$uid] = $row[1];
            $format[$uid] = $row[2];
        }

        $unique_str = chr(rand(65,80)).chr(rand(65,80)).chr(rand(65,80)).chr(rand(65,80));  
        $filename = "import_" . $unique_str . ".trt";
        $output = fopen("/tmp/tht/$filename","w");
        $pos = 1;
        fwrite($output, "trait,format,defaultValue,minimum,maximum,details,categories,isVisible,realPosition\n");
        foreach ($phen_list as $item) {
           fwrite($output, "$trait[$item],$format[$item],,,,,,TRUE,$pos\n");
           $pos++;
        }
        fclose($output);
        echo "<form method=\"link\" action=\"curator_data/download_file.php\" method=\"get\">";
        echo "<input type=hidden name=\"file\" value=\"$filename\">";
        echo "<input type=submit value=\"Download\">";
        echo "</form>";
    }

    function save()
    {
    	global $config;
    	global $mysqli;
    	$uid = $_GET['uid'];

        $sql = "select line_record_uid, line_record_name from line_records";
        $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
        while ($row = mysqli_fetch_row($res)) {
                $line_uid= $row[0];
                $line_record_name = $row[1];
                $name_list[$line_uid] = $line_record_name;
        }

        $error = 0;
    	$unique_str = chr(rand(65,80)).chr(rand(65,80)).chr(rand(65,80)).chr(rand(65,80));
    	$filename = "import_" . $unique_str . ".csv";
    	if (! file_exists('/tmp/tht')) mkdir('/tmp/tht');
    	$output = fopen("/tmp/tht/$filename","w");
    	fwrite($output, "plot_id,range,plot,tray_row,name,replication,block\n");
    	$sql = "select plot_uid, plot, block, row_id, column_id, replication, check_id, line_uid from fieldbook where experiment_uid = $uid order by row_id, plot";
    	$res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
    	while ($row = mysqli_fetch_row($res)) {
    		$plot_id = $row[0];
    		$plot = $row[1];
    		$block = $row[2];
    		$row_id = $row[3];
    		$column_id = $row[4];
                $replication = $row[5];
                $check_id = $row[6];
                $line_uid = $row[7];
                $line_record_name = $name_list[$line_uid];
                if (!preg_match("/\d+/",$row_id)) {
                    $error = 1;
                }
                if (!preg_match("/\d+/",$column_id)) {
                    $error = 1;
                }
    		fwrite($output, "$plot_id,$row_id,$plot,$column_id,$line_record_name,$replication,$block\n");
    	}
    	fclose($output);
        if ($error == 0) {
    	echo "<form method=\"link\" action=\"/tmp/tht/$filename\">";
    	echo "<input type=submit value=\"Download\">";
    	echo "</form>";
        } else {
            echo "<font color=red>Error: Fieldbook did not contain required row and column location information</font>\n";
        }
    }
}
