<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
require_once ROOT_PATH_FILE. 'tricho/data_setup.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML();
$table = $db->getTable($_POST['t']);
if (!$table) redirect('./');

$url = 'table_edit_main_view.php?t=' . urlencode($_POST['t']);
if ($_POST['action'] == 'Cancel') redirect($url);

// clear the current view
$table->clearView('list');

if (@count ($_POST['desc']) > 0) {
    
    // iterate through the posted arrays and do some cleva stuff
    foreach ($_POST['desc'] as $index => $desc_item) {
        list ($type, $params) = explode ('!!!', $desc_item, 2);
        
        // Process according to type
        switch ($type) {
            // ColumnViewItem
            case 'c':
                $column = $table->get ($params);
                if ($column == null) throw new exception ("Invalid column '{$params}'.");
                
                $view_item = new ColumnViewItem ();
                $view_item->setDetails ($column, true);
                break;
                
            // FunctionViewItem
            case 'f':
                $name = $params;
                $value = str_replace (array ("\r\n", "\r", "\n"), ' ', $_POST['desc_code'][$index]);
                $view_item = new FunctionViewItem ();
                $view_item->setDetails ($name, $value);
                break;
                
            // Huh?
            default:
                throw new exception ("Invalid opcode '{$type}'.");
        }
        
        $table->appendView('list', $view_item);
    }

}

$db->dumpXML('../tables.xml', $url);
?>
