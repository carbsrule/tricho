<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array ('tab' => 'cols');
require 'table_head.php';
?>

<table class="table_cols" cellspacing="0">
    <tr>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>Name</th>
        <th>Class</th>
        <th>Info</th>
        <th>Type</th>
        <th>Actions</th>
    </tr>
    
<?php
$columns = $table->getColumns ();
$table_mask = $table->getMask ();
$pks = $table->getPKnames ();
$columns_count = 1;

foreach ($columns as $col) {
?>
    <tr>
        <td><?= $columns_count; ?></td>
        <td class="mandatory"><?php
    if ($col->isMandatory ()) {
        echo '<img src="', ROOT_PATH_WEB, IMAGE_MANDATORY, '" alt="*" title="Mandatory">';
    } else {
        echo '&nbsp;';
    }
?></td>
        <td><?php
    if (in_array ($col->getName (), $pks)) {
        echo "<strong>{$col->getName ()}</strong>";
    } else {
        echo $col->getName ();
    }
?></td>
        <td><small><?= get_class($col); ?></small></td>
        <td><small><?= $col->getInfo(); ?></small></td>
        <td>
            <small><?php
    echo $col->getSqlType();
    if ($col->getSqlSize () != '') {
        echo '(', $col->getSqlSize (), ')';
    }
    echo ' ', implode (' ', $col->getSqlAttributes ());
?></small>
        </td>
        <td>
            <form method="get" action="table_edit_col_action.php">
            <input type="hidden" name="t" value="<?= hsc($_GET['t']); ?>">
            <input type="hidden" name="col" value="<?= $col->getName (); ?>">
            <input type="submit" name="action" value="Edit">
            <input type="submit" name="action" value="Del">
            </form>
        </td>
    </tr>
<?php
    $columns_count++;
}
?>
</table>

<br>
<form method="get" action="table_edit_col_add.php">
    <input type="hidden" name="t" value="<?= hsc($_GET['t']); ?>">
    <input type="submit" value="Add another column">
</form>

<?php
require 'foot.php';
?>
