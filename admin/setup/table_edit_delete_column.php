<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array('tab' => 'cols');
require 'table_head.php';

$curr_tbl = $db->getTable ($_SESSION['setup']['table_edit']['chosen_table']);

if ($curr_tbl == null) redirect ('./');

$column = $curr_tbl->get ($_GET['col']);

if ($column == null) {
    report_error ("Unknown column");
    die ();
}

?>
<form method="get" action="table_edit_delete_column2.php">
<input type="hidden" name="col" value="<?= $_GET['col']; ?>">
<p>Are you sure you want to delete the column <?= $_GET['col']; ?>?</p>
<p> <input type="button" onclick="window.location = 'table_edit_cols.php';" value="&lt; NO">
    <input type="submit" value="YES &gt;"> </p>
</form>

<?php
require 'foot.php';
?>
