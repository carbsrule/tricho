<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_admin_login();

$db = Database::parseXML();
$table = $db->getTable ($_GET['t']);


// Check the validity of the selected table
if ($table == null) {
    $error = "Table does not exist: {$_GET['t']}";
} else if (! $table->checkAuth ()) {
    $error = "Table does not exist: {$_GET['t']}";
}

// If there was an error, report it
if ($error) {
    require_once 'head.php';
    echo "<div id=\"main_data\">\n";
    report_error ($error);
    echo "</div>\n";
    require_once 'foot.php';
    unset ($_SESSION[ADMIN_KEY]['err']);
    unset ($_SESSION[ADMIN_KEY]['msg']);
    die ();
}


// work out the ancestors
if (trim ($_GET['p']) != '') {
    $ancestors = explode (',', $_GET['p']);
    
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
}

// get our columns and stuff
$main = new MainTable ($table, 'export');

// parent table support
if (isset ($ancestors)) {
    // changed by benno: the where clause doesn't need to reference the linked table, only the base
    $query = $main->getSelectQuery ();
    $link_col = $table->getLinkToTable ($parent_table);
    $query_col = new QueryColumn ($query->getBaseTable (), $link_col->getName ());
    
    // Use integer value where possible for parent joins
    if (preg_match ('/^-?[0-9]+$/', $parent_id)) {
        $escape_literal = false;
    } else {
        $escape_literal = true;
    }
    $parent_literal = new QueryFieldLiteral ($parent_id, $escape_literal);
    
    // condition for parent join
    $cond = new LogicConditionNode (
        $query_col,
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

// choose the filename and type
$file_name = tricho\Runtime::get('site_name') . '_' . $table->getEngName() .
    '_' . date('Y-m-d') . '.csv';
$file_name = addslashes($file_name);
$content_type = 'text/csv';
$export_type = EXPORT_TYPE_CSV;

// send some appropriate headers
header ("Content-Type: {$content_type}");
header ("Content-Disposition: attachment; filename=\"{$file_name}\"");
header ("Cache-Control: cache, must-revalidate");
header ("Pragma: public");

// do the export
echo $main->export ($export_type);
?>
