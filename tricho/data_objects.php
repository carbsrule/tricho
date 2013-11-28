<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package meta_xml
 */


/**
 * Converts 'y' to 1 and 'n' to 0.
 */
function to_num ($value) {
    switch (strtolower($value)) {
        case 'y':
        case '1':
            return 1;
        case 'n':
        case '0':
            return 0;
    }
}

/**
 * Converts 'y', 'yes', and '1' to true, and anything else to false
 * 
 * @param string $value the string for conversion
 * @return bool
 */
function to_bool ($value) {
    switch (strtolower($value)) {
        case 'y':
        case 'yes':
        case '1':
            return true;
            break;
            
        case 'n':
        case 'no':
        case '0':
            return false;
    }
}

/**
 * Converts 'y' to true, 'n' to false and 'i' to null
 */
function to_bool_i ($value) {
    switch (strtolower ($value)) {
        case 'i':
            return null;
        case 'y':
        case '1':
            return true;
        case 'n':
        case '0':
            return false;
    }
}


/**
 * Gets the type, size and other attributes (eg UNSIGNED) from a SQL column definition
 * 
 * @param string $str The column definition. e.g. INT (10) UNSIGNED AUTO_INCREMENT
 * @return array 3 strings representing the following: type, size, attributes
 * @author benno 2011-08-18 rewritten using regex
 */
function get_sql_params ($str) {
    $str = trim ($str);
    $matches = array ();
    $result = preg_match ('/^([a-zA-Z]+) *(\([0-9]+(, *[0-9]+)?\))?/', $str, $matches);
    if (!$result) {
        throw new InvalidArgumentException ('Invalid column definition');
    }
    $type = $matches[1];
    $size = str_replace(' ', '', trim(@$matches[2], '()'));
    $attribs = trim (substr ($str, strlen ($matches[0])));
    return array ($type, $size, $attribs);
}
