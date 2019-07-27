<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;

require '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

require_once 'setup_functions.php';
require_once 'column_definition.php';


$session = &$_SESSION['setup']['table_edit'];

$db = Database::parseXML();
$table = $db->getTable($_POST['t']);
if ($table == null) redirect ('./');

if ($_POST['action'] == 'cancel') {
    unset($session['add_column'][$_POST['t']]);
    redirect('table_edit_cols.php?t=' . urlencode($_POST['t']));
}
$form_url = 'table_edit_col_add.php?t=' . urlencode($_POST['t']);

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
if ($config['class'] != '') {
    $config['class'] = 'Tricho\\Meta\\' . $config['class'];
}
$session['add_column'][$_POST['t']] = $config;
$col = column_config_to_meta ($table, 'add', $form_url, $config);

// warn if duplicate english name
if ($col->hasDuplicateEnglishName()) {
    $_SESSION['setup']['warn'][] = 'A column with the english name ' .
        '<em>' . $col->getEngName() . '</em> already exists';
}

$q = column_def_add ($table, $col, $form_url, $config);
column_def_update_views ($col, $config);
unset ($session['add_column'][$_POST['t']]);

try {
    $db->dumpXML('', null);
    $log_message = "Added column ". $table->getName (). '.'. $col->getName ();
    if ($q) {
        log_action($log_message, $q);
    }
} catch (FileNotWriteableException $ex) {
    $_SESSION['setup']['err'] = 'Failed to save XML';
}

redirect('table_edit_cols.php?t=' . urlencode($_POST['t']));
?>
