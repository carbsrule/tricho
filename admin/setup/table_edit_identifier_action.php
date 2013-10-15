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

// if the user cancelled, do a cancel
if ($_POST['action'] != 'Save') {
    redirect ('./table_edit.php');
}

// get our selected table
$db = Database::parseXML ('../tables.xml');
$curr_tbl = $db->getTable ($_SESSION['setup']['table_edit']['chosen_table']);
if ($curr_tbl == null) redirect ('./');


// determine the identifier
$desc = array ();
if (isset($_POST['desc'])) {
    foreach ($_POST['desc'] as $item) {
        list ($type, $value) = explode (':', $item, 2);
        
        if ($type == 'c') {
            // column
            $temp = $curr_tbl->get ($value);
            if ($temp != null) $desc[] = $temp;
            
        } elseif ($type == 't') {
            // text
            $desc[] = $value;
        }
    }
}

// set the identifier
$curr_tbl->setRowIdentifier ($desc);

// write our xml
$db->dumpXML ('../tables.xml', 'table_edit_identifier.php');
?>
