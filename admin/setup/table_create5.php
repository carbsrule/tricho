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

if ($_SESSION['setup']['create_table']['table_name'] != '') {
    // save database meta-data in XML
    
    $db = Database::parseXML();
    $table = $_SESSION['setup']['create_table']['table'];
    
    // Check each element of the row identifier, and if it refers to a column,
    // check that the column actually exists as a member of the table - if not,
    // remove that element
    $row_identifiers = $table->getRowIdentifier ();
    foreach ($row_identifiers as $key => $row_identifier) {
        if (!in_array ($row_identifier, $table->getColumns (), true)) {
            unset ($row_identifiers[$key]);
        }
    }
    $table->setRowIdentifier ($row_identifiers);
    
    $db->addTable ($table);
    $table->setDatabase ($db);
    
    $db->dumpXML ('../tables.xml', 'table_create6.php');
    
} else {
    report_error ('Session data lost.');
}

require 'foot.php';
?>
