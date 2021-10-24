<?php // $Id: statement_admin.inc.php,v 1.17 2009-11-24 10:57:21 jexi Exp $
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

// the question form has been submitted
if(isset($submitQuestion)) {
	$questionName = trim($questionName);
	$questionDescription = trim($questionDescription);

	// no name given
	if(empty($questionName))
	{
		$msgErr = $langGiveQuestion;
	}
	// checks if the question is used in several exercises	
	elseif($exerciseId && !isset($modifyIn) && $objQuestion->selectNbrExercises() > 1)
	{
	        // if a picture has been set
        	if($imageUpload_size)
	        {
        	    // saves the picture into a temporary file
	            $objQuestion->setTmpPicture($imageUpload);
        	}
		// duplicates the question
		$questionId=$objQuestion->duplicate();
		// deletes the old question
		$objQuestion->delete($exerciseId);
		// removes the old question ID from the question list of the Exercise object
		$objExercise->removeFromList($modifyQuestion);
		$nbrQuestions--;
		// construction of the duplicated Question
		$objQuestion=new Question();
		$objQuestion->read($questionId);
		// adds the exercise ID into the exercise list of the Question object
		$objQuestion->addToList($exerciseId);
		// construction of the Answer object
		$objAnswerTmp=new Answer($modifyQuestion);
		// copies answers from $modifyQuestion to $questionId
		$objAnswerTmp->duplicate($questionId);
		// destruction of the Answer object
		unset($objAnswerTmp);	
	}
	
	$objQuestion->read($modifyQuestion);
	$objQuestion->updateTitle($questionName);
	$objQuestion->updateDescription($questionDescription);
	$objQuestion->updateType($answerType);
	$objQuestion->save($exerciseId);
	$questionId=$objQuestion->selectId();
	// if a picture has been set or checkbox "delete" has been checked
	if($imageUpload_size || isset($deletePicture)) {
		// we remove the picture
		$objQuestion->removePicture();
		// if we add a new picture
		if($imageUpload_size) {
			// image is already saved in a temporary file
			if(isset($modifyIn)) {
				// saves the picture into a temporary file
				$objQuestion->getTmpPicture();
			} else { // saves the picture coming from POST FILE
				$objQuestion->uploadPicture($imageUpload);
			}
		}
	}
	if($exerciseId)  {
		// adds the question ID into the question list of the Exercise object
		if($objExercise->addToList($questionId)) {
			$objExercise->save();
			$nbrQuestions++;
		}
	}
	if($newQuestion) {
		// goes to answer administration
		$modifyAnswers=$questionId;
	} else {
		// goes to exercise viewing
		$editQuestion=$questionId;
	}
	unset($newQuestion,$modifyQuestion);
}
else
{
// if we don't come here after having cancelled the warning message "used in several exercises"
	if(!isset($buttonBack)) {
		$questionName=$objQuestion->selectTitle();
		$questionDescription=$objQuestion->selectDescription();
		$answerType=$objQuestion->selectType();
	}
}

if(isset($newQuestion) || isset($modifyQuestion)) {
	// is picture set ?
	$okPicture = file_exists($picturePath.'/quiz-'.$questionId)?true:false;
	@$tool_content .= "<form enctype='multipart/form-data' method='post' action='$_SERVER[PHP_SELF]?modifyQuestion=$modifyQuestion&newQuestion=$newQuestion'>
	<table width=\"99%\" class=\"FormData\"><tbody>";

	// if there is an error message
	if(!empty($msgErr)) {
		$tool_content .= "<tr><td colspan='2'>
		<table border='0' cellpadding='3' align='center' width='400' bgcolor='#FFCC00'>
		<tr><td>${msgErr}</td></tr>
		</table></td></tr>";
	}

	$tool_content .= "<tr><th width=\"220\">&nbsp;</td>
	<td><b>$langInfoQuestion</b></td>
	</tr><tr>
	<th class=\"left\">".$langQuestion." :</th>
	<td><input type=\"text\" name=\"questionName\"" ."size=\"50\" maxlength=\"200\" value=\"".htmlspecialchars($questionName)."\" style=\"width:400px;\" class=\"FormData_InputText\"></td>";
	$tool_content .= "</tr><tr><th class='left'>$langQuestionDescription:</th>";
	$tool_content .= "<td>
	<textarea wrap=\"virtual\" name=\"questionDescription\" cols=\"50\" rows=\"4\" "."style=\"width:400px;\" class=\"FormData_InputText\">".str_replace('{','&#123;',htmlspecialchars($questionDescription))."</textarea>
	</td></tr><tr><th class='left'>";

	if ($okPicture) {
		$tool_content .= "$langReplacePicture";
	} else { 
		$tool_content .= "$langAddPicture";
	}	

	$tool_content .= " :</th><td>";
	if($okPicture) {
		$tool_content .= "<img src='$picturePath/quiz-$questionId' border='0'><br/><br/>";
	}
	$tool_content .= "<input type='file' name='imageUpload' size='30' style='width:390px;'></td></tr>";

	if ($okPicture) {
		$tool_content .= "<tr><th class='left'>$langDeletePicture :</th>
		<td><input type=\"checkbox\" name=\"deletePicture\" value=\"1\" ";
		if(isset($deletePicture)) 
			$tool_content .= 'checked="checked"'; 
		$tool_content .= "> ";
		$tool_content .= "</td></tr>";
	}

  $tool_content .= <<<cData

    <tr>
      <th class='left'>${langAnswerType} :</th>
cData;

$tool_content .= "
      <td><input type=\"radio\" name=\"answerType\" value=\"1\" ";
        if ($answerType <= 1)
                $tool_content .= 'checked="checked"';
        $tool_content .= "> ".$langUniqueSelect."
          <br>
          ";
        $tool_content .= "<input type=\"radio\" name=\"answerType\" value=\"2\" ";
                if ($answerType == 2)
                        $tool_content .= 'checked="checked"';
                $tool_content .= "> ".$langMultipleSelect."
          <br>
          ";
        $tool_content .= "<input type=\"radio\" name=\"answerType\" value=\"4\" ";
                if ($answerType >= 4)
                        $tool_content .= 'checked="checked"';
                $tool_content .= "> ".$langMatching."
          <br>
          ";
        $tool_content .= "<input type=\"radio\" name=\"answerType\" value=\"3\" ";
                if ($answerType == 3)
                        $tool_content .= 'checked="checked"';
                $tool_content .= "> ".$langFillBlanks;

	$tool_content .= "</td><tr><th>&nbsp;</td><td>
	<input type='submit' name='submitQuestion' value='$langOk'>
	&nbsp;&nbsp;<input type='submit' name='cancelQuestion' value='$langCancel'>
	</td></tr></tbody>
	</table></form>";
}
?>