<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array ('tab' => 'row');
require 'table_head.php';
?>

<script language="Javascript" src="table_edit_row_view.js"></script>


<form action="table_edit_row_view_action.php" method="post" name="link">
<table>

<!-- the field area -->
<tr><td style="padding: 1em; width: 650px; vertical-align: top;">
<fieldset>
<legend>Items to show on the add/edit views</legend>
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
    <input type="button" value="Add" onClick="add_desc('c', document.getElementById('add_col').value, [1,1,1]);">
</td></tr>

<!-- add new heading -->
<tr><td colspan="2"><strong>Add heading</strong></td></tr>
<tr><td>Text:</td><td><input type="text" name="add_text" id="add_text" class="add_view_item"></td></tr>
<tr><td colspan="2" align="right">
    <input type="button" value="Add" onClick="add_desc('h', document.getElementById('add_text').value, [1,1,1]);">
</td></tr>

<!-- add new function -->
<tr><td colspan="2"><strong>Add function</strong></td></tr>
<tr><td>Name:</td><td><input type="text" name="add_func" id="add_func_name" class="add_view_item"></td></tr>
<tr><td>Code:</td><td><textarea name="add_func" id="add_func_code" class="add_view_item"></textarea></td></tr>
<tr><td colspan="2" align="right">
    <input type="button" value="Add" onClick="add_desc('f', [document.getElementById('add_func_name').value, document.getElementById('add_func_code').value], [1,1,1]);">
</td></tr>

<!-- add new include -->
<tr><td colspan="2"><strong>Add include</strong></td></tr>
<tr><td>Name:</td><td><input type="text" name="add_inc" id="add_inc_name" class="add_view_item"></td></tr>
<tr><td>File:</td><td><input type="text" name="add_inc" id="add_inc_file" class="add_view_item"></td></tr>
<tr><td>Passthru:</td><td><input type="text" name="add_inc" id="add_inc_pass" class="add_view_item"></td></tr>
<tr><td colspan="2" align="right">
    <input type="button" value="Add" onClick="add_desc('i', [document.getElementById('add_inc_name').value,document.getElementById('add_inc_file').value,document.getElementById('add_inc_pass').value], [1,1,1]);">
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
echo "\niniting = true;\n";
$view_items = $table->getAddEditView ();

foreach ($view_items as $aev_item) {
    $item = $aev_item['item'];
    
    $checkboxes = ($aev_item['add'] ? 'true' : 'false');
    $checkboxes .= ','. ($aev_item['edit_view'] ? 'true' : 'false'). ','.
        ($aev_item['edit_change'] ? 'true' : 'false');
    
    if ($item instanceof ColumnViewItem) {
        if ($item->getColumn ()->getOption () != 'ordernum') {
            echo "add_desc('c', '{$item->getColumn ()->getName ()}', [{$checkboxes}]);\n";
        }
        
    } elseif ($item instanceof HeadingViewItem) {
        echo "add_desc('h', '", addslashes ($item->getName ()), "', [{$checkboxes}]);\n";
        
    } elseif ($item instanceof FunctionViewItem) {
        echo "add_desc('f', ['", addslashes ($item->getName ()),
            "', '", addslashes ($item->getCode ()), "'], [{$checkboxes}]);\n";
        
    } elseif ($item instanceof IncludeViewItem) {
        echo "add_desc('i', ['", addslashes ($item->getName ()),
            "', '{$item->getFilename()}', '", addslashes ($item->getPassthroughValue ()), "'], [{$checkboxes}]);\n";
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
