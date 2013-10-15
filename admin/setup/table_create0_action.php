<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
require_once ROOT_PATH_FILE. 'tricho/data_setup.php';
require_once 'table_create_checks.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

$_SESSION['setup']['create_table']['table_name'] = trim($_POST['table_name']);
$_SESSION['setup']['create_table']['table_name_eng'] = trim($_POST['table_name_eng']);
$_SESSION['setup']['create_table']['table_name_single'] = trim($_POST['table_name_single']);

$_SESSION['setup']['create_table']['engine'] = trim ($_POST['Engine']);
$_SESSION['setup']['create_table']['charset'] = trim ($_POST['Charset']);
$_SESSION['setup']['create_table']['collation'] = trim ($_POST['Collation']);

$_SESSION['setup']['create_table']['access_level'] = (int) $_POST['access_level'];

$_SESSION['setup']['create_table']['static'] = ($_POST['static'] == 1? true: false);

$_SESSION['setup']['create_table']['display_style'] = TABLE_DISPLAY_STYLE_ROWS;
$_SESSION['setup']['create_table']['display'] = (bool) $_POST['display'];

$options = array ('add', 'edit', 'del');
foreach ($options as $option) {
    $_SESSION['setup']['create_table']['allow_'. $option] = (bool) $_POST['allow_'. $option];
}

$_SESSION['setup']['create_table']['has_links'] = 0;
$_SESSION['setup']['create_table']['comments'] = trim($_POST['comments']);

check_1 ();

$table = new Table ($_SESSION['setup']['create_table']['table_name']);
$table->setEngName ($_SESSION['setup']['create_table']['table_name_eng']);
$table->setNameSingle ($_SESSION['setup']['create_table']['table_name_single']);
$table->setAccessLevel ($_SESSION['setup']['create_table']['access_level']);
$table->setStatic ($_SESSION['setup']['create_table']['static']);
$table->setDisplay ($_SESSION['setup']['create_table']['display']);
$table->setDisplayStyle ($_SESSION['setup']['create_table']['display_style']);
$table->setComments ($_SESSION['setup']['create_table']['comments']);
$options = array ('add', 'edit', 'del');

foreach ($options as $option) {
    if ($_SESSION['setup']['create_table']["allow_{$option}"] == 1) {
        $table->setAllowed ($option, true);
    } else {
        $table->setAllowed ($option, false);
    }
}
$table->setConfirmDel (true);

// make sure there are no duplicate masks
$db = Database::parseXML ('../tables.xml');

if ($db !== null) {
    $tables = $db->getTables ();
    
    $other_masks = array ();
    foreach ($tables as $other_table) {
        $other_masks[] = $other_table->getMask ();
    }
    
    while (in_array ($table->getMask (), $other_masks)) {
        $table->newMask ();
    }
}

$_SESSION['setup']['create_table']['table'] = $table;

redirect ('table_create1.php?id=1');

?>
