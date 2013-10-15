<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
test_setup_login (true, SETUP_ACCESS_FULL);

//print_r ($_POST);

if ($_POST['type'] == 'INDEX') {
    $type = 'INDEX';
} else if ($_POST['type'] == 'FULLTEXT') {
    $type = 'FULLTEXT';
} else if ($_POST['type'] == 'UNIQUE') {
    $type = 'UNIQUE INDEX';
} else {
    $_SESSION['setup']['err'] = 'Invalid index type';
    redirect ('table_edit_indexes.php');
}
if (@count ($_POST['columns']) == 0) {
    $_SESSION['setup']['err'] = 'Invalid index columns';
    redirect ('table_edit_indexes.php');
}


$q = "ALTER TABLE `". $_SESSION['setup']['table_edit']['chosen_table']. '` ';


$q .= ' ADD '. $type;
if (trim ($_POST['index_name']) != '') {
    $q .= ' `'. trim ($_POST['index_name']). '`';
}
$q .= ' (';

foreach ($_POST['columns'] as $id => $column) {
    if ($id > 0) $q .= ', ';
    $pos = strpos ($column, '(');
    if ($pos !== false) {
        $column = '`'. substr ($column, 0, $pos). '`'. substr ($column, $pos);
    } else {
        $column = '`'. $column. '`';
    }
    $q .= $column;
}
$q .= ')';

if (execq($q, false, false)) {
    $_SESSION['setup']['msg'] = 'Index created';
    
    $db = Database::parseXML ('../tables.xml');
    $log_message = 'Added index on '. $_SESSION['setup']['table_edit']['chosen_table']. ': [';
    $col_num = 0;
    foreach ($_POST['columns'] as $col) {
        if ($col_num++ > 0) $log_message .= ', ';
        $log_message .= $col;
    }
    $log_message .= ']';
    log_action ($db, $log_message, $q);
    
} else {
    $conn = ConnManager::get_active();
    $_SESSION['setup']['err'] = 'Index not created due to a ' .
        'database error:<br>' . $conn->last_error();
}

redirect ('table_edit_indexes.php');
?>
