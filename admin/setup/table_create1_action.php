<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
require_once ROOT_PATH_FILE. 'tricho/data_setup.php';

test_setup_login (true, SETUP_ACCESS_LIMITED);
require 'table_create_checks.php';
require_once 'setup_functions.php';
require_once 'column_definition.php';

$session = & $_SESSION['setup']['create_table'];
$col_num = $_POST['_col_id'];

$table = $session['table'];
if ($table == null) {
    $_SESSION['setup']['err'] = 'Session lost';
    redirect ('./');
}

if ($_POST['action'] == 'cancel') {
    redirect ('table_create1_done.php');
}

if (!table_name_valid ($_POST['name'])) $_POST['name'] = '';

if (!isset($session['columns'])) $session['columns'] = array();

// reposition columns if necessary
if ($_POST['insert_after'] == -1) {
    unset ($session['columns'][$col_num]);
    
    // can't use array merge, since indexes starting from 1 are desired
    $new_cols = array (1 => array ());
    foreach ($session['columns'] as $col) {
        $new_cols[] = $col;
    }
    $col_num = 1;
    $session['columns'] = $new_cols;
} else if ($_POST['insert_after'] != 'retain') {
    // ensure not to overwrite
    if ($_POST['insert_after'] == '') {
        $position = -1;
        unset ($session['columns'][$col_num]);
    } else {
        // increment value since keys start at 1
        $position = $_POST['insert_after'] + 1;
        
        // remove existing definition if there is one,
        // and offset the new position to accommodate the hole just created
        if (isset ($session['columns'][$col_num])) {
            unset ($session['columns'][$col_num]);
            if ($position >= $col_num) $position -= 1;
        }
    }
    
    $new_cols = array ();
    $key = 0;
    foreach ($session['columns'] as $col) {
        $new_cols[++$key] = $col;
        if ($key == $position) {
            $new_cols[++$key] = array ();
            $col_num = $key;
        }
    }
    if ($_POST['insert_after'] == '') {
        $new_cols[++$key] = array ();
        $col_num = $key;
    }
    $session['columns'] = $new_cols;
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
$session['columns'][$col_num] = $config;

$table->wipe ();
foreach ($session['columns'] as $col => $col_data) {
    $column = column_config_to_meta ($table, 'add', 'table_create1.php?id='. $col, $col_data);
    
    // Repositioning has been done, so don't retain it in the session.
    // If a user needs to change this column during the table create process,
    // its current position should be retained
    unset ($session['columns'][$col_num]['insert_after']);
    
    if ($column->getName () == 'Name') $table->setRowIdentifier (array ($column));
    
    $table->addColumn ($column);
    column_def_update_views ($column, $col_data);
}
redirect ('table_create1_done.php');
?>
