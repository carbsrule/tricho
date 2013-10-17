<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/*
column_definition:
    col_name type [NOT NULL | NULL] [DEFAULT default_value]
        [AUTO_INCREMENT] [UNIQUE [KEY] | [PRIMARY] KEY]
        [COMMENT 'string'] [reference_definition]

type:
    TINYINT[(length)] [UNSIGNED] [ZEROFILL]
  | SMALLINT[(length)] [UNSIGNED] [ZEROFILL]
  | MEDIUMINT[(length)] [UNSIGNED] [ZEROFILL]
  | INT[(length)] [UNSIGNED] [ZEROFILL]
  | INTEGER[(length)] [UNSIGNED] [ZEROFILL]
  | BIGINT[(length)] [UNSIGNED] [ZEROFILL]
  | REAL[(length,decimals)] [UNSIGNED] [ZEROFILL]
  | DOUBLE[(length,decimals)] [UNSIGNED] [ZEROFILL]
  | FLOAT[(length,decimals)] [UNSIGNED] [ZEROFILL]
  | DECIMAL(length,decimals) [UNSIGNED] [ZEROFILL]
  | NUMERIC(length,decimals) [UNSIGNED] [ZEROFILL]
  | DATE
  | TIME
  | TIMESTAMP
  | DATETIME
  | YEAR
  | CHAR(length) [BINARY | ASCII | UNICODE]
  | VARCHAR(length) [BINARY]
  | BINARY(length)
  | VARBINARY(length)
  | TINYBLOB
  | BLOB
  | MEDIUMBLOB
  | LONGBLOB
  | TINYTEXT [BINARY]
  | TEXT [BINARY]
  | MEDIUMTEXT [BINARY]
  | LONGTEXT [BINARY]
  | ENUM(value1,value2,value3,...)
  | SET(value1,value2,value3,...)
  | spatial_type
*/

function db_export_quote ($col, $value) {
    global $export, $numeric_types;
    if (is_null ($value)) {
        return 'NULL';
    } else {
        if (in_array ($export['col_type'][$col], $numeric_types)) {
            return $value;
        } else {
            return sql_enclose ($value, false);
        }
    }
}

$numeric_types = array (
    'BIT','TINYINT','SMALLINT','MEDIUMINT','INT','INTEGER','BIGINT',
    'REAL','DOUBLE','FLOAT',
    'DECIMAL','NUMERIC'
);
$text_blob_types = array (
    'TINYBLOB', 'BLOB', 'MEDIUMBLOB', 'LONGBLOB',
    'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT'
);
$varstring_types = array (
    'VARCHAR', 'VARBINARY'
);

require '../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

// allow the script to run for 15 minutes
set_time_limit (900);

if (@count ($_POST['tables']) == 0) {
    redirect ('export.php');
}

ksort ($_POST['tables']);

// determine engine types & default collations for all tables in DB
$engine_types = array ();
$collations = array ();
$res = execq("SHOW TABLE STATUS");
while ($row = fetch_assoc($res)) {
    // MySQL changed the column name between versions 4 and 5
    if (isset ($row['Engine'])) {
        // MySQL 5
        $engine_types[$row['Name']] = $row['Engine'];
    } else if (isset ($row['Type'])) {
        // MySQL 4
        $engine_types[$row['Name']] = $row['Type'];
    }
    
    // NB: table collations were added in MySQL 4.1:
    // see http://dev.mysql.com/doc/refman/4.1/en/charset.html
    $collations[$row['Name']] = $row['Collation'];
}

if ($_POST['dl'] == 1) {
    require_once '../tricho.php';
    test_admin_login ();
    
    $safe_name = str_replace(' ', '_', tricho\Runtime::get('site_name'));
    $safe_name = preg_replace("/[^A-Za-z0-9_\-]+/", '', $safe_name);
    $safe_name = strtolower($safe_name);
    $file_name = $safe_name . "_dump_mysql{$_POST['mysql_version']}_" .
        date ('Y-m-d') . '.sql';
    
    header ('Content-type: text/plain');
    header ("Content-Disposition: attachment; filename={$file_name}");
    header ("Cache-Control: cache, must-revalidate");
    header ("Pragma: public");
    function write_out ($txt) {
        echo $txt, "\n";
    }
} else {
    require 'head.php';
    echo "<div id=\"main_data\">\n";
    
    echo "    <h2>Export Database</h2>\n";
    
    $_GET['section'] = 'db';
    require_once 'tools_tabs.php';
    
    echo '<textarea rows="25" cols="120" wrap="off" style="width: 100%" id="export">';
    function write_out ($txt) {
        echo htmlspecialchars ($txt), "\n";
    }
}

$db = Database::parseXML ('tables.xml');
foreach ($_POST['tables'] as $table => $val) {
    
    // export is the generic table-specific data store
    $export = array();
    if ($_POST['mode'] == 'essential') {
        $export['s'] = true;
        $db_table = $db->getTable ($table);
        if ($db_table != null) {
            if ($db_table->isStatic ()) {
                $export['u'] = true;
            }
        }
    } else {
        if (strpos($val, 's') !== false) $export['s'] = true;
        if (strpos($val, 'd') !== false) $export['d'] = true;
        if (strpos($val, 'u') !== false) {
            $export = array ('u' => true);
        }
    }
    
    /* STRUCTURE */
    if ($export['s']) {
        // $col_names = array ();
        $col_defns = array ();
        $pk_fields = array ();
        $res = execq("SHOW FULL COLUMNS FROM `{$table}`");
        if ($export['d']) {
            $export['col_type'] = array();
        }
        while ($row = fetch_assoc($res)) {
            // name and type
            $col_defn = '`' . $row['Field']. '` '. $row['Type'];
            
            // determine our raw type
            preg_match("/[A-Za-z]*/", $row['Type'], $matches);
            $raw_type = strtoupper($matches[0]);
            
            // build column type (if needed later)
            if ($export['d']) {
                $export['col_type'][$row['Field']] = $raw_type;
            }
            
            // determine extra options
            $extra = strtoupper ($row['Extra']);
            $extra_options = array ('UNSIGNED', 'ZEROFILL', 'BINARY', 'ASCII', 'UNICODE');
            foreach ($extra_options as $option) {
                if (strpos($extra, $option) !== false) {
                    $col_defn .= " {$option}";
                }
            }
            
            // collation
            $collation = $row['Collation'];
            if ($collation != '' and $collation != $collations[$table]) {
                $charset = reset (explode ('_', $collation));
                $col_defn .= " CHARACTER SET {$charset} COLLATE {$collation}";
            }
            
            // null
            if (strtolower ($row['Null']) == 'yes') {
                $null = true;
            } else {
                $null = false;
                $col_defn .= ' NOT NULL';
            }
            
            // default
            if ($null or $row['Default'] !== null) {
                $col_defn .= " default " . sql_enclose($row['Default']);
            }
            
            // auto increment
            if (stripos ($extra, 'AUTO_INCREMENT') !== false) {
                if ($export['d']) {
                    $export['auto_inc'] = '`' . $row['Field'] . '` ' . $col_defn;
                }
                $col_defn .= ' auto_increment';
            }
            
            // primary key
            $col_defns[] = $col_defn;
            if (strtoupper ($row['Key']) == 'PRI') {
                $pk_fields[] = "`". $row['Field']. "`";
            }
        }
        
        // build CREATE TABLE
        if (count ($pk_fields) > 0) {
            $col_defns[] = "PRIMARY KEY (". implode (',', $pk_fields). ')';
        }
        if ($_POST['inc_del'] == 1) write_out ("DROP TABLE IF EXISTS `{$table}`;");
        
        // setup indexes (apart from primary key)
        $indexes = array ();
        $res = execq("SHOW INDEXES FROM `{$table}`");
        while ($row = fetch_assoc($res)) {
            if ($row['Key_name'] != 'PRIMARY') {
                if ($row['Non_unique'] == 0) {
                    $indexes[$row['Key_name']]['type'] = 'UNIQUE';
                }
                
                if ($row['Index_type'] == 'FULLTEXT') {
                    $indexes[$row['Key_name']]['type'] = 'FULLTEXT';
                } else if ($row['Index_type'] == 'SPATIAL') {
                    $indexes[$row['Key_name']]['type'] = 'SPATIAL';
                } else {
                    // The word "algorithm" here means what MySQL calls the type of index: BTREE | HASH | RTREE
                    // This only applies to indexes that support multiple types/algorithms,
                    // i.e. not FULLTEXT or SPATIAL indexes
                    $indexes[$row['Key_name']]['algorithm'] = $row['Index_type'];
                }
                
                $indexes[$row['Key_name']]['cols'][] = array (
                    'name' => $row['Column_name'],
                    'sub' => $row['Sub_part']
                );
            }
        }
        
        if (@count ($indexes) > 0) {
            foreach ($indexes as $index_name => $index) {
                
                $col_defn = ($index['type']? $index['type']. ' ': ''). "KEY `{$index_name}`";
                if ($_POST['mysql_version'] > 4 and $index['algorithm'] != '') {
                    // There doesn't seem to be a lot of point specifying this - mysqldump doesn't
                    //$col_defn .= " USING {$index['algorithm']}";
                }
                $col_defn .= "    (";
                $cols = 0;
                foreach ($index['cols'] as $index_defn) {
                    if (++$cols > 1) {
                        $col_defn .= ',';
                    }
                    $col_defn .= "`{$index_defn['name']}`";
                    if ($index_defn['sub'] != '') $col_defn .= " ({$index_defn['sub']})";
                }
                $col_defn .= ")";
                $col_defns[] = $col_defn;
            }
        }
        
        $table_defn = "CREATE TABLE IF NOT EXISTS `{$table}` (\n";
        $num_cols = count ($col_defns);
        $col_num = 1;
        foreach ($col_defns as $col_defn) {
            $table_defn .= "    {$col_defn}";
            if ($col_num++ < $num_cols) {
                $table_defn .= ",\n";
            } else {
                $table_defn .= "\n";
            }
        }
        $table_defn .= ") ENGINE={$engine_types[$table]}";
        $collation = $collations[$table];
        if ($collation != '') {
            $charset = reset (explode ('_', $collation));
            $table_defn .= " DEFAULT CHARSET={$charset} COLLATE={$collation}";
        }
        $table_defn .= ';';
        
        write_out ($table_defn);
    }
    
    /* AutoIncrement for autoincrement column */
    if (isset ($export['auto_inc'])) {
        $col = explode (' ', $export['auto_inc']);
        $col = $col[0];
        $res = execq("SELECT {$col} FROM `{$table}` WHERE {$col} < 1");
        if ($res->rowCount() > 0) {
            if ($_POST['mysql_version'] > 4) {
                write_out ("SET sql_mode='NO_AUTO_VALUE_ON_ZERO';");
            } else {
                write_out ("-- You will need to manually add your zero elements for them to work with MySQL 4.0");
            }
        }
    }
    
    if ($export['s'] and $export['d']) {
        write_out ('');
    }
    
    
    /* DATA */
    
    if ($export['d'] or $export['u']) {
        // build the column type array if it does not exist
        if (!isset($export['col_type'])) {
            $export['col_type'] = array();
            $res = execq("SHOW COLUMNS FROM `{$table}`");
            while ($row = fetch_assoc($res)) {
                preg_match("/[A-Za-z]*/", $row['Type'], $matches);
                $raw_type = strtoupper ($matches[0]);
                
                $export['col_type'][$row['Field']] = $raw_type;
            }
        }
        
        $res = execq("SELECT * FROM `{$table}`");
        while ($row = fetch_assoc($res)) {
            // echo "Row: <pre>", print_r ($row, true), "</pre>\n";
            
            $mysql_insert = '';
            $generic_insert = '(';
            $i = 0;
            foreach ($row as $col => $value) {
                if (++$i != 1) {
                    $mysql_insert .= ', ';
                    $generic_insert .= ', ';
                }
                $mysql_insert .= "`{$col}` = ". db_export_quote ($col, $value);
                $generic_insert .= "`{$col}`";
            }
            $i = 0;
            $generic_insert .= ') VALUES (';
            foreach ($row as $col => $value) {
                if (++$i != 1) $generic_insert .= ', ';
                $generic_insert .= db_export_quote ($col, $value);
            }
            $generic_insert .= ')';
            
            if ($export['d']) {
                $q = "INSERT INTO `{$table}` {$generic_insert};";
            } else if ($export['u']) {
                $q = "INSERT INTO `{$table}` SET {$mysql_insert} ".
                    "ON DUPLICATE KEY UPDATE {$mysql_insert};";
            }
            
            write_out ($q);
        }
    }
    
    if ($export['s'] or $export['d'] or $export['u']) {
        write_out ('');
    }
}
if ($_POST['dl'] != 1) {
    echo "</textarea></div>\n</body>\n</html>";
}
?>
