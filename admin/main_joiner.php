<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

// make sure this doesn't get called by a URL or some other method
if ($caller != 'main.php') die ();

// get the table on the other side of the join
// e.g. if current table is UserPrefs and parent is Users, get Prefs
$link_col = $table->getLinkToTable ($parent_table);
$joiner_col = $table->getJoinerColumn ($parent_table);
$view_items = $table->getViewColumns('edit');


// online help
$help_table = $db->getHelpTable ();
$help_columns = array ();
if ($help_table != null) {
    $q = "SELECT HelpColumn
        FROM `{$help_table->getName()}`
        WHERE HelpTable = ". sql_enclose ($_GET['t']);
    if ($_SESSION['setup']['view_q']) echo "<pre>Help Q: {$q}</pre>";
    $res = execq($q);
    while ($row = fetch_assoc($res)) {
        $help_columns[] = $row['HelpColumn'];
    }
}


$button_text = $table->getAltButtons ();
if ($button_text['edit'] == '') $button_text['edit'] = 'Save';
if ($button_text['cancel'] == '') $button_text['cancel'] = 'Cancel';

// echo "Buttons: <pre>", print_r ($button_text, true), "</pre>\n";

if ($link_col != null and $joiner_col != null) {
    
    // include all columns that aren't joiners
    $regular_columns = array ();
    $table_columns = $table->getColumns ();
    foreach ($table_columns as $col) {
        if ($col !== $link_col and $col !== $joiner_col) {
            $regular_columns['`'. $col->getName (). '`'] = $col->getName ();
        }
    }
    
    $extant_options = array ();
    $extra_columns = array ();
    $q = "SELECT `{$joiner_col->getName ()}`";
    if (count($regular_columns) > 0) {
        $q .= ', '. implode (', ', array_keys ($regular_columns));
    }
    
    if (preg_match ('/^[0-9]+$/', $parent_id)) {
        $escape_literal = false;
    } else {
        $escape_literal = true;
    }
    $parent_literal = new QueryFieldLiteral ($parent_id, $escape_literal);
    
    $q .= "\nFROM `". $table->getName (). "`".
        "\nWHERE `". $link_col->getName (). "` = ". sql_enclose ($parent_literal);
    
    if ($_SESSION['setup']['view_q']) {
        echo "<small>", htmlspecialchars ($q), "<br>\n</small>\n";
    }
    $res = execq($q);

    // columns
    for ($i = 0; $i < $res->columnCount(); ++$i) {
        
        // WARNINGS from PHP manual:
        // 1) This function is EXPERIMENTAL. The behaviour of this function,
        // its name, and surrounding documentation may change without notice
        // in a future release of PHP. This function should be used at your
        // own risk.
        // 2) Not all PDO drivers support PDOStatement::getColumnMeta()
        $field = $res->getColumnMeta($i);
        
        foreach ($view_items as $item) {
            $col = $item->getColumn ();
            if ($col->getName () == $field->name) {
                if (($col->getOption == '') and ($col !== $joiner_col) and ($col !== $link_col)) {
                    $extra_columns[] = $col;
                }
            }
        }
    }
    // extant options
    while ($row = fetch_assoc($res)) {
        $extant_option = array ();
        foreach ($row as $row_name => $row_data) {
            if ($row_name != $joiner_col->getName ()) {
                $extant_option[$row_name] = $row_data;
            }
        }
        $extant_options[$row[$joiner_col->getName ()]] = $extant_option;
    }
    
    
    $q = cast_to_string ($joiner_col->getChooserQuery ());
     
    
    // do the rows
    if ($_SESSION['setup']['view_q']) echo "<small>", htmlspecialchars ($q), "<br>\n</small>\n";
    $res = execq($q);
    if ($res->rowCount() > 0) {
        
        echo "<form method=\"post\" name=\"main_form\" action=\"main_joiner_action.php\" enctype=\"multipart/form-data\">\n";
        
        echo "<input type=\"hidden\" name=\"_joiner\" value=\"", htmlspecialchars ($table->getName ()), "\">\n";
        
        echo "<input type=\"hidden\" name=\"_p\" value=\"", htmlspecialchars ($_GET['p']), "\">\n";
        
        // echo "<input type=\"hidden\" name=\"_id\" value=\"", htmlspecialchars ($parent_id), "\">\n";
        
        if ($caller = strrchr ($_SERVER['PHP_SELF'], '/')) {
            $caller = substr ($caller, 1);
        } else {
            $caller = $_SERVER['PHP_SELF'];
        }
        if ($_SERVER['QUERY_STRING']) {
            $caller .= '?'. $_SERVER['QUERY_STRING'];
        }
        echo "<input type=\"hidden\" name=\"_caller\" value=\"", htmlspecialchars ($caller), "\">\n";
        
        // headings
        echo "<table class=\"form-table\">\n";
        echo '<tr><th>&nbsp;</th><th>';
        
        if ($_SESSION['setup']['view_c']) {
            echo "<div class=\"column_name\">", $joiner_col->getName (), "</div>\n";
        }
        
        echo $joiner_col->getEngName ();
        if ($_SESSION['setup']['view_h'] and $db->getHelpTable () != null) {
                echo " <a href=\"help_edit.php?t={$_GET['t']}&c={$joiner_col->getName ()}\" class=\"help\">[help]</a>";
        } else if (in_array ($joiner_col->getName (), $help_columns)) {
            echo " <a href=\"help.php?t={$_GET['t']}&c={$joiner_col->getName ()}\" target=\"_blank\" onclick=\"return popup_a(this);\" class=\"help\">[?]</a>";
        }
        echo '</th>';
        foreach ($view_items as $item) {
            $column = $item->getColumn ();
            if (!in_array ($column, $extra_columns, true)) continue;
            echo '<th>';
            if ($_SESSION['setup']['view_c']) {
                echo "<div class=\"column_name\">", $column->getName (), "</div>\n";
            }
            echo $column->getEngName ();
            if ($_SESSION['setup']['view_h'] and $db->getHelpTable () != null) {
                echo " <a href=\"help_edit.php?t={$_GET['t']}&c={$column->getName ()}\" class=\"help\">[help]</a>";
            } else if (in_array ($column->getName (), $help_columns)) {
                echo " <a href=\"help.php?t={$_GET['t']}&c={$column->getName ()}\" target=\"_blank\" onclick=\"return popup_a(this);\" class=\"help\">[?]</a>";
            }
            echo '</th>';
        }
        echo "</tr>\n";
        $total_num_cols = count ($extra_columns) + 2;
        
        // load row errors if they exist
        if (isset ($_SESSION['err_ext'])) {
            $all_row_errors = $_SESSION['err_ext']['row_errors'];
            $all_forgotten_rows = $_SESSION['err_ext']['forgotten_rows'];
            unset ($_SESSION['err_ext']);
        }
        
        // checkboxes
        $link_col_counter = 0;
        $alt = 1;
        
        $field_name = $joiner_col->getName ();
        $field_name = str_replace (' ', '_', $field_name);
        
        while ($row = fetch_assoc($res)) {
            // report an error if there is one
            $error = $all_row_errors[$row['pri_key']];
            if (isset ($error)) {
                echo "<tr class=\"altrow{$alt}\"><td colspan=\"{$total_num_cols}\">";
                echo "<p class=\"joiner-error\"><strong>Error:</strong> {$error}</p>";
                echo "</td></tr>";
                
                if (isset ($all_forgotten_rows[$row['pri_key']])) {
                    $extant_options[$row['pri_key']] = $all_forgotten_rows[$row['pri_key']];
                }
            }
            
            // master checkbox
            echo "<tr class=\"altrow{$alt}\"><td class=\"checkbox\"><input type=\"checkbox\" id=\"link_opt_{$link_col_counter}\" name=\"",
                $field_name, "[]\" value=\"{$row['pri_key']}\"";
            if (in_array ($row['pri_key'], array_keys ($extant_options))) {
                echo ' checked';
            }
            echo "></td><td><label for=\"link_opt_{$link_col_counter}\">{$row['val']}</label></td>";
            
            // Work out what the primary key is
            $primary_key = array ();
            $pk_names = $table->getPKnames ();
            foreach ($pk_names as $name) {
                if ($name == $joiner_col->getName ()) {
                    $primary_key[] = $row['pri_key'];
                } else if ($name == $link_col->getName ()) {
                    $primary_key[] = $parent_id;
                } else {
                    $primary_key[] = $extant_options[$row['pri_key']][$name];
                }
            }
            
            // extra columns
            foreach ($view_items as $item) {
                $column = $item->getColumn ();
                if (in_array ($column, $extra_columns, true)) {
                    if ($item->getEditable ()) {
                        
                        // TODO: temporarily change column name name to give different input name
                        $sub_col_name = $column->getName ();
                        $sub_col_name = str_replace (' ', '_', $sub_col_name);
                        
                        $field_params = array ();
                        $field_params['name'] = $sub_col_name . '_' . $row['pri_key'];
                        $field_params['change_event'] = "check_if_used('link_opt_{$link_col_counter}', this);";
                        echo '<td>'. $column->getInputField ($extant_options[$row['pri_key']][$column->getName ()], $primary_key, false, $field_params). '</td>';
                        
                    } else {
                        echo '<td>'. $extant_options[$row['pri_key']][$column->getName ()]. '</td>';
                    }
                }
            }
            
            echo "</tr>\n";
            $link_col_counter++;
            
            if ($alt == 1) {
                $alt = 2;
            } else {
                $alt = 1;
            }
        }
        
        echo "<tr><td colspan=\"{$total_num_cols}\" align=\"right\">";
        echo "<input type=\"hidden\" name=\"_do\" value=\"Edit\">";
        echo "<input type=\"button\" value=\"{$button_text['cancel']}\" onclick=\"main_form.elements['_do'].value='Cancel'; main_form.submit();\"> ";
        echo "<input type=\"submit\" value=\"{$button_text['edit']}\"></td></tr>\n";

        echo "</table>\n";
        echo "</form>\n";
    }
} else {
    report_error ("Invalid table configuration");
}

?>
