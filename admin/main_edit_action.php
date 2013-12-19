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

if (@$_POST['_joiner'] != '') {
    $table = $db->getTable ($_POST['_joiner']);
} else {
    $table = $db->getTable ($_POST['_t']);
}
force_redirect_to_alt_page_if_exists ($table, 'main_edit_action');

if ($table === null) {
    $_SESSION[ADMIN_KEY]['err'] = 'A fatal error has occurred. Perhaps you uploaded too much data?';
    redirect ('./');
}

if (!$table->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

list ($urls, $seps) = $table->getPageUrls ();


// cancel edit
if ($_POST['_do'] == 'Cancel') {
    unset ($_SESSION[ADMIN_KEY]['edit'][$table->getName().'.'.$_POST['_id']]);
    
    // joiner tables return to themself on edit or cancel. let the user know the edit was cancelled
    if (@$_POST['_joiner'] != '') {
        $_SESSION[ADMIN_KEY]['msg'] = 'Edit cancelled.';
    }
    
    if (@$_POST['_p'] != '') {
        redirect ("{$urls['main']}{$seps['main']}p={$_POST['_p']}&t=". urlencode ($table->getName ()));
    } else {
        redirect ("{$urls['main']}{$seps['main']}t=". urlencode ($table->getName ()));
    }
}

$row_key = $table->getName().'.'.$_POST['_id'];
if (!isset ($_SESSION[ADMIN_KEY]['edit'][$row_key])) {
    $_SESSION[ADMIN_KEY]['edit'][$row_key] = array ();
}
$session_data = &$_SESSION[ADMIN_KEY]['edit'][$row_key];

$view_columns = $table->getViewColumns('edit');


// get primary key values
$primary_key_cols = $table->getPKnames ();
$primary_key_values = explode (',', $_POST['_id']);

/** Used to fetch the newly inserted row from the database */
$new_primary_key_values = array ();

if (count($primary_key_cols) == count($primary_key_values)) {
    
    $prim_key_clauses = array ();
    
    reset($primary_key_cols);
    reset($primary_key_values);
    while (list($col_id, $col_name) = each($primary_key_cols)) {
        list($val_id, $val) = each($primary_key_values);
        
        try {
            $junk = '';
            $pk_col = $table->get($col_name);
            $pk_col->collateInput($val, $junk);
        } catch (DataValidationException $ex) {
            report_error("Invalid key provided!");
            die();
        }
        $new_primary_key_values[$col_name] = $val;
        
        $prim_key_clauses[] = "`{$col_name}` = ". sql_enclose ($val);
    }
} else {
    report_error ("Number of primary key values does not match number of primary key columns");
    die ();
}

$pk_clause = implode (' AND ', $prim_key_clauses);

$q = "SELECT * FROM `". $table->getName (). "` WHERE {$pk_clause}";
$res = execq($q);
$row = fetch_assoc($res);

$input_data = array ();
$field_values = array ();
$temp_errs = array ();
$temp_warns = array ();
$editable_pk_names = array();
foreach ($view_columns as $item) {
    $col = $item->getColumn ();
    if (in_array($col->getName(), $primary_key_cols)) {
        $editable_pk_names[] = $col->getName();
    }
    if ($item->getEditable ()) {
        
        // TODO: use existing row data as $input
        $input = @$session_data[$col->getName ()];
        $old_value = $input;
        try {
            // TODO: replace $col->getMandatory () with a value for each form
            // e.g. new fields added long after a table's creation may be mandatory
            // for new records (add), but not for existing records (edit)
            $value = $col->collateInputData($input);
            
            if ($col instanceOf FileColumn and $col->isInputEmpty ($value)) {
                continue;
            }
            
            // TODO: better means of handling password fields?
            if ($col instanceOf PasswordColumn) {
                $col->setMandatory (false);
            }
            
            if ($col->isMandatory () and $col->isInputEmpty ($value)) {
                $temp_errs[$col->getName ()] = 'Required field';
            } else {
                $field_values = array_merge ($field_values, $value);
            }
            $input_data[$col->getName ()] = $input;
        } catch (DataValidationException $ex) {
            $temp_errs[$col->getName ()] = $ex->getMessage ();
        }
    }
}

$debug = false;
$session_data = $input_data;

// see what's happening with the POSTed data
/*
echo "<pre><b>POST:</b>\n", print_r ($_POST, true),
    "\n<b>Original input:</b>\n", print_r ($input_data, true),
    "\n<b>Values:</b>\n", print_r ($field_values, true),
    "\n<b>Errors:</b>\n", print_r ($temp_errs, true);
echo "\n<b>View columns:</b>\n";
foreach ($view_columns as $column) {
    echo $column, "\n";
}
echo "</pre>\n";
die ();
//*/

// check the primary key is not already in use
if (count ($editable_pk_names) > 0) {
    $pk_names = $table->getPKnames ();
    $q = "SELECT 1 FROM `{$table->getName ()}` WHERE ";
    $j = 0;
    $num_changed = 0;
    foreach ($pk_names as $index => $column_name) {
        $column_value = $primary_key_values[$index];
        
        if (in_array($column_name, $editable_pk_names)) {
            $junk = '';
            $column_values = $table->get($column_name)->collateInputData($junk);
            if (count($column_values) != 1) {
                throw new LogicException('Wrong type of column for a PK');
            }
            $column_value = reset($column_values);
            if ($column_value != $row[$column_name]) {
                $num_changed++;
            }
        }
        
        $column_value = sql_enclose ($column_value);
        if (++$j > 1) $q .= ' AND ';
        $q .= "`{$column_name}` = {$column_value}";
    }
    
    // only actually run the check query and make the check if one or more of the PRIMARY KEY fields have changed
    if ($num_changed > 0) {
        $res = execq($q);
        if ($res->rowCount() == 1) {
            $temp_errs[] = $table->getKeyError ($pk_names, true, $parent_table);
        }
    }
}

// check the unique indexes are not in use
$unique_indexes = $table->getUniqueIndices ();
foreach ($unique_indexes as $index_columns) {
    $q = "SELECT 1 FROM `{$table->getName ()}` WHERE ";
    $j = 0;
    $num_changed = 0;
    foreach ($index_columns as $column_name) {
        $col = $table->get ($column_name);
        $junk = '';
        $column_values = $col->collateInputData($junk);
        if (count($column_values) != 1) {
            throw new LogicException('Wrong type of column for a unique index');
        }
        $column_value = reset($column_values);
        if ($column_value != $row[$column_name]) {
            $num_changed++;
        }
        
        $column_value = sql_enclose ($column_value);
        if (++$j > 1) $q .= ' AND ';
        $q .= "`{$column_name}` = {$column_value}";
    }
    
    // check for duplicate UNIQUE values only if one or more of the UNIQUE INDEX fields have changed
    if ($num_changed > 0) {
        $res = execq($q);
        if ($res->rowCount() == 1) {
            $temp_errs[] = $table->getKeyError ($index_columns, true, $parent_table);
        }
    }
}

// did the data pass?
if (count($temp_errs) > 0) {
    $_SESSION[ADMIN_KEY]['err'] = array ();
    foreach ($temp_errs as $col_name => $err) {
        $err = '<em>'. hsc ($table->get ($col_name)->getEngName ()). ':</em> '. hsc ($err);
        $_SESSION[ADMIN_KEY]['err'][] = $err;
    }
    
    $url = "{$urls['main_edit']}{$seps['main_edit']}";
    $url .= 't=' . $table->getName();
    if (isset ($_POST['_p'])) {
        $url .= '&p=' . urlencode ($_POST['_p']);
    }
    $url .= '&id=' . $_POST['_id'];
    
    redirect ($url);
}

// TODO: reimplement rejigging of OrderNum values
$rejig_order_qs = array();
if (count ($rejig_order_qs) > 0) {
    foreach ($rejig_order_qs as $q) {
        execq($q);
    }
    if ($table->isStatic ()) {
        log_action (
            $db,
            "Updated order in static table ". $table->getName (),
            implode (";\n", $rejig_order_qs). ';'
        );
    }
}

// Warnings
if (count($temp_warns) > 0) {
    $_SESSION[ADMIN_KEY]['warn'] = $temp_warns;
}

// do update
if (count($field_values) != 0) {
    $q = "UPDATE `". $table->getName (). "` SET ";
    $field_num = 0;
    $field_setters = array ();
    foreach ($field_values as $key => $val) {
        $field_setters[] = "`{$key}` = ". sql_enclose($val, false);
    }
    
    $q .= implode (', ', $field_setters);
    $q .= " WHERE {$pk_clause}";
    
    if ($debug) die ("<pre>{$q}</pre>");
    if (execq($q)) {
        
        foreach ($view_columns as $item) {
            $col = $item->getColumn ();
            if ($item->getEditable () and $col instanceof FileColumn) {
                $value = @$field_values[$col->getName()];
                if (!($value instanceof UploadedFile)) continue;
                $col->saveData ($value, $primary_key_values);
            }
        }
        
        // if this is a static table, fetch the updated values and log them -
        // an extra select is used here so that passwords that are stored in an encrypted state don't get logged
        // with their plain text state, e.g. SET Password = MD5('my_password')
        if ($table->isStatic ()) {
            
            $q = 'SELECT * FROM `'. $table->getName (). '` WHERE ';
            $field_num = 0;
            foreach ($new_primary_key_values as $key => $val) {
                if (++$field_num > 1) $q .= ' AND ';
                $q .= "`{$key}` = ". sql_enclose ($val);
            }
            
            $res = execq($q);
            $log_error = false;
            $rows = (int) @$res->rowCount();
            if ($rows == 1) {
                if ($row = @fetch_assoc($res)) {
                    
                    $q = 'UPDATE `'. $table->getName (). '` SET ';
                    $field_num = 0;
                    foreach ($row as $field => $value) {
                        if (++$field_num > 1) $q .= ', ';
                        
                        // if it is known that a field is numeric, don't use string values in the logged update query
                        if ($value !== null) {
                            $col_ref = $table->get ($field);
                            if ($col_ref != null and $col_ref->isNumeric ()) {
                                $value = new QueryFieldLiteral ($value, false);
                            }
                        }
                        $q .= "`{$field}` = ". sql_enclose ($value, false);
                    }
                    $q .= " WHERE {$pk_clause}";
                    
                    log_action ($db, "Edited row in static table ". $table->getName (), $q);
                } else {
                    $log_error = "failed to fetch row";
                }
            } else {
                $log_error = "query returned {$rows} rows";
            }
            if ($log_error) {
                $message = "Failed to log a query after a row in ". $table->getName ().
                    " was updated, for the following reason:\n\n{$log_error}\n\nThe query was:\n{$q}\n\n";
                email_error ($message);
            }
        }
        
        $_SESSION[ADMIN_KEY]['msg'] = 'Edited';
        
        unset($_SESSION[ADMIN_KEY]['edit'][$table->getName ().'.'.$_POST['_id']]);
    } else {
        $err = "A database error occured";
        if (test_setup_login(false, SETUP_ACCESS_LIMITED)) {
            $conn = ConnManager::get_active();
            $err .= ': ' . $conn->last_error();
        }
        $temp_errs[] = $err;
    }
} else {
    $_SESSION[ADMIN_KEY]['msg'] = 'No data modified';
    unset($_SESSION[ADMIN_KEY]['edit'][$table->getName ().'.'.$_POST['_id']]);
}

// any errors?
if (count($temp_errs) > 0) {
    $_SESSION[ADMIN_KEY]['err'] = '';
    if (count($temp_errs) > 1) {
        $_SESSION[ADMIN_KEY]['err'] .= "<br>\n";
    }
    $_SESSION[ADMIN_KEY]['err'] .= implode ("<br>\n", $temp_errs);
}

// go somewhere
$url = "{$urls['main']}{$seps['main']}t=". urlencode ($table->getName ());
if (@$_POST['_p'] != '') $url .= "&p={$_POST['_p']}";
redirect ($url);

?>
