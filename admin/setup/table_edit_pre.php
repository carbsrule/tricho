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

$table = $db->getTable ($_GET['table']);

if ($table == null) {
    redirect ('./');
}

// check user has access to the table
if ($table->getAccessLevel () == TABLE_ACCESS_SETUP_FULL and
    $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
    redirect ('./');
}

switch ($_GET['action']) {
    case 'Edit':
        $_SESSION['setup']['table_edit']['chosen_table'] = $_GET['table'];
        redirect ('table_edit_cols.php');
    
    case 'Copy':
        redirect('table_copy.php?table=' . urlencode($_GET['table']));
        
    case 'EditMainView':
        $_SESSION['setup']['table_edit']['chosen_table'] = $_GET['table'];
        redirect ('table_edit_main_view.php');
        
    case 'Delete':
        redirect ('table_del0.php?table=' . urlencode ($_GET['table']));
        
    default:
        $_SESSION['setup']['err'] = 'Unknown action';
        redirect ('./');
}
?>
