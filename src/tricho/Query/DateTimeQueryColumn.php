<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use Exception;

/**
 * Additional information for a QueryColumn
 * so that it can support dates, times and both
 *
 * @package query_builder
 * @author Josh (4 June 2007)
 */
class DateTimeQueryColumn extends QueryColumn {
    
    // Variables
    private $min_year;
    private $max_year;
    private $include_date;
    private $include_time;
    private $date_format = '%d/%m/%Y';
    
    
    function __toString () {
        
        if (defined ('UPPER_CASE_AM_PM') and (UPPER_CASE_AM_PM === true)) {
            $lc_am_pm = false;
        } else {
            $lc_am_pm = true;
        }
        $return_value = '';
        if ($lc_am_pm) $return_value .= 'LOWER(';
        $return_value .= 'DATE_FORMAT('. $this->table->identify ('normal'). '.`'. $this->name.
            "`,'". $this->date_format."')";
        if ($lc_am_pm) $return_value .= ')';
        
        return $return_value;
        
    }
    
    // Initialisation
    /**
     * Set the extended details for dates
     *
     * @param string $min_year The minimum year. Supports actual values, as
     *        well as + and -
     * @param string $max_year The maximum year. Supports actual values, as
     *        well as + and -
     */
    public function setDateDetails($min_year, $max_year) {
        
        $min_year = (string) $min_year;
        $max_year = (string) $max_year;
        
        // + or - for min year
        if ($min_year[0] == '+') {
            $min_year = date('Y') + substr($min_year, 1);
        } elseif ($min_year[0] == '-') {
            $min_year = date('Y') - substr($min_year, 1);
        }

        // + or - for max year
        if ($max_year[0] == '+') {
            $max_year = date('Y') + substr($max_year, 1);
        } elseif ($max_year[0] == '-') {
            $max_year = date('Y') - substr($max_year, 1);
        }
        
        // idiot check
        if ($min_year > $max_year) {
            $min_year = date('Y');
            $max_year = date('Y');
        }
        
        // store
        $this->min_year = $min_year;
        $this->max_year = $max_year;
        $this->include_date = true;
    }
    
    /**
     * identifies the column in a particular context
     * 
     * for example, MyTable.MyColumn with alias MyAlias would be returned as
     * "MyTable.MyColumn MyAlias" in 'select', which is used for listing the
     * SELECT columns. It would be returned as "MyTable.MyColumn" in 'normal',
     * which is used for performing JOINs. It would be returned as "MyAlias"
     * in 'row', which is used when looping through the result set.
     * 
     * @param string $context: the context used to identify the column:
     *        'select', 'normal', 'order_by', or 'row'.
     * @return string
     */
    function identify ($context) { //    = -1
        switch (strtolower($context)) {
            case 'select':
                if (defined ('UPPER_CASE_AM_PM') and (UPPER_CASE_AM_PM === true)) {
                    $lc_am_pm = false;
                } else {
                    $lc_am_pm = true;
                }
                $return_value = '';
                if ($lc_am_pm) $return_value .= 'LOWER(';
                $return_value .= 'DATE_FORMAT('. $this->table->identify ('normal'). '.`'. $this->name.
                    "`,'". $this->date_format."')";
                if ($lc_am_pm) $return_value .= ')';
                $return_value .= " AS `" . ($this->alias != '' ? $this->alias : $this->name) . '`';
                return $return_value;
                break;
                
            case 'normal':
                return $this->table->identify ('normal'). '.`'.
                    ($this->alias != '' ? $this->alias : $this->name). '`';
                break;
            
            case 'param':
                if (defined ('UPPER_CASE_AM_PM') and (UPPER_CASE_AM_PM === true)) {
                    $lc_am_pm = false;
                } else {
                    $lc_am_pm = true;
                }
                $return_value = '';
                if ($lc_am_pm) $return_value .= 'LOWER(';
                $return_value .= 'DATE_FORMAT('. $this->table->identify ('normal'). '.`'. $this->name.
                    "`,'". $this->date_format."')";
                if ($lc_am_pm) $return_value .= ')';
                return $return_value;
                
            case 'order_by':
                return $this->table->identify ('normal') . '.`'. $this->name. '`';
                break;
                
            case 'row':
                return $this->alias == ''? $this->name: $this->alias;
                break;
                
            default:
                throw new Exception ("Invalid context {$context}, must be 'select', ".
                    "'normal', 'order_by', or 'row'");
        }
    }
    
    /**
     * Set the extended details for times
     */
    public function setTimeDetails() {
        $this->include_time = true;
    }
    
    
    // Column type
    /**
     * Does this column support dates?
     */
    public function isDateColumn() {
        return $this->include_date;
    }
    
    /**
     * Does this column support times?
     */
    public function isTimeColumn() {
        return $this->include_time;
    }
    
    
    // Params
    /**
     * Get the date minimum year
     */
    public function getMinYear() {
        return $this->min_year;
    }
    
    /**
     * Get the date maximum year
     */
    public function getMaxYear() {
        return $this->max_year;
    }
    
    /**
     * Set the date format for this column
     * Needs to be in MySQL format
     */
    public function setDateFormat($format) {
        if ($format != '') {
            $this->date_format = $format;
        }
    }
}

?>
