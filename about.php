<?php require 'config.php';
include($config['root_dir'].'includes/bootstrap.inc');
include($config['root_dir'].'theme/normal_header.php'); ?>

<h1>About T3</h1>
<div class="section">
<h3>Project Description</h3>
  <p>The Triticeae Toolbox (T3) is the web portal for data generated by
  the <a href="http://triticeaecap.org/" title="">Triticeae Coordinated
  Agricultural Project</a> (T-CAP), funded by
  the <a href="http://usda.gov/wps/portal/usda.usdahome">National
  Institute for Food and Agriculture (NIFA)</a> of the United States
  Department of Agriculture (USDA).  The database, developed initially
  as <b>The Hordeum Toolbox</b> (THT) to hold barley data generated by
  the <a href="http://barleycap.org">Barley CAP</a> project (2006-2010)
  is being maintained as <b>T3 Barley</b> while a sister database <b>T3
  Wheat</b> will hold data generated for <i>Triticum</i> spp.  Both are
  being enhanced in database performance, community curation and user
  tools.  T3 contains germplasm line information, pedigree, genotype and
  phenotypic data from breeding programs participating in the CAP and
  core germplasm collections maintained by
  the <a href="http://www.ars.usda.gov/Main/docs.htm?docid=21891">USDA
  National Small Grains Collection.</a>

<h3>Dataset Acknowledgments</h3>
<b>9K wheat iSelect assay</b>
<p>The 9,000 SNP wheat iSelect assay was designed by research groups
    funded by the USDA National Institute of Food and Agriculture (grant
    CRIS0219050; PI: E. Akhunov; co-PIs: S. Chao, G. Brown-Guedira,
    D. See, M. Sorrells) and the Grains Research and Development
    Corporation (GRDC), Australia (PI: Matthew Hayden). The details of
    assay design can be obtained from
    the <a href="http://wheatgenomics.plantpath.ksu.edu/snp/">USDA wheat
    SNP development project</a>
    and <a href="http://wheatgenomics.plantpath.ksu.edu/IWSWG/">International
    Wheat SNP Working Group</a> websites.


<h3>T3 Team</h3>
<p>
<style type="text/css">
#thtteamtbl td{text-align:left} .strong{font-weight:bold}
</style>
<table id="thtteamtbl" border="0" cellpadding="0" cellspacing="0">
  <thead>
    <tr>
      <th>Name</th><th>Affiliation</th><th>Role</th>
    </tr>
  </thead>
  <tr><td class="strong">Jean-Luc Jannink</td><td>USDA-ARS, NAA, RWHCAH,
      Ithaca, NY</td><td>Project coordinator</td></tr>
  <tr><td class="strong">Mark Sorrells</td><td>Cornell University, Ithaca,
      NY</td><td>Project coordinator</td></tr>
  <tr><td class="strong">Clay Birkett</td><td>USDA-ARS, NAA, RWHCAH,
      Ithaca, NY</td><td>Database programming and development</td></tr>
  <tr><td class="strong">Victoria Carollo Blake</td><td>USDA-ARS, WRRC,
      Albany, CA</td><td>Data curator</td></tr>
  <tr><td class="strong">Dave Matthews</td><td>USDA-ARS, PWA, Ithaca,
      NY</td><td>Database development, GrainGenes collaborator</td></tr>
  <tr><td class="strong">Shiaoman Chao</td><td>USDA-ARS Biosciences
      Research Lab, Fargo, ND</td><td>SNP data production and
      curation</td></tr>
  <tr><td class="strong">Peter Bradbury</td><td>USDA-ARS, NAA, RWHCAH,
      Ithaca, NY</td><td>Pedigree information and links
      to <a href="http://www.maizegenetics.net/index.php?page=bioinformatics/tassel/index.html"
	    title="">TASSEL</a></td></tr>
  <tr><td class="strong">Mike Bonman</td><td>USDA-ARS, Aberdeen,
      ID</td><td>GRIN collaboration</td></tr>
  <tr><td class="strong">Harold Bockelman</td><td>USDA-ARS, Aberdeen,
      ID</td><td>GRIN collaboration</td></tr>
  <tr><td class="strong">Tim Close</td><td>Botany and Plant
      Sciences<br/>University of California<br/>Riverside, CA</td><td>Assembly
      and SNP context information from <a href="http://harvest.ucr.edu/"
					  title="">HarvEST: Barley</a></td></tr>
  <tr><td class="strong"><a href="http://triticeaecap.org/about/t-cap-directory/">T-CAP
	Participants</a></td><td>Throughout the U.S.A.</td><td>Data collection
      and contribution</td></tr>
</table>
</p>

<h3>THT Alumni</h3>
<style type="text/css">
#thtteamtbl td{text-align:left} .strong{font-weight:bold}
</style>
<table id="thtteamtbl" border="0" cellpadding="0" cellspacing="0">
  <thead>
    <tr>
      <th>Name</th><th>Affiliation</th><th>Role</th>
    </tr>
  </thead>
  <tr><td class="strong">Julie A. Dickerson</td><td>Electrical and
      Computer Engineering, Iowa State University</td><td>Principal
      Investigator<td></tr>
  <tr><td class="strong">Roger P. Wise</td><td>USDA-ARS<br/>Department of
      Plant Pathology<br/>Iowa State University</td><td>Principal
      Investigator</td></tr>
  <tr><td class="strong">Jennifer Kling</td><td>Dept. of Crop and Soil
      Science Oregon State University</td><td>Phenotype and pedigree data
      curation </td></tr>
  <tr><td class="strong">Shreyartha Mukherjee</td><td>Bioinformatics and
      Computational Biology, Iowa State
      University</td><td>Developer/Bioinformatics</td></tr>
  <tr><td class="strong">Kartic Ramesh</td><td>Computer Science, Iowa
      State University</td><td>Developer</td></tr>
  <tr><td class="strong">Gavin Monroe</td><td>Software Engineering, Iowa
      State University</td><td>Developer</td></tr>
  <tr><td class="strong">Ethan Wilder</td><td>Computer Engineering, Iowa
      State University</td><td>Developer</td></tr>
  <tr><td class="strong">Yong Huang</td><td>Bioinformatics and
      Computational Biology, Iowa State University</td><td>
      Developer/Bioinformatics</td></tr>
</table>

<p>
<h3>Collaborators</h3>
<a href="http://bioinf.scri.ac.uk/germinate" title="">SCRI Germinate</a>
Development Team (David Marshall, Paul Shaw)<br/>
<a href="http://www.plexdb.org/" title="">PLEXdb</a> Development Team at
Iowa State University (Ethy Cannon and Sudhansu Dash)<br/>
<a href="http://www.gramene.org/" title="">Gramene</a> Database (Doreen
Ware)<br/>
<a href="http://wheat.pw.usda.gov/" title="">GrainGenes</a> Database
(David Matthews) USDA/ARS, Cornell University<br/>

<p>
<h3>Software availability</h3>
The T3 software is available under the GNU General Public License
(<a href="docs/LICENSE">LICENSE</a>) and may be downloaded from
<a href="https://github.com/Dave-Matthews/The-Triticeae-Toolbox">github</a>.
It requires Unix, Apache, MySQL, and PHP.  Details are in
the <a href="docs/INSTALL.html">INSTALL</a> document.<br>
The database schema in .sql format
is <a href="docs/T3wheat_schema.sql">here</a>. Graphical diagrams are
available in MySQL Workbench (<a href="docs/T3wheat_schema_May2012.mwb">.mwb</a>)
format and <a href="docs/T3wheat_schema_May2012.png">.png</a> format.
</div>
</div>

<?php include($config['root_dir'].'theme/footer.php');?>
