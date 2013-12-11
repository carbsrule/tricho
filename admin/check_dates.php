<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';

test_setup_login (true, SETUP_ACCESS_FULL);

$_GET['t'] = '__tools';
require 'head.php';
?>

<div id="main_data">

<h2>Dates checker</h2>

<?php
$_GET['section'] = 'err';
require_once 'tools_tabs.php';
?>

<p>These are all of the date columns in your database, their details and any errors with the details of the columns</p>


<table class="bordered horizonal">
<tr>
    <th>Table</th>
    <th>Column</th>
    <th>SQL type</th>
    <th>From</th>
    <th>To</th>
    <th>Errors</th>
</tr>

<?php
$tables = $db->getOrderedTables ();
$altrow = 1;
foreach ($tables as $table) {
    
    // iterate through the columns and get all the links
    $date_cols = array ();
    $columns = $table->getColumns ();
    foreach ($columns as $column) {
        if ($column->getSqlType () == SQL_TYPE_DATE
                or $column->getSqlType () == SQL_TYPE_DATETIME
                or $column->getSqlType () == SQL_TYPE_TIME) {
            $date_cols[] = $column;
        }
    }
    
    // if there are links, show all the links for this table
    if (count ($date_cols) > 0) {
        foreach ($date_cols as $column) {
            // determine errors
            $errors = array();
            if ($column->getSqlType () != SQL_TYPE_TIME) {
                $val = $column->getMinYear();
                if ($val == '') $errors[] = 'no min year set';
                if ($val[0] == '+') $errors[] = 'min year must not be an offset';
                
                $val = $column->getMaxYear();
                if ($val == '') $errors[] = 'no max year set';
            }
            
            // work out class for this column
            $class = 'altrow'. $altrow;
            if ($altrow == 1) {
                $altrow = 2;
            } else {
                $altrow = 1;
            }
            if (count ($errors) > 0) $class .= '_error';
            
            // show column
            echo "<tr class=\"{$class}\">\n";
            echo "<td>{$table->getName ()}</td>\n";
            echo "<td>{$column->getName ()}</td>\n";
            
            // SQL type
            echo '<td>'. sql_type_string_from_defined_constant ($column->getSqlType ()). "</td>\n";
            
            // date range
            if ($column->getSqlType() != SQL_TYPE_TIME) {
                echo '<td>', $column->getMinYear(), '</td><td>', 
                    $column->getMaxYear(), "</td>\n";
            } else {
                echo "<td colspan=\"2\"><i>n/a</i></td>\n";
            }
            
            // errors
            if (count ($errors) > 0) {
                $errors = ucfirst (implode_and (', ', $errors));
                echo "<td>{$errors}</td>\n";
            } else {
                echo "<td>&nbsp;</td>\n";
            }
            
            echo "</tr>\n";
        }
    }
}
?>
</table>


</div>

<?php
require 'foot.php';
?>
