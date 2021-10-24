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
 * Personalised ForumPosts Component, eClass Personalised
 *
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id: forumPosts.php,v 1.19 2009-07-20 08:40:07 adia Exp $
 * @package eClass Personalised
 *
 * @abstract This component populates the Forum Posts block on the user's personalised
 * interface. It is based on the diploma thesis of Evelthon Prodromou.
 *
 */

/**
 * Function getUserForumPosts
 *
 * Populates an array with data regarding the user's personalised forum posts
 *
 * @param array $param
 * @param string $type (data, html)
 * @return array
 */
function getUserForumPosts($param, $type)
{
	global $mysqlMainDb, $uid, $dbname, $currentCourseID;

	$uid				= $param['uid'];
	$lesson_code		= $param['lesson_code'];
	$max_repeat_val		= $param['max_repeat_val'];
	$lesson_title		= $param['lesson_titles'];
	$lesson_code		= $param['lesson_code'];
	$lesson_professor	= $param['lesson_professor'];

	$usr_lst_login	= $param['usr_lst_login'];

	$usr_memory = $param['usr_memory'];

	//		Generate SQL code for all queries
	//		----------------------------------------

	$forum_query_new 	= createForumQueries($usr_lst_login);
	$forum_query_memo 	= createForumQueries($usr_memory);

	$forumPosts = array();
	$getNewPosts = false;
	for ($i=0;$i<$max_repeat_val;$i++) {

		$mysql_query_result = db_query($forum_query_new, $lesson_code[$i]);

		if ($num_rows = mysql_num_rows($mysql_query_result) > 0) {
			$getNewPosts = true;

			$forumData = array();
			$forumSubData = array();
			$forumContent = array();

			array_push($forumData, $lesson_title[$i]);
			array_push($forumData, $lesson_code[$i]);
		}

		while ($myForumPosts = mysql_fetch_row($mysql_query_result)) {
			if ($myForumPosts){
				array_push($forumContent, $myForumPosts);
			}
		}

		if ($num_rows > 0) {
			array_push($forumSubData, $forumContent);
			array_push($forumData, $forumSubData);
			array_push($forumPosts, $forumData);
		}
	}

	if ($getNewPosts) {
		$sqlNowDate = eregi_replace(" ", "-",$usr_lst_login);
		$sql = "UPDATE `user` SET `forum_flag` = '$sqlNowDate' WHERE `user_id` = $uid ";
		db_query($sql, $mysqlMainDb);
	} elseif (!$getNewPosts) {
		//if there are no new announcements, get the last announcements the user had
		//so that we always have something to display
		for ($i=0; $i < $max_repeat_val; $i++){
			$mysql_query_result = db_query($forum_query_memo, $lesson_code[$i]);
			if (mysql_num_rows($mysql_query_result) >0) {
				$forumData = array();
				$forumSubData = array();
				$forumContent = array();

				array_push($forumData, $lesson_title[$i]);
				array_push($forumData, $lesson_code[$i]);

				while ($myForumPosts = mysql_fetch_row($mysql_query_result)) {
					array_push($forumContent, $myForumPosts);
				}

				array_push($forumSubData, $forumContent);
				array_push($forumData, $forumSubData);
				array_push($forumPosts, $forumData);
			}
		}
	}

	if($type == "html") {
		return forumHtmlInterface($forumPosts);
	} elseif ($type == "data") {
		return $forumPosts;
	}
}


/**
 * Function forumHtmlInterface
 *
 * Generates html content for the Forum Posts block of eClass personalised.
 *
 * @param array $data
 * @return string HTML content for the documents block
 * @see function getUserForumPosts()
 */
function forumHtmlInterface($data)
{
	global $langNoPosts, $langMore, $langSender, $urlServer;

	$content = "";

	if($numOfLessons = count($data) > 0) {
		$content .= <<<fCont
    <div class="datacontainer">
      <ul class="datalist">
fCont;
		$numOfLessons = count($data);
		for ($i=0; $i <$numOfLessons; $i++) {
			$content .= "\n          <li class='category'>".$data[$i][0]."</li>";
			$iterator =  count($data[$i][2][0]);
			for ($j=0; $j < $iterator; $j++){
				$url = $urlServer."index.php?perso=5&amp;c=".$data[$i][1]."&amp;t=".$data[$i][2][0][$j][2]."&amp;f=".$data[$i][2][0][$j][0]."&amp;s=".$data[$i][2][0][$j][4];
                                $data[$i][2][0][$j][8] = ellipsize($data[$i][2][0][$j][8], 150,
                                        "... <strong><span class='announce_date'>$langMore</span></strong>");
				$content .= "\n          <li><a class='square_bullet' href='$url'><strong class='title_pos'>".$data[$i][2][0][$j][3]." (".nice_format(date("Y-m-d", strtotime($data[$i][2][0][$j][5]))).")</strong></a><p class='content_pos'>".$data[$i][2][0][$j][8]."<br /><cite class='content_pos'>".$data[$i][2][0][$j][6]." ".$data[$i][2][0][$j][7]."</cite></p></li>";
			}
			//if ($i+1 <$numOfLessons) $content .= "<br>";
		}
		$content .= "</ul></div>";
	} else {
		$content .= "<p>$langNoPosts</p>";
	}

	return $content;
}


/**
 * Function createForumQueries
 *
 * Creates needed queries used by getUserForumPosts()
 *
 * @param string $dateVar
 * @return string SQL query
 */
function createForumQueries($dateVar){

        $forum_query = 'SELECT forums.forum_id,
                               forums.forum_name,
                               topics.topic_id,
                               topics.topic_title,
                               topics.topic_replies,
                               posts.post_time,
                               posts.nom,
                               posts.prenom,
                               posts_text.post_text
                        FROM   forums,
                               topics,
                               posts,
                               posts_text,
                               accueil
                        WHERE  CONCAT(topics.topic_title, posts_text.post_text) != \'\'
                               AND forums.forum_id = topics.forum_id
                               AND posts.forum_id = forums.forum_id
                               AND posts.post_id = posts_text.post_id
                               AND posts.topic_id = topics.topic_id
                               AND DATE_FORMAT(posts.post_time, \'%Y %m %d\') >= "'.$dateVar.'"
                               AND accueil.visible = 1
                               AND accueil.id = 9
                        ORDER BY posts.post_time';

	return $forum_query;
}
