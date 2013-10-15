<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array ('tab' => 'ident');
require 'table_head.php';
?>

<script language="Javascript" src="table_edit_col_link.js"></script>
<h2>Edit identifier for table <?=$table->getName ();?></h2>

<?php
$identifier = $table->getRowIdentifier ();

echo "<form action=\"table_edit_identifier_action.php\" method=\"post\" name=\"link\">";
echo "<table>";

// description
echo "<tr><td colspan=2>\n";
echo "<fieldset>\n";
echo "<legend>Identify with</legend>\n";
echo "<div id=\"describe_with\" style=\"display: none;\"></div>\n";
echo "<div id=\"describe_none\" style=\"padding: 0.5em;\">No Description Selected!</div>\n";
echo "</fieldset>\n";
echo "</td></tr>\n";

// add to the description
echo "<tr><td colspan=2>";
echo "<table>";
echo "<tr><td><select name=\"add_col\" id=\"add_col\">";
$cols = $table->getColumns ();
foreach ($cols as $col) {
    echo "<option value=\"{$col->getName ()}\">{$col->getName ()}</option>";
}
echo "</select></td>";
echo "<td><input type=\"button\" value=\"Add\" onClick=\"add_desc('c', document.getElementById('add_col').value);\"></td></tr>";
echo "<tr><td><input type=\"text\" name=\"add_text\" id=\"add_text\"></td>";
echo "<td><input type=\"button\" value=\"Add\" onClick=\"add_desc('t', document.getElementById('add_text').value);\"></td></tr>";
echo "</table></td></tr>\n";

// save
echo "<tr><td>&nbsp;</td><td align=right>\n";
echo "<input type=\"hidden\" name=\"action\" value=\"Save\">\n";
echo "<input type=\"button\" value=\"Cancel\" onclick=\"forms.link.action.value='Cancel'; forms.link.submit();\">\n";
echo "<input type=\"submit\" value=\"Save\">\n";
echo "</td></tr>\n";

echo "</table>";
echo "</form>";



// initialise the current identifiers
echo "<script language=\"javascript\">\n";
echo "initing = true;\n";
foreach ($identifier as $item) {
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


require 'foot.php';
?>
