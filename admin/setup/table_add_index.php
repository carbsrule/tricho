<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;
use Tricho\Query\RawQuery;

require_once '../../tricho.php';
test_setup_login (true, SETUP_ACCESS_FULL);

$db = Database::parseXML();
$table = $db->get($_POST['t']);
if (!$table) redirect('./');
$url = 'table_edit_indexes.php?t=' . urlencode($_POST['t']);

if ($_POST['type'] == 'INDEX') {
    $type = 'INDEX';
} else if ($_POST['type'] == 'FULLTEXT') {
    $type = 'FULLTEXT';
} else if ($_POST['type'] == 'UNIQUE') {
    $type = 'UNIQUE INDEX';
} else {
    $_SESSION['setup']['err'] = 'Invalid index type';
    redirect($url);
}
if (@count ($_POST['columns']) == 0) {
    $_SESSION['setup']['err'] = 'Invalid index columns';
    redirect($url);
}

$q = "ALTER TABLE `" . $_POST['t'] . '` ';
$q .= ' ADD ' . $type;
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

$q = new RawQuery($q);
$q->set_internal(true);

try {
    execq($q);
    $_SESSION['setup']['msg'] = 'Index created';
    $log_message = 'Added index on '. $_POST['t'] . ': [';
    $col_num = 0;
    foreach ($_POST['columns'] as $col) {
        if ($col_num++ > 0) $log_message .= ', ';
        $log_message .= $col;
    }
    $log_message .= ']';
    log_action ($db, $log_message, $q);
    
} catch (QueryException $ex) {
    $conn = ConnManager::get_active();
    $_SESSION['setup']['err'] = 'Index not created due to a ' .
        'database error:<br>' . $ex->getMessage();
}

redirect($url);
?>
