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
	importLearningPath.php
	@last update: 25-03-2010 by Thanos Kyritsis
	@authors list: Thanos Kyritsis <atkyritsis@upnet.gr>

	based on Claroline version 1.7 licensed under GPL
	      copyright (c) 2001, 2006 Universite catholique de Louvain (UCL)

	      original file: importLearningPath.php Revision: 1.34.2.2

	Claroline authors: Piraux Sebastien <pir@cerdecam.be>
                      Lederer Guillaume <led@cerdecam.be>
==============================================================================
    @Description: This script handles importing of SCORM packages.
                  It mainly parses imsmanifest.xml and extracts the SCOs
                  from the zip file.

    @Comments:

    @todo:
==============================================================================
*/

require_once("../../include/lib/learnPathLib.inc.php");
require_once("../../include/lib/fileManageLib.inc.php");
require_once("../../include/lib/fileUploadLib.inc.php");
require_once("../../include/lib/fileDisplayLib.inc.php");

$require_current_course = TRUE;
$require_prof = TRUE;

$TABLELEARNPATH         = "lp_learnPath";
$TABLEMODULE            = "lp_module";
$TABLELEARNPATHMODULE   = "lp_rel_learnPath_module";
$TABLEASSET             = "lp_asset";
$TABLEUSERMODULEPROGRESS= "lp_user_module_progress";

define('CLARO_FILE_PERMISSIONS', 0777);

require_once("../../include/baseTheme.php");
$tool_content = "";
$pwd = getcwd();

$navigation[]= array ("url"=>"learningPathList.php", "name"=> $langLearningPaths);
$nameTools = $langimportLearningPath;

mysql_select_db($currentCourseID);

// error handling
$errorFound = false;

 /*--------------------------------------------------------
      Functions
   --------------------------------------------------------*/

/*
 * Function used by the SAX xml parser when the parser meets a opening tag
 * exemple :
 *          <manifest identifier="samplescorm" version"1.1">
 *      will give
 *          $name == "manifest"
 *          attributes["identifier"] == "samplescorm"
 *          attributes["version"]    == "1.1"
 *
 * @param $parser xml parser created with "xml_parser_create()"
 * @param $name name of the element
 * @param $attributes array with the attributes of the element
 */
function startElement($parser,$name,$attributes)
{
    global $elementsPile;
    global $itemsPile;
    global $manifestData;
    global $flagTag;


    array_push($elementsPile,$name);

    switch ($name)
    {
        case "MANIFEST" :
            if (isset($attributes['XML:BASE'])) $manifestData['xml:base']['manifest'] = $attributes['XML:BASE'];
            break;
        case "RESOURCES" :
            if (isset($attributes['XML:BASE'])) $manifestData['xml:base']['resources'] = $attributes['XML:BASE'];
            $flagTag['type'] == "resources";
            break;
        case "RESOURCE" :
            if ( isset($attributes['ADLCP:SCORMTYPE']) && $attributes['ADLCP:SCORMTYPE'] == 'sco' )
            {
                if (isset($attributes['HREF'])) $manifestData['scos'][$attributes['IDENTIFIER']]['href'] = $attributes['HREF'];
                if (isset($attributes['XML:BASE'])) $manifestData['scos'][$attributes['IDENTIFIER']]['xml:base'] = $attributes['XML:BASE'];
                $flagTag['type'] = "sco";
                $flagTag['value'] = $attributes['IDENTIFIER'];
            }
            elseif(isset($attributes['ADLCP:SCORMTYPE'])&& $attributes['ADLCP:SCORMTYPE'] == 'asset' )
            {
                if (isset($attributes['HREF']))     $manifestData['assets'][$attributes['IDENTIFIER']]['href'] = $attributes['HREF'];
                if (isset($attributes['XML:BASE'])) $manifestData['assets'][$attributes['IDENTIFIER']]['xml:base'] = $attributes['XML:BASE'];
                $flagTag['type'] = "asset";
                if (isset($attributes['IDENTIFIER'])) $flagTag['value'] = $attributes['IDENTIFIER'];
            }
            else // check in $manifestData['items'] if this ressource identifier is used
            {
                foreach ($manifestData['items'] as $itemToCheck )
                {
                    if ( isset($itemToCheck['identifierref']) && $itemToCheck['identifierref'] == $attributes['IDENTIFIER'] )
                    {
                        if (isset($attributes['HREF'])) $manifestData['scos'][$attributes['IDENTIFIER']]['href'] = $attributes['HREF'];

                        if (isset($attributes['XML:BASE'])) $manifestData['scos'][$attributes['IDENTIFIER']]['xml:base'] = $attributes['XML:BASE'];
                        
                        // eidiko flag gia na anixneusoume osa scorm paketa einai typou assets
                        // dhladh osa den perilambanoun javascript gia thn parakolou8hsh ths proodou,
                        // opote allou ston kwdika 8a prepei na ta xeiristoume diaforetika
                        $manifestData['scos'][$attributes['IDENTIFIER']]['contentTypeFlag'] = CTSCORMASSET_;
                    }
                }
            }
            break;

        case "ITEM" :
            if (isset($attributes['IDENTIFIER']))
            {
               	$manifestData['items'][$attributes['IDENTIFIER']]['itemIdentifier'] = $attributes['IDENTIFIER'];

	            if (isset($attributes['IDENTIFIERREF'])) $manifestData['items'][$attributes['IDENTIFIER']]['identifierref'] = $attributes['IDENTIFIERREF'];
	            if (isset($attributes['PARAMETERS']))    $manifestData['items'][$attributes['IDENTIFIER']]['parameters'] = $attributes['PARAMETERS'];
	            if (isset($attributes['ISVISIBLE']))     $manifestData['items'][$attributes['IDENTIFIER']]['isvisible'] = $attributes['ISVISIBLE'];

	            if ( count($itemsPile) > 0)
	                $manifestData['items'][$attributes['IDENTIFIER']]['parent'] = $itemsPile[count($itemsPile)-1];

	            array_push($itemsPile, $attributes['IDENTIFIER']);

	            if ( $flagTag['type'] == "item" )
	            {
	                $flagTag['deep']++;
	            }
	            else
	            {
	                $flagTag['type'] = "item";
	                $flagTag['deep'] = 0;
	            }
	            $manifestData['items'][$attributes['IDENTIFIER']]['deep'] = $flagTag['deep'];
	            $flagTag['value'] = $attributes['IDENTIFIER'];
            }
            break;

        case "ORGANIZATIONS" :
            if( isset($attributes['DEFAULT']) ) $manifestData['defaultOrganization'] = $attributes['DEFAULT'];
			else                                $manifestData['defaultOrganization'] = '';
            break;
        case "ORGANIZATION" :
            $flagTag['type'] = "organization";
            $flagTag['value'] = $attributes['IDENTIFIER'];
            break;
        case "ADLCP:LOCATION" :
            // when finding this tag we read the specified XML file so the data structure doesn't even
            // 'see' that this is another file
            // for that we remove this element from the pile so it doesn't appear when we compare the
            // pile with the position of an element
            // $poped = array_pop($elementsPile);
            break;

    }


}

/**
 * Function used by the SAX xml parser when the parser meets a closing tag
 *
 * @param $parser xml parser created with "xml_parser_create()"
 * @param $name name of the element
 */
function endElement($parser,$name)
{
    global $elementsPile;
    global $itemsPile;
    global $flagTag;

    switch($name)
    {
        case "ITEM" :
            $trash = array_pop($itemsPile);
            if ( $flagTag['type'] == "item" && $flagTag['deep'] > 0 )
            {
                $flagTag['deep']--;
            }
            else
            {
                $flagTag['type'] = "endItem";
            }
            break;
        case "RESOURCES" :
            $flagTag['type'] = "endResources";
            break;
        case "RESOURCE" :
            $flagTag['type'] = "endResource";
            break;

    }

    $poped = array_pop($elementsPile);
}

/**
 * Function used by the SAX xml parser when the parser meets something that's not a tag
 *
 * @param $parser xml parser created with "xml_parser_create()"
 * @param $data "what is not a tag"
 */
function elementData($parser,$data)
{
    global $elementsPile;
    global $itemsPile;
    global $manifestData;
    global $flagTag;
    global $iterator;
    global $dialogBox;
    global $errorFound;
    global $langErrorReadingXMLFile;
    global $zipFile;
    global $errorMsgs,$okMsgs;
    global $pathToManifest;
    global $langErrorOpeningXMLFile;
    global $charset;

// -----------------------------------
// when eclass is full utf 8 restore this
// -----------------------------------------

    $data = trim(($data));
    //$data = trim(iconv("utf-8", $charset, $data));

    if (!isset($data)) $data="";

    switch ( $elementsPile[count($elementsPile)-1] )
    {

        case "RESOURCE" :
            break;
        case "TITLE" :
            // $data == '' (empty string) means that title tag contains elements (<langstring> for an exemple), so it's not the title we need
            if( $data != '' )
            {
                if ( $flagTag['type'] == "item" ) // item title check
                {
                    if (!isset($manifestData['items'][$flagTag['value']]['title'])) $manifestData['items'][$flagTag['value']]['title'] = "";
                    $manifestData['items'][$flagTag['value']]['title'] .= $data;
                }


                // get title of package if it was not find in the manifest metadata in the default organization
                if ( $elementsPile[sizeof($elementsPile)-2]  == "ORGANIZATION" && $flagTag['type'] == "organization" && $flagTag['value'] == $manifestData['defaultOrganization'])
                {
                    // if we do not find this title
                    //  - the metadata title has been set as package title
                    //  - if there was nor title for metadata nor for default organization set 'unamed path'
                    // If we are here it means we have found the title in organization, this is the best to chose
                    $manifestData['packageTitle'] = $data;
                }
            }
            break;

        case "ITEM" :
            break;

        case "ADLCP:DATAFROMLMS" :
            $manifestData['items'][$flagTag['value']]['datafromlms'] = $data;
            break;

        // found a link to another XML file, parse it ...
        case "ADLCP:LOCATION" :
            if (!$errorFound)
               {
                $xml_parser = xml_parser_create();
                xml_set_element_handler($xml_parser, "startElement", "endElement");
                xml_set_character_data_handler($xml_parser, "elementData");

                $file = rawurldecode($data); //url of secondary manifest files is relative to the position of the base imsmanifest.xml

                // PHP extraction of zip file using zlib
                $unzippingState = $zipFile->extract(PCLZIP_OPT_BY_NAME,$pathToManifest.$file, PCLZIP_OPT_REMOVE_PATH, $pathToManifest);
                if ( !($fp = @fopen($file, "r")) )
                {
                	$file = str_replace("\\", "/", $file); // kanoume mia dokimh mhn tyxon do8hke la8os dir separator, antistrefontas tis ka8etous. ta windows paizoun kai me to unix dir separator
                	
                	$unzippingState = $zipFile->extract(PCLZIP_OPT_BY_NAME,$pathToManifest.$file, PCLZIP_OPT_REMOVE_PATH, $pathToManifest);
                	if ( !($fp = @fopen($file, "r")) )
	                {
	                    $errorFound = true;
	                    array_push ($errorMsgs, $langErrorOpeningXMLFile.$pathToManifest.$file );
                	}
                }
                
                if (!$errorFound)
                {
                    if (!isset($cache)) $cache = "";
                    while ($readdata = str_replace("\n","",fread($fp, 4096)))
                    {
                        // fix for fread breaking thing
                        // msg from "ml at csite dot com" 02-Jul-2003 02:29 on http://www.php.net/xml
                        // preg expression has been modified to match tag with inner attributes
                        $readdata = $cache . $readdata;
                        if (!feof($fp))
                        {
                            if (preg_match_all("/<[^\>]*.>/", $readdata, $regs))
                            {
                                $lastTagname = $regs[0][count($regs[0])-1];
                                $split = false;
                                for ($i=strlen($readdata)-strlen($lastTagname); $i>=strlen($lastTagname); $i--)
                                {
                                    if ($lastTagname == substr($readdata, $i, strlen($lastTagname)))
                                    {
                                        $cache = substr($readdata, $i, strlen($readdata));
                                        $readdata = substr($readdata, 0, $i);
                                        $split = true;
                                        break;
                                    }
                                }
                            }
                            if (!$split)
                            {
                                $cache = $readdata;
                            }
                        }
                        // end of fix
                        if (!xml_parse($xml_parser, $readdata, feof($fp)))
                        {
                            // if reading of the xml file in not successfull :
                            // set errorFound, set error msg, break while statement
                            $errorFound = true;
                            array_push ($errorMsgs, $langErrorReadingXMLFile.$pathToManifest.$file );
                            break;
                        }
                    } // while $readdata
                }    //if fopen
                // close file
                @fclose($fp);
            }
            break;

        case "LANGSTRING" :
            switch ( $flagTag['type'] )
            {
                case "item" :
                    // DESCRIPTION
                    // if the langstring tag is a children of a description tag
                    if ( $elementsPile[sizeof($elementsPile)-2] == "DESCRIPTION" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                    {
                        if (!isset($manifestData['items'][$flagTag['value']]['description'])) $manifestData['items'][$flagTag['value']]['description'] = "";
                        $manifestData['items'][$flagTag['value']]['description'] .= $data;
                    }
                    // title found in metadata of an item (only if we haven't already one title for this sco)
                    if( $manifestData['items'][$flagTag['value']]['title'] == '' || !isset( $manifestData['items'][$flagTag['value']]['title'] ) )
                    {
                        if ( $elementsPile[sizeof($elementsPile)-2] == "TITLE" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                        {
                            $manifestData['items'][$flagTag['value']]['title'] .= $data;
                        }
                    }
                    break;
                case "sco" :
                    // DESCRIPTION
                    // if the langstring tag is a children of a description tag
                    if ( $elementsPile[sizeof($elementsPile)-2] == "DESCRIPTION" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                    {
                        if (isset($manifestData['scos'][$flagTag['value']]['description'])) $manifestData['scos'][$flagTag['value']]['description'] .= $data;
                        else
                        $manifestData['scos'][$flagTag['value']]['description'] = $data;
                    }
                    // title found in metadata of an item (only if we haven't already one title for this sco)
                    if (!isset($manifestData['scos'][$flagTag['value']]['title']) || $manifestData['scos'][$flagTag['value']]['title'] == '')
                    {
                        if ( $elementsPile[sizeof($elementsPile)-2] == "TITLE" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                        {
                            $manifestData['scos'][$flagTag['value']]['title'] = $data;
                        }
                    }
                    break;
                case "asset" :
                    // DESCRIPTION
                    // if the langstring tag is a children of a description tag
                    if ( $elementsPile[sizeof($elementsPile)-2] == "DESCRIPTION" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                    {
                        if (isset($manifestData['assets'][$flagTag['value']]['description']))
                        $manifestData['assets'][$flagTag['value']]['description'] .= $data;
                        else
                        $manifestData['assets'][$flagTag['value']]['description'] = $data;

                    }
                    // title found in metadata of an item (only if we haven't already one title for this sco)
                    if(!isset( $manifestData['assets'][$flagTag['value']]['title'] ) || $manifestData['assets'][$flagTag['value']]['title'] == '')
                    {
                        if ( $elementsPile[sizeof($elementsPile)-2] == "TITLE" && $elementsPile[sizeof($elementsPile)-3] == "GENERAL" )
                        {
                            if (isset($manifestData['assets'][$flagTag['value']]['title']))
                            $manifestData['assets'][$flagTag['value']]['title'] .= $data;
                            else
                            $manifestData['assets'][$flagTag['value']]['title'] = $data;
                        }
                    }
                    break;
                default :
                    // DESCRIPTION
                    $posPackageDesc = array("MANIFEST", "METADATA", "LOM", "GENERAL", "DESCRIPTION");
                    if(compareArrays($posPackageDesc,$elementsPile))
                    {
                        if (!isset($manifestData['packageDesc'])) $manifestData['packageDesc'] = "";
                        $manifestData['packageDesc'] .= $data;
                    }

                    if (!isset($manifestData['packageTitle']) || $manifestData['packageTitle'] == '' )
                    {
                        $posPackageTitle = array("MANIFEST", "METADATA","LOM","GENERAL","TITLE");
                        if (compareArrays($posPackageTitle,$elementsPile))
                        {
                            $manifestData['packageTitle'] = $data;
                        }
                    }
                    break;

            } // end switch ( $flagTag['type'] )

            break;

        default :
            break;
    } // end switch ($elementsPile[count($elementsPile)-1] )

}


/**
 * This function checks in elementpile if the sequence of markup is the same as in array2Compare
 * Checks if the sequence is the same in the begining of pile.
 * If the sequences are the same then it means that the elementdata is the one we were looking for.
 *
 * @param $array1 list xml markups upper than the requesting markup
 * @return true if arrays are the same, false otherwise
 */
function compareArrays($array1, $array2)
{
    // sizeof(array2) so we do not compare the last tag, this is the one we are in, so we not that already.
    for ($i = 0; $i < sizeof($array2)-1; $i++)
    {
        if ( $array1[$i] != $array2[$i] ) return false;
    }
    return true;
}

/**
 * This function return true if $Str could be UTF-8, false otehrwise
 *
 * function found @ http://www.php.net/manual/en/function.utf8-encode.php
 */
function seems_utf8($str)
{
    for ($i=0; $i<strlen($str); $i++)
    {
        if (ord($str[$i]) < 0x80) continue; // 0bbbbbbb
        elseif ((ord($str[$i]) & 0xE0) == 0xC0) $n=1; // 110bbbbb
        elseif ((ord($str[$i]) & 0xF0) == 0xE0) $n=2; // 1110bbbb
        elseif ((ord($str[$i]) & 0xF8) == 0xF0) $n=3; // 11110bbb
        elseif ((ord($str[$i]) & 0xFC) == 0xF8) $n=4; // 111110bb
        elseif ((ord($str[$i]) & 0xFE) == 0xFC) $n=5; // 1111110b
        else return false; // Does not match any model
        for ($j=0; $j<$n; $j++) // n bytes matching 10bbbbbb follow ?
        {
            if ((++$i == strlen($str)) || ((ord($str[$i]) & 0xC0) != 0x80))
            return false;
        }
    }
    return true;
}

/**
 *
 */
function utf8_decode_if_is_utf8($str) {
    return seems_utf8($str)? utf8_decode($str): $str;
}

/*======================================
       CLAROLINE MAIN
  ======================================*/

// init msg arays
$okMsgs   = array();
$errorMsgs = array();

$maxFilledSpace = 100000000;

$courseDir   = "courses/".$currentCourseID."/scormPackages/";
$baseWorkDir = $webDir.$courseDir; // path_id

if (!is_dir($baseWorkDir)) claro_mkdir($baseWorkDir, CLARO_FILE_PERMISSIONS);

// handle upload
// if the post is done a second time, the claroformid mecanism
// will set $_POST to NULL, so we need to check it
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !is_null($_POST) )
{

    // arrays used to store inserted ids in case
    // will be used to build delete queries for mysql < 4.0.0
    $insertedModule_id = array();
    $insertedAsset_id = array();

    $lpName = $langUnamedPath;

    // we need a new path_id for this learning path so we prepare a line in DB
    // this line will be removed if an error occurs
    $sql = "SELECT MAX(`rank`)
            FROM `".$TABLELEARNPATH."`";
    $result = db_query($sql);

    list($rankMax) = mysql_fetch_row($result);

    $sql = "INSERT INTO `".$TABLELEARNPATH."`
            (`name`,`visibility`,`rank`,`comment`)
            VALUES ('". addslashes($lpName) ."','HIDE',".($rankMax+1).",'')";
    db_query($sql);


    $tempPathId = mysql_insert_id();
    $baseWorkDir .= "path_".$tempPathId;

    if (!is_dir($baseWorkDir)) claro_mkdir($baseWorkDir, CLARO_FILE_PERMISSIONS );

    // unzip package
    require_once("../../include/pclzip/pclzip.lib.php");

    /*
     * Check if the file is valid (not to big and exists)
     */
    if( !isset($_FILES['uploadedPackage']) || !is_uploaded_file($_FILES['uploadedPackage']['tmp_name']))
    {
        $errorFound = true;
        array_push ($errorMsgs, $langFileScormError.'<br />'.$langNotice.' : '.$langMaxFileSize.' '.ini_get('upload_max_filesize') );
    }

    /*
    * Check the file size doesn't exceed
     * the maximum file size authorized in the directory
     */

    elseif ( ! enough_size($_FILES['uploadedPackage']['size'], $baseWorkDir, $maxFilledSpace))
    {
        $errorFound = true;
        array_push ($errorMsgs, $langNoSpace ) ;
    }

    /*
     * Unzipping stage
     */

    elseif ( preg_match("/.zip$/i", $_FILES['uploadedPackage']['name']) )
    {
        array_push ($okMsgs, $langOkFileReceived.basename($_FILES['uploadedPackage']['name']) );

        if (!function_exists('gzopen'))
        {
            $errorFound = true;
            array_push ($errorMsgs,$langErrorNoZlibExtension );
        }
        else
        {
            $zipFile = new pclZip($_FILES['uploadedPackage']['tmp_name']);
            $is_allowedToUnzip = true ; // default initialisation

            // Check the zip content (real size and file extension)

            $zipContentArray = $zipFile->listContent();

            if ($zipContentArray == 0)
            {
              $errorFound = true;
              array_push ($errorMsgs,$langErrorReadingZipFile );
            }

            $pathToManifest  = ""; // empty by default because we can expect that the manifest.xml is in the root of zip file
            $pathToManifestFound = false;
            $realFileSize = 0;

            foreach($zipContentArray as $thisContent)
            {
                if ( preg_match('/.(php[[:digit:]]?|phtml)$/i', $thisContent['filename']) )
                {
                        $errorFound = true;
                        array_push ($errorMsgs, $langZipNoPhp );
                        $is_allowedToUnzip = false;
                        break;
                }

                if ( strtolower(substr($thisContent['filename'], -15)) == "imsmanifest.xml" )
                {
                    // this check exists to find the less deep imsmanifest.xml in the zip if there are several imsmanifest.xml
                    // if this is the first imsmanifest.xml we found OR path to the new manifest found is shorter (less deep)
                    if ( !$pathToManifestFound
                         || ( count(explode('/', $thisContent['filename'])) < count(explode('/', $pathToManifest."imsmanifest.xml")) )
                       )
                    {
                        $pathToManifest = substr($thisContent['filename'],0,-15) ;
                        $pathToManifestFound = true;
                    }
                }
                $realFileSize += $thisContent['size'];
            }

            if (!isset($alreadyFilledSpace)) $alreadyFilledSpace = 0;

            if ( ($realFileSize + $alreadyFilledSpace) > $maxFilledSpace) // check the real size.
            {
                $errorFound = true;
                array_push ($errorMsgs, $langNoSpace ) ;
                $is_allowedToUnzip = false;
            }

            if ($is_allowedToUnzip && !$errorFound)
            {
                // PHP extraction of zip file using zlib

                chdir($baseWorkDir);
                $unzippingState = $zipFile->extract( PCLZIP_OPT_BY_NAME, $pathToManifest."imsmanifest.xml",
                                                     PCLZIP_OPT_PATH, '',
                                                     PCLZIP_OPT_REMOVE_PATH, $pathToManifest );
                if ( $unzippingState == 0 )
                {
                    $errorFound = true;
                    array_push ($errorMsgs, $langErrortExtractingManifest );
                }
            } //end of if ($is_allowedToUnzip)
        } // end of if (!function_exists...
    }
    else
    {
        $errorFound = true;
        array_push ($errorMsgs, $langErrorFileMustBeZip );
    }
    // find xmlmanifest (must be in root else ==> cancel operation, delete files)

    // parse xml manifest to find :
    // package name - learning path name
    // SCO list
    // start asset path

    if ( !$errorFound )
    {
        $elementsPile = array(); // array used to remember where we are in the arborescence of the XML file
        $itemsPile = array(); // array used to remember parents items
        // declaration of global arrays used for extracting needed info from manifest for the new modules/SCO
        $manifestData = array();   // for global data  of the learning path
        $manifestData['items'] = array(); // item tags content (attributes + some child elements data (title for an example)
        $manifestData['scos'] = array();  // for path of start asset id of each new module to create

        $iterator = 0; // will be used to increment position of paths in manifestData['scosPaths"]
                       // and to have the names at the same pos if found

        //$xml_parser = xml_parser_create();
	$xml_parser = xml_parser_create('utf-8');
        xml_set_element_handler($xml_parser, "startElement", "endElement");
        xml_set_character_data_handler($xml_parser, "elementData");

        // this file has to exist in a SCORM conformant package
        // this file must be in the root the sent zip
        $file = "imsmanifest.xml";

        if (!($fp = @fopen($file, "r")))
        {
            $errorFound = true;
            array_push ($errorMsgs, $langErrorOpeningManifest );
        }
        else
        {
            if (!isset($manifestPath)) $manifestPath = "";

            array_push ($okMsgs, $langOkManifestFound.$manifestPath."imsmanifest.xml" );

            while ($data = str_replace("\n","",fread($fp, 4096)))
            {
                // fix for fread breaking thing
                // msg from "ml at csite dot com" 02-Jul-2003 02:29 on http://www.php.net/xml
                // preg expression has been modified to match tag with inner attributes

                if (!isset($cache)) $cache = "";

                $data = $cache . $data;
                if (!feof($fp))
                {
                    // search fo opening, closing, empty tags (with or without attributes)
                    if (preg_match_all("/<[^\>]*.>/", $data, $regs))
                    {
                        $lastTagname = $regs[0][count($regs[0])-1];
                        $split = false;
                        for ($i=strlen($data)-strlen($lastTagname); $i>=strlen($lastTagname); $i--)
                        {
                            if ($lastTagname == substr($data, $i, strlen($lastTagname)))
                            {
                                $cache = substr($data, $i, strlen($data));
                                $data = substr($data, 0, $i);
                                $split = true;
                                break;
                            }
                        }
                    }

                    if (!$split)
                    {
                        $cache = $data;
                    }
                }
                // end of fix

                if (!xml_parse($xml_parser, $data, feof($fp)))
                {
                    // if reading of the xml file in not successfull :
                    // set errorFound, set error msg, break while statement

                    $errorFound = true;
                    array_push ($errorMsgs, $langErrorReadingManifest );
                    break;
                }
            }
            // close file
            fclose($fp);

         }

         // liberate parser ressources
         xml_parser_free($xml_parser);

    } //if (!$errorFound)

    // check if all starts assets files exist in the zip file
    if ( !$errorFound )
    {
        array_push ($okMsgs, $langOkManifestRead );
        if ( sizeof($manifestData['items']) > 0 )
        {
            // if there is items in manifest we look for sco type resources referenced in idientifierref
            foreach ( $manifestData['items'] as $item )
            {
                if ( !isset($item['identifierref']) || $item['identifierref'] == '') break; // skip if no ressource reference in item (item is probably a chapter head)
                // find the file in the zip file
                $scoPathFound = false;

                for ( $i = 0 ; $i < sizeof($zipContentArray) ; $i++)
                {
                    if (isset($manifestData['scos'][$item['identifierref']]['xml:base']))
						$extraPath = $manifestData['scos'][$item['identifierref']]['xml:base'];
					else if (isset($manifestData['assets'][$item['identifierref']]['xml:base']))
						$extraPath = $manifestData['assets'][$item['identifierref']]['xml:base'];
					else
						$extraPath = "";

                    if ( isset($zipContentArray[$i]["filename"]) &&
                        ( ( isset($manifestData['scos'][$item['identifierref']]['href'] )
                            && $zipContentArray[$i]["filename"] == $pathToManifest.$extraPath.$manifestData['scos'][$item['identifierref']]['href'])
                         || (isset($manifestData['assets'][$item['identifierref']]['href'])
                         && $zipContentArray[$i]["filename"] == $pathToManifest.$extraPath.$manifestData['assets'][$item['identifierref']]['href'])
                        )
                       )
                    {
                        $scoPathFound = true;
                        break;
                    }
                }

                if ( !$scoPathFound )
                {
                    $errorFound = true;
                    array_push ($errorMsgs, $langErrorAssetNotFound.$manifestData['scos'][$item['identifierref']]['href'] );
                    break;
                }
            }
        } //if (sizeof ...)
        elseif( sizeof($manifestData['scos']) > 0 )
        {
            // if there ie no items in the manifest file
            // check for scos in resources

            foreach ( $manifestData['scos'] as $sco )
            {
                // find the file in the zip file

                // create a fake item so that the rest of the procedure (add infos of in db) can remains the same
                $manifestData['items'][$sco['href']]['identifierref'] = $sco['href'];
                $manifestData['items'][$sco['href']]['parameters'] = '';
                $manifestData['items'][$sco['href']]['isvisible'] = "true";
                $manifestData['items'][$sco['href']]['title'] = $sco['title'];
                $manifestData['items'][$sco['href']]['description'] = $sco['description'];
                $manifestData['items'][$attributes['IDENTIFIER']]['parent'] = 0;

                $scoPathFound = false;

                for ( $i = 0 ; $i < sizeof($zipContentArray) ; $i++)
                {
                    if ( $zipContentArray[$i]["filename"] == $sco['href'] )
                    {
                        $scoPathFound = true;
                        break;
                    }
                }
                if ( !$scoPathFound )
                {
                    $errorFound = true;
                    array_push ($errorMsgs, $langErrorAssetNotFound.$sco['href'] );
                    break;
                }
            }
        } // if sizeof (...ΰ
        else
        {
            $errorFound = true;
            array_push ($errorMsgs, $langErrorNoModuleInPackage );
        }
    }// if errorFound

    // unzip all files
    // &&
    // insert corresponding entries in database

    if ( !$errorFound )
    {
        // PHP extraction of zip file using zlib
        chdir($baseWorkDir);

        // PCLZIP_OPT_PATH is the path where files will be extracted ( '' )
        // PLZIP_OPT_REMOVE_PATH suppress a part of the path of the file ( $pathToManifest )
        // the result is that the manifest is in th eroot of the path_# directory and all files will have a path related to the root
        $unzippingState = $zipFile->extract(PCLZIP_OPT_PATH, '',PCLZIP_OPT_REMOVE_PATH, $pathToManifest);

        // insert informations in DB :
        //        - 1 learning path ( already added because we needed its id to create the package directory )
        //        - n modules
        //        - n asset as start asset of modules

        if ( sizeof( $manifestData['items'] ) == 0 )
        {
            $errorFound = true;
            array_push ($errorMsgs, $langErrorNoModuleInPackage );
        }
        else
        {
            $i = 0;
            $insertedLPMid = array(); // array of learnPath_module_id && order of related group
            $inRootRank = 1; // default rank for root module (parent == 0)

            foreach ( $manifestData['items'] as $item )
            {
                if ( isset($item['parent']) && isset($insertedLPMid[$item['parent']]) )
                {
                    $parent = $insertedLPMid[$item['parent']]['LPMid'];
                    $rank = $insertedLPMid[$item['parent']]['rank']++;
                }
                else
                {
                    $parent = 0;
                    $rank = $inRootRank++;
                }

                //-------------------------------------------------------------------------------
                // add chapter head
                //-------------------------------------------------------------------------------

                if( (!isset($item['identifierref']) || $item['identifierref'] == '') && isset($item['title']) && $item['title'] !='')
                {
                    // add title as a module
                    $chapterTitle = $item['title'];

                    $sql = "INSERT INTO `".$TABLEMODULE."`
                            (`name` , `comment`, `contentType`, `launch_data`)
                            VALUES ('".addslashes($chapterTitle)."' , '', '".CTLABEL_."','')";

                    $query = db_query($sql);

                    if ( mysql_error() )
                    {
                        $errorFound = true;
                        array_push($errorMsgs, $langErrorSql);
                        break;
                    }
                    $insertedModule_id[$i] = mysql_insert_id();  // array of all inserted module ids

                    // visibility
                    if ( isset($item['isvisible']) && $item['isvisible'] != '' )
                    {
                        ( $item['isvisible'] == "true" )? $visibility = "SHOW": $visibility = "HIDE";
                    }
                    else
                    {
                        $visibility = 'SHOW'; // IMS consider that the default value of 'isvisible' is true
                    }

                    // add title module in the learning path
                    // finally : insert in learning path
                    $sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
                            (`learnPath_id`, `module_id`,`rank`, `visibility`, `parent`)
                            VALUES ('".$tempPathId."', '".$insertedModule_id[$i]."', ".$rank.", '".$visibility."', ".$parent.")";
                    $query = db_query($sql);

                    // get the inserted id of the learnPath_module rel to allow 'parent' link in next inserts
                    $insertedLPMid[$item['itemIdentifier']]['LPMid'] = mysql_insert_id();
                    $insertedLPMid[$item['itemIdentifier']]['rank'] = 1;

                    if ( mysql_error() )
                    {
                        $errorFound = true;
                        array_push($errorMsgs, $langErrorSql);
                        break;
                    }
                    if (!$errorFound)
                    {
                        array_push ($okMsgs, $langOkChapterHeadAdded."<i>".$chapterTitle."</i>" ) ;
                    }
                    $i++;
                    continue;
                }

                // use found title of module or use default title
                if ( !isset( $item['title'] ) || $item['title'] == '')
                {
                    $moduleName = $langUnamedModule;
                }
                else
                {
                    $moduleName = $item['title'];
                }

                // set description as comment or default comment
                // look fo description in item description or in sco (resource) description
                // don't remember why I checked for parameters string ... so comment it
                if ( ( !isset( $item['description'] ) || $item['description'] == '' )
                        &&
                               ( !isset($manifestData['scos'][$item['identifierref']]['description']) /*|| $manifestData['scos'][$item['identifierref']]['parameters'] == ''*/ )
                       )
                {
                    $description = $langDefaultModuleComment;
                }
                else
                {
                    if (  isset( $item['description'] ) && $item['description'] != '' )
                    {
                        $description = $item['description'];
                    }
                    else
                    {
                        $description = $manifestData['scos'][$item['identifierref']]['description'];
                    }
                }

                // insert modules and their start asset
                // create new module

                if (!isset($item['datafromlms'])) $item['datafromlms'] = "";
                
                // elegxoume an to contentType prepei na einai scorm h asset
                if (isset($manifestData['scos'][$item['identifierref']]['contentTypeFlag']) && $manifestData['scos'][$item['identifierref']]['contentTypeFlag'] == CTSCORMASSET_)
                	$contentType = CTSCORMASSET_;
                else
                	$contentType = CTSCORM_;

                $sql = "INSERT INTO `".$TABLEMODULE."`
                        (`name` , `comment`, `contentType`, `launch_data`)
                        VALUES ('".addslashes($moduleName)."' , '".addslashes($description)."', '".$contentType."', '".addslashes($item['datafromlms'])."')";
                $query = db_query($sql);

                if ( mysql_error() )
                {
                    $errorFound = true;
                    array_push($errorMsgs, $langErrorSql);
                    break;
                }

                $insertedModule_id[$i] = mysql_insert_id();  // array of all inserted module ids

                // build asset path
                // a $manifestData['scos'][$item['identifierref']] __SHOULD__ not exist if a $manifestData['assets'][$item['identifierref']] exists
                // so according to IMS we can say that one is empty if the other is filled, so we concat them without more verification than if the var exists.

                if (!isset($manifestData['xml:base']['manifest']))   $manifestData['xml:base']['manifest'] = "";
                if (!isset($manifestData['xml:base']['ressources'])) $manifestData['xml:base']['ressources'] = "";
                if (!isset($manifestData['scos'][$item['identifierref']]['href'])) $manifestData['scos'][$item['identifierref']]['href'] = "";
                if (!isset($manifestData['assets'][$item['identifierref']]['href'])) $manifestData['assets'][$item['identifierref']]['href'] = "";
                if (!isset($manifestData['scos'][$item['identifierref']]['parameters'])) $manifestData['scos'][$item['identifierref']]['parameters'] = "";
                if (!isset($manifestData['assets'][$item['identifierref']]['parameters'])) $manifestData['assets'][$item['identifierref']]['parameters'] = "";

                if (isset($manifestData['scos'][$item['identifierref']]['xml:base']))
                	$extraPath = $manifestData['scos'][$item['identifierref']]['xml:base'];
                else if (isset($manifestData['assets'][$item['identifierref']]['xml:base']))
                	$extraPath = $manifestData['assets'][$item['identifierref']]['xml:base'];
                else
                	$extraPath = "";

                $assetPath = "/"
                             .$manifestData['xml:base']['manifest']
                             .$manifestData['xml:base']['ressources']
                             .$extraPath
                             .$manifestData['scos'][$item['identifierref']]['href']
                             .$manifestData['assets'][$item['identifierref']]['href']
                             .$manifestData['scos'][$item['identifierref']]['parameters']
                             .$manifestData['assets'][$item['identifierref']]['parameters'];

                // create new asset
                $sql = "INSERT INTO `".$TABLEASSET."`
                        (`path` , `module_id` , `comment`)
                        VALUES ('". addslashes($assetPath) ."', ".$insertedModule_id[$i]." , '')";

                $query = db_query($sql);
                if ( mysql_error() )
                {
                    $errorFound = true;
                    array_push($errorMsgs, $langErrorSql);
                    break;
                }

                $insertedAsset_id[$i] = mysql_insert_id(); // array of all inserted asset ids

                // update of module with correct start asset id
                $sql = "UPDATE `".$TABLEMODULE."`
                        SET `startAsset_id` = ". (int)$insertedAsset_id[$i]."
                        WHERE `module_id` = ". (int)$insertedModule_id[$i];
                $query = db_query($sql);

                if ( mysql_error() )
                {
                    $errorFound = true;
                    array_push($errorMsgs, $langErrorSql);
                    break;
                }

                // visibility
                if ( isset($item['isvisible']) && $item['isvisible'] != '' )
                {
                    ( $item['isvisible'] == "true" )? $visibility = "SHOW": $visibility = "HIDE";
                }
                else
                {
                    $visibility = 'SHOW'; // IMS consider that the default value of 'isvisible' is true
                }

                // finally : insert in learning path
                $sql = "INSERT INTO `".$TABLELEARNPATHMODULE."`
                        (`learnPath_id`, `module_id`, `specificComment`, `rank`, `visibility`, `lock`, `parent`)
                        VALUES ('".$tempPathId."', '".$insertedModule_id[$i]."','".addslashes($langDefaultModuleAddedComment)."', ".$rank.", '".$visibility."', 'OPEN', ".$parent.")";
                $query = db_query($sql);

                // get the inserted id of the learnPath_module rel to allow 'parent' link in next inserts
                $insertedLPMid[$item['itemIdentifier']]['LPMid']  = mysql_insert_id();
                $insertedLPMid[$item['itemIdentifier']]['rank']  = 1;


                if ( mysql_error() )
                {
                    $errorFound = true;
                    array_push($errorMsgs, $langErrorSql);
                    break;
                }

                if (!$errorFound)
                {
                    array_push ($okMsgs, $langOkModuleAdded."<i>".$moduleName."</i>" ) ;
                }
                $i++;
            }//foreach
        } // if sizeof($manifestData['items'] == 0 )

    } // if errorFound


    // last step
    // - delete all added files/directories/records in db
    // or
    // - update the learning path record

    if ( $errorFound )
    {

        // delete all database entries of this "module"

        /*
        //this query should work with mysql > 4 to replace
        $sql = "DELETE
                  FROM `".$TABLELEARNPATHMODULE."`,
                       `".$TABLELEARNPATH."`
                  WHERE `".$TABLELEARNPATHMODULE."`.`learnPath_id` = `".$TABLELEARNPATH."`.`learnPath_id`
                    AND `".$TABLELEARNPATH."`.`learnPath_id` = ".$_GET['path_id'] ;
        */

        // queries for mysql previous to 4.0.0

        // delete modules and assets (build query)
        // delete assets
        $sqlDelAssets = "DELETE FROM `".$TABLEASSET."`
                        WHERE 1 = 0";
        foreach ( $insertedAsset_id as $insertedAsset )
        {
            $sqlDelAssets .= " OR `asset_id` = ". (int)$insertedAsset;
        }
        db_query($sqlDelAssets);

        // delete modules
        $sqlDelModules = "DELETE FROM `".$TABLEMODULE."`
                          WHERE 1 = 0";
        foreach ( $insertedModule_id as $insertedModule )
        {
             $sqlDelModules .= " OR `module_id` = ". (int)$insertedModule;
        }
        db_query($sqlDelModules);

        // delete learningPath_module
        $sqlDelLPM = "DELETE FROM `".$TABLELEARNPATHMODULE."`
                      WHERE `learnPath_id` = ". (int)$tempPathId;
        db_query($sqlDelLPM);

        // delete learning path
        $sqlDelLP = "DELETE FROM `".$TABLELEARNPATH."`
                     WHERE `learnPath_id` = ". (int)$tempPathId;
        db_query($sqlDelLP);

        // delete the directory (and files) of this learning path and all its content
        claro_delete_file($baseWorkDir);

    }
    else
    {
        // finalize insertion : update the empty learning path insert that was made to find its id
        $sql = "SELECT MAX(`rank`)
                FROM `".$TABLELEARNPATH."`";
        $result = db_query($sql);

        list($rankMax) = mysql_fetch_row($result);

        if ( isset($manifestData['packageTitle']) )
        {
            $lpName = $manifestData['packageTitle'] ;
        }
        else
        {
            array_push($okMsgs, $langOkDefaultTitleUsed );
        }

        if ( isset($manifestData['packageDesc']) )
        {
            $lpComment = $manifestData['packageDesc'];
        }
        else
        {
            $lpComment = $langDefaultLearningPathComment;
            array_push($okMsgs, $langOkDefaultCommentUsed );
        }

        $sql = "UPDATE `".$TABLELEARNPATH."`
                SET `rank` = ".($rankMax+1).",
                    `name` = '".addslashes($lpName)."',
                    `comment` = '".addslashes($lpComment)."',
                    `visibility` = 'SHOW'
                WHERE `learnPath_id` = ". (int)$tempPathId;
        db_query($sql);

    }

    /*--------------------------------------
      status messages
     --------------------------------------*/
    $tool_content .= "\n<p><!-- Messages -->\n";
    foreach ( $okMsgs as $msg)
    {
        $tool_content .= "\n<b>[</b><span class=\"correct\">$langSuccessOk</span><b>]</b>&nbsp;&nbsp;&nbsp;".$msg."<br />";
    }

    foreach ( $errorMsgs as $msg)
    {
        $tool_content .= "\n<b>[</b><span class=\"error\">$langError</span><b>]</b>&nbsp;&nbsp;&nbsp;".$msg."<br />";
    }

    $tool_content .= "\n<!-- End messages -->\n";

    // installation completed or not message
    if ( !$errorFound )
    {
        $tool_content .= "\n<br /><center><b>".$langInstalled."</b></center>";
        $tool_content .= "\n<br /><br ><center><a href=\"learningPathAdmin.php?path_id=".$tempPathId."\">".$lpName."</a></center>";
    }
    else
    {
        $tool_content .= "\n<br /><center><b>".$langNotInstalled."</b></center>";
    }
    $tool_content .= "\n<br /><a href=\"learningPathList.php\">$langBack</a></p>";
}
else // if method == 'post'
{
    // don't display the form if user already sent it
    /*--------------------------------------
      UPLOAD FORM
     --------------------------------------*/
    $tool_content .= "
    <form enctype=\"multipart/form-data\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">
    <table width=\"99%\" align=\"left\" class=\"FormData\">
    <tbody>
    <tr>
      <th width=\"30%\">&nbsp;</th>
      <td><b>$langAskUserFile</b></td>
    </tr>
    <tr>
      <th class=\"left\">$langLearningPathUploadFile :</th>
      <td>
          <input type=\"hidden\" name=\"claroFormId\" value=\"".uniqid('')."\" >
          <input type=\"file\" name=\"uploadedPackage\">
      </td>
    </tr>
    <tr>
      <th class=\"left\">&nbsp;</th>
      <td><input type=\"submit\" value=\"".$langImport."\"></td>
    </tr>
    </tbody>
    </table>
    <p align=\"right\"><small>$langMaxFileSize ".ini_get('upload_max_filesize')."</small></p>
    <br />
    </form>
    <p>";
    
    
    /*****************************************
     *  IMPORT SCORM DIRECTLY FROM DOCUMENTS
     *****************************************/
    $basedir = $webDir . 'courses/' . $currentCourseID . '/document';

    /*** Retrieve file info for current directory from database and disk ***/
    $sql = db_query("SELECT * FROM document WHERE format='zip' ORDER BY filename");

    $fileinfo = array();
    while($row = mysql_fetch_array($sql, MYSQL_ASSOC)) {
	$fileinfo[] = array(
		'is_dir' => is_dir($basedir . $row['path']),
		'size' => filesize($basedir . $row['path']),
		'title' => $row['title'],
		'filename' => $row['filename'],
		'format' => $row['format'],
		'path' => $row['path'],
		'visible' => ($row['visibility'] == 'v'),
		'comment' => $row['comment'],
		'copyrighted' => $row['copyrighted'],
		'date' => strtotime($row['date_modified']));
    }
    
    if (mysql_num_rows($sql) != 0) {
		$tool_content .= "\n<div class=\"fileman\">";
		$tool_content .= "\n<form action='importFromDocument.php' method='post'>";
		$tool_content .= "\n  <table width=\"99%\" align='left' class=\"Documents\">";
		$tool_content .= "\n  <tbody>";
		$tool_content .= "\n  <tr><th height='18' colspan='5'><div align='left'><strong>$langLearningPathImportFromDocuments</strong</div></th></tr>";
		$tool_content .= "\n  <tr>";
		$tool_content .= "\n    <td></td>";
		$tool_content .= "\n    <td width='10%' class='DocHead'><div align='center'><b>$langType</b></div></td>";
		$tool_content .= "\n    <td class='DocHead'><div align='left'><b>$langName</b></div></td>";
		$tool_content .= "\n    <td width='15%' class='DocHead'><div align='center'><b>$langSize</b></div></td>";
		$tool_content .= "\n    <td width='15%' class='DocHead'><div align='center'><b>$langDate</b></div></td>";
		$tool_content .= "\n  </tr>";

		foreach ($fileinfo as $entry) {
		    if ($entry['is_dir']) // do not handle directories
		      continue;
	
		    $cmdDirName = $entry['path'];
		    $copyright_icon = '';
		    $image = '../document/img/' . choose_image('.' . $entry['format']);
		    $size = format_file_size($entry['size']);
			$date = format_date($entry['date']);
		    
			if ($entry['visible'])
				$style = '';
			else
				$style = ' class="invisible"';
		    
			if (empty($entry['title']))
				$link_text = $entry['filename'];
		    else
				$link_text = $entry['title'];

			if ($entry['copyrighted'])
				$link_text .= " <img src='../document/img/copyrighted.jpg' />";

		    $tool_content .= "\n  <tr$style>";
		    $tool_content .= "\n    <td><input type='radio' name='selectedDocument' value='".$entry['path']."'/></td>";
		    $tool_content .= "\n    <td width='1%' valign='top' align='center' style='padding-top: 7px;'><img src='$image' border='0' /></td>";
		    $tool_content .= "\n    <td><div align='left'>$link_text";
	
			if (!empty($entry['comment']))
				$tool_content .= "<br /><span class='commentDoc'>" . nl2br(htmlspecialchars($entry['comment'])) . "</span>\n";
		
			$tool_content .= "</div></td>\n";
			$tool_content .= "<td><div align='center'>$size</div></td><td><div align='center'>$date</div></td>";
		}

		$tool_content .=  "\n  <tr>";
		$tool_content .= "\n    <td colspan='2'></td>";
		$tool_content .= "\n    <td colspan='3'><div align='left'><input type='submit' value='".$langImport."'></div></td>";
		$tool_content .=  "\n  </tr>";
		$tool_content .=  "\n  </tbody>";
		$tool_content .=  "\n  </table>";
		$tool_content .= "\n</form>";
		$tool_content .=  "\n</div>";
	}
    
	$tool_content .= "</p>
		<p><u>$langNote</u> :</p>
		<p>$langScormIntroTextForDummies</p>";

} // else if method == 'post'

chdir($pwd);
draw($tool_content, 2, "learnPath");
?>
