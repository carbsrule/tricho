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

// check there is at least one column used for the ORDER BY clause
$order = $table->getOrder ('view');
if (count($order) > 0) {
    redirect ('table_create5.php');
} else {
    $_SESSION['setup']['err'] = 'You must select at least one column with which to order records';
    redirect ('table_create4.php');
}

?>
