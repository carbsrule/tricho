<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';
require 'order_functions.php';

$page_opts = array ('tab' => 'detail');
require 'table_head.php';
?>

<form method="post" action="table_edit_action.php" name="table_edit">

<table>
<tr valign="top"><td>

    <table>
    <tr><td>Table name (plural)</td><td><input type="text" name="table_name" value="<?= $table->getName (); ?>"></td></tr>
    <tr><td>English name (plural)</td><td><input type="text" name="table_name_eng" value="<?= htmlspecialchars($table->getEngName()); ?>"></td></tr>
    <tr><td>English name (singular)</td><td><input type="text" name="table_name_single" value="<?= htmlspecialchars ($table->getNameSingle ()); ?>"></td></tr>
    <tr>
        <td>Show record count on sub-tabs</td>
        <td>
            <select name="show_sub_record_count">
<?php
$options = array ();
$options['i'] = array (null, 'Inherit from database');
$options['y'] = array (true, 'Yes');
$options['n'] = array (false, 'No');
$selected = $table->getShowSubRecordCount ();
foreach ($options as $key => $option) {
    if ($option[0] === $selected) {
        echo "                <option value=\"{$key}\" selected>{$option[1]}</option>\n";
    } else {
        echo "                <option value=\"{$key}\">{$option[1]}</option>\n";
    }
}
?>
            </select>
        </td>
    </tr>
    <tr>
        <td>Show search bar by default on main view</td>
        <td>
            <select name="show_search">
<?php
$options = array ();
$options['i'] = array (null, 'Inherit from database');
$options['y'] = array (true, 'Yes');
$options['n'] = array (false, 'No');
$selected = $table->getShowSearch ();
foreach ($options as $key => $option) {
    if ($option[0] === $selected) {
        echo "                <option value=\"{$key}\" selected>{$option[1]}</option>\n";
    } else {
        echo "                <option value=\"{$key}\">{$option[1]}</option>\n";
    }
}
?>
            </select>
        </td>
    </tr>
    <tr><td>Display style</td>
        <td>
            <select name="display_style" onchange="show_tree_options ();">
<?php
$display_styles = array (
    TABLE_DISPLAY_STYLE_ROWS => 'Rows',
    TABLE_DISPLAY_STYLE_TREE => 'Tree'
);

foreach ($display_styles as $display_style => $display_style_name) {
    echo '                <option value="', $display_style, '"';
    if ($table->getDisplayStyle () == $display_style) echo ' selected';
    echo '>', $display_style_name, "</option>\n";
}
?>
            </select>
        </td>
    </tr>
    <tr style="display: none;" id="tree_width" valign="top">
        <td>Max tree node width (chars)</td>
        <td><input type="text" size="2" maxlength="3" name="node_chars" value="<?= $table->getTreeNodeChars (); ?>"></td>
    </tr>
    <tr style="display: none;" id="tree_partition">
        <td>Partition tree by</td>
        <td>
            <select name="partition">
                <option value="">(none)</option>
<?php
$columns = $table->getColumns ();
foreach ($columns as $column) {
    if (!($column instanceof LinkColumn)) continue;
    if ($column->getTarget()->getTable() !== $table) {
        echo '            <option value="', $column->getName(), '"';
        if ($table->getPartition() === $column) echo ' selected';
        echo '>', $column->getName(), ' (', $column->getEngName(),
            ")</option>\n";
    }
}
?>
            </select>
        </td>
    </tr>
    <tr style="display: none;" id="tree_top_level">
        <td><label for="tree_disable_top">Disable top-level nodes for tree</label></td>
        <td><span><input type="checkbox" name="disable_top_nodes" id="tree_disable_top" value="1"<?php
        if (!$table->getTopNodesEnabled ()) echo ' checked'; ?>></span></td>
    </tr>
    <tr><td valign="top">Defined under</td><td>
<?php
$columns = $table->getColumns ();
$lines = array();
$num_joins = 0;
foreach ($columns as $column) {
    if (!($column instanceof LinkColumn)) continue;
    if ($link != null) {
        $num_joins++;
        if ($link->isParent ()) {
            $text = "{$column->getName()} -&gt; ";
            $text .= $link->getToColumn ()->getTable ()->getName ();
            $lines[] = $text;
        }
    }
}
if (count ($lines) == 0) {
    echo 'None</td></tr>';
} else {
    echo implode ('<br>', $lines);
    
    echo '</td></tr>';
    
    if ($num_joins >= 2) {
?>
    <tr>
        <td><label for="joiner">Joiner table</td>
        <td><span><input type="checkbox" name="joiner" id="joiner" value="1"<?php if ($table->isJoiner ()) echo ' checked'; ?>></span></td>
    </tr>
<?php
    }
?>
    <tr>
        <td style="white-space: nowrap;"><label for="parent_disable">Hide parent links when accessed via parent</label></td>
        <td><span><input type="checkbox" name="disable_parent_edit" id="parent_disable" value="1"<?php if ($table->getDisableParentEdit ()) echo ' checked'; ?>></span></td>
    </tr>
<?php
}
?>
    <tr>
        <td><label for="static">Static content</label></td>
        <td><input type="checkbox" name="static" id="static" value="1"<?php if ($table->isStatic ()) echo ' checked'; ?>></td>
    </tr>
    <tr><td colspan="2">
        <br>Comments:
        <br><textarea name="comments" style="width: 100%; height: 5em;"><?= hsc(rem_br($table->getComments ())); ?></textarea>
    </td></tr>
    <tr><td colspan="2">
        <br><a href="table_show_create_query.php?t=<?= urlencode($_GET['t']); ?>">Show table create query</a>
    </td></tr>
    </table>


</td><td style="padding-left: 2em;">

    <table>
    <tr>
        <td>Accessible by</td>
        <td>
            <select name="access_level">
<?php
$access_levels = array (
    TABLE_ACCESS_ADMIN => 'Admins',
    TABLE_ACCESS_SETUP_LIMITED => 'Setup users (incl. those with limited access)',
    TABLE_ACCESS_SETUP_FULL => 'Setup users (only those with full access)'
);
foreach ($access_levels as $key => $val) {
    echo "                <option value=\"{$key}\"";
    if ($key == $table->getAccessLevel ()) echo ' selected="selected"';
    echo ">{$val}</option>\n";
}
?>
            </select>
        </td>
    </tr>
    <tr><td>Display in menu</td><td><label for="menu_y"><input type="radio" name="display" id="menu_y" value="1"<?php
    if ($table->getDisplay () == 1) echo ' checked'; ?>>Yes</label>
    <label for="menu_n"><input type="radio" name="display" id="menu_n" value="0"<?php
    if ($table->getDisplay () == 0) echo ' checked'; ?>>No</label>
    </td></tr>
<?php
$options = array('add', 'edit', 'del', 'export');
foreach ($options as $id => $option) {
    echo "<tr>\n";
    echo "<td>Allow {$option} operations</td>";
?>
        <td><label for="allow_<?= $option; ?>_y"><input type="radio" name="allow_<?= $option; ?>" id="allow_<?= $option; ?>_y" value="1"<?php
    if ($table->getAllowed ($option)) echo ' checked'; ?>>Yes</label>
    <label for="allow_<?= $option; ?>_n"><input type="radio" name="allow_<?= $option; ?>" id="allow_<?= $option; ?>_n" value="0"<?php
    if (!$table->getAllowed ($option)) echo ' checked'; ?>>No</label></td>
    </tr>
<?php
}
?>
    
    <tr><td>Confirm deletion</td><td><label for="confirm_del_y"><input type="radio" name="confirm_del" id="confirm_del_y" value="1"<?php
    if ($table->getConfirmDel ()) echo ' checked'; ?>>Yes</label>
    <label for="confirm_del_n"><input type="radio" name="confirm_del" id="confirm_del_n" value="0"<?php
    if (!$table->getConfirmDel ()) echo ' checked'; ?>>No</label></td></tr>
    
    <tr><td>Cascade deletion</td><td><label for="cascade_del_y"><input type="radio" name="cascade_del" id="cascade_del_y" value="1"<?php
    if ($table->getCascadeDel ()) echo ' checked'; ?>>Yes</label>
    <label for="cascade_del_n"><input type="radio" name="cascade_del" id="cascade_del_n" value="0"<?php
    if (!$table->getCascadeDel ()) echo ' checked'; ?>>No</label></td></tr>
    
    </table>

</td></tr>

    <tr>
        <td>&nbsp;</td>
        <td align="right"><input type="submit" value="Save &gt;&gt;"></td>
    </tr>

</table>
<input type="hidden" name="t" value="<?= hsc($_GET['t']); ?>">
</form>

<?php
require_once 'foot.php';
?>
