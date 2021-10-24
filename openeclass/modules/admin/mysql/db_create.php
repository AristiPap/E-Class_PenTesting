<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id: db_create.php 11982 2008-11-24 10:32:56Z nijel $
 * @package phpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
$GLOBALS['js_include'][] = 'functions.js';
require_once './libraries/mysql_charsets.lib.php';

PMA_checkParameters(array('new_db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'main.php?' . PMA_generate_common_url();

/**
 * Builds and executes the db creation sql query
 */
$sql_query = 'CREATE DATABASE ' . PMA_backquote($new_db);
if (!empty($db_collation)) {
    list($db_charset) = explode('_', $db_collation);
    if (in_array($db_charset, $mysql_charsets) && in_array($db_collation, $mysql_collations[$db_charset])) {
        $sql_query .= ' DEFAULT' . PMA_generateCharsetQueryPart($db_collation);
    }
    unset($db_charset, $db_collation);
}
$sql_query .= ';';

$result = PMA_DBI_try_query($sql_query);

if (! $result) {
    $message = PMA_Message::rawError(PMA_DBI_getError());
    // avoid displaying the not-created db name in header or navi panel
    $GLOBALS['db'] = '';
    $GLOBALS['table'] = '';
    require_once './libraries/header.inc.php';
    require_once './main.php';
} else {
    $message = PMA_Message::success('strDatabaseHasBeenCreated');
    $message->addParam($new_db);
    $GLOBALS['db'] = $new_db;

    require_once './libraries/header.inc.php';
    require_once './' . $cfg['DefaultTabDatabase'];
}
?>
