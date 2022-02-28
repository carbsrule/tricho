<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\DataUi\MainTable;
use Tricho\Meta\Database;
use Tricho\Query\LogicConditionNode;
use Tricho\Query\QueryFieldLiteral;

require '../tricho.php';
test_admin_login ();

$db = Database::parseXML();
$table = $db->getTable ($_GET['t']); // use table name
alt_page_redir($table, 'browse');

if (!$table->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

require 'head.php';
require 'main_functions.php';

$per_page = 15;
$start = (int) @$_GET['start'];

$new_per_page = (int) @$_GET['per'];
if ($new_per_page > 0) $per_page = $new_per_page;

$table = $db->getTable ($_GET['t']); // use table name


if (isset($_SESSION[ADMIN_KEY]['num_per_page'][$table->getName ()])) {
    $num_per_page = $_SESSION[ADMIN_KEY]['num_per_page'][$table->getName ()];
} else {
    $num_per_page = RECORDS_PER_PAGE;
}


if ($table == null) {
    echo "<div id=\"main_data\">\n";
    report_error ("Table does not exist: {$_GET['t']}");
    echo "</div>\n";
    require "foot.php";
    unset ($_SESSION[ADMIN_KEY]['err']);
    unset ($_SESSION[ADMIN_KEY]['msg']);
    die ();
}

?>

<div id="main_data">
<?php

// get the parent tables and show the tabs
$ancestors = array();
$parent_table = null;
if (trim(@$_GET['p']) != '') {
    $ancestors = explode(',', $_GET['p']);

    $parent_name = false;
    $parent_id = null;
    $parent_num = 0;
    foreach ($ancestors as $ancestor) {
        list ($ancestor_name, $ancestor_pk) = explode ('.', $ancestor);

        $ancestor_table = $db->getTable ($ancestor_name);
        if ($ancestor_table == null) {
            report_error ("Invalid ancestor {$ancestor_name}");
            die ();
        }

        if ($parent_num++ == 0) {
            $parent_id = $ancestor_pk;
            $parent_name = $ancestor_name;
            $parent_table = $ancestor_table;
        }
    }

    if ($db->getShowPrimaryHeadings ()) {
        if (count($ancestors) > 0) {
            echo "<h2>{$ancestor_table->getEngName ()}</h2>";
        } else {
            echo "<h2>{$table->getEngName ()}</h2>";
        }
    }

    show_parent_siblings ($table, $ancestors);
}

// heading
if ($db->getShowSectionHeadings ()) {
    // If this is a sub-table, get the alternate english name if there is one
    $heading_text = $table->getEngName ();
    if (count($ancestors) > 0) {
        $col = $table->getLinkToTable ($parent_table);
        $alt_name = $col->getAltEngName();
        if ($alt_name) $heading_text = $alt_name;
    }

    if ($db->getShowPrimaryHeadings () and count($ancestors) > 0) {
        echo "<h3>{$heading_text}</h3>";
    } else {
        echo "<h2>{$heading_text}</h2>";
    }
}

// err, warn, msg
check_session_response (ADMIN_KEY);



##
##    Handle joiner table view
if ($table->isJoiner () and $parent_id != null) {

    // comments
    if ($parent_table != null) {
        $filename = 'advice/' . strtolower ($_GET['t']) . '.' . strtolower ($parent_name) . '.edit.php';
        @include $filename;
    }

    // shove this off into a separate file for now
    $caller = 'main.php';
    require_once 'main_joiner.php';



##
##    Handle rows table view
} else if ($table->getDisplayStyle () == TABLE_DISPLAY_STYLE_ROWS) {

    // comments
    $advice_file = 'advice/'. strtolower ($_GET['t']). '.main.php';

    if ($parent_table != null) {
        $filename = 'advice/'. strtolower ($_GET['t']). '.'. strtolower ($parent_name). '.main.php';
        if (file_exists($filename)) {
            require $filename;
        } else if (file_exists($advice_file)) {
            require $advice_file;
        }
    } else if (file_exists($advice_file)) {
        require $advice_file;
    }

    // import our columns
    $main = new MainTable ($table);

    // add the ancestors to the filter hate list
    foreach ($ancestors as $ancestor) {
        list($ancestor) = explode('.', $ancestor);
        $main->addFilterSkipTable($ancestor);
    }

    // determine if there are any records at all in this table
    // this currently does the whole table. one day we may narrow it to only this tab if we are on a tab
    $q = "SELECT Count(*) as Count FROM `{$_GET['t']}`";
    $res = execq($q);
    $row = fetch_assoc($res);
    if ($row['Count'] < 3 and @count($_SESSION[ADMIN_KEY]['search_params']) == 0) {
        $main->clearSearchCols ();
    }

    // parent table support
    if (count($ancestors) > 0) {
        // changed by benno: the where clause doesn't need to reference the linked table, only the base
        $query = $main->getSelectQuery ();
        $link_col = $table->getLinkToTable ($parent_table);

        // Use integer value where possible for parent joins
        if (preg_match ('/^-?[0-9]+$/', $parent_id)) {
            $escape_literal = false;
        } else {
            $escape_literal = true;
        }
        $parent_literal = new QueryFieldLiteral ($parent_id, $escape_literal);

        // condition for parent join
        $cond = new LogicConditionNode (
            $link_col,
            LOGIC_CONDITION_EQ,
            $parent_literal
        );

        // modify the query handler
        $where = $query->getWhere ();
        $where->addCondition ($cond, LOGIC_TREE_AND);
    }

    // apply the filters
    if (@count($_SESSION[ADMIN_KEY]['search_params'][$table->getName ()]) > 0) {
        $main->applyFilters ($_SESSION[ADMIN_KEY]['search_params'][$table->getName ()]);
    }

    // show the table to the user
    echo $main->getHtml (null, $num_per_page, $table->showSearch ());


##
##    Handle tree table view
} else if ($table->getDisplayStyle () == TABLE_DISPLAY_STYLE_TREE) {

    // comments
    if ($parent_table != null) {
        $filename = 'advice/'. strtolower ($_GET['t']). '.' . strtolower ($parent_name). '.main.php';
        if (file_exists ($filename)) {
            @include $filename;
        } else {
            @include "advice/" . strtolower($_GET['t']) . ".main.php";
        }
    } else {
        @include "advice/" . strtolower($_GET['t']) . ".main.php";
    }

    // show partition if it exists
    $partition = $table->getPartition ();
    if ($partition != null) {

        // if the partition field is one of the parents, don't show it.
        if (count($ancestors) > 0) {
            $link = $partition->getLink ();
            $part_to_table = $link->getToColumn ()->getTable ()->getName ();
            foreach ($ancestors as $ancestor) {
                list ($ancestor_name, $ancestor_pk) = explode ('.', $ancestor);
                if ($ancestor_name == $part_to_table) {
                    $partition = null;
                    break;
                }
            }
        }

        // show the partition field
        if ($partition != null) {
            echo "<div id=\"filter\">
                <form method=\"post\" action=\"main_partition_action.php\">
                <input type=\"hidden\" name=\"_t\" value=\"". $table->getName (). "\">
                <h2>Filter</h2>
                <p>Choose area: ";
                $field = $partition->getInputField ($_SESSION[ADMIN_KEY]['partition'][$table->getName ()], null);
                $field = str_replace ('<br>', '', $field);
                echo $field;
                echo " <input type=\"submit\" value=\"View\"></p>
                </form>
            </div>\n";
        }
    }

    list ($urls, $seps) = $table->getPageUrls ();

    echo "<form method=\"post\" name=\"rows_", $table->getName (), "\" action=\"{$urls['main_action']}\">\n";
    echo "<input type=\"hidden\" name=\"_t\" value=\"", $table->getName (), "\">\n";

    if ($_GET['p'] != '') {
        echo "<input type=\"hidden\" name=\"_p\" value=\"", htmlspecialchars ($_GET['p']), "\">\n";
    }

    $button_text = $table->getAltButtons ();
    if ($button_text['main_add'] == '') {
        $button_text['main_add'] = 'Add new '. strtolower ($table->getNameSingle ());
    }
    if ($button_text['main_delete'] == '') $button_text['main_delete'] = 'Delete selected';
    if ($table->getAllowed ('add')) {

        echo "<p><input type=\"button\" name=\"add\" value=\"{$button_text['main_add']}\"",
            " onclick=\"window.location = '{$urls['main_add']}{$seps['main_add']}t=",
            urlencode ($table->getName ());

        if ($_GET['p'] != '') {
            echo "&p={$_GET['p']}";
        }

        echo "';\"></p>\n";
    }
    // show me the tree
    // SQL needs to get title (first viewable col), ordernum (if exists), primary key
    $cols = array ();
    $pk_names = $table->getPKnames ();
    // can only use a single primary key field
    $pk_name = $pk_names[0];
    $cols[] = '`' . $pk_name . '`';
    $view_col = '';
    $view_cols = $table->getViewColumns('list');
    foreach ($view_cols as $item) {
        $col = $item->getColumn ();
        if (!in_array ($col->getName (), $cols)) {
            $cols[] = '`'. $col->getName (). '`';
            $view_col = $col->getName ();
            break;
        }
    }
    // get first field that is to be displayed in main
    $has_ordernum = false;
    $order_list = array ();
    $order_list_cols = $table->getOrder ('view');
    $order_list_item_num = 1;
    $order_list_item_total = count ($order_list_cols);
    foreach ($order_list_cols as $id => $col_data) {
        $order_list[] = '`' . $col_data[0]->getName (). '` '. $col_data[1];
        if ($order_list_item_num == $order_list_item_total
                and $col_data[0]->getOption () == 'ordernum') {
            $has_ordernum = true;
        }
        $order_list_item_num++;
    }

    // get first field that links back to this table
    $link_col = '';
    $columns = $table->getColumns ();
    foreach ($columns as $col) {
        $link_data = $col->getLink();
        if ($link_data != null and $link_data->getToColumn()->getTable() === $table) {
            $link_col = $col->getName();
            $cols[] = '`'. $link_col. '`';
            break;
        }
    }

    $q = "SELECT ". implode (', ', $cols). " FROM `". $table->getName (). '`';

    if ($partition !== null) {
        $partn_link = $partition->getLink ();
        $partn_col = $partn_link->getToColumn ();
        if ($partn_col != null) {
            if ($_SESSION[ADMIN_KEY]['partition'][$table->getName ()] == '') {
                // get first item from linked column of partition
                $partn_q = "SELECT ". $partn_col->getName (). " FROM ". $partn_col->getTable ()->getName ();
                $order_cols = $partn_col->getTable ()-> getOrder ('view');
                $order_count = 0;
                foreach ($order_cols as $order_col) {
                    if ($order_count == 0) {
                        $partn_q .= "\nORDER BY";
                    } else {
                        $partn_q .= ', ';
                    }
                    $partn_q .= ' `'. $order_col[0]->getName (). '` '. $order_col[1];
                }
                $partn_q .= "\nLIMIT 1";
                $partn_res = execq($partn_q);
                if ($partn_row = fetch_row($partn_res)) {
                    $_SESSION[ADMIN_KEY]['partition'][$table->getName ()] = $partn_row[0];
                }
            }
        }
    }

    if (($_SESSION[ADMIN_KEY]['partition'][$table->getName ()] != '') and isset ($partition)) {
        $q .= "\nWHERE `". $table->getName (). "`.`". $partition->getName (). '` = '.
            sql_enclose ($_SESSION[ADMIN_KEY]['partition'][$table->getName ()]);
        $q .= " OR `". $table->getName (). "`.`{$link_col}` != 0";
        $has_where = true;
    }

    // parent table support
    // this is the non-SelectQuery version of a function above
    if (count($ancestors) > 0) {
        if ($has_where) {
            $q .= ' AND ';
        } else {
            $q .= ' WHERE ';
        }

        $link_column = $table->getLinkToTable ($parent_table);


        // Use integer value where possible for parent joins
        if (!preg_match ('/^[0-9]+$/', $parent_id)) {
            $parent_id = sql_enclose($parent_id);
        }

        $q .= '`'. $_GET['t']. '`.`'. $link_column->getName (). '` = '. $parent_id;
    }

    if (count($order_list) > 0) {
        $q .= "\nORDER BY ". implode (', ', $order_list);
    }
    if ($_SESSION['setup']['view_q']) echo "Q: {$q}<br>\n";

    echo "<div id=\"tree_{$table->getName ()}\" class=\"tree_display\"></div>\n\n";

    $res = execq($q);
    if ($res->rowCount() > 0) {

        echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
        echo "var tree_display = new treeDisplay ('{$table->getName ()}');\n";
        if ($has_ordernum) {
            echo "tree_display.orderable = true;\n";
        }
        echo "var db_table = '". $table->getName (). "';\n";
        echo "var up_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_UP . "';\n";
        echo "var down_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_DOWN . "';\n";
        echo "var plus_image = '" . ROOT_PATH_WEB . IMAGE_TREE_PLUS . "';\n";
        echo "var minus_image = '" . ROOT_PATH_WEB . IMAGE_TREE_MINUS . "';\n";

        $nodes = array ();
        $remnant_nodes = array ();
        $root_num = 0;
        $element_num = 0;

        $max_chars = $table->getTreeNodeChars ();

        while ($row = fetch_assoc($res)) {

            // apply node name truncation if specified
            if ($max_chars > 0 and strlen ($row[$view_col]) > $max_chars) {
                 $row[$view_col] = substr ($row[$view_col], 0, $max_chars - 3). '...';
            }

            echo "node{$element_num} = new treeNode (tree_display, '", addslashes ($row[$pk_name]), "', '",
                addslashes ($row[$view_col]), "'";
            if ($row[$link_col] == 0 and !$table->getTopNodesEnabled ()) {
                echo ', true';
            }
            echo ");\n";
            $nodes[$row[$pk_name]] = $element_num;
            if ($row[$link_col] == 0) {
                echo 'tree_display.roots[', $root_num++, "] = node{$element_num};\n";
            } else {
                if (cast_to_string ($nodes[$row[$link_col]]) != '') {
                    echo "node{$nodes[$row[$link_col]]}.addChild (node{$element_num});\n";
                } else {
                    $remnant_nodes[] = $row;
                }
            }
            $element_num++;
        }
        $nodes_added_this_pass = count($nodes);
        while (count($remnant_nodes) > 0 and $nodes_added_this_pass > 0) {
            $nodes_added_this_pass = 0;
            foreach ($remnant_nodes as $id => $row) {
                if (cast_to_string ($nodes[$row[$link_col]]) != '') {
                    echo "node{$nodes[$row[$link_col]]}.addChild (node{$nodes[$row[$pk_name]]});\n";
                    unset ($remnant_nodes[$id]);
                    $nodes_added_this_pass++;
                }
            }
        }

        // echo "updateTreeDisplay ('select', 'document.forms.test.id');\n";
        if ($_GET['_open'] != '' and cast_to_string ($nodes[$_GET['_open']]) != '') {
            echo "node{$nodes[$_GET['_open']]}.makeOpen ();\n";
        }
        echo "tree_display.display ('url', '{$urls['main_edit']}{$seps['main_edit']}t=", $table->getName ();
        if ($_GET['p'] != '') {
            echo "&p={$_GET['p']}";
        }
        echo "&id=');\n";
        echo "</script>\n";

        if ($table->getAllowed ('del')) {
            if ($table->getConfirmDel ()) {
                echo "<input type=\"hidden\" name=\"rem\" value=\"\">\n";
                echo "<input type=\"button\" value=\"{$button_text['main_delete']}\" onclick=\"confirmDelete('",
                    $table->getName (), "','", addslashes ($button_text['delete_alert']), "');\">\n";
            } else {
                echo "<input type=\"submit\" name=\"rem\" value=\"{$button_text['main_delete']}\">\n";
            }
        }
    } else {
        echo "<p>No ", strtolower ($table->getEngName ()), " available</p>\n";
    }

    echo "</form>\n";
}
?>

</div>
<?php
require "foot.php";
?>
