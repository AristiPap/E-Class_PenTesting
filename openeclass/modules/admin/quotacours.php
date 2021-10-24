<?php
/*========================================================================
*   Open eClass 2.3
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2010  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*			Yannis Exidaridis <jexi@noc.uoa.gr>
*			Alexandros Diamantidis <adia@noc.uoa.gr>
*			Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address: 	GUnet Asynchronous eLearning Group,
*  			Network Operations Center, University of Athens,
*  			Panepistimiopolis Ilissia, 15784, Athens, Greece
*  			eMail: info@openeclass.org
* =========================================================================*/


/*===========================================================================
	quotacours.php
	@last update: 31-05-2006 by Pitsiougas Vagelis
	@authors list: Karatzidis Stratos <kstratos@uom.gr>
		       Pitsiougas Vagelis <vagpits@uom.gr>
==============================================================================
        @Description: Edit quota of a course

 	This script allows the administrator to edit the quota of a selected
 	course

 	The user can : - Edit the quota of a course
                 - Return to edit course list

 	@Comments: The script is organised in four sections.

  1) Get course quota information
  2) Edit that information
  3) Update course quota
  4) Display all on an HTML page

==============================================================================*/

/*****************************************************************************
		DEAL WITH LANGFILES, BASETHEME, OTHER INCLUDES AND NAMETOOLS
******************************************************************************/

// Check if user is administrator and if yes continue
// Othewise exit with appropriate message
$require_admin = TRUE;
// Include baseTheme
include '../../include/baseTheme.php';
if(!isset($_GET['c'])) { die(); }
// Define $nameTools
$nameTools = $langQuota;
$navigation[] = array("url" => "index.php", "name" => $langAdmin);
$navigation[] = array("url" => "listcours.php", "name" => $langListCours);
$navigation[] = array("url" => "editcours.php?c=".htmlspecialchars($_GET['c']), "name" => $langCourseEdit);
// Initialise $tool_content
$tool_content = "";

/*****************************************************************************
		MAIN BODY
******************************************************************************/
// Initialize some variables
$searchurl = "";
$quota_info = "";

// Define $searchurl to go back to search results
if (isset($search) && ($search=="yes")) {
	$searchurl = "&search=yes";
}
// Update course quota
if (isset($submit))  {
	$dq = $dq * 1000000;
        $vq = $vq * 1000000;
        $gq = $gq * 1000000;
        $drq = $drq * 1000000;
  // Update query
	$sql = mysql_query("UPDATE cours SET doc_quota='$dq',video_quota='$vq',group_quota='$gq',dropbox_quota='$drq' 			WHERE code='".mysql_real_escape_string($_GET['c'])."'");
	// Some changes occured
	if (mysql_affected_rows() > 0) {
		$tool_content .= "<p>".$langQuotaSuccess."</p>";
	}
	// Nothing updated
	else {
		$tool_content .= "<p>".$langQuotaFail."</p>";
	}

}
// Display edit form for course quota
else {
	// Get course information
	$q = mysql_fetch_array(mysql_query("SELECT code,intitule,doc_quota,video_quota,group_quota,dropbox_quota
			FROM cours WHERE code='".mysql_real_escape_string($_GET['c'])."'"));
	$quota_info .= "<i>".$langTheCourse." <b>".$q['intitule']."</b> ".$langMaxQuota;
	$dq = $q['doc_quota'] / 1000000;
	$vq = $q['video_quota'] / 1000000;
	$gq = $q['group_quota'] / 1000000;
	$drq = $q['dropbox_quota'] / 1000000;
	// Constract the edit form
	$tool_content .= "
<form action=".$_SERVER['PHP_SELF']."?c=".htmlspecialchars($_GET['c'])."".$searchurl." method=\"post\">
  <table class=\"FormData\" width=\"99%\" align=\"left\">
  <tbody>
  <tr>
    <th width=\"220\">&nbsp;</th>
    <td><b>".$langQuotaAdmin."</b></td>
  </tr>
  <tr>
    <th>&nbsp;</th>
    <td>".$quota_info."</td>
  </tr>
  <tr>
    <th class=\"left\">$langLegend <b>$langDoc</b>:</th>
    <td><input type='text' name='dq' value='$dq' size='4' maxlength='4'> Mb.</td>
  </tr>
  <tr>
    <th class=\"left\">$langLegend <b>$langVideo</b>:</th>
    <td><input type='text' name='vq' value='$vq' size='4' maxlength='4'> Mb.</td>
  </tr>
  <tr>
    <th class=\"left\">$langLegend <b>$langGroups</b>:</th>
    <td><input type='text' name='gq' value='$gq' size='4' maxlength='4'> Mb.</td>
  </tr>
  <tr>
    <th class=\"left\">$langLegend <b>$langDropBox</b>:</th>
    <td><input type='text' name='drq' value='$drq' size='4' maxlength='4'> Mb.</td>
  </tr>
  <input type='hidden' name='c' value='".htmlspecialchars($_GET['c'])."'>
  <tr>
    <th>&nbsp;</th>
    <td><input type='submit' name='submit' value='$langModify'></td>
  </tr>
  </tbody>
  </table>
</form>\n";
}
// If course selected go back to editcours.php
if (isset($_GET['c'])) {
	$tool_content .= "<p align=\"right\"><a href=\"editcours.php?c=".htmlspecialchars($_GET['c'])."".$searchurl."\">".$langBack."</a></p>";
}
// Else go back to index.php directly
else {
	$tool_content .= "<p align=\"right\"><a href=\"index.php\">".$langBackAdmin."</a></p>";
}

/*****************************************************************************
		DISPLAY HTML
******************************************************************************/
// Call draw function to display the HTML
// $tool_content: the content to display
// 3: display administrator menu
// admin: use tool.css from admin folder
draw($tool_content,3,'admin');
?>
