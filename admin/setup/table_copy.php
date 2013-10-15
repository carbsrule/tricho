<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$db = Database::parseXML('../tables.xml');
$table = $db->getTable($_GET['table']);

if ($table == null) {
    report_error("Unknown table");
    die();
}

// check user has access to the table
if ($table->getAccessLevel () == TABLE_ACCESS_SETUP_FULL and
    $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
    report_error("Unknown table");
    die();
}
?>
<h2>Make a copy of a table</h2>

<form method="post" action="table_copy_action.php">
<style type="text/css">th {text-align: left;}</style>
<?php
$name = hsc($table->getName());
?>
<input type="hidden" name="source" value="<?= $name; ?>">
<table>
<tr>
    <td></td>
    <th>Name</th>
    <th>English name</th>
<tr>
    <th>Existing table</th>
    <td><?= $name; ?></td>
    <td><?= hsc($table->getEngName()); ?></td>
</tr>
<tr>
    <th>New table</th>
    <td><input type="text" name="dest"></td>
    <td><input type="text" name="dest_eng">
        <input type="submit" value="Make copy"></td>
</tr>
</table>
</form>
