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
        <th colspan="2" align="left">Type</th>
        <th>Info</th>
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
        <td>
            <small>
<?php
    echo get_class ($col);
    
    switch ($col->getOption ()) {
        case 'richtext':
            echo '[mce]';
            break;
            
        case 'richtext2':
            echo '[xstd]';
            break;
            
        case 'file':
            echo '[file:', $col->getParam ('storage_location'), ']';
            break;
            
        case 'image':
            echo '[img:', $col->getParam ('storage_location');
            if ($col->getParam ('size')) echo ':', $col->getParam ('size');
            echo ']';
            break;
            
        case 'password':
            $enc = $col->getParam ('encryption_mechanism');
            if ($enc != '') echo '[', strtoupper ($enc), ']';
            break;
    }
    
    if ($col instanceof TemporalColumn and $col->hasDate()) {
        $min_year = $col->getMinYear();
        $max_year = $col->getMaxYear();
        echo "[{$min_year}:{$max_year}]";
    }
?>
            </small>
        </td>
        <td>
            <small><?php
    echo sql_type_string_from_defined_constant ($col->getSqlType ());
    if ($col->getSqlSize () != '') {
        echo '(', $col->getSqlSize (), ')';
    }
    echo ' ', implode (' ', $col->getSqlAttributes ());
?></small>
        </td>
        <td><?php
    $link_data = $col->getLink ();
    if ($link_data != null) {
        // show link for linked columns
        echo ' -&gt; ', $link_data->getToColumn ()->getTable ()->getName (), '.', $link_data->getToColumn ()->getName ();
        if ($link_data->isParent()) {
            echo ' (parent)';
        }
    } else {
        // show mask for image/file columns
        $option = $col->getOption ();
        switch ($option) {
            case 'file':
            case 'image':
                echo $table_mask. '.'. $col->getMask ();
                break;
                
            case 'ordernum':
                echo 'Order #';
                break;
             
            default:
                echo '&nbsp;';
        }
    }
?></td>
        <td>
            <form method="get" action="table_edit_col_action.php">
            <input type="hidden" name="col" value="<?= $col->getName (); ?>">
            <input type="submit" name="action" value="Edit">
<?php
//TODO: remove old link mechanism once link descriptors/ordering mechanism
// has been implemented for LinkColumn
//            <input type="submit" name="action" value="Link">
?>
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
<form action="table_edit_col_add.php">
    <input type="submit" value="Add another column">
</form>

<?php
require 'foot.php';
?>
