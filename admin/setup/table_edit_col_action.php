<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML ('../tables.xml');

// check table is ok
$table = $db->getTable ($_SESSION['setup']['table_edit']['chosen_table']);
if ($table == null) {
    redirect ('./');
}

// check column is ok
$column = $table->get ($_GET['col']);
if ($column == null) {
    redirect ('table_edit.php');
}

// go to the appropriate page
switch ($_GET['action']) {
    case 'Edit':
        $_SESSION['setup']['table_edit']['chosen_column'] = $_GET['col'];
        redirect ('table_edit_col_edit.php');
        break;
        
    case 'Link':
        $_SESSION['setup']['table_edit']['chosen_column'] = $_GET['col'];
        redirect ('table_edit_col_link.php');
        break;
        
    case 'Del';
        redirect ('table_edit_delete_column.php?col=' . $_GET['col']);
        break;
        
    default:
        echo 'Invalid action!';
}

?>
