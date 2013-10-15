<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);
require_once ROOT_PATH_FILE. 'tricho/data_setup.php';
require_once 'setup_functions.php';
require_once 'column_definition.php';

$session = &$_SESSION['setup']['table_edit'];
if ($session['chosen_table'] == '') redirect ('./');
$db = Database::parseXML ('../tables.xml');
$table = $db->getTable ($session['chosen_table']);
if ($table == null) redirect ('./');
$column = $table->get ($session['chosen_column']);
if ($column == null) redirect ('./');
$old_sql_defn = array (
    'name' => $column->getName (),
    'defn' => $column->getSqlDefn ()
);

if ($_POST['action'] == 'cancel') {
    unset ($session['edit_column']);
    redirect (ROOT_PATH_WEB. ADMIN_DIR. 'setup/table_edit_cols.php');
}

$config = array ();
$class = $_POST['class'];
$pattern = $class. '_';
foreach ($_POST as $field => $value) {
    if (strpos ($field, 'Column_') !== false) {
        if (starts_with ($field, $pattern)) {
            $field = trim_start ($field, $pattern);
        } else {
            continue;
        }
    }
    $config[$field] = $value;
}
$session['edit_column'] = $config;

$session['edit_column']['specific_date'] = tricho_date_build ('specific_date');

$new_col = column_config_to_meta ($table, 'edit', 'table_edit_col_edit.php', $config);
$table->replaceColumn($column, $new_col);

// warn if duplicate english name
if ($new_col->hasDuplicateEnglishName()) {
    $_SESSION['setup']['warn'][] = 'A column with the english name ' .
        '<em>' . $new_col->getEngName() . '</em> already exists';
}
$q = column_def_edit ($new_col, $old_sql_defn, 'table_edit_col_edit.php', $config);
column_def_update_views ($new_col, $config);
unset ($session['edit_column']);

$db = $table->getDatabase ();
try {
    $db->dumpXML ('../tables.xml', null);
    $log_message = "Changed column ". $table->getName (). '.'. $old_sql_defn['name'];
    if ($new_col->getName () != $old_sql_defn['name']) {
        $log_message .= " - renamed to ". $new_col->getName ();
    }
    log_action ($db, $log_message, $q);
} catch (FileNotWriteableException $ex) {
    $_SESSION['setup']['err'] = 'Failed to save XML';
}

redirect (ROOT_PATH_WEB. ADMIN_DIR. 'setup/table_edit_cols.php');
?>
