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


if ($_SESSION['setup']['table_edit']['chosen_table'] == '') {
    redirect ('./');
}

$db = Database::parseXML ('../tables.xml');

$curr_tbl = $db->getTable ($_SESSION['setup']['table_edit']['chosen_table']);
if ($curr_tbl == null) {
    $_SESSION['setup']['err'] = 'No table specified in session variable';
    redirect ('./');
}

$column = $curr_tbl->get ($_GET['col']);
if ($column == null) {
    $_SESSION['setup']['err'] = 'Unknown column specified';
    redirect ('./');
}


if ($curr_tbl->removeColumn ($column)) {
    
    $q = "ALTER TABLE `". $curr_tbl->getName (). "` DROP COLUMN `". $_GET['col']. '`';
    $res = execq($q);
    if (! $res) {
        $_SESSION['setup']['err'] = 'Unable to remove column due to a database error.';
    } else {
        if ($db->dumpXML ('../tables.xml', null)) {
            $log_message = "Removed column ". $curr_tbl->getName (). '.'. $_GET['col'];
            log_action ($db, $log_message, $q);
        }
        redirect ('table_edit_cols.php');
    }
    
} else {
    $_SESSION['setup']['err'] = 'Unable to remove column.';
}

redirect ('table_edit_cols.php');
?>
