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

if ($_POST['action'] == 'cancel') {
    unset ($session['add_column']);
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

$session['add_column'] = $config;

$session['add_column']['specific_date'] = tricho_date_build ('specific_date');
$col = column_config_to_meta ($table, 'add', 'table_edit_col_add.php', $config);

// warn if duplicate english name
if ($col->hasDuplicateEnglishName()) {
    $_SESSION['setup']['warn'][] = 'A column with the english name ' .
        '<em>' . $col->getEngName() . '</em> already exists';
}

$q = column_def_add ($table, $col, 'table_edit_col_add.php', $config);
column_def_update_views ($col, $config);
unset ($session['add_column']);

try {
    $db->dumpXML ('../tables.xml', null);
    $log_message = "Added column ". $table->getName (). '.'. $col->getName ();
    log_action ($db, $log_message, $q);
} catch (FileNotWriteableException $ex) {
    $_SESSION['setup']['err'] = 'Failed to save XML';
}

redirect (ROOT_PATH_WEB. ADMIN_DIR. 'setup/table_edit_cols.php');
?>
