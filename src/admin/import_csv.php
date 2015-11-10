<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';

test_setup_login (true, SETUP_ACCESS_FULL);

$_GET['t'] = '__tools';
require_once 'head.php';

echo "\n<div id=\"main_data\">\n\n";

if ($db->getShowSectionHeadings ()) {
    echo "<h2>Import Data</h2>";
}

$_GET['section'] = 'db';
require_once 'tools_tabs.php';


check_session_response (ADMIN_KEY);
?>

<form action="import_csv_action.php" method="post" enctype="multipart/form-data">
    <table>
        <tr>
            <td>CSV File to import</td>
            <td><input type="file" name="csv"></td>
        </tr>
        <tr>
            <td>Table to import data into</td>
            <td>
                <?php
                    $tables = $db->getTables ();
                    if (count ($tables) == 0) {
                        echo '<em>Nothing Available</em>';
                    } else {
                        echo "<select name=\"table\">\n";
                        foreach ($tables as $table) {
                            echo "\t<option value=\"{$table->getName ()}\">{$table->getName ()}</option>\n";
                        }
                        echo "\t</select>\n";
                    }
                ?>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td><input type="submit" value="Upload Data"></td>
        </tr>
    </table>
</form>

</div>

<?php
require_once 'foot.php';
?>
