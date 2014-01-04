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
$table = $db->getTable($_GET['t']);
if ($table == null) {
    redirect ('./');
}

// check column is ok
$column = $table->get ($_GET['col']);
if ($column == null) {
    redirect ('table_edit.php');
}

// go to the appropriate page
$suffix = '?t=' . urlencode($_GET['t']) . '&col=' . urlencode($_GET['col']);
switch ($_GET['action']) {
case 'Edit':
    redirect('table_edit_col_edit.php' . $suffix);

case 'Link':
    redirect('table_edit_col_link.php' . $suffix);

case 'Del';
    redirect('table_edit_delete_column.php' . $suffix);

default:
    echo 'Invalid action!';
}

?>
