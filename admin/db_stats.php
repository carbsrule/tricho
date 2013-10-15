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

echo "<div id=\"main_data\">\n";

echo "<h2>Database Statistics</h2>";

$_GET['section'] = 'db';
require_once 'tools_tabs.php';

$q = "SHOW TABLE STATUS";
$res = execq($q);

echo "<table class=\"bordered\">\n";
echo "<tr>";
echo "<th>Name</th>";
echo "<th align=\"right\">Columns</th>";
echo "<th align=\"right\">Rows</th>";
echo "<th align=\"right\">Size</th>";
echo "<th align=\"right\">Next auto_inc</th>";
echo "</tr>";

$altrow = 1;
$total_num = 0;
$total_space = 0;
while ($row = fetch_assoc($res)) {
    
    // ignore views
    if ($row['Engine'] === null) continue;
    
    $q = "SHOW COLUMNS FROM `{$row['Name']}`";
    $res2 = execq($q);
    $cols = $res2->rowCount();
    
    $row['Name'] = htmlspecialchars ($row['Name']);
    if ($row['Auto_increment'] == null) $row['Auto_increment'] = '&nbsp;';
    $space = $row['Data_length'] + $row['Index_length'];
    
    $total_cols += $cols;
    $total_space += $space;
    $total_rows += $row['Rows'];
    
    $space = bytes_to_human ($space);
    
    echo "<tr class=\"altrow{$altrow}\">\n";
    if ($altrow == 1) {
        $altrow = 2;
    } else {
        $altrow = 1;
    }
    
    echo "<td>{$row['Name']}</td>";
    echo "<td align=\"right\">{$cols}</td>";
    echo "<td align=\"right\">{$row['Rows']}</td>";
    echo "<td align=\"right\">{$space}</td>";
    echo "<td align=\"right\">{$row['Auto_increment']}</td>";
    echo "</tr>\n";
}

$total_space = bytes_to_human ($total_space);

echo "<tr class=\"altrow{$altrow}\">\n";
echo "<td><b>Total</b></td>";
echo "<td align=\"right\"><b>{$total_cols}</b></td>";
echo "<td align=\"right\"><b>{$total_rows}</b></td>";
echo "<td align=\"right\"><b>{$total_space}</b></td>";
echo "<td>&nbsp;</td>";
echo "</tr>\n";

echo "</table>\n";

echo "</div>\n";

require "foot.php";
?>
