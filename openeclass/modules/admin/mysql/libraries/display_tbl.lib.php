<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * library for displaying table with results from all sort of select queries
 *
 * @version $Id: display_tbl.lib.php 12390 2009-05-04 16:05:24Z lem9 $
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/Table.class.php';
require_once './libraries/Index.class.php';

/**
 * Defines the display mode to use for the results of a SQL query
 *
 * It uses a synthetic string that contains all the required informations.
 * In this string:
 *   - the first two characters stand for the action to do while
 *     clicking on the "edit" link (e.g. 'ur' for update a row, 'nn' for no
 *     edit link...);
 *   - the next two characters stand for the action to do while
 *     clicking on the "delete" link (e.g. 'kp' for kill a process, 'nn' for
 *     no delete link...);
 *   - the next characters are boolean values (1/0) and respectively stand
 *     for sorting links, navigation bar, "insert a new row" link, the
 *     bookmark feature, the expand/collapse text/blob fields button and
 *     the "display printable view" option.
 *     Of course '0'/'1' means the feature won't/will be enabled.
 *
 * @param   string   the synthetic value for display_mode (see a few
 *                   lines above for explanations)
 * @param   integer  the total number of rows returned by the SQL query
 *                   without any programmatically appended "LIMIT" clause
 *                   (just a copy of $unlim_num_rows if it exists, else
 *                   computed inside this function)
 *
 * @return  array    an array with explicit indexes for all the display
 *                   elements
 *
 * @global  string   the database name
 * @global  string   the table name
 * @global  integer  the total number of rows returned by the SQL query
 *                   without any programmatically appended "LIMIT" clause
 * @global  array    the properties of the fields returned by the query
 * @global  string   the URL to return to in case of error in a SQL
 *                   statement
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_setDisplayMode(&$the_disp_mode, &$the_total)
{
    global $db, $table;
    global $unlim_num_rows, $fields_meta;
    global $err_url;

    // 1. Initializes the $do_display array
    $do_display              = array();
    $do_display['edit_lnk']  = $the_disp_mode[0] . $the_disp_mode[1];
    $do_display['del_lnk']   = $the_disp_mode[2] . $the_disp_mode[3];
    $do_display['sort_lnk']  = (string) $the_disp_mode[4];
    $do_display['nav_bar']   = (string) $the_disp_mode[5];
    $do_display['ins_row']   = (string) $the_disp_mode[6];
    $do_display['bkm_form']  = (string) $the_disp_mode[7];
    $do_display['text_btn']  = (string) $the_disp_mode[8];
    $do_display['pview_lnk'] = (string) $the_disp_mode[9];

    // 2. Display mode is not "false for all elements" -> updates the
    // display mode
    if ($the_disp_mode != 'nnnn000000') {
        // 2.0 Print view -> set all elements to false!
        if (isset($GLOBALS['printview']) && $GLOBALS['printview'] == '1') {
            $do_display['edit_lnk']  = 'nn'; // no edit link
            $do_display['del_lnk']   = 'nn'; // no delete link
            $do_display['sort_lnk']  = (string) '0';
            $do_display['nav_bar']   = (string) '0';
            $do_display['ins_row']   = (string) '0';
            $do_display['bkm_form']  = (string) '0';
            $do_display['text_btn']  = (string) '0';
            $do_display['pview_lnk'] = (string) '0';
        }
        // 2.1 Statement is a "SELECT COUNT", a
        //     "CHECK/ANALYZE/REPAIR/OPTIMIZE", an "EXPLAIN" one or
        //     contains a "PROC ANALYSE" part
        elseif ($GLOBALS['is_count'] || $GLOBALS['is_analyse'] || $GLOBALS['is_maint'] || $GLOBALS['is_explain']) {
            $do_display['edit_lnk']  = 'nn'; // no edit link
            $do_display['del_lnk']   = 'nn'; // no delete link
            $do_display['sort_lnk']  = (string) '0';
            $do_display['nav_bar']   = (string) '0';
            $do_display['ins_row']   = (string) '0';
            $do_display['bkm_form']  = (string) '1';
            if ($GLOBALS['is_maint']) {
                $do_display['text_btn']  = (string) '1';
            } else {
                $do_display['text_btn']  = (string) '0';
            }
            $do_display['pview_lnk'] = (string) '1';
        }
        // 2.2 Statement is a "SHOW..."
        elseif ($GLOBALS['is_show']) {
            /**
             * 2.2.1
             * @todo defines edit/delete links depending on show statement
             */
            $tmp = preg_match('@^SHOW[[:space:]]+(VARIABLES|(FULL[[:space:]]+)?PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS|DATABASES|FIELDS)@i', $GLOBALS['sql_query'], $which);
            if (isset($which[1]) && strpos(' ' . strtoupper($which[1]), 'PROCESSLIST') > 0) {
                $do_display['edit_lnk'] = 'nn'; // no edit link
                $do_display['del_lnk']  = 'kp'; // "kill process" type edit link
            } else {
                // Default case -> no links
                $do_display['edit_lnk'] = 'nn'; // no edit link
                $do_display['del_lnk']  = 'nn'; // no delete link
            }
            // 2.2.2 Other settings
            $do_display['sort_lnk']  = (string) '0';
            $do_display['nav_bar']   = (string) '0';
            $do_display['ins_row']   = (string) '0';
            $do_display['bkm_form']  = (string) '1';
            $do_display['text_btn']  = (string) '1';
            $do_display['pview_lnk'] = (string) '1';
        }
        // 2.3 Other statements (ie "SELECT" ones) -> updates
        //     $do_display['edit_lnk'], $do_display['del_lnk'] and
        //     $do_display['text_btn'] (keeps other default values)
        else {
            $prev_table = $fields_meta[0]->table;
            $do_display['text_btn']  = (string) '1';
            for ($i = 0; $i < $GLOBALS['fields_cnt']; $i++) {
                $is_link = ($do_display['edit_lnk'] != 'nn'
                            || $do_display['del_lnk'] != 'nn'
                            || $do_display['sort_lnk'] != '0'
                            || $do_display['ins_row'] != '0');
                // 2.3.2 Displays edit/delete/sort/insert links?
                if ($is_link
                    && ($fields_meta[$i]->table == '' || $fields_meta[$i]->table != $prev_table)) {
                    $do_display['edit_lnk'] = 'nn'; // don't display links
                    $do_display['del_lnk']  = 'nn';
                    /**
                     * @todo May be problematic with same fields names in two joined table.
                     */
                    // $do_display['sort_lnk'] = (string) '0';
                    $do_display['ins_row']  = (string) '0';
                    if ($do_display['text_btn'] == '1') {
                        break;
                    }
                } // end if (2.3.2)
                // 2.3.3 Always display print view link
                $do_display['pview_lnk']    = (string) '1';
                $prev_table = $fields_meta[$i]->table;
            } // end for
        } // end if..elseif...else (2.1 -> 2.3)
    } // end if (2)

    // 3. Gets the total number of rows if it is unknown
    if (isset($unlim_num_rows) && $unlim_num_rows != '') {
        $the_total = $unlim_num_rows;
    } elseif (($do_display['nav_bar'] == '1' || $do_display['sort_lnk'] == '1')
             && (strlen($db) && !empty($table))) {
        $the_total   = PMA_Table::countRecords($db, $table, true);
    }

    // 4. If navigation bar or sorting fields names URLs should be
    //    displayed but there is only one row, change these settings to
    //    false
    if ($do_display['nav_bar'] == '1' || $do_display['sort_lnk'] == '1') {

        // - Do not display sort links if less than 2 rows.
        // - For a VIEW we (probably) did not count the number of rows
        //   so don't test this number here, it would remove the possibility
        //   of sorting VIEW results.
        if (isset($unlim_num_rows) && $unlim_num_rows < 2 && ! PMA_Table::isView($db, $table)) {
            // garvin: force display of navbar for vertical/horizontal display-choice.
            // $do_display['nav_bar']  = (string) '0';
            $do_display['sort_lnk'] = (string) '0';
        }
    } // end if (3)

    // 5. Updates the synthetic var
    $the_disp_mode = join('', $do_display);

    return $do_display;
} // end of the 'PMA_setDisplayMode()' function


/**
 * Displays a navigation bar to browse among the results of a SQL query
 *
 * @uses    $_SESSION['userconf']['disp_direction']
 * @uses    $_SESSION['userconf']['repeat_cells']
 * @uses    $_SESSION['userconf']['max_rows']
 * @uses    $_SESSION['userconf']['pos']
 * @param   integer  the offset for the "next" page
 * @param   integer  the offset for the "previous" page
 * @param   string   the URL-encoded query
 *
 * @global  string   $db             the database name
 * @global  string   $table          the table name
 * @global  string   $goto           the URL to go back in case of errors
 * @global  integer  $num_rows       the total number of rows returned by the
 *                                   SQL query
 * @global  integer  $unlim_num_rows the total number of rows returned by the
 *                                   SQL any programmatically appended "LIMIT" clause
 * @global  boolean  $is_innodb      whether its InnoDB or not
 * @global  array    $showtable      table definitions
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_displayTableNavigation($pos_next, $pos_prev, $sql_query)
{
    global $db, $table, $goto;
    global $num_rows, $unlim_num_rows;
    global $is_innodb;
    global $showtable;

    // here, using htmlentities() would cause problems if the query
    // contains accented characters
    $html_sql_query = htmlspecialchars($sql_query);

    /**
     * @todo move this to a central place
     * @todo for other future table types
     */
    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');

    ?>

<!-- Navigation bar -->
<table border="0" cellpadding="2" cellspacing="0">
<tr>
    <?php
    // Move to the beginning or to the previous page
    if ($_SESSION['userconf']['pos'] && $_SESSION['userconf']['max_rows'] != 'all') {
        // loic1: patch #474210 from Gosha Sakovich - part 1
        if ($GLOBALS['cfg']['NavigationBarIconic']) {
            $caption1 = '&lt;&lt;';
            $caption2 = ' &lt; ';
            $title1   = ' title="' . $GLOBALS['strPos1'] . '"';
            $title2   = ' title="' . $GLOBALS['strPrevious'] . '"';
        } else {
            $caption1 = $GLOBALS['strPos1'] . ' &lt;&lt;';
            $caption2 = $GLOBALS['strPrevious'] . ' &lt;';
            $title1   = '';
            $title2   = '';
        } // end if... else...
        ?>
<td>
    <form action="sql.php" method="post">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $html_sql_query; ?>" />
        <input type="hidden" name="pos" value="0" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="submit" name="navig" value="<?php echo $caption1; ?>"<?php echo $title1; ?> />
    </form>
</td>
<td>
    <form action="sql.php" method="post">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $html_sql_query; ?>" />
        <input type="hidden" name="pos" value="<?php echo $pos_prev; ?>" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="submit" name="navig" value="<?php echo $caption2; ?>"<?php echo $title2; ?> />
    </form>
</td>
        <?php
    } // end move back
    ?>
<td>
    &nbsp;&nbsp;&nbsp;
</td>
<td align="center">
<?php // if displaying a VIEW, $unlim_num_rows could be zero because
      // of $cfg['MaxExactCountViews']; in this case, avoid passing
      // the 5th parameter to checkFormElementInRange()
      // (this means we can't validate the upper limit ?>
    <form action="sql.php" method="post"
onsubmit="return (checkFormElementInRange(this, 'session_max_rows', '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidRowNumber']); ?>', 1) &amp;&amp; checkFormElementInRange(this, 'pos', '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidRowNumber']); ?>', 0<?php echo $unlim_num_rows > 0 ? ',' . $unlim_num_rows - 1 : ''; ?>))">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $html_sql_query; ?>" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="submit" name="navig" value="<?php echo $GLOBALS['strShow']; ?> :" />
        <input type="text" name="session_max_rows" size="3" value="<?php echo (($_SESSION['userconf']['max_rows'] != 'all') ? $_SESSION['userconf']['max_rows'] : $GLOBALS['cfg']['MaxRows']); ?>" class="textfield" onfocus="this.select()" />
        <?php echo $GLOBALS['strRowsFrom'] . "\n"; ?>
        <input type="text" name="pos" size="6" value="<?php echo (($pos_next >= $unlim_num_rows) ? 0 : $pos_next); ?>" class="textfield" onfocus="this.select()" />
        <br />
    <?php
    // Display mode (horizontal/vertical and repeat headers)
    $param1 = '            <select name="disp_direction">' . "\n"
            . '                <option value="horizontal"' . (($_SESSION['userconf']['disp_direction'] == 'horizontal') ? ' selected="selected"': '') . '>' . $GLOBALS['strRowsModeHorizontal'] . '</option>' . "\n"
            . '                <option value="horizontalflipped"' . (($_SESSION['userconf']['disp_direction'] == 'horizontalflipped') ? ' selected="selected"': '') . '>' . $GLOBALS['strRowsModeFlippedHorizontal'] . '</option>' . "\n"
            . '                <option value="vertical"' . (($_SESSION['userconf']['disp_direction'] == 'vertical') ? ' selected="selected"': '') . '>' . $GLOBALS['strRowsModeVertical'] . '</option>' . "\n"
            . '            </select>' . "\n"
            . '           ';
    $param2 = '            <input type="text" size="3" name="repeat_cells" value="' . $_SESSION['userconf']['repeat_cells'] . '" class="textfield" />' . "\n"
            . '           ';
    echo '    ' . sprintf($GLOBALS['strRowsModeOptions'], "\n" . $param1, "\n" . $param2) . "\n";
    ?>
    </form>
</td>
<td>
    &nbsp;&nbsp;&nbsp;
</td>
    <?php
    // Move to the next page or to the last one
    if (($_SESSION['userconf']['pos'] + $_SESSION['userconf']['max_rows'] < $unlim_num_rows) && $num_rows >= $_SESSION['userconf']['max_rows']
        && $_SESSION['userconf']['max_rows'] != 'all') {
        // loic1: patch #474210 from Gosha Sakovich - part 2
        if ($GLOBALS['cfg']['NavigationBarIconic']) {
            $caption3 = ' &gt; ';
            $caption4 = '&gt;&gt;';
            $title3   = ' title="' . $GLOBALS['strNext'] . '"';
            $title4   = ' title="' . $GLOBALS['strEnd'] . '"';
        } else {
            $caption3 = '&gt; ' . $GLOBALS['strNext'];
            $caption4 = '&gt;&gt; ' . $GLOBALS['strEnd'];
            $title3   = '';
            $title4   = '';
        } // end if... else...
        echo "\n";
        ?>
<td>
    <form action="sql.php" method="post">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $html_sql_query; ?>" />
        <input type="hidden" name="pos" value="<?php echo $pos_next; ?>" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="submit" name="navig" value="<?php echo $caption3; ?>"<?php echo $title3; ?> />
    </form>
</td>
<td>
    <form action="sql.php" method="post"
        onsubmit="return <?php echo (($_SESSION['userconf']['pos'] + $_SESSION['userconf']['max_rows'] < $unlim_num_rows && $num_rows >= $_SESSION['userconf']['max_rows']) ? 'true' : 'false'); ?>">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $html_sql_query; ?>" />
        <input type="hidden" name="pos" value="<?php echo @((ceil($unlim_num_rows / $_SESSION['userconf']['max_rows'])- 1) * $_SESSION['userconf']['max_rows']); ?>" />
        <?php
        if ($is_innodb && $unlim_num_rows > $GLOBALS['cfg']['MaxExactCount']) {
            echo '<input type="hidden" name="find_real_end" value="1" />' . "\n";
            // no backquote around this message
            $onclick = ' onclick="return confirmAction(\'' . PMA_jsFormat($GLOBALS['strLongOperation'], false) . '\')"';
        }
        ?>
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="submit" name="navig" value="<?php echo $caption4; ?>"<?php echo $title4; ?> <?php echo (empty($onclick) ? '' : $onclick); ?>/>
    </form>
</td>
        <?php
    } // end move toward


    //page redirection
    // (unless we are showing all records)
    if ('all' != $_SESSION['userconf']['max_rows']) { //if1
        $pageNow = @floor($_SESSION['userconf']['pos'] / $_SESSION['userconf']['max_rows']) + 1;
        $nbTotalPage = @ceil($unlim_num_rows / $_SESSION['userconf']['max_rows']);

        if ($nbTotalPage > 1){ //if2
       ?>
   <td>
       &nbsp;&nbsp;&nbsp;
   </td>
   <td>
        <?php //<form> for keep the form alignment of button < and << ?>
        <form action="none">
        <?php
            $_url_params = array(
                'db'        => $db,
                'table'     => $table,
                'sql_query' => $sql_query,
                'goto'      => $goto,
            );
            echo PMA_pageselector(
                     'sql.php' . PMA_generate_common_url($_url_params) . PMA_get_arg_separator('js'),
                     $_SESSION['userconf']['max_rows'],
                    $pageNow,
                    $nbTotalPage,
                    200,
                    5,
                    5,
                    20,
                    10,
                    $GLOBALS['strPageNumber']
            );
        ?>
        </form>
    </td>
        <?php
        } //_if2
    } //_if1

    // Display the "Show all" button if allowed
    if ($GLOBALS['cfg']['ShowAll'] && ($num_rows < $unlim_num_rows)) {
        echo "\n";
        ?>
<td>
    &nbsp;&nbsp;&nbsp;
</td>
<td>
    <form action="sql.php" method="post">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
        <input type="hidden" name="sql_query" value="<?php echo $html_sql_query; ?>" />
        <input type="hidden" name="pos" value="0" />
        <input type="hidden" name="session_max_rows" value="all" />
        <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
        <input type="submit" name="navig" value="<?php echo $GLOBALS['strShowAll']; ?>" />
    </form>
</td>
        <?php
    } // end show all
    echo "\n";
    ?>
</tr>
</table>

    <?php
} // end of the 'PMA_displayTableNavigation()' function


/**
 * Displays the headers of the results table
 *
 * @uses    $_SESSION['userconf']['disp_direction']
 * @uses    $_SESSION['userconf']['repeat_cells']
 * @uses    $_SESSION['userconf']['max_rows']
 * @uses    $_SESSION['userconf']['display_text']
 * @uses    $_SESSION['userconf']['display_binary']
 * @param   array    which elements to display
 * @param   array    the list of fields properties
 * @param   integer  the total number of fields returned by the SQL query
 * @param   array    the analyzed query
 *
 * @return  boolean  always true
 *
 * @global  string   $db               the database name
 * @global  string   $table            the table name
 * @global  string   $goto             the URL to go back in case of errors
 * @global  string   $sql_query        the SQL query
 * @global  integer  $num_rows         the total number of rows returned by the
 *                                     SQL query
 * @global  array    $vertical_display informations used with vertical display
 *                                     mode
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_displayTableHeaders(&$is_display, &$fields_meta, $fields_cnt = 0, $analyzed_sql = '', $sort_expression, $sort_expression_nodirection, $sort_direction)
{
    global $db, $table, $goto;
    global $sql_query, $num_rows;
    global $vertical_display, $highlight_columns;

    if ($analyzed_sql == '') {
        $analyzed_sql = array();
    }

    // can the result be sorted?
    if ($is_display['sort_lnk'] == '1') {

        // Just as fallback
        $unsorted_sql_query     = $sql_query;
        if (isset($analyzed_sql[0]['unsorted_query'])) {
            $unsorted_sql_query = $analyzed_sql[0]['unsorted_query'];
        }
        // Handles the case of multiple clicks on a column's header
        // which would add many spaces before "ORDER BY" in the
        // generated query.
        $unsorted_sql_query = trim($unsorted_sql_query);

        // sorting by indexes, only if it makes sense (only one table ref)
        if (isset($analyzed_sql) && isset($analyzed_sql[0]) &&
            isset($analyzed_sql[0]['querytype']) && $analyzed_sql[0]['querytype'] == 'SELECT' &&
            isset($analyzed_sql[0]['table_ref']) && count($analyzed_sql[0]['table_ref']) == 1) {

            // grab indexes data:
            $indexes = PMA_Index::getFromTable($table, $db);

            // do we have any index?
            if ($indexes) {

                if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
                 || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
                    $span = $fields_cnt;
                    if ($is_display['edit_lnk'] != 'nn') {
                        $span++;
                    }
                    if ($is_display['del_lnk'] != 'nn') {
                        $span++;
                    }
                    if ($is_display['del_lnk'] != 'kp' && $is_display['del_lnk'] != 'nn') {
                        $span++;
                    }
                } else {
                    $span = $num_rows + floor($num_rows/$_SESSION['userconf']['repeat_cells']) + 1;
                }

                echo '<form action="sql.php" method="post">' . "\n";
                echo PMA_generate_common_hidden_inputs($db, $table);
                echo $GLOBALS['strSortByKey'] . ': <select name="sql_query" onchange="this.form.submit();">' . "\n";
                $used_index = false;
                $local_order = (isset($sort_expression) ? $sort_expression : '');
                foreach ($indexes as $index) {
                    $asc_sort = '`' . implode('` ASC, `', array_keys($index->getColumns())) . '` ASC';
                    $desc_sort = '`' . implode('` DESC, `', array_keys($index->getColumns())) . '` DESC';
                    $used_index = $used_index || $local_order == $asc_sort || $local_order == $desc_sort;
                    echo '<option value="'
                        . htmlspecialchars($unsorted_sql_query  . ' ORDER BY ' . $asc_sort)
                        . '"' . ($local_order == $asc_sort ? ' selected="selected"' : '')
                        . '>' . htmlspecialchars($index->getName()) . ' ('
                        . $GLOBALS['strAscending'] . ')</option>';
                    echo '<option value="'
                        . htmlspecialchars($unsorted_sql_query . ' ORDER BY ' . $desc_sort)
                        . '"' . ($local_order == $desc_sort ? ' selected="selected"' : '')
                        . '>' . htmlspecialchars($index->getName()) . ' ('
                        . $GLOBALS['strDescending'] . ')</option>';
                }
                echo '<option value="' . htmlspecialchars($unsorted_sql_query) . '"' . ($used_index ? '' : ' selected="selected"') . '>' . $GLOBALS['strNone'] . '</option>';
                echo '</select>' . "\n";
                echo '<noscript><input type="submit" value="' . $GLOBALS['strGo'] . '" /></noscript>';
                echo '</form>' . "\n";
            }
        }
    }


    $vertical_display['emptypre']   = 0;
    $vertical_display['emptyafter'] = 0;
    $vertical_display['textbtn']    = '';

    // Display options (if we are not in print view)
    if (! (isset($GLOBALS['printview']) && $GLOBALS['printview'] == '1')) {
        echo '<form method="post" action="sql.php" name="displayOptionsForm" id="displayOptionsForm">';
        $url_params = array(
            'db' => $db,
            'table' => $table,
            'sql_query' => $sql_query,
            'goto' => $goto,
            'display_options_form' => 1
        );
        echo PMA_generate_common_hidden_inputs($url_params);
        echo '<br />';
        PMA_generate_slider_effect('displayoptions',$GLOBALS['strOptions']);
        echo '<fieldset>';

        echo '<div class="formelement">';
        $choices = array(
            'P'   => $GLOBALS['strPartialText'],
            'F'   => $GLOBALS['strFullText']
        );
        PMA_generate_html_radio('display_text', $choices, $_SESSION['userconf']['display_text']);
        echo '</div>';

        if ($GLOBALS['cfgRelation']['relwork'] && $GLOBALS['cfgRelation']['displaywork']) {
            echo '<div class="formelement">';
            $choices = array(
                'K'   => $GLOBALS['strRelationalKey'],
                'D'   => $GLOBALS['strRelationalDisplayField']
            );
            PMA_generate_html_radio('relational_display', $choices, $_SESSION['userconf']['relational_display']);
            echo '</div>';
        }

        echo '<div class="formelement">';
        PMA_generate_html_checkbox('display_binary', $GLOBALS['strShowBinaryContents'], ! empty($_SESSION['userconf']['display_binary']), false);
        echo '<br />';
        PMA_generate_html_checkbox('display_blob', $GLOBALS['strShowBLOBContents'], ! empty($_SESSION['userconf']['display_blob']), false);
        echo '</div>';

        // I would have preferred to name this "display_transformation".
        // This is the only way I found to be able to keep this setting sticky
        // per SQL query, and at the same time have a default that displays
        // the transformations.
        echo '<div class="formelement">';
        PMA_generate_html_checkbox('hide_transformation', $GLOBALS['strHide'] . ' ' . $GLOBALS['strMIME_transformation'], ! empty($_SESSION['userconf']['hide_transformation']), false);
        echo '</div>';

        echo '<div class="clearfloat"></div>';
        echo '</fieldset>';

        echo '<fieldset class="tblFooters">';
        echo '<input type="submit" value="' . $GLOBALS['strGo'] . '" />';
        echo '</fieldset>';
        echo '</div>';
        echo '</form>';
    }

    // Start of form for multi-rows edit/delete/export

    if ($is_display['del_lnk'] == 'dr' || $is_display['del_lnk'] == 'kp') {
        echo '<form method="post" action="tbl_row_action.php" name="rowsDeleteForm" id="rowsDeleteForm">' . "\n";
        echo PMA_generate_common_hidden_inputs($db, $table, 1);
        echo '<input type="hidden" name="goto"             value="sql.php" />' . "\n";
    }

    echo '<table id="table_results" class="data">' . "\n";
    if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
     || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
        echo '<thead><tr>' . "\n";
    }

    // 1. Displays the full/partial text button (part 1)...
    if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
     || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
        $colspan  = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn')
                  ? ' colspan="3"'
                  : '';
    } else {
        $rowspan  = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn')
                  ? ' rowspan="3"'
                  : '';
    }

    //     ... before the result table
    if (($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')
        && $is_display['text_btn'] == '1') {
        $vertical_display['emptypre'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 0;
        if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
         || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
            ?>
    <th colspan="<?php echo $fields_cnt; ?>"></th>
</tr>
<tr>
            <?php
        } // end horizontal/horizontalflipped mode
        else {
            ?>
<tr>
    <th colspan="<?php echo $num_rows + floor($num_rows/$_SESSION['userconf']['repeat_cells']) + 1; ?>"></th>
</tr>
            <?php
        } // end vertical mode
    }

    //     ... at the left column of the result table header if possible
    //     and required
    elseif ($GLOBALS['cfg']['ModifyDeleteAtLeft'] && $is_display['text_btn'] == '1') {
        $vertical_display['emptypre'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 0;
        if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
         || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
            ?>
    <th <?php echo $colspan; ?>></th>
            <?php
        } // end horizontal/horizontalflipped mode
        else {
            $vertical_display['textbtn'] = '    <th ' . $rowspan . ' valign="middle">' . "\n"
                                         . '        ' . "\n"
                                         . '    </th>' . "\n";
        } // end vertical mode
    }

    //     ... elseif no button, displays empty(ies) col(s) if required
    elseif ($GLOBALS['cfg']['ModifyDeleteAtLeft']
             && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')) {
        $vertical_display['emptypre'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 0;
        if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
         || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
            ?>
    <td<?php echo $colspan; ?>></td>
            <?php
        } // end horizontal/horizontalfipped mode
        else {
            $vertical_display['textbtn'] = '    <td' . $rowspan . '></td>' . "\n";
        } // end vertical mode
    }

    // 2. Displays the fields' name
    // 2.0 If sorting links should be used, checks if the query is a "JOIN"
    //     statement (see 2.1.3)

    // 2.0.1 Prepare Display column comments if enabled ($GLOBALS['cfg']['ShowBrowseComments']).
    //       Do not show comments, if using horizontalflipped mode, because of space usage
    if ($GLOBALS['cfg']['ShowBrowseComments']
     && $_SESSION['userconf']['disp_direction'] != 'horizontalflipped') {
        $comments_map = array();
        if (isset($analyzed_sql[0]) && is_array($analyzed_sql[0])) {
            foreach ($analyzed_sql[0]['table_ref'] as $tbl) {
                $tb = $tbl['table_true_name'];
                $comments_map[$tb] = PMA_getComments($db, $tb);
                unset($tb);
            }
        }
    }

    if ($GLOBALS['cfgRelation']['commwork'] && $GLOBALS['cfgRelation']['mimework'] && $GLOBALS['cfg']['BrowseMIME'] && ! $_SESSION['userconf']['hide_transformation']) {
        require_once './libraries/transformations.lib.php';
        $GLOBALS['mime_map'] = PMA_getMIME($db, $table);
    }

    if ($is_display['sort_lnk'] == '1') {
        $select_expr = $analyzed_sql[0]['select_expr_clause'];
    }

    // garvin: See if we have to highlight any header fields of a WHERE query.
    //  Uses SQL-Parser results.
    $highlight_columns = array();
    if (isset($analyzed_sql) && isset($analyzed_sql[0]) &&
        isset($analyzed_sql[0]['where_clause_identifiers'])) {

        $wi = 0;
        if (isset($analyzed_sql[0]['where_clause_identifiers']) && is_array($analyzed_sql[0]['where_clause_identifiers'])) {
            foreach ($analyzed_sql[0]['where_clause_identifiers'] AS $wci_nr => $wci) {
                $highlight_columns[$wci] = 'true';
            }
        }
    }

    for ($i = 0; $i < $fields_cnt; $i++) {
        // garvin: See if this column should get highlight because it's used in the
        //  where-query.
        if (isset($highlight_columns[$fields_meta[$i]->name]) || isset($highlight_columns[PMA_backquote($fields_meta[$i]->name)])) {
            $condition_field = true;
        } else {
            $condition_field = false;
        }

        // 2.0 Prepare comment-HTML-wrappers for each row, if defined/enabled.
        if (isset($comments_map) &&
                isset($comments_map[$fields_meta[$i]->table]) &&
                isset($comments_map[$fields_meta[$i]->table][$fields_meta[$i]->name])) {
            $comments = '<span class="tblcomment">' . htmlspecialchars($comments_map[$fields_meta[$i]->table][$fields_meta[$i]->name]) . '</span>';
        } else {
            $comments = '';
        }

        // 2.1 Results can be sorted
        if ($is_display['sort_lnk'] == '1') {

            // 2.1.1 Checks if the table name is required; it's the case
            //       for a query with a "JOIN" statement and if the column
            //       isn't aliased, or in queries like
            //       SELECT `1`.`master_field` , `2`.`master_field`
            //       FROM `PMA_relation` AS `1` , `PMA_relation` AS `2`

            if (isset($fields_meta[$i]->table) && strlen($fields_meta[$i]->table)) {
                $sort_tbl = PMA_backquote($fields_meta[$i]->table) . '.';
            } else {
                $sort_tbl = '';
            }

            // 2.1.2 Checks if the current column is used to sort the
            //       results
            // the orgname member does not exist for all MySQL versions
            // but if found, it's the one on which to sort
            $name_to_use_in_sort = $fields_meta[$i]->name;
            if (isset($fields_meta[$i]->orgname) && strlen($fields_meta[$i]->orgname)) {
                $name_to_use_in_sort = $fields_meta[$i]->orgname;
            }
            // $name_to_use_in_sort might contain a space due to
            // formatting of function expressions like "COUNT(name )"
            // so we remove the space in this situation
            $name_to_use_in_sort = str_replace(' )', ')', $name_to_use_in_sort);

            if (empty($sort_expression)) {
                $is_in_sort = false;
            } else {
                // field name may be preceded by a space, or any number
                // of characters followed by a dot (tablename.fieldname)
                // so do a direct comparison
                // for the sort expression (avoids problems with queries
                // like "SELECT id, count(id)..." and clicking to sort
                // on id or on count(id))
                if (strpos($sort_expression_nodirection, $sort_tbl) === false) {
                    $sort_expression_nodirection = $sort_tbl . $sort_expression_nodirection;
                }
                $is_in_sort = (str_replace('`', '', $sort_tbl) . $name_to_use_in_sort == str_replace('`', '', $sort_expression_nodirection) ? true : false);
            }
            // 2.1.3 Check the field name for a bracket.
            //       If it contains one, it's probably a function column
            //       like 'COUNT(`field`)'
            if (strpos($name_to_use_in_sort, '(') !== false) {
                $sort_order = ' ORDER BY ' . $name_to_use_in_sort . ' ';
            } else {
                $sort_order = ' ORDER BY ' . $sort_tbl . PMA_backquote($name_to_use_in_sort) . ' ';
            }
            unset($name_to_use_in_sort);

            // 2.1.4 Do define the sorting URL
            if (! $is_in_sort) {
                // loic1: patch #455484 ("Smart" order)
                $GLOBALS['cfg']['Order'] = strtoupper($GLOBALS['cfg']['Order']);
                if ($GLOBALS['cfg']['Order'] === 'SMART') {
                    $sort_order .= (preg_match('@time|date@i', $fields_meta[$i]->type)) ? 'DESC' : 'ASC';
                } else {
                    $sort_order .= $GLOBALS['cfg']['Order'];
                }
                $order_img   = '';
            } elseif ('DESC' == $sort_direction) {
                $sort_order .= ' ASC';
                $order_img   = ' <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 's_desc.png" width="11" height="9" alt="'. $GLOBALS['strDescending'] . '" title="'. $GLOBALS['strDescending'] . '" id="soimg' . $i . '" />';
            } else {
                $sort_order .= ' DESC';
                $order_img   = ' <img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 's_asc.png" width="11" height="9" alt="'. $GLOBALS['strAscending'] . '" title="'. $GLOBALS['strAscending'] . '" id="soimg' . $i . '" />';
            }

            if (preg_match('@(.*)([[:space:]](LIMIT (.*)|PROCEDURE (.*)|FOR UPDATE|LOCK IN SHARE MODE))@i', $unsorted_sql_query, $regs3)) {
                $sorted_sql_query = $regs3[1] . $sort_order . $regs3[2];
            } else {
                $sorted_sql_query = $unsorted_sql_query . $sort_order;
            }
            $_url_params = array(
                'db'        => $db,
                'table'     => $table,
                'sql_query' => $sorted_sql_query,
            );
            $order_url  = 'sql.php' . PMA_generate_common_url($_url_params);

            // 2.1.5 Displays the sorting URL
            // added 20004-06-09: Michael Keck <mail@michaelkeck.de>
            //                    enable sort order swapping for image
            $order_link_params = array();
            if (isset($order_img) && $order_img!='') {
                if (strstr($order_img, 'asc')) {
                    $order_link_params['onmouseover'] = 'if(document.getElementById(\'soimg' . $i . '\')){ document.getElementById(\'soimg' . $i . '\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_desc.png\'; }';
                    $order_link_params['onmouseout']  = 'if(document.getElementById(\'soimg' . $i . '\')){ document.getElementById(\'soimg' . $i . '\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_asc.png\'; }';
                } elseif (strstr($order_img, 'desc')) {
                    $order_link_params['onmouseover'] = 'if(document.getElementById(\'soimg' . $i . '\')){ document.getElementById(\'soimg' . $i . '\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_asc.png\'; }';
                    $order_link_params['onmouseout']  = 'if(document.getElementById(\'soimg' . $i . '\')){ document.getElementById(\'soimg' . $i . '\').src=\'' . $GLOBALS['pmaThemeImage'] . 's_desc.png\'; }';
                }
            }
            if ($_SESSION['userconf']['disp_direction'] == 'horizontalflipped'
             && $GLOBALS['cfg']['HeaderFlipType'] == 'css') {
                $order_link_params['style'] = 'direction: ltr; writing-mode: tb-rl;';
            }
            $order_link_params['title'] = $GLOBALS['strSort'];
            $order_link_content = ($_SESSION['userconf']['disp_direction'] == 'horizontalflipped' && $GLOBALS['cfg']['HeaderFlipType'] == 'fake' ? PMA_flipstring(htmlspecialchars($fields_meta[$i]->name), "<br />\n") : htmlspecialchars($fields_meta[$i]->name));
            $order_link = PMA_linkOrButton($order_url, $order_link_content . $order_img, $order_link_params, false, true);

            if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
             || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
                echo '<th';
                if ($condition_field) {
                    echo ' class="condition"';
                }
                if ($_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
                    echo ' valign="bottom"';
                }
                echo '>' . $order_link . $comments . '</th>';
            }
            $vertical_display['desc'][] = '    <th '
                . ($condition_field ? ' class="condition"' : '') . '>' . "\n"
                . $order_link . $comments . '    </th>' . "\n";
        } // end if (2.1)

        // 2.2 Results can't be sorted
        else {
            if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
             || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
                echo '<th';
                if ($condition_field) {
                    echo ' class="condition"';
                }
                if ($_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
                    echo ' valign="bottom"';
                }
                if ($_SESSION['userconf']['disp_direction'] == 'horizontalflipped'
                 && $GLOBALS['cfg']['HeaderFlipType'] == 'css') {
                    echo ' style="direction: ltr; writing-mode: tb-rl;"';
                }
                echo '>';
                if ($_SESSION['userconf']['disp_direction'] == 'horizontalflipped'
                 && $GLOBALS['cfg']['HeaderFlipType'] == 'fake') {
                    echo PMA_flipstring(htmlspecialchars($fields_meta[$i]->name), '<br />');
                } else {
                    echo htmlspecialchars($fields_meta[$i]->name);
                }
                echo "\n" . $comments . '</th>';
            }
            $vertical_display['desc'][] = '    <th '
                . ($condition_field ? ' class="condition"' : '') . '>' . "\n"
                . '        ' . htmlspecialchars($fields_meta[$i]->name) . "\n"
                . $comments . '    </th>';
        } // end else (2.2)
    } // end for

    // 3. Displays the needed checkboxes at the right
    //    column of the result table header if possible and required...
    if ($GLOBALS['cfg']['ModifyDeleteAtRight']
        && ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn')
        && $is_display['text_btn'] == '1') {
        $vertical_display['emptyafter'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 1;
        if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
         || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
            echo "\n";
            ?>
<th <?php echo $colspan; ?>>
</th>
            <?php
        } // end horizontal/horizontalflipped mode
        else {
            $vertical_display['textbtn'] = '    <th ' . $rowspan . ' valign="middle">' . "\n"
                                         . '        ' . "\n"
                                         . '    </th>' . "\n";
        } // end vertical mode
    }

    //     ... elseif no button, displays empty columns if required
    // (unless coming from Browse mode print view)
    elseif ($GLOBALS['cfg']['ModifyDeleteAtRight']
             && ($is_display['edit_lnk'] == 'nn' && $is_display['del_lnk'] == 'nn')
             && (!$GLOBALS['is_header_sent'])) {
        $vertical_display['emptyafter'] = ($is_display['edit_lnk'] != 'nn' && $is_display['del_lnk'] != 'nn') ? 3 : 1;
        if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
         || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
            echo "\n";
            ?>
<td<?php echo $colspan; ?>></td>
            <?php
        } // end horizontal/horizontalflipped mode
        else {
            $vertical_display['textbtn'] = '    <td' . $rowspan . '></td>' . "\n";
        } // end vertical mode
    }

    if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
     || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
        ?>
</tr>
</thead>
        <?php
    }

    return true;
} // end of the 'PMA_displayTableHeaders()' function



/**
 * Displays the body of the results table
 *
 * @uses    $_SESSION['userconf']['disp_direction']
 * @uses    $_SESSION['userconf']['repeat_cells']
 * @uses    $_SESSION['userconf']['max_rows']
 * @uses    $_SESSION['userconf']['display_text']
 * @uses    $_SESSION['userconf']['display_binary']
 * @uses    $_SESSION['userconf']['display_blob']
 * @param   integer  the link id associated to the query which results have
 *                   to be displayed
 * @param   array    which elements to display
 * @param   array    the list of relations
 * @param   array    the analyzed query
 *
 * @return  boolean  always true
 *
 * @global  string   $db                the database name
 * @global  string   $table             the table name
 * @global  string   $goto              the URL to go back in case of errors
 * @global  string   $sql_query         the SQL query
 * @global  array    $fields_meta       the list of fields properties
 * @global  integer  $fields_cnt        the total number of fields returned by
 *                                      the SQL query
 * @global  array    $vertical_display  informations used with vertical display
 *                                      mode
 * @global  array    $highlight_columns column names to highlight
 * @global  array    $row               current row data
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_displayTableBody(&$dt_result, &$is_display, $map, $analyzed_sql) {
    global $db, $table, $goto;
    global $sql_query, $fields_meta, $fields_cnt;
    global $vertical_display, $highlight_columns;
    global $row; // mostly because of browser transformations, to make the row-data accessible in a plugin

    $url_sql_query          = $sql_query;

    // query without conditions to shorten URLs when needed, 200 is just
    // guess, it should depend on remaining URL length

    if (isset($analyzed_sql) && isset($analyzed_sql[0]) &&
        isset($analyzed_sql[0]['querytype']) && $analyzed_sql[0]['querytype'] == 'SELECT' &&
        strlen($sql_query) > 200) {

        $url_sql_query = 'SELECT ';
        if (isset($analyzed_sql[0]['queryflags']['distinct'])) {
            $url_sql_query .= ' DISTINCT ';
        }
        $url_sql_query .= $analyzed_sql[0]['select_expr_clause'];
        if (!empty($analyzed_sql[0]['from_clause'])) {
            $url_sql_query .= ' FROM ' . $analyzed_sql[0]['from_clause'];
        }
    }

    if (!is_array($map)) {
        $map = array();
    }
    $row_no                         = 0;
    $vertical_display['edit']       = array();
    $vertical_display['delete']     = array();
    $vertical_display['data']       = array();
    $vertical_display['row_delete'] = array();

    // Correction University of Virginia 19991216 in the while below
    // Previous code assumed that all tables have keys, specifically that
    // the phpMyAdmin GUI should support row delete/edit only for such
    // tables.
    // Although always using keys is arguably the prescribed way of
    // defining a relational table, it is not required. This will in
    // particular be violated by the novice.
    // We want to encourage phpMyAdmin usage by such novices. So the code
    // below has been changed to conditionally work as before when the
    // table being displayed has one or more keys; but to display
    // delete/edit options correctly for tables without keys.

    $odd_row = true;
    while ($row = PMA_DBI_fetch_row($dt_result)) {
        // lem9: "vertical display" mode stuff
        if ($row_no != 0 && $_SESSION['userconf']['repeat_cells'] != 0 && !($row_no % $_SESSION['userconf']['repeat_cells'])
          && ($_SESSION['userconf']['disp_direction'] == 'horizontal'
           || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped'))
        {
            echo '<tr>' . "\n";
            if ($vertical_display['emptypre'] > 0) {
                echo '    <th colspan="' . $vertical_display['emptypre'] . '">' . "\n"
                    .'        &nbsp;</th>' . "\n";
            }

            foreach ($vertical_display['desc'] as $val) {
                echo $val;
            }

            if ($vertical_display['emptyafter'] > 0) {
                echo '    <th colspan="' . $vertical_display['emptyafter'] . '">' . "\n"
                    .'        &nbsp;</th>' . "\n";
            }
            echo '</tr>' . "\n";
        } // end if

        $class = $odd_row ? 'odd' : 'even';
        $odd_row = ! $odd_row;
        if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
         || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
            // loic1: pointer code part
            echo '    <tr class="' . $class . '">' . "\n";
            $class = '';
        }


        // 1. Prepares the row (gets primary keys to use)
        // 1.1 Results from a "SELECT" statement -> builds the
        //     "primary" key to use in links
        /**
         * @todo $unique_condition could be empty, for example a table
         *       with only one field and it's a BLOB; in this case,
         *       avoid to display the delete and edit links
         */
        $unique_condition      = PMA_getUniqueCondition($dt_result, $fields_cnt, $fields_meta, $row);
        $unique_condition_html = urlencode($unique_condition);

        // 1.2 Defines the URLs for the modify/delete link(s)

        if ($is_display['edit_lnk'] != 'nn' || $is_display['del_lnk'] != 'nn') {
            // We need to copy the value or else the == 'both' check will always return true

            if ($GLOBALS['cfg']['PropertiesIconic'] === 'both') {
                $iconic_spacer = '<div class="nowrap">';
            } else {
                $iconic_spacer = '';
            }

            // 1.2.1 Modify link(s)
            if ($is_display['edit_lnk'] == 'ur') { // update row case
                $_url_params = array(
                    'db'            => $db,
                    'table'         => $table,
                    'primary_key'   => $unique_condition,
                    'sql_query'     => $url_sql_query,
                    'goto'          => 'sql.php',
                );
                $edit_url = 'tbl_change.php' . PMA_generate_common_url($_url_params);

                $edit_str = PMA_getIcon('b_edit.png', $GLOBALS['strEdit'], true);
            } // end if (1.2.1)

            if (isset($GLOBALS['cfg']['Bookmark']['table']) && isset($GLOBALS['cfg']['Bookmark']['db']) && $table == $GLOBALS['cfg']['Bookmark']['table'] && $db == $GLOBALS['cfg']['Bookmark']['db'] && isset($row[1]) && isset($row[0])) {
                $_url_params = array(
                    'db'                    => $row[1],
                    'id_bookmark'           => $row[0],
                    'action_bookmark'       => '0',
                    'action_bookmark_all'   => '1',
                    'SQL'       => $GLOBALS['strExecuteBookmarked'],
                );
                $bookmark_go = '<a href="import.php'
                                . PMA_generate_common_url($_url_params)
                                .' " title="' . $GLOBALS['strExecuteBookmarked'] . '">';

                $bookmark_go .= PMA_getIcon('b_bookmark.png', $GLOBALS['strExecuteBookmarked'], true);

                $bookmark_go .= '</a>';
            } else {
                $bookmark_go = '';
            }

            // 1.2.2 Delete/Kill link(s)
            if ($is_display['del_lnk'] == 'dr') { // delete row case
                $_url_params = array(
                    'db'        => $db,
                    'table'     => $table,
                    'sql_query' => $url_sql_query,
                    'zero_rows' => $GLOBALS['strDeleted'],
                    'goto'      => (empty($goto) ? 'tbl_sql.php' : $goto),
                );
                $lnk_goto = 'sql.php' . PMA_generate_common_url($_url_params, 'text');

                $del_query = 'DELETE FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table)
                    . ' WHERE ' . $unique_condition . ' LIMIT 1';

                $_url_params = array(
                    'db'        => $db,
                    'table'     => $table,
                    'sql_query' => $del_query,
                    'zero_rows' => $GLOBALS['strDeleted'],
                    'goto'      => $lnk_goto,
                );
                $del_url  = 'sql.php' . PMA_generate_common_url($_url_params);

                $js_conf  = 'DELETE FROM ' . PMA_jsFormat($db) . '.' . PMA_jsFormat($table)
                          . ' WHERE ' . PMA_jsFormat($unique_condition, false)
                          . ' LIMIT 1';
                $del_str = PMA_getIcon('b_drop.png', $GLOBALS['strDelete'], true);
            } elseif ($is_display['del_lnk'] == 'kp') { // kill process case

                $_url_params = array(
                    'db'        => $db,
                    'table'     => $table,
                    'sql_query' => $url_sql_query,
                    'goto'      => 'main.php',
                );
                $lnk_goto = 'sql.php' . PMA_generate_common_url($_url_params, 'text');

                $_url_params = array(
                    'db'        => 'mysql',
                    'sql_query' => 'KILL ' . $row[0],
                    'goto'      => $lnk_goto,
                );
                $del_url  = 'sql.php' . PMA_generate_common_url($_url_params);
                $del_query = 'KILL ' . $row[0];
                $js_conf  = 'KILL ' . $row[0];
                $del_str = PMA_getIcon('b_drop.png', $GLOBALS['strKill'], true);
            } // end if (1.2.2)

            // 1.3 Displays the links at left if required
            if ($GLOBALS['cfg']['ModifyDeleteAtLeft']
             && ($_SESSION['userconf']['disp_direction'] == 'horizontal'
              || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped')) {
                $doWriteModifyAt = 'left';
                require './libraries/display_tbl_links.lib.php';
            } // end if (1.3)
        } // end if (1)

        // 2. Displays the rows' values
        for ($i = 0; $i < $fields_cnt; ++$i) {
            $meta    = $fields_meta[$i];
            $pointer = $i;
            // garvin: See if this column should get highlight because it's used in the
            //  where-query.
            if (isset($highlight_columns) && (isset($highlight_columns[$meta->name]) || isset($highlight_columns[PMA_backquote($meta->name)]))) {
                $condition_field = true;
            } else {
                $condition_field = false;
            }

            $mouse_events = '';
            if ($_SESSION['userconf']['disp_direction'] == 'vertical' && (!isset($GLOBALS['printview']) || ($GLOBALS['printview'] != '1'))) {
                if ($GLOBALS['cfg']['BrowsePointerEnable'] == true) {
                    $mouse_events .= ' onmouseover="setVerticalPointer(this, ' . $row_no . ', \'over\', \'odd\', \'even\', \'hover\', \'marked\');"'
                              . ' onmouseout="setVerticalPointer(this, ' . $row_no . ', \'out\', \'odd\', \'even\', \'hover\', \'marked\');" ';
                }
                if ($GLOBALS['cfg']['BrowseMarkerEnable'] == true) {
                    $mouse_events .= ' onmousedown="setVerticalPointer(this, ' . $row_no . ', \'click\', \'odd\', \'even\', \'hover\', \'marked\'); setCheckboxColumn(\'id_rows_to_delete' . $row_no . '\');" ';
                } else {
                    $mouse_events .= ' onmousedown="setCheckboxColumn(\'id_rows_to_delete' . $row_no . '\');" ';
                }
            }// end if

            // garvin: Wrap MIME-transformations. [MIME]
            $default_function = 'default_function'; // default_function
            $transform_function = $default_function;
            $transform_options = array();

            if ($GLOBALS['cfgRelation']['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {

                if (isset($GLOBALS['mime_map'][$meta->name]['mimetype']) && isset($GLOBALS['mime_map'][$meta->name]['transformation']) && !empty($GLOBALS['mime_map'][$meta->name]['transformation'])) {
                    $include_file = $GLOBALS['mime_map'][$meta->name]['transformation'];

                    if (file_exists('./libraries/transformations/' . $include_file)) {
                        $transformfunction_name = str_replace('.inc.php', '', $GLOBALS['mime_map'][$meta->name]['transformation']);

                        require_once './libraries/transformations/' . $include_file;

                        if (function_exists('PMA_transformation_' . $transformfunction_name)) {
                            $transform_function = 'PMA_transformation_' . $transformfunction_name;
                            $transform_options  = PMA_transformation_getOptions((isset($GLOBALS['mime_map'][$meta->name]['transformation_options']) ? $GLOBALS['mime_map'][$meta->name]['transformation_options'] : ''));
                            $meta->mimetype     = str_replace('_', '/', $GLOBALS['mime_map'][$meta->name]['mimetype']);
                        }
                    } // end if file_exists
                } // end if transformation is set
            } // end if mime/transformation works.

            $_url_params = array(
                'db'            => $db,
                'table'         => $table,
                'primary_key'   => $unique_condition,
                'transform_key' => $meta->name,
            );

            if (! empty($sql_query)) {
                $_url_params['sql_query'] = $url_sql_query;
            }

            $transform_options['wrapper_link'] = PMA_generate_common_url($_url_params);

            // n u m e r i c
            if ($meta->numeric == 1) {

                // lem9: if two fields have the same name (this is possible
                //       with self-join queries, for example), using $meta->name
                //       will show both fields NULL even if only one is NULL,
                //       so use the $pointer

                if (!isset($row[$i]) || is_null($row[$i])) {
                    $vertical_display['data'][$row_no][$i]     = '    <td align="right"' . $mouse_events . ' class="' . $class . ($condition_field ? ' condition' : '') . '"><i>NULL</i></td>' . "\n";
                } elseif ($row[$i] != '') {

                    $nowrap = ' nowrap';
                    $where_comparison = ' = ' . $row[$i];

                    $vertical_display['data'][$row_no][$i]     = '<td align="right"' . PMA_prepare_row_data($mouse_events, $class, $condition_field, $analyzed_sql, $meta, $map, $row[$i], $transform_function, $default_function, $nowrap, $where_comparison, $transform_options);
                } else {
                    $vertical_display['data'][$row_no][$i]     = '    <td align="right"' . $mouse_events . ' class="' . $class . ' nowrap' . ($condition_field ? ' condition' : '') . '">&nbsp;</td>' . "\n";
                }

            //  b l o b

            } elseif (stristr($meta->type, 'BLOB')) {
                // loic1 : PMA_mysql_fetch_fields returns BLOB in place of
                // TEXT fields type so we have to ensure it's really a BLOB
                $field_flags = PMA_DBI_field_flags($dt_result, $i);
                if (stristr($field_flags, 'BINARY')) {
                    // rajk - for blobstreaming

                    $bs_reference_exists = $allBSTablesExist = FALSE;

                    // load PMA configuration
                    $PMA_Config = $_SESSION['PMA_Config'];

                    // if PMA configuration exists
                    if ($PMA_Config)
                    {
                        // load BS variables
                        $pluginsExist = $PMA_Config->get('BLOBSTREAMING_PLUGINS_EXIST');

                        // if BS plugins exist
                        if ($pluginsExist)
                        {
                            // load BS databases
                            $bs_tables = $PMA_Config->get('BLOBSTREAMABLE_DATABASES');

                            // if BS db array and specified db string not empty and valid
                            if (!empty($bs_tables) && strlen($db) > 0)
                            {
                                $bs_tables = $bs_tables[$db];

                                if (isset($bs_tables))
                                {
                                    $allBSTablesExist = TRUE;

                                    // check if BS tables exist for given database
                                    foreach ($bs_tables as $table_key=>$bs_tbl)
                                        if (!$bs_tables[$table_key]['Exists'])
                                        {
                                            $allBSTablesExist = FALSE;
                                            break;
                                        }
                                }
                            }
                        }
                    }

                    // if necessary BS tables exist
                    if ($allBSTablesExist)
                        $bs_reference_exists = PMA_BS_ReferenceExists($row[$i], $db);

                    // if valid BS reference exists
                    if ($bs_reference_exists)
                        $blobtext = PMA_BS_CreateReferenceLink($row[$i], $db);
                    else
                        $blobtext = PMA_handle_non_printable_contents('BLOB', (isset($row[$i]) ? $row[$i] : ''), $transform_function, $transform_options, $default_function, $meta);

                    $vertical_display['data'][$row_no][$i]      = '    <td align="left"' . $mouse_events . ' class="' . $class . ($condition_field ? ' condition' : '') . '">' . $blobtext . '</td>';
                    unset($blobtext);
                } else {
                    if (!isset($row[$i]) || is_null($row[$i])) {
                        $vertical_display['data'][$row_no][$i] = '    <td' . $mouse_events . ' class="' . $class . ($condition_field ? ' condition' : '') . '"><i>NULL</i></td>' . "\n";
                    } elseif ($row[$i] != '') {
                        // garvin: if a transform function for blob is set, none of these replacements will be made
                        if (PMA_strlen($row[$i]) > $GLOBALS['cfg']['LimitChars'] && $_SESSION['userconf']['display_text'] == 'P') {
                            $row[$i] = PMA_substr($row[$i], 0, $GLOBALS['cfg']['LimitChars']) . '...';
                        }
                        // loic1: displays all space characters, 4 space
                        // characters for tabulations and <cr>/<lf>
                        $row[$i]     = ($default_function != $transform_function ? $transform_function($row[$i], $transform_options, $meta) : $default_function($row[$i], array(), $meta));

                        $vertical_display['data'][$row_no][$i] = '    <td' . $mouse_events . ' class="' . $class . ($condition_field ? ' condition' : '') . '">' . $row[$i] . '</td>' . "\n";
                    } else {
                        $vertical_display['data'][$row_no][$i] = '    <td' . $mouse_events . ' class="' . $class . ($condition_field ? ' condition' : '') . '">&nbsp;</td>' . "\n";
                    }
                }
            } else {
                if (!isset($row[$i]) || is_null($row[$i])) {
                    $vertical_display['data'][$row_no][$i]     = '    <td' . $mouse_events . ' class="' . $class . ($condition_field ? ' condition' : '') . '"><i>NULL</i></td>' . "\n";
                } elseif ($row[$i] != '') {
                    // loic1: support blanks in the key
                    $relation_id = $row[$i];

                    // nijel: Cut all fields to $GLOBALS['cfg']['LimitChars']
                    // lem9: (unless it's a link-type transformation)
                    if (PMA_strlen($row[$i]) > $GLOBALS['cfg']['LimitChars'] && $_SESSION['userconf']['display_text'] == 'P' && !strpos($transform_function, 'link') === true) {
                        $row[$i] = PMA_substr($row[$i], 0, $GLOBALS['cfg']['LimitChars']) . '...';
                    }

                    // loic1: displays special characters from binaries
                    $field_flags = PMA_DBI_field_flags($dt_result, $i);
                    if (isset($meta->_type) && $meta->_type === MYSQLI_TYPE_BIT) {
                        $row[$i]     = PMA_printable_bit_value($row[$i], $meta->length);
                    } elseif (stristr($field_flags, 'BINARY') && $meta->type == 'string') {
                        if ($_SESSION['userconf']['display_binary'] || (isset($GLOBALS['is_analyse']) && $GLOBALS['is_analyse'])) {
                            // user asked to see the real contents of BINARY
                            // fields, or we detected a PROCEDURE ANALYSE in
                            // the query (results are reported as being
                            // binary strings)
                            $row[$i] = PMA_replace_binary_contents($row[$i]);
                        } else {
                            // we show the BINARY message and field's size
                            // (or maybe use a transformation)
                            $row[$i] = PMA_handle_non_printable_contents('BINARY', $row[$i], $transform_function, $transform_options, $default_function, $meta);
                        }
                    }

                    // garvin: transform functions may enable no-wrapping:
                    $function_nowrap = $transform_function . '_nowrap';
                    $bool_nowrap = (($default_function != $transform_function && function_exists($function_nowrap)) ? $function_nowrap($transform_options) : false);

                    // loic1: do not wrap if date field type
                    $nowrap = ((preg_match('@DATE|TIME@i', $meta->type) || $bool_nowrap) ? ' nowrap' : '');
                    $where_comparison = ' = \'' . PMA_sqlAddslashes($row[$i]) . '\'';
                    $vertical_display['data'][$row_no][$i]     = '<td ' . PMA_prepare_row_data($mouse_events, $class, $condition_field, $analyzed_sql, $meta, $map, $row[$i], $transform_function, $default_function, $nowrap, $where_comparison, $transform_options);

                } else {
                    $vertical_display['data'][$row_no][$i]     = '    <td' . $mouse_events . ' class="' . $class . ($condition_field ? ' condition' : '') . '">&nbsp;</td>' . "\n";
                }
            }

            // lem9: output stored cell
            if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
             || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
                echo $vertical_display['data'][$row_no][$i];
            }

            if (isset($vertical_display['rowdata'][$i][$row_no])) {
                $vertical_display['rowdata'][$i][$row_no] .= $vertical_display['data'][$row_no][$i];
            } else {
                $vertical_display['rowdata'][$i][$row_no] = $vertical_display['data'][$row_no][$i];
            }
        } // end for (2)

        // 3. Displays the modify/delete links on the right if required
        if ($GLOBALS['cfg']['ModifyDeleteAtRight']
         && ($_SESSION['userconf']['disp_direction'] == 'horizontal'
          || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped')) {
                $doWriteModifyAt = 'right';
                require './libraries/display_tbl_links.lib.php';
        } // end if (3)

        if ($_SESSION['userconf']['disp_direction'] == 'horizontal'
         || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') {
            ?>
</tr>
            <?php
        } // end if

        // 4. Gather links of del_urls and edit_urls in an array for later
        //    output
        if (!isset($vertical_display['edit'][$row_no])) {
            $vertical_display['edit'][$row_no]       = '';
            $vertical_display['delete'][$row_no]     = '';
            $vertical_display['row_delete'][$row_no] = '';
        }

        $column_style_vertical = '';
        if ($GLOBALS['cfg']['BrowsePointerEnable'] == true) {
            $column_style_vertical .= ' onmouseover="setVerticalPointer(this, ' . $row_no . ', \'over\', \'odd\', \'even\', \'hover\', \'marked\');"'
                         . ' onmouseout="setVerticalPointer(this, ' . $row_no . ', \'out\', \'odd\', \'even\', \'hover\', \'marked\');"';
        }
        $column_marker_vertical = '';
        if ($GLOBALS['cfg']['BrowseMarkerEnable'] == true) {
            $column_marker_vertical .= 'setVerticalPointer(this, ' . $row_no . ', \'click\', \'odd\', \'even\', \'hover\', \'marked\');';
        }

        if (!empty($del_url) && $is_display['del_lnk'] != 'kp') {
            $vertical_display['row_delete'][$row_no] .= '    <td align="center" class="' . $class . '" ' . $column_style_vertical . '>' . "\n"
                                                     .  '        <input type="checkbox" id="id_rows_to_delete' . $row_no . '[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[' . $unique_condition_html . ']"'
                                                     .  ' onclick="' . $column_marker_vertical . 'copyCheckboxesRange(\'rowsDeleteForm\', \'id_rows_to_delete' . $row_no . '\',\'[%_PMA_CHECKBOX_DIR_%]\');"'
                                                     .  ' value="' . htmlspecialchars($del_query) . '" ' . (isset($GLOBALS['checkall']) ? 'checked="checked"' : '') . ' />' . "\n"
                                                     .  '    </td>' . "\n";
        } else {
            unset($vertical_display['row_delete'][$row_no]);
        }

        if (isset($edit_url)) {
            $vertical_display['edit'][$row_no]   .= '    <td align="center" class="' . $class . '" ' . $column_style_vertical . '>' . "\n"
                                                 . PMA_linkOrButton($edit_url, $edit_str, array(), false)
                                                 . $bookmark_go
                                                 .  '    </td>' . "\n";
        } else {
            unset($vertical_display['edit'][$row_no]);
        }

        if (isset($del_url)) {
            $vertical_display['delete'][$row_no] .= '    <td align="center" class="' . $class . '" ' . $column_style_vertical . '>' . "\n"
                                                 . PMA_linkOrButton($del_url, $del_str, (isset($js_conf) ? $js_conf : ''), false)
                                                 .  '    </td>' . "\n";
        } else {
            unset($vertical_display['delete'][$row_no]);
        }

        echo (($_SESSION['userconf']['disp_direction'] == 'horizontal' || $_SESSION['userconf']['disp_direction'] == 'horizontalflipped') ? "\n" : '');
        $row_no++;
    } // end while

    return true;
} // end of the 'PMA_displayTableBody()' function


/**
 * Do display the result table with the vertical direction mode.
 * Credits for this feature goes to Garvin Hicking <hicking@faktor-e.de>.
 *
 * @return  boolean  always true
 *
 * @uses    $_SESSION['userconf']['repeat_cells']
 * @global  array    $vertical_display the information to display
 *
 * @access  private
 *
 * @see     PMA_displayTable()
 */
function PMA_displayVerticalTable()
{
    global $vertical_display;

    // Displays "multi row delete" link at top if required
    if ($GLOBALS['cfg']['ModifyDeleteAtLeft'] && is_array($vertical_display['row_delete']) && (count($vertical_display['row_delete']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        echo $vertical_display['textbtn'];
        $foo_counter = 0;
        foreach ($vertical_display['row_delete'] as $val) {
            if (($foo_counter != 0) && ($_SESSION['userconf']['repeat_cells'] != 0) && !($foo_counter % $_SESSION['userconf']['repeat_cells'])) {
                echo '<th></th>' . "\n";
            }

            echo str_replace('[%_PMA_CHECKBOX_DIR_%]', '', $val);
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays "edit" link at top if required
    if ($GLOBALS['cfg']['ModifyDeleteAtLeft'] && is_array($vertical_display['edit']) && (count($vertical_display['edit']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        if (!is_array($vertical_display['row_delete'])) {
            echo $vertical_display['textbtn'];
        }
        $foo_counter = 0;
        foreach ($vertical_display['edit'] as $val) {
            if (($foo_counter != 0) && ($_SESSION['userconf']['repeat_cells'] != 0) && !($foo_counter % $_SESSION['userconf']['repeat_cells'])) {
                echo '    <th></th>' . "\n";
            }

            echo $val;
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays "delete" link at top if required
    if ($GLOBALS['cfg']['ModifyDeleteAtLeft'] && is_array($vertical_display['delete']) && (count($vertical_display['delete']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        if (!is_array($vertical_display['edit']) && !is_array($vertical_display['row_delete'])) {
            echo $vertical_display['textbtn'];
        }
        $foo_counter = 0;
        foreach ($vertical_display['delete'] as $val) {
            if (($foo_counter != 0) && ($_SESSION['userconf']['repeat_cells'] != 0) && !($foo_counter % $_SESSION['userconf']['repeat_cells'])) {
                echo '<th></th>' . "\n";
            }

            echo $val;
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays data
    foreach ($vertical_display['desc'] AS $key => $val) {

        echo '<tr>' . "\n";
        echo $val;

        $foo_counter = 0;
        foreach ($vertical_display['rowdata'][$key] as $subval) {
            if (($foo_counter != 0) && ($_SESSION['userconf']['repeat_cells'] != 0) and !($foo_counter % $_SESSION['userconf']['repeat_cells'])) {
                echo $val;
            }

            echo $subval;
            $foo_counter++;
        } // end while

        echo '</tr>' . "\n";
    } // end while

    // Displays "multi row delete" link at bottom if required
    if ($GLOBALS['cfg']['ModifyDeleteAtRight'] && is_array($vertical_display['row_delete']) && (count($vertical_display['row_delete']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        echo $vertical_display['textbtn'];
        $foo_counter = 0;
        foreach ($vertical_display['row_delete'] as $val) {
            if (($foo_counter != 0) && ($_SESSION['userconf']['repeat_cells'] != 0) && !($foo_counter % $_SESSION['userconf']['repeat_cells'])) {
                echo '<th></th>' . "\n";
            }

            echo str_replace('[%_PMA_CHECKBOX_DIR_%]', 'r', $val);
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays "edit" link at bottom if required
    if ($GLOBALS['cfg']['ModifyDeleteAtRight'] && is_array($vertical_display['edit']) && (count($vertical_display['edit']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        if (!is_array($vertical_display['row_delete'])) {
            echo $vertical_display['textbtn'];
        }
        $foo_counter = 0;
        foreach ($vertical_display['edit'] as $val) {
            if (($foo_counter != 0) && ($_SESSION['userconf']['repeat_cells'] != 0) && !($foo_counter % $_SESSION['userconf']['repeat_cells'])) {
                echo '<th></th>' . "\n";
            }

            echo $val;
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    } // end if

    // Displays "delete" link at bottom if required
    if ($GLOBALS['cfg']['ModifyDeleteAtRight'] && is_array($vertical_display['delete']) && (count($vertical_display['delete']) > 0 || !empty($vertical_display['textbtn']))) {
        echo '<tr>' . "\n";
        if (!is_array($vertical_display['edit']) && !is_array($vertical_display['row_delete'])) {
            echo $vertical_display['textbtn'];
        }
        $foo_counter = 0;
        foreach ($vertical_display['delete'] as $val) {
            if (($foo_counter != 0) && ($_SESSION['userconf']['repeat_cells'] != 0) && !($foo_counter % $_SESSION['userconf']['repeat_cells'])) {
                echo '<th></th>' . "\n";
            }

            echo $val;
            $foo_counter++;
        } // end while
        echo '</tr>' . "\n";
    }

    return true;
} // end of the 'PMA_displayVerticalTable' function

/**
 *
 * @uses    $_SESSION['userconf']['disp_direction']
 * @uses    $_REQUEST['disp_direction']
 * @uses    $GLOBALS['cfg']['DefaultDisplay']
 * @uses    $_SESSION['userconf']['repeat_cells']
 * @uses    $_REQUEST['repeat_cells']
 * @uses    $GLOBALS['cfg']['RepeatCells']
 * @uses    $_SESSION['userconf']['max_rows']
 * @uses    $_REQUEST['session_max_rows']
 * @uses    $GLOBALS['cfg']['MaxRows']
 * @uses    $_SESSION['userconf']['pos']
 * @uses    $_REQUEST['pos']
 * @uses    $_SESSION['userconf']['display_text']
 * @uses    $_REQUEST['display_text']
 * @uses    $_SESSION['userconf']['relational_display']
 * @uses    $_REQUEST['relational_display']
 * @uses    $_SESSION['userconf']['display_binary']
 * @uses    $_REQUEST['display_binary']
 * @uses    $_SESSION['userconf']['display_blob']
 * @uses    $_REQUEST['display_blob']
 * @uses    PMA_isValid()
 * @uses    $GLOBALS['sql_query']
 * @todo    make maximum remembered queries configurable
 * @todo    move/split into SQL class!?
 * @todo    currently this is called twice unnecessary
 * @todo    ignore LIMIT and ORDER in query!?
 */
function PMA_displayTable_checkConfigParams()
{
    $sql_key = md5($GLOBALS['sql_query']);

    $_SESSION['userconf']['query'][$sql_key]['sql'] = $GLOBALS['sql_query'];

    if (PMA_isValid($_REQUEST['disp_direction'], array('horizontal', 'vertical', 'horizontalflipped'))) {
        $_SESSION['userconf']['query'][$sql_key]['disp_direction'] = $_REQUEST['disp_direction'];
        unset($_REQUEST['disp_direction']);
    } elseif (empty($_SESSION['userconf']['query'][$sql_key]['disp_direction'])) {
        $_SESSION['userconf']['query'][$sql_key]['disp_direction'] = $GLOBALS['cfg']['DefaultDisplay'];
    }

    if (PMA_isValid($_REQUEST['repeat_cells'], 'numeric')) {
        $_SESSION['userconf']['query'][$sql_key]['repeat_cells'] = $_REQUEST['repeat_cells'];
        unset($_REQUEST['repeat_cells']);
    } elseif (empty($_SESSION['userconf']['query'][$sql_key]['repeat_cells'])) {
        $_SESSION['userconf']['query'][$sql_key]['repeat_cells'] = $GLOBALS['cfg']['RepeatCells'];
    }

    if (PMA_isValid($_REQUEST['session_max_rows'], 'numeric') || $_REQUEST['session_max_rows'] == 'all') {
        $_SESSION['userconf']['query'][$sql_key]['max_rows'] = $_REQUEST['session_max_rows'];
        unset($_REQUEST['session_max_rows']);
    } elseif (empty($_SESSION['userconf']['query'][$sql_key]['max_rows'])) {
        $_SESSION['userconf']['query'][$sql_key]['max_rows'] = $GLOBALS['cfg']['MaxRows'];
    }

    if (PMA_isValid($_REQUEST['pos'], 'numeric')) {
        $_SESSION['userconf']['query'][$sql_key]['pos'] = $_REQUEST['pos'];
        unset($_REQUEST['pos']);
    } elseif (empty($_SESSION['userconf']['query'][$sql_key]['pos'])) {
        $_SESSION['userconf']['query'][$sql_key]['pos'] = 0;
    }

    if (PMA_isValid($_REQUEST['display_text'], array('P', 'F'))) {
        $_SESSION['userconf']['query'][$sql_key]['display_text'] = $_REQUEST['display_text'];
        unset($_REQUEST['display_text']);
    } elseif (empty($_SESSION['userconf']['query'][$sql_key]['display_text'])) {
        $_SESSION['userconf']['query'][$sql_key]['display_text'] = 'P';
    }

    if (PMA_isValid($_REQUEST['relational_display'], array('K', 'D'))) {
        $_SESSION['userconf']['query'][$sql_key]['relational_display'] = $_REQUEST['relational_display'];
        unset($_REQUEST['relational_display']);
    } elseif (empty($_SESSION['userconf']['query'][$sql_key]['relational_display'])) {
        $_SESSION['userconf']['query'][$sql_key]['relational_display'] = 'K';
    }

    if (isset($_REQUEST['display_binary'])) {
        $_SESSION['userconf']['query'][$sql_key]['display_binary'] = true;
        unset($_REQUEST['display_binary']);
    } elseif (isset($_REQUEST['display_options_form'])) {
        // we know that the checkbox was unchecked
        unset($_SESSION['userconf']['query'][$sql_key]['display_binary']);
    } else {
        // selected by default because some operations like OPTIMIZE TABLE
        // and all queries involving functions return "binary" contents,
        // according to low-level field flags
        $_SESSION['userconf']['query'][$sql_key]['display_binary'] = true;
    }

    if (isset($_REQUEST['display_blob'])) {
        $_SESSION['userconf']['query'][$sql_key]['display_blob'] = true;
        unset($_REQUEST['display_blob']);
    } elseif (isset($_REQUEST['display_options_form'])) {
        // we know that the checkbox was unchecked
        unset($_SESSION['userconf']['query'][$sql_key]['display_blob']);
    }

    if (isset($_REQUEST['hide_transformation'])) {
        $_SESSION['userconf']['query'][$sql_key]['hide_transformation'] = true;
        unset($_REQUEST['hide_transformation']);
    } elseif (isset($_REQUEST['display_options_form'])) {
        // we know that the checkbox was unchecked
        unset($_SESSION['userconf']['query'][$sql_key]['hide_transformation']);
    }

    // move current query to the last position, to be removed last
    // so only least executed query will be removed if maximum remembered queries
    // limit is reached
    $tmp = $_SESSION['userconf']['query'][$sql_key];
    unset($_SESSION['userconf']['query'][$sql_key]);
    $_SESSION['userconf']['query'][$sql_key] = $tmp;

    // do not exceed a maximum number of queries to remember
    if (count($_SESSION['userconf']['query']) > 10) {
        array_shift($_SESSION['userconf']['query']);
        //echo 'deleting one element ...';
    }

    // populate query configuration
    $_SESSION['userconf']['display_text'] = $_SESSION['userconf']['query'][$sql_key]['display_text'];
    $_SESSION['userconf']['relational_display'] = $_SESSION['userconf']['query'][$sql_key]['relational_display'];
    $_SESSION['userconf']['display_binary'] = isset($_SESSION['userconf']['query'][$sql_key]['display_binary']) ? true : false;
    $_SESSION['userconf']['display_blob'] = isset($_SESSION['userconf']['query'][$sql_key]['display_blob']) ? true : false;
    $_SESSION['userconf']['hide_transformation'] = isset($_SESSION['userconf']['query'][$sql_key]['hide_transformation']) ? true : false;
    $_SESSION['userconf']['pos'] = $_SESSION['userconf']['query'][$sql_key]['pos'];
    $_SESSION['userconf']['max_rows'] = $_SESSION['userconf']['query'][$sql_key]['max_rows'];
    $_SESSION['userconf']['repeat_cells'] = $_SESSION['userconf']['query'][$sql_key]['repeat_cells'];
    $_SESSION['userconf']['disp_direction'] = $_SESSION['userconf']['query'][$sql_key]['disp_direction'];

    /*
     * debugging
    echo '<pre>';
    var_dump($_SESSION['userconf']);
    echo '</pre>';
     */
}

/**
 * Displays a table of results returned by a SQL query.
 * This function is called by the "sql.php" script.
 *
 * @param   integer the link id associated to the query which results have
 *                  to be displayed
 * @param   array   the display mode
 * @param   array   the analyzed query
 *
 * @uses    $_SESSION['userconf']['pos']
 * @global  string   $db                the database name
 * @global  string   $table             the table name
 * @global  string   $goto              the URL to go back in case of errors
 * @global  string   $sql_query         the current SQL query
 * @global  integer  $num_rows          the total number of rows returned by the
 *                                      SQL query
 * @global  integer  $unlim_num_rows    the total number of rows returned by the
 *                                      SQL query without any programmatically
 *                                      appended "LIMIT" clause
 * @global  array    $fields_meta       the list of fields properties
 * @global  integer  $fields_cnt        the total number of fields returned by
 *                                      the SQL query
 * @global  array    $vertical_display  informations used with vertical display
 *                                      mode
 * @global  array    $highlight_columns column names to highlight
 * @global  array    $cfgRelation       the relation settings
 *
 * @access  private
 *
 * @see     PMA_showMessage(), PMA_setDisplayMode(),
 *          PMA_displayTableNavigation(), PMA_displayTableHeaders(),
 *          PMA_displayTableBody(), PMA_displayResultsOperations()
 */
function PMA_displayTable(&$dt_result, &$the_disp_mode, $analyzed_sql)
{
    global $db, $table, $goto;
    global $sql_query, $num_rows, $unlim_num_rows, $fields_meta, $fields_cnt;
    global $vertical_display, $highlight_columns;
    global $cfgRelation;
    global $showtable;

    // why was this called here? (already called from sql.php)
    //PMA_displayTable_checkConfigParams();

    /**
     * @todo move this to a central place
     * @todo for other future table types
     */
    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');

    if ($is_innodb
     && ! isset($analyzed_sql[0]['queryflags']['union'])
     && ! isset($analyzed_sql[0]['table_ref'][1]['table_name'])
     && (empty($analyzed_sql[0]['where_clause'])
      || $analyzed_sql[0]['where_clause'] == '1 ')) {
        // "j u s t   b r o w s i n g"
        $pre_count = '~';
        $after_count = PMA_showHint(PMA_sanitize($GLOBALS['strApproximateCount']), true);
    } else {
        $pre_count = '';
        $after_count = '';
    }

    // 1. ----- Prepares the work -----

    // 1.1 Gets the informations about which functionalities should be
    //     displayed
    $total      = '';
    $is_display = PMA_setDisplayMode($the_disp_mode, $total);

    // 1.2 Defines offsets for the next and previous pages
    if ($is_display['nav_bar'] == '1') {
        if ($_SESSION['userconf']['max_rows'] == 'all') {
            $pos_next     = 0;
            $pos_prev     = 0;
        } else {
            $pos_next     = $_SESSION['userconf']['pos'] + $_SESSION['userconf']['max_rows'];
            $pos_prev     = $_SESSION['userconf']['pos'] - $_SESSION['userconf']['max_rows'];
            if ($pos_prev < 0) {
                $pos_prev = 0;
            }
        }
    } // end if

    // 1.3 Find the sort expression

    // we need $sort_expression and $sort_expression_nodirection
    // even if there are many table references
    if (! empty($analyzed_sql[0]['order_by_clause'])) {
        $sort_expression = trim(str_replace('  ', ' ', $analyzed_sql[0]['order_by_clause']));
        /**
         * Get rid of ASC|DESC
         */
        preg_match('@(.*)([[:space:]]*(ASC|DESC))@si', $sort_expression, $matches);
        $sort_expression_nodirection = isset($matches[1]) ? trim($matches[1]) : $sort_expression;
        $sort_direction = isset($matches[2]) ? trim($matches[2]) : '';
        unset($matches);
    } else {
        $sort_expression = $sort_expression_nodirection = $sort_direction = '';
    }

    // 1.4 Prepares display of first and last value of the sorted column

    if (! empty($sort_expression_nodirection)) {
        list($sort_table, $sort_column) = explode('.', $sort_expression_nodirection);
        $sort_table = PMA_unQuote($sort_table);
        $sort_column = PMA_unQuote($sort_column);
        // find the sorted column index in row result
        // (this might be a multi-table query)
        $sorted_column_index = false;
        foreach($fields_meta as $key => $meta) {
            if ($meta->table == $sort_table && $meta->name == $sort_column) {
                $sorted_column_index = $key;
                break;
            }
        }
        if ($sorted_column_index !== false) {
            // fetch first row of the result set
            $row = PMA_DBI_fetch_row($dt_result);
            $column_for_first_row = $row[$sorted_column_index];
            // fetch last row of the result set
            PMA_DBI_data_seek($dt_result, $num_rows - 1);
            $row = PMA_DBI_fetch_row($dt_result);
            $column_for_last_row = $row[$sorted_column_index];
            // reset to first row for the loop in PMA_displayTableBody()
            PMA_DBI_data_seek($dt_result, 0);
            // we could also use here $sort_expression_nodirection
            $sorted_column_message = ' [' . htmlspecialchars($sort_column) . ': <strong>' . htmlspecialchars($column_for_first_row) . ' - ' . htmlspecialchars($column_for_last_row) . '</strong>]';
            unset($row, $column_for_first_row, $column_for_last_row);
        }
        unset($sorted_column_index, $sort_table, $sort_column);
    }

    // 2. ----- Displays the top of the page -----

    // 2.1 Displays a messages with position informations
    if ($is_display['nav_bar'] == '1' && isset($pos_next)) {
        if (isset($unlim_num_rows) && $unlim_num_rows != $total) {
            $selectstring = ', ' . $unlim_num_rows . ' ' . $GLOBALS['strSelectNumRows'];
        } else {
            $selectstring = '';
        }
        $last_shown_rec = ($_SESSION['userconf']['max_rows'] == 'all' || $pos_next > $total)
                        ? $total - 1
                        : $pos_next - 1;

        if (PMA_Table::isView($db, $table)
         && $total == $GLOBALS['cfg']['MaxExactCountViews']) {
            $message = PMA_Message::notice('strViewHasAtLeast');
            $message->addParam('[a@./Documentation.html#cfg_MaxExactCount@_blank]');
            $message->addParam('[/a]');
            $message_view_warning = PMA_showHint($message);
        } else {
            $message_view_warning = false;
        }

        $message = PMA_Message::success('strShowingRecords');
        $message->addMessage($_SESSION['userconf']['pos']);
        if ($message_view_warning) {
            $message->addMessage('...', ' - ');
            $message->addMessage($message_view_warning);
            $message->addMessage('(');
        } else {
            $message->addMessage($last_shown_rec, ' - ');
            $message->addMessage($pre_count  . PMA_formatNumber($total, 0) . $after_count, ' (');
            $message->addString('strTotal');
            $message->addMessage($selectstring, '');
            $message->addMessage(', ', '');
        }

        $messagge_qt = PMA_Message::notice('strQueryTime');
        $messagge_qt->addParam($GLOBALS['querytime']);

        $message->addMessage($messagge_qt, '');
        $message->addMessage(')', '');

        $message->addMessage(isset($sorted_column_message) ? $sorted_column_message : '', '');

        PMA_showMessage($message, $sql_query, 'success');

    } elseif (!isset($GLOBALS['printview']) || $GLOBALS['printview'] != '1') {
        PMA_showMessage($GLOBALS['strSuccess'], $sql_query, 'success');
    }

    // 2.3 Displays the navigation bars
    if (! strlen($table)) {
        if (isset($analyzed_sql[0]['query_type'])
           && $analyzed_sql[0]['query_type'] == 'SELECT') {
            // table does not always contain a real table name,
            // for example in MySQL 5.0.x, the query SHOW STATUS
            // returns STATUS as a table name
            $table = $fields_meta[0]->table;
        } else {
            $table = '';
        }
    }

    if ($is_display['nav_bar'] == '1') {
        PMA_displayTableNavigation($pos_next, $pos_prev, $sql_query);
        echo "\n";
    } elseif (!isset($GLOBALS['printview']) || $GLOBALS['printview'] != '1') {
        echo "\n" . '<br /><br />' . "\n";
    }

    // 2b ----- Get field references from Database -----
    // (see the 'relation' configuration variable)
    // loic1, 2002-03-02: extended to php3

    // initialize map
    $map = array();

    // find tables
    $target=array();
    if (isset($analyzed_sql[0]['table_ref']) && is_array($analyzed_sql[0]['table_ref'])) {
        foreach ($analyzed_sql[0]['table_ref'] AS $table_ref_position => $table_ref) {
           $target[] = $analyzed_sql[0]['table_ref'][$table_ref_position]['table_true_name'];
        }
    }
    $tabs    = '(\'' . join('\',\'', $target) . '\')';

    if ($cfgRelation['displaywork']) {
        if (! strlen($table)) {
            $exist_rel = false;
        } else {
            $exist_rel = PMA_getForeigners($db, $table, '', 'both');
            if ($exist_rel) {
                foreach ($exist_rel AS $master_field => $rel) {
                    $display_field = PMA_getDisplayField($rel['foreign_db'], $rel['foreign_table']);
                    $map[$master_field] = array($rel['foreign_table'],
                                          $rel['foreign_field'],
                                          $display_field,
                                          $rel['foreign_db']);
                } // end while
            } // end if
        } // end if
    } // end if
    // end 2b

    // 3. ----- Displays the results table -----
    PMA_displayTableHeaders($is_display, $fields_meta, $fields_cnt, $analyzed_sql, $sort_expression, $sort_expression_nodirection, $sort_direction);
    $url_query = '';
    echo '<tbody>' . "\n";
    PMA_displayTableBody($dt_result, $is_display, $map, $analyzed_sql);
    // vertical output case
    if ($_SESSION['userconf']['disp_direction'] == 'vertical') {
        PMA_displayVerticalTable();
    } // end if
    unset($vertical_display);
    echo '</tbody>' . "\n";
    ?>
</table>

    <?php
    // 4. ----- Displays the link for multi-fields delete

    if ($is_display['del_lnk'] == 'dr' && $is_display['del_lnk'] != 'kp') {

        $delete_text = $is_display['del_lnk'] == 'dr' ? $GLOBALS['strDelete'] : $GLOBALS['strKill'];

        $_url_params = array(
            'db'        => $db,
            'table'     => $table,
            'sql_query' => $sql_query,
            'goto'      => $goto,
        );
        $uncheckall_url = 'sql.php' . PMA_generate_common_url($_url_params);

        $_url_params['checkall'] = '1';
        $checkall_url = 'sql.php' . PMA_generate_common_url($_url_params);

        if ($_SESSION['userconf']['disp_direction'] == 'vertical') {
            $checkall_params['onclick'] = 'if (setCheckboxes(\'rowsDeleteForm\', true)) return false;';
            $uncheckall_params['onclick'] = 'if (setCheckboxes(\'rowsDeleteForm\', false)) return false;';
        } else {
            $checkall_params['onclick'] = 'if (markAllRows(\'rowsDeleteForm\')) return false;';
            $uncheckall_params['onclick'] = 'if (unMarkAllRows(\'rowsDeleteForm\')) return false;';
        }
        $checkall_link = PMA_linkOrButton($checkall_url, $GLOBALS['strCheckAll'], $checkall_params, false);
        $uncheckall_link = PMA_linkOrButton($uncheckall_url, $GLOBALS['strUncheckAll'], $uncheckall_params, false);
        if ($_SESSION['userconf']['disp_direction'] != 'vertical') {
            echo '<img class="selectallarrow" width="38" height="22"'
                .' src="' . $GLOBALS['pmaThemeImage'] . 'arrow_' . $GLOBALS['text_dir'] . '.png' . '"'
                .' alt="' . $GLOBALS['strWithChecked'] . '" />';
        }
        echo $checkall_link . "\n"
            .' / ' . "\n"
            .$uncheckall_link . "\n"
            .'<i>' . $GLOBALS['strWithChecked'] . '</i>' . "\n";

        PMA_buttonOrImage('submit_mult', 'mult_submit',
            'submit_mult_change', $GLOBALS['strChange'], 'b_edit.png');
        PMA_buttonOrImage('submit_mult', 'mult_submit',
            'submit_mult_delete', $delete_text, 'b_drop.png');
        if ($analyzed_sql[0]['querytype'] == 'SELECT') {
            PMA_buttonOrImage('submit_mult', 'mult_submit',
                'submit_mult_export', $GLOBALS['strExport'],
                'b_tblexport.png');
        }
        echo "\n";

        echo '<input type="hidden" name="sql_query"'
            .' value="' . htmlspecialchars($sql_query) . '" />' . "\n";
        echo '<input type="hidden" name="url_query"'
            .' value="' . $GLOBALS['url_query'] . '" />' . "\n";
        echo '</form>' . "\n";
    }

    // 5. ----- Displays the navigation bar at the bottom if required -----

    if ($is_display['nav_bar'] == '1') {
        echo '<br />' . "\n";
        PMA_displayTableNavigation($pos_next, $pos_prev, $sql_query);
    } elseif (!isset($GLOBALS['printview']) || $GLOBALS['printview'] != '1') {
        echo "\n" . '<br /><br />' . "\n";
    }

    // 6. ----- Displays "Query results operations"
    if (!isset($GLOBALS['printview']) || $GLOBALS['printview'] != '1') {
        PMA_displayResultsOperations($the_disp_mode, $analyzed_sql);
    }
} // end of the 'PMA_displayTable()' function

function default_function($buffer) {
    $buffer = htmlspecialchars($buffer);
    $buffer = str_replace("\011", ' &nbsp;&nbsp;&nbsp;',
        str_replace('  ', ' &nbsp;', $buffer));
    $buffer = preg_replace("@((\015\012)|(\015)|(\012))@", '<br />', $buffer);

    return $buffer;
}

/**
 * Displays operations that are available on results.
 *
 * @param   array   the display mode
 * @param   array   the analyzed query
 *
 * @uses    $_SESSION['userconf']['pos']
 * @uses    $_SESSION['userconf']['display_text']
 * @global  string   $db                the database name
 * @global  string   $table             the table name
 * @global  string   $sql_query         the current SQL query
 * @global  integer  $unlim_num_rows    the total number of rows returned by the
 *                                      SQL query without any programmatically
 *                                      appended "LIMIT" clause
 *
 * @access  private
 *
 * @see     PMA_showMessage(), PMA_setDisplayMode(),
 *          PMA_displayTableNavigation(), PMA_displayTableHeaders(),
 *          PMA_displayTableBody(), PMA_displayResultsOperations()
 */
function PMA_displayResultsOperations($the_disp_mode, $analyzed_sql) {
    global $db, $table, $sql_query, $unlim_num_rows;

    $header_shown = FALSE;
    $header = '<fieldset><legend>' . $GLOBALS['strQueryResultsOperations'] . '</legend>';

    if ($the_disp_mode[6] == '1' || $the_disp_mode[9] == '1') {
        // Displays "printable view" link if required
        if ($the_disp_mode[9] == '1') {

            if (!$header_shown) {
                echo $header;
                $header_shown = TRUE;
            }

            $_url_params = array(
                'db'        => $db,
                'table'     => $table,
                'printview' => '1',
                'sql_query' => $sql_query,
            );
            $url_query = PMA_generate_common_url($_url_params);

            echo PMA_linkOrButton(
                'sql.php' . $url_query,
                PMA_getIcon('b_print.png', $GLOBALS['strPrintView'], false, true),
                '', true, true, 'print_view') . "\n";

            if ($_SESSION['userconf']['display_text']) {
                $_url_params['display_text'] = 'F';
                echo PMA_linkOrButton(
                    'sql.php' . PMA_generate_common_url($_url_params),
                    PMA_getIcon('b_print.png', $GLOBALS['strPrintViewFull'], false, true),
                    '', true, true, 'print_view') . "\n";
                unset($_url_params['display_text']);
            }
        } // end displays "printable view"
    }

    // Export link
    // (the url_query has extra parameters that won't be used to export)
    // (the single_table parameter is used in display_export.lib.php
    //  to hide the SQL and the structure export dialogs)
    // If the parser found a PROCEDURE clause
    // (most probably PROCEDURE ANALYSE()) it makes no sense to
    // display the Export link).
    if (isset($analyzed_sql[0]) && $analyzed_sql[0]['querytype'] == 'SELECT' && !isset($printview) && ! isset($analyzed_sql[0]['queryflags']['procedure'])) {
        if (isset($analyzed_sql[0]['table_ref'][0]['table_true_name']) && !isset($analyzed_sql[0]['table_ref'][1]['table_true_name'])) {
            $_url_params['single_table'] = 'true';
        }
        if (!$header_shown) {
            echo $header;
            $header_shown = TRUE;
        }
        $_url_params['unlim_num_rows'] = $unlim_num_rows;
        echo PMA_linkOrButton(
            'tbl_export.php' . PMA_generate_common_url($_url_params),
            PMA_getIcon('b_tblexport.png', $GLOBALS['strExport'], false, true),
            '', true, true, '') . "\n";
    }

    // CREATE VIEW
    /**
     *
     * @todo detect privileges to create a view
     *       (but see 2006-01-19 note in display_create_table.lib.php,
     *        I think we cannot detect db-specific privileges reliably)
     * Note: we don't display a Create view link if we found a PROCEDURE clause
     */
    if (!$header_shown) {
        echo $header;
        $header_shown = TRUE;
    }
    if (! isset($analyzed_sql[0]['queryflags']['procedure'])) {
        echo PMA_linkOrButton(
            'view_create.php' . $url_query,
            PMA_getIcon('b_views.png', 'CREATE VIEW', false, true),
            '', true, true, '') . "\n";
    }
    if ($header_shown) {
        echo '</fieldset><br />';
    }
}

/**
 * Verifies what to do with non-printable contents (binary or BLOB)
 * in Browse mode.
 *
 * @uses    is_null()
 * @uses    isset()
 * @uses    strlen()
 * @uses    PMA_formatByteDown()
 * @uses    strpos()
 * @uses    str_replace()
 * @param   string  $category BLOB|BINARY
 * @param   string  $content  the binary content
 * @param   string  $transform_function
 * @param   string  $transform_options
 * @param   string  $default_function
 * @param   object  $meta   the meta-information about this field
 * @return  mixed  string or float
 */
function PMA_handle_non_printable_contents($category, $content, $transform_function, $transform_options, $default_function, $meta) {
    $result = '[' . $category;
    if (is_null($content)) {
        $result .= ' - NULL';
        $size = 0;
    } elseif (isset($content)) {
        $size = strlen($content);
        $display_size = PMA_formatByteDown($size, 3, 1);
        $result .= ' - '. $display_size[0] . $display_size[1];
    }
    $result .= ']';

    if (strpos($transform_function, 'octetstream')) {
        $result = $content;
    }
    if ($size > 0) {
        if ($default_function != $transform_function) {
            $result = $transform_function($result, $transform_options, $meta);
        } else {
            $result = $default_function($result, array(), $meta);
            if (stristr($meta->type, 'BLOB') && $_SESSION['userconf']['display_blob']) {
                // in this case, restart from the original $content
                $result = PMA_replace_binary_contents($content);
            }
        }
    }
    return($result);
}

/**
 * Prepares the displayable content of a data cell in Browse mode,
 * taking into account foreign key description field and transformations
 *
 * @uses    is_array()
 * @uses    PMA_backquote()
 * @uses    PMA_DBI_try_query()
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_fetch_row()
 * @uses    $GLOBALS['strLinkNotFound']
 * @uses    PMA_DBI_free_result()
 * @uses    $GLOBALS['printview']
 * @uses    htmlspecialchars()
 * @uses    PMA_generate_common_url()
 * @param   string  $mouse_events
 * @param   string  $class
 * @param   string  $condition_field
 * @param   string  $analyzed_sql
 * @param   object  $meta   the meta-information about this field
 * @param   string  $map
 * @param   string  $data
 * @param   string  $transform_function
 * @param   string  $default_function
 * @param   string  $nowrap
 * @param   string  $where_comparison
 * @return  string  formatted data
 */
function PMA_prepare_row_data($mouse_events, $class, $condition_field, $analyzed_sql, $meta, $map, $data, $transform_function, $default_function, $nowrap, $where_comparison, $transform_options) {

    // continue the <td> tag started before calling this function:
    $result = $mouse_events . ' class="' . $class . ($condition_field ? ' condition' : '') . $nowrap . '">';

    if (isset($analyzed_sql[0]['select_expr']) && is_array($analyzed_sql[0]['select_expr'])) {
        foreach ($analyzed_sql[0]['select_expr'] AS $select_expr_position => $select_expr) {
            $alias = $analyzed_sql[0]['select_expr'][$select_expr_position]['alias'];
            if (isset($alias) && strlen($alias)) {
                $true_column = $analyzed_sql[0]['select_expr'][$select_expr_position]['column'];
                if ($alias == $meta->name) {
                    // this change in the parameter does not matter
                    // outside of the function
                    $meta->name = $true_column;
                } // end if
            } // end if
        } // end foreach
    } // end if

    if (isset($map[$meta->name])) {
        // Field to display from the foreign table?
        if (isset($map[$meta->name][2]) && strlen($map[$meta->name][2])) {
            $dispsql     = 'SELECT ' . PMA_backquote($map[$meta->name][2])
                . ' FROM ' . PMA_backquote($map[$meta->name][3])
                . '.' . PMA_backquote($map[$meta->name][0])
                . ' WHERE ' . PMA_backquote($map[$meta->name][1])
                . $where_comparison;
            $dispresult  = PMA_DBI_try_query($dispsql, null, PMA_DBI_QUERY_STORE);
            if ($dispresult && PMA_DBI_num_rows($dispresult) > 0) {
                list($dispval) = PMA_DBI_fetch_row($dispresult, 0);
            } else {
                $dispval = $GLOBALS['strLinkNotFound'];
            }
            @PMA_DBI_free_result($dispresult);
        } else {
            $dispval     = '';
        } // end if... else...

        if (isset($GLOBALS['printview']) && $GLOBALS['printview'] == '1') {
            $result .= ($transform_function != $default_function ? $transform_function($data, $transform_options, $meta) : $transform_function($data, array(), $meta)) . ' <code>[-&gt;' . $dispval . ']</code>';
        } else {

            if ('K' == $_SESSION['userconf']['relational_display']) {
                // user chose "relational key" in the display options, so
                // the title contains the display field
                $title = (! empty($dispval))? ' title="' . htmlspecialchars($dispval) . '"' : '';
            } else {
                $title = ' title="' . htmlspecialchars($data) . '"';
            }

            $_url_params = array(
                'db'    => $map[$meta->name][3],
                'table' => $map[$meta->name][0],
                'pos'   => '0',
                'sql_query' => 'SELECT * FROM '
                                    . PMA_backquote($map[$meta->name][3]) . '.' . PMA_backquote($map[$meta->name][0])
                                    . ' WHERE ' . PMA_backquote($map[$meta->name][1])
                                    . $where_comparison,
            );
            $result .= '<a href="sql.php' . PMA_generate_common_url($_url_params)
                 . '"' . $title . '>';

            if ($transform_function != $default_function) {
                // always apply a transformation on the real data,
                // not on the display field
                $result .= $transform_function($data, $transform_options, $meta);
            } else {
                if ('D' == $_SESSION['userconf']['relational_display']) {
                    // user chose "relational display field" in the
                    // display options, so show display field in the cell
                    $result .= $transform_function($dispval, array(), $meta);
                } else {
                    // otherwise display data in the cell
                    $result .= $transform_function($data, array(), $meta);
                }
            }
            $result .= '</a>';
        }
    } else {
        $result .= ($transform_function != $default_function ? $transform_function($data, $transform_options, $meta) : $transform_function($data, array(), $meta));
    }
    $result .= '</td>' . "\n";

    return $result;
}
?>
