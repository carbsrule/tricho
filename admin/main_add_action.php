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
$table = $db->getTable ($_POST['_t']);
force_redirect_to_alt_page_if_exists ($table, 'main_add_action');

if (!$table->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

list ($urls, $seps) = $table->getPageUrls ();

// cancel add
if ($_POST['_do'] == 'Cancel') {
    unset($_SESSION[ADMIN_KEY]['add'][$table->getName()]);
    
    if ($_POST['_p'] != '') {
        redirect ("{$urls['main']}{$seps['main']}p={$_POST['_p']}&t=". urlencode ($table->getName ()));
    } else {
        redirect ("{$urls['main']}{$seps['main']}t=". urlencode ($table->getName ()));
    }
}

$auto_inc_col_name = $table->getAutoIncPK ();
$primary_key_values = $table->getPostedPK ();
$view_columns = $table->getViewColumns('add');

$input_data = array ();
$field_values = array ();
$temp_errs = array ();
$temp_warns = array ();

// Loop through editable fields, and add to the session
foreach ($view_columns as $item) {
    $col = $item->getColumn ();
    if ($item->getEditable ()) {
        
        // TODO: make getInputField be aware of the fact that it's on an add form
        // and therefore doesn't need to ask for the current password
        if ($col instanceof PasswordColumn) {
            $col->setExistingRequired (false);
        }
        
        $input = null;
        try {
            if ($col instanceOf FileColumn) {
                $source = $_FILES;
            } else {
                $source = $_POST;
            }
            
            // TODO: replace $col->getMandatory () with a value for each form
            // e.g. new fields added long after a table's creation may be mandatory
            // for new records (add), but not for existing records (edit)
            if (method_exists ($col, 'collateMultiInputs')) {
                $value = $col->collateMultiInputs ($source, $input);
            } else {
                $value = $col->collateInput ($source[$col->getPostSafeName ()], $input);
            }
            
            $extant_value = $_SESSION[ADMIN_KEY]['add'][$table->getName()][$col->getName()];
            if ($col instanceOf FileColumn and $col->isInputEmpty ($value)) {
                if ($extant_value instanceof UploadedFile) {
                    $input_data[$col->getName ()] = $extant_value;
                    $field_values[$col->getName ()] = $extant_value;
                    continue;
                }
            }
            
            if ($col->isMandatory () and $col->isInputEmpty ($value)) {
                $temp_errs[$col->getName ()] = 'Required field';
            } else {
                $field_values = array_merge ($field_values, $value);
            }
        } catch (DataValidationException $ex) {
            $temp_errs[$col->getName ()] = $ex->getMessage ();
        }
        $input_data[$col->getName ()] = $input;
    }
}

$_SESSION[ADMIN_KEY]['add'][$table->getName ()] = $input_data;

// did the data pass?
if (count($temp_errs) > 0) {
    $_SESSION[ADMIN_KEY]['err'] = array ();
    foreach ($temp_errs as $col_name => $err) {
        $err = '<em>'. hsc ($table->get ($col_name)->getEngName ()). ':</em> '. hsc ($err);
        $_SESSION[ADMIN_KEY]['err'][] = $err;
    }
    
    $url = "{$urls['main_add']}{$seps['main_add']}";
    $url .= 't='. $table->getName ();
    if (isset($_POST['_p'])) {
        $url .= '&p='. $_POST['_p'];
    }
    
    redirect ($url);
}

// Warnings
if (count($temp_warns) > 0) {
    $_SESSION[ADMIN_KEY]['warn'] = $temp_warns;
}

// build query
$q = "INSERT INTO `". $table->getName (). "` ";
if (count($field_values) == 0) {
    $q .= " VALUES ()";
} else {
    $q .= " SET ";
    $field_num = 0;
    $field_setters = array ();
    foreach ($field_values as $key => $val) {
        $field_setters[] = "`{$key}` = " . sql_enclose($val, false);
    }
    $q .= implode (', ', $field_setters);
}

// execute query
if (execq($q, false)) {
    
    if ($auto_inc_col_name !== false) {
        $conn = ConnManager::get_active();
        $auto_inc_value = $conn->get_pdo()->lastInsertId();
        
        // store files that have newly generated primary key in their name
        foreach ($view_columns as $item) {
            $col = $item->getColumn ();
            if ($item->getEditable() and $col instanceof FileColumn) {
                $value = $field_values[$col->getName()];
                if (!($value instanceof UploadedFile)) continue;
                $col->saveData ($value, $auto_inc_value);
            }
        }
        
        $q = 'SELECT * FROM `'. $table->getName (). "` WHERE `{$auto_inc_col_name}` = {$auto_inc_value}";
    } else {
        $q = 'SELECT * FROM `'. $table->getName (). '` WHERE ';
        $field_num = 0;
        $pk_names = $table->getPKnames ();
        reset($pk_names);
        foreach ($primary_key_values as $val) {
            list($junk, $key) = each($pk_names);
            if (++$field_num > 1) $q .= ' AND ';
            $q .= "`{$key}` = " . sql_enclose ($val);
        }
    }
    
    // if this is a static table, fetch the newly inserted values and log them -
    // an extra select is used here so that passwords that are stored in an encrypted state don't get logged
    // with their plain text state, e.g. SET Password = MD5('my_password')
    if ($table->isStatic ()) {
        $res = execq($q);
        $log_error = false;
        $rows = (int) @$res->rowCount();
        if ($rows == 1) {
            if ($row = @fetch_assoc($res)) {
                
                $q = 'INSERT INTO `'. $table->getName (). '` SET ';
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
                
                log_action ($db, "Added row in static table ". $table->getName (), $q);
            } else {
                $log_error = "failed to fetch row";
            }
        } else {
            $log_error = "query returned {$rows} rows";
        }
        if ($log_error) {
            $message = "Failed to log a query after a row in ". $table->getName ().
                " was inserted, for the following reason:\n\n{$log_error}\n\nThe query was:\n{$q}\n\n";
            email_error ($message);
        }
    }
    
    $_SESSION[ADMIN_KEY]['msg'] = 'Added';
    
    unset($_SESSION[ADMIN_KEY]['add'][$table->getName()]);
    
    if (count($temp_errs) > 0) {
        $_SESSION[ADMIN_KEY]['err'] = '';
        if (count($temp_errs) > 1) {
            $_SESSION[ADMIN_KEY]['err'] .= "<br>\n";
        }
        $_SESSION[ADMIN_KEY]['err'] .= implode ("<br>\n", $temp_errs);
    }
    
    $url = "{$urls['main']}{$seps['main']}t=". urlencode ($table->getName ());
    
    
    if ($_POST['_p'] != '') $url .= "&p={$_POST['_p']}";
    
    redirect ($url);
    
} else {
    $err = "A database error occured";
    
    if (test_setup_login(false, SETUP_ACCESS_LIMITED)) {
        $conn = ConnManager::get_active();
        $err .= ': ' . $conn->last_error();
    }
    $temp_errs[] = $err;
    
    $_SESSION[ADMIN_KEY]['err'] = $temp_errs;
    redirect ("{$urls['main_add']}{$seps['main_add']}t=". $table->getName ());
}
?>
