<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id: db_structure.php 12242 2009-02-20 09:22:20Z lem9 $
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';

$GLOBALS['js_include'][] = 'mootools.js';

/**
 * Prepares the tables list if the user where not redirected to this script
 * because there is no table in the database ($is_info is true)
 */
if (empty($is_info)) {
    // Drops/deletes/etc. multiple tables if required
    if ((!empty($submit_mult) && isset($selected_tbl))
      || isset($mult_btn)) {
        $action = 'db_structure.php';
        $err_url = 'db_structure.php?'. PMA_generate_common_url($db);
        require './libraries/mult_submits.inc.php';
        if (empty($message)) {
            $message = PMA_Message::success();
        }
    }
    require './libraries/db_common.inc.php';
    $url_query .= '&amp;goto=db_structure.php';

    // Gets the database structure
    $sub_part = '_structure';
    require './libraries/db_info.inc.php';
}

// 1. No tables
if ($num_tables == 0) {
    echo '<p>' . $strNoTablesFound . '</p>' . "\n";

    if (empty($db_is_information_schema)) {
        require './libraries/display_create_table.lib.php';
    } // end if (Create Table dialog)

    /**
     * Displays the footer
     */
    require_once './libraries/footer.inc.php';
    exit;
}

// else
// 2. Shows table informations - staybyte - 11 June 2001

require_once './libraries/bookmark.lib.php';

require_once './libraries/mysql_charsets.lib.php';
$db_collation = PMA_getDbCollation($db);

// Display function
/**
 * void PMA_TableHeader([bool $db_is_information_schema = false])
 * display table header (<table><thead>...</thead><tbody>)
 *
 * @uses    PMA_showHint()
 * @uses    $GLOBALS['cfg']['PropertiesNumColumns']
 * @uses    $GLOBALS['is_show_stats']
 * @uses    $GLOBALS['strTable']
 * @uses    $GLOBALS['strAction']
 * @uses    $GLOBALS['strRecords']
 * @uses    $GLOBALS['strApproximateCount']
 * @uses    $GLOBALS['strType']
 * @uses    $GLOBALS['strCollation']
 * @uses    $GLOBALS['strSize']
 * @uses    $GLOBALS['strOverhead']
 * @uses    $GLOBALS['structure_tbl_col_cnt']
 * @uses    PMA_SortableTableHeader()
 * @param   boolean $db_is_information_schema
 */
function PMA_TableHeader($db_is_information_schema = false)
{
    $cnt = 0; // Let's count the columns...

    if ($db_is_information_schema) {
        $action_colspan = 3;
    } else {
        $action_colspan = 6;
    }

    echo '<table class="data" style="float: left;">' . "\n"
        .'<thead>' . "\n"
        .'<tr><th></th>' . "\n"
        .'    <th>' . PMA_SortableTableHeader($GLOBALS['strTable'], 'table') . '</th>' . "\n"
        .'    <th colspan="' . $action_colspan . '">' . "\n"
        .'        ' . $GLOBALS['strAction'] . "\n"
        .'    </th>'
        .'    <th>' . PMA_SortableTableHeader($GLOBALS['strRecords'], 'records')
        .PMA_showHint(PMA_sanitize($GLOBALS['strApproximateCount'])) . "\n"
        .'    </th>' . "\n";
    if (!($GLOBALS['cfg']['PropertiesNumColumns'] > 1)) {
        echo '    <th>' . PMA_SortableTableHeader($GLOBALS['strType'], 'type') . '</th>' . "\n";
        $cnt++;
        echo '    <th>' . PMA_SortableTableHeader($GLOBALS['strCollation'], 'collation') . '</th>' . "\n";
        $cnt++;
    }
    if ($GLOBALS['is_show_stats']) {
        echo '    <th>' . PMA_SortableTableHeader($GLOBALS['strSize'], 'size') . '</th>' . "\n"
           . '    <th>' . PMA_SortableTableHeader($GLOBALS['strOverhead'], 'overhead') . '</th>' . "\n";
        $cnt += 2;
    }
    echo '</tr>' . "\n";
    echo '</thead>' . "\n";
    echo '<tbody>' . "\n";
    $GLOBALS['structure_tbl_col_cnt'] = $cnt + $action_colspan + 3;
} // end function PMA_TableHeader()


/**
 * Creates a clickable column header for table information
 *
 * @param   string  title to use for the link
 * @param   string  corresponds to sortable data name mapped in libraries/db_info.inc.php  
 * @returns string  link to be displayed in the table header
 */
function PMA_SortableTableHeader($title, $sort)
{
    // Set some defaults
    $requested_sort = 'table';
    $requested_sort_order = 'ASC';
    $sort_order = 'ASC';
    
    // If the user requested a sort
    if (isset($_REQUEST['sort'])) {
        $requested_sort = $_REQUEST['sort'];

        if (isset($_REQUEST['sort_order'])) {
            $requested_sort_order = $_REQUEST['sort_order'];
        }
    }

    $order_img = '';
    $order_link_params = array();
    $order_link_params['title'] = $GLOBALS['strSort'];

    // If this column was requested to be sorted.
    if ($requested_sort == $sort) {
        if ($requested_sort_order == 'ASC') {
            $sort_order = 'DESC';
            $order_img  = ' <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 's_desc.png" width="11" height="9" alt="'. $GLOBALS['strDescending'] . '" title="'. $GLOBALS['strDescending'] . '" id="sort_arrow" />';
            $order_link_params['onmouseover'] = 'if(document.getElementById(\'sort_arrow\')){ document.getElementById(\'sort_arrow\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_asc.png\'; }';
            $order_link_params['onmouseout']  = 'if(document.getElementById(\'sort_arrow\')){ document.getElementById(\'sort_arrow\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_desc.png\'; }';
        } else {
            $order_img  = ' <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 's_asc.png" width="11" height="9" alt="'. $GLOBALS['strAscending'] . '" title="'. $GLOBALS['strAscending'] . '" id="sort_arrow" />';
            $order_link_params['onmouseover'] = 'if(document.getElementById(\'sort_arrow\')){ document.getElementById(\'sort_arrow\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_desc.png\'; }';
            $order_link_params['onmouseout']  = 'if(document.getElementById(\'sort_arrow\')){ document.getElementById(\'sort_arrow\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_asc.png\'; }';
        }
    }

    $_url_params = array(
        'db' => $_REQUEST['db'],
    );

    $url = 'db_structure.php'.PMA_generate_common_url($_url_params);
    // We set the position back to 0 every time they sort.
    $url .= "&amp;pos=0&amp;sort=$sort&amp;sort_order=$sort_order";

    return PMA_linkOrButton($url, $title . $order_img, $order_link_params);
}

$titles = array();
if (true == $cfg['PropertiesIconic']) {
    $titles['Browse']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_browse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" />';
    $titles['NoBrowse']   = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_browse.png" alt="' . $strBrowse . '" title="' . $strBrowse . '" />';
    $titles['Search']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_select.png" alt="' . $strSearch . '" title="' . $strSearch . '" />';
    $titles['NoSearch']   = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_select.png" alt="' . $strSearch . '" title="' . $strSearch . '" />';
    $titles['Insert']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_insrow.png" alt="' . $strInsert . '" title="' . $strInsert . '" />';
    $titles['NoInsert']   = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_insrow.png" alt="' . $strInsert . '" title="' . $strInsert . '" />';
    $titles['Structure']  = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_props.png" alt="' . $strStructure . '" title="' . $strStructure . '" />';
    $titles['Drop']       = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" />';
    $titles['NoDrop']     = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" />';
    $titles['Empty']      = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'b_empty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" />';
    $titles['NoEmpty']    = '<img class="icon" width="16" height="16" src="' .$pmaThemeImage . 'bd_empty.png" alt="' . $strEmpty . '" title="' . $strEmpty . '" />';

    if ('both' === $cfg['PropertiesIconic']) {
        $titles['Browse']     .= $strBrowse;
        $titles['Search']     .= $strSearch;
        $titles['NoBrowse']   .= $strBrowse;
        $titles['NoSearch']   .= $strSearch;
        $titles['Insert']     .= $strInsert;
        $titles['NoInsert']   .= $strInsert;
        $titles['Structure']  .= $strStructure;
        $titles['Drop']       .= $strDrop;
        $titles['NoDrop']     .= $strDrop;
        $titles['Empty']      .= $strEmpty;
        $titles['NoEmpty']    .= $strEmpty;
    }
} else {
    $titles['Browse']     = $strBrowse;
    $titles['Search']     = $strSearch;
    $titles['NoBrowse']   = $strBrowse;
    $titles['NoSearch']   = $strSearch;
    $titles['Insert']     = $strInsert;
    $titles['NoInsert']   = $strInsert;
    $titles['Structure']  = $strStructure;
    $titles['Drop']       = $strDrop;
    $titles['NoDrop']     = $strDrop;
    $titles['Empty']      = $strEmpty;
    $titles['NoEmpty']    = $strEmpty;
}

/**
 * Displays the tables list
 */
$_url_params = array(
    'pos' => $pos,
    'db'  => $db);

// Add the sort options if they exists
if (isset($_REQUEST['sort'])) {
    $_url_params['sort'] = $_REQUEST['sort'];
}

if (isset($_REQUEST['sort_order'])) {
    $_url_params['sort_order'] = $_REQUEST['sort_order'];
}

PMA_listNavigator($total_num_tables, $pos, $_url_params, 'db_structure.php', 'frame_content', $GLOBALS['cfg']['MaxTableList']);

?>
<form method="post" action="db_structure.php" name="tablesForm" id="tablesForm">
<?php
echo PMA_generate_common_hidden_inputs($db);

PMA_TableHeader($db_is_information_schema);

$i = $sum_entries = 0;
$sum_size       = (double) 0;
$overhead_size  = (double) 0;
$overhead_check = '';
$checked        = !empty($checkall) ? ' checked="checked"' : '';
$num_columns    = $cfg['PropertiesNumColumns'] > 1 ? ceil($num_tables / $cfg['PropertiesNumColumns']) + 1 : 0;
$row_count      = 0;


$hidden_fields = array();
$odd_row       = true;
$sum_row_count_pre = '';

// added by rajk - for blobstreaming
$PMA_Config = $_SESSION['PMA_Config'];

if (!empty($PMA_Config))
    $session_bs_tables = $PMA_Config->get('BLOBSTREAMING_TABLES'); // list of blobstreaming tables

$tableReductionCount = 0;   // the amount to reduce the table count by

foreach ($tables as $keyname => $each_table) {
    if (isset($session_bs_tables))
    {
        // compare table name against blobstreaming tables
        foreach ($session_bs_tables as $table_key=>$table_val)
            // if the table is a blobstreaming table, reduce table count and skip outer foreach loop
            if ($table_key == $keyname)
            {
                $tableReductionCount++;
                continue 2;
            }
    }

    // loic1: Patch from Joshua Nye <josh at boxcarmedia.com> to get valid
    //        statistics whatever is the table type

    $table_is_view = false;
    $table_encoded = urlencode($each_table['TABLE_NAME']);
    // Sets parameters for links
    $tbl_url_query = $url_query . '&amp;table=' . $table_encoded;
    // do not list the previous table's size info for a view
    $formatted_size = '-';
    $unit = '';

    switch ( $each_table['ENGINE']) {
        // MyISAM, ISAM or Heap table: Row count, data size and index size
        // are accurate.
        case 'MyISAM' :
        case 'ISAM' :
        case 'HEAP' :
        case 'MEMORY' :
            if ($db_is_information_schema) {
                $each_table['Rows'] = PMA_Table::countRecords($db,
                    $each_table['Name'], $return = true);
            }
                
            if ($is_show_stats) {
                $tblsize                    =  doubleval($each_table['Data_length']) + doubleval($each_table['Index_length']);
                $sum_size                   += $tblsize;
                list($formatted_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
                if (isset($each_table['Data_free']) && $each_table['Data_free'] > 0) {
                    list($formatted_overhead, $overhead_unit)     = PMA_formatByteDown($each_table['Data_free'], 3, ($each_table['Data_free'] > 0) ? 1 : 0);
                    $overhead_size           += $each_table['Data_free'];
                }
            }
            break;
        case 'InnoDB' :
            // InnoDB table: Row count is not accurate but data and index
            // sizes are.

            if ($each_table['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount']) {
                $each_table['COUNTED'] = true;
                $each_table['TABLE_ROWS'] = PMA_Table::countRecords($db,
                    $each_table['TABLE_NAME'], $return = true, $force_exact = true,
                    $is_view = false);
            } else {
                $each_table['COUNTED'] = false;
            }

            if ($is_show_stats) {
                $tblsize                    =  $each_table['Data_length'] + $each_table['Index_length'];
                $sum_size                   += $tblsize;
                list($formatted_size, $unit) =  PMA_formatByteDown($tblsize, 3, ($tblsize > 0) ? 1 : 0);
            }
            //$display_rows                   =  ' - ';
            break;
        case 'MRG_MyISAM' :
        case 'BerkeleyDB' :
            // Merge or BerkleyDB table: Only row count is accurate.
            if ($is_show_stats) {
                $formatted_size =  ' - ';
                $unit          =  '';
            }
            break;
        // for a view, the ENGINE is null
        case null :
        case 'SYSTEM VIEW' :
            // countRecords() takes care of $cfg['MaxExactCountViews']
            $each_table['TABLE_ROWS'] = PMA_Table::countRecords($db,
                    $each_table['TABLE_NAME'], $return = true, $force_exact = true,
                    $is_view = true);
            $table_is_view = true;
            break;
        default :
            // Unknown table type.
            if ($is_show_stats) {
                $formatted_size =  'unknown';
                $unit          =  '';
            }
    } // end switch
    $sum_entries += $each_table['TABLE_ROWS'];

    if (isset($each_table['Collation'])) {
        $collation = '<dfn title="'
            . PMA_getCollationDescr($each_table['Collation']) . '">'
            . $each_table['Collation'] . '</dfn>';
    } else {
        $collation = '---';
    }

    if ($is_show_stats) {
        if (isset($formatted_overhead)) {
            $overhead = '<a href="tbl_structure.php?'
                . $tbl_url_query . '#showusage">' . $formatted_overhead
                . ' ' . $overhead_unit . '</a>' . "\n";
            unset($formatted_overhead);
            $overhead_check .=
                "document.getElementById('checkbox_tbl_" . ($i + 1) . "').checked = true;";
        } else {
            $overhead = '-';
        }
    } // end if

    $alias = (!empty($tooltip_aliasname) && isset($tooltip_aliasname[$each_table['TABLE_NAME']]))
               ? str_replace(' ', '&nbsp;', htmlspecialchars($tooltip_truename[$each_table['TABLE_NAME']]))
               : str_replace(' ', '&nbsp;', htmlspecialchars($each_table['TABLE_NAME']));
    $truename = (!empty($tooltip_truename) && isset($tooltip_truename[$each_table['TABLE_NAME']]))
               ? str_replace(' ', '&nbsp;', htmlspecialchars($tooltip_truename[$each_table['TABLE_NAME']]))
               : str_replace(' ', '&nbsp;', htmlspecialchars($each_table['TABLE_NAME']));

    $i++;

    $row_count++;
    if ($table_is_view) {
        $hidden_fields[] = '<input type="hidden" name="views[]" value="' .  $each_table['TABLE_NAME'] . '" />';
    }

    if ($each_table['TABLE_ROWS'] > 0) {
        $browse_table = '<a href="sql.php?' . $tbl_url_query . '&amp;pos=0">' . $titles['Browse'] . '</a>';
        $search_table = '<a href="tbl_select.php?' . $tbl_url_query . '">' . $titles['Search'] . '</a>';
    } else {
        $browse_table = $titles['NoBrowse'];
        $search_table = $titles['NoSearch'];
    }

    if (! $db_is_information_schema) {
        if (! empty($each_table['TABLE_ROWS'])) {
            $empty_table = '<a href="sql.php?' . $tbl_url_query
                 . '&amp;sql_query=';
            $empty_table .= urlencode('TRUNCATE ' . PMA_backquote($each_table['TABLE_NAME']))
                 . '&amp;zero_rows='
                 . urlencode(sprintf($strTableHasBeenEmptied, htmlspecialchars($each_table['TABLE_NAME'])))
                 . '" onclick="return confirmLink(this, \'TRUNCATE ';
            $empty_table .= PMA_jsFormat($each_table['TABLE_NAME']) . '\')">' . $titles['Empty'] . '</a>';
        } else {
            $empty_table = $titles['NoEmpty'];
        }
        $drop_query = 'DROP '
            . ($table_is_view ? 'VIEW' : 'TABLE')
            . ' ' . PMA_backquote($each_table['TABLE_NAME']);
        $drop_message = sprintf(
            $table_is_view ? $strViewHasBeenDropped : $strTableHasBeenDropped,
            str_replace(' ', '&nbsp;', htmlspecialchars($each_table['TABLE_NAME'])));
    }

    if ($num_columns > 0 && $num_tables > $num_columns
      && (($row_count % $num_columns) == 0)) {
        $row_count = 1;
        $odd_row = true;
        ?>
    </tr>
</tbody>
</table>
        <?php
        PMA_TableHeader();
    }
    ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
    <td align="center">
        <input type="checkbox" name="selected_tbl[]"
            value="<?php echo $each_table['TABLE_NAME']; ?>"
            id="checkbox_tbl_<?php echo $i; ?>"<?php echo $checked; ?> /></td>
    <th><label for="checkbox_tbl_<?php echo $i; ?>"
            title="<?php echo $alias; ?>"><?php echo $truename; ?></label>
    </th>
    <td align="center"><?php echo $browse_table; ?></td>
    <td align="center">
        <a href="tbl_structure.php?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Structure']; ?></a></td>
    <td align="center"><?php echo $search_table; ?></td>
    <?php if (! $db_is_information_schema) { ?>
    <td align="center">
        <a href="tbl_change.php?<?php echo $tbl_url_query; ?>">
            <?php echo $titles['Insert']; ?></a></td>
    <td align="center"><?php echo $empty_table; ?></td>
    <td align="center">
        <a href="sql.php?<?php echo $tbl_url_query;
            ?>&amp;reload=1&amp;purge=1&amp;sql_query=<?php
            echo urlencode($drop_query); ?>&amp;zero_rows=<?php
            echo urlencode($drop_message); ?>"
            onclick="return confirmLink(this, '<?php echo PMA_jsFormat($drop_query, false); ?>')">
            <?php echo $titles['Drop']; ?></a></td>
    <?php } // end if (! $db_is_information_schema)

    // there is a null value in the ENGINE
    // - when the table needs to be repaired, or
    // - when it's a view
    //  so ensure that we'll display "in use" below for a table
    //  that needs to be repaired
    if (isset($each_table['TABLE_ROWS']) && ($each_table['ENGINE'] != null || $table_is_view)) {
        if ($table_is_view) {
            if ($each_table['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews']){
                $row_count_pre = '~';
                $sum_row_count_pre = '~';
                $show_superscript = PMA_showHint(PMA_sanitize(sprintf($strViewHasAtLeast, '[a@./Documentation.html#cfg_MaxExactCountViews@_blank]', '[/a]')));
            }
        } elseif($each_table['ENGINE'] == 'InnoDB' && (! $each_table['COUNTED'])) {
            // InnoDB table: we did not get an accurate row count
            $row_count_pre = '~';
            $sum_row_count_pre = '~';
            $show_superscript = '';
        } else {
            $row_count_pre = '';
            $show_superscript = '';
        }
    ?>
    <td class="value"><?php echo $row_count_pre . PMA_formatNumber($each_table['TABLE_ROWS'], 0) . $show_superscript; ?></td>
        <?php if (!($cfg['PropertiesNumColumns'] > 1)) { ?>
    <td nowrap="nowrap"><?php echo ($table_is_view ? $strView : $each_table['ENGINE']); ?></td>
            <?php if (isset($collation)) { ?>
    <td nowrap="nowrap"><?php echo $collation ?></td>
            <?php } ?>
        <?php } ?>

        <?php if ($is_show_stats) { ?>
    <td class="value"><a
        href="tbl_structure.php?<?php echo $tbl_url_query; ?>#showusage"
        ><?php echo $formatted_size . ' ' . $unit; ?></a></td>
    <td class="value"><?php echo $overhead; ?></td>
        <?php } // end if ?>
    <?php } elseif ($table_is_view) { ?>
    <td class="value">-</td>
    <td><?php echo $strView; ?></td>
    <td>---</td>
        <?php if ($is_show_stats) { ?>
    <td class="value">-</td>
    <td class="value">-</td>
        <?php } ?>
    <?php } else { ?>
    <td colspan="<?php echo ($structure_tbl_col_cnt - ($db_is_information_schema ? 5 : 8)) ?>"
        align="center">
        <?php echo $strInUse; ?></td>
    <?php } // end if (isset($each_table['TABLE_ROWS'])) else ?>
</tr>
    <?php
} // end foreach

// Show Summary
if ($is_show_stats) {
    list($sum_formatted, $unit) = PMA_formatByteDown($sum_size, 3, 1);
    list($overhead_formatted, $overhead_unit) =
        PMA_formatByteDown($overhead_size, 3, 1);
}
?>
</tbody>
<tbody>
<tr><th></th>
    <th align="center" nowrap="nowrap">
        <?php
            // for blobstreaming - if the number of tables is 0, set tableReductionCount to 0
            // (we don't want negative numbers here) - rajk
            if ($num_tables == 0)
                $tableReductionCount = 0;

            echo sprintf($strTables, PMA_formatNumber($num_tables - $tableReductionCount, 0));
        ?>
    </th>
    <th colspan="<?php echo ($db_is_information_schema ? 3 : 6) ?>" align="center">
        <?php echo $strSum; ?></th>
    <th class="value"><?php echo $sum_row_count_pre . PMA_formatNumber($sum_entries, 0); ?></th>
<?php
if (!($cfg['PropertiesNumColumns'] > 1)) {
    $default_engine = PMA_DBI_get_default_engine();
    echo '    <th align="center">' . "\n"
       . '        <dfn title="'
       . sprintf($strDefaultEngine, $default_engine) . '">' .$default_engine . '</dfn></th>' . "\n";
    // we got a case where $db_collation was empty
    echo '    <th align="center">' . "\n";
    if (! empty($db_collation)) {
        echo '        <dfn title="'
            . PMA_getCollationDescr($db_collation) . ' (' . $strDefault . ')">' . $db_collation
            . '</dfn>';
    }
    echo '</th>';
}

if ($is_show_stats) {
    ?>
    <th class="value"><?php echo $sum_formatted . ' ' . $unit; ?></th>
    <th class="value"><?php echo $overhead_formatted . ' ' . $overhead_unit; ?></th>
    <?php
}
?>
</tr>
</tbody>
</table>

<div class="clearfloat">
<?php
// Check all tables url
$checkall_url = 'db_structure.php?' . PMA_generate_common_url($db);
?>
<img class="selectallarrow" src="<?php echo $pmaThemeImage .'arrow_'.$text_dir.'.png'; ?>"
    width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
<a href="<?php echo $checkall_url; ?>&amp;checkall=1"
    onclick="if (markAllRows('tablesForm')) return false;">
    <?php echo $strCheckAll; ?></a>
/
<a href="<?php echo $checkall_url; ?>"
    onclick="if (unMarkAllRows('tablesForm')) return false;">
    <?php echo $strUncheckAll; ?></a>
<?php if ($overhead_check != '') { ?>
/
<a href="#" onclick="unMarkAllRows('tablesForm');
    <?php echo $overhead_check; ?> return false;">
    <?php echo $strCheckOverhead; ?></a>
<?php } ?>

<select name="submit_mult" onchange="this.form.submit();" style="margin: 0 3em 0 3em;">
<?php
echo '    <option value="' . $strWithChecked . '" selected="selected">'
     . $strWithChecked . '</option>' . "\n";
echo '    <option value="' . $strEmpty . '" >'
     . $strEmpty . '</option>' . "\n";
echo '    <option value="' . $strDrop . '" >'
     . $strDrop . '</option>' . "\n";
echo '    <option value="' . $strPrintView . '" >'
     . $strPrintView . '</option>' . "\n";
echo '    <option value="' . $strCheckTable . '" >'
     . $strCheckTable . '</option>' . "\n";
echo '    <option value="' . $strOptimizeTable . '" >'
     . $strOptimizeTable . '</option>' . "\n";
echo '    <option value="' . $strRepairTable . '" >'
     . $strRepairTable . '</option>' . "\n";
echo '    <option value="' . $strAnalyzeTable . '" >'
     . $strAnalyzeTable . '</option>' . "\n";
?>
</select>
<script type="text/javascript">
<!--
// Fake js to allow the use of the <noscript> tag
//-->
</script>
<noscript>
    <input type="submit" value="<?php echo $strGo; ?>" />
</noscript>
<?php echo implode("\n", $hidden_fields) . "\n"; ?>
</div>
</form>
<?php
// display again the table list navigator
PMA_listNavigator($total_num_tables, $pos, $_url_params, 'db_structure.php', 'frame_content', $GLOBALS['cfg']['MaxTableList']);
?>
<hr />

<?php
// Routines
require './libraries/db_routines.inc.php';

// Events
if (PMA_MYSQL_INT_VERSION > 50100) {
    require './libraries/db_events.inc.php';
}

/**
 * Work on the database
 * redesigned 2004-05-08 by mkkeck
 */
/* DATABASE WORK */
/* Printable view of a table */
echo '<p>';
echo '<a href="db_printview.php?' . $url_query . '">';
if ($cfg['PropertiesIconic']) {
     echo '<img class="icon" src="' . $pmaThemeImage
        .'b_print.png" width="16" height="16" alt="" />';
}
echo $strPrintView . '</a> ';

echo '<a href="./db_datadict.php?' . $url_query . '">';
if ($cfg['PropertiesIconic']) {
    echo '<img class="icon" src="' . $pmaThemeImage
        .'b_tblanalyse.png" width="16" height="16" alt="" />';
}
echo $strDataDict . '</a>';
echo '</p>';

if (empty($db_is_information_schema)) {
    require './libraries/display_create_table.lib.php';
} // end if (Create Table dialog)

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
