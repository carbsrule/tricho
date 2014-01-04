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

$db = Database::parseXML ('../tables.xml');
$table = $db->getTable($_POST['t']);
if ($table == null) redirect ('./');
$url = 'table_edit_indexes.php?t=' . urlencode($table->getName());

$new_index = array ();
$new_index_cols = array ();

// get new index columns
if (@is_array ($_POST['fields'])) {
    foreach ($_POST['fields'] as $field_name) {
        $new_col = $table->get ($field_name);
        if ($new_col != null) {
            $new_index_cols[] = '`'. $field_name. '`';
            $new_index[] = $new_col;
        } else {
            $_SESSION['setup']['err'] = "Unknown column {$field_name}";
            redirect($url);
        }
    }
}

if (count($new_index) == 0) {
    $_SESSION['setup']['err'] = "You must select at least one column for the primary key";
    redirect($url);
}


$sorted_old_cols = $table->getPKnames ();
sort ($sorted_old_cols);

$sorted_new_cols = $_POST['fields'];
sort ($sorted_new_cols);

$table_auto_inc_col = false;
$sorted_db_cols = array ();
$res = execq("SHOW COLUMNS FROM {$table->getName ()}");
while ($row = fetch_assoc($res)) {
    if ($row['Key'] == 'PRI') {
        $sorted_db_cols[] = $row['Field'];
    }
    if (stripos ($row['Extra'], 'auto_increment') !== false) {
        $table_auto_inc_col = $row['Field'];
    }
}
sort ($sorted_db_cols);


$update_xml = false;
$update_database = false;

// if the XML definition hasn't changed, and the Database matches the XML, there's nothing to do.
if ($sorted_old_cols == $sorted_db_cols and $sorted_new_cols == $sorted_db_cols) {
    $_SESSION['setup']['msg'] = "Primary key definition unchanged";
    redirect($url);
}


// if the XML definition has changed, the XML needs to be updated
if ($sorted_old_cols != $sorted_new_cols) {
    $update_xml = true;
}

// if the new XML definition doesn't match the database, the database needs to be updated
if ($sorted_new_cols != $sorted_db_cols) {
    $update_database = true;
}


if ($update_xml) {
    $table->addIndex ('PRIMARY KEY', $new_index);
}

$query_list = array ();

// If the database needs to change, remove the existing primary key and then create a new one
if ($update_database) {
    
    // Check before removing the primary key to see if there is a constraint that prevents its removal
    // If there's an AUTO_INCREMENT column that is contained only in the Primary Key and not in another index,
    // the Primary Key cannot be changed
    if ($table_auto_inc_col and in_array ($table_auto_inc_col, $sorted_db_cols)) {
        
        // if there's another index on an AUTO_INCREMENT column, the PK can safely be removed
        $removal_unsafe = true;
        $res = execq("SHOW INDEX FROM {$table->getName ()}");
        while ($row = fetch_assoc($res)) {
            if ($row['Key_name'] != 'PRIMARY' and $row['Column_name'] == $table_auto_inc_col) {
                $removal_unsafe = false;
            }
        }
        
        if ($removal_unsafe) {
            $_SESSION['setup']['err'] = "Cannot modify the primary key, as it alone contains an ".
                "AUTO INCREMENT column. Add another index on <em>{$table_auto_inc_col}</em> if you wish to ".
                "change the primary key for this table";
            redirect($url);
        }
    }
    
    // Remove the existing primary key from the database, if there is one
    if (count ($sorted_db_cols) > 0) {
        $q = "ALTER TABLE `{$_POST['t']}` DROP PRIMARY KEY";
        $query_list[] = $q;
        if (!execq($q)) {
            $conn = ConnManager::get_active();
            $_SESSION['setup']['err'] = 'Database error removing ' .
                'old PK: ' . $conn->last_error();
            redirect($url);
        }
    }
    
    // Add the new primary key, made up of the columns requested
    $q = "ALTER TABLE `{$_POST['t']}` ".
        "ADD PRIMARY KEY (". implode (',', $new_index_cols). ")";
    $query_list[] = $q;
    if (!execq($q)) {
        $conn = ConnManager::get_active();
        $_SESSION['setup']['err'] = 'Database error adding new ' .
            'PK: ' . $conn->last_error();
        redirect($url);
    }
}

if ($update_xml) {
    
    // dump the XML
    try {
        $db->dumpXML('../tables.xml', null);
        $log_message = "Modified Primary Key on {$_POST['t']}";
        log_action ($db, $log_message, implode (";\n", $query_list));
    } catch (FileNotWriteableException $ex) {
        $_SESSION['setup']['err'] = 'Failed to write XML';
    }
    redirect($url);
    
} else {
    $_SESSION['setup']['msg'] = 'Database updated';
    redirect($url);
}
?>
