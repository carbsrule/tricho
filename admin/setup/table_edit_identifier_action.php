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

$db = Database::parseXML('../tables.xml');
$table = $db->getTable($_POST['t']);
if ($table == null) redirect('./');

$url = 'table_edit_identifier.php?t=' . urlencode($_POST['t']);
if ($_POST['action'] != 'Save') redirect($url);

// determine the identifier
$desc = array ();
if (isset($_POST['desc'])) {
    foreach ($_POST['desc'] as $item) {
        list ($type, $value) = explode (':', $item, 2);
        
        if ($type == 'c') {
            // column
            $temp = $table->get($value);
            if ($temp != null) $desc[] = $temp;
            
        } elseif ($type == 't') {
            // text
            $desc[] = $value;
        }
    }
}

// set the identifier
$table->setRowIdentifier($desc);
$db->dumpXML('../tables.xml', $url);
?>
