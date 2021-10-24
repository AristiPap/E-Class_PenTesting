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
===========================================================================
    usage/userlogins.php
 * @version $Id: userlogins.php,v 1.13 2009-07-22 14:35:21 jexi Exp $
    @last update: 2006-12-27 by Evelthon Prodromou <eprodromou@upnet.gr>
    @authors list: Vangelis Haniotakis haniotak@ucnet.uoc.gr,
                    Ophelia Neofytou ophelia@ucnet.uoc.gr
==============================================================================
    @Description: Shows logins made by a user or all users of a course, during a specific period.
    Takes data from table 'logins' (and also from table 'stat_accueil' if still exists).

==============================================================================
*/

$require_current_course = TRUE;
$require_help = true;
$helpTopic = 'Usage';
$require_login = true;
$require_prof = true;

include '../../include/baseTheme.php';
include('../../include/action.php');
$tool_content = '';
$tool_content .= "
  <div id=\"operations_container\">
    <ul id=\"opslist\">
      <li><a href='usage.php'>".$langUsageVisits."</a></li>
      <li><a href='favourite.php?first='>".$langFavourite."</a></li>
      <li><a href='userduration.php'>".$langUserDuration."</a></li>
    </ul>
  </div>";

$nameTools = $langUsage;
$local_style = '
    .month { font-weight : bold; color: #FFFFFF; background-color: #000066;
     padding-left: 15px; padding-right : 15px; }
    .content {position: relative; left: 25px; }';

include('../../include/jscalendar/calendar.php');
if ($language == 'greek') {
    $lang = 'el';
} else if ($language == 'english') {
    $lang = 'en';
}

$jscalendar = new DHTML_Calendar($urlServer.'include/jscalendar/', $lang, 'calendar-blue2', false);
$local_head = $jscalendar->get_load_files_code();




$usage_defaults = array (
    'u_user_id' => -1,
    'u_date_start' => strftime('%Y-%m-%d', strtotime('now -2 day')),
    'u_date_end' => strftime('%Y-%m-%d', strtotime('now')),
);

foreach ($usage_defaults as $key => $val) {
    if (!isset($_POST[$key])) {
        $$key = $val;
    } else {
        $$key = $_POST[$key];
    }
}


$date_fmt = '%Y-%m-%d';
$date_where = " (date_time BETWEEN '$u_date_start 00:00:00' AND '$u_date_end 23:59:59') ";
$date_what  = "DATE_FORMAT(MIN(date_time), '$date_fmt') AS date_start, DATE_FORMAT(MAX(date_time), '$date_fmt') AS date_end ";

if ($u_user_id != -1) {
    $user_where = " (a.user_id = '$u_user_id') ";
} else {
    $user_where = " (1) ";
}


$sql_1=" SELECT user_id, ip, date_time FROM logins AS a WHERE ".$date_where." AND ".$user_where." order by date_time desc";

$sql_2=" SELECT a.user_id as user_id, a.nom as nom, a.prenom as prenom, a.username
            FROM user AS a LEFT JOIN cours_user AS b ON a.user_id = b.user_id
            WHERE b.cours_id = $cours_id AND ".$user_where;



$sql_3="SHOW TABLES FROM `$currentCourseID` LIKE 'stat_accueil'";
$result_3=db_query($sql_3, $currentCourseID);
$exist_stat_accueil=0;
if (mysql_fetch_assoc($result_3)) {
    $exist_stat_accueil=1;
}

$sql_4="  SELECT host, address, `date` FROM stat_accueil WHERE `date` BETWEEN '$u_date_start 00:00:00' AND '$u_date_end 23:59:59' order by `date` desc";




///Take data from logins
$result_2= db_query($sql_2, $mysqlMainDb);

$users = array();
while ($row = mysql_fetch_assoc($result_2)) {
    $users[$row['user_id']] = $row['nom'].' '.$row['prenom'];
}

$result = db_query($sql_1, $currentCourseID);
$table_cont = '';
$unknown_users = array();

$k=0;
while ($row = mysql_fetch_assoc($result)) {
        $known = false;
        if (isset($users[$row['user_id']])) {
                $user = $users[$row['user_id']];
                $known = true;
        } elseif (isset($unknown_users[$row['user_id']])) {
                $user = $unknown_users[$row['user_id']];
        } else {
                $user = uid_to_name($row['user_id']);
                if ($user === false) {
                        $user = $langAnonymous;
                }
                $unknown_users[$row['user_id']] = $user;
        }
        if ($k%2==0) {
                $table_cont .= "<tr>";
        } else {
                $table_cont .= "<tr class='odd'>";
        }
        $table_cont .= "
                <td width=\"1\"><img src='${urlServer}/template/classic/img/arrow_grey.gif' title='bullet'></td>
                <td>";
        if ($known) {
                $table_cont .= $user;
        } else {
                $table_cont .= "<font color='red'>$user</font>";
        }
        $table_cont .= "</td>
                <td align=\"center\">".$row['ip']."</td>
                <td align=\"center\">".$row['date_time']."</td>
                </tr>";

        $k++;
}

//Take data from stat_accueil
$table2_cont = '';
if ($exist_stat_accueil){
    $result_4= db_query($sql_4, $currentCourseID);
    $k1=0;
    while ($row = mysql_fetch_assoc($result_4)) {
	if ($k%2==0) {
	$table2_cont .= "
  <tr>";
	} else {
	$table2_cont .= "
  <tr class=\"odd\">";
	}
        $table2_cont .= "
    <td width=\"1\"><img style='border:0px;' src='${urlServer}/template/classic/img/arrow_grey.gif' title='bullet'></td>
    <td>".$row['host']."</td>
    <td align=\"center\">".$row['address']."</td>
    <td align=\"center\">".$row['date']."</td>
  </tr>";

    $k1++;
    }
}

//$tool_content .= "<p>$langUserLogins</p>";
//Records exist?
if (count($unknown_users) > 0) {
        $tool_content .= "<p class='alert1'>$langAnonymousExplain</p>\n";
}

if ($table_cont) {
  $tool_content .= "
  <table class=\"FormData\" width=\"99%\" align=\"left\" style=\"border: 1px solid #edecdf;\">
  <tbody>
  <tr>
    <th colspan=\"4\" style=\"border-top: 1px solid #edecdf; border-left: 1px solid #edecdf; border-right: 1px solid #edecdf;\">$langUserLogins</th>
  </tr>
  <tr>
    <th colspan=\"2\" class=\"left\" style=\"border: 1px solid #edecdf;\">&nbsp;&nbsp;&nbsp;&nbsp;".$langUser."</th>
    <th style=\"border: 1px solid #edecdf;\">".$langAddress."</th>
    <th style=\"border: 1px solid #edecdf;\">".$langLoginDate."</th>
  </tr>";
  $tool_content .= "".$table_cont."";
  $tool_content .= "
  </tbody>
  </table>";
}

if ($table2_cont) {
  $tool_content .= "
  <br>
  <p>".$langStatAccueil."</p>
  <table class=\"FormData\" width=\"99%\" align=\"left\">
  <tbody>
  <tr>
    <th colspan=\"4\" style=\"border-top: 1px solid #edecdf; border-left: 1px solid #edecdf; border-right: 1px solid #edecdf;\">$langUserLogins</th>
  </tr>
  <tr>
    <th colspan=\"2\" class=\"left\" style=\"border: 1px solid #edecdf;\">&nbsp;&nbsp;&nbsp;&nbsp;".$langHost."</th>
    <th style=\"border: 1px solid #edecdf;\">".$langAddress."</th>
    <th style=\"border: 1px solid #edecdf;\">".$langLoginDate."</th>
  </tr>";
  $tool_content .= "".$table2_cont."";
  $tool_content .= "
  </tbody>
  </table>";
}
if (!($table_cont || $table2_cont)) {

    $tool_content .= '<p class="alert1">'.$langNoLogins.'</p>';
}

    //make form
    $start_cal = $jscalendar->make_input_field(
           array('showsTime'      => false,
                 'showOthers'     => true,
                 'ifFormat'       => '%Y-%m-%d',
                 'timeFormat'     => '24'),
           array('style'       => 'width: 10em; color: #727266; background-color: #fbfbfb; border: 1px solid #CAC3B5; text-align: center',
                 'name'        => 'u_date_start',
                 'value'       => $u_date_start));

    $end_cal = $jscalendar->make_input_field(
           array('showsTime'      => false,
                 'showOthers'     => true,
                 'ifFormat'       => '%Y-%m-%d',
                 'timeFormat'     => '24'),
           array('style'       => 'width: 10em; color: #727266; background-color: #fbfbfb; border: 1px solid #CAC3B5; text-align: center',
                 'name'        => 'u_date_end',
                 'value'       => $u_date_end));



    $qry = "SELECT LEFT(a.nom, 1) AS first_letter
        FROM user AS a LEFT JOIN cours_user AS b ON a.user_id = b.user_id
        WHERE b.cours_id = $cours_id
        GROUP BY first_letter ORDER BY first_letter";
    $result = db_query($qry, $mysqlMainDb);

    $letterlinks = '';
    while ($row = mysql_fetch_assoc($result)) {
        $first_letter = $row['first_letter'];
        $letterlinks .= '<a href="?first='.$first_letter.'">'.$first_letter.'</a> ';
    }

    if (isset($_GET['first'])) {
        $firstletter = mysql_real_escape_string($_GET['first']);
        $qry = "SELECT a.user_id, a.nom, a.prenom, a.username, a.email, b.statut
            FROM user AS a LEFT JOIN cours_user AS b ON a.user_id = b.user_id
            WHERE b.cours_id = $cours_id AND LEFT(a.nom,1) = '$firstletter'";
    } else {
        $qry = "SELECT a.user_id, a.nom, a.prenom, a.username, a.email, b.statut
            FROM user AS a LEFT JOIN cours_user AS b ON a.user_id = b.user_id
            WHERE b.cours_id = $cours_id";
    }


    $user_opts = '<option value="-1">'.$langAllUsers."</option>\n";
    $result = db_query($qry, $mysqlMainDb);
    while ($row = mysql_fetch_assoc($result)) {
        if ($u_user_id == $row['user_id']) { $selected = 'selected'; } else { $selected = ''; }
        $user_opts .= '<option '.$selected.' value="'.$row["user_id"].'">'.$row['prenom'].' '.$row['nom']."</option>\n";
    }

    $tool_content .= '
<p>&nbsp;</p>
<form method="post">

  <table class="FormData" width="99%" align="left">
  <tbody>
  <tr>
    <th width="220" class="left">&nbsp;</th>
    <td><b>'.$langUserLogins.'</b><br />'.$langCreateStatsGraph.':</td>
  </tr>
  <tr>
    <th class="left">'.$langStartDate.':</th>
    <td>'."$start_cal".'</td>
  </tr>
  <tr>
    <th class="left">'.$langEndDate.':</th>
    <td>'."$end_cal".'</td>
  </tr>
  <tr>
    <th class="left">'.$langUser.':</th>
    <td>'.$langFirstLetterUser.': '.$letterlinks.' <br /><select name="u_user_id" class="auth_input">'.$user_opts.'</select></td>
  </tr>
  <tr>
    <th>&nbsp;</th>
    <td><input type="submit" name="btnUsage" value="'.$langSubmit.'">
        <div align="right"><a href="oldStats.php">'.$langOldStats.'</a></div>
    </td>
  </tr>
  </tbody>
  </table>

</form>';


draw($tool_content, 2, '', $local_head, '');
?>
