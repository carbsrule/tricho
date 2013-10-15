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

$table = $_SESSION['setup']['create_table']['table'];

if (is_array($_POST['PRIMARY_KEY'])) {
    $new_primary_key = array();
    foreach ($_POST['PRIMARY_KEY'] as $id => $val) {
        if ($val == 1) {
            $new_primary_key[] = $table->get($id);
        }
    }
    $table->addIndex('PRIMARY KEY', $new_primary_key);
}

$primary_key = $table->getIndex ('PRIMARY KEY');
if (@count($primary_key) == 0) {
    $_SESSION['setup']['err'] = 'You must select a primary key';
    redirect ('table_create2.php');
}


// if there is a field named OrderNum, add it to the order lists
$order = $table->getOrder('view');
if (empty ($order)) {
    $columns = $table->getColumns ();
    foreach ($columns as $column) {
        if ($column->getOption () == 'ordernum') {
            $table->addToOrder ('view', $column);
        }
    }
}


if ($_SESSION['setup']['create_table']['has_links'] == 1) {
    // set up external links if there are any
    redirect ('table_create3.php');
} else {
    // otherwise, move on to ordering of columns for view and edit
    redirect ('table_create4.php');
}

?>
