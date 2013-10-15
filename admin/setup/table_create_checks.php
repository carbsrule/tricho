<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once 'setup_functions.php';

function enforce_special_type ($var, $type) {
    //echo "Enforcing $type on $var<br>\n";
    global $enforceable_data_types, $recognised_SQL_types;
    switch ($type) {
        case 'type':
            if (!in_array ($var, $enforceable_data_types)) {
                if (substr ($var, 0, 4) != 'eval') {
                    $var = false;
                }
            }
            break;
            
        case 'sqltype':
            $found = false;
            foreach ($recognised_SQL_types as $group => $sql_types) {
                foreach ($sql_types as $sql_type) {
                    if ($sql_type == $var) {
                        $found = true;
                        break 2;
                    }
                }
            }
            if (!$found) {
                $var = false;
            }
            break;
            
        default:
            $var = false;
    }
    //echo "Result: $var<br>\n";
    return $var;
}

function check_1 () {
    $errors = array ();
    
    // check for invalid chars
    if (!table_name_valid ($_SESSION['setup']['create_table']['table_name'])) {
        $_SESSION['setup']['create_table']['table_name'] = '';
    }
    
    // check for blank
    if ($_SESSION['setup']['create_table']['table_name'] == '') {
        if ($_POST['table_name'] == '') {
            $errors[] = "You must enter a table name";
        } else {
            $_SESSION['setup']['create_table']['table_name'] = $_POST['table_name'];
            $errors[] = "Invalid table name";
        }
    }
    
    // check table does not already exist in db
    $res = execq("SHOW TABLES");
    while ($row = fetch_row($res)) {
        if ($row[0] == $_POST['table_name']) {
            $errors[] = "Table '{$_POST['table_name']}' already exists in database";
            break;
        }
    }
    
    // check table does not already exist in tables.xml
    $db = Database::parseXML ('../tables.xml');
    foreach ($db->getTables () as $table) {
        if ($table->getName () == $_POST['table_name']) {
            $errors[] = "Table '{$_POST['table_name']}' already exists in tables.xml";
            break;
        }
    }
    
    if (trim($_SESSION['setup']['create_table']['table_name_single']) == '') {
        $errors[] = 'You must supply a single name';
    }
    
    // return on error
    if (count($errors) > 0) {
        $_SESSION['setup']['err'] = $errors;
        redirect ('table_create0.php');
    }
}


?>
