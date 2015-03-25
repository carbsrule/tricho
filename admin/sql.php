<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\DbConn\ConnManager;
use Tricho\Meta\Database;
use Tricho\Query\RawQuery;
use Tricho\Util\SqlParser;

require_once '../tricho.php';

test_setup_login (true, SETUP_ACCESS_LIMITED);

// allow the script to run for 15 minutes
set_time_limit (900);

$_GET['t'] = '__tools';
require 'head.php';

echo "<div id=\"main_data\">\n";
echo "<script type=\"text/javascript\" src=\"sql.js\"></script>\n";
echo "<h2>MySQL Query Tool</h2>";

$_GET['section'] = 'db';
require_once 'tools_tabs.php';

$conn = ConnManager::get_active();

// If this is the initial import - a file upload and there are no real tables in the database
//    - Turn action logging off
//    - Do not confirm successful queries
// Real tables are tables that do not begin with '_tricho'
$do_query_logging = true;
if (@$_FILES['sql_file']['tmp_name']) {
    $q = "SHOW TABLES";
    $res = execq($q);
    
    $num_real_tables = 0;
    while ($row = $res->fetch(PDO::FETCH_NUM)) {
        if (strncmp($row[0], '_tricho', 7) == 0) continue;
        $num_real_tables++;
    }
    
    if ($num_real_tables == 0) {
        echo "<p>Initial import mode: actions will not be logged, and successful queries will not be reported.</p>";
        $_POST['show_success'] = 0;
        $do_query_logging = false;
    }
}


// after how many chars we should break up large fields. Defaults to 50
$break_point = 50;
if (defined ('SQL_FIELD_BREAK')) {
    $break_point = SQL_FIELD_BREAK;
}

settype ($_POST['max_fails'], 'int');
if ($_POST['max_fails'] <= 0) $_POST['max_fails'] = 100;

$parser = new SqlParser();
$queries = array();
$file = @$_FILES['sql_file'];
if (is_uploaded_file($file['tmp_name'])) {
    $file_contents = file_get_contents($file['tmp_name']);
    $queries = $parser->parse($file_contents);
} else if ($file['error'] != UPLOAD_ERR_NO_FILE
        and $file['error'] != UPLOAD_ERR_OK) {
    report_error("File upload failed");
}
$queries = $parser->parse(@$_POST['query'], $queries);

if (count($queries) > 0) {
    
    // work out which tables the user can't access
    $disabled_tables = array ();
    $tables = $db->getTables ();
    foreach ($tables as $table) {
        if ($table->getAccessLevel () == TABLE_ACCESS_SETUP_FULL and
                $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
            $disabled_tables[] = $table->getName ();
        }
    }
    
    $successes = 0;
    $errors = 0;
    $num_comments = 0;
    
    echo "<h3>SQL Result</h3>\n";
    
    $all_start_time = microtime (true);
    $expando_num = 0;
    $html = 0;
    
    foreach ($queries as $q_id => $query) {
        
        $query = trim ($query);
        
        // display comments, don't try to execute them
        $comments = array ();
        $comment = SqlParser::getComment($query);
        while ($comment) {
            $comments[] = $comment;
            $comment = SqlParser::getComment($query);
        }
        
        if (count($comments) > 0) {
            $num_comments++;
            $curr_comment = 0;
            $comment_text = '';
            foreach ($comments as $comment) {
                $comment_text .= "<br>\n". htmlspecialchars ($comment);
            }
            echo "<a name=\"comment{$num_comments}\"></a>",
                "<div class=\"sql_comment\"><b>Comment:</b>{$comment_text}</div>\n";
        }
        
        $query = trim ($query);
        
        // ignore empty queries, say after a final query that ends in ;
        if ($query != '') {
            
            $query_allowed = true;
            foreach ($disabled_tables as $disabled_table) {
                if (strpos ($query, $disabled_table) !== false) {
                    $query_allowed = false;
                }
            }
            
            $start_time = microtime (true);
            
            $reason = '';
            $result = null;
            if ($query_allowed) {
                try {
                    $query = new RawQuery($query);
                    $query->set_internal(true);
                    $result = execq($query);
                } catch (QueryException $qe) {
                    $reason = $qe->getMessage();
                }
            } else {
                $reason = 'Invalid table';
            }
            
            if ($result === null) {
                ++$errors;
                $query = add_br($query);
                $query = str_replace(' ', '&nbsp;', $query);
                echo "<a name='error{$errors}'></a>";
                echo "<div class='sql_error'><b>Error:</b> ",
                    "Cannot execute query:<br>\n",
                    "<div>$query</div><b>Reason:</b> {$reason}</div>\n";
                if ($errors >= $_POST['max_fails']) {
                    echo "<div class='sql_error'><b>FINAL ERROR:</b> Too many failed queries, giving up.</div>\n";
                    break;
                }
                continue;
            }
            
            // Log the queries. This is almost always, except in the case of the initial import
            
            $matches = array ();
            $match = preg_match ('/[a-z]\s/i', $query, $matches, PREG_OFFSET_CAPTURE);
            if ($match) {
                list($junk, $offset) = $matches[0];
                $query_type = substr($query, 0, $offset + 1);
                $query_type = strtoupper(ltrim($query_type, "( \t\n\r\0\x0B"));
            }
            
            if ($do_query_logging) {
                if ($match) {
                    switch ($query_type) {
                        case 'SELECT':
                        case 'INSERT':
                        case 'UPDATE':
                        case 'DELETE':
                        case 'EXPLAIN':
                        case 'SHOW':
                            break;
                        
                        case 'TRUNCATE':
                            log_action("Query via sql.php", '-- ' . $query);
                            break;
                        
                        default:
                            log_action("Query via sql.php", $query);
                    }
                } else {
                    $log_msg = "Failed to analyse query via sql.php";
                    log_action($log_msg, '-- ' . $query);
                }
            }
            
            // Always report the number of rows found for SQL_CALC_FOUND_ROWS queries
            $report_found_rows = false;
            if (preg_match ('/SELECT\s+.*SQL_CALC_FOUND_ROWS/i', $query)) {
                $report_found_rows = true;
            }
            
            $successes++;
            
            if ($result !== true or $_POST['show_success'] == 1 or $report_found_rows) {
                
                $time_taken = round ((microtime (true) - $start_time) * 1000, 2). ' ms';
                
                echo "<p>Sucessful query ({$time_taken}): <code>$query</code>";
                
                if (in_array($query_type, array('UPDATE', 'DELETE', 'TRUNCATE'))) {
                    
                    $affected_rows = $result->rowCount();
                    echo "<br><b>{$affected_rows} row". ($affected_rows == 1? '': 's'). " affected</b></p>\n";
                    
                } else {
                    $res_array = $result->fetch(PDO::FETCH_NUM);
                    
                    if ($res_array) {
                        
                        $numeric_types = array (
                            'tinyint',
                            'smallint',
                            'mediumint',
                            'int',
                            'bigint',
                            'float',
                            'double',
                            'real',
                            'decimal',
                            'numeric',
                            'long',
                            'longlong',
                            'newdecimal'
                        );
                        
                        $col_names = array ();
                        $col_aligns = array ();
                        $possible_bit_fields = array ();
                        $num_fields = count($res_array);
                        for ($field_counter = 0; $field_counter < $num_fields; $field_counter++) {
                            $col_data = $result->getColumnMeta($field_counter);
                            $col_names[] = $col_data['name'];
                            
                            // N.B. The MySQL PDO driver doesn't appear to set
                            // the native_type value for certain columns:
                            // TINYINT, for example. Also, pdo_type seems,
                            // erroneously, to always be 2 (PDO::PARAM_STR), so
                            // that can't be used reliably either.
                            // Both of these results apply to PHP 5.3.26,
                            // using MySQL 5.5.34
                            if (!isset($col_data['native_type'])) {
                                $col_data['native_type'] = 'tinyint';
                            }
                            $col_type = strtolower($col_data['native_type']);
                            
                            $align = '';
                            if (in_array ($col_type, $numeric_types)) {
                                $align = ' align="right"';
                            } else if ($col_type == 'bit' or $col_type == 'unknown') {
                                $possible_bit_fields[] = $field_counter;
                            }
                            $col_aligns[] = $align;
                        }
                        
                        echo "</p><table class=\"sql_result\" cellpadding=\"3\" cellspacing=\"0\">\n";
                        echo "    <tr>\n";
                        foreach ($col_names as $col_num => $key) {
                            echo "        <th{$col_aligns[$col_num]}>$key</th>\n";
                        }
                        echo "    </tr>\n";
                        
                        $totalrows = 0;
                        
                        while ($res_array) {
                            echo "    <tr valign=\"top\">\n";
                            foreach ($res_array as $col_num => $col) {
                                if ($col === null) {
                                    // handle NULL
                                    $out = '<em class="sql_null">null</em>';
                                    
                                } else if (in_array ($col_num, $possible_bit_fields)) {
                                    // handle bit fields
                                    if ($col === chr (0)) {
                                        $out = 0;
                                    } else if ($col === chr (1)) {
                                        $out = 1;
                                    }
                                    
                                } else {
                                    // regular columns
                                    if ($break_point <= 0 or strlen ($col) <= $break_point) {
                                        
                                        $out = htmlspecialchars ($col);
                                        
                                    } else {
                                        $expando_num++;
                                        
                                        // determine if this field contains HTML
                                        $html = preg_match('/\<[a-zA-Z]+.*>/', $col);
                                        
                                        if ($html > 0) {
                                            $out = "<div class=\"\" id=\"opener{$expando_num}\">";
                                            $out .= htmlspecialchars (substr ($col, 0, $break_point));
                                            $out .= " <a class=\"expando_link\" href=\"#\" ".
                                                "onclick=\"open_expando({$expando_num}); return false;\">More&nbsp;&raquo;</a>";
                                            $out .= "</div>";
                                            $out .= "<div id=\"expando{$expando_num}\" style=\"display: none;\">";
                                            $out .= $col;
                                            $out .= " <a class=\"expando_link\" href=\"#\" ".
                                                "onclick=\"close_expando({$expando_num}); return false;\">&laquo;&nbsp;Less</a>";
                                            $out .= "</div>";
                                        
                                        } else {
                                            $out = htmlspecialchars (substr ($col, 0, $break_point));
                                            $out .= "<span class=\"\" id=\"opener{$expando_num}\">";
                                            $out .= " <a class=\"expando_link\" href=\"#\" ".
                                                "onclick=\"open_expando({$expando_num}); return false;\">More&nbsp;&raquo;</a>";
                                            $out .= "</span>";
                                            $out .= "<span id=\"expando{$expando_num}\" style=\"display: none;\">";
                                            $out .= htmlspecialchars (substr($col, $break_point));
                                            $out .= " <a class=\"expando_link\" href=\"#\" ".
                                                "onclick=\"close_expando({$expando_num}); return false;\">&laquo;&nbsp;Less</a>";
                                            $out .= "</span>";
                                        }
                                    }
                                }
                                
                                echo "        <td{$col_aligns[$col_num]}>{$out}</td>\n";
                            }
                            echo "    </tr>\n";
                            $totalrows++;
                            $res_array = $result->fetch(PDO::FETCH_NUM);
                        }
                        echo "</table>\n<p>{$totalrows} row", ($totalrows != 1? 's': ''), ' returned';
                        
                    } else {
                        
                        echo "<br><b>No rows returned</b>";
                        
                    }
                    
                    if ($report_found_rows) {
                        $res = execq('SELECT FOUND_ROWS()');
                        $num_rows = $res->fetchColumn(0);
                        echo "<br>{$num_rows} matching ", ($num_rows == 1? 'row': 'rows'), ' found';
                    }
                    echo "</p>\n";
                }
            }
        }
    }
    
    // summary
    $all_time_taken = round ((microtime (true) - $all_start_time) * 1000, 2). ' ms';
    
    echo "<div class=\"sql_summary\">";
    echo "<h2>Summary ({$all_time_taken})</h2>";
    if ($successes == 1) {
        echo '<p style="color: green;">1 query executed successfully</p>';
    } else {
        echo '<p style="color: green;">', $successes, ' queries executed successfully</p>';
    }
    
    // comments
    if ($num_comments > 0) {
        echo "<p style='color: #606060;'>{$num_comments} comment", ($num_comments == 1? '': 's'), ": ";
        for ($x = 1; $x <= $num_comments; $x++) {
            echo "<a href='#comment{$x}'>{$x}</a> ";
        }
        echo "</p>";
    }
    
    // errors
    if ($errors > 0) {
        echo "<p style='color: red;'>{$errors} quer";
        echo ($errors == 1? 'y': 'ies');
        echo " failed: ";
        for ($x = 1; $x <= $errors; $x++) {
            echo "<a href='#error{$x}'>{$x}</a> ";
        }
        echo "</p>";
    }
    echo "</div>";
}
?>

<p style="margin-bottom: 1px;">Enter the query/queries you wish to execute on database <em><?= $conn->get_param('db'); ?></em>:</p>
<table id="sql_container"><tr><td>
<?php

// Main form (execute queries)
$ini_max_upload = ini_get ('upload_max_filesize');

// Determine max file upload size from the ini settings
$matches = array ();
preg_match ('/^([0-9]+)([kKmM])$/', $ini_max_upload, $matches);
list ($junk, $ini_max_upload, $type) = $matches;
if ($ini_max_upload > 0) {
    switch (strtolower ($type)) {
        case 'g':
            $ini_max_upload *= 1024;
        
        case 'm':
            $ini_max_upload *= 1024;
        
        case 'k':
            $ini_max_upload *= 1024;
    }
}
$max_upload_size = bytes_to_human ($ini_max_upload);
?>
<form action="sql.php" method="post" enctype="multipart/form-data">
        <p>Use queries from file (max size <?= $max_upload_size; ?>): <input type="file" name="sql_file"></p>
        <p>
            <label for="show_success" class="label_plain"><input id="show_success" type="checkbox" name="show_success" <?= ((@$_POST['show_success'] == 1 or !isset($_POST['query']))? 'checked' : ''); ?> value="1"> Show confirmation of successful queries that don't return a result set </label>
        </p>
        <p>Stop trying after <input type="text" name="max_fails" size="3" maxlength="4" value="<?=    $_POST['max_fails']; ?>"> failed queries</p>
        <textarea name="query" cols="40" rows="12"><?= htmlspecialchars(@$_POST['query']); ?></textarea>
        <p><input type="submit" value="Execute"></p>
    </form>
</td>

<td id="mini_struct">
    <form method="get" action="sql.php">
        <select id="table" onChange="update_side_list();">
            <option value="">- Please Select -</option>
<?php
$db = Database::parseXML();
$q = "SHOW TABLES";
$res = execq($q);
while ($table_name = $res->fetchColumn()) {
    
    $table = $db->get($table_name);
    // check user has access to the table
    if ($table != null and $table->getAccessLevel () == TABLE_ACCESS_SETUP_FULL and
        $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
        continue;
    }
    
    echo "            <option value=\"{$table_name}\">{$table_name}</option>\n";
}
?>
        </select>
        
        <p><label for="type" class="label_plain">
            <input type="checkbox" value="1" id="type" onClick="update_side_list();" checked>Show types
        </label></p>
        
        <div id="sql_collist"></div>
    </form>
</td></tr></table>


</div>
<?php
require "foot.php";
?>