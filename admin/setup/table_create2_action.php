<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login();
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
require_once ROOT_PATH_FILE. 'tricho/data_setup.php';
require 'column_definition.php';

$session = & $_SESSION['setup']['create_table'];

if (is_array($_POST['PRIMARY_KEY'])) {
    $new_primary_key = [];
    foreach ($_POST['PRIMARY_KEY'] as $pk_name) {
        $found = false;
        foreach ($session['columns'] as $col) {
            if ($pk_name == $col['name']) {
                $new_primary_key[] = $pk_name;
                $found = true;
                break;
            }
        }
        if (!$found) throw new Exception('Column missing: ' . $pk_name);
    }
    $session['pk_cols'] = $new_primary_key;
}

if (@count($session['pk_cols']) == 0) {
    $_SESSION['setup']['err'] = 'You must select a primary key';
    redirect('table_create2.php');
}


// Save database meta-data in XML
$db = Database::parseXML();

$table = new Table($session['table_name']);
$table->setEngName($session['table_name_eng']);
$table->setNameSingle($session['table_name_single']);
$table->setAccessLevel($session['access_level']);
$table->setStatic($session['static']);
$table->setDisplay($session['display']);
$table->setDisplayStyle($session['display_style']);
$table->setComments($session['comments']);

$options = array ('add', 'edit', 'del');
foreach ($options as $option) {
    if ($session["allow_{$option}"] == 1) {
        $table->setAllowed($option, true);
    } else {
        $table->setAllowed($option, false);
    }
}
$table->setConfirmDel(true);

$db->addTable($table);
$table->setDatabase($db);

foreach ($session['columns'] as $col) {
    $col = column_config_to_meta($table, 'add', 'table_create1_done.php', $col);
    $table->addColumn($col);
}

$pk_cols = array();
foreach ($session['pk_cols'] as $col_name) {
    $pk_cols[] = $table->get($col_name);
}
$table->addIndex('PRIMARY KEY', $pk_cols);


// Make sure there are no duplicate masks
if ($db !== null) {
    $tables = $db->getTables();
    $other_masks = [];
    foreach ($tables as $other_table) {
        if ($other_table === $table) continue;
        $other_masks[] = $other_table->getMask();
    }
    while (in_array($table->getMask(), $other_masks)) {
        $table->newMask();
    }
}

$search_for = function(array $columns, array $wanted_cols) {
    foreach ($wanted_cols as $wanted) {
        if ($wanted == '0') return reset($columns);
        foreach ($columns as $col) {
            if (strcasecmp($col->getName(), $wanted) == 0) return $col;
        }
    }
    return null;
};


// View order
$columns = $table->getColumns();
$order_col = $search_for($columns, ['ordernum', 'name', 'id', '0']);
if ($order_col) {
    $table->addToOrder('view', $order_col);
}

// Row identifier
$id_col = $search_for($columns, ['name', 'id', '0']);
if ($id_col) {
    $table->setRowIdentifier([$id_col]);
}

// Browse
foreach ($session['columns'] as $col) {
    if (!empty($col['list_view'])) {
        $col = $table->get($col['name']);
        $item = new ColumnViewItem();
        $item->setColumn($col);
        $table->appendView('list', $item);
    }
}

// Add/edit form
$form = new Form();
$form->setTable($table);
$form->setFile("admin.{$table->getName()}");
foreach ($session['columns'] as $col) {
    $apply = [];
    if (!empty($col['add_view'])) $apply[] = 'add';
    if (!empty($col['edit_view_edit'])) {
        $apply[] = 'edit';
    } else if (!empty($col['edit_view_show'])) {
        $apply[] = 'edit-view';
    }
    if (count($apply) == 0) continue;
    $item = new ColumnFormItem($table->get($col['name']));
    $item->setApply(implode(',', $apply));
    $form->addItem($item);
}
FormManager::save($form);

// Save Table metadata
$url = 'table_edit_main_view.php?t=' . urlencode($table->getName());
$db->dumpXML('../tables.xml', null);

// Create database table
$sql = $table->getCreateQuery($session['engine'], $session['collation']);
$res = execq($sql);
$error = $res->errorCode();
if ($error == '00000') {
    log_action($db, "Created table {$table->getName()}", $sql);
    $_SESSION['setup']['msg'] = "Table created.<br>You can now configure this table.";

} else {
    $conn = ConnManager::get_active();
    $_SESSION['setup']['err'] = "Table was not created in " .
        "database due to database error:<br>\n" . $conn->last_error() .
        "<br><br>\nYou can create the database at any time by going to " .
        "<a href=\"./table_show_create_query.php?t=" . hsc($table->getName()) .
        "\">setup/table_show_create_query.php</a>";
}

redirect('table_edit_pre.php?action=Edit&table=' . $table->getName());
