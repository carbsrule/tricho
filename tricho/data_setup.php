<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package data_setup
 */


/**
 * Convert a value (integer or boolean) to a 'y' or a 'n'.
 */
function to_yn ($value) {
    if ((int) $value == 1) {
        return 'y';
    } else {
        return 'n';
    }
}

/**
 * Convert a value (integer or boolean) to a 'y' a 'n' or an 'i' (i=inherit).
 */
function to_yni ($value) {
    if ($value === null) return 'i';
    if ((int) $value == 1) {
        return 'y';
    } else {
        return 'n';
    }
}

/**
 * Convert a value to its accociated row type name
 */
function to_row_type ($value) {
    if ($value == TABLE_DISPLAY_STYLE_TREE) {
        return 'tree';
    } else {
        return 'rows';
    }
}


/**
 * Converts an access level (e.g. TABLE_ACCESS_SETUP_LIMITED) into its equivalent string for XML storage
 * 
 * @author benno, 2008-07-02
 * 
 * @param int $value
 * @return string
 */
function to_access_string ($value) {
    switch ($value) {
        case TABLE_ACCESS_ADMIN:
            return 'admin';
            break;
        
        case TABLE_ACCESS_SETUP_LIMITED:
            return 'setup-limited';
            break;
        
        default:
            return 'setup-full';
            break;
    }
}


/**
 * Get the SQL definition for a column
 * @param Column $col The column
 * @return string
 */
function get_sql_defn (Column $col) {
    $type = sql_type_string_from_defined_constant($col->getSqlType());
    $sql = $type;
    $txt = $col->getSqlSize ();
    if ($txt != '') {
        $sql .= "({$txt})";
    }
    $txt = implode (' ', $col->getSqlAttributes ());
    if ($txt != '') {
        $sql .= " {$txt}";
    }
    
    $default = $col->getDefault ();
    if ($default !== null) {
        $sql .= ' DEFAULT '. sql_enclose ($default);
    }
    
    $collation = $col->getCollation();
    if ($collation != '') {
        $sql .= " COLLATE {$collation}";
    }
    
    return $sql;
}
