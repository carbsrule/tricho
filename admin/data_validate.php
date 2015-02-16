<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_setup_login (true, SETUP_ACCESS_FULL);

// Allow 2 minutes in case there are a lot of data
set_time_limit (120);

$_GET['t'] = '__tools';
require 'head.php';
    
echo "<div id=\"main_data\">\n";
if ($db->getShowSectionHeadings ()) {
    echo "<h2>Data Validator</h2>";
}

$_GET['section'] = 'err';
require_once 'tools_tabs.php';


//
// DETERMINE WHAT'S WRONG
//
$output = array ();
$tables = $db->getTables ();
foreach ($tables as $table) {
    
    $columns = $table->getColumns ();
    $output_table = array ();
    $link_values = array ();
    
    // determine link values
    foreach ($columns as $column) {
        if ($column instanceof LinkColumn) {
            $target = $column->getTarget();
            $column_name = $target->getName();
            $table_name = $target->getTable()->getName();
            
            if (isset($link_values[$table_name . '.' . $column_name])) continue;
            
            $q = "SELECT `{$column_name}` FROM `{$table_name}`";
            if ($_SESSION['setup']['view_q']) echo "<pre>[val] q: {$q}</pre>";
            $res = execq($q);
            $values = array ();
            while ($row = fetch_assoc($res)) {
                $values[] = $row[$column_name];
            }
            $link_values[$table_name . '.' . $column_name] = $values;
        }
    }
    
    $q = "SELECT * FROM `{$table->getName ()}`";
    if (@$_SESSION['setup']['view_q']) {
        echo "<pre>[data_validate] q: {$q}</pre>";
    }
    $res = execq($q);
    while ($row = fetch_assoc($res)) {
        
        $output_row = array ();
        
        // check the columns of this table
        foreach ($columns as $column) {
            
            // check mandatory of column
            $cur_value = $row[$column->getName ()];
            if ($cur_value == '') {
                if ($column->isMandatory ()) {
                    $output_row[] = 'No value for mandatory column ' . $column->getName ();
                }
                continue;
            }
            
            // if a password field is non-empty, there's nothing more that can
            // be checked
            if ($column instanceof PasswordColumn) continue;
            
            //TODO: implement checking of file and image columns
            if ($column instanceof FileColumn) continue;
            
            if ($column instanceof LinkColumn) {
                $target = $column->getTarget();
                
                $column_name = $target->getName();
                $table_name = $target->getTable()->getName();
                
                // if it's not mandatory, allow null or 0
                if (!$column->isMandatory() and $cur_value == 0) continue;
                
                $valid_vals = $link_values[$table_name . '.' . $column_name];
                if (!in_array($cur_value, $valid_vals)) {
                    $output_row[] = 'Invalid link: From ' . $table->getName() .
                        '.' . $column->getName() . ' (value: "' .
                        $cur_value . '") to ' . $table_name . '.' .
                        $column_name . '; value not found';
                }
            } else {
                $junk = '';
                try {
                    $column->collateInput($cur_value, $junk);
                } catch (Exception $ex) {
                    if ($cur_value === null) {
                        $output_value = '<em>null</em>';
                    } else if (is_numeric($cur_value)) {
                        $output_value = $cur_value;
                    } else {
                        $output_value = "\"{$cur_value}\"";
                    }
                    $output_row[] = 'Invalid ' . get_class($column) .
                        ' value  in column ' . $column->getName () . ': ' . $output_value . '; ' . $ex->getMessage();
                }
            }
        }
        
        // store row errors if there have been any column errors
        if (count ($output_row) > 0) {
            $row_data = build_row_data ($table, $row);
            $row_data['err'] = $output_row;
            $output_table[] = $row_data;
        }
    }
    
    // store table errors if there have been any row errors
    if (count ($output_table) > 0) {
        $id = $table->getName ();
        $output[$id] = $output_table;
    }
    
}



//
// DISPLAY ALL ERRORS
//
echo '<div class="validator">';
foreach ($output as $table => $rows) {
    
    // Determine number of errors
    $num_errors = 0;
    foreach ($rows as $row_data) {
        $num_errors += count ($row_data['err']);
    }
    if ($num_errors == 1) {
        $num_errors = '1 error';
    } else {
        $num_errors .= ' errors';
    }
    
    // show table
    echo "<h3>Table \"{$table}\": {$num_errors}</h3>";
    foreach ($rows as $row_data) {
        echo "<p><a href=\"{$row_data['edit_link']}\" class=\"border-hover\"><strong>{$row_data['id']}</strong>";
        foreach ($row_data['err'] as $error) {
            echo '<br>' . $error;
        }
        echo '</a></p>';
    }
}
echo '</div>';

// id="main_data"
echo "</div>\n";

/**
 * Builds an identifier for a specific row of a table
 * If a row identifier has been defined, it uses that, otherwise it uses the primary key values
 * Also returns the edit link for this table
 *
 * @param Table $table The table that the row belongs to
 * @param array $row All the row data for the row that is being outputted
 * @return array The identifier and the edit link in the format [ 'id' => identifier, 'edit_link' => the edit link ]
 */
function build_row_data ($table, $row) {
    $pks = $table->getPKnames ();
    
    $primary_key = array ();
    foreach ($pks as $pk_name) {
        $primary_key[$pk_name] = $row[$pk_name];
    }
    
    $id = $table->buildIdentifier ($primary_key);

    if ($id == '') {
        foreach ($primary_key as $name => $value) {
            $id .= "{$name} = '{$value}' ";
        }
    }
    
    $edit_link = "edit.php?t={$table->getName()}&id=" . implode(',', $primary_key);

    return array ('id' => $id, 'edit_link' => $edit_link);
}

require "foot.php";
?>
