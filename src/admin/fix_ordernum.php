<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\IntColumn;
use Tricho\Meta\NumericColumn;

require_once '../tricho.php';
    
test_setup_login (true, SETUP_ACCESS_LIMITED);


$_GET['t'] = '__tools';
require 'head.php';
    
echo "<div id='main_data'>\n";
if ($db->getShowSectionHeadings ()) {
    echo "<h2>Fix Table Ordering</h2>";
}

$_GET['section'] = 'err';
require_once 'tools_tabs.php';

if ($_SESSION['setup']['level'] == SETUP_ACCESS_FULL) {
    echo '<p>It is recommended that you run the <a href="check_ordernums.php">',
        "OrderNum column checking tool</a> before running this tool.</p>\n";
}

// if a table is specified, only use it rather than all tables
if (isset ($_GET['table'])) {
    $table = $db->getTable ($_GET['table']);
    if ($table != null) {
        $tables = array ($table);
    }
}

// loop through the tables
//
$order_errors = array ();
$table_order_cols = array ();
foreach ($tables as $table) {
    $columns = $table->getColumns ();
    $table_name = $table->getName ();
    
    
    // look for an ordernum col
    $ordernum_col = null;
    foreach ($columns as $idx => $column) {
        if ($column->getOption () == 'ordernum') {
            $ordernum_col = $column;
            break;
        }
    }
    
    // check for ordering errors if the table has an ordernum col
    if (isset ($ordernum_col)) {
        $order_cols = $table->getOrder ('view');
        $table_order_errors = array ();
        $view_cols = $table->getViewColumns('list');
        
        if (count ($order_cols) == 0) continue;
        
        
        // build query
        //
        $j = 0;
        $q = 'SELECT ';
        foreach ($order_cols as $order_col) {
            if ($j++ > 0) $q .= ', ';
            
            $q .= '`'. $order_col[0]->getName () .'`';
        }
        
        foreach ($view_cols as $view_col) {
            $view_col = $view_col->getColumn ();
            $q .= ', `'. $view_col->getName (). '`';
        }
        
        $j = 0;
        $q .= " FROM `{$table_name}` ORDER BY ";
        foreach ($order_cols as $order_col) {
            if ($j++ > 0) $q .= ', ';
            
            $q .= '`'. $order_col[0]->getName (). '` '. $order_col[1];
        }
        
        
        // process query
        //
        $res = execq($q);
        $expected_ordernum = 1;
        $last_data = null;
        while ($row = fetch_assoc($res)) {
            $record_ordernum = $row[$ordernum_col->getName ()];

            // create an array of current data
            $curr_data = array ();
            foreach ($order_cols as $order_col) {
                if ($order_col[0] !== $ordernum_col) {
                    $curr_data[$order_col[0]->getName ()] = $row[$order_col[0]->getName ()];
                }
            }
            
            // look for a change in last_data
            if ($last_data != null) {
                if ($last_data != $curr_data) {
                    $expected_ordernum = 1;
                }
            }
            
            // check ordernum is good
            if ($record_ordernum != $expected_ordernum) {
                $record = array ();
                $record = $row;
                $record['expected'] = $expected_ordernum;
                $record['actual'] = $record_ordernum;
                $table_order_errors[] = $record;
            }
            $expected_ordernum++;
            
            // update last_data
            $last_data = $curr_data;
        }
        
        
        // there were some order errors
        if (count ($table_order_errors) > 0) {
            $order_errors[$table_name] = $table_order_errors;
            $table_order_cols[$table_name] = $ordernum_col;
        }
    }
}


// Report status
//
if (count ($order_errors) == 0) {
    echo "<p>There are no errors!</p>";
    
} else {
    echo "<p>The following errors were found:</p>";
    
    echo "<form action='fix_ordernum_action.php' method='post'>";
    
    $exp_col = new IntColumn('expected');
    $act_col = new IntColumn('actual');
    
    // display errors for each table
    foreach ($order_errors as $table_name => $table_order_errors) {
        echo "<h3>{$table_name}</h3>";
        $table = $db->getTable ($table_name);
        $columns = $table->getColumns ();
        
        
        // headings
        echo "<table class='form-table'>\n<tr>";
        $view_columns = array ();
        
        // first visible col
        foreach ($view_cols as $view_col) {
            $col = $view_col->getColumn ();
            if (colIsOrderer ($table, $col)) continue;
            
            echo "<th>{$col->getEngName()}</th>";
            $view_columns[] = $col;
        }
        
        $order_cols = $table->getOrder ('view');
        foreach ($order_cols as $col) {
            if ($col[0]->getOption() == 'ordernum') continue;
            
            echo "<th>{$col[0]->getEngName ()} <small style=\"font-weight: normal;\">({$col[1]})</small></th>";
            $view_columns[] = $col[0];
        }
        
        echo "<th>Actual<br>OrderNum</th><th>Expected<br>OrderNum</th></tr>\n";
        
        $view_columns[] = $act_col;
        $view_columns[] = $exp_col;
        
        // data
        $alt = 1;
        foreach ($table_order_errors as $error) {
            echo "<tr class='altrow{$alt}'>";
            
            foreach ($view_columns as $col) {
                if ($col instanceof NumericColumn) {
                    echo "<td align=\"right\">{$error[$col->getName ()]}</td>";
                } else {
                    echo "<td>{$error[$col->getName ()]}</td>";
                }
            }
            echo "</tr>\n";
            
            if ($alt == 1) {
                $alt = 2;
            } else {
                $alt = 1;
            }
        }
        echo "</table>";
        
        $table_name = str_replace (' ', '_', $table_name);
        echo "<input type='checkbox' name='{$table_name}' value='1' id='{$table_name}'><label for='{$table_name}'>Fix this table</label>";
    }
    
    echo "<p><input type='submit' value='Fix OrderNumbers'></p>";
    echo "</form>";
}



function colIsOrderer ($table, $column) {
    $order_cols = $table->getOrder ('view');
    foreach ($order_cols as $col) {
        if ($col[0] === $column) {
            return true;
        }
    }
    
    return false;
}


require "foot.php";
?>