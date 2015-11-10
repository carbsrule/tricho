<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;
use Tricho\Meta\Table;

require_once '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML();
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

$db->dumpXML('', 'database_details2.php');
?>
