<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DataValidationException;


/**
 * Column to store an Australian postcode
 * Allowed values from Australia Post PDF, Aug 2015
 */
class PostcodeColumn extends InputColumn {
    protected $sql_size = 4;
    
    static function getAllowedSqlTypes() {
        return ['CHAR'];
    }
    
    static function getDefaultSqlType() {
        return 'CHAR';
    }
    
    function setSqlSize($str) {
        // Ignore request; size is always 4
    }
    
    
    /**
     * Gets the data posted from a form
     * @param mixed $data Data submission, e.g. $_POST['AwesomeField']
     * @param mixed $original_value A value into which to store the submitted
     *        data after it has been collated (even if it's invalid), so that
     *        it can be retained for use in a later submission
     * @return array DB field names and their values. Note that a single Column
     *         might actually map to multiple columns in the database.
     * @throws DataValidationException if the input data isn't valid
     */
    function collateInput($input, &$original_value) {
        $value = (string) $input;
        $original_value = $value;
        if (!preg_match('/^[0-9]{4}$/', $input)) {
            throw new DataValidationException('Invalid format');
        }
        
        $value = (int) $value;
        $ok = false;
        
        // ACT
        if (($value >= 200 and $value <= 299)
            or ($value >= 2600 and $value <= 2619)
            or ($value >= 2900 and $value <= 2920))
        {
            $ok = true;
            
        // NSW
        } else if (($value >= 1000 and $value <= 2599)
            or ($value >= 2620 and $value <= 2899)
            or ($value >= 2921 and $value <= 2999))
        {
            $ok = true;
            
        // NT
        } else if ($value >= 800 and $value <= 999) {
            $ok = true;
            
        // Queensland
        } else if (($value >= 4000 and $value <= 4999)
            or ($value >= 9000 and $value <= 9999))
        {
            $ok = true;
            
        // South Australia
        } else if ($value >= 5000 and $value <= 5999) {
            $ok = true;
            
        // Tasmania
        } else if ($value >= 7000 and $value <= 7999) {
            $ok = true;
            
        // Victoria
        } else if (($value >= 3000 and $value <= 3999)
            or ($value >= 8000 and $value <= 8999))
        {
            $ok = true;
            
        // Western Australia
        } else if ($value >= 6000 and $value <= 6999) {
            $ok = true;
        }
        
        if (!$ok) throw new DataValidationException('Unknown postcode');
        
        return [$this->name => $value];
    }
    
}
