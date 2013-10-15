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
    echo "<h2>Generate tables.xml</h2>";
}

$_GET['section'] = 'gen';
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
if ($res->rowCount() > 0) {
    echo "<p class=\"error\">Please make sure you back up your current tables.xml file before continuing.<br>",
        "Any table definitions which exist in tables.xml may be overwritten.</p>\n";
    echo "<form method=\"post\" action=\"generate_tables_xml_action.php\">\n";
    echo "<table>\n";
    echo "    <tr><th>Table</th><th>Defined in tables.xml</th></tr>\n";
    while ($row = fetch_row($res)) {
        
        if ($row[1] == 'VIEW') continue;
        
        $table_name = $row[0];
        // check to see if table exists in tables.xml
        $existing_table = $db->getTable ($table_name);
        
        echo "    <tr>\n";
        echo "        <td><label class=\"label_plain\" for=\"import_{$table_name}\">",
            "<input type=\"checkbox\" name=\"import[]\" value=\"{$table_name}\" id=\"import_{$table_name}\"";
        if ($existing_table == null) echo ' checked';
        echo ">{$table_name}</label></td>\n";
        echo "        <td>", ($existing_table != null? 'Yes': '&nbsp;'), "</td>\n";
        echo "    </tr>\n";
        
    }
    echo "</table>\n";
    echo "<input type=\"submit\" value=\"Generate tables.xml &raquo;\">\n";
    echo "</form>\n";
    
} else {
    report_error ('No tables defined');
}
?>
</div>

<?php
require "foot.php";
?>
