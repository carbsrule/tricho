<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

$_GET['t'] = '__tools';
require_once 'head.php';

if (@$_GET['section'] == '') $_GET['section'] = 'gen';

if (test_setup_login (false, SETUP_ACCESS_FULL)) {
    $show_all_tools = true;
} else {
    $show_all_tools = false;
}
?>

<div id="main_data">

<h2>Tools</h2>

<?php
require_once 'tools_tabs.php';
?>



<?php
switch ($_GET['section']) {
    case 'gen':
?>
<h4>General tools</h4>
<ul>
    <li><a href="info.php">Info</a> <small>get info about Tricho, PHP, MySQL, and view session</small></li>
<?php
        if ($show_all_tools) {
?>
    <li><a href="info.php?view=session">View session</a> <small>view the PHP session</small></li>
<?php
        }
?>
    <li><a href="export_files.php">Export files</a> <small>export directories that have admin-created files (e.g. dbfiles, xstd_files, ...)</small></li>
</ul>
<ul>
    <li><a href="generate_password.php">Generate a password</a></li>
<?php
        
        if ($db->getHelpTable () != null) {
            echo "<li><a href=\"main.php?t={$db->getHelpTable ()->getName ()}\">Edit On-Line Help</a>",
                " <small>add or edit the help messages for columns</small></li>";
        }
        echo '</ul>';
    break;
        
        
    case 'db':
?>
<h4>Database tools</h4>
<ul>
    <li><a href="sql.php">Execute MySQL queries</a> <small>direct database access</small></li>
    <li><a href="search_all.php">Search and replace</a> <small>search and optionally replace in all columns in all tables</small></li>
    <li><a href="struct.php">View structure</a> <small>a quick reference of the tables and columns</small></li>
    <li><a href="db_stats.php">View statistics</a> <small>shows the size, number of rows, etc. for each table</small></li>
</ul>
<ul>
    <li><a href="export.php">Export database</a> <small>generates a .sql file</small></li>
    
<?php
        if ($show_all_tools) {
?>
    <li><a href="import_csv.php">Import Data from a file</a> <strong style="color: #CC0000;">BROKEN</strong> <small>import some data from a csv file</small></li>
    <li><a href="generate_tables_xml.php">Generate tables.xml</a> <small>from the existing database</small></li>
    <li><a href="generate_tables_db.php">Generate database tables</a> <small>from the current tables.xml file</small></li>
    <li><a href="tables_import.php">Import SQL structure</a> <small>from an existing tables.xml file</small></li>
    <li><a href="table_column_comments.php">View database comments</a> <small>all of the comments defined in the setup</small></li>
<?php
        }
?>
</ul>
<?php
        break;
        
        
    case 'err':
?>
<h4>Error detection and correction tools</h4>
<ul>
    <li><a href="fix_ordernum.php">Fix order numbers</a> <small>if order numbers become discontiguous and thus ordering stops working</small></li>
</ul>
<?php
        if ($show_all_tools) {
?>
<ul>
    <li><a href="data_validate.php">Data Validator</a> <small>check that the data in the database matches the type definitions saved in XML</small></li>
    <li><a href="check_links.php">Check links</a> <small>check that all the linked rows have valid data, and that the field types on both sides of the links match</small></li>
    <li><a href="check_dates.php">Check dates</a> <small>check that all the date field definitions are valid</small></li>
    <li><a href="check_ordernums.php">Check OrderNum columns</a> <small>checks that OrderNum columns are correctly in the main view order options</small></li>
</ul>
<?php
        }
        break;
        
        
    default:
        echo "<p>Invalid section</p>";
}


echo "</div>\n\n";

require_once 'foot.php';
?>
    
