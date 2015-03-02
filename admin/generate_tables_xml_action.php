<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_setup_login (true, SETUP_ACCESS_FULL);

$db = Database::parseXML();

if ($db == null) $db = new Database ();

$temp_errs = array ();
$temp_warn = array ();

$num_ok_tables = 0;

// get list of current tables from database
$tables = execq("SHOW TABLES");
$form = new Form();
while ($table = fetch_row($tables)) {
    
    $table_name = $table[0];
    
    if (@in_array ($table_name, $_POST['import'])) {
        
        $num_ok_tables++;
        
        $table_obj = $db->get ($table_name);
        
        if ($table_obj == null) {
            $table_obj = new Table ($table_name);
            if ($table_name[0] == '_') {
                $table_obj->setAccessLevel (TABLE_ACCESS_SETUP_FULL);
            }
            
            // set up default parameters for table
            $table_obj->setDisplay (true);
            $table_obj->setDisplayStyle (TABLE_DISPLAY_STYLE_ROWS);
            $table_obj->setAllowed ('add', true);
            $table_obj->setAllowed ('edit', true);
            $table_obj->setAllowed ('del', true);
            $table_obj->setAllowed ('csv', false);
            $table_obj->setConfirmDel (true);
            $table_obj->setCascadeDel (true);
            
            $db->addTable ($table_obj);
        }
        
        // set table's single name and english name based on SQL name
        $eng_name = convert_to_english_name ($table_name);
        if ($table_obj->getEngName () == '') $table_obj->setEngName ($eng_name);
        if ($table_obj->getNameSingle () == '') {
            if ($eng_name[strlen ($eng_name) - 1] == 's') {
                $table_obj->setNameSingle (substr ($eng_name, 0, strlen ($eng_name) - 1));
            } else {
                $table_obj->setNameSingle ($eng_name);
            }
        }
        
        $pk_columns = array ();
        
        // get the list of columns for the current table,
        // and load each column into the meta-data store.
        $columns = execq("SHOW COLUMNS FROM `{$table_name}`");
        $first_column = true;
        while ($column = fetch_assoc($columns)) {
            $matches = array();
            $pattern = '/^([a-z]+)\s*(\((\S*)\))?(\s*([a-z]+))?/i';
            preg_match($pattern, $column['Type'], $matches);
            
            switch (strtolower($matches[1])) {
            case 'bit':
                $class = 'BooleanColumn';
                break;
            
            case 'tinyint':
                if ($matches[3] == 1) {
                    $class = 'BooleanColumn';
                } else {
                    $class = 'IntColumn';
                }
                break;
            
            case 'int':
            case 'smallint':
            case 'mediumint':
            case 'bigint':
                $class = 'IntColumn';
                break;
                
            case 'float':
            case 'decimal':
            case 'double':
                $class = 'DecimalColumn';
                break;
                
            case 'char':
            case 'varchar':
            case 'binary':
            case 'varbinary':
                $class = 'CharColumn';
                break;
                
            case 'text':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
                $class = 'TextColumn';
                $temp_warn[] = "{$table_name}: The column <i>{$column['Field']}</i> may be a richtext column";
                break;
                
            // binary types would never be richtext columns
            case 'blob':
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
                $class = 'CharColumn';
                break;
                
            case 'date':
            case 'datetime':
            case 'time':
                $class = ucwords($matches[1]) . 'Column';
                break;
                
            default:
                throw new Exception('Unable to determine column class');
            }
            
            // determine an appropriate class for each
            $column_obj = new $class($column['Field']);
            if (substr($class, 0, 4) == 'Date') {
                $temp_warn[] = "{$table_name}: The column <i>{$column['Field']}</i> contains a date and needs a year range set; using default of 1970 - 2037";
                
                //TODO: implement these
                //$column_obj->setMinYear(1970);
                //$column_obj->setMaxYear(2037);
            }
            
            // determine an appropriate english name for the column from the SQL name
            $english_name = convert_to_english_name ($column['Field']);
            $column_obj->setEngName ($english_name);
            
            // By default, use the first column in the table for ordering the table's rows
            if ($first_column) {
                $table_obj->addToOrder ('view', $column_obj, 'ASC');
                $first_column = false;
            }
            if ($column['Key'] == 'PRI') {
                $pk_columns[] = $column_obj;
                
                // Update primary key as we go
                $table_obj->addIndex ('PRIMARY KEY', $pk_columns);
                $column_obj->setMandatory (true);
            }
            
            // Pretend ENUMs are VARCHAR columns
            if (strcasecmp ($matches[1], 'ENUM') == 0) {
                $enum = $column;
                $enum['arr'] = enum_to_array (substr ($column['Type'], 5, -1));
                $matches[1] = 'VARCHAR';
                $longest = 0;
                foreach ($enum['arr'] as $enum_value) {
                    $longest = max ($longest, strlen ($enum_value));
                }
                $matches[3] = $longest;
                $temp_warn[] = "Column ". $table_obj->getName (). '.'. $column_obj->getName().
                    " is an ENUM column, which is not fully supported";
                
            // Pretend TIMESTAMPs are DATETIME columns
            } else if (strcasecmp ($matches[1], 'TIMESTAMP') == 0) {
                $matches[1] = 'DATETIME';
            }
            
            try {
                $sql_type = sql_type_string_to_defined_constant ($matches[1]);
            } catch (Exception $e) {
                // if an unsupported column type is found, ignore this table
                $temp_errs[] = "Could not import table {$table_name}: column ({$column['Field']}) is ".
                    "of unsupported type ". strtoupper ($column['Type']);
                $db->removeTable ($table_name);
                $num_ok_tables--;
                continue 2;
            }
            $column_obj->setSqlType ($sql_type);
            if (@$matches[3] != '') {
                $column_obj->setSqlSize ($matches[3]);
            }
            
            
            // work out what the sql attributes should be
            $attrs = array ();
            if (@$matches[5] != '') {
                $attrs[] = $matches[5];
            }
            if ($column['Null'] == 'NO') {
                $attrs[] = 'NOT NULL';
                if ($class != 'BooleanColumn') $column_obj->setMandatory(true);
            }
            if ($column['Extra'] !== '') {
                $attrs[] = $column['Extra'];
            }
            if ($column['Default'] != null) {
                $attrs[] = 'DEFAULT '. sql_enclose ($column['Default']);
            }
            
            // set the attributes if any exist
            if (count ($attrs) > 0) {
                $column_obj->setSqlAttributes (implode (' ', $attrs));
            }
            
            
            // try to use a tree if possible
            $lc_field_name = strtolower ($column['Field']);
            if ($lc_field_name == 'subof' or $lc_field_name == 'childof') {
                
                // remove any existing ordering
                $order_cols = $table_obj->getOrder ('view');
                while (@count($order_cols) > 0) {
                    $table_obj->ChangeOrder ('view', count($order_cols) - 1, false);
                    $order_cols = $table_obj->getOrder ('view');
                }
                
                $table_obj->addToOrder ('view', $column_obj, 'ASC');
                
                $view_cols = $table_obj->getViewColumns('list');
                $view_col = $view_cols[0]->getColumn ();
                
                if (is_object ($pk_columns[0]) and is_object ($view_cols) and $view_cols !== $column_obj) {
                    $link = new Link ($column_obj, $view_col, array ($view_col));
                    $column_obj->setLink ($link);
                    $table_obj->setDisplayStyle (TABLE_DISPLAY_STYLE_TREE);
                }
            } else if ($lc_field_name == 'ordernum') {
                if ($table_obj->getDisplayStyle () != TABLE_DISPLAY_STYLE_TREE) {
                    // remove any existing ordering
                    $order_cols = $table_obj->getOrder ('view');
                    while (@count($order_cols) > 0) {
                        $table_obj->ChangeOrder ('view', count($order_cols) - 1, false);
                        $order_cols = $table_obj->getOrder ('view');
                    }
                }
                
                $table_obj->addToOrder ('view', $column_obj, 'ASC');
            } else if ($lc_field_name == 'name' or $lc_field_name == 'title') {
                $column_obj->setMandatory (true);
                
                // remove existing ordering if it's just the primary key, and instead order by name or title
                $order_cols = $table_obj->getOrder ('view');
                if (count ($order_cols) == 1 and $order_cols[0][0] === $pk_columns[0]) {
                    while (@count($order_cols) > 0) {
                        $table_obj->ChangeOrder ('view', count($order_cols) - 1, false);
                        $order_cols = $table_obj->getOrder ('view');
                    }
                    $table_obj->addToOrder ('view', $column_obj, 'ASC');
                }
                
            }
            
            // A few warnings
            if ($column['Field'] != 'ID' and strpos ($column['Field'], 'ID') !== false) {
                $temp_warn[] = "{$table_name}: The column <i>{$column['Field']}</i> is probably a linked column";
            }
            
            $names = array ('File', 'Image', 'Photo');
            foreach ($names as $name) {
                if (strpos ($column['Field'], $name) !== false) {
                    $temp_warn[] = "{$table_name}: The column <i>{$column['Field']}</i> is probably an image or file column";
                    break;
                }
            }
            
            // Add column
            $table_obj->addColumn ($column_obj);
            
        }
        
        if (count($pk_columns) == 0) {
            $temp_errs[] = "Could not import table {$table_name}: it has no primary key";
            $db->removeTable ($table_name);
            $num_ok_tables--;
        } else {
            
            // Set up views:
            // PK columns will not be imported into any view
            // The main view will contain the first 5 (non-PK) columns, and any other mandatory (non-PK) columns
            // The add and edit views will contain all (non-PK) columns
            
            // Don't double-add columns to views
            // The existing view columns are recorded as an array of strings (column names),
            // because the column definitions were overwritten earlier, and thus object equality checks would fail,
            // as the ColumnViewItems still currently point to the original Column objects. The ColumnViewItems
            // will point to the updated Column definitions after the XML is saved and read again.
            $main_cols = array ();
            $main_view = $table_obj->getView('list');
            foreach ($main_view as $item) {
                if ($item instanceof ColumnViewItem) {
                    $main_cols[] = $item->getColumn ()->getName ();
                }
            }
            
            $export_cols = array ();
            $export_view = $table_obj->getView('export');
            foreach ($export_view as $item) {
                if ($item instanceof ColumnViewItem) {
                    $export_cols[] = $item->getColumn ()->getName ();
                }
            }
            
            $num_main_columns = 0;
            $form->setTable($table_obj);
            $form->setFile('admin.' . $table_obj->getName());
            foreach ($table_obj->getColumns () as $col) {
                if (in_array ($col, $pk_columns, true)) continue;
                
                $view_item = new ColumnViewItem ();
                $view_item->setDetails ($col, true);
                
                $form_item = new ColumnFormItem($col);
                $form_item->setApply('add,edit');
                $form->addItem($form_item);
                
                if (($col->isMandatory () or $num_main_columns < 5) and $col->getSqlType () != SQL_TYPE_TEXT) {
                    if (!in_array ($col->getName (), $main_cols)) {
                        $table_obj->appendView('list', $view_item);
                        $num_main_columns++;
                    }
                    if (!in_array ($col->getName (), $export_cols)) {
                        $table_obj->appendView('export', $view_item);
                    }
                }
            }
            
            // Also try to work out a simple row identifier:
            // a 'Name' column, 'Title' column, or the first non-PK column
            $row_identifier = $table_obj->getRowIdentifier ();
            if (count ($row_identifier) == 0) {
                $id_set = false;
                $title_col = null;
                foreach ($table_obj->getColumns () as $col) {
                    if (strcasecmp ($col->getName (), 'Name') == 0) {
                        $table_obj->setRowIdentifier (array ($col));
                        $id_set = true;
                        break;
                    } else if (strcasecmp ($col->getName (), 'Title') == 0) {
                        $title_col = $col;
                    }
                }
                
                if (!$id_set) {
                    if ($title_col != null) {
                        $table_obj->setRowIdentifier (array ($title_col));
                    } else {
                        foreach ($table_obj->getColumns () as $col) {
                            if (in_array ($col, $pk_columns, true)) continue;
                            $table_obj->setRowIdentifier (array ($col));
                            break;
                        }
                    }
                }
            }
        }
    }
}

if (count($temp_warn) > 0) {
    $_SESSION[ADMIN_KEY]['warn'] = $temp_warn;
}

if (count($temp_errs) > 0) $_SESSION[ADMIN_KEY]['err'] = $temp_errs;

if ($num_ok_tables > 0) {
    
    // Save the meta-data
    $db->dumpXML('', null);
    
    // Save the form
    if ($form) {
        FormManager::save($form);
    }
    
    redirect('generate_tables_xml.php');
    
} else {
    redirect ('generate_tables_xml.php');
}




function convert_to_english_name ($name) {
    if (strtoupper($name) == 'ID') return 'ID';
    
    $name = trim(str_replace('_', ' ', $name));
    
    // remove trailing IDs
    if (strtolower(substr($name, -3)) == '_id') {
        $name = substr($name, 0, -3);
    } else if (preg_match ('/[^A-Z]I[Dd]$/', $name)) {
        $name = substr($name, 0, -2);
    }
    
    $eng_name = strtoupper($name[0]);
    for ($i = 1; $i < strlen($name); $i++) {
        $chr = $name[$i];
        if (strtolower($chr) == $chr) {
            $eng_name .= $chr;
        } else {
            $eng_name .= ' ' . strtolower($chr);
        }
    }
    
    // strip 'ID' from end of english name
    $eng_name = preg_replace('/\s+id$/', '', $eng_name);
    
    $eng_name = trim(preg_replace('/\s\s+/', ' ', $eng_name));
    return $eng_name;
}

?>
