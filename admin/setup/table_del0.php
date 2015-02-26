<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$db = Database::parseXML();
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
?>
<h2>Delete a table</h2>
<p>Are you sure you want to delete <?= $_GET['table']; ?>?<br>
<?php
$links = array();
$tables = $db->getTables();
foreach ($tables as $curr_table) {
    if ($table === $curr_table) continue;
    foreach ($curr_table->getColumns() as $column) {
        if (!($column instanceof LinkColumn)) continue;
        if ($column->getTarget()->getTable() !== $table) continue;
        $links[] = $column;
    }
}
$num = count($links);
if ($num > 0) {
    echo "<br>\n<strong>{$num} column", ($num == 1? '': 's'),
        " link to this table:</strong><br>\n";
    foreach ($links as $link) {
        echo $link->getFullName(), ' &#8658; ',
            $link->getTarget()->getFullName(), "<br>\n";
    }
    echo "<br>\n";
}
?>
All forms relating to this table will be deleted.</p>

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
