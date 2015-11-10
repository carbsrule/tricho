<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

$_GET['t'] = '__tools';
require 'head.php';

define ('SEARCH_ALL_NUM_TERMS', 3);
?>
<div id="main_data">

<?php
if ($db->getShowSectionHeadings ()) {
    echo "<h2>Database search and replace</h2>\n";
}

$_GET['section'] = 'db';
require_once 'tools_tabs.php';
?>


<h3>Please <a href="export.php">back-up your database</a> before attempting to use this tool!</h3>


<form method="post" action="search_all.php">
<table>
    <tr><td>&nbsp;</td><th>Search for</th><th>Replace with</th></tr>
<?php
for ($i = 1; $i <= SEARCH_ALL_NUM_TERMS; $i++) {
    echo "    <tr><td>{$i}</td><td><input type=\"text\" name=\"search[{$i}]\" value=\"",
        hsc(@$_POST['search'][$i]),
        "\"></td><td><input type=\"text\" name=\"replacement[{$i}]\" value=\"",
        hsc(@$_POST['replacement'][$i]), "\"></td></tr>\n";
}
?>
    <tr><td colspan="3">Perform replacement(s) <input type="checkbox" name="replace" value="1"></td>
    <tr><td colspan="3" align="right"><input type="submit" value="Search"></td></tr>
</table>
</form>

<?php
if (@count($_POST['search']) > 0) {
    foreach ($_POST['search'] as $post_id => $search_term) {
        if ($search_term == '') {
            unset($_POST['search'][$post_id]);
            unset($_POST['replacement'][$post_id]);
        }
    }
}


if (@count($_POST['search']) > 0) {
    
    echo "<h3>Results</h3>\n";
    
    $tables_rs = execq("SHOW TABLES");
    if ($tables_rs->rowCount() > 0) {
        echo "<table class=\"form-table\">\n";
        echo "    <tr><th>Table</th><th>PK</th><th>Before</th><th>After</th>";
        if (@$_POST['replace']) {
            echo "<th>Result</th>";
        }
        echo "</tr>\n";
        
        $records_matched = 0;
        $altrow = 2;
        
        while ($table_row = fetch_row($tables_rs)) {
            reset($table_row);
            list($table_num, $table_name) = each($table_row);
            
            // find pk of table, and column names
            $pk_col_names = array ();
            $col_names = array ();
            
            $fields_rs = execq("SHOW COLUMNS FROM `{$table_name}`");
            
            while ($fields_row = fetch_assoc($fields_rs)) {
                // Field        Type        Null        Key        Default        Extra
                $col_names[] = $fields_row['Field'];
                if ($fields_row['Key'] == 'PRI') {
                    $pk_col_names[] = $fields_row['Field'];
                }
            }
            
            // Create a list of where clauses that matches the search terms provided
            $clauses = array ();
            foreach ($col_names as $col_id => $col_name) {
                foreach ($_POST['search'] as $post_id => $search_term) {
                    $clauses[] = "`{$col_name}` LIKE '%". $search_term. "%'";
                }
            }
            
            if (count($clauses) > 0) {
                // Perform the actual searches
                $q = "SELECT * FROM `{$table_name}` WHERE ". implode (' OR ', $clauses);
                $data_rs = execq($q);
                
                while ($data_row = fetch_assoc($data_rs)) {
                    if ($altrow == 2) { $altrow = 1; } else { $altrow = 2; }
                    
                    // Table - PK - Before - Query
                    echo "    <tr valign=\"top\" class=\"altrow{$altrow}\">\n";
                    echo "        <td>{$table_name}</td>\n";
                    echo "        <td>";
                    $pk_clauses = array ();
                    foreach ($pk_col_names as $pk_col_id => $pk_col_name) {
                        if ($pk_col_id > 0) echo ',';
                        echo $data_row[$pk_col_name];
                        if (is_numeric ($data_row[$pk_col_name])
                                and ((int) $data_row[$pk_col_name]) == $data_row[$pk_col_name]) {
                            $pk_clauses[] = "`{$pk_col_name}` = {$data_row[$pk_col_name]}";
                        } else {
                            $pk_clauses[] = "`{$pk_col_name}` = " .
                                sql_enclose($data_row[$pk_col_name]);
                        }
                    }
                    echo "</td>\n";
                    
                    // check each column for a match, display the column if so
                    $matching_fields = array ();
                    foreach ($col_names as $col_id => $col_name) {
                        foreach ($_POST['search'] as $post_id => $search_term) {
                            if (stripos($data_row[$col_name], $search_term) !== false) {
                                if (!isset ($matching_fields[$col_name]['before'])) {
                                    $matching_fields[$col_name]['before'] = $data_row[$col_name];
                                }
                                
                                if (!isset ($matching_fields[$col_name]['after'])) {
                                    $matching_fields[$col_name]['after'] = str_ireplace (
                                        $search_term,
                                        $_POST['replacement'][$post_id],
                                        $data_row[$col_name]
                                    );
                                } else {
                                    $matching_fields[$col_name]['after'] = str_ireplace (
                                        $search_term,
                                        $_POST['replacement'][$post_id],
                                        $matching_fields[$col_name]['after']
                                    );
                                }
                            }
                        }
                    }
                    
                    // If there were some matches found, update the record
                    $first_match = true;
                    $replace_query = "UPDATE `{$table_name}` SET ";
                    echo "        <td>";
                    foreach ($matching_fields as $matching_field_name => $matching_field_data) {
                        if ($first_match) {
                            $first_match = false;
                        } else {
                            echo "<br><br>\n";
                            $replace_query .= ', ';
                        }
                        echo "<b>{$matching_field_name}</b>:<br>\n";
                        echo htmlspecialchars ($matching_field_data['before']);
                        $replace_query .= "`{$matching_field_name}` = ".
                            sql_enclose($matching_field_data['after']);
                    }
                    echo "</td>\n";
                    $replace_query .= " WHERE ". implode (' AND ', $pk_clauses);
                    
                    // And explain to the user what was done
                    $first_match = true;
                    echo "        <td>";
                    foreach ($matching_fields as $matching_field_name => $matching_field_data) {
                        if ($first_match) {
                            $first_match = false;
                        } else {
                            echo "<br><br>\n";
                        }
                        echo "<b>{$matching_field_name}</b>:<br>\n";
                        echo htmlspecialchars ($matching_field_data['after']);
                    }
                    echo "</td>\n";
                    
                    if (@$_POST['replace']) {
                        echo "        <td>";
                        // echo htmlspecialchars ($replace_query);
                        if (execq($replace_query, true, true, false)) {
                            $records_matched++;
                            echo "Success";
                        }
                        echo "</td>\n";
                        
                    } else {
                        $records_matched++;
                    }
                    
                    echo "    </tr>\n";
                }
            }
            
        }
        echo "</table>\n";
        
        echo "<p>{$records_matched} ";
        if (@$_POST['replace']) {
            echo 'replacement(s) performed';
        } else {
            echo 'match(es) found';
        }
        echo "</p>\n";
        
    } else {
        echo "No tables defined";
    }
}
?>
</div>

<?php
require 'foot.php';
?>
