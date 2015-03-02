<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML();

$table = $db->getTable ($_GET['table']);

if ($table == null) {
    redirect ('./');
}

// check user has access to the table
if ($table->getAccessLevel () == TABLE_ACCESS_SETUP_FULL and
    $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
    redirect ('./');
}

$table = urlencode($_GET['table']);
switch ($_GET['action']) {
case 'Edit':
    redirect('table_edit_cols.php?t=' . $table);

case 'Copy':
    redirect('table_copy.php?table=' . $table);
    
case 'EditMainView':
    redirect('table_edit_main_view.php?t=' . $table);
    
case 'Delete':
    redirect('table_del0.php?table=' . $table);
    
default:
    $_SESSION['setup']['err'] = 'Unknown action';
    redirect('./');
}
?>
