<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\DataUi\FormManager;
use Tricho\Meta\Database;
use Tricho\Meta\Table;
use Tricho\Meta\ColumnViewItem;
use Tricho\Meta\FileColumn;
use Tricho\Meta\LinkColumn;

require_once '../../tricho.php';
require_once 'setup_functions.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML();
$source = $db->get($_POST['source']);
$error_url = 'table_copy.php?table=' . urlencode($_POST['source']);
if ($source and $source->getAccessLevel() == TABLE_ACCESS_SETUP_FULL and
        $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
    $source = null;
}

if ($source == null) {
    $_SESSION['setup']['err'] = 'Unknown source table';
    redirect($error_url);
}

$extant_dest = $db->get($_POST['dest']);
if ($extant_dest != null) {
    $_SESSION['setup']['err'] = 'Target already exists: ' .
        hsc($_POST['dest']);
    redirect($error_url);
}

$dest = new Table($_POST['dest']);
$dest->setEngName(trim($_POST['dest_eng']));
$dest->setNameSingle(trim($_POST['dest_single']));
foreach ($source->getColumns() as $col) {
    $new_col = clone $col;
    $new_col->setTable($dest);
    $dest->addColumn($new_col);
    if ($new_col instanceof FileColumn) {
        $new_col->newMask();
    }
}

// Self-links from original table need to be remapped to new table
foreach ($dest->getColumns() as $col) {
    if (!($col instanceof LinkColumn)) continue;
    $target = $col->getTarget();
    if ($target->getTable() !== $source) continue;
    $col->setTarget($dest->get($target->getName()));
}

// Copy PK
$pk_cols = array();
foreach ($source->getPKnames() as $pk_name) {
    $pk_cols[] = $dest->get($pk_name);
}
$dest->addIndex('PRIMARY KEY', $pk_cols);

// Copy views
$view_types = array('list', 'export');
foreach ($view_types as $type) {
    $items = array();
    $view = $source->getView($type);
    foreach ($view as $item) {
        if (!($item instanceof ColumnViewItem)) {
            $items[] = $item;
            continue;
        }
        $column = $item->getColumn();
        $item = new ColumnViewItem();
        $item->setColumn($dest->get($column->getName()));
        $items[] = $item;
    }
    $dest->setView($type, $items);
}


// Copy ordering
$order = $source->getOrder('view');
foreach ($order as $order_item) {
    list($source_col, $dir) = $order_item;
    $dest_col = $dest->get($source_col->getName());
    $dest->addToOrder('view', $dest_col, $dir);
}

// Copy row identifier
$ident = array();
foreach ($source->getRowIdentifier() as $ident_part) {
    if (!is_object($ident_part)) {
        $ident[] = $ident_part;
        continue;
    }
    $ident[] = $dest->get($ident_part->getName());
}
$dest->setRowIdentifier($ident);

// Copy menu status and allowed actions
$allowed = $source->getAllAllowed();
$dest->setAllAllowed(false);
foreach ($allowed as $action) {
    $dest->setAllowed($action, true);
}
$dest->setDisplay($source->getDisplay());

// Save in tables.xml
$db->addTable($dest);
$db->dumpXML('', '');

// Copy admin add/edit form
$form = FormManager::load('admin.' . $source->getName());
if ($form != null) {
    $form->setTable($dest);
    $form->setFile('admin.' . $dest->getName());
    FormManager::save($form);
}

// Actually create the table
try {
    execq($dest->getCreateQuery());
    $_SESSION['setup']['msg'] = 'Table has been copied';
} catch (QueryException $ex) {
    unset($_SESSION['setup']['msg']);
    $err = 'Copied table data was saved in XML, but new database table failed to be created';
    $_SESSION['setup']['warn'] = $err;
}

// Copy other indexes
$q = "SHOW INDEXES FROM `{$_POST['source']}`";
$indexes = array();
try {
    $res = execq($q);
    while ($row = fetch_assoc($res)) {
        $name = $row['Key_name'];
        if ($name == 'PRIMARY') continue;
        if (!isset($indexes[$name])) {
            $indexes[$name] = array(
                'unique' => (bool) (1 - $row['Non_unique']),
                'cols' => array()
            );
        }
        $col = "`{$row['Column_name']}`";
        if ($row['Sub_part'] !== null) {
            $col .= "({$row['Sub_part']})";
        }
        $indexes[$name]['cols'][] = $col;
    }
} catch (QueryException $ex) {
}

if (count($indexes) > 0) {
    $q = "ALTER TABLE `{$_POST['dest']}` ";
    $index_num = 0;
    foreach ($indexes as $name => $data) {
        if (++$index_num != 1) $q .= ', ';
        $unique = ($data['unique'])? 'UNIQUE': '';
        $q .= "ADD {$unique} INDEX `{$name}` (" .
            implode(', ', $data['cols']) . ')';
    }
}
execq($q);

redirect('table_edit_cols.php?t=' . urlencode($dest->getName()));
