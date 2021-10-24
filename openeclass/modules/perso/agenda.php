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


/**
 * Personalised Documents Component, eClass Personalised
 *
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id: agenda.php,v 1.23 2009-03-24 11:51:35 adia Exp $
 * @package eClass Personalised
 *
 * @abstract This component populates the agenda block on the user's personalised
 * interface. It is based on the diploma thesis of Evelthon Prodromou.
 *
 */

/**
 * Function getUserAgenda
 *
 * Populates an array with data regarding the user's personalised agenda.
 *
 * @param array $param
 * @param string $type (data, html)
 * @return array
 */
function getUserAgenda($param, $type)
{

	//number of unique dates to collect data for
	$uniqueDates = 5;
	global $mysqlMainDb, $uid, $dbname, $currentCourseID;
	$uid			= $param['uid'];
	$lesson_code		= $param['lesson_code'];
	$max_repeat_val		= $param['max_repeat_val'];
	$tbl_lesson_codes = array();
	
	for($i=0; $i < $max_repeat_val; $i++) {
		array_push($tbl_lesson_codes, $lesson_code[$i]);
	}
	array_walk($tbl_lesson_codes, 'wrap_each');
	$tbl_lesson_codes = implode(",", $tbl_lesson_codes);

	//mysql version 4.x query
	$sql_4 = "SELECT agenda.titre, agenda.contenu, agenda.day, agenda.hour, agenda.lasting, 
			agenda.lesson_code, cours.intitule
		FROM agenda, cours WHERE agenda.lesson_code IN ($tbl_lesson_codes)
		AND agenda.lesson_code = cours.code
		GROUP BY day
		HAVING (TO_DAYS(day) - TO_DAYS(NOW())) >= '0'
		ORDER BY day, hour DESC
		LIMIT $uniqueDates";

	//mysql version 5.x query

	$sql_5 = "SELECT agenda.titre, agenda.contenu, agenda.day, DATE_FORMAT(agenda.hour, '%H:%i'), 
		agenda.lasting, agenda.lesson_code, cours.intitule
		FROM agenda, cours WHERE agenda.lesson_code IN ($tbl_lesson_codes) 
		AND agenda.lesson_code = cours.code
		GROUP BY day
		HAVING (TO_DAYS(day) - TO_DAYS(NOW())) >= '0'
		ORDER BY day, hour DESC
		LIMIT $uniqueDates"; 

	$ver = mysql_get_server_info();

	if (version_compare("5.0", $ver) <= 0)
	$sql = $sql_5;//mysql 4 compatible query
	elseif (version_compare("4.1", $ver) <= 0)
	$sql = $sql_4;//mysql 5 compatible query

	$mysql_query_result = db_query($sql, $mysqlMainDb);
	$agendaDateData = array();
	$previousDate = "0000-00-00";
	$firstRun = true;
	while ($myAgenda = mysql_fetch_row($mysql_query_result)) {

		//allow certain html tags that do not cause errors in the
		//personalised interface
		$myAgenda[1] = strip_tags($myAgenda[1], '<b><i><u><ol><ul><li><br>');
		if ($myAgenda[2] != $previousDate ) {
			if (!$firstRun) {
				@array_push($agendaDateData, $agendaData);
			}

		}

		if ($firstRun) $firstRun = false;

		if ($myAgenda[2] == $previousDate) {
			array_push($agendaData, $myAgenda);
		} else {
			$agendaData = array();
			$previousDate = $myAgenda[2];
			array_push($agendaData, $myAgenda);
		}
	}

	if (!$firstRun) {
		array_push($agendaDateData, $agendaData);
	}

	if($type == "html") {
		return agendaHtmlInterface($agendaDateData);
	} elseif ($type == "data") {
		return $agendaDateData;
	}
}


/*
 * Function agendaHtmlInterface
 *
 * @param array $data
 * @return string HTML content for the documents block
 * @see function getUserAgenda()
 */
function agendaHtmlInterface($data)
{
	global $langNoEventsExist, $langUnknown, $langDuration, $langMore, $langHours, $langHour, $langExerciseStart, $urlServer;

	$numOfDays = count($data);
	if ($numOfDays > 0) {
		$agenda_content= <<<agCont
      <div class="datacontainer">
        <ul class="datalist">
agCont;
		for ($i=0; $i <$numOfDays; $i++) {
			$agenda_content .= "\n          <li class=\"category\">".nice_format($data[$i][0][2])."</li>";
			$iterator =  count($data[$i]);
			for ($j=0; $j < $iterator; $j++){
				$url = $urlServer . "index.php?perso=4&c=" . $data[$i][$j][5];
				if (strlen($data[$i][$j][4]) == 0) {
					$data[$i][$j][4] = "$langUnknown";
				}
				elseif ($data[$i][$j][4] == 1) {
					$data[$i][$j][4] = $data[$i][$j][4]." $langHour";
				}
				else {
					$data[$i][$j][4] = $data[$i][$j][4]." $langHours";
				}

				if(strlen($data[$i][$j][0]) > 80) {
					$data[$i][$j][0] = substr($data[$i][$j][0], 0, 80);
					$data[$i][$j][0] .= "...";
				}

				if(strlen($data[$i][$j][1]) > 150) {
					$data[$i][$j][1] = substr($data[$i][$j][1], 0, 150);
					$data[$i][$j][1] .= "... <a href=\"$url\">[$langMore]</a>";

				}

				$agenda_content .= "\n          <li><a class=\"square_bullet2\" href=\"$url\"><strong class=\"title_pos\">".$data[$i][$j][0]."</strong></a> <p class=\"content_pos\"><b class=\"announce_date\">".$data[$i][$j][6]."</b>&nbsp;-&nbsp;(".$langExerciseStart.":<b>".$data[$i][$j][3]."</b>, $langDuration:<b>".$data[$i][$j][4]."</b>)<br /><span class=\"announce_date\"> ".$data[$i][$j][1].autoCloseTags($data[$i][$j][1])."</span></p></li>";
			}
		}
		$agenda_content .= "
        </ul>
      </div> ";
	} else {
		$agenda_content = "<p>$langNoEventsExist</p>";
	}
	return $agenda_content;
}
