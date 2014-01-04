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

$db = Database::parseXML('../tables.xml');
$table = $db->getTable($_POST['t']);
if (!$table) redirect('./');

$url = 'table_edit_row_view.php?t=' . urlencode($_POST['t']);
if ($_POST['action'] == 'Cancel') redirect($url);

// clear the current view
$table->clearAddEditView ();


if (@count ($_POST['desc']) > 0) {
    
    // iterate through the posted arrays and do some cleva stuff
    foreach ($_POST['desc'] as $index => $desc_item) {
        list ($type, $params) = explode ('!!!', $desc_item, 2);
        
        
        // Add flag
        if ($_POST['desc_add'][$index] == 1) {
            $add_flag = 'y';
        } else {
            $add_flag = 'n';
        }
        
        // Edit flag
        if ($_POST['desc_edit_view'][$index] == 1) {
            $edit_flag = 'y';
        } else {
            $edit_flag = 'n';
        }
        
        // Don't save items that are never displayed, because they'll just get
        // thrown away the next time the XML is loaded anyway
        if ($add_flag == 'n' and $edit_flag == 'n') {
            continue;
        }
        
        // Process according to type
        switch ($type) {
            // ColumnViewItem
            case 'c':
                $column = $table->get($params);
                if ($column == null) throw new exception ("Invalid column '{$params}'.");
                
                if ($_POST['desc_edit_change'][$index] != 1 and $edit_flag == 'y') $edit_flag = 'v';
                
                $view_item = new ColumnViewItem();
                $view_item->setDetails($column, true);
                break;
                
            // HeadingViewItem
            case 'h':
                $view_item = new HeadingViewItem();
                $view_item->setDetails($params);
                break;
                
            // FunctionViewItem
            case 'f':
                $name = $params;
                $value = str_replace (array("\r\n", "\r", "\n"), ' ', $_POST['desc_code'][$index]);
                $view_item = new FunctionViewItem();
                $view_item->setDetails($name, $value);
                break;
                
            // IncludeViewItem
            case 'i':
                $name = $params;
                $file = $_POST['desc_file'][$index];
                $pass = $_POST['desc_pass'][$index];
                $view_item = new IncludeViewItem();
                $view_item->setDetails($file, $name, $pass);
                break;
                
            // Huh?
            default:
                throw new exception ("Invalid type '{$type}'.");
        }
        
        //echo '<pre>' . get_class($view_item) . " {$add_flag} {$edit_flag}</pre>";
        
        $table->appendAddEditView($view_item, $add_flag, $edit_flag);
    }
    
}


$db->dumpXML('../tables.xml', $url);
?>
