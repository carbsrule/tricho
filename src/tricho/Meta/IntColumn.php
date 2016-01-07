<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DataValidationException;

/**
 * Stores meta-data about a column that stores integer data
 * @package meta_xml
 */
class IntColumn extends NumericColumn {
    private $auto_inc;
    
    
    static function getAllowedSqlTypes () {
        return array ('TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT', 'BIT');
    }
    
    static function getDefaultSqlType () {
        return 'INT';
    }
    
    
    function getMaxLength() {
        switch ($this->sqltype) {
        case 'TINYINT': return 3;
        case 'SMALLINT': return 5;
        case 'MEDIUMINT': return 7;
        case 'INT': return 10;
        case 'BIGINT': return 19;
        case 'BIT': return 1;
        default: return 0;
        }
    }
    
    
    function setAutoInc ($value) {
        // temp code
        if (!is_array ($this->sql_attributes)) {
            $this->sql_attributes = (array) $this->sql_attributes;
        }
        if ($value) {
            $this->sql_attributes[] = 'AUTO_INCREMENT';
        } else {
            $key = array_search ('AUTO_INCREMENT');
            if ($key !== false) {
                unset ($this->sql_attributes[$key]);
            }
        }
    }
    
    
    function collateInput ($input, &$original_value) {
        $value = trim ($input);
        $original_value = $value;
        if ($value == '') {
            $value = null;
        } else {
            if (!preg_match ('/^-?[0-9]+$/', $value)) {
                throw new DataValidationException ('Must be an integer');
            }
            settype ($value, 'int');
            $min_failed = false;
            $max_failed = false;
            if ($this->min !== null and $value < (int) $this->min) $min_failed = true;
            if ($this->max !== null and $value > (int) $this->max) $max_failed = true;
            if ($min_failed and $max_failed) {
                throw new DataValidationException ("Must be at least {$this->min} and at most {$this->max}");
            }
            if ($min_failed) {
                throw new DataValidationException ("Must be at least {$this->min}");
            }
            if ($max_failed) {
                throw new DataValidationException ("Must be at most {$this->max}");
            }
            if ($value < 0 and $this->isUnsigned ()) {
                throw new DataValidationException ('Must be at least 0');
            }
        }
        return array ($this->name => $value);
    }
}
?>
