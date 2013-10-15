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

if ($_SESSION['setup']['create_table']['table_name'] == '') {
    redirect ('table_create0.php');
}

$table_name = $_SESSION['setup']['create_table']['table_name'];
$engine = $_SESSION['setup']['create_table']['engine'];
$collation = $_SESSION['setup']['create_table']['collation'];
unset ($_SESSION['setup']['create_table']);

$db = Database::parseXML ('../tables.xml');
$table = $db->getTable ($table_name);

// generate database table from XML definition
$sql = $table->getCreateQuery($engine, $collation);

// echo "<p>Q: $sql</p>";
$res = execq($sql);
$error = $res->errorCode();
if ($error == '00000') {
    log_action ($db, "Created table {$table_name}", $sql);
    $_SESSION['setup']['msg'] = "Table created.<br>You can now set up the links and other properties for this table.";

} else {
    $conn = ConnManager::get_active();
    $_SESSION['setup']['err'] = "Table was not created in " .
        "database due to database error:<br>\n" . $conn->last_error() .
        "<br><br>\nYou can create the database at any time by going to " .
        "<a href=\"./table_show_create_query.php\">setup/" .
        "table_show_create_query.php</a>";
}

redirect ('table_edit_pre.php?action=Edit&table=' . $table->getName ());
?>
