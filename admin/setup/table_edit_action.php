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
require_once 'setup_functions.php';

$db = Database::parseXML ('../tables.xml');

$table = $db->getTable ($_SESSION['setup']['table_edit']['chosen_table']);

if ($table == null) {
    redirect ('./');
}

$_POST['table_name'] = trim ($_POST['table_name']);

// check for single name
if (trim ($_POST['table_name_single']) == '') {
    $_SESSION['setup']['err'] = 'You need a single name for your table.';
    redirect ('table_edit.php');
}

$q = '';
$old_name = $_SESSION['setup']['table_edit']['chosen_table'];
if ($_POST['table_name'] != $old_name) {
    // rename table in database
    if (table_name_valid ($_POST['table_name'])) {
        $q = "ALTER TABLE `{$_SESSION['setup']['table_edit']['chosen_table']}`
            RENAME TO `" . $_POST['table_name'] . "`";
        if (execq($q, false)) {
            $table->setName ($_POST['table_name']);
            $_SESSION['setup']['table_edit']['chosen_table'] = $_POST['table_name'];
        } else {
            $_SESSION['setup']['err'] = 'Table name was not changed due to a database error';
        }
    } else {
        $_SESSION['setup']['err'] = 'Table name was not changed due to an invalid table name';
    }
}

$table->setEngName ($_POST['table_name_eng']);
$table->setNameSingle ($_POST['table_name_single']);
$table->setAccessLevel ($_POST['access_level']);

$table->setJoiner (false);
if ($_POST['joiner'] == 1) $table->setJoiner (true);
// $table->setHomePage ($_POST['home']);
$table->setDisplay ($_POST['display']);
$table->setDisplayStyle ($_POST['display_style']);

$table->setStatic ($_POST['static'] == 1? true: false);

$table->setComments ($_POST['comments']);
$options = array('add', 'edit', 'del', 'export');
foreach ($options as $id => $option) {
    if ($_POST["allow_{$option}"] == 1) {
        $table->setAllowed ($option, true);
    } else {
        $table->setAllowed ($option, false);
    }
}
$table->setConfirmDel ($_POST['confirm_del']);
$table->setCascadeDel ($_POST['cascade_del']);

if ($_POST['disable_top_nodes'] == 1) {
    $table->setTopNodesEnabled (false);
} else {
    $table->setTopNodesEnabled (true);
}

if ($_POST['disable_parent_edit'] == 1) {
    $table->setDisableParentEdit (true);
} else {
    $table->setDisableParentEdit (false);
}

switch ($_POST['show_sub_record_count']) {
    case 'y': $table->setShowSubRecordCount (true); break;
    case 'n': $table->setShowSubRecordCount (false); break;
    case 'i': $table->setShowSubRecordCount (null); break;
}

switch ($_POST['show_search']) {
    case 'y': $table->setShowSearch (true); break;
    case 'n': $table->setShowSearch (false); break;
    default: $table->setShowSearch (null); break;
}

if ($_POST['display_style'] == TABLE_DISPLAY_STYLE_TREE) {
    $partition_col = $table->get ($_POST['partition']);
    if ($partition_col == null) {
        $table->clearPartition ();
    } else {
        $table->setPartition ($partition_col);
    }
    
    settype ($_POST['node_chars'], 'int');
    $table->setTreeNodeChars ($_POST['node_chars']);
}

try {
    $db->dumpXML ('../tables.xml', null);
    $log_message = "Changed table {$old_name}";
    if ($_POST['table_name'] != $old_name) {
        $log_message .= " - renamed to {$_POST['table_name']}";
    }
    log_action ($db, $log_message, $q);
} catch (FileNotWriteableException $ex) {
    $_SESSION['setup']['err'] = 'Failed to save XML';
}
redirect ('table_edit.php');
?>
