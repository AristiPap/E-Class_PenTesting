<?PHP
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

/*
 * Index
 *
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id: password.php,v 1.9 2008-09-28 16:11:08 costas Exp $
 *
 * @abstract Password change component (for platform administrator)
 *
 */
$require_login = true;
$require_admin = TRUE;
$helpTopic = 'Profile';
$require_valid_uid = TRUE;

include '../../include/baseTheme.php';

$nameTools = $langChangePass;
$navigation[] = array("url" => "index.php", "name" => $langAdmin);
$navigation[]= array ("url"=>"./edituser.php", "name"=> $langEditUser);

check_uid();
$tool_content = "";

if (!isset($urlSecure)) {
	$passurl = $urlServer.'modules/admin/password.php';
} else {
	$passurl = $urlSecure.'modules/admin/password.php';
}

if (!isset($changePass)) {
	$tool_content .= "
<form method=\"post\" action=\"$passurl?submit=yes&changePass=do&userid=$userid\">
  <table class=\"FormData\" width=\"99%\" align=\"left\">
  <tbody>
  <tr>
    <th width=\"220\">&nbsp;</th>
    <td><b>$lang_remind_pass</b></td>
  </tr>
  <tr>
    <th class=\"left\">$langNewPass1</th>
    <td><input type=\"password\" size=\"40\" name=\"password_form\" value=\"\"></td>
  </tr>
  <tr>
    <th class=\"left\">$langNewPass2</th>
    <td><input type=\"password\" size=\"40\" name=\"password_form1\" value=\"\"></td>
  </tr>
  <tr>
    <th class=\"left\">&nbsp;</th>
    <td><input type=\"submit\" name=\"submit\" value=\"$langModify\"></td>
  </tr>
  </tbody>
  </table>
</form>";
}

elseif (isset($submit) && isset($changePass) && ($changePass == "do")) {
	$userid = $_REQUEST['userid'];
	if (empty($_REQUEST['password_form']) || empty($_REQUEST['password_form1'])) {
		$tool_content .= mes($langFields, "", 'caution');
		draw($tool_content, 3);
		exit();
	}
	if ($_REQUEST['password_form1'] !== $_REQUEST['password_form']) {
		$tool_content .= mes($langPassTwo, "", 'caution_small');
		draw($tool_content, 3);
		exit();
	}
	//all checks ok. Change password!
	$sql = "SELECT `password` FROM `user` WHERE `user_id`='$userid'";
	$result = db_query($sql, $mysqlMainDb);
	$myrow = mysql_fetch_array($result);
	$old_pass_db = $myrow['password'];
	$new_pass = md5($_REQUEST['password_form']);
	$sql = "UPDATE `user` SET `password` = '$new_pass' WHERE `user_id` = '$userid'";
	db_query($sql, $mysqlMainDb);
	$tool_content .= mes($langPassChanged, $langHome, 'success_small');
	draw($tool_content, 3);
	exit();
}

draw($tool_content, 3);
// display message
function mes($message, $urlText, $type) {
	global $urlServer, $langBack, $userid;

 	$str = "<p class='$type'>$message<br /><a href='$urlServer'>$urlText</a><br /><a href='$_SERVER[PHP_SELF]?userid=$userid'>$langBack</a></p><br />";
	return $str;
}

?>
