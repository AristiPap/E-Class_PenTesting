<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id: themes.php 12281 2009-03-03 15:20:29Z nijel $
 * @package phpMyAdmin
 */

/**
 * get some globals
 */
require_once './libraries/common.inc.php';

/* Theme Select */
$path_to_themes = $cfg['ThemePath'] . '/';

/* set language and charset */
require_once './libraries/header_http.inc.php';

/* HTML header */
$page_title = 'phpMyAdmin - ' . $strTheme;
require './libraries/header_meta_style.inc.php';
?>
<script type="text/javascript" language="javascript">
// <![CDATA[
function takeThis(what){
    if (window.opener && window.opener.document.forms['setTheme'].elements['set_theme']) {
        window.opener.document.forms['setTheme'].elements['set_theme'].value = what;
        window.opener.document.forms['setTheme'].submit();
        self.close();
    } else {
        alert('<?php echo sprintf($strNoThemeSupport, $cfg['ThemePath']); ?>');
        self.close();
    }
}
// ]]>
</script>
</head>

<body id="bodythemes">
<h1>phpMyAdmin - <?php echo $strTheme; ?></h1>
<p><a href="http://www.phpmyadmin.net/home_page/themes.php#pma_<?php echo preg_replace('/([0-9]*)\.([0-9]*)\..*/', '\1_\2', PMA_VERSION); ?>"><?php echo $strGetMoreThemes; ?></a></p>
<?php
$_SESSION['PMA_Theme_Manager']->printPreviews();
?>
</body>
</html>
