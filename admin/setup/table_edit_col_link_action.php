<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);
require_once ROOT_PATH_FILE. 'tricho/data_setup.php';

// if the user cancelled, do a cancel
if ($_POST['action'] != 'Save') {
    redirect ('./table_edit_cols.php');
}

// get our selected table
$db = Database::parseXML ('../tables.xml');
$curr_tbl = $db->getTable ($_SESSION['setup']['table_edit']['chosen_table']);
if ($curr_tbl == null) redirect ('./');

// get our selected column
$curr_col = $curr_tbl->get ($_SESSION['setup']['table_edit']['chosen_column']);
if ($curr_col == null) redirect ('./');



/* remove the old link if requested */
$old_link = $curr_col->getLink ();
if ($_POST['to_table'] == '') {
    $curr_col->setLink (null);
    try {
        $db->dumpXML ('../tables.xml', null);
        if ($old_link != null) {
            $old_table = $old_link->getToColumn ()->getTable ()->getName ();
            $old_col = $old_link->getToColumn ()->getName ();
            $log_message = "Unlinked column ". $curr_tbl->getName (). '.'. $curr_col->getName ().
                " from {$old_table}.{$old_col}";
            log_action ($db, $log_message);
        }
    } catch (FileNotWriteableException $ex) {
        $_SESSION['setup']['err'] = 'Failed to save XML';
    }
    redirect ('table_edit_cols.php');
}



/* they actually wanted a link. check it's valid */
// determine the link table
$to_table = $db->getTable ($_POST['to_table']);
if ($to_table == null) {
    $_SESSION['setup']['err'] = 'Invalid table';
    redirect ('table_edit_col_link.php');
}

// determine the link column
$to_col = $to_table->get ($_POST['to_col']);
if ($to_col == null) {
    $_SESSION['setup']['err'] = 'Invalid column';
    redirect ('table_edit_col_link.php');
}

// are they stupid? did they forget a description?
if (!isset ($_POST['desc'])) {
    $_SESSION['setup']['err'] = 'No description set!';
    redirect ('table_edit_col_link.php');
}

// type
$format_type = (int) $_POST['format_type'];

/* determine link description */
$desc = array ();
foreach ($_POST['desc'] as $item) {
    list ($type, $value) = explode (':', $item, 2);
    
    if ($type == 'c') {
        // column
        $temp = $to_table->get ($value);
        if ($temp != null) $desc[] = $temp;
        
    } else if ($type == 't') {
        // text
        $desc[] = $value;
    }
}

// show_record_count
switch ($_POST['show_record_count']) {
    case 'y': $show_record_count = true; break;
    case 'n': $show_record_count = false; break;
    case 'i': $show_record_count = null; break;
}

/* do it */
// create the link
$options = array ();
$options['type'] = $format_type;
$options['top_item'] = $_POST['top_item'];
$options['parent'] = (bool) $_POST['is_parent'];
$options['show_cnt'] = $show_record_count;
$options['order'] = $_POST['order'];

// check to column is a PK of the designation table, warn the user if not
$pk_cols = $to_table->getIndex ('PRIMARY KEY');
if (!in_array ($to_col, $pk_cols, true)) {
    $warning = "The link's \"to\" column ". $to_col->getName (). " is not a primary key of the table ".
        $to_table->getName ();
    if ($_SESSION['setup']['warn'] != '') {
        $_SESSION['setup']['warn'] .= "<br/>\n{$warning}";
    } else {
        $_SESSION['setup']['warn'] = $warning;
    }
}

$link = new Link ($curr_col, $to_col, $desc, $options);
if ($options['parent']) {
    $link->setAltEngName ($_POST['alt']);
}
$curr_col->setLink ($link);

// write to tables.xml
try {
    $db->dumpXML ('../tables.xml', null);
    if ($old_link == null or $old_link->getToColumn () !== $link->getToColumn ()) {
        $new_table = $link->getToColumn ()->getTable ()->getName ();
        $new_col = $link->getToColumn ()->getName ();
        $log_message = "Linked column ". $curr_tbl->getName (). '.'. $curr_col->getName ().
            " to {$new_table}.{$new_col}";
        log_action ($db, $log_message);
    }
} catch (FileNotWriteableException $ex) {
    $_SESSION['setup']['err'] = 'Failed to save XML';
}

redirect ('table_edit_cols.php');
?>
