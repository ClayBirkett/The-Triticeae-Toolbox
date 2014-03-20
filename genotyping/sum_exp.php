<?php
require 'config.php';
include($config['root_dir'].'includes/bootstrap.inc');
connect();

include $config['root_dir'].'theme/admin_header.php';

$sql = "select line_record_uid, line_record_name from line_records";
$result = mysql_query($sql) or die(mysql_error());
while ($row=mysql_fetch_row($result)) {
  $uid = $row[0];
  $name = $row[1];
  $name_list[$uid] = $name;
}

$sql = "select experiment_uid, trial_code from experiments";
$result = mysql_query($sql) or die(mysql_error());
while ($row=mysql_fetch_row($result)) {
  $uid = $row[0];
  $name = $row[1];
  $trial_name_list[$uid] = $name;
}

echo "<h2>Allele Conflicts by Experiment</h2>\n";
echo "Also see <a href=genotyping/sum_lines.php>conflicts by line</a>, <a href=genotyping/sum_markers.php>conflicts by marker</a>";
echo ", and <a href=genotyping/allele_conflicts.php>All Allele Conflicts</a>.<br><br>\n";
if (isset($_GET['uid'])) {
  $uid = $_GET['uid'];
  $sql = "select l.line_record_name, m.marker_name, a.alleles, e.trial_code
  from allele_conflicts a, line_records l, markers m, experiments e
  where a.line_record_uid = l.line_record_uid
  and a.marker_uid = m.marker_uid
  and a.experiment_uid = e.experiment_uid
  and a.alleles != '--'
  and a.experiment_uid = $uid
  order by l.line_record_name, m.marker_name";
  $result = mysql_query($sql) or die(mysql_error());
  echo "Conflicts for experiment $trial_name_list[$uid]<br>\n";
  echo "<table>\n";
  echo "<tr><td>Line<td>Marker<td>alleles\n";
  while ($row=mysql_fetch_row($result)) {
      $line_name = $row[0];
      $marker_name = $row[1];
      $alleles = $row[2];
      echo "<tr><td>$line_name<td>$marker_name<td>$alleles\n";
  }
} else {
    $sql = "select experiment_uid, count(*) from allele_frequencies
    group by experiment_uid"; 
    $result = mysql_query($sql) or die(mysql_error());
    while ($row=mysql_fetch_row($result)) {
        $uid = $row[0];
        $count = $row[1];
        $total_marker_list[$uid] = $count;
    }
    $sql = "select experiment_uid, count(*) from tht_base
    group by experiment_uid";
    $result = mysql_query($sql) or die(mysql_error());
    while ($row=mysql_fetch_row($result)) {
        $uid = $row[0];
        $count = $row[1];
        $total_line_list[$uid] = $count;
    }
    
    echo "<table>";
    echo "<tr><td>Experiment<td>total<br>measured<td>conflicts<td>percent<br>conflicts\n";
    $sql = "select experiment_uid, count(marker_uid) as temp from allele_conflicts group by experiment_uid order by temp DESC";
    $result = mysql_query($sql) or die(mysql_error());
    while ($row=mysql_fetch_row($result)) {
        $uid = $row[0];
        $count = $row[1];
        $total = ($total_marker_list[$uid]*$total_line_list[$uid]);
        $perc = round(100*$count/$total,2);
        $total = round(($total/1000),0) . "K";
        echo "<tr><td><a href=genotyping/sum_exp.php?uid=$uid>$trial_name_list[$uid]</a><td>$total<td>$count<td>$perc\n";
    }
}
echo "</table></div>";
include $config['root_dir'].'theme/footer.php';