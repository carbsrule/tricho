<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once 'head.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);


$page_opts = array('tab' => 'indexes');
require 'table_head.php';


$index = trim ($_GET['i']);


if ($index == '') {
    $_SESSION['setup']['err'] = 'Invalid index';
    redirect ('table_edit_indexes.php');
}


echo "<p>Are you sure you want to delete the index <strong>{$index}</strong> which indexes the column(s) ";


$q = "SHOW INDEXES FROM `". $table->getName (). "`
    WHERE Key_name = " . sql_enclose($index);
$res = execq($q);
$row_num = 0;
while ($row = fetch_assoc($res)) {
    if (++$row_num != 1) echo ', ';
    echo "<strong>{$row['Column_name']}</strong>";
    if ($row['Sub_part'] != null) echo ' (' . $row['Sub_part'] . ')';
}

echo "?</p>";
?>

<table><tr>
    <form method="get" action="table_edit_indexes.php"><td><input type="submit" value="&lt;&lt; No"><input type="hidden" name="t" value="<?= hsc($_GET['t']); ?>"></td></form>
    <form method="post" action="table_del_index_action.php">
        <input type="hidden" name="t" value="<?= urlencode($_GET['t']); ?>">
        <input type="hidden" name="index" value="<?= urlencode($index); ?>">
        <td><input type="submit" value="Yes &gt;&gt;"></td>
    </form>
</tr></table>

<?php
require_once 'foot.php';
?>
