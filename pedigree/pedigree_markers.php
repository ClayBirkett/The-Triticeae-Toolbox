<?php
require 'config.php';
/*
 * Logged in page initialization
 */

include($config['root_dir'] . 'includes/bootstrap.inc');
connect();
session_start();

include($config['root_dir'] . 'theme/admin_header.php');

/**
 * Generate the image map
 *
 * @param array $blks the drawing blocks
 * @param string $umapname the name of the image map
 * @return string $mapstr
 */
function get_imagemap (array $blks, $umapname) {
	$imgmap=array();
	foreach ($blks as $blk) {
		if (isset($blk['link']) && $blk['link']!=='' && $blk['link']!='TODO') {
			array_push($imgmap, array('shape'=>'rect',
									  'coords'=>implode(",",$blk['coords']),
									  'href'=>$blk['link'],
									  'alt'=>'',
									  'title'=>$blk['title']));
		}
	}
	$mapstr="<map name=\"$umapname\">";
	foreach ($imgmap as $marr) {
		$mapstr.="<area ";
		foreach ($marr as $mk=>$mv) {
			$mapstr.="$mk=\"$mv\" ";
		}
		$mapstr.=">\n";
	}
	$mapstr.="</map>";
	return $mapstr;
}
?>

<div id="primaryContentContainer">
	<div id="primaryContent">
		<div class="box">
		<h2>Haplotype Data for Selected Lines and Markers</h2>
  <table>
<tr>
<td>
<?php
  if(isset($_SESSION['phenotype'])) {
    $phenotype = $_SESSION['phenotype'];
    $r = mysql_query("select phenotypes_name from phenotypes where phenotype_uid = $phenotype");
    $phenotypename = mysql_result($r,0);
  }
if(isset($_SESSION['experiments'])) {
  $experiments = $_SESSION['experiments'];
 }
if(isset($_SESSION['selected_lines']) && isset($_SESSION['clicked_buttons'])) {
	$slines=$_SESSION['selected_lines'];
	$smkrs_all=$_SESSION['clicked_buttons'];
	$cnt_all=count($smkrs_all);
	$mkrppg=20; // display 20 markers per page
	$page=0; // default page number to 0,
	if (isset($_GET['pagenum'])) $page=$_GET['pagenum'];
	if ($page>floor((count($smkrs_all)-1)/$mkrppg)) $page=floor((count($smkrs_all)-1)/$mkrppg);
	if ($page<0) $page=0;
	$spl_len=$mkrppg;
	if ((count($smkrs_all)-$page*$mkrppg)<$mprppg) $spl_len=count($smkrs_all)-$page*$mkrppg;
	$smkrs=array_splice($smkrs_all, $page*$mkrppg, $spl_len);
	/* the size specifications for the drawing elements */
	$x=20; // the left margin
	$y=20; // the top margin
	$cwd=95; // the cell width for the lines
	$cht=16; // the cell height for the lines
	$twd=40; // the cell width for trait values
	$nwd=16; // the width of a SNP character
	$nht=33; // the height of a SNP character
	$hlw=1; // half of the line width;
	$bmg=1; // margins
	$cmg=10; // margins for characters?
	$disp_len=14; // length of line name
	$imw=$x+$cwd+count($smkrs)*$nwd+100;
	$imh=$y+count($slines)*$cht+100;

	/* the drawing blocks, texts and lines */
	$blks=array();
	$dlns=array();
	$dtxs=array();

	/* draw a more sign if there is more markers outside smkrs */
	if (($page>0) || ($cnt_all>$page*$mkrppg+$spl_len)) {
	$leftlink="";
	$leftsign="start";
	$rightlink="";
	$rightsign="end";
	  if ($page>0) {
	    $leftlink=$_SERVER['PHP_SELF']."?pagenum=".($page-1);
	    $leftsign=" <<";
	  }
	  if ($cnt_all>$page*$mkrppg+$spl_len) {
	    $rightlink=$_SERVER['PHP_SELF']."?pagenum=".($page+1);
	    $rightsign=" >>";
	  }
	  array_push($blks, array('coords'=>array($bmg,$bmg,$cwd-$bmg,$cht-$bmg),
				  'imgclr'=>'im_khaki3',
				  'text'=>$leftsign,
				  'textsize'=>2,
				  'border'=>1,
				  'border_color'=>'im_skyblue',
				  'link'=>$leftlink,
				  'title'=>"Display previous $mkrppg markers"));
	  array_push($blks, array('coords'=>array($cwd+$bmg,$bmg,$cwd+$cwd-$bmg,$cht-$bmg),
				  'imgclr'=>'im_khaki3',
				  'text'=>$rightsign,
				  'textsize'=>2,
				  'border'=>1,
				  'border_color'=>'im_skyblue',
				  'link'=>$rightlink,
				  'title'=>"Display next $mkrppg markers"));
	  array_push($dtxs, array('x'=>2*$cwd, 'y'=>10, 'text'=>$spl_len." markers from number ".($page*$mkrppg)." of total $cnt_all", 'fontsize'=>3, 'text_clr'=>'im_black'));
	  }

	// print $cnt_all." ".($page*$mkrppg+$spl_len)."<br>";
	// print_r($blks);
	/* draw the lines */
	$line_names=array();
	for ($i=0; $i<count($slines); $i++) {
		// get the line_name from line_uid
		$lineuid=$slines[$i];
		$linename="";
		$result=mysql_query("select line_record_name from line_records where line_record_uid=$lineuid") or die("invalid line uid\n");
		while ($row=mysql_fetch_assoc($result)) {
			$linename=$row['line_record_name'];
		}
		array_push($line_names, $linename);
		$dispname=$linename;
		if (strlen($linename)>$disp_len) $dispname=substr($linename, 0, $disp_len)."\\";
		$xcoord=$x;
		$ycoord=$y+$cht*($i+1);
		array_push($blks, array('coords'=>array($xcoord+$bmg,$ycoord+$bmg,$xcoord+$cwd-$bmg,$ycoord+$cht-$bmg),
					'imgclr'=>'im_whitesmoke',
								'text'=>$dispname,
								'textsize'=>2,
							'border'=>0,
								'border_color'=>'im_khaki3',
								'link'=>"pedigree/show_pedigree.php?line=$linename",
								'title'=>$linename));
	}

	/* draw the markers */
	// draw the logo
	$ycoord=$y-14;
	$xcoord=$x+$cwd+10;
	array_push($dtxs, array('x'=>$xcoord, 'y'=>$ycoord, 'text'=>"Markers", 'fontsize'=>2, 'text_clr'=>'im_black'));
	if (isset($phenotypename)) {
	array_push($dtxs, array('x'=>$xcoord + 90, 'y'=>$ycoord, 'text'=>'Trait', 'fontsize'=>2, 'text_clr'=>'im_black'));
	array_push($dtxs, array('x'=>$xcoord + 140, 'y'=>$ycoord, 'text'=>'Expts', 'fontsize'=>2, 'text_clr'=>'im_black'));
	}

	// draw the markers
	$nx=$xcoord;
	$ny=$y;
	for ($i=0; $i<count($smkrs); $i++) {
		$mkrname="";
    	$result=mysql_query("SELECT marker_name from markers where marker_uid=".$smkrs[$i]);
    	if (mysql_num_rows($result)>=1) {
			$row = mysql_fetch_assoc($result);
			$mkrname=$row['marker_name'];
    	}
    	else continue;
    	$xcoord=$nx+$i*$nwd;
    	$ycoord=$ny;
    	array_push($blks, array('coords'=>array($xcoord+1,$ycoord,$xcoord+$nwd-1,$ycoord+14),
							    'imgclr'=>'im_grayblue',
								'text'=>$i+1,
								'textsize'=>2,
								'border'=>0,
								'border_color'=>'im_khaki3',
								'link'=>"view.php?table=markers&name=$mkrname",
								'title'=>$mkrname));

	}

	// draw the allele values
	$line_mkr=array(); // to avoid duplications
	for ($i=0; $i<count($slines); $i++) {
	  $lineuid=$slines[$i];
	  if (array_key_exists($lineuid, $line_mkr)) continue;
	  else {
	    $line_mkr[$lineuid]=1;
	    for ($j=0; $j<count($smkrs); $j++) {
	      $mkruid=$smkrs[$j];
	      $mkrval="";
	      $result=mysql_query("
		select marker_name, line_record_name, allele_1, allele_2 
		from markers as A, genotyping_data as B, alleles as C, tht_base as D, line_records as E
		where A.marker_uid=B.marker_uid 
		and B.genotyping_data_uid=C.genotyping_data_uid 
		and B.tht_base_uid=D.tht_base_uid
		and D.line_record_uid=E.line_record_uid 
		and E.line_record_uid=$lineuid and A.marker_uid=$mkruid
		") 
		or die (mysql_error());
	      if (mysql_num_rows($result)>=1) {
		$row = mysql_fetch_assoc($result);
		$mkrval=$row['allele_1'].$row['allele_2'];
	      }
	      else {
		// print "$linename no marker information\n";
	      }
	      $dispval=$mkrval;
	      $dnx=$nx+$j*$nwd;
	      $dny=$y+7+$cht*($i);
	      $cls=array('AA'=>'im_tomato', 'BB'=>'im_grayblue', 'AB'=>'im_purple', '--'=>'im_whitesmoke', 'N'=>'im_gray');
	      if (! isset($mkrval) || strlen($mkrval)<1) $mkrval="N";
	      array_push($blks, array('coords'=>array($dnx+1,$dny+$cmg,$dnx+$nwd-1,$dny+$nht-$cmg),
				      'imgclr'=>$cls[$mkrval],
				      'text'=>$dispval,
				      'textsize'=>2,
				      'border'=>0,
				      'border_color'=>'im_khaki3',
				      'link'=>'',
				      'title'=>''));
	    }
	  }
	}

	// draw the trait values
	if (isset($phenotype)) {
	  $line_trt=array(); // to avoid duplications
	  $in_these_experiments = "";
	  if (isset($experiments)) {
	    $in_these_experiments = "and tb.experiment_uid in ($experiments)";
	  }
	  for ($i=0; $i<count($slines); $i++) {
	    $lineuid=$slines[$i];
	    if (array_key_exists($lineuid, $line_trt)) continue;
	    else {
	      $line_trt[$lineuid]=1;
	      $trtval = "";
	      // Show mean over selected experiments.
	      $result=mysql_query("
			      select avg(value), count(value)
			      from line_records as lr, phenotype_data as pd, tht_base as tb
			      where lr.line_record_uid = tb.line_record_uid
			      and tb.tht_base_uid = pd.tht_base_uid
			      and pd.phenotype_uid = $phenotype
			      and lr.line_record_uid = $lineuid
                              $in_these_experiments
                              -- and value is not null
			      ") or die (mysql_error());
	      if (mysql_num_rows($result)>=1) {
		$row = mysql_fetch_assoc($result);
		$trtval = $row['avg(value)'];
		$cntval = $row['count(value)'];
		if ($cntval == 0) { $trtval = ""; }
		$dispval = number_format($trtval);
		$dnx=$nx+ 5 * $nwd + 10;  // to be adjusted; replace 5 with number of markers
		$dny=$y+7+$cht*($i);
		array_push($blks, array('coords'=>array($dnx+1,$dny+$cmg,$dnx+$twd-1,$dny+$nht-$cmg),
					'imgclr'=>'im_whitesmoke',
					'text'=>$dispval,
					'textsize'=>2,
					'border'=>0,
					'border_color'=>'im_khaki3',
					'link'=>'',
					'title'=>''));
		$dnx=$dnx+$twd+10;
		array_push($blks, array('coords'=>array($dnx+1,$dny+$cmg,$dnx+20,$dny+$nht-$cmg),
					'imgclr'=>'im_whitesmoke',
					'text'=>$cntval,
					'textsize'=>2,
					'border'=>0,
					'border_color'=>'im_khaki3',
					'link'=>'',
					'title'=>''));
	      }
	    }
	  }
	}

	  // Now do the drawing.
	$_SESSION['draw_map_matrix']=array('image_size'=>array($imw, $imh), 'image_blks'=>$blks, 'image_dlns'=>$dlns, 'image_dtxs'=>$dtxs);
	// print_r($blks);
	// print "<a href=\"http://lab.bcb.iastate.edu/sandbox/yhames04/images/map_image.php\">View Image</a>";  // used for testing
	$imgrand=rand();
	print "<img style=\"border:none\" src=\"".$config['base_url']."images/map_image.php?rand=$imgrand\" usemap='#mapmap' alt=\"Map $mapname \">";
	print get_imagemap($blks, 'mapmap')."\n";

	echo "</td><td valign='top' style='text-align: left'>";

	// Table row 1, column 2.  Print names of markers, trait, trait_codes.
	echo "<b>Markers</b><br>";
 	for ($i=0; $i<count($_SESSION['clicked_buttons']); $i++) {
	  $markeruid = $_SESSION['clicked_buttons'][$i];
	  $r = mysql_query("select marker_name from markers where marker_uid = $markeruid");
	  $markername = mysql_result($r,0);
	  $num = $i + 1;
	  echo "<b>$num</b> $markername<br>";
	}
	echo "<br><b>Trait</b><br>";
	echo "$phenotypename<br>";
	echo "<br><b>Experiments</b><br>";
	// Find which experiments the results were found in.
	$theselines = implode(",", $slines);
	$trials_found = mysql_query("
	    select distinct e.trial_code
	    from line_records as lr, phenotype_data as pd, tht_base as tb, experiments as e
	    where lr.line_record_uid = tb.line_record_uid
	    and tb.tht_base_uid = pd.tht_base_uid
	    and e.experiment_uid = tb.experiment_uid
	    and pd.phenotype_uid = $phenotype
	    and lr.line_record_uid in ($theselines)
	    $in_these_experiments
	    ") or die (mysql_error());
	for ($i=0; $i<mysql_num_rows($trials_found); $i++) {
	  $tf = mysql_fetch_assoc($trials_found);
	  $tfc = $tf['trial_code'];
	  echo "$tfc<br>";
	  //	  echo "$tf['trial_code']<br>";  //??? Why doesn't this work?
	}
	echo "<br><i>Trait value is mean over experiments.</i><br>";

	echo "</td></tr></table>";

}
else if(count($_SESSION['selected_lines']) < 1) {
        echo "<p>No lines have been selected</p><ul><li><a href=\"pedigree/line_selection.php\">Select Lines</a></li><li><a href=\"phenotype/compare.php\">Select Lines by Phenotype</a></li></ul>";
}
else if(count($_SESSION['clicked_buttons']) < 1){
       echo "<p>No markers selected - <a href='genotyping/marker_selection.php'>Select Markers</a></p>";
}
?>

	</div>
</div>
</div>
</div>

<?php include($config['root_dir'] . 'theme/footer.php'); ?>
