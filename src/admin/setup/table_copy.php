<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;

require 'head.php';

$db = Database::parseXML();
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
    <th>Existing table</th>
    <th>New table</th>
<tr>
    <th>Name</th>
    <td><?= $name; ?></td>
    <td><input type="text" name="dest"></td>
</tr>
<tr>
    <th>English name</th>
    <td><?= hsc($table->getEngName()); ?></td>
    <td><input type="text" name="dest_eng"></td>
</tr>
<tr>
    <th>Singular name</th>
    <td><?= hsc($table->getNameSingle()); ?></td>
    <td><input type="text" name="dest_single"></td>
</tr>
<tr>
    <td colspan="2"></td>
    <td><input type="submit" value="Make copy"></td></td>
</tr>
</table>
</form>
