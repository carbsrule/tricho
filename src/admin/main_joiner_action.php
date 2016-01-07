<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;

require_once '../tricho.php';
test_admin_login();

$db = Database::parseXML();

// get joiner table and its parent based on form data
$parents = explode (',', $_POST['_p']);
list ($parent_name, $parent_id) = explode ('.', $parents[0]);
$table = $db->getTable ($_POST['_joiner']);
$parent = $db->getTable ($parent_name);

// Check user is allowed to access the parent table
// It is assumed that the user has rights to the child table,
// because otherwise the parent relationship probably shouldn't exist
if (!$parent->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

$valid_table_and_parent = true;
if ($table != null and $parent != null and $table->isJoiner ()) {
    
    $parent_link_col = $table->getLinkToTable ($parent);
    $joiner_col = $table->getJoinerColumn ($parent);
     
    if ($parent_link_col != null and $joiner_col != null) {
        
        if (!$parent_link_col->getLink ()->isParent ()) {
            $valid_table_and_parent = false;
        }
        
        $result = validate_type ($parent_id, $parent_link_col->getType (), array (), false, $parent_link_col);
        if ($result->isRubbish ()) {
            $valid_table_and_parent = false;
        } else {
            $parent_id = $result->getValue ();
        }
        
    } else {
        $valid_table_and_parent = false;
    }
    
} else {
    $valid_table_and_parent = false;
}


// do stuff (if valid)
if ($valid_table_and_parent) {
    
    if (strcasecmp ($_POST['_do'], 'cancel') == 0) {
        
        $_SESSION[ADMIN_KEY]['msg'] = 'Edit cancelled';
        
    } else {
        
        $regular_columns = array ();
        $file_columns = array ();
        $columns = $table->getViewColumns('edit');
        foreach ($columns as $item) {
            $col = $item->getColumn();
            if ($col !== $parent_link_col and $col !== $joiner_col and $item->getEditable()) {
                if ($col->getOption() == '') {
                    $regular_columns[] = $col;
                    
                } else if ($col->getOption() == 'file' or $col->getOption() == 'image') {
                    $regular_columns[] = $col;
                    $file_columns[] = $col;
                    
                }
            }
        }
        
        // Before the deletion, the filenames of all file and image columns need to be saved
        // so that if no new file has been uploaded, the old filename can be plonked back into the new record
        if (count ($file_columns) > 0) {
            $q = "SELECT `{$joiner_col->getName ()}`";
            foreach ($file_columns as $col) {
                $q .= ", `{$col->getName ()}`";
            }
            $q .= "FROM `{$table->getName ()}` WHERE `{$parent_link_col->getName ()}` = ". sql_enclose ($parent_id);
            $res = execq($q);
            
            $old_filenames = array ();
            while ($row = fetch_assoc($res)) {
                $old_filenames[$row[$joiner_col->getName ()]] = $row;
            }
        }
        
        
        // Get the primary keys for all existing records
        $q = "SELECT `{$joiner_col->getName ()}`
            FROM `{$table->getName ()}`
            WHERE `{$parent_link_col->getName ()}` = ". sql_enclose ($parent_id);
        $res = execq($q);
        
        $existing_pks = array ();
        while ($row = fetch_assoc($res)) {
            $existing_pks[] = $row[$joiner_col->getName ()];
        }
        
        
        $field_name = $joiner_col->getName ();
        $field_name = str_replace (' ', '_', $field_name);
        
        if (is_array ($_POST[$field_name]) and count($_POST[$field_name]) > 0) {
            $all_row_errors = array();
            $all_forgotten_rows = array();
            foreach ($_POST[$field_name] as $id => $val) {
                
                // validate the pk2 value
                $result = validate_type ($val, $joiner_col->getType (), $joiner_col->getTextFilterArray (), false, $joiner_col);
                if ($result->isRubbish ()) {
                    throw new Exception("Invalid value {$val} for field <i>{$joiner_col->getName ()}</i>");
                }
                $val = $result->getValue ();
                
                // insert one
                $set_data = array ();
                $set_data[$parent_link_col->getName ()] = $parent_id;
                $set_data[$joiner_col->getName ()] = $result->getValue ();
                
                // Create a primary key array for this record
                $primary_key = array ();
                $pk_names = $table->getPKnames ();
                foreach ($pk_names as $name) {
                    $primary_key[] = $set_data[$name];
                }
                
                // load additional data
                $row_errors = array();
                foreach ($regular_columns as $col) {
                    
                    $col_name = $col->getName ();
                    $col_name = str_replace (' ', '_', $col_name);
                    
                    // check mand
                    if (($_POST[$col_name . '_' . $val] === '') and ($col->isMandatory())) {
                        $row_errors[] = "No value for required field <i>{$col->getEngName ()}</i>";
                        $forget_row = true;
                        
                    } else {
                        try {
                            
                            // File and image columns get special treatment, because files can be deleted, etc
                            if ($col->getOption () == 'file' or $col->getOption () == 'image') {
                                $params = $col->getParams ();
                                
                                if ($_POST[$col_name. '_'. $val. '__clean'] == 1) {
                                    // clean old data
                                    $set_data[$col->getName ()] = '';
                                    
                                } else if ($_POST[$col_name. '_'. $val. '__del'] == 1 and $params['allow_del']) {
                                    // delete existing file
                                    $file_name = '../'. $params['storage_location'];
                                    if ($file_name[strlen ($file_name) - 1] != '/') {
                                        $file_name .= '/';
                                    }
                                    $file_name .= $table->getMask (). '.'. $col->getMask (). '.'. implode (',', $primary_key);
                                    
                                    // delete thumbnails
                                    if ($col->getOption () == 'image' and is_array ($params['thumbnails'])) {
                                        reset ($params['thumbnails']);
                                        foreach ($params['thumbnails'] as $thumbnail_name => $size_constraints) {
                                            @unlink ($file_name. '.'. $thumbnail_name);
                                        }
                                    }
                                    
                                    @unlink ($file_name);
                                    
                                    $set_data[$col->getName ()] = '';
                                    
                                    
                                } else {
                                    // A regular upload
                                    $data = $col->dataFromPost ($primary_key, $col_name . '_' . $val);
                                    if ($data == null) {
                                        $set_data[$col->getName ()] = $old_filenames[$val][$col->getName ()];
                                    } else {
                                        $set_data[$col->getName ()] = $data;
                                    }
                                }
                                
                                
                            // Everything else is handled the good old-fashioned way
                            } else {
                                $set_data[$col->getName ()] = $col->dataFromPost ($primary_key, $col_name . '_' . $val);
                            }
                            
                        } catch (Exception $e) {
                            $error = "Invalid value for <i>{$col->getEngName ()}</i>: {$e->getMessage ()}";
                            
                            $row_errors[] = $error;
                            if ($col->isMandatory ()) {
                                $forget_row = true;
                            }
                        }
                    }
                }
                
                // if we need to forget this row, forget it
                if ($forget_row) {
                    $row_errors[] = 'Some required fields were not filled in; Record not saved';
                    $all_row_errors[$val] = implode ('<br>', $row_errors);
                    if (count ($row_errors) > 1) {
                        $all_row_errors[$val] = '<br>'. $all_row_errors[$val];
                    }
                    $all_forgotten_rows[$val] = $set_data;
                    continue;
                }
                
                // If the record already exists, it has to be an update query
                $action = 'INSERT INTO';
                $pk_index = array_search ($set_data[$joiner_col->getName ()], $existing_pks);
                if ($pk_index !== false) $action = 'UPDATE';
                
                // build the query
                $q = "{$action} `". $table->getName () . "` SET ";
                $j = 0;
                foreach ($set_data as $field => $value) {
                    if ($j++ > 0)$q .= ', ';
                    $q .= "`{$field}` = " . sql_enclose ($value);
                }
                
                // For update queries, add a where clause
                if ($action == 'UPDATE') {
                    $q .= " WHERE `{$parent_link_col->getName ()}` = ".
                        sql_enclose ($set_data[$parent_link_col->getName ()]).
                        " AND `{$joiner_col->getName ()}` = ".
                        sql_enclose ($set_data[$joiner_col->getName ()]);
                }
                
                // execute it
                $res = execq($q);
                if (! $res) {
                    $row_errors[] = 'Record save into database failed';
                } else if ($pk_index !== false) {
                    unset ($existing_pks[$pk_index]);
                }
                
                // do errors if they were generated
                if (count ($row_errors) != 0) {
                    $all_row_errors[$val] = implode ('<br>', $row_errors);
                    if (count ($row_errors) > 1) {
                        $all_row_errors[$val] = '<br>'. $all_row_errors[$val];
                    }
                }
            }
        }
        
        // Delete all records that were not updated
        foreach ($existing_pks as $pk) {
            $q = "DELETE FROM `{$table->getName ()}`
                WHERE `{$parent_link_col->getName ()}` = ". sql_enclose ($parent_id). "
                    AND `{$joiner_col->getName ()}` = ". sql_enclose ($pk);
            execq($q);
            
            if (count($file_columns) > 0) {
                // Create a primary key array for this record
                $primary_key = array ();
                $pk_names = $table->getPKnames ();
                foreach ($pk_names as $name) {
                    if ($name == $parent_link_col->getName ()) {
                        $primary_key[] = $parent_id;
                    } else if ($name == $joiner_col->getName ()) {
                        $primary_key[] = $pk;
                    }
                }
                
                // Delete the files
                foreach ($file_columns as $col) {
                    $params = $col->getParams ();
                    
                    // delete existing file
                    $file_name = '../'. $params['storage_location'];
                    if ($file_name[strlen ($file_name) - 1] != '/') {
                        $file_name .= '/';
                    }
                    $file_name .= $table->getMask (). '.'. $col->getMask (). '.'. implode (',', $primary_key);
                    
                    // delete thumbnails
                    if ($col->getOption () == 'image' and is_array ($params['thumbnails'])) {
                        foreach ($params['thumbnails'] as $thumbnail_name => $size_constraints) {
                            @unlink ($file_name. '.'. $thumbnail_name);
                        }
                    }
                    
                    @unlink ($file_name);
                }
            }
        }
        
        
        // TODO: proper redirection
        
        if (count ($all_row_errors) != 0) {
            $_SESSION[ADMIN_KEY]['warn'] = 'Edited but with errors';
            $_SESSION['err_ext'] = array('row_errors' => $all_row_errors, 'forgotten_rows' => $all_forgotten_rows);
        } else {
            $_SESSION[ADMIN_KEY]['msg'] = 'Edited';
        }
    }
    
    if ($_POST['_caller'] != '') {
        redirect ($_POST['_caller']);
    } else {
        redirect ('./');
    }
    
    
} else {
    // argh!
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid joiner table references';
    redirect ('./');
}

?>
