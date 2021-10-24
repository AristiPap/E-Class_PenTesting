<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like fields/columns, indexes, size, rows
 * and allows manipulation of indexes and columns/fields
 * @version $Id: tbl_structure.php 12546 2009-06-08 12:04:35Z lem9 $
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/mysql_charsets.lib.php';
require_once './libraries/relation.lib.php';

$GLOBALS['js_include'][] = 'mootools.js';

/**
 * handle multiple field commands if required
 *
 * submit_mult_*_x comes from IE if <input type="img" ...> is used
 */
if (isset($_REQUEST['submit_mult_change_x'])) {
    $submit_mult = $strChange;
} elseif (isset($_REQUEST['submit_mult_drop_x'])) {
    $submit_mult = $strDrop;
} elseif (isset($_REQUEST['submit_mult_primary_x'])) {
    $submit_mult = $strPrimary;
} elseif (isset($_REQUEST['submit_mult_index_x'])) {
    $submit_mult = $strIndex;
} elseif (isset($_REQUEST['submit_mult_unique_x'])) {
    $submit_mult = $strUnique;
} elseif (isset($_REQUEST['submit_mult_fulltext_x'])) {
    $submit_mult = $strIdxFulltext;
} elseif (isset($_REQUEST['submit_mult_browse_x'])) {
    $submit_mult = $strBrowse;
} elseif (isset($_REQUEST['submit_mult'])) {
    $submit_mult = $_REQUEST['submit_mult'];
} elseif (isset($_REQUEST['mult_btn']) && $_REQUEST['mult_btn'] == $strYes) {
    $submit_mult = 'row_delete';
    if (isset($_REQUEST['selected'])) {
        $_REQUEST['selected_fld'] = $_REQUEST['selected'];
    }
}

if (! empty($submit_mult) && isset($_REQUEST['selected_fld'])) {
    $err_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);
    if ($submit_mult == $strBrowse) {
        // browsing the table displaying only selected fields/columns
        $GLOBALS['active_page'] = 'sql.php';
        $sql_query = '';
        foreach ($_REQUEST['selected_fld'] as $idx => $sval) {
            if ($sql_query == '') {
                $sql_query .= 'SELECT ' . PMA_backquote($sval);
            } else {
                $sql_query .=  ', ' . PMA_backquote($sval);
            }
        }

        // what is this htmlspecialchars() for??
        //$sql_query .= ' FROM ' . PMA_backquote(htmlspecialchars($table));
        $sql_query .= ' FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
        require './sql.php';
        exit;
    } else {
        // handle multiple field commands
        // handle confirmation of deleting multiple fields/columns
        $action = 'tbl_structure.php';
        require './libraries/mult_submits.inc.php';
        //require_once './libraries/header.inc.php';
        //require_once './libraries/tbl_links.inc.php';

        if (empty($message)) {
            $message = PMA_Message::success();
        }
    }
}

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * Runs common work
 */
require_once './libraries/tbl_common.php';
$url_query .= '&amp;goto=tbl_structure.php&amp;back=tbl_structure.php';
$url_params['goto'] = 'tbl_structure.php';
$url_params['back'] = 'tbl_structure.php';

/**
 * Prepares the table structure display
 */


/**
 * Gets tables informations
 */
require_once './libraries/tbl_info.inc.php';

/**
 * Displays top menu links
 */
require_once './libraries/tbl_links.inc.php';
require_once './libraries/Index.class.php';

// 2. Gets table keys and retains them
// @todo should be: $server->db($db)->table($table)->primary()
$primary = PMA_Index::getPrimary($table, $db);


// 3. Get fields
$fields_rs   = PMA_DBI_query('SHOW FULL FIELDS FROM ' . PMA_backquote($table) . ';', null, PMA_DBI_QUERY_STORE);
$fields_cnt  = PMA_DBI_num_rows($fields_rs);


// Get more complete field information
// For now, this is done just for MySQL 4.1.2+ new TIMESTAMP options
// but later, if the analyser returns more information, it
// could be executed for any MySQL version and replace
// the info given by SHOW FULL FIELDS FROM.
//
// We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
// SHOW FULL FIELDS or INFORMATION_SCHEMA incorrectly says NULL
// and SHOW CREATE TABLE says NOT NULL (tested
// in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

$show_create_table = PMA_DBI_fetch_value(
        'SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table),
        0, 1);
$analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

/**
 * prepare table infos
 */
// action titles (image or string)
$titles = array();
$titles['Change']               = PMA_getIcon('b_edit.png', $strChange, true);
$titles['Drop']                 = PMA_getIcon('b_drop.png', $strDrop, true);
$titles['NoDrop']               = PMA_getIcon('b_drop.png', $strDrop, true);
$titles['Primary']              = PMA_getIcon('b_primary.png', $strPrimary, true);
$titles['Index']                = PMA_getIcon('b_index.png', $strIndex, true);
$titles['Unique']               = PMA_getIcon('b_unique.png', $strUnique, true);
$titles['IdxFulltext']          = PMA_getIcon('b_ftext.png', $strIdxFulltext, true);
$titles['NoPrimary']            = PMA_getIcon('bd_primary.png', $strPrimary, true);
$titles['NoIndex']              = PMA_getIcon('bd_index.png', $strIndex, true);
$titles['NoUnique']             = PMA_getIcon('bd_unique.png', $strUnique, true);
$titles['NoIdxFulltext']        = PMA_getIcon('bd_ftext.png', $strIdxFulltext, true);
$titles['BrowseDistinctValues'] = PMA_getIcon('b_browse.png', $strBrowseDistinctValues, true);

/**
 * Displays the table structure ('show table' works correct since 3.23.03)
 */
/* TABLE INFORMATION */
// table header
$i = 0;
?>
<form method="post" action="tbl_structure.php" name="fieldsForm" id="fieldsForm">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<table id="tablestructure" class="data">
<thead>
<tr>
    <th id="th<?php echo ++$i; ?>"></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strField; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strType; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strCollation; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strAttr; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strNull; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strDefault; ?></th>
    <th id="th<?php echo ++$i; ?>"><?php echo $strExtra; ?></th>
<?php if ($db_is_information_schema || $tbl_is_view) { ?>
    <th id="th<?php echo ++$i; ?>"><?php echo $strView; ?></th>
<?php } else { ?>
    <th colspan="7" id="th<?php echo ++$i; ?>"><?php echo $strAction; ?></th>
<?php } ?>
</tr>
</thead>
<tbody>
<?php
unset($i);


// table body

// prepare comments
$comments_map = array();
$mime_map = array();

if ($GLOBALS['cfg']['ShowPropertyComments']) {
    require_once './libraries/relation.lib.php';
    require_once './libraries/transformations.lib.php';

    //$cfgRelation = PMA_getRelationsParam();

    $comments_map = PMA_getComments($db, $table);

    if ($cfgRelation['mimework'] && $cfg['BrowseMIME']) {
        $mime_map = PMA_getMIME($db, $table, true);
    }
}

$rownum    = 0;
$aryFields = array();
$checked   = (!empty($checkall) ? ' checked="checked"' : '');
$save_row  = array();
$odd_row   = true;
while ($row = PMA_DBI_fetch_assoc($fields_rs)) {
    $save_row[] = $row;
    $rownum++;
    $aryFields[]      = $row['Field'];

    $type             = $row['Type'];
    $extracted_fieldspec = PMA_extractFieldSpec($row['Type']);

    if ('set' == $extracted_fieldspec['type'] || 'enum' == $extracted_fieldspec['type']) {
        $type         = $extracted_fieldspec['type'] . '(' . $extracted_fieldspec['spec_in_brackets'] . ')';

        // for the case ENUM('&#8211;','&ldquo;')
        $type         = htmlspecialchars($type);

        $type_nowrap  = '';

        $binary       = 0;
        $unsigned     = 0;
        $zerofill     = 0;
    } else {
        $type_nowrap  = ' nowrap="nowrap"';
        // strip the "BINARY" attribute, except if we find "BINARY(" because
        // this would be a BINARY or VARBINARY field type
        if (!preg_match('@BINARY[\(]@i', $type)) {
            $type         = preg_replace('@BINARY@i', '', $type);
        }
        $type         = preg_replace('@ZEROFILL@i', '', $type);
        $type         = preg_replace('@UNSIGNED@i', '', $type);
        if (empty($type)) {
            $type     = ' ';
        }

        if (!preg_match('@BINARY[\(]@i', $row['Type'])) {
            $binary           = stristr($row['Type'], 'blob') || stristr($row['Type'], 'binary');
        } else {
            $binary           = false;
        }

        $unsigned     = stristr($row['Type'], 'unsigned');
        $zerofill     = stristr($row['Type'], 'zerofill');
    }

    unset($field_charset);
    if ((substr($type, 0, 4) == 'char'
        || substr($type, 0, 7) == 'varchar'
        || substr($type, 0, 4) == 'text'
        || substr($type, 0, 8) == 'tinytext'
        || substr($type, 0, 10) == 'mediumtext'
        || substr($type, 0, 8) == 'longtext'
        || substr($type, 0, 3) == 'set'
        || substr($type, 0, 4) == 'enum'
        ) && !$binary) {
        if (strpos($type, ' character set ')) {
            $type = substr($type, 0, strpos($type, ' character set '));
        }
        if (!empty($row['Collation'])) {
            $field_charset = $row['Collation'];
        } else {
            $field_charset = '';
        }
    } else {
        $field_charset = '';
    }

    // garvin: Display basic mimetype [MIME]
    if ($cfgRelation['commwork'] && $cfgRelation['mimework'] && $cfg['BrowseMIME'] && isset($mime_map[$row['Field']]['mimetype'])) {
        $type_mime = '<br />MIME: ' . str_replace('_', '/', $mime_map[$row['Field']]['mimetype']);
    } else {
        $type_mime = '';
    }

    $attribute     = ' ';
    if ($binary) {
        $attribute = 'BINARY';
    }
    if ($unsigned) {
        $attribute = 'UNSIGNED';
    }
    if ($zerofill) {
        $attribute = 'UNSIGNED ZEROFILL';
    }

    // MySQL 4.1.2+ TIMESTAMP options
    // (if on_update_current_timestamp is set, then it's TRUE)
    if (isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['on_update_current_timestamp'])) {
        $attribute = 'on update CURRENT_TIMESTAMP';
    }

    // here, we have a TIMESTAMP that SHOW FULL FIELDS reports as having the
    // NULL attribute, but SHOW CREATE TABLE says the contrary. Believe
    // the latter.
    if (!empty($analyzed_sql[0]['create_table_fields'][$row['Field']]['type']) && $analyzed_sql[0]['create_table_fields'][$row['Field']]['type'] == 'TIMESTAMP' && $analyzed_sql[0]['create_table_fields'][$row['Field']]['timestamp_not_null']) {
        $row['Null'] = '';
    }


    if (!isset($row['Default'])) {
        if ($row['Null'] == 'YES') {
            $row['Default'] = '<i>NULL</i>';
        }
    } else {
        $row['Default'] = htmlspecialchars($row['Default']);
    }

    $field_encoded = urlencode($row['Field']);
    $field_name    = htmlspecialchars($row['Field']);

    // garvin: underline commented fields and display a hover-title (CSS only)

    $comment_style = '';
    if (isset($comments_map[$row['Field']])) {
        $field_name = '<span style="border-bottom: 1px dashed black;" title="' . htmlspecialchars($comments_map[$row['Field']]) . '">' . $field_name . '</span>';
    }

    if ($primary && $primary->hasColumn($field_name)) {
        $field_name = '<u>' . $field_name . '</u>';
    }
    echo "\n";
    ?>
<tr class="<?php echo $odd_row ? 'odd': 'even'; $odd_row = !$odd_row; ?>">
    <td align="center">
        <input type="checkbox" name="selected_fld[]" value="<?php echo htmlspecialchars($row['Field']); ?>" id="checkbox_row_<?php echo $rownum; ?>" <?php echo $checked; ?> />
    </td>
    <th nowrap="nowrap"><label for="checkbox_row_<?php echo $rownum; ?>"><?php echo $field_name; ?></label></th>
    <td<?php echo $type_nowrap; ?>><bdo dir="ltr" xml:lang="en"><?php echo $type; echo $type_mime; ?></bdo></td>
    <td><?php echo (empty($field_charset) ? '' : '<dfn title="' . PMA_getCollationDescr($field_charset) . '">' . $field_charset . '</dfn>'); ?></td>
    <td nowrap="nowrap" style="font-size: 70%"><?php echo $attribute; ?></td>
    <td><?php echo (($row['Null'] == 'YES') ? $strYes : $strNo); ?></td>
    <td nowrap="nowrap"><?php
    if (isset($row['Default'])) {
        if ($extracted_fieldspec['type'] == 'bit') {
            echo PMA_printable_bit_value($row['Default'], $extracted_fieldspec['spec_in_brackets']);
        } else {
            echo $row['Default'];
        }
    }
    else {
        echo '<i>' . $strNoneDefault . '</i>';
    } ?></td>
    <td nowrap="nowrap"><?php echo $row['Extra']; ?></td>
    <td align="center">
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('SELECT COUNT(*) AS ' . PMA_backquote($strRows) . ', ' . PMA_backquote($row['Field']) . ' FROM ' . PMA_backquote($table) . ' GROUP BY ' . PMA_backquote($row['Field']) . ' ORDER BY ' . PMA_backquote($row['Field'])); ?>">
            <?php echo $titles['BrowseDistinctValues']; ?></a>
    </td>
    <?php if (! $tbl_is_view && ! $db_is_information_schema) { ?>
    <td align="center">
        <a href="tbl_alter.php?<?php echo $url_query; ?>&amp;field=<?php echo $field_encoded; ?>">
            <?php echo $titles['Change']; ?></a>
    </td>
    <td align="center">
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP ' . PMA_backquote($row['Field'])); ?>&amp;cpurge=1&amp;purgekey=<?php echo urlencode($row['Field']); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strFieldHasBeenDropped, htmlspecialchars($row['Field']))); ?>"
            onclick="return confirmLink(this, 'ALTER TABLE <?php echo PMA_jsFormat($table); ?> DROP <?php echo PMA_jsFormat($row['Field']); ?>')">
            <?php echo $titles['Drop']; ?></a>
    </td>
    <td align="center">
        <?php
        if ($type == 'text' || $type == 'blob' || 'ARCHIVE' == $tbl_type) {
            echo $titles['NoPrimary'] . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ($primary ? ' DROP PRIMARY KEY,' : '') . ' ADD PRIMARY KEY(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAPrimaryKey, htmlspecialchars($row['Field']))); ?>"
            onclick="return confirmLink(this, 'ALTER TABLE <?php echo PMA_jsFormat($table) . ($primary ? ' DROP PRIMARY KEY,' : ''); ?> ADD PRIMARY KEY(<?php echo PMA_jsFormat($row['Field']); ?>)')">
            <?php echo $titles['Primary']; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <td align="center">
        <?php
        if ($type == 'text' || $type == 'blob' || 'ARCHIVE' == $tbl_type) {
            echo $titles['NoUnique'] . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD UNIQUE(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex, htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['Unique']; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <td align="center">
        <?php
        if ($type == 'text' || $type == 'blob' || 'ARCHIVE' == $tbl_type) {
            echo $titles['NoIndex'] . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD INDEX(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex, htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['Index']; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <?php
        if (! empty($tbl_type) && ($tbl_type == 'MYISAM' || $tbl_type == 'MARIA')
            // FULLTEXT is possible on TEXT, CHAR and VARCHAR
            && (strpos(' ' . $type, 'text') || strpos(' ' . $type, 'char'))) {
            echo "\n";
            ?>
    <td align="center" nowrap="nowrap">
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD FULLTEXT(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex, htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['IdxFulltext']; ?></a>
    </td>
            <?php
        } else {
            echo "\n";
        ?>
    <td align="center" nowrap="nowrap">
        <?php echo $titles['NoIdxFulltext'] . "\n"; ?>
    </td>
        <?php
        } // end if... else...
        echo "\n";
    } // end if (! $tbl_is_view && ! $db_is_information_schema)
    ?>
</tr>
    <?php
    unset($field_charset);
} // end while

echo '</tbody>' . "\n"
    .'</table>' . "\n";

$checkall_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);
?>

<img class="selectallarrow" src="<?php echo $pmaThemeImage . 'arrow_' . $text_dir . '.png'; ?>"
    width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
<a href="<?php echo $checkall_url; ?>&amp;checkall=1"
    onclick="if (markAllRows('fieldsForm')) return false;">
    <?php echo $strCheckAll; ?></a>
/
<a href="<?php echo $checkall_url; ?>"
    onclick="if (unMarkAllRows('fieldsForm')) return false;">
    <?php echo $strUncheckAll; ?></a>

<i><?php echo $strWithChecked; ?></i>

<?php
PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_browse', $strBrowse, 'b_browse.png');

if (! $tbl_is_view && ! $db_is_information_schema) {
    PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_change', $strChange, 'b_edit.png');
    PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_drop', $strDrop, 'b_drop.png');
    if ('ARCHIVE' != $tbl_type) {
        PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_primary', $strPrimary, 'b_primary.png');
        PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_unique', $strUnique, 'b_unique.png');
        PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_index', $strIndex, 'b_index.png');
    }
    if (! empty($tbl_type) && ($tbl_type == 'MYISAM' || $tbl_type == 'MARIA')) {
        PMA_buttonOrImage('submit_mult', 'mult_submit', 'submit_mult_fulltext', $strIdxFulltext, 'b_ftext.png');
    }
}
?>
</form>
<hr />

<?php
/**
 * Work on the table
 */
?>
<a href="tbl_printview.php?<?php echo $url_query; ?>"><?php
if ($cfg['PropertiesIconic']) {
    echo '<img class="icon" src="' . $pmaThemeImage . 'b_print.png" width="16" height="16" alt="' . $strPrintView . '"/>';
}
echo $strPrintView;
?></a>

<?php
if (! $tbl_is_view && ! $db_is_information_schema) {

    // if internal relations are available, or foreign keys are supported
    // ($tbl_type comes from libraries/tbl_info.inc.php)
    if ($cfgRelation['relwork'] || PMA_foreignkey_supported($tbl_type)) {
        ?>
<a href="tbl_relation.php?<?php echo $url_query; ?>"><?php
        if ($cfg['PropertiesIconic']) {
            echo '<img class="icon" src="' . $pmaThemeImage . 'b_relations.png" width="16" height="16" alt="' . $strRelationView . '"/>';
        }
        echo $strRelationView;
        ?></a>
        <?php
    }
    ?>
<a href="sql.php?<?php echo $url_query; ?>&amp;session_max_rows=all&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table) . ' PROCEDURE ANALYSE()'); ?>"><?php
    if ($cfg['PropertiesIconic']) {
        echo '<img class="icon" src="' . $pmaThemeImage . 'b_tblanalyse.png" width="16" height="16" alt="' . $strStructPropose . '" />';
    }
    echo $strStructPropose;
    ?></a><?php
    echo PMA_showMySQLDocu('Extending_MySQL', 'procedure_analyse') . "\n";
    ?><br />
<form method="post" action="tbl_addfield.php"
    onsubmit="return checkFormElementInRange(this, 'num_fields', '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidFieldAddCount']); ?>', 1)">
    <?php
    echo PMA_generate_common_hidden_inputs($db, $table);
    if ($cfg['PropertiesIconic']) {
        echo '<img class="icon" src="' . $pmaThemeImage . 'b_insrow.png" width="16" height="16" alt="' . $strAddNewField . '"/>';
    }
    echo sprintf($strAddFields, '<input type="text" name="num_fields" size="2" maxlength="2" value="1" style="vertical-align: middle" onfocus="this.select()" />');

    // I tried displaying the drop-down inside the label but with Firefox
    // the drop-down was blinking
    $fieldOptions = '<select name="after_field" style="vertical-align: middle" onclick="this.form.field_where[2].checked=true" onchange="this.form.field_where[2].checked=true">';
    foreach ($aryFields as $fieldname) {
        $fieldOptions .= '<option value="' . htmlspecialchars($fieldname) . '">' . htmlspecialchars($fieldname) . '</option>' . "\n";
    }
    unset($aryFields);
    $fieldOptions .= '</select>';

    $choices = array(
        'last'  => $strAtEndOfTable,
        'first' => $strAtBeginningOfTable,
        'after' => sprintf($strAfter, '')
    );
    PMA_generate_html_radio('field_where', $choices, 'last', false);
    echo $fieldOptions;
    unset($fieldOptions, $choices);
    ?>
<input type="submit" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
</form>
<hr />
    <?php
}

/**
 * If there are more than 20 rows, displays browse/select/insert/empty/drop
 * links again
 */
if ($fields_cnt > 20) {
    require './libraries/tbl_links.inc.php';
} // end if ($fields_cnt > 20)

/**
 * Displays indexes
 */
PMA_generate_slider_effect('tablestatistics_indexes', $strDetails);

if (! $tbl_is_view && ! $db_is_information_schema && 'ARCHIVE' !=  $tbl_type) {
    /**
     * Display indexes
     */
    echo PMA_Index::getView($table, $db);
    ?>
<br />
<form action="./tbl_indexes.php" method="post"
    onsubmit="return checkFormElementInRange(this, 'idx_num_fields',
        '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidColumnCount']); ?>',
        1)">
<fieldset>
    <?php
    echo PMA_generate_common_hidden_inputs($db, $table);
    echo sprintf($strCreateIndex,
        '<input type="text" size="2" name="added_fields" value="1" />');
    ?>
    <input type="submit" name="create_index" value="<?php echo $strGo; ?>"
        onclick="return checkFormElementInRange(this.form,
            'idx_num_fields',
            '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidColumnCount']); ?>',
            1)" />
</fieldset>
</form>
<br />
    <?php
}
echo '<div id="tablestatistics">' . "\n";

/**
 * Displays Space usage and row statistics
 */
// BEGIN - Calc Table Space - staybyte - 9 June 2001
// loic1, 22 feb. 2002: updated with patch from
//                      Joshua Nye <josh at boxcarmedia.com> to get valid
//                      statistics whatever is the table type
if ($cfg['ShowStats']) {
    if (empty($showtable)) {
        $showtable = PMA_Table::sGetStatusInfo($GLOBALS['db'], $GLOBALS['table'], null, true);
    }

    $nonisam     = false;
    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');
    if (isset($showtable['Type']) && !preg_match('@ISAM|HEAP@i', $showtable['Type'])) {
        $nonisam = true;
    }

    // Gets some sizes
    $mergetable     = false;
    if (isset($showtable['Type']) && $showtable['Type'] == 'MRG_MyISAM') {
        $mergetable = true;
    }
    // this is to display for example 261.2 MiB instead of 268k KiB
    $max_digits = 5;
    $decimals = 1;
    list($data_size, $data_unit)         = PMA_formatByteDown($showtable['Data_length'], $max_digits, $decimals);
    if ($mergetable == false) {
        list($index_size, $index_unit)   = PMA_formatByteDown($showtable['Index_length'], $max_digits, $decimals);
    }
    // InnoDB returns a huge value in Data_free, do not use it
    if (! $is_innodb && isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
        list($free_size, $free_unit)     = PMA_formatByteDown($showtable['Data_free'], $max_digits, $decimals);
        list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free'], $max_digits, $decimals);
    } else {
        list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length'], $max_digits, $decimals);
    }
    list($tot_size, $tot_unit)           = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length'], $max_digits, $decimals);
    if ($table_info_num_rows > 0) {
        list($avg_size, $avg_unit)       = PMA_formatByteDown(($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'], 6, 1);
    }

    // Displays them
    $odd_row = false;
    ?>

    <a name="showusage"></a>
    <?php if (! $tbl_is_view && ! $db_is_information_schema) { ?>
    <table id="tablespaceusage" class="data">
    <caption class="tblHeaders"><?php echo $strSpaceUsage; ?></caption>
    <thead>
    <tr>
        <th><?php echo $strType; ?></th>
        <th colspan="2"><?php echo $strUsage; ?></th>
    </tr>
    </thead>
    <tbody>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strData; ?></th>
        <td class="value"><?php echo $data_size; ?></td>
        <td class="unit"><?php echo $data_unit; ?></td>
    </tr>
        <?php
        if (isset($index_size)) {
            ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strIndex; ?></th>
        <td class="value"><?php echo $index_size; ?></td>
        <td class="unit"><?php echo $index_unit; ?></td>
    </tr>
            <?php
        }
        if (isset($free_size)) {
            ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?> warning">
        <th class="name"><?php echo $strOverhead; ?></th>
        <td class="value"><?php echo $free_size; ?></td>
        <td class="unit"><?php echo $free_unit; ?></td>
    </tr>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strEffective; ?></th>
        <td class="value"><?php echo $effect_size; ?></td>
        <td class="unit"><?php echo $effect_unit; ?></td>
    </tr>
            <?php
        }
        if (isset($tot_size) && $mergetable == false) {
            ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strTotalUC; ?></th>
        <td class="value"><?php echo $tot_size; ?></td>
        <td class="unit"><?php echo $tot_unit; ?></td>
    </tr>
            <?php
        }
        // Optimize link if overhead
        if (isset($free_size) && ($tbl_type == 'MYISAM' || $tbl_type == 'MARIA' || $tbl_type == 'BDB')) {
            ?>
    <tr class="tblFooters">
        <td colspan="3" align="center">
            <a href="sql.php?<?php echo $url_query; ?>&pos=0&amp;sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . PMA_backquote($table)); ?>"><?php
            if ($cfg['PropertiesIconic']) {
               echo '<img class="icon" src="' . $pmaThemeImage . 'b_tbloptimize.png" width="16" height="16" alt="' . $strOptimizeTable. '" />';
            }
            echo $strOptimizeTable;
            ?></a>
        </td>
    </tr>
            <?php
        }
        ?>
    </tbody>
    </table>
        <?php
    }
    $odd_row = false;
    ?>
    <table id="tablerowstats" class="data">
    <caption class="tblHeaders"><?php echo $strRowsStatistic; ?></caption>
    <thead>
    <tr>
        <th><?php echo $strStatement; ?></th>
        <th><?php echo $strValue; ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (isset($showtable['Row_format'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strFormat; ?></th>
        <td class="value"><?php
        if ($showtable['Row_format'] == 'Fixed') {
            echo $strStatic;
        } elseif ($showtable['Row_format'] == 'Dynamic') {
            echo $strDynamic;
        } else {
            echo $showtable['Row_format'];
        }
        ?></td>
    </tr>
        <?php
    }
    if (! empty($showtable['Create_options'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strOptions; ?></th>
        <td class="value"><?php
        if ($showtable['Create_options'] == 'partitioned') {
            echo $strPartitioned;
        } else {
            echo $showtable['Create_options'];
        }
        ?></td>
    </tr>
        <?php
    }
    if (!empty($tbl_collation)) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strCollation; ?></th>
        <td class="value"><?php
            echo '<dfn title="' . PMA_getCollationDescr($tbl_collation) . '">' . $tbl_collation . '</dfn>';
            ?></td>
    </tr>
        <?php
    }
    if (!$is_innodb && isset($showtable['Rows'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strRows; ?></th>
        <td class="value"><?php echo PMA_formatNumber($showtable['Rows'], 0); ?></td>
    </tr>
        <?php
    }
    if (!$is_innodb && isset($showtable['Avg_row_length']) && $showtable['Avg_row_length'] > 0) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strRowLength; ?> &oslash;</th>
        <td class="value"><?php echo PMA_formatNumber($showtable['Avg_row_length'], 0); ?></td>
    </tr>
        <?php
    }
    if (!$is_innodb && isset($showtable['Data_length']) && $showtable['Rows'] > 0 && $mergetable == false) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strRowSize; ?> &oslash;</th>
        <td class="value"><?php echo $avg_size . ' ' . $avg_unit; ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Auto_increment'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strNext; ?> Autoindex</th>
        <td class="value"><?php echo PMA_formatNumber($showtable['Auto_increment'], 0); ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Create_time'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strStatCreateTime; ?></th>
        <td class="value"><?php echo PMA_localisedDate(strtotime($showtable['Create_time'])); ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Update_time'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strStatUpdateTime; ?></th>
        <td class="value"><?php echo PMA_localisedDate(strtotime($showtable['Update_time'])); ?></td>
    </tr>
        <?php
    }
    if (isset($showtable['Check_time'])) {
        ?>
    <tr class="<?php echo ($odd_row = !$odd_row) ? 'odd' : 'even'; ?>">
        <th class="name"><?php echo $strStatCheckTime; ?></th>
        <td class="value"><?php echo PMA_localisedDate(strtotime($showtable['Check_time'])); ?></td>
    </tr>
        <?php
    }
    ?>
    </tbody>
    </table>
    <?php
}
// END - Calc Table Space

require './libraries/tbl_triggers.lib.php';

echo '<div class="clearfloat"></div>' . "\n";
echo '</div>' . "\n";
echo '</div>' . "\n";

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
