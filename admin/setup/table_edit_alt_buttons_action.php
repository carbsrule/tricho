<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML();
$table = $db->getTable($_POST['t']);
if (!$table) redirect('./');

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
$url = 'table_edit_alt_buttons.php?t=' . urlencode($_POST['t']);
$db->dumpXML('', $url);
?>
