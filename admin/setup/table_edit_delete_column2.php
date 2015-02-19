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

$db = Database::parseXML();
$table = $db->getTable($_POST['t']);
if ($table == null) {
    $_SESSION['setup']['err'] = 'Invalid table specified';
    redirect('./');
}

$column = $table->get($_POST['col']);
if ($column == null) {
    $_SESSION['setup']['err'] = 'Unknown column specified';
    redirect('./');
}

$url = 'table_edit_cols.php?t=' . urlencode($_POST['t']);
if ($table->removeColumn($column)) {
    $q = "ALTER TABLE `" . $table->getName() . "` DROP COLUMN `" .
        $_POST['col'] . '`';
    $res = execq($q);
    if (!$res) {
        $_SESSION['setup']['err'] = 'Unable to remove column due to a database error.';
    } else {
        if ($db->dumpXML ('../tables.xml', null)) {
            $log_message = "Removed column ". $table->getName (). '.'. $_POST['col'];
            log_action ($db, $log_message, $q);
        }
        redirect($url);
    }
    
} else {
    $_SESSION['setup']['err'] = 'Unable to remove column.';
}

redirect($url);
?>
