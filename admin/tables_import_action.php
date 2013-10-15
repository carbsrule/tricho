<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_admin_login ();

require 'tables_import_functions.php';

$debug_import = false;

$test_mode = false;
if ($_POST['mode'] != 'terse' and $_POST['mode'] != 'verbose') {
    $_POST['mode'] = 'test';
    $test_mode = true;
}

$_SESSION['import_output'] = '';
function out ($s) {
    $_SESSION['import_output'] .= $s . "\n";
}

$tables_added = array (
    'db' => array (),
    'xml' => array ()
);
$columns_added = array (
    'db' => array (),
    'xml' => array ()
);
$columns_modified = array (
    'db' => array (),
    'xml' => array ()
);
$columns_deleted = array (
    'db' => array (),
    'xml' => array ()
);

if ($test_mode) {
    out ('<p class="warning">In test mode - no actual changes have been made</p>');
}

// check file
if ($_POST['data_from'] == 'self') {
    out ('<p>Loading data from the current tables.xml file</p>');
    // load the existing tables.xml file
    $save_nodes = false;
    $existing_xml_doc = null;
    if (is_file ('tables.xml')) {
        $new_xml_doc = new DOMDocument ();
        $new_xml_doc->load ('tables.xml');
        $existing_xml_doc = $new_xml_doc;
    } else {
        $_SESSION[ADMIN_KEY]['err'] = "tables.xml does not exist";
        redirect ('tables_import.php');
    }
    
    
} else if ($_POST['data_from'] == 'file') {
    // load the uploaded file, and prepare to copy its nodes into the existing tables.xml
    
    if (is_uploaded_file ($_FILES['file']['tmp_name'])) {
        out ("<p>Loading data from {$_FILES['file']['name']}</p>");
        
        $save_nodes = true;
        if (is_file ('tables.xml')) {
            if (is_writeable ('tables.xml')) {
                $existing_xml_doc = new DOMDocument ();
                $existing_xml_doc->load ('tables.xml');
            } else {
                $_SESSION[ADMIN_KEY]['err'] = 'permission denied writing to tables.xml';
                redirect ('tables_import.php');
            }
        } else {
            if ($existing_xml_file = fopen ('tables.xml', 'w')) {
                // close file for now, just needed to check that we have write access
                fclose ($existing_xml_file);
                $existing_xml_doc = new DOMDocument ();
                // set up defaults
                $database_node = $existing_xml_doc->createElement ('database');
                $database_node->setAttribute ('menutype', MENU_TYPE_LIST);
                $existing_xml_doc->appendChild ($database_node);
                // save most basic version in case things go pear
                $existing_xml_doc->save ('tables.xml');
            } else {
                $_SESSION[ADMIN_KEY]['err'] = 'tables.xml does not exist and could not be created';
                redirect ('tables_import.php');
            }
        }
        $new_xml_doc = new DOMDocument ();
        $new_xml_doc->load ($_FILES['file']['tmp_name']);
    } else {
        switch ($error_type) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $_SESSION[ADMIN_KEY]['err'] = 'File failed to upload - larger than maximum allowed file size';
                break;
            case UPLOAD_ERR_PARTIAL:
            case UPLOAD_ERR_NO_FILE:
                $_SESSION[ADMIN_KEY]['err'] = 'File failed to upload - please try again';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                $_SESSION[ADMIN_KEY]['err'] = 'File failed to upload - file system or permissions error';
                break;
            default:
                $_SESSION[ADMIN_KEY]['err'] = 'File failed to upload - unknown error';
                break;
        }
        redirect ('tables_import.php');
    }
    
} else {
    $_SESSION[ADMIN_KEY]['err'] = "You must select a file";
    redirect ('tables_import.php');
}

$sql_tables = array ();
$res = execq("SHOW TABLES");
while ($row = fetch_row($res)) {
    $sql_tables[] = $row[0];
}

$extant_db = $existing_xml_doc->getElementsByTagName ('database')->item (0);

// loop through each table defined in the import XML
$tables = $new_xml_doc->getElementsByTagName ('table');
for ($table_num = 0; $table_num < $tables->length; $table_num++) {
    
    $table = $tables->item ($table_num);
    $table_name = $table->getAttribute ('name');
    
    if ($test_mode or $_POST['mode'] == 'verbose') out ("<p>Processing table <em>{$table_name}</em>");
    
    if ($table_name != '') {
        $columns = get_xml_columns ($table);
        $indexes = get_xml_indexes ($table);
        
        // 1: Import the new XML definition into the database
        if (in_array ($table_name, $sql_tables)) {
        // if table exists in database:
            
            if ($test_mode or $_POST['mode'] == 'verbose') out ("<br>table already exists in SQL</p>");
            
            // get existing primary key definition from the database
            $pk_cols = array ();
            $res = execq("SHOW INDEXES FROM `". $table->getAttribute ('name'). '`');
            while ($row = fetch_assoc($res)) {
                if ($row['Key_name'] == 'PRIMARY') {
                    $pk_cols[] = $row['Column_name'];
                }
            }
            
            // get existing columns from database
            $sql_columns = array ();
            $res = execq("SHOW COLUMNS FROM `". $table->getAttribute ('name'). '`');
            while ($row = fetch_assoc($res)) {
                
                // filter extras into an array
                $type = $row['Type'];
                $type_extras = array ();
                
                $replaced = 0;
                $type = str_ireplace (' unsigned', '', $type, $replaced);
                if ($replaced > 0) $type_extras[] = 'UNSIGNED';
                
                $replaced = 0;
                $type = str_ireplace (' zerofill', '', $type, $replaced);
                if ($replaced > 0) $type_extras[] = 'ZEROFILL';
                
                $type = str_replace (' ', '', $type);
                
                if (strcasecmp ($row['Extra'], 'auto_increment') == 0) $type_extras[] = 'AUTO_INCREMENT';
                if (strcasecmp ($row['Null'], 'NO') == 0) $type_extras[] = 'NOT NULL';
                
                $default = $row['Default'];
                
                // throw away int length information, since it's inconsequential
                // i.e. the sql type has not changed if the int lengths don't match - the formatting has changed,
                // but only when using the MySQL command-line tools
                $type = preg_replace ('/int\(.*/i', 'INT', $type);
                
                $sql_columns[$row['Field']] = array ('type' => $type, 'extras' => $type_extras, 'default' => $default);
            }
            
            // if specified, delete existing columns in the database that aren't found in the XML
            if (@in_array ('delete', $_POST['options'])) {
                foreach ($sql_columns as $column_name => $column_defn) {
                    if (!in_array ($column_name, array_keys ($columns))) {
                        $q = "ALTER TABLE `{$table_name}` DROP COLUMN `{$column_name}`";
                        if ($test_mode) {
                            out ("<pre>{$q}</pre>\n");
                        } else if (execq($q)) {
                            if ($_POST['mode'] == 'verbose') out ("<p>Deleted database column {$column_name}</p>");
                            $columns_deleted['db'][$table_name][] = $column_name;
                        } else {
                            out ("<p class=\"error\">Failed to delete database column {$table_name}.{$column_name}</p>");
                        }
                        unset ($sql_columns[$sql_col_key]);
                    }
                }
            }
            
            // loop through the columns defined in the XML
            foreach ($columns as $column_name => $column_node) {
                $col_defn = $column_node->getAttribute ('sql_defn');
                
                if ($sql_col = $sql_columns[$column_name]) {
                    // if they exist in the database, and if specified, update the column definition in the database
                    if (@in_array ('modify', $_POST['options'])) {
                        if ($col_defn != '') {
                            
                            // Check that the SQL actually needs to be updated. If the SQL schema matches that specified
                            // by the new XML, there's no need to modify the database
                            
                            $type = $col_defn;
                            $type_extras = array ();
                            
                            $replaced = 0;
                            $type = str_ireplace (' unsigned', '', $type, $replaced);
                            if ($replaced > 0) $type_extras[] = 'UNSIGNED';
                            
                            $replaced = 0;
                            $type = str_ireplace (' zerofill', '', $type, $replaced);
                            if ($replaced > 0) $type_extras[] = 'ZEROFILL';
                            
                            $replaced = 0;
                            $type = str_ireplace (' auto_increment', '', $type, $replaced);
                            if ($replaced > 0) $type_extras[] = 'AUTO_INCREMENT';
                            
                            $replaced = 0;
                            $type = str_ireplace (' NOT NULL', '', $type, $replaced);
                            if ($replaced > 0) $type_extras[] = 'NOT NULL';
                            
                            $default = null;
                            $matches = array ();
                            preg_match ("/default +(NULL|.*){1}/i", $type, $matches);
                            if (isset ($matches[1])) {
                                $default = str_replace ("''", "'", $matches[1]);
                                $default = str_replace ("\\'", "'", $matches[1]);
                                if ($default[0] == "'" and $default[strlen ($default) - 1] == "'") {
                                    $default = substr ($default, 1, strlen ($default) - 2);
                                }
                            }
                            $type = preg_replace ('/default +.*/i', '', $type);
                            
                            $type = str_replace (' ', '', $type);
                            
                            // throw away int length information, since it's inconsequential
                            $type = preg_replace ('/int\(.*/i', 'INT', $type);
                            
                            // schema has changed if column definition has changed, options have changed,
                            // or default value has changed,
                            
                            $changed = false;
                            
                            if (strcasecmp ($type, $sql_col['type']) != 0) {
                                if ($debug_import) echo "Col {$table_name}.{$column_name} changed: types don't match ".
                                    "({$type} vs. {$sql_col['type']})<br>\n";
                                $changed = true;
                            }
                            
                            if (!$changed) {
                                foreach ($type_extras as $extra) {
                                    if (!in_array ($extra, $sql_col['extras'])) {
                                        if ($debug_import) echo "Col {$table_name}.{$column_name} changed: ".
                                            "added attribute {$extra}<br>\n";
                                        $changed = true;
                                    }
                                }
                            }
                            
                            if (!$changed) {
                                foreach ($sql_col['extras'] as $extra) {
                                    if (!in_array ($extra, $type_extras)) {
                                        if ($debug_import) echo "Col {$table_name}.{$column_name} changed: ".
                                            "removed attribute {$extra}<br>\n";
                                        $changed = true;
                                    }
                                }
                                if ($sql_col['default'] !== $default) {
                                    if ($debug_import) echo "Col {$table_name}.{$column_name} changed: default value changed ".
                                        "from {$sql_col['default']} to {$default}<br>\n";
                                    $changed = true;
                                }
                            }
                            
                            if ($changed) {
                                $q = "ALTER TABLE `{$table_name}` MODIFY COLUMN `{$column_name}` {$col_defn}";
                                if ($test_mode) {
                                    out ("<pre>{$q}</pre>\n");
                                } else if (execq($q)) {
                                    if ($_POST['mode'] == 'verbose') {
                                        $sql_col_defn = $sql_col['type'];
                                        if (count ($sql_col['extras']) > 0) {
                                            $sql_col_defn .= ' '. implode (' ', $sql_col['extras']);
                                        }
                                        if ($sql_col['default'] != '') $sql_col_defn .= ' '. $sql_col['default'];
                                        
                                        out ("<p>Modified database column {$column_name} (".
                                            "{$sql_col_defn} -&gt; {$col_defn})</p>"
                                        );
                                    }
                                    $columns_modified['db'][$table_name][] = $column_name;
                                } else {
                                    out ("<p class=\"error\">Failed to modify database column ".
                                        "{$table_name}.{$column_name}</p>");
                                }
                            }
                        }
                    }
                } else {
                    // if they don't exist, add new columns appropriately
                    $q = "ALTER TABLE `{$table_name}` ADD COLUMN `{$column_name}` {$col_defn}";
                    if ($test_mode) {
                        out ("<pre>{$q}</pre>\n");
                    } else if (execq($q)) {
                        if ($_POST['mode'] == 'verbose') out ("<p>Added column {$column_name} to database</p>");
                        $columns_added['db'][$table_name][] = $column_name;
                    } else {
                        out ("<p class=\"error\">Failed to add database column {$table_name}.{$column_name}</p>");
                    }
                }
            }
            
            // update primary key definition if necessary
            if (count($pk_cols) != count($indexes['PRIMARY KEY'])) {
                $pk_changed = true;
            } else {
                $pk_changed = false;
                reset($pk_cols);
                reset($indexes['PRIMARY KEY']);
                while (list ($old_pk_id, $old_pk_col) = each($pk_cols)) {
                    list ($new_pk_id, $new_pk_col) = each($indexes['PRIMARY KEY']);
                    if ($old_pk_col != $new_pk_col) {
                        $pk_changed = true;
                        break;
                    }
                }
            }
            
            if ($pk_changed) {
                $pk_defn = '';
                
                $pk_col_num = 0;
                foreach ($indexes['PRIMARY KEY'] as $pk_col) {
                    if (++$pk_col_num != 1) {
                        $pk_defn .= ', ';
                    }
                    $pk_defn .= "`{$pk_col}`";
                };
                
                if (execq("ALTER TABLE `{$table_name}` DROP PRIMARY KEY, ADD PRIMARY KEY ($pk_defn)")) {
                    out ("<p class=\"warning\">Modified PRIMARY KEY definition for {$table_name}: {$pk_defn}</p>");
                } else {
                    out ("<p class=\"error\">Failed to modify PRIMARY KEY definition for {$table_name}: {$pk_defn}</p>");
                }
            }
            
        } else {
            
            if ($test_mode or $_POST['mode'] == 'verbose') out ("<br>table does not exist in SQL</p>");
            
            // if table doesn't exist:
            // create the table itself
            $create_table_str = "CREATE TABLE `{$table_name}` (";
            
            // loop through the columns defined in the XML, and add each one
            $col_count = 0;
            foreach ($columns as $column_name => $column_node) {
                $col_defn = $column_node->getAttribute ('sql_defn');
                if ($col_count++ > 0) {
                    $create_table_str .= ",\n";
                } else {
                    $create_table_str .= "\n";
                }
                $create_table_str .= "`{$column_name}` {$col_defn}";
            }
            
            // set up the primary key and other indexes
            foreach ($indexes as $index_name => $index_cols) {
                if (count ($index_cols) > 0) {
                    if ($col_count++ > 0) {
                        $create_table_str .= ",\n";
                    } else {
                        $create_table_str .= "\n";
                    }
                    
                    $index_col_num = 0;
                    $col_list = '';
                    foreach ($index_cols as $index_col) {
                        if (++$index_col_num != 1) $col_list .= ', ';
                        $col_list .= "`{$index_col}`";
                    }
                    if ($index_name == 'PRIMARY KEY') {
                        $create_table_str .= "PRIMARY KEY ({$col_list})";
                    } else {
                        $create_table_str .= "INDEX `{$index_name}` ({$col_list})";
                    }
                }
            }
            
            $create_table_str .= "\n)";
            
            if ($test_mode) {
                out ("<pre>{$create_table_str}</pre>\n");
            } else if (execq($create_table_str)) {
                if ($_POST['mode'] == 'verbose') out ("<p>Added table {$table_name}</p>");
                $tables_added['db'][] = $table_name;
            } else {
                out ("<p class=\"error\">Creation of <em>{$table_name}</em> failed:<br>{$create_table_str}</p>");
            }
            
        }
        
        
        // 2: If using uploaded file, import the new XML definition into the existing XML definition
        // (This obviously isn't necessary if the user selected the existing tables.xml and just wants
        // the database to be updated accordingly.)
        if ($_POST['data_from'] == 'file') {
            
            // TODO: remember which columns were deleted above and also remove them from existing XML definition
            
            // attempt to find the table definition in the existing XML file
            $extant_table = null;
            $extant_tables = $existing_xml_doc->getElementsByTagName ('table');
            for ($extant_table_num = 0; $extant_table_num < $extant_tables->length; $extant_table_num++) {
                $extant_table_to_check = $extant_tables->item ($extant_table_num);
                if ($extant_table_to_check->getAttribute ('name') == $table_name) {
                    $extant_table = $extant_table_to_check;
                    break;
                }
            }
            
            // if table exists in tables.xml:
            // - copy all the attributes of the uploaded XML file onto the existing table definition
            // - loop through the columns in the table, importing each one
            if ($extant_table !== null) {
                
                foreach ($table->attributes as $attr) {
                    $extant_table->setAttribute ($attr->name, $attr->value);
                }
                
                $first_change = false;
                
                $extant_columns = get_xml_columns ($extant_table);
                
                // if specified, delete columns in the existing XML that aren't found in the imported table
                if (@in_array ('delete', $_POST['options'])) {
                    foreach ($extant_columns as $extant_column_name => $extant_column) {
                        if (!in_array ($extant_column_name, array_keys ($columns))) {
                            if ($test_mode) {
                                if ($first_change) {
                                    out ("<p><strong>Modifications for {$table_name}:</strong></p>");
                                    $first_change = false;
                                }
                                out ("<p>Removing XML for column {$extant_column_name}</p>");
                            } else {
                                $extant_column->parentNode->removeChild ($extant_column);
                                if ($_POST['mode'] == 'verbose') {
                                    out ("<p>Deleted column {$table_name}.{$extant_column_name} from XML</p>");
                                }
                                $columns_deleted['xml'][$table_name][] = $extant_column_name;
                            }
                            unset ($extant_columns[$extant_column_name]);
                        }
                    }
                }
                
                // loop through the columns defined in the imported table: add new columns,
                // and modify existing ones where necessary
                foreach ($columns as $column_name => $column_node) {
                    $col_defn = $column_node->getAttribute ('sql_defn');
                    if (in_array ($column_name, array_keys ($extant_columns))) {
                        // if they exist in the existing XML, and if specified,
                        // update the column definition in the XML
                        if (@in_array ('modify', $_POST['options'])) {
                            $extant_column = $extant_columns[$column_name];
                            $imported_column = $existing_xml_doc->importNode ($column_node, true);
                            if ($test_mode) {
                                if ($first_change) {
                                    out ("<p><strong>Modifications for {$table_name}:</strong></p>");
                                    $first_change = false;
                                }
                                out ("<p>Replacing XML for column {$column_name}</p>");
                            } else {
                                $extant_column->parentNode->replaceChild ($imported_column, $extant_column);
                                if ($_POST['mode'] == 'verbose') {
                                    out ("<p>Modified column {$table_name}.{$column_name} in XML</p>");
                                }
                                $columns_modified['xml'][$table_name][] = $column_name;
                            }
                        }
                    } else {
                        // if they don't exist, add new columns appropriately
                        $imported_column = $existing_xml_doc->importNode ($column_node, true);
                        if ($test_mode) {
                            if ($first_change) {
                                out ("<p><strong>Modifications for {$table_name}:</strong></p>");
                                $first_change = false;
                            }
                            out ("<p>Adding XML for column {$extant_column_name}</p>");
                        } else {
                            $extant_table->appendChild ($imported_column);
                            if ($_POST['mode'] == 'verbose') {
                                out ("<p>Added column {$table_name}.{$column_name} to XML</p>");
                            }
                            $columns_added['xml'][$table_name][] = $column_name;
                        }
                    }
                }
                
                // once columns have been imported, need to import all other table meta-data:
                // views (main, add_edit, export), ordering (vieworder), indices, row_identifier
                $properties_to_replace = array (
                    'main', 'add_edit', 'export', 'vieworder', 'indices', 'row_identifier'
                );
                foreach ($properties_to_replace as $property) {
                    if (import_and_overwrite_node ($extant_table, $table, $property)) {
                        if ($_POST['mode'] == 'verbose') out ("<p>Imported property {$property}</p>");
                    } else {
                        out ("<p class=\"error\">Couldn't import property {$property}</p>");
                    }
                }
                
                // add alternate pages and buttons
                $alt_pages = $table->getElementsByTagName ('alt_page');
                foreach ($alt_pages as $alt_page) {
                    set_alt ($extant_table, $alt_page);
                }
                $alt_buttons = $table->getElementsByTagName ('alt_button');
                foreach ($alt_buttons as $alt_button) {
                    set_alt ($extant_table, $alt_button);
                }
                
            } else {
                
                // if table doesn't exist:
                // import the entire table definition from the existing XML file.
                $imported_table = $existing_xml_doc->importNode ($table, true);
                if ($test_mode) {
                    out ("<p>Adding XML definition for <em>{$table_name}</em></p>");
                } else {
                    $extant_db->appendChild ($imported_table);
                    if ($_POST['mode'] == 'verbose') out ("<p>Added table {$table_name} to XML</p>");
                    $tables_added['xml'][] = $table_name;
                }
                
            }
            
        }
    }
}

if (!$test_mode) {
    
    out ('<p></p>');
    out ('<p><b>Summary of actions:</b></p>');
    
    out ('<p>Database</p>');
    
    out ('<p>');
    
    $table_count = count ($tables_added['db']);
    out ($table_count. ' new table'. ($table_count != 1? 's': ''). ' added<br>');
    
    // added
    $table_count = count ($columns_added['db']);
    $col_count = 0;
    foreach ($columns_added['db'] as $columns) {
        $col_count += count ($columns);
    }
    out ($col_count. ' column'. ($col_count != 1? 's': ''). ' added to '. $table_count.
        ' existing table'. ($table_count != 1? 's': ''). '<br>');
    
    // modified
    $table_count = count ($columns_modified['db']);
    $col_count = 0;
    foreach ($columns_modified['db'] as $columns) {
        $col_count += count ($columns);
    }
    out ($col_count. ' column'. ($col_count != 1? 's': ''). ' modified in '. $table_count.
        ' existing table'. ($table_count != 1? 's': ''). '<br>');
    
    // deleted
    $table_count = count ($columns_deleted['db']);
    $col_count = 0;
    foreach ($columns_deleted['db'] as $columns) {
        $col_count += count ($columns);
    }
    out ($col_count. ' column'. ($col_count != 1? 's': ''). ' removed from '. $table_count.
        ' existing table'. ($table_count != 1? 's': ''). '</p>');
    
    if ($_POST['data_from'] == 'file') {
        out ("<p>XML</p>");
        
        out ('<p>');
        
        $table_count = count ($tables_added['xml']);
        out ($table_count. ' new table'. ($table_count != 1? 's': ''). ' added<br>');
        
        // added
        $table_count = count ($columns_added['xml']);
        $col_count = 0;
        foreach ($columns_added['xml'] as $columns) {
            $col_count += count ($columns);
        }
        out ($col_count. ' column'. ($col_count != 1? 's': ''). ' added to '. $table_count.
            ' existing table'. ($table_count != 1? 's': ''). '<br>');
        
        // modified
        $table_count = count ($columns_modified['xml']);
        $col_count = 0;
        foreach ($columns_modified['xml'] as $columns) {
            $col_count += count ($columns);
        }
        out ($col_count. ' column'. ($col_count != 1? 's': ''). ' modified in '. $table_count.
            ' existing table'. ($table_count != 1? 's': ''). '<br>');
        
        // deleted
        $table_count = count ($columns_deleted['xml']);
        $col_count = 0;
        foreach ($columns_deleted['xml'] as $columns) {
            $col_count += count ($columns);
        }
        out ($col_count. ' column'. ($col_count != 1? 's': ''). ' removed from '. $table_count.
            ' existing table'. ($table_count != 1? 's': ''). '</p>');
    }
    
    if ($_POST['data_from'] == 'file') $existing_xml_doc->save ('tables.xml');
}
redirect ('tables_import2.php');
?>
