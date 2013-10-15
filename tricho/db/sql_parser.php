<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

class SQLParser {
    const TEST = false;
    const WHITESPACE = 0;
    const COMMENT = 1;
    const QUERY = 2;
    const STRING_SINGLE = 3;
    const STRING_DOUBLE = 4;
    const QUOTE_SINGLE = 5;
    const QUOTE_DOUBLE = 6;
    const BACKSLASH = 7;
    
    static $state_names = array(
        self::WHITESPACE => 'WS',
        self::COMMENT => 'C',
        self::QUERY => 'Q',
        self::STRING_SINGLE => 'S1',
        self::STRING_DOUBLE => 'S2',
        self::QUOTE_SINGLE => "'",
        self::QUOTE_DOUBLE => '"',
        self::BACKSLASH => "\\"
    );
    
    function __construct () {
        $this->tokens = array ();
    }
    
    public function __toString () {
        return __CLASS__;
    }
    
    function getTokens () {
        return $this->tokens;
    }
    
    function parse ($sql_str, $queries = array ()) {
        
        global $db;
        
        $this->tokens = array ();
        
        $sql_str = (string) $sql_str;
        
        if (!is_array ($queries)) {
            throw new Exception ('$queries must be an array');
        }
        
        $curr_query = '';
        $curr_pos = 0;
        $curr_state = self::WHITESPACE;
        $last_pos = strlen ($sql_str);
        $num_dashes = 0;
        $curr_token = '';
        $token_value = '';
        
        if (self::TEST) {
            echo "<table style=\"font-size: 9px;\">\n";
            echo "<tr><th>State</th><th>Pos</th><th>Char</th></tr>\n";
        }
        
        $prev_state = null;
        
        while ($curr_pos < $last_pos) {
            $curr_char = $sql_str[$curr_pos];
            if (self::TEST) {
                echo "<tr><td>", self::$state_names[$curr_state],
                    "</td><td>{$curr_pos}</td><td>{$curr_char}</td></tr>\n";
            }
            
            if ($curr_state != self::COMMENT) {
                $num_dashes = 0;
            }
            
            switch ($curr_state) {
                case self::WHITESPACE:
                    switch ($curr_char) {
                        case "\n":
                        case "\t":
                        case "\r":
                        case ' ':
                            break;
                        
                        case '-':
                            $num_dashes = 1;
                            $curr_query .= $curr_char;
                            $curr_state = self::COMMENT;
                            break;
                        
                        case '#':
                            $curr_query .= $curr_char;
                            $curr_state = self::COMMENT;
                            break;
                        
                        default:
                            $curr_query .= $curr_char;
                            $curr_state = self::QUERY;
                            $token_value .= $curr_char;
                            $token = array ('type' => self::QUERY, 'value' => '');
                            break;
                    }
                    break;
                
                case self::COMMENT:
                    if ($num_dashes == 1 and $curr_char != '-') {
                        $curr_state = self::QUERY;
                        $curr_query .= $curr_char;
                        break;
                    }
                    if ($curr_char == "\n" or $curr_char == "\r") {
                        $queries[] = $curr_query;
                        $curr_query = '';
                        $curr_state = self::WHITESPACE;
                    } else {
                        if ($curr_char == '-') $num_dashes = 2;
                        $curr_query .= $curr_char;
                    }
                    break;
                
                case self::QUERY:
                    $original_state = $curr_state;
                    if ($curr_char == ';') {
                        $queries[] = $curr_query;
                        $curr_query = '';
                        $curr_state = self::WHITESPACE;
                    } else if ($curr_char == "'") {
                        $curr_query .= $curr_char;
                        $token_value .= $curr_char;
                        $curr_state = self::STRING_SINGLE;
                    } else if ($curr_char == '"') {
                        $curr_query .= $curr_char;
                        $token_value .= $curr_char;
                        $curr_state = self::STRING_DOUBLE;
                    } else {
                        $curr_query .= $curr_char;
                        $token_value .= $curr_char;
                    }
                    if ($original_state != $curr_state) {
                        $token['value'] = $token_value;
                        $this->tokens[] = $token;
                        $token = array ('type' => $curr_state, 'value' => '');
                        $token_value = '';
                    }
                    break;
                
                case self::STRING_SINGLE:
                    $curr_query .= $curr_char;
                    $token_value .= $curr_char;
                    if ($curr_char == "'") {
                        $curr_state = self::QUOTE_SINGLE;
                    } else if ($curr_char == "\\") {
                        $prev_state = self::STRING_SINGLE;
                        $curr_state = self::BACKSLASH;
                    }
                    break;
                
                case self::STRING_DOUBLE:
                    $curr_query .= $curr_char;
                    $token_value .= $curr_char;
                    if ($curr_char == '"') {
                        $curr_state = self::QUOTE_DOUBLE;
                    } else if ($curr_char == "\\") {
                        $prev_state = self::STRING_DOUBLE;
                        $curr_state = self::BACKSLASH;
                    }
                    break;
                
                case self::QUOTE_SINGLE:
                    $original_state = self::STRING_SINGLE;
                    $curr_query .= $curr_char;
                    if ($curr_char == "'") {
                        $curr_state = self::STRING_SINGLE;
                        $token_value .= $curr_char;
                    } else if ($curr_char == ';') {
                        $queries[] = $curr_query;
                        $curr_query = '';
                        $curr_state = self::WHITESPACE;
                    } else {
                        $curr_state = self::QUERY;
                    }
                    if ($original_state != $curr_state) {
                        if ($curr_state == self::QUERY) {
                            $token['value'] = substr ($token_value, 0, strlen ($token_value) - 1);
                        } else {
                            $token['value'] = $token_value;
                        }
                        $this->tokens[] = $token;
                        $token = array ('type' => $curr_state, 'value' => '');
                        $token_value = '';
                        if ($curr_state == self::QUERY) $token_value .= "'". $curr_char;
                    }
                    break;
                
                case self::QUOTE_DOUBLE:
                    $original_state = self::STRING_DOUBLE;
                    $curr_query .= $curr_char;
                    if ($curr_char == '"') {
                        $curr_state = self::STRING_DOUBLE;
                        $token_value .= $curr_char;
                    } else if ($curr_char == ';') {
                        $queries[] = $curr_query;
                        $curr_query = '';
                        $curr_state = self::WHITESPACE;
                    } else {
                        $curr_state = self::QUERY;
                    }
                    if ($original_state != $curr_state) {
                        if ($curr_state == self::QUERY) {
                            $token['value'] = substr ($token_value, 0, strlen ($token_value) - 1);
                        } else {
                            $token['value'] = $token_value;
                        }
                        $this->tokens[] = $token;
                        $token = array ('type' => $curr_state, 'value' => '');
                        $token_value = '';
                        if ($curr_state == self::QUERY) $token_value .= '"'. $curr_char;
                    }
                    break;
                
                case self::BACKSLASH:
                    $curr_query .= $curr_char;
                    $curr_state = $prev_state;
                    $token_value .= $curr_char;
                    break;
                
                default:
                    report_error ("SQL parser in unknown state at position ". $curr_pos);
                    die ();
            }
            $curr_pos++;
            if ($curr_pos == $last_pos) {
                $token['value'] = $token_value;
                $this->tokens[] = $token;
                $token = array ('type' => $curr_state, 'value' => '');
                $token_value = '';
            }
        }
        
        if (self::TEST) {
            echo "</table>\n";
        }
        
        if ($curr_query != '') {
            $queries[] = $curr_query;
        }
        
        return $queries;
        
    }
    
}

/**
 * strips a comment from an SQL query and returns it
 */
function get_comment (&$str) {
    
    $matches = array ();
    preg_match ('/^--\s*(.*)$/m', $str, $matches);
    
    // echo "MATCHING $str WITH --: ", print_r ($matches, true), "<br>\n";
    
    if (count($matches) > 0) {
        $str = preg_replace ('/^--.*$/m', '', $str, 1);
        return trim ($matches[1]);
    }
    
    $matches = array ();
    preg_match ('/^#\s*(.*)$/m', $str, $matches);
    
    // echo "MATCHING $str WITH #: ", print_r ($matches, true), "<br>\n";
    
    if (count($matches) > 0) {
        $str = preg_replace ('/^#.*$/m', '', $str, 1);
        return trim ($matches[1]);
    }
    
    return false;
}
?>
