<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\Meta\Database;

require_once '../tricho.php';
test_setup_login (true, SETUP_ACCESS_FULL);
$root = Runtime::get('root_path');

$db = Database::parseXML();

$output = '';
$unknown_tables = array ();
$_POST['import'] = (array) $_POST['import'];
foreach ($_POST['import'] as $table_name) {
    if ($table = $db->get ($table_name)) {
        if ($output != '') $output .= "\n\n";
        $output .= $table->getCreateQuery() . ';';
    } else {
        $unknown_tables[] = $table_name;
    }
}

if (count ($unknown_tables) > 0) {
    $_SESSION[ADMIN_KEY]['err'][] = 'Unknown tables: '. implode (', ', $unknown_tables);
    redirect ('generate_tables_db.php');
}

header ('Content-type: text/plain');
echo $output;
?>
