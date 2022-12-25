<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho;

use InvalidArgumentException;

use Tricho\Meta\Column;

/**
 * Stores functions related to metadata
 */
class Meta
{
    
    /**
     * Converts a value (integer or boolean) to a 'y' or a 'n'.
     * @param mixed $value
     * @return string
     */
    static function toYesNo($value)
    {
        if ((int) $value == 1) {
            return 'y';
        } else {
            return 'n';
        }
    }
    
    
    /**
     * Converts 'y' to 1 and 'n' to 0.
     * @param mixed $value
     * @return int
     */
    static function toNum($value)
    {
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
    static function toBool($value)
    {
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
     * Converts a value (integer or boolean) to a 'y', 'n' or 'i' (i=inherit).
     * @param mixed $value
     * @return string
     */
    static function toYesNoInherit($value)
    {
        if ($value === null) return 'i';
        if ((int) $value == 1) {
            return 'y';
        } else {
            return 'n';
        }
    }
    
    
    /**
     * Converts 'y' to true, 'n' to false and 'i' to null
     * @param mixed $value
     * @return mixed
     */
    static function toBoolInherit($value)
    {
        switch (strtolower($value)) {
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
     * Converts a value to its associated row type name
     * @param int $value
     * @return string
     */
    static function toRowType($value)
    {
        if ($value == TABLE_DISPLAY_STYLE_TREE) {
            return 'tree';
        } else {
            return 'rows';
        }
    }
    
    
    /**
     * Converts an access level (e.g. TABLE_ACCESS_SETUP_LIMITED) into its
     * equivalent string for XML storage
     * 
     * @author benno, 2008-07-02
     * 
     * @param int $value
     * @return string
     */
    static function toAccessString($value)
    {
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
     * Gets the SQL definition for a column
     * @param Column $col The column
     * @return string
     */
    static function getSqlDefn(Column $col)
    {
        $type = $col->getSqlType();
        $sql = $type;
        $txt = $col->getSqlSize();
        if ($txt != '') {
            $sql .= "({$txt})";
        }
        $txt = implode(' ', $col->getSqlAttributes());
        if ($txt != '') {
            $sql .= " {$txt}";
        }
        
        $default = $col->getDefault();
        if ($default !== null) {
            $sql .= ' DEFAULT ' . sql_enclose($default);
        }
        
        $collation = $col->getCollation();
        if ($collation != '') {
            $sql .= " COLLATE {$collation}";
        }
        
        return $sql;
    }
    
    
    /**
     * Gets the type, size and other attributes (eg UNSIGNED) from an SQL
     * column definition.
     * 
     * @param string $str The column definition, e.g. INT (10) UNSIGNED
     *        AUTO_INCREMENT
     * @return array 3 strings representing type, size, and attributes
     * @author benno 2011-08-18 rewritten using regex
     */
    static function getSqlParams($str)
    {
        $str = trim($str);
        $matches = [];
        $pattern = '/^([a-zA-Z]+) *(\([0-9]+(, *[0-9]+)?\))?/';
        $result = preg_match($pattern, $str, $matches);
        if (!$result) {
            throw new InvalidArgumentException('Invalid column definition');
        }
        $type = $matches[1];
        $size = str_replace(' ', '', trim($matches[2] ?? '', '()'));
        $attribs = trim(substr($str, strlen($matches[0])));
        return [$type, $size, $attribs];
    }
}
