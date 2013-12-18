<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML ('../tables.xml');
$db->setDataChecking ((bool) @$_POST['data_check']);
$db->setShowPrimaryHeadings ((bool) @$_POST['primary_heading']);
$db->setShowSectionHeadings ((bool) @$_POST['section_heading']);
$db->setShowSubRecordCount ((bool) @$_POST['show_sub_record_count']);
$db->setShowSearch ((bool) @$_POST['show_search']);

$table = $db->getTable (@$_POST['help_table']);
if ($table == null) {
    $table = new Table (@$_POST['help_table']);
}
$db->setHelpTable ($table);

$db->setConvertOutput ((int) @$_POST['convert_output']);

$db->dumpXML ('../tables.xml', 'database_details2.php');
?>
