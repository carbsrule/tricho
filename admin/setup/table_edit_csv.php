<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array ('tab' => 'csv');
require 'table_head.php';
?>

<script language="Javascript" src="table_edit_csv.js"></script>


<form action="table_edit_csv_action.php" method="post" name="link">
<table>

<!-- the field area -->
<tr><td style="padding: 1em; width: 650px; vertical-align: top;">
<fieldset>
<legend>Items to export</legend>
<table id="describe_with" style="display: none; width: 100%;"></table>
<div id="describe_none" style="padding: 0.5em;">No columns are currently used</div>
</fieldset>
</td>


<td style="padding: 1em; vertical-align: top; padding-right: 0px;">
<table>

<!-- add new column -->
<tr><td colspan="2"><strong>Add column</strong></td></tr>
<tr><td>Name:</td>
    <td><select name="add_col" id="add_col" class="add_view_item">
<?php
$cols = $table->getColumns ();
foreach ($cols as $col) {
    if ($col->hasLink ()) {
        echo "<option value=\"{$col->getName ()}\">{$col->getName ()}*</option>";
    } else {
        echo "<option value=\"{$col->getName ()}\">{$col->getName ()}</option>";
    }
}
?>
    </select>
</td></tr>
<tr><td colspan="2" align="right">
    <input type="button" value="Add" onClick="add_desc('c', document.getElementById('add_col').value, [1,1,1,1]);">
</td></tr>


<!-- add new function -->
<tr><td colspan="2"><strong>Add function</strong></td></tr>
<tr><td>Name:</td><td><input type="text" name="add_func" id="add_func_name" class="add_view_item"></td></tr>
<tr><td>Code:</td><td><textarea name="add_func" id="add_func_code" class="add_view_item"></textarea></td></tr>
<tr><td colspan="2" align="right">
    <input type="button" value="Add" onClick="add_desc('f', [document.getElementById('add_func_name').value, document.getElementById('add_func_code').value], [1,1,1,1]);">
</td></tr>

</table></td></tr>


<!-- save and cancel -->
<tr><td>&nbsp;</td><td align="right" style="padding-top: 2em;">
<input type="hidden" name="action" value="Save">
<input type="button" value="Cancel" onclick="forms.link.action.value='Cancel'; forms.link.submit();">
<input type="submit" value="Save">
</td></tr>

</table>
</form>


<?php
echo "<script language=\"javascript\">\n";
echo "initing = true;\n";

// create an array of the link desciptions (i.e. "-> table.column (parent)?")
echo "var link_info = [];\n";
$cols = $table->getColumns ();
foreach ($cols as $col) {
    if ($col->hasLink ()) {
        $to_col = $col->getLink ()->getToColumn ();
        $link_info = " -> {$to_col->getTable ()->getName ()}.{$to_col->getName ()}";
        if ($col->getLink ()->isParent ()) $link_info .= ' (parent)';
        
        $col_name = str_replace ("'", "\'", $col->getName ());
        $link_info = str_replace ("'", "\'", $link_info);
        echo "link_info['{$col_name}'] = '{$link_info}';\n";
    }
}

// initialise the current identifiers
$view_items = $table->getView('export');
foreach ($view_items as $item) {
    if ($item instanceof ColumnViewItem) {
        echo "add_desc('c', '{$item->getColumn ()->getName ()}', [1,1,1,1]);\n";
        
    } elseif ($item instanceof FunctionViewItem) {
        echo "add_desc('f', ['{$item->getName ()}', \"{$item->getCode ()}\"], [1,1,1,1]);\n";
        
    } else {
        
    }
}
echo "initing = false;\n";
echo "var up_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_UP . "';\n";
echo "var down_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_DOWN . "';\n";
echo "draw_nodes();\n";
echo "</script>\n";


require 'foot.php';
?>
