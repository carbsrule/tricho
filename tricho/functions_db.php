<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\DbConn\ConnManager;
use Tricho\DbConn\DbConn;
use Tricho\Query\QueryField;
use Tricho\Util\SqlParser;

/**
 * Executes a database query. This is a shorthand method.
 * @param $q mixed The query to execute (a Query or a string)
 * @return PDOStatement
 * @throws QueryException if the query fails
 */
function execq($q) {
    $conn = ConnManager::get_active();
    if (!$conn) throw new Exception('No active connection');
    return $conn->exec($q, PDO::FETCH_ASSOC);
}


function fetch_assoc($res) {
    if (!($res instanceof PDOStatement)) return false;
    return $res->fetch(PDO::FETCH_ASSOC);
}

function fetch_row($res) {
    if (!($res instanceof PDOStatement)) return false;
    return $res->fetch(PDO::FETCH_NUM);
}

/**
 * @param string $q An SQL query that may contain private data
 * @return string The clean SQL query (i.e. with private data converted to ???)
 */
function sql_remove_private ($q) {
    $q = (string) $q;
    
    $parser = new SqlParser();
    $parser->parse ($q);
    $tokens = $parser->getTokens ();
    
    $query = "";
    $in_encrypt = false;
    $num_brackets = 0;
    foreach ($tokens as $token) {
        $str = $token['value'];
        
        if ($token['type'] == SqlParser::QUERY) {
            while (strlen ($str) > 0) {
                if (preg_match ('/^(MD5|SHA1?)\s*\(/i', $str, $matches)) {
                    $str = substr ($str, strlen ($matches[0]));
                    $in_encrypt = true;
                    ++$num_brackets;
                } else if ($in_encrypt) {
                    if ($str[0] == ')') {
                        if (--$num_brackets == 0) $in_encrypt = false;
                    } else if ($str[0] == '(') {
                        ++$num_brackets;
                    }
                    $str = substr ($str, 1);
                } else {
                    $str = substr ($str, 1);
                }
            }
        } else if (($token['type'] == SqlParser::STRING_SINGLE or
            $token['type'] == SqlParser::STRING_DOUBLE) and $in_encrypt) {
            // replace private data with ???
            $token['value'] = "???";
        }
        $query .= $token['value'];
    }
    
    return $query;
}


/**
 * Checks to see if a SELECT query is unoptimised.
 * @param string $query A SELECT query
 * @return array [0] bool True if the query was determined to be unoptimised.
 *         N.B. being unoptimised doesn't guarantee that there's a way to
 *         optimise the query.
 *         [1] array The rows of the EXPLAIN result
 */
function check_query_unoptimised($query) {
    // Query is optimised if:
    // 1) Both of the following conditions are true
    //    (a) There is no WHERE clause
    //    (b) The only non-indexed EXPLAIN row refers to the base table
    // 2) All tables are indexed
    if (preg_match("/\swhere\s/i", $query)) {
        $max_unindexed = 0;
    } else {
        $max_unindexed = 1;
    }
    
    $res = execq("EXPLAIN {$query}");
    $explain = array();
    $num_unindexed = 0;
    while ($row = $res->fetch()) {
        $explain[] = $row;
        if ($row['key']) continue;
        ++$num_unindexed;
    }
    
    $unoptimised = $num_unindexed > $max_unindexed;
    return array($unoptimised, $explain);
}


/**
 * Puts the rows returned by an EXPLAIN query into a human-readable string
 * @param array $explain The rows from the EXPLAIN query
 */
function format_explain(array $explain) {
    $explain_string = str_repeat('-', 72). "\n";
    foreach ($explain as $row_data) {
        $keys = array();
        $key_field_names = array('key', 'key_len', 'possible_keys');
        foreach ($key_field_names as $field_name) {
            $keys[$field_name] = $row_data[$field_name];
            unset($row_data[$field_name]);
        }
        $lines = array('', '');
        foreach ($row_data as $heading => $data) {
            $col_width = max(strlen($heading), strlen($data)) + 3;
            $lines[0] .= str_pad($heading, $col_width, ' ', STR_PAD_RIGHT);
            $lines[1] .= str_pad($data, $col_width, ' ', STR_PAD_RIGHT);
        }
        $explain_string .= implode("\n", $lines) . "\n";
        if ($keys['key']) {
            $explain_string .= "* Key: {$keys['key']} ({$keys['key_len']}) " .
                "from possible: {$keys['possible_keys']}\n";
        } else {
            if ($keys['possible_keys']) {
                $explain_string .= "! No key used; available: " .
                    "{$keys['possible_keys']}\n";
            } else {
                $explain_string .= "! No keys available\n";
            }
        }
        $explain_string .= str_repeat('-', 72). "\n";
    }
    return $explain_string;
}


/**
 * Analyses a query to determine if it should be reported for being slow, and if it should,
 * sends an email to the admins when a slow query has taken place.
 *
 * @param string $query The query that was slow
 * @param float $time_taken The time taken to execute the query (in ms)
 * @param float $max_time The maximum expected execution time for the query
 *        (also in ms)
 * @param bool $unoptimised True for an unoptimised SELECT query
 * @param mixed $explain An array containing the result of an EXPLAIN query, or
 *        null if not applicable
 */
function slow_query_email($query, $time_taken, $max_time, $unoptimised, $explain) {
    $time_taken = round($time_taken, 3);
    
    // Remove anything from the backtrace after the query itself
    $backtrace = debug_backtrace();
    $db_call_key = -1;
    foreach ($backtrace as $entry_num => &$entry) {
        if (!isset($entry['class']) and $entry['function'] == 'execq') {
            $db_call_key = $entry_num;
            unset($entry['args']);
            continue;
        }
        if (!(@$entry['object'] instanceof DbConn)) continue;
        if (@$entry['function'] == 'exec') {
            $db_call_key = $entry_num;
            unset($entry['args']);
        }
    }
    if ($db_call_key > -1) {
        $backtrace = array_slice($backtrace, $db_call_key);
    }
    $top_entry = end($backtrace);
    $line = $top_entry['line'];
    $backtrace = create_backtrace_string($backtrace);
    
    if ($_SERVER['SERVER_NAME'] == '') {
        $uname = posix_uname ();
        
        if ($_SERVER['SCRIPT_NAME'][0] == '/') {
            $file_location = $_SERVER['SCRIPT_NAME'];
        } else {
            $file_location = ($_SERVER['PWD'] != ''? $_SERVER['PWD']. '/': ''). $_SERVER['SCRIPT_NAME'];
        }
        
        $occurred = "This occurred on {$uname['nodename']}, on line {$line} of\n{$file_location}";
        
    } else {
        $proto_host = get_proto_host ();
        $full_url = $proto_host. $_SERVER['REQUEST_URI'];
        if (substr ($_SERVER['REQUEST_URI'], 0, strlen ($_SERVER['SCRIPT_NAME'])) != $_SERVER['SCRIPT_NAME']) {
            $full_url = $proto_host. $_SERVER['SCRIPT_NAME'].
                ($_SERVER['QUERY_STRING'] != ''? '?'. $_SERVER['QUERY_STRING']: '');
        }
        
        $occurred = "This occurred on line {$line} of\n{$full_url}";
    }
    
    $site_name = Runtime::get('site_name');
    $message = "{$site_name} had the following slow query";
    if ($unoptimised) {
        $message .= ", which should be optimised:";
    } else {
        $message .= ", which could indicate a DB performance problem:";
    }
    $message .= "\n\n{$query}\n\n";
    $message .= "Time taken: {$time_taken} ms ";
    $message .= "(hoping for {$max_time} ms or less)\n\n";
    $message .= "{$occurred}\n\n";
    if ($explain) $message .= "EXPLAIN:\n" . format_explain($explain);
    $message .= "See also: http://dev.mysql.com/doc/refman/5.5/en/using-explain.html\n\n";
    if ($backtrace) $message .= "BACKTRACE:\n{$backtrace}\n\n";
    $message .= get_email_footer_info ();
    
    // Send the email to the developers
    $site_error_emails = preg_split ('/,\s*/', SITE_EMAILS_ERROR);
    $subject = 'Slow query on ' . Runtime::get('site_name');
    foreach ($site_error_emails as $admin) {
        mail($admin, $subject, $message, 'From: ' . SITE_EMAIL);
    }
}


/**
 * Converts a MySQL date to standard Australian DD/MM/YYYY (Y2K Compliant)
 * 
 * @param string $date The date to be formatted
 * @param string $s The separator to be used in the output date ('/' by default)
 * @return string The formatted date
 */
function standardise_mysql_date ($date, $s = '/') {
    list ($y,$m,$d) = explode ('-', $date);
    $d = substr ($d,0,2);
    return $d . $s . $m . $s . $y;
}

/**
 * Converts a MySQL date to standard Australian DD/MM/YY (Short format - not Y2K Compliant)
 * 
 * @param string $date The date to be formatted
 * @param string $s The separator to be used in the output date ('/' by default)
 * @return string The formatted date
 */
function standardise_mysql_date_short ($date, $s = '/') {
    list ($y, $m, $d) = explode ('-', $date);
    $d = substr ($d, 0, 2);
    $y = substr ($y, 2, 2);
    return $d . $s . $m . $s . $y;
}


/**
 * Makes a datum ready for SQL-insertion by adding enclosing single quotes if necessary.
 * Note: this function also escapes the datum
 * 
 * @author benno, 2007-01-04 initial version
 * @author benno, 2007-06-13 added special handling for QueryFieldLiterals
 * @author benno, 2008-07-15 added detect_numbers parameter
 * 
 * @param mixed $datum The datum to enclose, if necessary
 * @param bool $detect_numbers Whether or not to check if a string datum is numeric. If true, and the datum is
 *     a string that looks like a number, e.g. '2000' or '3.1415', the datum will not be quoted
 * @return mixed The datum which is enclosed if required, either as a string or an integer
 */
function sql_enclose ($datum, $detect_numbers = true) {
    if ($datum === null) {
        $datum = 'NULL';
    } else if ($datum instanceof QueryField) {
        $datum = cast_to_string ($datum);
    } else if (!is_int ($datum)) {
        $datum = (string) $datum;
        if (!$detect_numbers or !preg_match ('/^\-?[0-9]+(\.[0-9]+)?$/', $datum)) {
            $conn = ConnManager::get_active();
            $datum = $conn->quote($datum);
        }
    }
    return $datum;
}


/**
 * Converts a list of values for an SQL ENUM type into an array containing those values
 * String escaping within the ENUM type declaration should be handled nicely
 * 
 * @author benno, 2009-07-16
 * 
 * @param $string The string from which to extract the ENUM values, e.g. "'Value1','It''s value 2'"
 * @return array
 */
function enum_to_array ($string) {
    
    $array = array ();
    
    $pos = 0;
    $in_str = false;
    $length = strlen ($string);
    $sub_string = '';
    while ($pos < $length) {
        $char = $string[$pos];
        if ($in_str and $char == '\\') {
            $next = $string[$pos + 1];
            if ($next == "'") {
                $sub_string .= "'";
                $pos += 2;
                continue;
            } else if ($next == '\\') {
                $sub_string .= '\\';
                $pos += 2;
                continue;
            }
        }
        if ($char == "'") {
            if ($in_str) {
                if (@$string[$pos + 1] == "'") {
                    $sub_string .= "'";
                    $pos += 2;
                    continue;
                }
                $array[] = $sub_string;
                $sub_string = '';
                $in_str = false;
            } else {
                $in_str = true;
            }
        } else {
            if ($in_str) $sub_string .= $char;
        }
        $pos++;
    }
    if ($in_str) $array[] = $sub_string;
    
    return $array;
}
?>
