<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../tricho.php';
test_admin_login ();
require 'main_functions.php';

$db = Database::parseXML ('tables.xml');
$table = $db->getTable ($_GET['t']); // use table name
force_redirect_to_alt_page_if_exists ($table, 'main_add');

if (!$table->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

list ($urls, $seps) = $table->getPageUrls ();

require 'head.php';

tricho\Runtime::load_help_text($table);

// get the view items
$view_items = $table->getView('add');
$form = new Form();
$form->setType('add');
$form->setTable($table);

// alt button text
$button_text = $table->getAltButtons ();
if (@$button_text['add'] == '') $button_text['add'] = 'Add';
if (@$button_text['cancel'] == '') $button_text['cancel'] = 'Cancel';

// include JS editor stuff if there are any columns that require it, and check for file fields
$file_uploads_required = false;
$tinymce_fields = array ();
foreach ($view_items as $item) {
    if ($item instanceof ColumnViewItem) {
        $col = $item->getColumn ();
        if ($col->getOption () == 'richtext') {
            $tinymce_fields[] = $col;
        }
        if ($col instanceOf FileColumn) {
            $file_uploads_required = true;
        }
    }
}

// Richtext editor stuff
$has_a_richtext_editor = false;
if (count($tinymce_fields) > 0) {
    $has_a_richtext_editor = true;
?>
<script language="JavaScript" type="text/javascript">
<!--
<?php
init_tinymce ($tinymce_fields);
?>
//-->
</script>
<noscript><p><b>Javascript must be enabled to use this form.</b></p></noscript>
<?php
}

// main form
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

show_parent_siblings($table, $parents);

if ($db->getShowSectionHeadings()) {
    if (count($parents) > 0 or $db->getShowPrimaryHeadings ()) {
        echo "<h3>Adding new {$table->getNameSingle ()}</h3>";
    } else {
        echo "<h2>Adding new {$table->getNameSingle ()}</h2>";
    }
}

// comments
if ($parent_table != null) {
    $filename = 'advice/' . strtolower ($_GET['t']) . '.' . strtolower ($parent_table) . '.add.php';
    if (file_exists ($filename)) {
        @include $filename;
    } else {
        @include 'advice/' . strtolower($_GET['t']) . '.add.php';
    }
} else {
    @include 'advice/' . strtolower($_GET['t']) . '.add.php';
}

// online help
$help_table = $db->getHelpTable ();
$help_columns = array ();
if ($help_table != null) {
    $q = "SELECT HelpColumn, QuickHelp, HelpText
        FROM `{$help_table->getName()}` WHERE HelpTable = '{$_GET['t']}'";
    if (@$_SESSION['setup']['view_q']) echo "<pre>Help Q: {$q}</pre>";
    $res = execq($q);
    while ($row = fetch_assoc($res)) {
        $help_columns[$row['HelpColumn']] = array (
            'QuickHelp' => trim ($row['QuickHelp']),
            'HelpText' => (trim ($row['HelpText']) != ''? true: false)
        );
    }
}

$hidden_fields = array ();

// TODO: move all of this into Form::render or several methods
echo "<form name=\"main_form\" method=\"post\" action=\"{$urls['main_add_action']}\"";
if ($has_a_richtext_editor) echo ' onsubmit="return submitForm();"';
if ($file_uploads_required) echo ' enctype="multipart/form-data"';
echo ">\n";

echo "<input type=\"hidden\" name=\"_t\" value=\"{$_GET['t']}\">\n";

if (@$_GET['p'] != '') {
    echo "<input type=\"hidden\" name=\"_p\" value=\"", htmlspecialchars ($_GET['p']), "\">\n";
    
    $parents = explode (',', $_GET['p']);
    $parent_vals = array ();
    foreach ($parents as $parent) {
        $bits = explode ('.', $parent);
        $parent_vals[$bits[0]] = $bits[1];
    }
}


// determine what functions are going to be called, and build a MySQL string of all of them
$functions = array ();
$function_id = 0;
foreach ($view_items as $item) {
    if ($item instanceof FunctionViewItem) {
        $functions[] = $item->getCode (). ' AS func'. ($function_id++);
    }
}
if ($function_id > 0) {
    $q = 'SELECT ' . implode (', ', $functions);
    $res = execq($q);
    if ($_SESSION['setup']['view_q'] === true) {
        echo "Q: {$q}<br>\n";
    }
    $row = fetch_assoc($res);
}

check_session_response (ADMIN_KEY);


echo "<table class=\"main_add_edit\">\n";
echo "<col class=\"col_eng_name\" id=\"col_eng_name_{$table->getName ()}\">\n";
echo "<col class=\"col_value\" id=\"col_value_{$table->getName ()}\">\n";
echo "<tbody>\n";

$function_id = 0;
$include_id = 0;
$heading_id = 0;
foreach ($view_items as $item) {
    if ($item instanceof HeadingViewItem) {
        // Heading
        $heading_id++;
        echo "<tr id=\"heading{$heading_id}\" class=\"heading\">";
        echo "<td colspan=\"2\"><h4>{$item->getName ()}</h4></td></tr>";
        
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
            echo '<td class="error">Error: Invalid include file name: file "',
                $item->getfilename (), '" does not exist';
        }
        echo "</td></tr>";
        $include_id++;

    } else if ($item instanceof ColumnViewItem) {
        // Column
        $col = $item->getColumn ();
        
        // TODO: make getInputField be aware of the fact that it's on an add form
        // and therefore doesn't need to ask for the current password
        if ($col instanceof PasswordColumn) {
            $col->setExistingRequired (false);
        }
        
        $value = @$_SESSION[ADMIN_KEY]['add'][$table->getName ()][$col->getName ()];
        
        if (method_exists ($col, 'getMultiInputs')) {
            $input_rows = $col->getMultiInputs ($form, $value);
        } else {
            $input_rows = array (array (
                'label' => $col->getInputLabel (),
                'field' => $col->getInputField ($form, $value),
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
        
    } else {
        echo '<tr><td class="error" colspan="2">Error: Invalid view item type '. get_class ($item). '</td></tr>';
    }
}

echo "<tr class=\"buttons\"><td>&nbsp;</td><td>";
echo "<input type=\"submit\" value=\"{$button_text['add']}\"> ";
echo "<input type=\"submit\" value=\"{$button_text['cancel']}\" name=\"cancel\"> ";
echo "</td></tr>\n";

echo "</tbody>\n";
echo "</table>\n";

// output all the hidden fields
foreach ($hidden_fields as $name => $value) {
    echo "<input type=\"hidden\" name=\"{$name}\" value=\"", htmlspecialchars ($value), "\">\n";
}

echo "</form>\n</div>\n";

require "foot.php";
?>
