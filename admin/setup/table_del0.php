<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$db = Database::parseXML ('../tables.xml');
$table = $db->getTable ($_GET['table']);

if ($table == null) {
    report_error ("Unknown table");
    die ();
}

// check user has access to the table
if ($table->getAccessLevel () == TABLE_ACCESS_SETUP_FULL and
    $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
    report_error ("Unknown table");
    die ();
}

// echo "tables <pre>"; print_r ($tables); echo "</pre>\n";
?>
<h2>Delete a table</h2>
Are you sure you want to delete <?= $_GET['table']; ?>?<br>
<b>Make sure you have backed up the database and the XML file before continuing</b><br>&nbsp;
<table>
<tr>
<form method="get" action="./"><td><input type="submit" value="&lt;&lt; No"></td></form>
<form method="post" action="table_del0_action.php">
    <input type="hidden" name="table" value="<?= $_GET['table']; ?>">
    <td><input type="submit" value="Yes &gt;&gt;"></td>
</form>
</tr>
</table>
</form>
