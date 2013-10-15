<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';
?>
<div id="main_data">
<form method="post" action="tables_import_action.php" enctype="multipart/form-data">
<?php
if ($db->getShowSectionHeadings ()) {
    echo "<h2>Import SQL table and column definitions from XML</h2>\n";
}

$_GET['section'] = 'db';
require_once 'tools_tabs.php';
?>
<p class="error">This function is experimental - please ensure you have backed up both the database and
the tables.xml file before continuing.</p>
<?php check_session_response (ADMIN_KEY); ?>
<table>
    <tr><th colspan="2" align="left">File</th></tr>
    <tr>
        <td><input type="radio" name="data_from" value="self" id="data_from_self" onclick="if(this.checked)document.getElementById('file').value = '';"></td>
        <td><label for="data_from_self">Use current tables.xml</label></td>
    </tr>
    <tr>
        <td><input type="radio" name="data_from" value="file" id="data_from_file"></td>
        <td><label for="data_from_file">Use file: </label><input type="file" name="file" id="file" onchange="document.getElementById('data_from_file').checked = true;"></td>
    </tr>
    
    <tr><th colspan="2" align="left">Options</th></tr>
    <tr valign="top">
        <td><input type="checkbox" name="options[]" value="modify" id="option_modify"></td>
        <td><label for="option_modify">Modify existing columns (in database and XML definition) to match the XML file provided</label></td>
    </tr>
    <tr>
        <td><input type="checkbox" name="options[]" value="delete" id="option_delete"></td>
        <td><label for="option_delete">Delete existing columns (in database and XML definition) that aren't found in the XML file provided</label></td>
    </tr>
    
    <tr><th colspan="2" align="left">Mode</th></tr>
    <tr>
        <td><input type="radio" name="mode" value="test" checked id="option_test"></td>
        <td><label for="option_test">Test - show queries that would be executed, rather than executing them</label></td>
    </tr>
    <tr>
        <td><input type="radio" name="mode" value="terse" id="option_terse"></td>
        <td><label for="option_terse">Terse - summmarise changes made</label></td>
    </tr>
    <tr>
        <td><input type="radio" name="mode" value="verbose" id="option_verbose"></td>
        <td><label for="option_verbose">Verbose - show all database and XML changes made</label></td>
    </tr>
    <tr><td colspan="2" align="right"><input type="submit" value="Import"></td></tr>
</table>
</form>
</div>

</body>
</html>
