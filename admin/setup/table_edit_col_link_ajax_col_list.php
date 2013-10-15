<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';

header('Content-type: text/xml');

$db = Database::parseXML ('../tables.xml');
$table = $db->getTable ($_GET['table']);

echo "<select name=\"{$_GET['name']}\" id=\"{$_GET['name']}\">";
if ($table == null) {
    echo "<option value=\"\">- Select table first -</option>";
    
} else {
    $cols = $table->getColumns ();
    foreach ($cols as $col) {
        echo "<option value=\"{$col->getName ()}\">{$col->getName ()}</option>";
    }
}
echo '</select>';
?>
