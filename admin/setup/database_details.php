<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;

require 'head.php';

$db = Database::parseXML();
$convert_types = array (
    CONVERT_OUTPUT_FAIL => 'Show an error',
    CONVERT_OUTPUT_WARN => 'Show a warning',
    CONVERT_OUTPUT_NONE => 'Silently ignore'
);

$help_table = $db->getHelpTable();
if ($help_table != null) {
  $help_table = $help_table->getName();
}
?>
<h2>Database details</h2>
<form method="post" action="database_details_action.php">
    <table>
        <tr>
            <td><strong>Options</strong></td>
            <td>
                <label for="data_check">
                <input type="checkbox" id="data_check" name="data_check"<?php if ($db->getDataChecking ()) echo ' checked'?> value="1"> Enforce data checking</label>
                <br>
                <label for="primary_heading">
                <input type="checkbox" id="primary_heading" name="primary_heading"<?php if ($db->getShowPrimaryHeadings ()) echo ' checked'?> value="1"> Show primary headings</label>
                <br>
                <label for="section_heading">
                <input type="checkbox" id="section_heading" name="section_heading"<?php if ($db->getShowSectionHeadings ()) echo ' checked'?> value="1"> Show section headings</label>
                <br>
                <label for="show_sub_record_count">
                <input type="checkbox" id="show_sub_record_count" name="show_sub_record_count"<?php if ($db->getShowSubRecordCount ()) echo ' checked'?> value="1"> Show sub-record count in tab</label>
                <br>
                <label for="show_search">
                <input type="checkbox" id="show_search" name="show_search"<?php if ($db->getShowSearch ()) echo ' checked'?> value="1"> Open search bar by default on table main views</label>
            </td>
        </tr>
        <tr>
            <td><strong>Help Table</strong></td>
            <td>
                <input type="text" name="help_table" value="<?=$help_table;?>">
            </td>
        </tr>
        <tr>
            <td><strong>Converted value output</strong></td>
            <td>
                <select name="convert_output">
<?php
foreach ($convert_types as $number => $name) {
    if ($number == $db->getConvertOutput ()) {
        echo "<option selected value=\"{$number}\">{$name}</option>\n";
    } else {
        echo "<option value=\"{$number}\">{$name}</option>\n";
    }
}
?>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="2" align="right"><input type="submit" value="Modify details &gt;&gt;"></td>
        </tr>
    </table>
</form>

<?php
require_once 'foot.php';
?>
