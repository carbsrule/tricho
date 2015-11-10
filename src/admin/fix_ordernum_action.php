<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
    
test_setup_login (true, SETUP_ACCESS_LIMITED);

$_GET['t'] = '__tools';
require 'head.php';
    
echo "<div id='main_data'>\n";
echo "<h2>Fix Table Ordering</h2>\n";

$_GET['section'] = 'err';
require_once 'tools_tabs.php';

// loop through em
//
$order_fixes = array ();
foreach ($tables as $table) {
    
    $columns = $table->getColumns ();
    $table_name = $table->getName ();
    
    // skip tables that aren't checked
    $check_table_name = str_replace (' ', '_', $table_name);
    if ($_POST[$check_table_name] != '1') {
        continue;
    }
    
    // look for an ordernum col
    $ordernum_col = null;
    foreach ($columns as $idx => $column) {
        if ($column->getOption() == 'ordernum') {
            $ordernum_col = $column;
            break;
        }
    }
    
    
    // do something with our ordernum col
    if (isset($ordernum_col)) {
        $order_cols = $table->getOrder('view');
        if (count ($order_cols) == 0) continue;
        
        
        // build query
        //
        $first = true;
        $q = "SELECT * FROM `{$table_name}` ORDER BY ";
        foreach ($order_cols as $order_col) {
            if ($first) {
                $first = false;
            } else {
                $q .= ', ';
            }
            
            $q .= '`'. $order_col[0]->getName (). '` '. $order_col[1];
        }
        
        
        
        // process query
        //
        $res = execq($q);
        $expected_ordernum = 1;
        $last_data = null;
        $log_qs = array ();
        while ($row = fetch_assoc($res)) {
            $ordernum_name = $ordernum_col->getName ();
            $record_ordernum = $row[$ordernum_name];
            
            // create an array of current data
            $curr_data = array();
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
                
                // where clause
                $pk_data = $table->getPKvalues ($row);
                $where_clauses = array();
                foreach ($pk_data as $name => $value) {
                    $pk_field = $table->get ($name);
                    
                    if ($pk_field != null and $pk_field->isNumeric ()) {
                        $where_clauses[] = "`{$name}` = {$value}";
                    } else {
                        $where_clauses[] = "`{$name}` = ". sql_enclose ($value, false);
                    }
                }
                $where = implode (' AND ', $where_clauses);
                
                $q = "UPDATE `{$table_name}` SET `{$ordernum_name}` = {$expected_ordernum} WHERE {$where} LIMIT 1";
                execq($q);
                
                if ($table->isStatic ()) $log_qs[] = $q;
                
                $fixed++;
            }
            $expected_ordernum++;
            
            // update last_data
            $last_data = $curr_data;
        }
        if ($table->isStatic() and count($log_qs) > 0) {
            $log_msg = "Fixed order numbering in static table {$table_name}";
            log_action($log_msg, implode(";\n", $log_qs));
        }
        
        // there were some order errors
        if ($fixed > 0) {
            $order_fixes[$table_name] = $fixed;
        }
    }
}


if (count ($order_fixes) == 0) {
    echo "<p>Nothing was fixed</p>";
    
} else {
    echo "<p>The following tables were fixed:</p>";
    echo "<table class='form-table'>\n";
    echo "<tr><th>Table Name</th><th>Number of Fixes</th></tr>\n";
    $alt = 1;
    foreach ($order_fixes as $table => $number) {
        echo "<tr class='altrow{$alt}'><td>{$table}</td><td>{$number}</td></tr>\n";
        if ($alt == 1) {
            $alt = 2;
        } else {
            $alt = 1;
        }
    }
    echo "</table>\n";
}

require "foot.php";
?>