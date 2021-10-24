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

/*
 * Announcements Component
 *
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id: announcements.php,v 1.55 2009-09-18 11:08:57 adia Exp $
 * @abstract This component offers several operations regarding a course's announcements.
 * The course administrator can:
 * 1. Re-arrange the order of the announcements
 * 2. Delete announcements (one by one or all at once)
 * 3. Modify existing announcements
 * 4. Add new announcements
 */

$require_current_course = true;
$require_help = true;
$helpTopic = 'Announce';
$guest_allowed = true;

include '../../include/baseTheme.php';
include('../../include/lib/textLib.inc.php');
include('../../include/sendMail.inc.php');

// support for math symbols
include('../../include/phpmathpublisher/mathpublisher.php');

/*
 * *** The following is added for statistics purposes **
 */
include('../../include/action.php');
$action = new action();
$action->record('MODULE_ID_ANNOUNCE');
/*
 */

$nameTools = $langAnnouncements;
$tool_content = $head_content = "";

if ($is_adminOfCourse and
    (isset($_GET['addAnnouce']) or isset($_GET['modify']))) {
	$lang_editor = langname_to_code($language);

        $head_content = <<<hContent
<script type="text/javascript">
        _editor_url  = "$urlAppend/include/xinha/";
        _editor_lang = "$lang_editor";
</script>
<script type="text/javascript" src="$urlAppend/include/xinha/XinhaCore.js"></script>
<script type="text/javascript" src="$urlAppend/include/xinha/my_config.js"></script>
hContent;
}

/*
 * TEACHER ONLY
 */
if ($is_adminOfCourse) { // check teacher status
        $head_content .= '
<script type="text/javascript">
function confirmation (name)
{
	if (name != "all") {
    	if (confirm("' . $langSureToDelAnnounce . ' "+ name + " ?"))
        	{return true;}
    	else
        	{return false;}
	} else {
		if (confirm("' . $langSureToDelAnnounceAll . ' "+" ?"))
        	{return true;}
    	else
        	{return false;}
	}
}

</script>
';

$head_content .= <<<hContent
<script type="text/javascript">
function checkrequired(which, entry) {
	var pass=true;
	if (document.images) {
		for (i=0;i<which.length;i++) {
			var tempobj=which.elements[i];
			if (tempobj.name == entry) {
				if (tempobj.type=="text"&&tempobj.value=='') {
					pass=false;
					break;
		  		}
	  		}
		}
	}
	if (!pass) {
		alert("$langEmptyAnTitle");
		return false;
	} else {
		return true;
	}
}

</script>
hContent;

    $result = db_query("SELECT count(*) FROM annonces WHERE cours_id = $cours_id", $mysqlMainDb);
    list($announcementNumber) = mysql_fetch_row($result);
    mysql_free_result($result);

    $displayAnnouncementList = true;
    $displayForm = true;

    /*----------------------------------------
	MOVE UP AND MOVE DOWN COMMANDS
	--------------------------------------*/
    if (isset($down) && $down) {
        $thisAnnouncementId = $down;
        $sortDirection = "DESC";
    }

    if (isset($up) && $up) {
        $thisAnnouncementId = $up;
        $sortDirection = "ASC";
    }

    if (isset($thisAnnouncementId) && $thisAnnouncementId && isset($sortDirection) && $sortDirection) {
        $result = db_query("SELECT id, ordre FROM annonces WHERE cours_id = $cours_id
		ORDER BY ordre $sortDirection", $mysqlMainDb);

        while (list ($announcementId, $announcementOrder) = mysql_fetch_row($result)) {
            if (isset ($thisAnnouncementOrderFound) && $thisAnnouncementOrderFound == true) {
                $nextAnnouncementId = $announcementId;
                $nextAnnouncementOrder = $announcementOrder;
                db_query("UPDATE annonces SET ordre = '$nextAnnouncementOrder' WHERE id = '$thisAnnouncementId'", $mysqlMainDb);
                db_query("UPDATE annonces SET ordre = '$thisAnnouncementOrder' WHERE id = '$nextAnnouncementId'", $mysqlMainDb);
                break;
            }
            // STEP 1 : FIND THE ORDER OF THE ANNOUNCEMENT
            if ($announcementId == $thisAnnouncementId) {
                $thisAnnouncementOrder = $announcementOrder;
                $thisAnnouncementOrderFound = true;
            }
        }
    }

    /*----------------------------------------
	DELETE ANNOUNCEMENT COMMAND
	--------------------------------------*/

    if (isset($delete) && $delete) {
        $result = db_query("DELETE FROM annonces WHERE id='$delete'", $mysqlMainDb);
        $message = "<p class='success_small'>$langAnnDel</p>";
    }

    /*----------------------------------------
	DELETE ALL ANNOUNCEMENTS COMMAND
	--------------------------------------*/

    if (isset($deleteAllAnnouncement) && $deleteAllAnnouncement) {
        db_query("DELETE FROM annonces WHERE cours_id = $cours_id", $mysqlMainDb);
        $message = "<p class='success_small'>$langAnnEmpty</p>";
    }

    /*----------------------------------------
	MODIFY COMMAND
	--------------------------------------*/

    if (isset($_GET['modify'])) {
        $modify = intval($_GET['modify']);
        // RETRIEVE THE CONTENT OF THE ANNOUNCEMENT TO MODIFY
        $result = db_query("SELECT * FROM annonces WHERE id='$modify'", $mysqlMainDb);
        $myrow = mysql_fetch_array($result);

        if ($myrow) {
            $AnnouncementToModify = $myrow['id'];
	    $contentToModify = unescapeSimple($myrow['contenu']);
            $titleToModify = q($myrow['title']);
            $displayAnnouncementList = true;
        }
    }
    /*----------------------------------------
	SUBMIT ANNOUNCEMENT COMMAND
	--------------------------------------*/

    if (isset($_POST['submitAnnouncement'])) {
        // modify announcement
        $antitle = autoquote($_POST['antitle']);
        $newContent = autoquote($_POST['newContent']);
        if ($id) {
            $id = intval($_POST['id']);
            db_query("UPDATE annonces SET contenu = $newContent,
			title = $antitle, temps = NOW()
			WHERE id = $id", $mysqlMainDb);
            $message = "<p class='success_small'>$langAnnModify</p>";
        }

        // add new announcement
        else {
            // DETERMINE THE ORDER OF THE NEW ANNOUNCEMENT
            $result = db_query("SELECT MAX(ordre) FROM annonces
				WHERE cours_id = $cours_id", $mysqlMainDb);
            list($orderMax) = mysql_fetch_row($result);
            $order = $orderMax + 1;
            // INSERT ANNOUNCEMENT
            db_query("INSERT INTO annonces SET contenu = $newContent,
			title = $antitle, temps = NOW(),
			cours_id = $cours_id, ordre = $order");
        }

        // SEND EMAIL (OPTIONAL)
        if (isset($_POST['emailOption']) and $_POST['emailOption']) {
            $emailContent = autounquote($_POST['antitle']) .
                            "<br><br>" .
                            autounquote($_POST['newContent']);
            $emailSubject = "$professorMessage ($currentCourseID - $intitule)";
            // Select students email list
            $sqlUserOfCourse = "SELECT user.email FROM cours_user, user
                                WHERE cours_id = $cours_id AND cours_user.user_id = user.user_id";
            $result = db_query($sqlUserOfCourse, $mysqlMainDb);

            $countEmail = mysql_num_rows($result); // number of mail recipients

            $invalid = 0;
	    $recipients = array();
            $emailBody = html2text($emailContent);
            $general_to = 'Members of course ' . $currentCourseID;
            while ($myrow = mysql_fetch_array($result)) {
                    $emailTo = $myrow["email"]; 
                    // check email syntax validity
                    if (!email_seems_valid($emailTo)) {
                            $invalid++;
                    } else {
                            array_push($recipients, $emailTo);
                    }
                    // send mail message per 50 recipients
                    if (count($recipients) >= 50) {
                            send_mail_multipart("$prenom $nom", $email,
                                                $general_to,
                                            $recipients, $emailSubject,
                                            $emailBody, $emailContent, $charset);
                            $recipients = array();
                    }
            }
            if (count($recipients) > 0)  {
                    send_mail_multipart("$prenom $nom", $email, $general_to,
                                    $recipients, $emailSubject,
                                    $emailBody, $emailContent, $charset);
            }
            $messageUnvalid = " $langOn $countEmail $langRegUser, $invalid $langUnvalid";
            $message = "<p class='success_small'>$langAnnAdd $langEmailSent<br />$messageUnvalid</p>";
        } // if $emailOption==1
        else {
            $message = "<p class='success_small'>$langAnnAdd</p>";
        }
    } // if $submit Announcement


    // teacher display
    /*----------------------------------------
	DISPLAY ACTION MESSAGE
	--------------------------------------*/
    if (isset($message) && $message) {
        $tool_content .= $message . "<br/>";
        $displayAnnouncementList = true; //do not show announcements
        $displayForm = false; //do not show form
    }


    /*----------------------------------------
	DISPLAY ACTIONS TOOL BAR
	--------------------------------------*/
    $tool_content .= "
      <div id='operations_container'>
        <ul id='opslist'>
          <li><a href='" . $_SERVER['PHP_SELF'] . "?addAnnouce=1'>" . $langAddAnn . "</a></li>";

    if ($announcementNumber > 1 || isset($_POST['submitAnnouncement'])) {
        $tool_content .= "
          <li><a href='$_SERVER[PHP_SELF]?deleteAllAnnouncement=1' onClick='return confirmation('all');'>$langEmptyAnn</a></li>";
    }
    $tool_content .= "
        </ul>
      </div>";

    /*----------------------------------------
	DISPLAY FORM TO FILL AN ANNOUNCEMENT
	(USED FOR ADD AND MODIFY)
	--------------------------------------*/
    if ($displayForm and
        (isset($_GET['addAnnouce']) or isset($_GET['modify']))) {
        // DISPLAY ADD ANNOUNCEMENT COMMAND
        $tool_content .= "<form method='post' action='$_SERVER[PHP_SELF]' onsubmit=\"return checkrequired(this, 'antitle');\">";
        // should not send email if updating old message
        if (isset ($modify) && $modify) {
            $tool_content .= "
      <table width='99%' class='FormData'>
      <tbody>
      <tr>
        <th>&nbsp;</th>
        <td><b>$langModifAnn</b></td>
      </tr>";
            $langAdd = $nameTools = $langModifAnn;
        } else {
		$tool_content .= "
      <table width='99%' class='FormData' align='center'>
      <tbody>
      <tr>
        <th width='220'>&nbsp;</th>
        <td><b>".$langAddAnn."</b></td>
      </tr>";
		$nameTools = $langAddAnn;
        }
	$navigation[] = array("url" => "announcements.php", "name" => $langAnnouncements);
        if (!isset($AnnouncementToModify)) $AnnouncementToModify = "";
        if (!isset($contentToModify)) $contentToModify = "";
        if (!isset($titleToModify)) $titleToModify = "";

        $tool_content .= "
      <tr>
        <th width='150' class='left'>$langAnnTitle:</th>
        <td><input type='text' name='antitle' value='$titleToModify' size='50' class='FormData_InputText' /></td>
      </tr>
      <tr>
        <th class='left'>$langAnnBody:</th>
        <td>
          <table class='xinha_editor'>
          <tr>
            <td><textarea id='xinha' name='newContent'>$contentToModify</textarea></td>
          </tr>
          </table>
        </td>
      </tr>
      <tr>
        <th>&nbsp;</th>
        <td><input type='checkbox' value='1' name='emailOption' /> $langEmailOption</td>
      </tr>
      <tr>
        <th>&nbsp;</th>
        <td><input type='submit' name='submitAnnouncement' value='$langAdd' /></td>
      </tr>
      </tbody>
      </table>
      <input type='hidden' name='id' value='$AnnouncementToModify' />
      </form>
      <br />";
    }

    /*----------------------------------------
	DISPLAY ANNOUNCEMENT LIST
	--------------------------------------*/
    if ($displayAnnouncementList == true) {
        $result = db_query("SELECT * FROM annonces WHERE cours_id = $cours_id ORDER BY ordre DESC", $mysqlMainDb);
        $iterator = 1;
        $bottomAnnouncement = $announcementNumber = mysql_num_rows($result);

	$tool_content .= "<table width='99%' align='left' class='announcements'>";
	if ($announcementNumber > 0) {
		$tool_content .= "<thead><tr><th class='left' colspan='2'><b>$langAnnouncement</b></th>";
		$tool_content .= "<th width='70' class='right'><b>$langActions</b></th>";
		if ($announcementNumber > 1) {
			$tool_content .= "<th width='70'><b>$langMove</b></th>";
		}
		$tool_content .= "</tr></thead>";
	}
	$tool_content .= "<tbody>";
	$k = 0;
	while ($myrow = mysql_fetch_array($result)) {
            // FORMAT CONTENT
            $content = make_clickable($myrow['contenu']);
            $content = nl2br($content);
            // display math symbols (if there are)
            $content = mathfilter($content, 12, "../../courses/mathimg/");
            $myrow['temps'] = nice_format($myrow['temps']);
            if ($k%2==0) {
	           $tool_content .= "\n      <tr>";
	        } else {
	           $tool_content .= "\n      <tr class='odd'>";
            }
            $tool_content .= "
        <td width='1'><img style='padding-top:3px;' src='${urlServer}/template/classic/img/arrow_grey.gif' title='bullet' /></td>
        <td><b>";

            if ($myrow["title"]=="") {
                $tool_content .= "".$langAnnouncementNoTille."";
            } else {
                $tool_content .= "".$myrow["title"]."";
            }

            $tool_content .= "</b>&nbsp;<small>(" . $myrow['temps'] . ")</small>
            <br />".$content."</td>
        <td width='70' class='right'>
        <a href='$_SERVER[PHP_SELF]?modify=" . $myrow['id'] . "'>
        <img src='../../template/classic/img/edit.gif' title='" . $langModify . "' /></a>
        <a href='$_SERVER[PHP_SELF]?delete=" . $myrow['id'] . "' onClick=\"return confirmation('');\">
        <img src='../../template/classic/img/delete.gif' title='" . $langDelete . "' /></a>
        </td>";

	if ($announcementNumber > 1)  {
		$tool_content .= "<td align='center' width='70' class='right'>";
	}
           // DISPLAY MOVE UP COMMAND
            // condition: only if it is not the top announcement
	if ($iterator != 1)  {
		$tool_content .= "<a href='$_SERVER[PHP_SELF]?up=" . $myrow["id"] . "'><img class='displayed' src='../../template/classic/img/up.gif' title='" . $langUp . "' /></a>";
	}
        // DISPLAY MOVE DOWN COMMAND
	if ($iterator < $bottomAnnouncement) {
		$tool_content .= "<a href='$_SERVER[PHP_SELF]?down=" . $myrow["id"] . "'><img class='displayed' src='../../template/classic/img/down.gif' title='" . $langDown . "' /></a>";
	}
	if ($announcementNumber > 1) {
		$tool_content .= "</td>";
	}
// DISPLAY ANNOUNCEMENT CONTENT
	$tool_content .= "\n      </tr>";
            $iterator ++;
            $k++;
        } // end while ($myrow = mysql_fetch_array($result))
        $tool_content .= "
      </tbody>
      </table>";
    } // end: if ($displayAnnoucementList == true)
    if ($announcementNumber < 1) {
        $no_content = true;
        if (isset($_GET['addAnnouce'])) {
            $no_content = false;
        }

        if (isset($_GET['modify'])) {
            $no_content = false;
        }

        if ($no_content) $tool_content .= "<p class='alert1'>$langNoAnnounce</p>";
    }
} // end: teacher only
// student view
else {
	$result = db_query("SELECT * FROM annonces WHERE cours_id = $cours_id
		ORDER BY ordre DESC", $mysqlMainDb) OR die("DB problem");
	if (mysql_num_rows($result) > 0) {
		$tool_content .= "<table width='99%' align='left' class='announcements'>
		<thead>
		<tr><th class='left' colspan='2'><b>$langAnnouncement</b></th>
		</tr></thead><tbody>";
		$k = 0;
		while ($myrow = mysql_fetch_array($result)) {
			$content = $myrow['contenu'];
			$content = make_clickable($content);
			$content = nl2br($content);
			if ($k%2==0) {
				$tool_content .= "\n      <tr>";
			} else {
				$tool_content .= "\n      <tr class='odd'>";
			}
			$tool_content .= "
			<td width='1'><img style='padding-top:3px;' src='${urlServer}/template/classic/img/arrow_grey.gif' title='bullet' /></td>
			<td><b>$myrow[title]</b>&nbsp;<small>(" . nice_format($myrow["temps"]) . ")</small><br/>".unescapeSimple($content)."</td></tr>";
			$k++;
		} // while loop
		$tool_content .= "
	</tbody></table>";
	} else {
		$tool_content .= "<p class='alert1'>$langNoAnnounce</p>";
	}
}
add_units_navigation(TRUE);
if ($is_adminOfCourse) {
    draw($tool_content, 2, 'announcements', $head_content, @$body_action);
} else {
    draw($tool_content, 2, 'announcements');
}
?>
