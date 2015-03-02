<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

require_once 'setup_functions.php';
require_once 'column_definition.php';

@list($table, $column) = explode('.', $_POST['col']);
$db = Database::parseXML();
$table = $db->get($table);
if (!$table) redirect('./');

$column = $table->get($column);
if (!$column) redirect('./');

$id = $_POST['col'];
unset($_POST['col']);
if (!isset($_SESSION['setup']['table_edit']['edit_column'])) {
    $_SESSION['setup']['table_edit']['edit_column'] = array();
}
$session = &$_SESSION['setup']['table_edit']['edit_column'];
$old_sql_defn = array (
    'name' => $column->getName (),
    'defn' => $column->getSqlDefn ()
);

if ($_POST['action'] == 'cancel') {
    unset($session[$id]);
    redirect('table_edit_cols.php?t=' . $table->getName());
}
$form_url = 'table_edit_col_edit.php?t=' . urlencode($table->getName()) .
    '&col=' . urlencode($column->getName());

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
if (!@$_POST['set_default']) $config['sql_default'] = null;
$config['old_name'] = $column->getName();
$session[$id] = $config;
$new_col = column_config_to_meta ($table, 'edit', $form_url, $config);
$table->replaceColumn($column, $new_col);

// warn if duplicate english name
if ($new_col->hasDuplicateEnglishName()) {
    $_SESSION['setup']['warn'][] = 'A column with the english name ' .
        '<em>' . $new_col->getEngName() . '</em> already exists';
}
$q = column_def_edit ($new_col, $old_sql_defn, $form_url, $config);
column_def_update_views ($new_col, $config);
unset($session[$id]);

$db = $table->getDatabase ();
try {
    $db->dumpXML('', null);
    $log_message = "Changed column ". $table->getName (). '.'. $old_sql_defn['name'];
    if ($new_col->getName () != $old_sql_defn['name']) {
        $log_message .= " - renamed to ". $new_col->getName ();
    }
    log_action ($db, $log_message, $q);
} catch (FileNotWriteableException $ex) {
    $_SESSION['setup']['err'] = 'Failed to save XML';
}

redirect('table_edit_cols.php?t=' . $table->getName());
?>
