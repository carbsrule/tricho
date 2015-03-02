<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';
require_once 'setup_functions.php';

$page_opts = array ('tab' => 'detail');
require 'table_head.php';

report_session_info ('err', 'setup');

// The engine, charset and collation information need to match the existing table, if it exists
$engine = '';
$table_collation = '';
$res = execq("SHOW TABLE STATUS");
while ($row = fetch_assoc($res)) {
    if ($row['Name'] == $table->getName ()) {
        if ($row['Engine'] != '') {
            $engine = $row['Engine'];
        } else if ($row['Type'] != '') {
            $engine = $row['Type'];
        }
        $table_collation = $row['Collation'];
        break;
    }
}
$res = null;

$column_collations = array ();
$q = new RawQuery("SHOW FULL COLUMNS FROM `{$table->getName ()}`");
$q->set_internal(true);
try {
    $res = execq($q);
    while ($row = @fetch_assoc($res)) {
        if ($row['Collation'] != '' and $row['Collation'] != $table_collation) {
            $column_collations[$row['Field']] = $row['Collation'];
        }
    }
} catch (QueryException $q) {
    // It's OK if the table doesn't exist at this point
}

$create_query = $table->getCreateQuery ($engine, $table_collation, $column_collations);

echo '<h4>Table creation query</h4>';
echo '<pre>';
echo $create_query;
echo '</pre>';

echo "<form method=\"post\" action=\"../sql.php\" id=\"create_sql\" style=\"display: none;\">\n";
echo '<input type="hidden" name="query" value="', htmlspecialchars ($create_query), "\">\n";
echo "</form>\n";

echo "<p><a href=\"#\" onclick=\"document.getElementById ('create_sql').submit (); return false;\">",
    "&raquo; Run this query</a></p>\n";

$drop_query = "DROP TABLE IF EXISTS `{$table->getName()}`";

echo '<h4>Table destruction query</h4>';
echo "<pre>{$drop_query}</pre>";

echo "<form method=\"post\" action=\"../sql.php\" id=\"drop_sql\" style=\"display: none;\">\n";
echo '<input type="hidden" name="query" value="', htmlspecialchars ($drop_query), "\">\n";
echo "</form>\n";

echo "<p><a href=\"#\" onclick=\"document.getElementById ('drop_sql').submit (); return false;\">",
    "&raquo; Run this query</a></p>\n";


echo '<p>Other queries can be run via the <a href="../sql.php">MySQL query tool</a></p>';


require 'foot.php';
?>
