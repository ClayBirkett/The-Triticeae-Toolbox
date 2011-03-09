<?php
require 'config.php';
include($config['root_dir'].'includes/bootstrap.inc');
include($config['root_dir'].'theme/admin_header.php');
?>

<div id="primaryContentContainer">
  <div id="primaryContent">
  <h1>Cluster Lines by Genotype</h1>
  <div class="section">
  <h3>Please wait ...</h3>

  <!-- Strange that this is not working. See http://api.jquery.com/jQuery.get -->
  <script src="./jquery.js" type="text/javascript"></script>
  <script type="text/javascript">
  jQuery.get("cluster_lines.php");
  </script>

<?php
  $linecount = count($_SESSION['selected_lines']);
echo "<br>Retrieving all marker alleles for <b>$linecount</b> lines.<br>";
echo "Retrieval rate is ca. one minute for 500 lines (1.5 million alleles).";
echo "<meta http-equiv='refresh' content='0; url=".$config['base_url']."cluster_lines.php'>";
?>

</div></div></div>
<?php 
$footer_div=1;
include($config['root_dir'].'theme/footer.php'); ?>