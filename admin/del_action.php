<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_admin_login ();
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';

$db = Database::parseXML ('tables.xml');
$table = $db->getTable ($_POST['_t']); // use table name
force_redirect_to_alt_page_if_exists($table, 'del_action');

if (!$table->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

$ok = true;

list ($urls, $seps) = $table->getPageUrls ();
$primary_key_cols = $table->getIndex ('PRIMARY KEY');

// possible actions for this page:
// rem (uses del[Primary Key]), add, prev (uses start_prev), next (uses start_next)


$table_eng_name = $table->getEngName ();
$table_single_name = $table->getNameSingle ();

// Load alternate names if they have been specified
if (@$_POST['_p']) {
    $ancestors = explode (',', $_POST['_p']);
    
    if (count($ancestors) > 0) {
        list ($parent_table) = explode ('.', $ancestors[0]);
        
        $parent_table = $db->getTable ($parent_table);
        if ($parent_table == null) {
            report_error ("Invalid ancestor {$ancestor_name}");
            die();
        }
        
        $col = $table->getLinkToTable ($parent_table);
        
        $alt_name = $col->getLink ()->getAltEngName ();
        if ($alt_name) $table_eng_name = $alt_name;
        
        // Ready for the future
        //$alt_name = $col->getLink()->getAltSingleName();
        //if ($alt_name) $table_single_name = $alt_name;
    }
}


    
// delete selected rows
if (@count ($_POST['del']) > 0) {
    
    $record_pks = array ();
    foreach ($_POST['del'] as $item_to_del => $val) {
        if ($val == 1) {
            $record_pks[] = explode (',', $item_to_del);
        }
    }
    
    // do the delete
    $num_removed = $table->deleteRecords ($record_pks);
    
    // confirmation message
    switch ($num_removed) {
        case 0: $_SESSION[ADMIN_KEY]['err'] = "Deletion failed"; break;
        case 1: $_SESSION[ADMIN_KEY]['msg'] = "1 {$table_single_name} deleted successfully"; break;
        default: $_SESSION[ADMIN_KEY]['msg'] = "{$num_removed} {$table_eng_name} deleted successfully"; break;
    }
    
} else {
    // user didnt select anything
    $_SESSION[ADMIN_KEY]['err'] = "No {$table_eng_name} were selected for deletion!";
}


// return
$url = "{$urls['browse']}{$seps['browse']}t=" . urlencode($_POST['_t']);
if (@$_POST['_p'] != '') $url .= "&p={$_POST['_p']}";
redirect ($url);
