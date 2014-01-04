<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array('tab' => 'cols');
require 'table_head.php';

$table = $db->get($_GET['t']);
if ($table == null) {
    report_error ("Unknown table");
    die ();
}

$column = $table->get($_GET['col']);
if ($column == null) {
    report_error ("Unknown column");
    die ();
}

?>
<form method="post" action="table_edit_delete_column2.php">
<input type="hidden" name="t" value="<?= hsc($_GET['t']); ?>">
<input type="hidden" name="col" value="<?= hsc($_GET['col']); ?>">
<p>Are you sure you want to delete the column <?= $_GET['col']; ?>?</p>
<p> <input type="button" onclick="window.location = 'table_edit_cols.php?t=<?= urlencode($_GET['t']); ?>';" value="&lt; NO">
    <input type="submit" value="YES &gt;"> </p>
</form>

<?php
require 'foot.php';
?>
