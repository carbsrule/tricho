<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';

$db = Database::parseXML();

// header
header ('Content-Type: text/xml');


// return nothing in blank case
if ($_GET['table'] == '') {
    echo '<table />';
    exit;
}

$table = $db->get ($_GET['table']);
if ($table != null) {
    // check user has access to the table
    if ($table->getAccessLevel () == TABLE_ACCESS_SETUP_FULL and
            $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
        echo '<table />';
        exit;
    }
}

$cols_with_index = array();
$primary_key = array();
$q = "SHOW INDEX FROM `{$_GET['table']}`";
$res = execq($q);
while ($row = fetch_assoc($res)) {
    if ($row['Key_name'] == 'PRIMARY') {
        $primary_key[] = $row['Column_name'];
    } else {
        $cols_with_index[] = $row['Column_name'];
    }
}

// get columns
$q = "SHOW COLUMNS FROM `{$_GET['table']}`";
$res = execq($q);

// output columns
echo "<table name=\"{$_GET['table']}\">";
while ($row = fetch_assoc($res)) {
    $row['Field'] = htmlspecialchars ($row['Field']);
    
    if (in_array ($row['Field'], $primary_key)) {
        $index = 'PRI';
    } else if (in_array ($row['Field'], $cols_with_index)) {
        $index = 'INDEX';
    } else {
        $index = '';
    }
    
    echo "<column name=\"{$row['Field']}\" type=\"{$row['Type']}\" index=\"{$index}\"/>";
}
echo '</table>';
?>
