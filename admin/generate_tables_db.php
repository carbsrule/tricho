<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_setup_login (true, SETUP_ACCESS_FULL);

$_GET['t'] = '__tools';
require 'head.php';
?>


<div id="main_data">

<?php
if ($db->getShowSectionHeadings ()) {
    echo "<h2>Generate database tables</h2>";
}

$_GET['section'] = 'db';
require_once 'tools_tabs.php';


check_session_response (array (ADMIN_KEY, 'setup'));

// Views are supported since MySQL 5 - ignore them
$res = execq("SELECT VERSION() as v");
$row = fetch_assoc($res);
list ($major_version, $junk) = explode ('.', $row['v']);

if ($major_version < 5) {
    $q = 'SHOW TABLES';
} else {
    $q = 'SHOW FULL TABLES';
}

$res = execq($q);
$database_tables = array ();
while ($row = fetch_row($res)) {
    if ($row[1] == 'VIEW') continue;
    $database_tables[] = $row[0];
}
$res = null;

$xml_tables = $db->getOrderedTables ();
if (count ($xml_tables) > 0) {
    
    echo "<form method=\"post\" name=\"generate_tables_db\" action=\"generate_tables_db_action.php\">\n";
    
    echo "<p>\n";
    echo "<input type=\"button\" value=\"Select All\" onclick=\"checkall('generate_tables_db', 'import'); return false;\"/>\n";
    echo "<input type=\"button\" value=\"Select None\" onclick=\"uncheckall('generate_tables_db', 'import'); return false;\"/>\n";
    echo "</p>\n";
    
    echo "<table>\n";
    echo "    <tr><th>Table</th><th>Defined in database</th></tr>\n";
    foreach ($xml_tables as $table) {
        
        $table_name = $table->getName ();
        
        // check to see if table exists in database
        $existing_table = in_array ($table_name, $database_tables);
        
        echo "    <tr>\n";
        echo "        <td><label class=\"label_plain\" for=\"import_{$table_name}\">",
            "<input type=\"checkbox\" name=\"import[]\" value=\"{$table_name}\" id=\"import_{$table_name}\"";
        if (!$existing_table) echo ' checked';
        echo ">{$table_name}</label></td>\n";
        echo "        <td>", ($existing_table? 'Yes': '&nbsp;'), "</td>\n";
        echo "    </tr>\n";
        
    }
    echo "</table>\n";
    echo "<input type=\"submit\" value=\"Generate CREATE TABLE queries &raquo;\">\n";
    echo "</form>\n";
    
} else {
    report_error ('No tables defined');
}
?>
</div>

<?php
require "foot.php";
?>
