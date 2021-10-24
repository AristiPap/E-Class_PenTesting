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
/**===========================================================================
	class.wiki2xhtmlarea.php
	@last update: 15-05-2007 by Thanos Kyritsis
	@authors list: Thanos Kyritsis <atkyritsis@upnet.gr>
	               
	based on Claroline version 1.7.9 licensed under GPL
	      copyright (c) 2001, 2007 Universite catholique de Louvain (UCL)
	      
	      original file: class.wiki2xhtmlarea Revision: 1.10.2.2
	      
	Claroline authors: Frederic Minne <zefredz@gmail.com>
==============================================================================        
    @Description: 

    @Comments:
 
    @todo: 
==============================================================================
*/
     

    require_once dirname(__FILE__) . "/lib.javascript.php";
    
    /**
     * Wiki2xhtml editor textarea
     */
    class Wiki2xhtmlArea
    {
        var $content;
        var $attributeList;
        
        /**
         * Constructor
         * @param string content of the area
         * @param string name name of the area
         * @param int cols number of cols
         * @param int rows number of rows
         * @param array extraAttributes extra html attributes for the area
         */
        function Wiki2xhtmlArea(
            $content = ''
            , $name = 'wiki_content'
            , $cols = 80
            , $rows = 30
            , $extraAttributes = null )
        {
            $this->setContent( $content );
            
            $attributeList = array();
            $attributeList['name'] = $name;
            $attributeList['id'] = $name;
            $attributeList['cols'] = $cols;
            $attributeList['rows'] = $rows;
            
            $this->attributeList = ( is_array( $extraAttributes ) )
                ? array_merge( $attributeList, $extraAttributes )
                : $attributeList
                ;
        }
        
        /**
         * Set area content
         * @param string content
         */
        function setContent( $content )
        {
            $this->content = $content;
        }
        
        /**
         * Get area content
         * @return string area content
         */
        function getContent()
        {
            return $this->content;
        }
        
        /**
         * Get area wiki syntax toolbar
         * @return string toolbar javascript code
         */
        function getToolbar()
        {

	    global $wiki_toolbar, $langWikiUrl, $langWikiUrlLang;
            $toolbar = '';

            $toolbar .= '<script type="text/javascript" src="'
                .document_web_path().'/lib/javascript/toolbar.js"></script>'
                . "\n"
                ;
            $toolbar .= "<script type=\"text/javascript\">if (document.getElementById) {
		var tb = new dcToolBar(document.getElementById('".$this->attributeList['id']."'),
		'wiki','".document_web_path()."/toolbar/');

        	tb.btStrong('".$wiki_toolbar['Strongemphasis']."');
		tb.btEm('".$wiki_toolbar['Emphasis']."');
		tb.btIns('".$wiki_toolbar['Inserted']."');
		tb.btDel('".$wiki_toolbar['Deleted']."');
		tb.btQ('".$wiki_toolbar['Inlinequote']."');
		tb.btCode('".$wiki_toolbar['Code']."');
		tb.addSpace(10);
		tb.btBr('".$wiki_toolbar['Linebreak']."');
		tb.addSpace(10);
		tb.btBquote('".$wiki_toolbar['Blockquote']."');
		tb.btPre('".$wiki_toolbar['Preformatedtext']."');
		tb.btList('".$wiki_toolbar['Unorderedlist']."','ul');
		tb.btList('".$wiki_toolbar['Orderedlist']."','ol');
		tb.addSpace(10);
        	tb.btLink('".$wiki_toolbar['Link']."','".$langWikiUrl."','".$langWikiUrlLang."','gr');
        	tb.btImgLink('".$wiki_toolbar['Externalimage']."','".$langWikiUrl."');
		tb.draw('');
	}
	</script>\n";
            
            return $toolbar;
        }
        
        /**
         * paint (ie echo) area
         */
        function paint()
        {
            echo $this->toHTML();
        }
        
        /**
         * get area html code for string inclusion
         * @return string area html code
         */
        function toHTML()
        {
            $wikiarea = '';

            $attr = '';

            foreach( $this->attributeList as $attribute => $value )
            {
                $attr .= ' ' . $attribute . '="' . $value . '"';
            }

            $wikiarea .= '<textarea'.$attr.'>'.$this->getContent().'</textarea>' . "\n";

            $wikiarea .= $this->getToolbar();

            return $wikiarea;
        }
    }
?>
