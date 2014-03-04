<?php
require 'config.php';
include($config['root_dir'].'includes/bootstrap.inc');
connect();

include $config['root_dir'].'theme/admin_header.php';

$sql = "select marker_uid, marker_name from markers";
$result = mysql_query($sql) or die(mysql_error());
while ($row=mysql_fetch_row($result)) {
  $uid = $row[0];
  $name = $row[1];
  $name_list[$uid] = $name;
}

echo "<h2>Allele Conflicts by Markers</h2>\n";
echo "Also see <a href=genotyping/sum_lines.php>conflicts by line</a>, <a href=genotyping/sum_exp.php>conflicts by experiment</a>";
echo ", and <a href=genotyping/allele_conflicts.php>All Allele Conflicts</a>.<br><br>\n";

if (isset($_GET['uid'])) {
  $uid = $_GET['uid'];
  echo "<h3>Allele Conflicts for $name_list[$uid] between experiments</h3>\n";

  //get list of trials
  $sql = "select distinct(e.trial_code), e.experiment_uid
  from allele_conflicts a, line_records l, markers m, experiments e
  where a.line_record_uid = l.line_record_uid
  and a.marker_uid = m.marker_uid
  and a.experiment_uid = e.experiment_uid
  and m.marker_uid = $uid";
  $result = mysql_query($sql) or die(mysql_error());
  $count = 0;
  while ($row=mysql_fetch_row($result)) {
    $count++;
    $trial = $row[0];
    $e_uid = $row[1];
    $empty[$trial] = "";
    $trial_list[$e_uid] = $trial;
  }

  echo "<table>";
  echo "<tr><td>";
  foreach ($trial_list as $trial1=>$val1) {
    echo "<td style=\"font-size:10px; word-break:break-all\">$val1";
  }
  foreach ($trial_list as $trial1=>$val1) {
    echo "<tr><td>$val1";
    foreach ($trial_list as $trial2=>$val2) {
      $count = 0;
      unset($marker_list1);
      unset($marker_list2);
      $sql = "select line_record_uid, alleles from allele_conflicts
        where marker_uid = $uid
        and experiment_uid = $trial1";
      $result = mysql_query($sql) or die(mysql_error());
      while ($row=mysql_fetch_row($result)) {
          $count1++;
          $line_record_uid = $row[0];
          $alleles1 = $row[1];
          $marker_list1[$line_record_uid] = $alleles1;
      }
      $sql = "select line_record_uid, alleles from allele_conflicts
        where marker_uid = $uid
        and experiment_uid = $trial2";
      $result = mysql_query($sql) or die(mysql_error());
      while ($row=mysql_fetch_row($result)) {
          $count2++;
          $line_record_uid = $row[0];
          $alleles1 = $row[1];
          $marker_list2[$line_record_uid] = $alleles1;
      }
      foreach ($marker_list1 as $line_record_uid=>$alleles1) {
        if (isset($marker_list2[$line_record_uid])) {
          $alleles2 = $marker_list2[$line_record_uid];
          if ($alleles1 == $alleles2) {
          } else {
            $count++;
          }
        }
      }
      echo "<td>$count";
    }
    echo "\n";
  }
  echo "</table><br>\n";

  echo "<h3>Allele Conflicts for $name_list[$uid] sorted by line name</h3>\n";
  $sql = "select l.line_record_name, m.marker_name, a.alleles, e.trial_code
  from allele_conflicts a, line_records l, markers m, experiments e
  where a.line_record_uid = l.line_record_uid
  and a.marker_uid = m.marker_uid
  and a.experiment_uid = e.experiment_uid
  and a.alleles != '--'
  and m.marker_uid = $uid
  order by l.line_record_name, m.marker_name, e.trial_code";
  $result = mysql_query($sql) or die(mysql_error());
  $count = 0;
  $prev = "";
  echo "<table>\n";
  echo "<tr><td>Line name\n";
  foreach ($trial_list as $trial=>$val) {
      echo "<td style=\"font-size:10px; word-break:break-all\">$val";
  }
  while ($row=mysql_fetch_row($result)) {
      $line_name = $row[0];
      $marker_name = $row[1];
      $alleles = $row[2];
      $trial = $row[3];
      if ($line_name == $prev) {
        $allele_ary[$trial] = $alleles;
      } else {
        if ($count > 0) {
          echo "<tr><td>$prev";
          foreach ($allele_ary as $t1=>$a) {
              echo "<td>$a";
          }
          echo "\n";
        }
        $prev = $line_name;
        $allele_ary = $empty;
        $allele_ary[$trial] = $alleles;
        $count++;
    }
    //  echo "<tr><td>$line_name<td>$alleles<td>$trial\n";
  }
} else {
    echo "Top 100 conflicts\n";
    echo "<table>";
    echo "<tr><td>marker name<td>total<br>measured<td>conflicts<td>percent<br>conflicts\n";
    $sql = "select marker_uid, count(marker_uid) as temp from allele_conflicts group by marker_uid order by temp DESC limit 100";
    $result = mysql_query($sql) or die(mysql_error());
    while ($row=mysql_fetch_row($result)) {
      $uid = $row[0];
      $count = $row[1];
      $total = 0;
      $sql = "select alleles from allele_bymarker where marker_uid = $uid";
      $result2 = mysql_query($sql) or die(mysql_error());
      if ($row2=mysql_fetch_row($result2)) {
           $alleles = $row2[0];
           $outarray = explode(',', $alleles);
           foreach ($outarray as $allele) {
               if ($allele != '') {
                   $total++;
               }
           }
       }
       $perc = round(100*$count/$total,2);
      echo "<tr><td><a href=genotyping/sum_markers.php?uid=$uid>$name_list[$uid]</a><td>$total<td>$count<td>$perc\n";
    }
}
echo "</table></div>";
include $config['root_dir'].'theme/footer.php';
