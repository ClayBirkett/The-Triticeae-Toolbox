<?php

#######################################################################################
# ViroBLAST
# viroblast.php
# Copyright � University of Washington. All rights reserved.
# Written by Wenjie Deng in the Department of Microbiology at University of Washington.
#######################################################################################

require 'config.php';

include $config['root_dir'] . 'includes/bootstrap_curator.inc';
include $config['root_dir'] . 'theme/admin_header.php';
?>

<script type="text/javascript" src='viroblast/javascripts/viroblast.js'></script>

<form enctype='multipart/form-data' name='blastForm' action = 'viroblast/blastresult.php' method='post'>
<div class='box'>
<div id="title">
<span><strong>Basic Search - using default BLAST parameter settings</strong></span>
</div>

<p>Enter query sequences here in <a href='viroblast/docs/parameters.html#format'>Fasta format</a></p> 

<p><textarea name='querySeq' rows='6' cols='66'></textarea></p>
<p>Or upload sequence fasta file: <input type='file' name='queryfile'></p>

<p><table border=0 style='font-size: 12px'>
<tr><td valign=top>
<a href=viroblast/docs/blast_program.html>Program</a> <select id="programList" name='program' onchange="changeDBList(this.value, this.form.dbList, dblib[programNode.value]); changeParameters(this.value, 'adv_parameters');">
<option value='blastn' selected>blastn
</select></td>

<td valign=top>&nbsp;&nbsp;&nbsp;
<a href=viroblast/docs/blast_databases.html>Database(s) </a>
</td><td>
<?php
if (authenticate(array(USER_TYPE_CURATOR, USER_TYPE_ADMINISTRATOR))) {
    $fp = fopen("./viroblastLocal.ini", "r");
} else {
    $fp = fopen("./viroblast.ini", "r");
}
if (!$fp) {
    echo "<p><strong> Error: Couldn't open file viroblast.ini </strong></p></body></html>";
    exit;
}
while (!feof($fp)) {
    $blastdbstring = rtrim(fgets($fp));
    if (!$blastdbstring) {
        continue;
    }
	if (!preg_match("/^\s*#/", $blastdbstring)) {
		$blastdbArray = preg_split("/:/", $blastdbstring);	
		$blastProgram = $blastdbArray[0];
		$dbString = $blastdbArray[1];
		
		if ($blastProgram == "blast+") {
			echo "<input type='hidden' name= 'blastpath' value='$dbString'>";
		}else {
			if (preg_match("/^\s*(.*?)\s*$/", $blastProgram, $match)) {
				$blastProgram = $match[1];
			}
			if (preg_match("/^\s*(.*?)(\s*|\s*,\s*)$/", $dbString, $match)) {
				$dbString = $match[1];
			}
			$dbString = preg_replace("/\s*=>\s*/", "=>", $dbString);
			if (preg_match("/,/", $dbString, $match)) {
				$dbString = preg_replace("/\s*,\s*/", ",", $dbString);
			}		
			echo "<input id='$blastProgram' type='hidden' name='blastdb[]' value='$dbString'>";
		}
    }
}
fclose($fp);

?>
<select id="dbList" size=4 multiple="multiple" name ="patientIDarray[]">
<script type="text/javascript">
	var dblib = Array();
	var programNode = document.getElementById("programList");
	var blastndbNode = document.getElementById("blastn");
	var blastpdbNode = document.getElementById("blastp");
	var blastxdbNode = document.getElementById("blastx");
	var tblastndbNode = document.getElementById("tblastn");
	var tblastxdbNode = document.getElementById("tblastx");
	dblib["blastn"] = blastndbNode.value;
	dblib["blastp"] = blastpdbNode.value;
	dblib["blastx"] = blastxdbNode.value;
	dblib["tblastn"] = tblastndbNode.value;
	dblib["tblastx"] = tblastxdbNode.value;
	changeDBList(programNode.value, document.getElementById("dbList"), dblib[programNode.value]);
</script>

</select>
</td></tr></table></p>

<p>And/or upload sequence fasta file: <input type='file' name='blastagainstfile'></p>

<input type='hidden' name='blast_flag' value=1>

<p><input type='button' name="bblast" value='Basic search' onclick="checkform(this.form, this.value)">&nbsp;&nbsp;<input type='reset' value='Reset' onclick="window.location.reload();"></p>

<div id="title">
    <span><strong>Advanced Search - setting your favorite parameters below</strong></span>
</div>

<div id="adv_parameters">

<script type="text/javascript">
    var programNode = document.getElementById("programList");
    changeParameters(programNode.value, 'adv_parameters');
</script>

</div>
<p><input type='button' name="ablast" value='Advanced search' onclick="checkform(this.form, this.value)">&nbsp;&nbsp;<input type='reset' value='Reset' onclick="window.location.reload();"></p>
</form>
<br>
<hr>
<p style="font-size: 8pt;">
<!-- <a href=<?php echo $config['base_url'] ?>viroblast/docs/aboutviroblast.html>ViroBLAST 2.2+</a>  -->
<a href=http://indra.mullins.microbiol.washington.edu/viroblast/viroblast.php>ViroBLAST</a> 2.2+ 
&copy; 2005-2010 University of Washington. All rights reserved. 
&nbsp;<a href=<?php echo $config['base_url'] ?>viroblast/docs/termsofservice.html>Terms of Service</a>
<br>Used under the <a href="<?php echo $config['base_url'] ?>viroblast/License.mail">Academic License</a>. 
ResultGraph feature from <a href="mailto:flemming@ipk-gatersleben.de">Steffen Flemming</a> (thanks!).
</div>
<?php
  $footer_div=1;
include $config['root_dir'].'theme/footer.php'; ?>
