<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require_once ROOT_PATH_FILE . 'tricho/data_objects.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML('../tables.xml');
$table = $db->getTable($_SESSION['setup']['table_edit']['chosen_table']);

// clear existing alternate pages
$buttons = $table->getAltButtons();
foreach ($buttons as $id => $button) {
    $table->unsetAltButton($id);
}

// add new choices
if (@is_array($_POST['button'])) {
    foreach ($_POST['button'] as $old => $new) {
        if (str_replace(' ', '', $new) != '') {
            $table->setAltButton($old, $new);
        }
    }
}

// test: show data
// echo "<pre>", print_r ($table->getAltPages (), true), "</pre>\n";

// store xml
$db->dumpXML('../tables.xml', 'table_edit.php');
?>
