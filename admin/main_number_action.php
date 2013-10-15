<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_admin_login ();
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';

$db = Database::parseXML ('tables.xml');
$table = $db->getTable ($_POST['_t']);

// get the number
$num = (int) $_POST['num'];
if ($num < MAIN_VIEW_PER_PAGE_MIN) $num = MAIN_VIEW_PER_PAGE_MIN;
if ($num > MAIN_VIEW_PER_PAGE_MAX) $num = MAIN_VIEW_PER_PAGE_MAX;

// set it
$_SESSION[ADMIN_KEY]['num_per_page'][$table->getName ()] = $num;

redirect ($_POST['_c']);

?>
