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
 * Stores meta-data about a column that stores floating point data
 * @package meta_xml
 */
class FloatColumn extends NumericColumn {
    static function getAllowedSqlTypes () {
        return array ('FLOAT', 'DOUBLE');
    }
    
    static function getDefaultSqlType () {
        return 'DOUBLE';
    }
    
    
    function collateInput ($input, &$original_value) {
        $value = trim ($input);
        $original_value = $value;
        if ($value == '') {
            $value = null;
        } else {
            if (!preg_match ('/^-?[0-9]+(?:\.[0-9]+)?$|^-?\.[0-9]+$/', $value)) {
                throw new DataValidationException ('Must be a decimal number');
            }
            settype ($value, 'float');
            $min_failed = false;
            $max_failed = false;
            if ($this->min !== null and $value < (float) $this->min) $min_failed = true;
            if ($this->max !== null and $value > (float) $this->max) $max_failed = true;
            if ($min_failed and $max_failed) {
                throw new DataValidationException ("Must be at least {$this->min} and at most {$this->max}");
            }
            if ($min_failed) {
                throw new DataValidationException ("Must be at least {$this->min}");
            }
            if ($max_failed) {
                throw new DataValidationException ("Must be at most {$this->max}");
            }
            if ($value < 0.0 and $this->isUnsigned ()) {
                throw new DataValidationException ('Must be at least 0.0');
            }
        }
        if ($value === null and !$this->isNullAllowed) $value = 0.0;
        return array ($this->name => $value);
    }
}
?>
