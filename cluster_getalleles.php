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
 * @link     http://triticeaetoolbox.org/wheat/cluster_getalleles.php
 * 
 */

require 'config.php';
//Need write access to update the cache table.
//include($config['root_dir'].'includes/bootstrap.inc');
include($config['root_dir'].'includes/bootstrap_curator.inc');
include($config['root_dir'].'downloads/marker_filter.php');

connect();

  foreach ($_SESSION['selected_lines'] as $lineuid) {
    $result=mysql_query("select line_record_name from line_records where line_record_uid=$lineuid") or die("invalid line uid\n");
    while ($row=mysql_fetch_assoc($result)) {
      $selval=$row['line_record_name'];
    }
  }

$starttime = time();
$selected_lines = $_SESSION['selected_lines'];
$min_maf = $_GET['mmaf'];
$max_missing = $_GET['mmm'];
$max_miss_line = $_GET['mml'];
calculate_af($selected_lines, $min_maf, $max_missing, $max_miss_line);

if (!isset ($_SESSION['selected_lines']) || (count($_SESSION['selected_lines']) == 0) ) {
  // No lines selected so prompt to get some.
  echo "<a href=".$config['base_url']."pedigree/line_selection.php>Select lines.</a> ";
  echo "(Patience required for more than a few hundred lines.)";
} elseif (!isset ($_SESSION['filtered_lines'])) {
  echo "Error: filtering routine did not work<br>\n";
  die();
} else {
  $sel_lines = implode(",", $_SESSION['filtered_lines']);
  $delimiter =",";
  // Adapted from download/downloads.php:
  // 2D array of alleles for all markers x currently selected lines

  // Get all markers that have allele data, in marker_uid order as they are in allele_byline.alleles.
  $sql = "select marker_uid, marker_name from allele_byline_idx order by marker_uid";
  $res = mysql_query($sql) or die(mysql_error());
  while ($row = mysql_fetch_row($res)) {
    $markerids[] = $row[0];
    // First row of output file mrkData.csv is list of marker names.
    $outputheader .= $row[1] . $delimiter;
  }

  // Create cache table if necessary.
  $n = mysql_num_rows(mysql_query("show tables like 'allele_byline_clust'"));
  if ($n == 0) {
    $sql = "create table allele_byline_clust (
	      line_record_uid int(11) NOT NULL,
              line_record_name varchar(50),
	      alleles TEXT  COMMENT 'Up to 2^16 (65K) characters. Use MEDIUMTEXT for 2^24.',
              updated_on timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	      PRIMARY KEY (line_record_uid)
	    ) COMMENT 'Cache created from table allele_byline.'";
    $res = mysql_query($sql) or die (mysql_error());
    $update = TRUE;
  }
  else {
    // Update cache table if necessary. Empty?
    if(mysql_num_rows(mysql_query("select * from allele_byline_clust")) == 0)
      $update = TRUE;
    // Out of date?
    $sql = "select if( datediff(
	    (select max(updated_on) from allele_frequencies),
	    (select max(updated_on) from allele_byline_clust)
  	  ) > 0, 'need_update', 'okay')";
    $need = mysql_grab($sql);
    if ($need == 'need_update') $update = TRUE;
  }
  if ($update) {
    echo "Updating table allele_byline_clust...<p>";
    set_time_limit(3000);  // Default 30sec runs out in ca. line 105. So does 300.
    mysql_query("truncate table allele_byline_clust") or die(mysql_error());
    $lookup = array('AA' => '1',
		    'BB' => '0',
		    'AB' => '0.5');
    // Compute global allele frequencies.
    $sql = "select marker_uid, aa_cnt, ab_cnt, total from allele_frequencies";
    $res = mysql_query($sql) or die(mysql_error());
    while ($row = mysql_fetch_array($res)){
      $aa_sum[$row[0]] += $row[1];
      $ab_sum[$row[0]] += $row[2];
      $total_sum[$row[0]] += $row[3];
    }
    // Store in the same order as $markerids[], i.e. table allele_byline_idx.
    foreach ($markerids as $id) {
      $afreq[$id] = ($aa_sum[$id] + 0.5 * $ab_sum[$id]) / $total_sum[$id];
      $afreq[$id] = number_format($afreq[$id], 3);
    } 
    // Read in the allele_byline table.
    $sql = "select * from allele_byline";
    $res = mysql_query($sql) or die(mysql_error());
    while ($row = mysql_fetch_array($res)) {
      $lineid = $row['line_record_uid'];
      $line = $row['line_record_name'];
      $alleles = explode(',', $row['alleles']);
      for ($i=0; $i<count($alleles); $i++) {
	if ($alleles[$i] == '' or $alleles[$i] == '--')
	  // Substitute global frequency for missing values.
	  $alleles[$i] = $afreq[$markerids[$i]];
	else
	  // Translate to numeric score.
	  $alleles[$i] = $lookup[$alleles[$i]];
      }
      $alleles = implode(',', $alleles);
      // Store in cache table.
      $sql = "insert into allele_byline_clust values (
         $lineid, '$line', '$alleles', NOW() )";
      mysql_query($sql) or die(mysql_error()."<br>Query:<br>$sql");
    }
  } // end of if($update)

  $sql = "select marker_uid, marker_name from allele_byline_idx order by marker_uid";
                $res = mysql_query($sql) or die(mysql_error() . "<br>" . $sql);
                $i=0;
                while ($row = mysql_fetch_array($res)) {
                   $marker_list[$i] = $row[0];
                   $marker_list_name[$i] = $row[1];
                   $i++;
                }

  $markers = $_SESSION['filtered_markers'];
  foreach ($markers as $temp) {
    $marker_lookup[$temp] = 1;
  }
  // Save the list of marker names to the output file.
  //$outputheader = trim($outputheader, ",")."\n";
  $outputheader = '';
  foreach ($marker_list as $i => $marker_id) {
    $marker_name = $marker_list_name[$i];
    if (isset($marker_lookup[$marker_id])) {
      if ($outputheader == '') {
         $outputheader .= $marker_name;
      } else {
         $outputheader .= $delimiter.$marker_name;
      }
    }
  }
  $outputheader .= "\n";
  // Make the filename unique to deal with concurrency.
  $time = $_GET['time'];
  if (! file_exists('/tmp/tht')) mkdir('/tmp/tht');
  $outfile = "/tmp/tht/mrkData.csv".$time;
  file_put_contents($outfile, $outputheader);

  //$starttime = time();
  // Get the alleles for currently selected lines, all genotyped markers.	
  foreach ($_SESSION['filtered_lines'] as $lineuid) {
    $sql = "select line_record_name, alleles from allele_byline_clust
          where line_record_uid = $lineuid";
    //echo "$sql<br>\n";
    $res = mysql_query($sql) or die(mysql_error());
    if ($row = mysql_fetch_array($res)) 
      $outarray2 = array();
      $line_name = $row[0];
      $alleles = $row[1];
      //echo "$line_name $alleles\n";
      $outarray = explode(',',$alleles);
      $i=0;
      foreach ($outarray as $allele) {
        $marker_id = $marker_list[$i];
        if (isset($marker_lookup[$marker_id])) {
          $outarray2[]=$allele;
        }
        $i++;
      }
      $outarray = implode($delimiter,$outarray2);  
      file_put_contents($outfile, $line_name.$delimiter.$outarray."\n", FILE_APPEND);
    }
    $elapsed = time() - $starttime;
    $_SESSION['timmer'] = $elapsed;
  }

echo "</div></div></div>";

?>
