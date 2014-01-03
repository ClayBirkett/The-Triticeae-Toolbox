<?php
/**
 * Header and Menu
 *
 * PHP version 5.3
 *
 * @category PHP
 * @package  T3
 * @author   Clay Birkett <clb343@cornell.edu>
 * @license  http://triticeaetoolbox.org/wheat/docs/LICENSE Berkeley-based
 * @version  GIT: 2
 * @link     http://triticeaetoolbox.org/wheat/theme/admin_header.php
 *
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<!-- "chrome=1"is required for X3DOM (WebGL) function in IE with Flash or Chrome Frame. -->
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8,chrome=1">
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
  <meta name="copyright" content="Copyright (C) 2008 Iowa State University. All rights reserved." >
  <meta name="expires" content="<?php echo date("D, d M Y H:i:s", time()+6*60*60); ?> GMT">
  <meta name="keywords" content="hordeum,toolbox,barley,tht,database" >
  <meta name="revisit-After" content="1 days" >

  <base href="<?php echo $config['base_url']; ?>" >
  <link rel="stylesheet" type="text/css" href="<?php echo $config['base_url']; ?>theme/new.css">
  <script type="text/javascript" src="<?php echo $config['base_url']; ?>includes/core.js"></script>
  <script type="text/javascript" src="<?php echo $config['base_url']; ?>theme/new.js"></script>
  <script type="text/javascript" src="<?php echo $config['base_url']; ?>theme/js/prototype.js"></script>
  <script type="text/javascript" src="<?php echo $config['base_url']; ?>theme/js/scriptaculous.js"></script>

<?php
   connect();
   // clear session if it contains variables from another database
   $database = mysql_grab("select value from settings where name='database'");
   $temp = $_SESSION['database'];
   if (empty($database)) {
     //error, settings table should have this entry
   } elseif ($temp != $database) {
     session_unset();
   }
   $_SESSION['database'] = $database;
   // Create <title> for browser to show.
   $title = mysql_grab("select value from settings where name='title'");
   if (empty($title))
     $title = "The Triticeae Toolbox";
   echo "<title>$title</title>";
   global $usegbrowse;

if (isset($usegbrowse) && $usegbrowse)
  require_once $config['root_dir'] . 'includes/gbrowse-deps.inc';
?>
</head>
<body onload="javascript:setup();<?php
if (isset($usegbrowse) && $usegbrowse)
  echo " Overview.prototype.initialize(); Details.prototype.initialize()"; ?>">
  <?php 
  require_once $config['root_dir'].'includes/analyticstracking.php';
  if (isset($usegbrowse) && $usegbrowse)
    echo <<<EOD
      <script>
      var balloon500 = new Balloon;
BalloonConfig(balloon500,'GBubble');
balloon500.images              = './gbrowse/images/balloons';
balloon500.balloonImage        = 'balloon.png';
balloon500.ieImage             = 'balloon_ie.png';
balloon500.upLeftStem          = 'up_left.png';
balloon500.downLeftStem        = 'down_left.png';
balloon500.upRightStem         = 'up_right.png';
balloon500.downRightStem       = 'down_right.png';
balloon500.closeButton         = 'close.png';
balloon500.maxWidth = 500;
balloon500.delayTime = 50;
var balloon = new Balloon;
BalloonConfig(balloon,'GBubble');
balloon.images              = './gbrowse/images/balloons';
balloon.balloonImage        = 'balloon.png';
balloon.ieImage             = 'balloon_ie.png';
balloon.upLeftStem          = 'up_left.png';
balloon.downLeftStem        = 'down_left.png';
balloon.upRightStem         = 'up_right.png';
balloon.downRightStem       = 'down_right.png';
balloon.closeButton         = 'close.png';
balloon.delayTime = 50;
</script>
EOD;
?>
<div id="container">
  <div id="barleyimg">
  </div>
  <h1 id="logo">
  The Triticeae Toolbox
  </h1>
  <div id="util">
  <div id="utilright">
  </div>
  <a href="./feedback.php">Contact Us</a>
  </div>

<?php
  //The navigation tab menus 
  //Tooltips:
  $lang = array(
      "desc_sc1" => "Search by germplasm and phenotype information",
      "desc_sc2" => "Credits, data status ... ",
      "desc_sc3" => "Search by genotyping information",
      "desc_sc4" => "Search by Expression Related information.",
      "desc_sc5" => "Database administration",
      "desc_sc6" => "Visualization tools",
  );
?>
<div id="nav">
  <ul>
    <li>
      <a href="">Home</a>
    <li><a href="" title="Lines and Phenotypes">Select</a>
      <ul>
	<li>
          <a href="<?php echo $config['base_url']; ?>downloads/select_all.php" title="Lines and Phenotypes">
            Wizard (Lines, Traits, Trials)</a>
	  <!-- <a href="<?php echo $config['base_url']; ?>pedigree/line_selection.php" title="Select by name, source, or simply-inherited characters"> -->
	  <a href="<?php echo $config['base_url']; ?>pedigree/line_properties.php" title="Select by name, source, or simply-inherited characters">
	    Lines by Properties</a>
	<li>
	  <a href="<?php echo $config['base_url']; ?>phenotype/compare.php" title="Select within a range of trait values">
	    Lines by Phenotype</a>
	<li><a href="<?php echo $config['base_url']; ?>haplotype_search.php" title="Select desired alleles for a set of markers">
	    Lines by Haplotype</a>
        <li><a href="<?php echo $config['base_url']; ?>downloads/select_genotype.php" title="Select by Genotype Experiment">
            Lines by Genotype Experiment</a>
  <?php 
  /* if( authenticate( array(USER_TYPE_PUBLIC, USER_TYPE_PARTICIPANT, USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR ) ) ):  */
  /* Everybody is USER_TYPE_PUBLIC.  Require he be signed in (therefore registered). */
  if( loginTest2() ): 
?>
	<li><a href="<?php echo $config['base_url']; ?>myown/panels.php" title="Panels I created"><font color=green>My Line Panels</font></a>
        <li><a href="<?php echo $config['base_url']; ?>genotyping/panels.php" title="Panels I created"><font color=green>My Marker Panels</font></a>
 <?php endif ?>
	<li>
	  <a href="<?php echo $config['base_url']; ?>phenotype/phenotype_selection.php" title='"Phenotype" = a Trait value in a particular Trial'>
	    Traits and Trials</a>
	<li>
	  <a href="<?php echo $config['base_url']; ?>genotyping/marker_selection.php" title="Select by name or map position">
	    Markers</a>
        <li>
          <a href="<?php echo $config['base_url']; ?>maps/select_map.php" title="Select genetic map">Genetic Map</a>
        <li>
          <a href="<?php echo $config['base_url']; ?>downloads/clear_selection.php" title="Clear selection">Clear selection</a>
      </ul>
    <li><a href="" title="<?php echo $lang["desc_sc6"]; ?>">Analyze</a>
      <ul>
	<li><a href="<?php echo $config['base_url']; ?>cluster_lines.php" title="Genetic structure">Cluster Lines by Genotype</a>
        <li><a href="<?php echo $config['base_url']; ?>cluster_lines3d.php" title="Genetic structure">Cluster Lines 3D (pam)</a>
 	<li><a href="<?php echo $config['base_url']; ?>cluster_lines4d.php" title="Genetic structure">Cluster Lines 3D (hclust)</a>
	<li><a href="<?php echo $config['base_url']; ?>Index/traits.php" title="Combination of traits">Selection Index</a>
        <li><a href="<?php echo $config['base_url']; ?>analyze/histo.php" title="Histogram">Traits and Trials Histogram</a>
        <li><a href="<?php echo $config['base_url']; ?>curator_data/cal_index.php" title="Canopy Spectral Reflectance">Canopy Spectral Reflectance</a>
        <li><a href="<?php echo $config['base_url']; ?>gensel.php" title="Genomic selection">Genomic Association and Prediction</a>
        <li><a href="<?php echo $config['base_url']; ?>analyze/compare_index.php" title="Compare Trait value for 2 Trials">Compare Trials</a>
	<li>
	  <a href="<?php echo $config['base_url']; ?>pedigree/pedigree_tree.php" title="Show pedigree annotated with alleles of selected markers ">
	    Track Alleles through Pedigree</a>
	<li><a href="<?php echo $config['base_url']; ?>pedigree/parse_pedigree.php" title="Parse a pedigree string in Purdy notation">Parse Purdy Pedigrees</a>
	<li><a href="<?php echo $config['base_url']; ?>genotyping/allele_conflicts.php" title="Disagreements among repeated genotyping experiments">Allele Data Conflicts</a>
	<li><a href="<?php echo $config['base_url']; ?>viroblast" title="Find mapped sequences similar to yours">
	    BLAST Search against Markers</a>
        <li><a href="<?php echo $config['base_url']; ?>pedigree/pedigree_markers.php" title="Show haplotype and phenotype for selected lines and markers">Haplotype Data</a>
        <li><a href="<?php echo $config['base_url']; ?>downloads/downloads_tassel.php" title="Open TASSEL with selected data">Open TASSEL</a>
	  <!--  <li><a href="<?php echo $config['base_url']; ?>not_yet.php" title="Markers polymorphic for a pair of lines">Marker Polymorphisms</a> -->
      </ul>
    <li><a href="" title="">Download</a>
      <ul>
	<li><a href="<?php echo $config['base_url']; ?>downloads/downloads.php" title="Tassel format">
            Genotype and Phenotype Data</a>
	<!-- <li><a href="<?php echo $config['base_url']; ?>flapjack_download.php" title="Flapjack format">
            Genotype Data</a> -->
	  <!-- dem 12jan12 Needs data correctness check. -->
	<li><a href="<?php echo $config['base_url']; ?>snps.php" title="Context sequences and A/B => nucleotide translation">
	    SNP Alleles and Sequences</a> 
	<li><a href="<?php echo $config['base_url']; ?>maps.php" title="Genetic Maps">Genetic Maps</a>
      </ul>

  <?php 
  //  if( authenticate( array( USER_TYPE_PARTICIPANT, USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR ) ) ): 
  if( authenticate( array( USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR ) ) ): 
  ?> 
   <li> <a href="" title="Add, edit or delete data">Curate</a>
      <ul>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_line_names.php" title="Must precede loading data about the lines">
      Lines</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_pedigree_router.php" title="Pedigree information about the lines, optional">
      Pedigrees</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_annotations_upload_router.php" title="Descriptions of phenotype experiments, must precede loading results">
      Phenotype Trials</a></li>
      <!-- <li><a href="<?php echo $config['base_url']; ?>curator_data/input_experiments_upload_router.php" title="Phenotype data"> -->
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_experiments_upload_excel.php" title="Phenotype data">
      Phenotype Results</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_csr_router.php" title="Phenotype CSR data">
      CSR Data</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/fieldbook_export.php" title="Phenotype Tablet tools">Tablet Tools</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/delete_experiment.php" title="Careful!">
      Delete Trials</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_trait_router.php" title="Must precede loading data about the traits">
      Traits and Genetic Characters</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/genotype_annotations_upload.php" title="Add Genotype Annotations Data">
      Genotype Experiments</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/genotype_data_upload.php" title="Add Genotyping Result Data">
      Genotype Results </a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/input_map_upload.php" title="Genetic maps of the markers">
      Maps</a></li>
      <li><a href="<?php echo $config['base_url']; ?>curator_data/markers_upload.php" title="Must precede loading data about the markers">
      Markers</a></li>
      <li><a href="<?php echo $config['base_url']; ?>login/edit_programs.php">
      CAP Data Programs</a></li>
      <!-- Too dangerous. -->
      <!-- <li><a href="<?php echo $config['base_url']; ?>login/edit_anything.php"> -->
      <!-- Anything!</a></li> -->
      </ul>
      <?php endif ?>

      <?php if( authenticate( array( USER_TYPE_ADMINISTRATOR ) ) ): ?>
  <li>
    <a href="" title="<?php echo $lang["desc_sc5"]; ?>">Administer</a>
    <ul>
      <li><a href="<?php echo $config['base_url']; ?>login/edit_users.php" title="No deletion yet">Edit Users</a>
      <li><a href="<?php echo $config['base_url']; ?>dbtest/" title="Table Status">Table Status</a>
      <li><a href="<?php echo $config['base_url']; ?>dbtest/backupDB.php" title="Full Database Backup">Full Database Backup</a>
      <li><a href="<?php echo $config['base_url']; ?>login/input_gateway.php" title="Data Input Gateway">Data Input Gateway</a>
      <li><a href="<?php echo $config['base_url']; ?>login/export_gateway.php" title="Data Export Gateway">Data Export Gateway</a>
      <li><a href="<?php echo $config['base_url']; ?>login/cleanup_temporary_dir.php" title="Clean up temporary files">Clean up temporary files</a>
      <li><a href="http://thehordeumtoolbox.org/webalizer/" title="Webalizer old" target="_blank">Usage, wheat.pw.usda.gov</a>
      <li><a href="http://triticeaetoolbox.org/webalizer/" title="Webalizer new" target="_blank">Usage, tcap</a>
      <li><a href="http://google.com/analytics/web/?hl=en#home/a37631546w66043588p67910931/" title="Google Analytics, if you're permitted" target="_blank">Usage Analytics</a>
    </ul>
  </li>
<?php endif; ?>

  <?php  			
//   if( authenticate( array( USER_TYPE_PARTICIPANT, USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR ) ) ): 
  if( authenticate( array( USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR ) ) ): 
    ?>
  <li> <a href="" title="Manage access to my data">Share data</a>
  <ul>
  <li><a href="<?php echo $config['base_url']; ?>sharegroup.php">Manage access to my data</a>
  </ul>
  <?php endif ?>

  <li>
  <a href="" title="<?php echo $lang["desc_sc2"]; ?>">About T3</a>
  <ul>
    <li><a href="<?php echo $config['base_url']; ?>about.php" title="Description, contributors">Overview</a>
    <li><a href="<?php echo $config['base_url']; ?>t3_report.php" title="Current summary of data loaded">Content Status</a>
    <li><a href="<?php echo $config['base_url']; ?>traits.php" title="Traits and units used">Trait Descriptions</a>
    <li><a href="<?php echo $config['base_url']; ?>properties.php" title="Environment-independent line properties">Genetic Character Descriptions</a>
    <li><a href="<?php echo $config['base_url']; ?>all_breed_css.php" title="Sources of the data">CAP Data Programs</a>
    <li><a href="<?php echo $config['base_url']; ?>toronto.php" title="Toronto Statement">Data Usage Policy</a>
    <!-- <li><a href="<?php echo $config['base_url']; ?>acknowledge.php" title="Contributions from other projects">Acknowledgments</a> -->
    <!-- <li><a href="<?php echo $config['base_url']; ?>termsofuse.php" title="Restrictions on free use of the data">Terms of Use</a> -->
  </ul>
			
			
</ul>
</div>
<div id="quicklinks" style="top:141px">
  <h2>Quick Links </h2>
  <ul>
  <?php if ( isset( $_SESSION['username'] ) && !isset( $_REQUEST['logout'] ) ):  ?>
    <li>
       <a title="Logout" href="<?php echo $config['base_url']; ?>logout.php">Logout <span style="font-size: 10px">(<?php echo $_SESSION['username'] ?>)</span></a>
            <?php else: ?>
    <li>
      <a title="Login" href="<?php echo $config['base_url']; ?>login.php"><strong>Login/Register</strong></a>
   <?php endif; ?>

<?php
   echo "<p><li><b>Current selections:</b>";
   echo "<li><a href='".$config['base_url']."pedigree/line_selection.php'>Lines:</a> ". count($_SESSION['selected_lines']);
   echo "<li><a href='".$config['base_url']."genotyping/marker_selection.php'>Markers:</a> ";
   if (isset($_SESSION['clicked_buttons'])) {
     echo count($_SESSION['clicked_buttons']);
   } else {
     echo "All";
   }
   echo "<li><a href='".$config['base_url']."phenotype/phenotype_selection.php'>Traits:</a> ";
   if (isset($_SESSION['selected_traits'])) {
     echo count($_SESSION['selected_traits']);
   } elseif (isset($_SESSION['phenotype'])) {
     echo count($_SESSION['phenotype']);
   } else {
     echo "0";
   }
   echo "<li><a href='".$config['base_url']."phenotype/phenotype_selection.php'>Trials:</a> " . count($_SESSION['selected_trials']);
?>
			
  </ul>
  <div id="searchbox">
  <form style="margin-bottom:3px" action="search.php" method="post">
  <div style="margin: 0; padding: 0;">
  <input type="hidden" value="Search" >
  <input style="width:170px" type="text" name="keywords" value="Quick search..."
   title="This search term will match on any part of a string.
These regular expressions modify the search
   ^ - beginning of string
   $ - end of string
   . - any single character
   * - zero or more instances of preceding element
   + - one or more instances of preceding element" onfocus="javascript:this.value=''" onblur="javascript:if(this.value==''){this.value='Quick search...';}" >
  </div>
  </form>
  <br></div>

<div  style="margin-left: -25px; width: 170px; padding: 10px 15px;">
<?php include($config['root_dir'].'whatsnew.html'); ?>
</div>

  </div>
  <div id="main">
