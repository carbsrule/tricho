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
 * Stores meta-data about a column that stores decimal data
 * @package meta_xml
 */
class DecimalColumn extends NumericColumn {
    private $prefix;
    
    static function getAllowedSqlTypes () {
        return array ('DECIMAL');
    }
    
    static function getDefaultSqlType () {
        return 'DECIMAL';
    }
    
    
    function getMaxLength() {
        list($all, $after_decimal) = preg_split('/, */', $this->sql_size);
        return $all + 1;
    }
    
    
    /**
     * Sets a prefix for numbers stored in this table,
     * e.g. for currency
     */
    function setPrefix ($prefix) {
        $this->prefix = (string) $prefix;
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
            
            // strip negative while checking lengths of parts
            $negative = false;
            if ($value[0] == '-') {
                $negative = true;
                $value = substr ($value, 1);
            }
            @list($int_part, $frac_part) = explode('.', $value);
            @list($full_len, $frac_len) = preg_split('/, */', $this->sql_size);
            settype($full_len, 'int');
            settype($frac_len, 'int');
            $int_len = $full_len - $frac_len;
            if ($int_part != '0' and strlen ($int_part) > $int_len) {
                $max = "{$int_len} ". ($int_len == 1? 'digit is': "digits are");
                throw new DataValidationException ("A maximum of {$max} allowed before the decimal point");
            }
            if ($frac_part != '0' and strlen ($frac_part) > $frac_len) {
                $max = "{$frac_len} ". ($frac_len == 1? 'digit is': "digits are");
                throw new DataValidationException ("A maximum of {$max} allowed after the decimal point");
            }
            
            // reinstate negative to do range checking
            if ($negative) $value = '-'. $value;
            
            $min_failed = false;
            $max_failed = false;
            if ($this->min !== null and StringNumber::compare ($value, $this->min) < 0) $min_failed = true;
            if ($this->max !== null and StringNumber::compare ($value, $this->max) > 0) $max_failed = true;
            if ($min_failed and $max_failed) {
                throw new DataValidationException ("Must be at least {$this->min} and at most {$this->max}");
            }
            if ($min_failed) {
                throw new DataValidationException ("Must be at least {$this->min}");
            }
            if ($max_failed) {
                throw new DataValidationException ("Must be at most {$this->max}");
            }
            if ($this->isUnsigned () and StringNumber::compare ($value, '0.0') < 0) {
                throw new DataValidationException ('Must be at least 0');
            }
        }
        if ($value === null and !$this->isNullAllowed) $value = '0.0';
        return array ($this->name => $value);
    }
}
?>
