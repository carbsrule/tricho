<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../tricho.php';
test_admin_login ();
$db = Database::parseXML ('tables.xml');
$table = $db->getTable ($_GET['t']); // use table name
force_redirect_to_alt_page_if_exists($table, 'edit');

if (!$table->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

require 'head.php';
require_once 'main_functions.php';

tricho\Runtime::load_help_text ($table);

// Get the view items for this col
$view_items = $table->getView('edit');
$form = new Form();
$form->setType('edit');
$form->setTable($table);

// Work out urls and buttons
list ($urls, $seps) = $table->getPageUrls ();
$button_text = $table->getAltButtons ();
if (@$button_text['edit'] == '') $button_text['edit'] = 'Save';
if (@$button_text['cancel'] == '') $button_text['cancel'] = 'Cancel';

// URL stuff
$hidden_url = substr ($_SERVER['PHP_SELF'], strrpos ($_SERVER['PHP_SELF'], '/') + 1);
if ($_SERVER['QUERY_STRING'] != '') {
    $hidden_url .= '?'. $_SERVER['QUERY_STRING'];
}

// Get the primary key
$primary_key_cols = $table->getIndex ('PRIMARY KEY');
$primary_key_values = explode (',', $_GET['id']);
$prim_key_clauses = array ();

if (count($primary_key_cols) == count($primary_key_values)) {
    reset($primary_key_cols);
    reset($primary_key_values);
    while (list($col_id, $col) = each($primary_key_cols)) {
        list($val_id, $val) = each($primary_key_values);
        
        $old_val = 0;
        try {
            $val = $col->collateInput($val, $old_val);
            $val = reset($val);
        } catch (DataValidationException $ex) {
            report_error('Invalid key provided');
            require 'foot.php';
            exit(1);
        }
        
        $prim_key_clauses[] = '`'. $table->getName (). "`.`{$col->getName ()}` = " . sql_enclose ($val);
        $primary_keys[$col->getName ()] = $val;
    }
} else {
    report_error ("Number of primary key values does not match number of primary key columns");
}

// build an identifier
$identifier = $table->buildIdentifier ($primary_keys);
unset ($primary_keys);

$pk_clause = implode (' AND ', $prim_key_clauses);


// include JS editor stuff if there are any columns that require it, and check for file fields
$file_uploads_required = false;
$tinymce_fields = array ();
foreach ($view_items as $item) {
    if (!($item instanceof ColumnViewItem)) continue;
    $col = $item->getColumn();
    if ($col instanceof TinymceColumn) {
        $tinymce_fields[] = $col;
    } else if ($col instanceof FileColumn) {
        $file_uploads_required = true;
    }
}

// Richtext editor stuff
$has_a_richtext_editor = false;
if (count($tinymce_fields) > 0) {
    $has_a_richtext_editor = true;
?>
<script type="text/javascript">
<!--
<?php
init_tinymce($tinymce_fields);
?>
//-->
</script>
<noscript><p><b>Javascript must be enabled to use this form.</b></p></noscript>
<?php
}



// start the useful output
echo "<div id=\"main_data\">\n";

// tabs
$parents = array();
$parent_table = null;
if (trim(@$_GET['p']) != '') {
    $parents = explode (',', $_GET['p']);
    if (count ($parents) > 0) {
        list ($parent_table) = explode ('.', $parents[0]);
    }
}

if ($db->getShowPrimaryHeadings ()) {
    if (count($parents) > 0) {
        
        list ($ancestor_name) = explode ('.', $parents[count($parents) - 1]);
        $ancestor_table = $db->getTable ($ancestor_name);
        
        echo "<h2>{$ancestor_table->getEngName ()}</h2>";
    } else {
        echo "<h2>{$table->getEngName ()}</h2>";
    }
}

show_parent_siblings ($table, $parents);
show_children ($table, $identifier);

// heading
if ($db->getShowSectionHeadings()) {
    $act = 'Editing';
    if (! $table->getAllowed ('edit')) $act = 'Viewing';
    
    $lc = strtolower ($table->getNameSingle());
    if ($identifier != '') $identifier = ': ' . $identifier;
    if (count($parents) > 0 or $db->getShowPrimaryHeadings ()) {
        echo "<h3>{$act} {$lc}{$identifier}</h3>\n";
    } else {
        echo "<h2>{$act} {$lc}{$identifier}</h2>\n";
    }
}

// comments
if ($parent_table != null) {
    $filename = 'advice/'. strtolower ($_GET['t']). '.'. strtolower ($parent_table). '.edit.php';
    if (file_exists($filename)) {
        @include $filename;
    } else {
        @include 'advice/' . strtolower($_GET['t']) . '.edit.php';
    }
} else {
    @include "advice/" . strtolower($_GET['t']) . '.edit.php';
}

// online help
$help_table = $db->getHelpTable ();
$help_columns = array ();
if ($help_table != null) {
    $q = "SELECT HelpColumn, QuickHelp, HelpText
        FROM `{$help_table->getName ()}`
        WHERE HelpTable = '{$_GET['t']}'";
    if (@$_SESSION['setup']['view_q']) echo "<pre>Help Q: {$q}</pre>";
    $res = execq($q);
    while ($row = fetch_assoc($res)) {
        $help_columns[$row['HelpColumn']] = array (
            'QuickHelp' => trim ($row['QuickHelp']),
            'HelpText' => (trim ($row['HelpText']) != ''? true: false)
        );
    }
}

// determine what functions are going to be called, and build a MySQL string of all of them
$functions = array ();
$function_id = 0;
$function_sql = '';
foreach ($view_items as $item) {
    if ($item instanceof FunctionViewItem) {
        $functions[] = $item->getCode (). ' AS func'. ($function_id++);
    }
}
if ($function_id > 0) {
    $function_sql = ', ' . implode (', ', $functions);
}

$hidden_fields = array ();

// the query for loading the data
$q = "SELECT `{$_GET['t']}`.*{$function_sql} FROM `{$_GET['t']}` WHERE {$pk_clause}";

// show the query
if (@$_SESSION['setup']['view_q'] === true) {
    echo "Q: {$q}<br>\n";
}

$res = execq($q);

if (@$res->rowCount() == 1) {
    
    check_session_response (ADMIN_KEY);
    
    // layer session data over row data
    $row = fetch_assoc($res);
    
    if (isset ($_SESSION[ADMIN_KEY]['edit'][$table->getName ().'.'.$_GET['id']])) {
        foreach ($_SESSION[ADMIN_KEY]['edit'][$table->getName ().'.'.$_GET['id']] as $key => $value) {
            $row[$key] = $value;
        }
    }
    
    if ($table->getAllowed ('edit')) {
        echo "<form name=\"main_form\" method=\"post\" action=\"{$urls['edit_action']}\"";
        if ($has_a_richtext_editor) echo ' onsubmit="return submitForm();"';
        if ($file_uploads_required) echo ' enctype="multipart/form-data"';
        echo ">\n";
        
        echo "<input type=\"hidden\" name=\"_t\" value=\"{$_GET['t']}\">\n";
        if (@$_GET['p'] != '') {
            echo "<input type=\"hidden\" name=\"_p\" value=\"", htmlspecialchars ($_GET['p']), "\">\n";
        }
    }
    
    // determine PK values for ancestors that were used for drill-down
    $parents = explode(',', @$_GET['p']);
    $parent_vals = array ();
    foreach ($parents as $parent) {
        @list($parent_col, $parent_val) = explode('.', $parent);
        $parent_vals[$parent_col] = $parent_val;
    }
    
    echo "<table class=\"main_add_edit\">\n";
    echo "<col class=\"col_eng_name\" id=\"col_eng_name_{$table->getName ()}\">\n";
    echo "<col class=\"col_value\" id=\"col_value_{$table->getName ()}\">\n";
    echo "<tbody>\n";
    
    $found_first_field = false;
    $tree_parent_id = -1;
    $function_id = 0;
    $include_id = 0;
    $heading_id = 0;
    foreach ($view_items as $item) {
        if ($item instanceof HeadingViewItem) {
            // Heading
            $heading_id++;
            echo "<tr id=\"heading{$heading_id}\" class=\"heading\"><td colspan=\"2\"><h4>{$item->getName ()}</h4></td></tr>";
            
            
        } else if ($item instanceof FunctionViewItem) {
            // Function
            $function_name = 'func'. ($function_id++);
            echo "<tr id=\"func{$function_id}\" class=\"function\">";
            echo "<td><span>{$item->getName ()}</span></td>";
            echo "<td>{$row[$function_name]}</td>";
            echo "</tr>\n";
            
            
        } else if ($item instanceof IncludeViewItem) {
            // Include
            echo "<tr id=\"include{$include_id}\" class=\"include\">";
            echo "<td><span>{$item->getName ()}</span></td>";
            
            if (file_exists ($item->getfilename ())) {
                echo '<td>';
                $current_view = 'add';
                $passthrough = $item->getPassthroughValue ();
                include $item->getfilename();
                
            } else {
                echo '<td class="error">Error: Invalid include file name: file "'. $item->getfilename (). '" does not exist';
            }
            echo "</td></tr>";
            $include_id++;
            
            
        } else if ($item instanceof ColumnViewItem) {
            // Column
            $col = $item->getColumn ();
            
            if ($col->getOption () == 'ordernum') continue;
            
            // START New functionality
            $value = $row[$col->getName ()];
            if (method_exists ($col, 'getMultiInputs')) {
                $input_rows = $col->getMultiInputs ($form, $value, $primary_key_values);
            } else {
                if ($item->getEditable()) {
                    $field = $col->getInputField($form, $value, $primary_key_values);
                } else {
                    $field = $col->displayValue($value);
                }
                $input_rows = array (array (
                    'label' => $col->getInputLabel (),
                    'field' => $field,
                    'suffix' => ''
                ));
            }
            
            foreach ($input_rows as $input_row) {
                $row_id = "row_{$col->getPostSafeName ()}";
                if ($input_row['suffix'] != '') $row_id .= "_{$input_row['suffix']}";
                echo "<tr id=\"{$row_id}\" class=\"column\">\n";
                echo "    <td>{$input_row['label']}</td>\n";
                echo "    <td>{$input_row['field']}</td>\n";
                echo "</tr>\n";
            }
            // END New functionality
            continue;
            
            
            // auto-fill in columns based on the parents
            if ($table->getDisableParentEdit ()) {
                if (isset($parent_vals)) {
                    $link_data = $col->getLink ();
                    if ($link_data != null and $link_data->isParent()) {
                        $val = $parent_vals[$link_data->getToColumn ()->getTable ()->getName ()];
                        if ($val != null) {
                            continue;
                        }
                    }
                }
            }
            
            // don't show anything if top-level node in a tree which has top-level nodes disabled
            if ($table->getDisplayStyle () == TABLE_DISPLAY_STYLE_TREE and !$table->getTopNodesEnabled ()) {
                $link_data = $col->getLink ();
                if ($link_data != null and $link_data->getToColumn ()->getTable () === $table) {
                    $tree_parent_id = $row[$col->getName ()];
                    if ($row[$col->getName ()] == 0) {
                        $hidden_fields[$col->getName ()] = '0';
                        continue;
                    }
                }
            }
            
        } else {
            echo '<tr><td class="error" colspan="2">Error: Invalid view item type '. get_class ($item). '</td></tr>';
        }
    }
    
    if ($table->getAllowed ('edit')) {
        echo "<tr class=\"buttons\"><td>&nbsp;</td><td>";
        echo "<input type=\"submit\" value=\"{$button_text['edit']}\"> ";
        echo "<input type=\"submit\" value=\"{$button_text['cancel']}\" name=\"cancel\">";
        echo "</td></tr>\n";
    }
    
    echo "</tbody>\n";
    echo "</table>\n";
    
    if ($table->getAllowed ('edit')) {
        foreach ($hidden_fields as $field_name => $data) {
            echo "<input type=\"hidden\" name=\"{$field_name}\" value=\"",
                htmlspecialchars ($data), "\">\n";
        }
        echo "<input type=\"hidden\" name=\"_id\" value=\"{$_GET['id']}\"></form>\n";
    }
    
} else {
    check_session_response (ADMIN_KEY);
    report_error ('Invalid key provided');
}

echo "</div>\n";
require "foot.php";
?>
