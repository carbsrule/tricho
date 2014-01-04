<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array ('tab' => 'cols');
require 'table_head.php';

$curr_tbl = $db->getTable($_GET['t']);
if ($curr_tbl == null) redirect ('./');

$curr_col = $curr_tbl->get($_GET['t']);
if ($curr_col == null) redirect ('./');
?>
<script language="Javascript" src="table_edit_col_link.js"></script>

<h2>Edit column <?=$curr_col->getName ();?> in table <?=$curr_tbl->getName ();?></h2>
<p>Link from column <?=$curr_col->getName ();?></p>


<?php
$link_data = $curr_col->getLink ();
if ($link_data != null) {
    $link_table = $link_data->getToColumn ()->getTable ();
}

// table
echo "<form action=\"table_edit_col_link_action.php\" method=\"post\" name=\"link\">";
echo "<table>";
echo "<tr><td>to Table</td><td><select name=\"to_table\" onchange=\"update_on_table_change(this.value)\">";
echo "<option value=\"\"></option>";
$tables = $db->getTables ();
usort ($tables, 'table_sorter');

foreach ($tables as $table) {
    if ($link_table != null and $link_table->getName () === $table->getName ()) {
        echo "<option value=\"{$table->getName ()}\" selected>{$table->getName ()}</option>";
    } else {
        echo "<option value=\"{$table->getName ()}\">{$table->getName ()}</option>";
    }
}
echo "</select></td></tr>\n";

// column
echo "<tr><td> &nbsp; &nbsp; Column</td><td><select name=\"to_col\" id=\"to_col\">";
if (isset ($link_table)) {
    $cols = $link_table->getColumns ();
    foreach ($cols as $col) {
        if ($col->getName () === $link_data->getToColumn ()->getName ()) {
            echo "<option value=\"{$col->getName ()}\" selected>{$col->getName ()}</option>";
        } else {
            echo "<option value=\"{$col->getName ()}\">{$col->getName ()}</option>";
        }
    }
} else {
    echo "<option value=\"\">- Select table first -</option>";
}
echo "</select></td></tr>\n";

echo "<tr><td colspan=2>&nbsp;</td></tr>";

// description
echo "<tr><td colspan=2>\n";
echo "<fieldset>\n";
echo "<legend>Describe With</legend>\n";
echo "<div id=\"describe_with\" style=\"display: none;\"></div>\n";
echo "<div id=\"describe_none\" style=\"padding: 0.5em;\">No Description Selected!</div>\n";
echo "</fieldset>\n";
echo "</td></tr>\n";

// add to the description
echo "<tr><td colspan=2>";
echo "<table>";
echo "<tr><td><select name=\"add_col\" id=\"add_col\">";
if (isset ($link_table)) {
    $cols = $link_table->getColumns ();
    foreach ($cols as $col) {
        echo "<option value=\"{$col->getName ()}\">{$col->getName ()}</option>";
    }
} else {
    echo "<option value=\"\">- Select table first -</option>";
}
echo "</select></td>";
echo "<td><input type=\"button\" value=\"Add\" onClick=\"add_desc('c', document.getElementById('add_col').value);\"></td></tr>";
echo "<tr><td><input type=\"text\" name=\"add_text\" id=\"add_text\"></td>";
echo "<td><input type=\"button\" value=\"Add\" onClick=\"add_desc('t', document.getElementById('add_text').value);\"></td></tr>";
echo "</table></td></tr>\n";

echo "<tr><td colspan=2>&nbsp;</td></tr>";

$checked = ' checked="checked"';
if ($link_data != null) {
    $value = $link_data->getOrderingMethod ();
} else {
    $value = ORDER_LINKED_TABLE;
}
echo "<tr><td>Order by</td>\n";
echo "<td><label><input type=\"radio\" name=\"order\" value=\"", ORDER_DESCRIPTORS, "\"",
    ($value == ORDER_DESCRIPTORS? $checked: ''), "> Link descriptors</label>\n";
echo "<label><input type=\"radio\" name=\"order\" value=\"", ORDER_LINKED_TABLE, "\"",
    ($value == ORDER_LINKED_TABLE? $checked: ''), "> Linked table</label>\n";
echo "</td></tr>\n";

// select top item
$value = '';
if ($link_data != null) $value = $link_data->getTopItem ();
echo "<tr><td>Prepend options with</td><td><input type=\"text\" value=\"{$value}\" name=\"top_item\"></td></tr>\n";

if ($link_data != null) $value = $link_data->getFormatType ();
echo "<tr><td>Format</td><td>\n";

echo "    <label><input type=\"radio\" name=\"format_type\" value=\"", LINK_FORMAT_SELECT, "\"",
    (!($value == LINK_FORMAT_RADIO or $value == LINK_FORMAT_INLINE_SEARCH)? $checked: ''), "> Select List</label>\n";

echo "    <label><input type=\"radio\" name=\"format_type\" value=\"", LINK_FORMAT_RADIO, "\"",
    ($value == LINK_FORMAT_RADIO? $checked: ''), "> Radio Buttons</label>\n";

echo "    <label><input type=\"radio\" name=\"format_type\" value=\"", LINK_FORMAT_INLINE_SEARCH, "\"",
    ($value == LINK_FORMAT_INLINE_SEARCH? $checked: ''), "> In-line search</label>\n";

echo "</td></tr>\n";

if ($link_data != null) {
    $is_parent = $link_data->isParent ();
} else {
    $is_parent = false;
}
echo "<tr><td>Is a parent link?</td><td><label for=\"is_parent\" onclick=\"parent_status();\"><input type=\"checkbox\" value=\"1\"". ($is_parent? ' checked': '').
    " name=\"is_parent\" id=\"is_parent\" onchange=\"parent_status();\">".
    "Yes</label></td></tr>\n";

if ($link_data != null) {
    $eng_name = $link_data->getAltEngName ();
} else {
    $eng_name = null;
}
echo "<tr><td>Alternate english name via parent</td><td><input type=\"text\" value=\"",
    $eng_name, "\" name=\"alt\" id=\"alt\"", ($is_parent? '': ' disabled="disabled"'), "></td></tr>\n";

// show counts
if ($link_data != null) {
    $selected = $link_data->getShowRecordCount ();
} else {
    $selected = null;
}
echo '<tr><td>Show record count on tab</td>';
echo '<td><select name="show_record_count">';
$options = array ();
$options['i'] = array (null, 'Inherit from parent table');
$options['y'] = array (true, 'Yes');
$options['n'] = array (false, 'No');
foreach ($options as $key => $option) {
    if ($option[0] === $selected) {
        echo "<option value=\"{$key}\" selected>{$option[1]}</option>\n";
    } else {
        echo "<option value=\"{$key}\">{$option[1]}</option>\n";
    }
}
echo '</select></td></tr>';

echo "<tr><td colspan=2>&nbsp;</td></tr>\n";

// save
echo "<tr><td>&nbsp;</td><td align=right>\n";
echo "<input type=\"hidden\" name=\"action\" value=\"Save\">\n";
echo "<input type=\"button\" value=\"Cancel\" onclick=\"forms.link.action.value='Cancel'; forms.link.submit();\">\n";
echo "<input type=\"submit\" value=\"Save\">\n";
echo "</td></tr>\n";

echo "</table>";
echo "</form>";

if ($link_data != null) {
    $description = $link_data->getDescription ();
    echo "<script language=\"javascript\">\n";
    echo "initing = true;\n";
    $description = $link_data->getDescription ();
    foreach ($description as $item) {
        if (is_object ($item)) {
            echo "add_desc('c', '{$item->getName ()}');\n";
        } else {
            echo "add_desc('t', '{$item}');\n";
        }
    }
    echo "initing = false;\n";
    echo "var up_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_UP . "';\n";
    echo "var down_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_DOWN . "';\n";
    echo "draw_nodes();\n";
    echo "</script>\n";
} else {
    echo "<script language=\"javascript\">\n";
    echo "var up_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_UP . "';\n";
    echo "var down_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_DOWN . "';\n";
    echo "</script>\n";
}
require_once 'foot.php';




function table_sorter ($a, $b) {
    return strcmp ($a->getName (), $b->getName ());
}
?>
