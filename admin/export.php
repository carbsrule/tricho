<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';

test_setup_login (true, SETUP_ACCESS_LIMITED);

$_GET['t'] = '__tools';
require 'head.php';
?>
<script type="text/javascript">
function show_export_options (sel) {
    var div = document.getElementById ('custom_mode');
    if (sel.value == 'custom') {
        div.style.display = '';
    } else {
        div.style.display = 'none';
    }
}
function set_all_to() {
    val = document.getElementById ('set_all').value;
    nodes = document.getElementsByTagName ('select');
    
    for (var x = 0; x < nodes.length; x++) {
        nodes[x].value = val;
        nodes[x].className = nodes[x].options[nodes[x].selectedIndex].className;
    }
}
</script>
<div id="main_data">
<?php
$res = execq("SHOW TABLE STATUS");
$db_tables = array ();
$tables_not_defined = array ();
$db = Database::parseXML ('tables.xml');
while ($db_row = fetch_assoc($res)) {
    $engine = $db_row['Engine'];
    
    // It's pointless to export views
    if ($engine == null) continue;
    
    $db_tables[] = array ('name' => $db_row['Name'], 'size' => bytes_to_human ($db_row['Data_length']));
    
    $table = $db->getTable ($db_row['Name']);
    if ($table == null) {
        $tables_not_defined[] = $db_row['Name'];
    }
}

$num_tables = count ($db_tables);
if ($num_tables > 0) {
?>
<form method="post" name="export" action="export_action.php">

<h2>Export Database</h2>

<?php
$_GET['section'] = 'db';
require_once 'tools_tabs.php';

if (count ($tables_not_defined) > 0) {
    echo "<p class=\"error\">The following tables are not defined in the XML: ". implode (', ', $tables_not_defined). "</p>\n";
}
?>

<p>
    <label><input type="radio" name="mode" value="custom" checked onclick="show_export_options(this);">Custom export </label><br>
    <label><input type="radio" name="mode" value="essential" onclick="show_export_options(this);">Essential data export </label>
</p>

<div id="custom_mode">
    <p>Set all to
        <select class="export_no" id="set_all" onchange="set_all_to(); this.className = this.options[this.selectedIndex].className; return false;">
            <option value="" class="export_no" selected>Do not export</option>
            <option value="sd" class="export_struct_data">Structure &amp; Data</option>
            <option value="s" class="export_struct">Structure Only</option>
            <option value="d" class="export_data">Data Only</option>
            <option value="u" class="export_update">Data as INSERT/UPDATE</option>
        </select>
        <input type="button" onclick="set_all_to(); return false;" value="Set">
    </p>
    
    <table style="margin-top: 10px;" class="export_tables">
<?php
$max_cols = 3;

$per_col = 8;
$num_cols = ceil ($num_tables / $per_col);
if ($num_cols > $max_cols) {
    $per_col = ceil ($num_tables / $max_cols);
    $num_cols = $max_cols;
}

$i = 0;
$cells = array ();
foreach ($db_tables as $db_table) {
    $row = $i % $per_col;
    $col = floor ($i / $per_col);
    $cells[$col][$row] = $db_table;
    $i++;
}
$num_rows = count ($cells[0]);

$j = 0;
for ($row = 0; $row < $num_rows; $row++) {
    echo "<tr>\n";
    for ($col = 0; $col < $num_cols; $col++) {
        echo "<td>";
        if (isset ($cells[$col][$row])) {
            echo "{$cells[$col][$row]['name']} ({$cells[$col][$row]['size']})</td><td>";
            echo "<select name=\"tables[{$cells[$col][$row]['name']}]\" onchange=\"this.className = this.options[this.selectedIndex].className;\" class=\"export_struct_data\">";
            echo "<option class=\"export_no\" value=\"\">Do not export</option>";
            echo "<option class=\"export_struct_data\" value=\"sd\" selected>Structure &amp; Data</option>";
            echo "<option class=\"export_struct\" value=\"s\">Structure Only</option>";
            echo "<option class=\"export_data\" value=\"d\">Data Only</option>";
            echo "<option class=\"export_update\" value=\"u\">Data as INSERT/UPDATE</option>";
            echo "</select>";
            
        } else {
            echo "&nbsp;</td><td>&nbsp;";
        }
        echo "</td>\n";
    }
    echo "</tr>\n";
}
?>
    </table>
</div>

<table>
    <tr>
        <td>
            <br><label class="label_plain" for="inc_del"><input type="checkbox" id="inc_del" name="inc_del" value="1" checked>
                Include DROP TABLE pre-queries</label>
            
            <br><label class="label_plain" for="dl"><input type="checkbox" id="dl" name="dl" value="1" checked>
                Download as file</label>
        </td>
    </tr>
    <tr>
        <td>
            <input type="submit" value="Export data">
        </td>
    </tr>
</table>
</form>
<?php
} else {
    echo "No tables defined";
}
?>
</div>

<?php
require "foot.php";
?>
