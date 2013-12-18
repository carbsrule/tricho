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

$session = &$_SESSION['setup']['create_table'];

$session['table_name'] = trim($_POST['table_name']);
$session['table_name_eng'] = trim($_POST['table_name_eng']);
$session['table_name_single'] = trim($_POST['table_name_single']);

$session['engine'] = trim($_POST['Engine']);
$session['charset'] = trim($_POST['Charset']);
$session['collation'] = trim($_POST['Collation']);

$session['access_level'] = (int) $_POST['access_level'];

$session['static'] = (@$_POST['static'] == 1? true: false);

$session['display_style'] = TABLE_DISPLAY_STYLE_ROWS;
$session['display'] = (bool) $_POST['display'];

$options = array ('add', 'edit', 'del');
foreach ($options as $option) {
    $session['allow_'. $option] = (bool) $_POST['allow_'. $option];
}

$session['has_links'] = 0;
$session['comments'] = trim($_POST['comments']);

check_1 ();

$table = new Table ($session['table_name']);
$table->setEngName ($session['table_name_eng']);
$table->setNameSingle ($session['table_name_single']);
$table->setAccessLevel ($session['access_level']);
$table->setStatic ($session['static']);
$table->setDisplay ($session['display']);
$table->setDisplayStyle ($session['display_style']);
$table->setComments ($session['comments']);
$options = array ('add', 'edit', 'del');

foreach ($options as $option) {
    if ($session["allow_{$option}"] == 1) {
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

$session['table'] = $table;

redirect ('table_create1.php?id=1');

?>
